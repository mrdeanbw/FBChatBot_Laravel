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
 * @property \Carbon\Carbon $created_at
 * @property AudienceFilter $filter
 */
class Broadcast extends BaseModel
{
    
    use HasEmbeddedArrayModels;

    protected $dates = ['next_send_at'];

    public $arrayModels = [
        'filter' => AudienceFilter::class,
    ];
}
