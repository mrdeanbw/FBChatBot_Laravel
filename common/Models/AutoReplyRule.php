<?php namespace Common\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property bool     $readonly
 * @property string   $mode
 * @property string   $keywords
 * @property Template $template
 * @property bool     subscribe
 * @property bool     unsubscribe
 * @property ObjectID template_id
 */
class AutoReplyRule extends BaseModel
{

}
