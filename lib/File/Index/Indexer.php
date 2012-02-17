<?php

class File_Index_Indexer
{
    /**
     * @var array
     */
    protected $columns;

    /**
     * @var File_Index_Interface
     */
    protected $index;

    /**
     * Get index instance.
     * 
     * @return File_Index_Interface
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Index the given row accordingly to internal column list to index.
     * 
     * @param array $row
     *   Key/value pairs, keys are fields and values are associated values.
     *   Fields can be numeric.
     */
    public function index($index, array $row)
    {
        $numericIndex = array_keys($row);
        $conditions = array();

        if (isset($this->columns)) {
            foreach ($this->columns as $field) {
                if (is_numeric($field)) {
                    if (isset($numericIndex[$field])) {
                        $conditions[$field] = $row[$numericIndex[$field]];
                    }
                } else {
                    if (isset($row[$field])) {
                        $conditions[$field] = $row[$field];
                    }
                }
            }
        } else {
            $i = 0;
            foreach ($row as $field => $value) {
                if (is_numeric($field)) {
                    $conditions[$field] = $value;
                } else {
                    $conditions[$field] = $value;
                    $conditions[$i] = $value;
                }
                ++$i;
            }
        }

        $this->index->setIndex($index, $conditions);
    }

    /**
     * Default constructor.
     * 
     * @param File_Index_Interface $index
     * @param array $columns = null
     *   Columns to index. It can contain both strings and numeric values, even
     *   if both are redundant.
     */
    public function __construct(File_Index_Interface $index, array $columns = null)
    {
        $this->index = $index;
        $this->columns = $columns;
    }
}
