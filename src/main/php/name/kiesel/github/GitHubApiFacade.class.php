<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses(
    'webservices.rest.RestClient',
    'webservices.rest.RestRequest',
    'util.Date',
    'lang.ElementNotFoundException',
    'util.log.Traceable'
  );    

  class GitHubApiFacade extends Object implements Traceable {
    private $oauth          = NULL;
    private $cat            = NULL;
    private $callsAvailable = NULL;
    private $callsRemaining = NULL;

    /**
     * Fetch client
     *
     * @return  webservices.rest.RestClient
     */
    private function client() {
      $client= new RestClient('https://api.github.com/');
      $this->cat && $client->setTrace($this->cat);
      return $client;
    }

    /**
     * Set log category
     *
     * @param   util.log.LogCategory cat default NULL
     */
    public function setTrace($cat= NULL) {
      $this->cat= $cat;
    }

    /**
     * Set oauth client
     *
     * @param   security.oauth2.OAuth2Client oauth
     */
    public function setOAuth(OAuth2Client $oauth) {
      $this->oauth= $oauth;
    }

    /**
     * Retrieve total available calls
     *
     * @return  int
     */
    public function callsAvailable() {
      return $this->callsAvailable;
    }

    /**
     * Retrieve calls remaining
     *
     * @return  int
     */
    public function callsRemaining() {
      return $this->callsRemaining;
    }

    /**
     * Perform API request
     *
     * @param   webservices.rest.RestRequest req
     * @return  array
     * @throws  lang.ElementNotFoundException for 404 response
     * @throws  lang.IllegalStateException for non-200 response
     */
    private function apiRequest(RestRequest $req, $hint= NULL) {
      if ($this->oauth instanceof OAuth2Client) {
        $req->addHeader($this->oauth->getAuthorization());
      }
      $this->cat && $this->cat->info($this->getClassName(), '~ calling ', $req->getTarget());

      $resp= $this->client()->execute($req);

      // Check GitHub api call limits
      $this->callsAvailable= $resp->header('X-RateLimit-Limit');
      $this->callsRemaining= $resp->header('X-RateLimit-Remaining');

      if (HttpConstants::STATUS_NOT_FOUND == $resp->status()) {
        throw new ElementNotFoundException('No commits.');
      }

      if (HttpConstants::STATUS_OK !== $resp->status()) {
        throw new IllegalStateException('Could not fetch list of commits.');
      }

      return $resp->data($hint);
    }

    /**
     * Dump api status to log
     *
     */
    public function dumpApiStatus() {
      $this->cat && $this->cat->info($this->getClassName(), 'Call limit:', $this->callsAvailable, '- calls remaining:', $this->callsRemaining);
    }

    /**
     * Fetch references
     *
     * @param   type name
     * @return  type
     * @throws  type description
     */
    public function referenceByName($owner, $repo, $name) {
      $req= new RestRequest('/repos/{owner}/{repo}/git/refs/{ref}');
      $req->addSegment('owner', $owner);
      $req->addSegment('repo', $repo);
      $req->addSegment('ref', $name);

      return $this->apiRequest($req);
    }
    
    /**
     * Fetch commits for given repo
     * 
     * @param  string $owner
     * @param  string $repo 
     * @param  util.Date $since default NULL
     * @return name.kiesel.github.dto.GitHubCommit[]
     */
    public function commitsForRepository($owner, $repo, Date $since= NULL) {
      $req= new RestRequest('/repos/{owner}/{repo}/commits');
      $req->addSegment('owner', $owner);
      $req->addSegment('repo', $repo);
      if (NULL !== $since) {
        $req->addParameter('since', $since->toString(DATE_ISO8601));
      }

      return $this->apiRequest($req, 'name.kiesel.github.dto.GitHubCommit[]');
    }

    /**
     * Fetch a single commit by its SHA1 hash
     *
     * @param string owner
     * @param string repo
     * @param string sha
     * @return name.kiesel.github.dto.GitHubCommit
     */
    public function commitBySha($owner, $repo, $sha) {
      $req= new RestRequest('/repos/{owner}/{repo}/commits/{sha}');
      $req->addSegment('owner', $owner);
      $req->addSegment('repo', $repo);
      $req->addSegment('sha', $sha);

      return $this->apiRequest($req, 'name.kiesel.github.dto.GitHubCommit');
    }

    /**
     * Retrieve all commits starting from given SHA
     *
     * @param string owner
     * @param string repo
     * @param string sha
     * @return name.kiesel.github.dto.GitHubCommit[]
     */
    public function commitsBySha($owner, $repo, $sha, $perPage= 10) {
      $req= new RestRequest('/repos/{owner}/{repo}/commits');
      $req->addSegment('owner', $owner);
      $req->addSegment('repo', $repo);
      $req->addParameter('sha', $sha);
      $req->addParameter('per_page', $perPage);

      return $this->apiRequest($req, 'name.kiesel.github.dto.GitHubCommit[]');
    }
  }
?>