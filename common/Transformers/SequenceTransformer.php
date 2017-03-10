<?php namespace Common\Transformers;

use Common\Models\Sequence;

class SequenceTransformer extends BaseTransformer
{

    protected $availableIncludes = ['template', 'messages', 'filter'];

    public function transform(Sequence $sequence)
    {
        return [
            'id'               => $sequence->id,
            'name'             => $sequence->name,
            'message_count'    => count($sequence->messages),
            'subscriber_count' => $sequence->subscriber_count,
        ];
    }

    public function includeMessages(Sequence $sequence)
    {
        return $this->collection($sequence->messages, new SequenceMessageTransformer(), false);
    }

    public function includeFilter(Sequence $sequence)
    {
        return $this->item($sequence->filter, new AudienceFilterTransformer(), false);
    }
}