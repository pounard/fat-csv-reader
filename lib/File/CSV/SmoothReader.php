<?php

class File_CSV_SmoothReader implements Iterator, Countable
{
    /**
     * @var bool
     */
    protected $decentFgetcsv = false;

    /**
     * @var bool
     */
    protected $forceContentTrim = true;

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
    protected $eofReached = false;

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

    /**
     * @var bool
     */
    protected $isCountReliable;

    /**
     * Current file pointer position found by fgetscsv().
     * 
     * @var int
     */
    protected $currentFileIndex = 0;

    /**
     * Get current line position in file stream.
     * 
     * @return int
     */
    public function getCurrentFileIndex()
    {
        return $this->currentFileIndex;
    }

    public function getHeaders()
    {
        $this->init();

        return $this->headers;
    }

    public function hasHeader($name)
    {
        return in_array($name, $this->getHeaders());
    }

    protected function handleIsValid()
    {
        return isset($this->handle) && false !== $this->handle;
    }

    protected function fetchNextLine()
    {
        $this->currentFileIndex = ftell($this->handle);

        // Prepare some parameters.
        $l = $this->settings['length'];
        $d = $this->settings['delimiter'];
        $e = $this->settings['enclosure'];
        $c = $this->settings['escape'];
        $line = null;
        $this->line = null;

        // PHP 5.3 accepts the 'escape' parameters, using it on PHP 5.2 will make
        // the fgetcsv() function throw a warning and not return any array.
        if ($this->decentFgetcsv) {
            $line = fgetcsv($this->handle, $l, $d, $e, $c);
        } else {
            $line = fgetcsv($this->handle, $l, $d, $e);
        }

        // Check for reading sanity.
        if (false === $line) {
            if (feof($this->handle)) {
                // We reached the end of file, but our object is still valid. Reset
                // the buffer to empty but leave the rest as-is.
                $this->eofReached = true;
            } else {
                throw new RuntimeException("Error while reading the file '" . $this->filename . "'");
            }
        } else {
            $this->line = $line;
        }
    }

    protected function init()
    {
        if (!$this->handleIsValid()) {

            $this->handle = fopen($this->filename, "r");

            if (false === $this->handle) {
                if (feof($this->handle)) {
                    throw new RuntimeException("File '" . $this->filename . "' is empty");
                } else {
                    throw new RuntimeException("Cannot fopen() file '" . $this->filename . "'");
                }
            }

            $this->fetchNextLine();

            if (false === $this->line) {
                throw new RuntimeException("Empty CSV file");
            } else {
                foreach ($this->line as $header) {
                    $this->headers[] = trim($header);
                }
            }

            // Position the stream over the real first item.
            $this->fetchNextLine();
        }
    }

    protected function close()
    {
        if (isset($this->handle)) {
            fclose($this->handle);
            unset($this->handle);
        }
    }

    protected function reset()
    {
        $this->close();
        $this->eofReached = false;
        $this->offset = 0;
        $this->line = null;
        $this->headers = null;
    }

    public function count()
    {
        return $this->countApproximation;
    }

    public function isCountReliable()
    {
        return $this->isCountReliable;
    }

    public function current()
    {
        if ($this->eofReached) {
            return null;
        } else {
            if (isset($this->line)) {
                return $this->formatLine($this->line);
            } else {
                return null;
            }
        }
    }

    protected function formatLine($line)
    {
        $ret = array();

        foreach ($line as $key => $value) {
            if (isset($this->headers[$key])) {
                if ($this->forceContentTrim) {
                    $ret[$this->headers[$key]] = trim($value);
                } else {
                    $ret[$this->headers[$key]] = $value;
                }
            } else {
                if ($this->forceContentTrim) {
                    $ret[] = trim($value);
                } else {
                    $ret[] = $value;
                }
            }
        }

        return $ret;
    }

    public function next()
    {
        $this->init();
        $this->fetchNextLine();
        ++$this->offset;
    }

    public function key()
    {
        return $this->offset;
    }

    public function valid()
    {
        $this->init();

        return !$this->eofReached && isset($this->line);
    }

    public function rewind()
    {
        $this->reset();
    }

    protected function checkFile()
    {
        if (!file_exists($this->filename)) {
            throw new RuntimeException("File '" . $this->filename . "' does not exists");
        }
        if (!is_readable($this->filename)) {
            throw new RuntimeException("File '" . $this->filename . "' cannot be read");
        }

        // Set the count approximation.
        if (shell_exec("which cat")) {
            $this->countApproximation = ((int)shell_exec("cat " . escapeshellcmd($this->filename) . " | wc -l")) - 1;
            $this->isCountReliable = true;
        } else {
            $this->countApproximation = (int)filesize($this->filename) / 100;
            $this->isCountReliable = false;
        }
    }

    public function __construct($filename, $settings = array())
    {
        $this->settings = $settings + array(
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'length' => 2048,
        );

        $this->forceContentTrim = true;
        $this->filename = $filename;
        $this->checkFile();
        $this->decentFgetcsv = (version_compare(PHP_VERSION, '5.3.0') >= 0);
    }

    public function __destruct()
    {
        $this->close();
    }
}
