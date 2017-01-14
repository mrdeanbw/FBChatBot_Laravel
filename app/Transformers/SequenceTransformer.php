<?php
namespace App\Transformers;

use App\Models\Sequence;

class SequenceTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['messages', 'filter_groups'];

    public function transform(Sequence $sequence)
    {
        return [
            'id'                => (int)$sequence->id,
            'name'              => $sequence->name,
            'subscribers_count' => $sequence->subscribers()->count(),
            'filter_enabled'    => (boolean)$sequence->filter_enabled,
            'filter_type'       => $sequence->filter_type,
        ];
    }

    public function includeMessages(Sequence $sequence)
    {
        return $this->collection($sequence->messages()->withTrashed()->get(), new SequenceMessageTransformer, false);
    }
}