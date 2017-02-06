<?php namespace App\Models;

use Carbon\Carbon;

/**
 * @property bool   $readonly
 * @property string $mode
 * @property string $keyword
 * @property string $action
 */
class AutoReplyRule extends BaseModel
{
    public static function boot()
    {
        parent::boot();
        static::saving(function (AutoReplyRule $model) {
            $model->mode_priority = static::getModePriority($model->mode);
        });
    }

    private static function getModePriority($mode)
    {
        switch ($mode) {
            case 'is':
                return 10;

            case 'begins_with':
                return 20;

            case 'contains':
                return 30;

            default:
                return 1000;
        }
    }
}
