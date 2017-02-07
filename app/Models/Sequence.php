<?php namespace App\Models;

/**
 * @property string            $name
 * @property SequenceMessage[] $messages
 */
class Sequence extends BaseModel
{

    use HasEmbeddedArrayModels;

    public $multiArrayModels = [
        'messages' => SequenceMessage::class,
    ];
}
