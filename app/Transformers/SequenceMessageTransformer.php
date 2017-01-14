<?php
namespace App\Transformers;

use App\Models\SequenceMessage;

class SequenceMessageTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['message_blocks'];

    public function transform(SequenceMessage $message)
    {
        return [
            'id'         => (int)$message->id,
            'name'       => $message->name,
            'days'       => (int)$message->days,
            'order'      => (int)$message->order,
            'is_live'    => (bool)$message->is_live,
            'is_deleted' => ! ! $message->deleted_at,
            'queued'     => $message->schedules()->whereStatus('pending')->count(),
        ];
    }
}