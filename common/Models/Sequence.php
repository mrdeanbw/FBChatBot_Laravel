<?php namespace Common\Models;

/**
 * @property string            $name
 * @property SequenceMessage[] $messages
 * @property AudienceFilter    $filter
 * @property string            $bot_id
 * @property int               $subscriber_count
 */
class Sequence extends BaseModel
{

    use HasEmbeddedArrayModels;

    public $arrayModels = ['filter' => AudienceFilter::class];
    public $multiArrayModels = ['messages' => SequenceMessage::class];
}
