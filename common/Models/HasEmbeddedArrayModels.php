<?php namespace Common\Models;

trait HasEmbeddedArrayModels
{

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (! empty($value) && ($nested = explode('.', $key))) {
            if (count($nested) == 2 && isset($this->embedded[$nested[0]])) {
                if (is_array($this->embedded[$nested[0]])) {
                    foreach ($this->{$nested[0]} as $object) {
                        $object->{$nested[1]} = $value;
                    }
                } else {
                    $object = $this->{$nested[0]};
                    $object->{$nested[1]} = $value;
                }

                return $this;
            }

            if (count($nested) == 3 && isset($this->embedded[$nested[0]]) && is_array($this->embedded[$nested[0]])) {
                $object = $this->{$nested[0]}[$nested[1]];
                $object->{$nested[2]} = $value;

                return $this;
            }
        }

        return parent::setAttribute($key, $value);
    }
}