<?php namespace Common\Models;

abstract class ArrayModel
{

    public function __construct($data = [], $strict = false)
    {
        foreach ($data as $key => $value) {
            if ($strict && ! property_exists($this, $key)) {
                continue;
            }
            $this->{$key} = $value;
        }
    }
}