<?php
namespace App\Services;

use League\Fractal\Serializer\DataArraySerializer;

class CustomFractalSerializer extends DataArraySerializer
{

    public function collection($resourceKey, array $data)
    {
        if ($resourceKey === false) {
            return $data;
        }

        return [$resourceKey?: 'data' => $data];
    }

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