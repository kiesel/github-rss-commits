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

  /**
   * RSS Scriptlet
   *
   */
  class RssScriptlet extends HttpScriptlet {
    private $owner = NULL;
    private $repo  = NULL;
    
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
      Logger::getInstance()->getCategory()->debug('Processing', $commit);

      $feed->addItem(
        $this->titleIn($commit['commit']['message']),
        sprintf('https://github.com/%s/%s/commit/%s',
          $this->owner,
          $this->repo,
          $commit['sha']
        ),
        $this->prepareCommitDetails($commit),
        new Date($commit['author']['date'])
      );
    }

    /**
     * Prepare body of commit details
     *
     * @param   array commit
     * @return  string
     */
    private function prepareCommitDetails($commit) {
      $s= '<h1><img src="'.$commit['author']['avatar_url'].'" align="left" hspace="2" vspace="2"/>'.
        $this->titleIn($commit['commit']['message']).'<br clear="all"/></h1>'.
        '<h2>'.$this->changedBy('Authored by', $commit['commit']['author'], $commit['author']).', '.
        $this->changedBy('committed by', $commit['commit']['committer'], $commit['committer']).'</h2>'.
        '<p><pre>'.nl2br($commit['commit']['message']).'</pre></p>'.
        '<p>Overall stats: '.sprintf('%d additions, %d deletions, %d total',
          $commit['stats']['additions'],
          $commit['stats']['deletions'],
          $commit['stats']['total']).'</p>';

      $s.= '<h2>File details</h2>';
      foreach ($commit['files'] as $file) {
        $s.= '<h3><a href="'.$file['raw_url'].'">'.$file['filename'].'</a></h3>';
        $s.= '<p>'.$this->formatPatch($file['patch']).'</p>';
      }

      return $s;
    }

    /**
     * Add changed by
     *
     * @param string
     * @param <string,string> info
     * @param <string,string> user
     * @return string
     */
    private function changedBy($intro, $info, $user) {
      return $intro.' <a href="https://github.com/'.$user['login'].'">'.$info['name'].'</a> on '.$info['date'];
    }

    /**
     * Format patch for HTML
     *
     * @param   string patch
     * @return  string
     */
    private function formatPatch($patch) {
      $s= '<pre>';
      foreach (explode("\n", $patch) as $line) {
        $style= '';
        if ('+' == $line{0}) {
          $style= 'green';
        } else if ('-' == $line{0}) {
          $style= 'red';
        }

        if ($style) {
          $s.= '<font color="'.$style.'">'.$line.'</font><br/>';
        } else {
          $s.= $line.'<br/>';
        }
      }

      return $s.'</pre><hr/>';
    }
  }
?>