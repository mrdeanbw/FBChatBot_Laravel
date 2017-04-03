<?php namespace Common\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property string            $name
 * @property SequenceMessage[] $messages
 * @property AudienceFilter    $filter
 * @property ObjectID          $bot_id
 * @property int               $subscriber_count
 */
class Sequence extends BaseModel
{

    use HasEmbeddedArrayModels;

//    public $embedded = [
//        'filter.groups.rules' => [AudienceFilterRule::class],
//        'filter.groups'       => [AudienceFilterGroup::class],
//        'filter'              => AudienceFilter::class,
//        'messages'            => [SequenceMessage::class]
//    ];
}
