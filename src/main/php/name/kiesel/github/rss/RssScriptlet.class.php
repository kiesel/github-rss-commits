<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses(
    'name.kiesel.github.GitHubApiFacade',
    'scriptlet.HttpScriptlet',
    'util.Date',
    'util.DateUtil',
    'xml.rdf.RDFNewsFeed'
  );

  class RssScriptlet extends HttpScriptlet {
    private $owner = NULL;
    private $repo  = NULL;
    
    /**
     * Parse owner & repo from URL
     *
     * @param   type name
     * @return  type
     * @throws  type description
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

      $tree= $this->commitsToRss($commits);

      $response->setContentType('application/rss+xml');
      $response->write($tree->getSource(0));

    }

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

    private function addCommitTo(RDFNewsFeed $feed, $commit) {
      $feed->addItem(
        $commit['commit']['message'],
        $commit['commit']['url'],
        '<p>Authored by '.$commit['commit']['author']['name'].'<br/>'.
        '<small>Committed by '.$commit['commit']['committed']['name'].' on '.$commit['commit']['date'].'</small>'.
        '</p><p>'.$commit['commit']['message'].'</p>',
        new Date($commit['author']['date'])
      );
    }
  }
?>