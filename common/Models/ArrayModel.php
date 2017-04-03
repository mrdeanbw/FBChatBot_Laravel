<?php namespace Common\Models;

abstract class ArrayModel
{

    /**
     * ArrayModel constructor.
     * @param array $data
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            //            if ($value && $this->isDate($key)) {
            //                $value = mongo_date($value);
            //            }
            $this->{$key} = $value;
        }
    }

    //    /**
    //     * @param $key
    //     * @return bool
    //     */
    //    public function isDate($key)
    //    {
    //        return false;
    //    }

    public function __get($name)
    {
        if (! isset($this->{$name})) {
            return null;
        }

        return $this->{$name};
    }
}
