<?php namespace App\Events;

use App\Models\Sequence;

class SequenceTargetingWasAltered
{

    /**
     * @type Sequence
     */
    public $sequence;

    /**
     * Create a new event instance.
     *
     * @param  Sequence $sequence
     */
    public function __construct(Sequence $sequence)
    {
        $this->sequence = $sequence;
    }
}