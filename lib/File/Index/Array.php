<?php

/**
 * In memory array index.
 */
class File_Index_Array implements File_Index_Interface
{
    /**
     * @var bool
     */
    protected $isUpdated = false;

    /**
     * Internal field cache. This is a multidimensional array, first dimension
     * is indexed by field names, second by values, third by values. The final
     * values you get are array of lines matching the condition.
     * 
     * @var array
     */
    protected $cache = array();

    public function isEmpty()
    {
        return empty($this->cache);
    }

    public function flush()
    {
        $this->cache = array();
    }

    public function isUpdated()
    {
        return $this->isUpdated;
    }

    public function getFieldKeys()
    {
        return array_keys($this->cache);
    }

    public function getSize()
    {
        $count = 0;

        foreach ($this->cache as $field => $value) {
            $count += count($value);
        }

        return $count;
    }

    public function hasRows($field, $value)
    {
        return !empty($this->cache[$field][$value]);
    }

    public function getRows($field, $value)
    {
        if (isset($this->cache[$field][$value])) {
            return $this->cache[$field][$value];
        } else {
            return array();
        }
    }

    public function setIndex($index, $conditions)
    {
        foreach ($conditions as $field => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                $this->cache[$field][$value][] = $index;
            }
        }
        $this->isUpdated = true;
    }
}
