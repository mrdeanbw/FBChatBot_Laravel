<?php namespace App\Models;

/**
 * Class HasEmbeddedModels
 */
trait HasEmbeddedArrayModels
{

    /**
     * @param array $attributes
     * @param bool  $sync
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
     * @return mixed
     */
    private function callConstructingFunction($class, $value)
    {
        if (str_contains($class, '::')) {
            return $class($value);
        }

        return new $class($value);
    }
}