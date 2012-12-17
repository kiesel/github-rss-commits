<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses(
    'lang.ResourceProvider',
    'name.kiesel.github.GitHubApiFacade',
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
      foreach ($commits as $index => $commit) {
        $newcommit= $api->commitBySha($this->owner, $this->repo, $commit['sha']);

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
     * Extract title out of message
     *
     * @param   string message
     * @return  string
     */
    private function titleIn($message) {
      if (FALSE !== ($pos= strpos($message, "\n"))) {
        return substr($message, 0, $pos);
      }

      return $message;
    }

    /**
     * Add commit to rss
     *
     * @param xml.rdf.RDFNewsFeed $feed
     * @param array $commit
     */
    private function addCommitTo(RDFNewsFeed $feed, $commit) {
      $commit['commit']['title']= $this->titleIn($commit['commit']['message']);
      $commit= $this->prepareCommit($commit);
      Logger::getInstance()->getCategory()->debug('Processing', $commit);

      $feed->addItem(
        $commit['commit']['title'],
        sprintf('https://github.com/%s/%s/commit/%s',
          $this->owner,
          $this->repo,
          $commit['sha']
        ),
        $this->renderCommitDetails($commit),
        new Date($commit['author']['date'])
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

    /**
     * Prepare commit
     *
     * @param   type name
     * @return  type
     * @throws  type description
     */
    private function prepareCommit($commit) {
      foreach ($commit['files'] as $index => $file) {
        $lines= array();
        foreach (explode("\n", $file['patch']) as $line) {
          if ('-' == $line{0}) {
            $lines[]= array('mode' => 'red', 'content' => $line);
          } else if ('+' == $line{0}) {
            $lines[]= array('mode' => 'green', 'content' => $line);
          } else {
            $lines[]= array('content' => $line);
          }
        }

        $commit['files'][$index]['patchlines']= $lines;
      }

      return $commit;
    }
  }
?>