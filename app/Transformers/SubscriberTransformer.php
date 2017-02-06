<?php
namespace App\Transformers;

use App\Models\Subscriber;

class SubscriberTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['sequences'];

    public function transform(Subscriber $subscriber)
    {
        $history = $subscriber->subscriptionHistory()->get(['action', 'action_at']);

        return [
            'id'                   => (int)$subscriber->id,
            'first_name'           => $subscriber->first_name,
            'last_name'            => $subscriber->last_name,
            'avatar_url'           => $subscriber->avatar_url,
            'gender'               => $subscriber->gender,
            'active'            => $subscriber->active,
            'last_contacted_at'    => $subscriber->last_contacted_at? $subscriber->last_contacted_at->toDateTimeString() : null,
            'last_subscribed_at'   => $subscriber->last_subscribed_at? $subscriber->last_subscribed_at->toDateTimeString() : null,
            'last_unsubscribed_at' => $subscriber->last_unsubscribed_at? $subscriber->last_unsubscribed_at->toDateTimeString() : null,
            'subscription_history' => $history,
            'first_subscribed_at'  => count($history)? $history[count($history) - 1]->action_at->toDateTimeString() : null,
            'tags'                 => $subscriber->tags()->pluck('tag')->toArray()
        ];
    }

    public function includeSequences(Subscriber $subscriber)
    {
        return $this->collection($subscriber->sequences, new SequenceTransformer(), false);
    }
}