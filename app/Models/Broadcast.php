<?php namespace App\Models;

/**
 * @property string         $status
 * @property Template       $template
 * @property string         $template_id
 * @property string         $name
 * @property string         $timezone
 * @property string         $notification
 * @property string         $date
 * @property string         $time
 * @property int            $send_to
 * @property int            $send_from
 * @property AudienceFilter $filter
 * @property \Carbon\Carbon $next_send_at
 * @property double         $next_utc_offset
 * @property string         $bot_id
 * @property \Carbon\Carbon $completed_at
 * @property Bot            $bot
 */
class Broadcast extends BaseModel
{

    use HasEmbeddedArrayModels;

    protected $dates = ['next_send_at', 'completed_at'];

    public $arrayModels = ['filter' => AudienceFilter::class];
}
