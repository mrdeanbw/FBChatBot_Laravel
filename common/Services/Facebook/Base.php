<?php namespace Common\Services\Facebook;

abstract class Base extends API
{

    protected function url($path, $params = [])
    {
        return $this->graphUrl . '/' . $this->version . '/' . ltrim($path, '/') . '?' . http_build_query($params);
    }
}