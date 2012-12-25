<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses(
    'lang.ResourceProvider',
    'name.kiesel.github.GitHubApiFacade',
    'name.kiesel.github.mustache.GitHubCommitView',
    'scriptlet.HttpScriptlet',
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
    private $owner = NULL;
    private $repo  = NULL;

    /**
     * Constructor
     *
     */
    public function __construct() {
      // HACK:
      require_once($_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php');
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

      list ($owner, $repo)= explode('/', $path);
      $this->owner= $owner;
      $this->repo= $repo;

      if (empty($this->owner) || empty($this->repo)) {
        throw new IllegalArgumentException('Path must be /rss/{owner}/{repo}/');
      }
    }

    /**
     * Perform GET request
     *
     * @param   scriptlet.HttpScriptletRequest
     * @param   scriptlet.HttpScriptletResponse
     */
    public function doGet($request, $response) {

      $this->parseOwnerRepoFromURL($request->getURL());

      $api= new GitHubApiFacade();
      $commits= $api->commitsForRepository(
        $this->owner, 
        $this->repo, 
        DateUtil::addDays(Date::now(), -7)
      );
      Logger::getInstance()->getCategory()->debug($commits);
      foreach ($commits as $index => $commit) {
        $newcommit= $api->commitBySha($this->owner, $this->repo, $commit->getSha());

        // Replace original commit info
        $commits[$index]= $newcommit;
        break;
      }

      $tree= $this->commitsToRss($commits);

      $response->setContentType('application/rss+xml');
      $response->write($tree->getSource(0));
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
        'Commits of '.$this->owner.'/'.$this->repo,
        'https://github.com/'.$this->owner.'/'.$this->repo,
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
      // $commit= $this->prepareCommit($commit);
      Logger::getInstance()->getCategory()->debug('Processing', new GitHubCommitView($commit));

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