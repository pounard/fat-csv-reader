<?php

/**
 * This implementation allows the user to seek into the CSV file easily. It
 * maintains an index in memory, so it can be resource intensive for your
 * PHP script: do not use this on huge CSV files.
 * 
 * The file index will be lazzy-built at the first seek() function hit, so
 * if you do not actually use it, it will behave exactly the same as the
 * legacy reader.
 * 
 * If the seek() method actually find more than one rows matching, order is
 * not guarantee and it may return a random line, depending on the given
 * index implementation.
 * 
 * Even if the seek method could potentially work with any indexed field, for
 * method signature consistency accross the SeekableIterator, it will always
 * only lookup for the primary key field.
 */
class File_CSV_IndexedSmoothReader extends File_CSV_SmoothReader implements SeekableIterator
{
    /**
     * @var File_Index_Interface 
     */
    protected $index;

    /**
     * When object is instanciated, it can be a string, this will changed at
     * the first init() method call to the associated column number.
     * 
     * @var int
     */
    protected $primaryKeyColumn;

    /**
     * @var array
     */
    protected $indexColumns;

    /**
     * Current key.
     * 
     * @var mixed
     */
    protected $currentKey;

    /**
     * @var bool
     */
    protected $consistencyChecked = false;

    /**
     * Check that index columns really exists in file.
     * 
     * @return bool
     */
    protected function checkColumns()
    {
        $this->getHeaders();
        $headerCount = count($this->headers);

        return true;
    }

    /**
     * Build CSV index.
     * 
     * You can call this function publicly to force the index build whenever
     * you want if you need this time consumption to be predictable.
     */
    public function buildIndex()
    {
        if (!isset($this->index)) {
            $this->index = new File_Index_Array;
        } else if (!$this->index->isEmpty()) {
            return;
        }

        $this->reset();
        $this->init();

        $indexer = new File_Index_Indexer($this->index, $this->indexColumns);

        while (!$this->eofReached) {
            $indexer->index($this->currentFileIndex, $this->current());
            $this->next();
        }
    }

    public function fetchNextLine()
    {
        parent::fetchNextLine();

        if (0 !== $this->offset) {
            if (isset($this->line)) {
                $this->currentKey = $this->line[$this->primaryKeyColumn];
            } else {
                $this->currentKey = null;
            }
        }
    }

    public function init()
    {
        parent::init();

        if ($this->consistencyChecked) {
            return;
        }

        if (!is_numeric($this->primaryKeyColumn)) {
            $i = 0;
            foreach ($this->headers as $field) {
                if ($this->primaryKeyColumn === $field) {
                    $this->primaryKeyColumn = $i;
                    break;
                }
                ++$i;
            }

            if (!is_numeric($this->primaryKeyColumn)) {
                throw new RuntimeException("Could not find the '" . $this->primaryKeyColumn . "' primary field header");
            }
        }

        if (!isset($this->indexColumns)) {
            $this->indexColumns = array($this->primaryKeyColumn);
        } else if (!in_array($this->primaryKeyColumn, $this->indexColumns)) {
            $this->indexColumns[] = $this->primaryKeyColumn;
        }

        $headerCount = count($this->headers);
        foreach ($this->indexColumns as $field) {
            if (is_numeric($field)) {
                if ($field < 0 || $headerCount < $field) {
                    throw new RuntimeException("Column number " . $field . " is out of CSV headers bound");
                }
            } else {
                if (!in_array($field, $this->headers)) {
                    throw new RuntimeException("Column '" . $field . "' does not exists in CSV headers");
                }
            }
        }

        $this->consistencyChecked = true;
    }

    public function key()
    {
        return $this->currentKey;
    }

    public function seek($position)
    {
        if (isset($this->index)) {
            // If index already has been built, ensure the file handle is still
            // opened: it could have been closed by the reset() method call.
            $this->init();
        } else {
            $this->buildIndex();
        }

        $rows = $this->index->getRows($this->primaryKeyColumn, $position);

        if (!empty($rows)) {
            fseek($this->handle, $rows[0]);
            $this->fetchNextLine();
            $this->currentKey = $position;
            $this->eofReached = false;
        } else {
            throw new OutOfBoundsException;
        }
    }

    /**
     * Default constructor.
     * 
     * @param string $filename
     * @param string $settings = array()
     * @param string|int $primaryKeyColumn = null
     *   If set, the given field will be used as primary key for the
     *   Iterator::key() method. Defaults to first column if nothing is
     *   here.
     * @param array $indexColumns = null
     *   Array of columns on which to index the file. Can contain both field
     *   names matching headers and numeric values.
     * @param File_Index_Interface $index = null
     *   Optionnaly, if you are persisting the file index, you can pass here
     *   either an empty instance or a filled instance of an index.
     *   If the given index instance is empty, it will be rebuilt.
     *   If you don't give any, it will internally build and use an array based
     *   File_Index_Array instance.
     */
    public function __construct($filename, $settings = array(),
        $primaryKeyColumn = null, array $indexColumns = null, File_Index_Interface $index = null)
    {
        parent::__construct($filename, $settings);

        $this->indexColumns = $indexColumns;

        if (isset($index)) {
            $this->index = $index;
        }

        if (isset($primaryKeyColumn)) {
            $this->primaryKeyColumn = $primaryKeyColumn;
        } else {
            $this->primaryKeyColumn = 0;
        }
    }
}
