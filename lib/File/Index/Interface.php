<?php

/**
 * An index will live side by side an existing file.
 * 
 * The condition keyword refers to a given field/value couple, an array of
 * condition is then a key/value pairs: keys are fields, values are either
 * a single value for the field, or an array of valid values for the field
 * (OR capabilities).
 * 
 * Notice that not all index may implement the OR operation.
 * 
 * Also notice that fields can be numeric. 
 */
interface File_Index_Interface
{
    /**
     * Does this instance is empty.
     * 
     * @return bool
     */
    public function isEmpty();

    /**
     * Force index to be cleared.
     */
    public function flush();

    /**
     * Does this instance has been modified.
     * 
     * @return bool
     */
    public function isUpdated();

    /**
     * Get field keys.
     * 
     * @return array
     */
    public function getFieldKeys();

    /**
     * Get number of field/value couple stored.
     * 
     * @return int
     */
    public function getSize();

    /**
     * Does this instance has rows with the given field/value couple.
     * 
     * @param string|int $field
     * @param scalar $value
     * 
     * @return bool
     */
    public function hasRows($field, $value);

    // public function hasRowByCondition($conditions);

    /**
     * Get the rows that matches the given field/value couple.
     * 
     * @param string|int $field
     * @param scalar $value
     * 
     * @return array
     *   Array of row indexes.
     */
    public function getRows($field, $value);

    // public function getRowsByConditions($conditions);

    /**
     * Set index.
     * 
     * @param mixed $index
     *   Value that will be fetched back when calling on of the getters.
     *   For example, if you are working on a CSV file, this would be the
     *   line index (or line starting position in the file).
     * @param array $conditions
     *   Array of conditions associated to the line.
     */
    public function setIndex($index, $conditions);
}
