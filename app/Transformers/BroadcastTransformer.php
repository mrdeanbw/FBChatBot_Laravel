<?php namespace App\Transformers;

use App\Models\Broadcast;

class BroadcastTransformer extends BaseTransformer
{

    protected $availableIncludes = ['template', 'filter'];

    public function transform(Broadcast $broadcast)
    {
        return [
            'id'           => $broadcast->id,
            'name'         => $broadcast->name,
            'timezone'     => $broadcast->timezone,
            'notification' => $broadcast->notification,
            'date'         => $broadcast->date,
            'time'         => $broadcast->time,
            'send_from'    => $broadcast->send_from,
            'send_to'      => $broadcast->send_to,
            'status'       => $broadcast->status,
            'created_at'   => $broadcast->created_at->toAtomString(),
            'completed_at' => $broadcast->completed_at ? $broadcast->completed_at->toAtomString() : null,
        ];
    }

    public function includeFilter(Broadcast $broadcast)
    {
        return $this->item($broadcast->filter, new AudienceFilterTransformer(), false);
    }
}