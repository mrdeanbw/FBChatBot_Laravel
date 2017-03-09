<?php namespace Common\Models;

/**
 * @property Bot $bot
 */
class Page extends ArrayModel
{

    public $id;
    public $name;
    public $access_token;
    public $avatar_url;
    public $url;
}