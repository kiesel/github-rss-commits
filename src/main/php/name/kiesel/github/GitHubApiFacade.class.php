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
    'util.log.Logger'
  );    

  class GitHubApiFacade extends Object {
    private $oauth  = NULL;

    /**
     * Fetch client
     *
     * @return  webservices.rest.RestClient
     */
    private function client() {
      $client= new RestClient('https://api.github.com/');
      $client->setTrace(Logger::getInstance()->getCategory($this->getClassName()));
      return $client;
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
      $resp= $this->client()->execute($req);

      if (HttpConstants::STATUS_NOT_FOUND == $resp->status()) {
        throw new ElementNotFoundException('No commits.');
      }

      if (HttpConstants::STATUS_OK !== $resp->status()) {
        throw new IllegalStateException('Could not fetch list of commits.');
      }

      return $resp->data($hint);
    }
    
    /**
     * Fetch commits for given repo
     * 
     * @param  string $owner
     * @param  string $repo 
     * @param  util.Date $since default NULL
     * @return var
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
     * @return webservices.rest.RestResponse
     */
    public function commitBySha($owner, $repo, $sha) {
      $req= new RestRequest('/repos/{owner}/{repo}/commits/{sha}');
      $req->addSegment('owner', $owner);
      $req->addSegment('repo', $repo);
      $req->addSegment('sha', $sha);

      return $this->apiRequest($req, 'name.kiesel.github.dto.GitHubCommit');
    }
  }
?>