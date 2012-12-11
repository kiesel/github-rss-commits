<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses(
    'util.cmd.Command',
    'webservices.rest.RestClient',
    'webservices.rest.RestRequest',
    'peer.http.HttpConstants',
    'lang.ElementNotFoundException'
  );

  class ShowNotifyBySha1 extends Command {
    private $sha1   = NULL;
    private $owner  = NULL,
    private $repo   = NULL;

    /**
     * Set sha1 of commit to notify
     *
     * @param   string sha1
     */
    #[@arg]
    public function setSha1($sha1) {
      $this->sha1= $sha1;
    }

    /**
     * Set owner
     *
     * @param   string o
     */
    #[@arg]
    public function setOwner($o) {
      $this->owner= $o;
    }

    /**
     * Set repo
     *
     * @param   string r
     */
    #[@arg]
    public function setRepo($r) {
      $this->repo= $r;
    }

    /**
     * Run
     *
     */
    public function run() {
      $this->out->writeLine('---> Fetching info for commit ', $this->sha1);

      $this->outputCommit($this->fetchCommit($this->sha1));
    }

    /**
     * Fetch commit from GitHub API
     *
     */
    private function fetchCommit($sha1) {
      $client= new RestClient('https://api.github.com/');
      $request= new RestRequest('/repos/{owner}/{repo}/commits/{sha}');
      $request->addSegment('owner', $this->owner);
      $request->addSegment('repo', $this->repo);
      $request->addSegment('sha', $this->sha1);

      $response= $client->execute($request);

      if (HttpConstants::STATUS_NOT_FOUND == $response->status()){
        throw new ElementNotFoundException('No such commit '.$this->sha1);
      }

      if (HttpConstants::STATUS_OK !== $response->status()) {
        throw new IllegalStateException('Something went wrong.');
      }

      return $response->data();
    }

    /**
     * Description of method
     *
     */
    private function outputCommit($commit) {
      $this->out->writeLine('Commit ', $commit['sha']);
      $this->out->writeLine('Author', $commit['commit']['login']);
      $this->out->writeLine();
      $this->out->writeLine();
      
    }
  }
?>