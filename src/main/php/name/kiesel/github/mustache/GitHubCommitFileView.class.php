<?php
/*
 * This class is part of the XP Framework
 *
 */

  uses('name.kiesel.github.dto.GitHubCommitFile');

  class GitHubCommitFileView extends Object {
    private
      $file = NULL;

    /**
     * Constructor
     *
     * @param name.kiesel.github.dto.GitHubCommitFile file
     */
    public function __construct(GitHubCommitFile $file) {
      $this->file= $file;
    }

    /**
     * Retrieve patch lines
     *
     * @return  string[]
     */
    public function lines() {
      $lines= array();
      foreach (explode("\n", $this->file->getPatch()) as $line) {
        if ('-' == $line{0}) {
          $lines[]= array('mode' => 'red', 'content' => $line);
        } else if ('+' == $line{0}) {
          $lines[]= array('mode' => 'green', 'content' => $line);
        } else {
          $lines[]= array('content' => $line);
        }
      }

      return $lines;
    }

    /**
     * Retrieve filename
     *
     * @return string
     */
    public function filename() {
      return $this->file->getFileName();
    }

    /**
     * Additions
     *
     * @return int
     */
    public function additions() {
      return $this->file->getAdditions();
    }

    /**
     * Deletions
     *
     * @return  int
     */
    public function deletions() {
      return $this->file->getDeletions();
    }
  }
?>