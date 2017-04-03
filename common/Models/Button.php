<?php namespace Common\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property string    title
 * @property string    url
 * @property Message[] messages
 * @property ObjectID  template_id
 * @property array     add_tags
 * @property array     remove_tags
 * @property bool|null unsubscribe
 */
class Button extends Message
{

    public $type = 'button';
}
