<?php namespace Common\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property int                 $status
 * @property Template            $template
 * @property string              $template_id
 * @property string              $name
 * @property string              $timezone
 * @property string              $notification
 * @property string              $date
 * @property string              $time
 * @property AudienceFilter      $filter
 * @property ObjectID            $bot_id
 * @property \Carbon\Carbon      $completed_at
 * @property Bot                 $bot
 * @property array               $stats
 * @property int                 $message_type
 * @property bool                $send_now
 * @property int                 $timezone_mode
 * @property array               $limit_time
 * @property BroadcastSchedule[] $schedules
 * @property \Carbon\Carbon|null $send_at
 * @property int                 $remaining_target
 */
class Broadcast extends BaseModel
{

    use HasEmbeddedArrayModels;

    protected $dates = ['send_at', 'completed_at'];

    /**
     * @param array $attributes
     * @param bool  $sync
     * @return BaseModel
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        if (isset($attributes['filter']['groups'])) {
            foreach ($attributes['filter']['groups'] as $i => $group) {
                foreach ($group['rules'] as $j => $rule) {
                    $group['rules'][$j] = new AudienceFilterRule($rule);
                }
                $attributes['filter']['groups'][$i] = new AudienceFilterGroup($group);
            }
        }
        $attributes['filter'] = new AudienceFilter($attributes['filter']);

        if (isset($attributes['schedules'])) {
            $attributes['schedules'] = array_map(function ($schedule) {
                return new BroadcastSchedule($schedule);
            }, $attributes['schedules']);
        }

        return parent::setRawAttributes($attributes, $sync);
    }

}
