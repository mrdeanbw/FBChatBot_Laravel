<?php namespace Common\Transformers;

use Common\Models\Subscriber;
use Common\Services\LoadsAssociatedModels;

class SubscriberTransformer extends BaseTransformer
{

    use LoadsAssociatedModels;

    public $availableIncludes = ['history', 'sequences'];

    public function transform(Subscriber $subscriber)
    {
        return [
            'id'                   => $subscriber->id,
            'first_name'           => $subscriber->first_name,
            'last_name'            => $subscriber->last_name,
            'avatar_url'           => $subscriber->avatar_url,
            'gender'               => $subscriber->gender,
            'timezone'             => $subscriber->timezone,
            'locale'               => $subscriber->locale,
            'tags'                 => $subscriber->tags,
            'active'               => $subscriber->active,
            'created_at'           => $subscriber->created_at->toAtomString(),
            'last_interaction_at'  => $subscriber->last_interaction_at? $subscriber->last_interaction_at->toAtomString() : null,
            'last_subscribed_at'   => $subscriber->last_subscribed_at? $subscriber->last_subscribed_at->toAtomString() : null,
            'last_unsubscribed_at' => $subscriber->last_unsubscribed_at? $subscriber->last_unsubscribed_at->toAtomString() : null,
        ];
    }

    /**
     * @param Subscriber $subscriber
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeHistory(Subscriber $subscriber)
    {
        $history = object_get($subscriber, 'history', []);

        return $this->collection(array_reverse($history), new SubscriptionHistoryTransformer(), false);
    }

    /**
     * @param Subscriber $subscriber
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeSequences(Subscriber $subscriber)
    {
        $sequences = $this->loadModel($subscriber, 'sequences');

        return $this->collection($sequences, new SequenceTransformer(), false);
    }
}