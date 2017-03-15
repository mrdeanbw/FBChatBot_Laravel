<?php namespace Common\Models;

abstract class ArrayModel
{

    protected $dates = [];

    public function __construct($data = [], $strict = false)
    {
        foreach ($data as $key => $value) {
            if ($strict && ! property_exists($this, $key)) {
                continue;
            }

            if ($value && $this->isDate($key)) {
                $value = mongo_date($value);
            }

            $this->{$key} = $value;
        }
    }

    /**
     * @param $key
     * @return bool
     */
    public function isDate($key)
    {
        return in_array($key, $this->dates);
    }
}