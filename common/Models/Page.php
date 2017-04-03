<?php namespace Common\Models;

/**
 * @property Bot    $bot
 * @property string access_token
 */
class Page extends ArrayModel
{

    public $id;
    public $name;
    public $avatar_url;
    public $url;
}