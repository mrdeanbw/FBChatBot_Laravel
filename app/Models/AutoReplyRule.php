<?php namespace App\Models;

/**
 * @property bool     $readonly
 * @property string   $mode
 * @property string   $keyword
 * @property string   $action
 * @property Template $template
 * @property int      mode_priority
 */
class AutoReplyRule extends BaseModel
{

    // (the lower value, the higher priority)
    CONST MATCH_MODE_IS = 10;
    CONST MATCH_MODE_PREFIX = 20;
    CONST MATCH_MODE_CONTAINS = 30;
}
