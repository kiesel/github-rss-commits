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
    
    /**
     * Fetch commits for given repo
     * 
     * @param  string $owner
     * @param  string $repo 
     * @param  util.Date $since default NULL
     * @return var
     */
    public function commitsForRepository($owner, $repo, Date $since= NULL) {
      $client= new RestClient('https://api.github.com/');
      $client->setTrace(Logger::getInstance()->getCategory($this->getClassName()));
      $req= new RestRequest('/repos/{owner}/{repo}/commits');
      $req->addSegment('owner', $owner);
      $req->addSegment('repo', $repo);
      if (NULL !== $since) {
        $req->addParameter('since', $since->toString(DATE_ISO8601));
      }

      $resp= $client->execute($req);

      if (HttpConstants::STATUS_NOT_FOUND == $resp->status()) {
        throw new ElementNotFoundException('No commits.');
      }

      if (HttpConstants::STATUS_OK !== $resp->status()) {
        throw new IllegalStateException('Could not fetch list of commits.');
      }

      return $resp->data();
    }
  }
?>