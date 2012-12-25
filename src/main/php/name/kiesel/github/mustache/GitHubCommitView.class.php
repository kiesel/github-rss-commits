<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses(
    'name.kiesel.github.dto.GitHubCommit',
    'name.kiesel.github.mustache.GitHubCommitFileView'
  );

  class GitHubCommitView extends Object {
    private $commit   = NULL;

    /**
     * Constructor
     *
     * @param   type name
     * @return  type
     * @throws  type description
     */
    public function __construct(GitHubCommit $c) {
      $this->commit= $c;
    }

    public function commit() {
      return $this->commit->getCommit();
    }

    /**
     * Retrieve sha
     *
     * @param   type name
     * @return  type
     * @throws  type description
     */
    public function sha() {
      return $this->commit->getSha();
    }

    /**
     * Retrieve title
     *
     * @return  string
     */
    public function title() {
      return $this->commit->getTitle();
    }

    /**
     * Retrieve whether we have a patch
     *
     * @param   type name
     * @return  type
     * @throws  type description
     */
    public function haveFiles() {
      return sizeof($this->commit->getFiles());
    }

    /**
     * Retrieve list of files
     *
     * @param   type name
     * @return  type
     * @throws  type description
     */
    public function files() {
      $files= array();
      foreach ($this->commit->getFiles() as $file) {
        $files[]= new GitHubCommitFileView($file);
      }

      return $files;
    }
  }
?>