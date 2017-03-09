<?php namespace App\Transformers;

use Common\Models\SequenceMessage;

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
            'is_deleted' => (bool)$message->deleted_at,
            'queued'     => $message->queued,
        ];
    }
}