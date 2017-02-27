<?php namespace App\Transformers;

use App\Models\SubscriptionHistory;

class SubscriptionHistoryTransformer extends BaseTransformer
{

    public function transform(SubscriptionHistory $record)
    {
        return [
            'action'    => $record->action,
            'action_at' => carbon_date($record->action_at)->toAtomString()
        ];
    }
}