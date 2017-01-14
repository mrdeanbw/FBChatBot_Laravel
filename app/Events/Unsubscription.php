<?php

namespace App\Events;

use App\Models\Subscriber;

class Unsubscription
{

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