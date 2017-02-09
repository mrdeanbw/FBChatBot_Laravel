<?php namespace App\Models;

/**
 * @property string            $name
 * @property SequenceMessage[] $messages
 * @property AudienceFilter    $filter
 */
class Sequence extends BaseModel
{

    use HasEmbeddedArrayModels;

    public $multiArrayModels = [
        'messages' => SequenceMessage::class
    ];
    
    public $arrayModels = [
        'filter'   => AudienceFilter::class,
    ];
}
