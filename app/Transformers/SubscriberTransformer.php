<?php namespace App\Transformers;

use App\Models\Subscriber;

class SubscriberTransformer extends BaseTransformer
{

    public function transform(Subscriber $subscriber)
    {
        return [
            'id'                   => $subscriber->id,
            'first_name'           => $subscriber->first_name,
            'last_name'            => $subscriber->last_name,
            'avatar_url'           => $subscriber->avatar_url,
            'gender'               => $subscriber->gender,
            'active'               => $subscriber->active,
            'last_contacted_at'    => $subscriber->last_contacted_at? $subscriber->last_contacted_at->toAtomString() : null,
            'last_subscribed_at'   => $subscriber->last_subscribed_at? $subscriber->last_subscribed_at->toAtomString() : null,
            'last_unsubscribed_at' => $subscriber->last_unsubscribed_at? $subscriber->last_unsubscribed_at->toAtomString() : null,
            'tags'                 => $subscriber->tags,
            'sequences'            => $subscriber->sequence,
        ];
    }
}