<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses(
    'lang.ResourceProvider',
    'name.kiesel.github.GitHubApiFacade',
    'name.kiesel.github.mustache.GitHubCommitView',
    'security.oauth2.OAuth2Client',
    'security.oauth2.GithubOAuth2Provider',
    'scriptlet.HttpScriptlet',
    'scriptlet.Cookie',
    'util.Date',
    'util.DateUtil',
    'xml.rdf.RDFNewsFeed',
    'io.File',
    'io.FileUtil'
  );

  /**
   * RSS Scriptlet
   *
   */
  class RssScriptlet extends HttpScriptlet {
    private $owner    = NULL;
    private $repo     = NULL;
    private $branch   = NULL;

    private static $OAUTH_SCOPE = array();
    private $oauth    = NULL;
    private $cat      = NULL;

    /**
     * Constructor
     *
     */
    public function __construct() {
      // HACK:
      require_once($_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php');
      $this->cat= $this->catFor('default');
    }

    /**
     * Retrieve log category
     *
     * @param   type name
     * @return  type
     * @throws  type description
     */
    private function catFor($name) {
      return Logger::getInstance()->getCategory($name);
    }

    /**
     * Parse owner & repo from URL
     *
     * @param   peer.URL url
     * @throws  lang.IllegalArgumentException in case path could not be parsed
     */
    private function parseOwnerRepoFromURL(URL $url) {
      $path= $url->getPath();
      if ('/rss/' == substr($path, 0, 5)) {
        $path= substr($path, 5);
      }

      @list ($owner, $repo, $branch)= explode('/', $path);
      $this->branch= 'master';
      $this->owner= $owner;
      $this->repo= $repo;
      $branch && $this->branch= $branch;

      if (empty($this->owner) || empty($this->repo)) {
        throw new IllegalArgumentException('Path must be /rss/{owner}/{repo}/[{branch}]');
      }
    }

    /**
     * Perform OAuth login
     *
     * @param   scriptlet.HttpScriptletRequest request
     * @param   scriptlet.HttpScriptletResponse response
     * @return  bool
     */
    private function oauthLogin($request, $response) {
      $prop= PropertyManager::getInstance()->getProperties('oauth2');

      $this->oauth= new OAuth2Client(new GithubOAuth2Provider());
      $this->oauth->setClientId($prop->readString('oauth', 'clientId'));
      $this->oauth->setClientSecret($prop->readString('oauth', 'clientSecret'));
      $this->oauth->setTrace($this->catFor($this->oauth->getClassName()));

      if ($request->hasCookie('token')) {
        $this->oauth->setAccessTokenRaw($request->getCookie('token')->getValue());
        $this->cat->info('Received oauth token', $this->oauth->getAccessToken());
        return TRUE;
      }

      if ($request->hasParam('code')) {
        $token= $this->oauth->authenticate($request->getParam('code'));
        $this->cat->info('OAuth2 Stage 3: Received oauth token', $token);

        // Set cookie w/ oauth information, valid until in a year...
        $response->setCookie(new Cookie('token', $this->oauth->getAccessToken(), DateUtil::addMonths(Date::now(), 12)));

        // Redirect to same URL w/o code parameter (enable clean page refresh)
        $url= $request->getUrl();
        $url->removeParam('code');

        $this->cat->info('OAuth2 Stage 2: Processed code, redirecting to', $url->getURL());
        $response->sendRedirect($url->getURL());

        return FALSE;
      }

      // No oauth information, yet - redirect...
      $this->oauth->setRedirectUri($request->getURL()->getURL());
      $url= $this->oauth->createAuthURL(self::$OAUTH_SCOPE);
      $this->cat->info('OAuth2 Stage 1: No oauth information, redirecting to', $url);
      $response->sendRedirect($url);
      return FALSE;
    }

    /**
     * Perform GET request
     *
     * @param   scriptlet.HttpScriptletRequest
     * @param   scriptlet.HttpScriptletResponse
     */
    public function doGet($request, $response) {
      $this->cat->mark();
      $this->parseOwnerRepoFromURL($request->getURL());
      $this->cat->info($this->getClassName(), '~ fetching for', $this->owner.'/'.$this->repo.' branch '.$this->branch);

      // Perform OAuth2 login...
      if (FALSE === $this->oauthLogin($request, $response)) return;

      $api= new GitHubApiFacade();
      $api->setTrace($this->catFor($api->getClassName()));
      $api->setOAuth($this->oauth);

      // Fetch information for master branch
      $branch= $api->referenceByName($this->owner, $this->repo, 'heads/'.$this->branch);
      $this->cat->info($this->getClassName(), '~ have branch', $branch);

      // Fetch commits on master branch
      $commits= $api->commitsBySha($this->owner, $this->repo, $branch['object']['sha']);
      $this->cat->info($this->getClassName(), '~ delivering', sizeof($commits), 'commits');

      // Enrich all commits
      foreach ($commits as $index => $commit) {
        $newcommit= $api->commitBySha($this->owner, $this->repo, $commit->getSha());

        // Replace original commit info
        $commits[$index]= $newcommit;
      }

      $tree= $this->commitsToRss($commits);

      $response->setContentType('application/rss+xml');
      $response->write($tree->getSource(0));

      $api->dumpApiStatus();
    }

    /**
     * Adds a list of commits as rss items
     *
     * @param  array  $commits
     * @return xml.rdf.RDFNewsFeed
     */
    private function commitsToRss(array $commits) {
      $rss= new RDFNewsFeed();
      $rss->setChannel(
        'Commits of '.$this->owner.'/'.$this->repo.' on '.$this->branch,
        'https://github.com/'.$this->owner.'/'.$this->repo.'/tree/'.$this->branch,
        'Overview of commits',
        Date::now()
      );

      foreach ($commits as $commit) {
        $this->addCommitTo($rss, $commit);
      }

      return $rss;
    }

    /**
     * Add commit to rss
     *
     * @param xml.rdf.RDFNewsFeed $feed
     * @param array $commit
     */
    private function addCommitTo(RDFNewsFeed $feed, $commit) {
      $feed->addItem(
        $commit->getTitle(),
        sprintf('https://github.com/%s/%s/commit/%s',
          $this->owner,
          $this->repo,
          $commit->getSha()
        ),
        $this->renderCommitDetails(new GitHubCommitView($commit)),
        new Date($commit->getCommit()['author']['date'])
      );
    }

    /**
     * Render body of commit details
     *
     * @param   array commit
     * @return  string
     */
    private function renderCommitDetails($commit) {
      $mustache= new Mustache_Engine(array(
      ));
      return $mustache->render(
        FileUtil::getContents(new File('res://mustache/commitdetails.mustache')),
        $commit
      );
    }

  }
?>