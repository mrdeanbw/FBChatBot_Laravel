<?php

namespace App\Events;

use App\Models\Subscriber;

class Resubscription
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