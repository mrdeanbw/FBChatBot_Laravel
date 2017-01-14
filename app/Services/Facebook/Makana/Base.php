<?php
namespace App\Services\Facebook\Makana;

use App\Services\Facebook\API;

abstract class Base extends API
{

    protected function url($path, $params = [])
    {
        return $this->graphUrl . '/' . $this->version . '/'. ltrim($path, '/') . '?' . http_build_query($params);
    }
}