<?php

/**
 * Proxy index that will store and restore the given index from a file.
 * 
 * It uses an underlaying File_Index_Array instance for operating.
 */
class File_Index_File extends File_Index_Array
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var bool
     */
    protected $fileExists = false;

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return bool
     */
    public function fileExists()
    {
        return $this->fileExists;
    }

    /**
     * Force file to be saved.
     */
    public function save()
    {
        if (false === file_put_contents($this->filename, serialize($this->cache))) {
            throw new RuntimeException("Could not write '" . $this->filename . "'");
        }
        $this->fileExists = true;
    }

    /**
     * Default constructor.
     * 
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;

        if (file_exists($filename)) {
            if (!is_readable($filename)) {
                throw new RuntimeException("File '" . $this->filename . "' is not readable");
            }

            $this->cache = unserialize(file_get_contents($this->filename));

            if (!is_array($this->cache)) {
                throw new RuntimeException("File '" . $this->filename . "' contents are broken or is not a valid index file");
            }

            $this->fileExists = true;
        }
    }
}
