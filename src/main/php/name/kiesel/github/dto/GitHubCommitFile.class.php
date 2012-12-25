<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses();

  class GitHubCommitFile extends Object {
    private
      $filename   = NULL,
      $additions  = NULL,
      $deletions  = NULL,
      $changes    = NULL,
      $status     = NULL,
      $patch      = NULL;

    /**
     * Set filename
     *
     * @param string f
     */
    public function setFileName($f) {
      $this->filename= $f;
    }

    /**
     * Retrieve filename
     *
     * @return string
     */
    public function getFileName() {
      return $this->filename;
    }

    /**
     * Set additions
     *
     * @param int a 
     */
    public function setAdditions($a) {
      $this->additions= $a;
    }

    /**
     * Retrieve additions
     *
     * @return int
     */
    public function getAdditions() {
      return $this->additions;
    }

    /**
     * Set deletions
     *
     * @param int d 
     */
    public function setDeletions($d) {
      $this->deletions= $d;
    }

    /**
     * Retrieve filename
     *
     * @return string
     */
    public function getDeletions() {
      return $this->deletions;
    }

    /**
     * Set changes
     *
     * @param int c
     */
    public function setChanges($c) {
      $this->changes= $c;
    }

    /**
     * Set status
     *
     * @param string s 
     */
    public function setStatus($s) {
      $this->status= $s;
    }

    /**
     * Set patch
     *
     * @param string p 
     */
    public function setPatch($p) {
      $this->patch= $p;
    }

    /**
     * Retrieve patch
     *
     * @return  string
     */
    public function getPatch() {
      return $this->patch;
    }
  }
?>