<?php
/*
 * This class is part of the XP Framework
 *
 */

  class GitHubCommit extends Object {
    protected
      $commit     = NULL,
      $committer  = NULL,
      $author     = NULL,
      $sha        = NULL,
      $url        = NULL,
      $parents    = NULL,
      $files      = NULL;

    /**
     * Set SHA
     *
     * @param string s
     */
    public function setSha($s) {
      $this->sha= $s;
    }

    /**
     * Retrieve SHA
     *
     * @return  string
     */
    public function getSha() {
      return $this->sha;
    }

    /**
     * Set parents
     *
     * @param   var p
     */
    public function setParents($p) {
      $this->parents= $p;
    }

    /**
     * Set commit
     *
     * @param   var c
     */
    public function setCommit($c) {
      $this->commit= $c;
    }

    /**
     * Retrieve commit
     *
     * @return  <string,var>
     */
    public function getCommit() {
      return $this->commit;
    }

    /**
     * Set commiter
     *
     * @param   var c
     */
    public function setCommitter($c) {
      $this->committer= $c;
    }

    /**
     * Set author
     *
     * @param   var a
     */
    public function setAuthor($a) {
      $this->author= $a;
    }

    /**
     * Retrieve author
     *
     * @return <string,var>
     */
    public function getAuthor() {
      return $this->author;
    }

    /**
     * Set URL
     *
     * @param string u
     */
    public function setURL($u) {
      $this->url= $u;
    }

    /**
     * Extract title out of message
     *
     * @param   string message
     * @return  string
     */
    public function getTitle() {
      if (FALSE !== ($pos= strpos($this->commit['message'], "\n"))) {
        return substr($this->commit['message'], 0, $pos);
      }

      return $this->commit['message'];
    }

    /**
     * Set files
     *
     * @param name.kiesel.github.dto.GitHubCommitFile[] f
     */
    public function setFiles($f) {
      $this->files= $f;
    }

    /**
     * Retrieve files
     *
     * @return name.kiesel.github.dto.GitHubCommitFile[]
     */
    public function getFiles() {
      return $this->files;
    }
  }
?>