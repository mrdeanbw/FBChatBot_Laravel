<?php namespace Common\Services;

use League\Fractal\Serializer\DataArraySerializer;

class CustomFractalSerializer extends DataArraySerializer
{

    /**
     * Serialize a collection.
     * @param string $resourceKey
     * @param array  $data
     * @return array
     */
    public function collection($resourceKey, array $data)
    {
        if ($resourceKey === false) {
            return $data;
        }

        return [$resourceKey?: 'data' => $data];
    }

    /**
     * Serialize an item.
     * @param string $resourceKey
     * @param array  $data
     * @return array
     */
    public function item($resourceKey, array $data)
    {
        if ($resourceKey === false) {
            return $data;
        }

        return [$resourceKey?: 'data' => $data];
    }
    
    /**
     * Serialize null resource.
     *
     * @return array
     */
    public function null()
    {
        return [];
    }

}