<?php namespace App\Events;

use App\Models\Subscriber;

class SubscriberTagsWereAltered
{

    /**
     * @type Subscriber
     */
    public $subscriber;

    /**
     * Create a new event instance.
     *
     * @param  Subscriber $subscriber
     */
    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }
}