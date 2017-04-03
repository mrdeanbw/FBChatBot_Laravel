<?php namespace Common\Models;

/**
 * Class Card
 * @package Common\Models
 * @property ImageFile  file
 * @property string     image_url
 * @property Button[]   buttons
 * @property string     title
 * @property string     subtitle
 * @property string     url
 */
class Card extends Message
{

    public $type = 'card';
}
