<?php namespace Common\Models;

trait HasEmbeddedArrayModels
{

    /**
     * @param array $attributes
     * @param bool  $sync
     *
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        foreach ($attributes as $key => &$value) {

            if (empty($value)) {
                continue;
            }

            if (isset($this->arrayModels[$key])) {
                $class = $this->arrayModels[$key];
                $value = $this->callConstructingFunction($class, $value);
                continue;
            }

            if (isset($this->multiArrayModels[$key])) {
                $class = $this->multiArrayModels[$key];
                $value = array_map(function ($elem) use ($class) {
                    return $this->callConstructingFunction($class, $elem);
                }, $value);
            }

        }

        return parent::setRawAttributes($attributes, $sync);
    }

    /**
     * @param $class
     * @param $value
     *
     * @return mixed
     */
    private function callConstructingFunction($class, $value)
    {
        if (str_contains($class, '::')) {
            return $class($value);
        }

        return new $class($value);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (! empty($value) && ($nested = explode('.', $key))) {

            if (count($nested) == 2 && isset($this->arrayModels[$nested[0]])) {
                $this->{$nested[0]}->{$nested[1]} = $value;
                return $this;
            }
            if (count($nested) == 3 && isset($this->multiArrayModels[$nested[0]])) {
                $item = $this->{$nested[0]}[$nested[1]];
                $item->{$nested[2]} = $value;
                return $this;
            }
        }

        return parent::setAttribute($key, $value);
    }
}