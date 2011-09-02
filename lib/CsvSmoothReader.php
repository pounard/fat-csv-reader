<?php

class CsvSmoothReader implements Iterator, Countable {
  /**
   * @var bool
   */
  protected $decentFgetcsv = FALSE;

  /**
   * @var resource
   */
  protected $handle;

  /**
   * @var string
   */
  protected $filename;

  /**
   * @var int
   */
  protected $offset = 0;

  /**
   * @var bool
   */
  protected $eofReached = FALSE;

  /**
   * @var array
   */
  protected $settings;

  /**
   * @var array
   */
  protected $headers;

  /**
   * @var array
   */
  protected $line;

  /**
   * @var int
   */
  protected $countApproximation;

  public function getHeaders() {
    if (!isset($this->headers)) {
      $this->next();
    }
    return $this->headers;
  }

  protected function handleIsValid() {
    return isset($this->handle) && FALSE !== $this->handle;
  }

  protected function init() {
    if (!$this->handleIsValid()) {
      $this->handle = fopen($this->filename, "r");
      if (FALSE === $this->handle) {
        if(feof($this->handle)) {
          throw new Exception("File '" . $this->filename . "' is empty");
        } else {
          throw new Exception("Cannot fopen() file '" . $this->filename . "'");
        }
      }
    }
  }

  protected function close() {
    if (isset($this->handle)) {
      fclose($this->handle);
      unset($this->handle);
    }
  }

  protected function reset() {
    $this->close();
    $this->eofReached = FALSE;
    $this->offset = 0;
    $this->line = NULL;
    // next() method will wipe out first row only if headers are not set, in
    // case of rewing we need to remove this first row again.
    unset($this->headers);
  }

  public function count() {
    return $this->countApproximation;
  }

  public function current() {
    if ($this->eofReached) {
      return NULL;
    } else {
      if (!isset($this->line)) {
        $this->next();
      }
      return $this->line; 
    }
  }

  protected function formatLine($line) {
    $ret = array();
    foreach ($line as $key => $value) {
      if (isset($this->headers[$key])) {
        $ret[$this->headers[$key]] = $value;
      } else {
        $ret[] = $value;
      }
    }
    return $ret;
  }

  public function next() {
    if (!$this->handleIsValid()) {
      $this->init();
    }

    // Prepare some parameters.
    $l = $this->settings['length'];
    $d = $this->settings['delimiter'];
    $e = $this->settings['enclosure'];
    $c = $this->settings['escape'];
    $line = NULL;
    $this->line = NULL;

    // PHP 5.3 accepts the 'escape' parameters, using it on PHP 5.2 will make
    // the fgetcsv() function throw a warning and not return any array.
    if ($this->decentFgetcsv) {
      $line = fgetcsv($this->handle, $l, $d, $e, $c);
    } else {
      $line = fgetcsv($this->handle, $l, $d, $e);
    }

    // Check for reading sanity.
    if (FALSE === $line) {
      if (feof($this->handle)) {
        // We reached the end of file, but our object is still valid. Reset
        // the buffer to empty but leave the rest as-is.
        $this->eofReached = TRUE;
        return;
      } else {
        throw new Exception("Error while reading the file '" . $this->filename . "'");
      }
    }

    if (!isset($this->headers)) {
      $this->headers = $line;
    } else {
      ++$this->offset;
      $this->line = $this->formatLine($line);
    }
  }

  public function key() {
    return $this->offset;
  }

  public function valid() {
    $this->init();
    return !$this->eofReached;
  }

  public function rewind() {
    $this->reset();
  }

  protected function checkFile() {
    if (!file_exists($this->filename)) {
      throw new Exception("File '" . $this->filename . "' does not exists");
    }
    if (!is_readable($this->filename)) {
      throw new Exception("File '" . $this->filename . "' cannot be read");
    }

    // Set the count approximation.
    if (shell_exec("which cat")) {
      $this->countApproximation = (int)shell_exec("cat " . escapeshellcmd($this->filename) . " | wc -l");
    } else {
      $this->countApproximation = (int)filesize($this->filename) / 100;
    }
  }

  public function __construct($filename, $settings = array()) {
    $this->settings = $settings + array(
      'delimiter' => ',',
      'enclosure' => '"',
      'escape' => '\\',
      'length' => 2048,
    );
    $this->filename = $filename;
    $this->checkFile();
    $this->decentFgetcsv = (version_compare(PHP_VERSION, '5.3.0') >= 0);
  }

  public function __destruct() {
    $this->close();
  }
}
