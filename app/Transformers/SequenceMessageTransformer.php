<?php namespace App\Transformers;

use App\Models\SequenceMessage;

class SequenceMessageTransformer extends BaseTransformer
{

    protected $availableIncludes = ['template'];

    public function transform(SequenceMessage $message)
    {
        return [
            'id'         => $message->id->__toString(),
            'name'       => $message->name,
            'conditions' => $message->conditions,
            'live'       => $message->live,
            'is_deleted' => false,
            'queued'     => 0,
        ];
    }
}