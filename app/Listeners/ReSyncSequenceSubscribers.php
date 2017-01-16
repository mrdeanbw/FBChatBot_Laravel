<?php namespace App\Listeners;

use App\Events\SequenceTargetingWasAltered;
use App\Events\SubscriberTagsWereAltered;
use App\Repositories\Sequence\SequenceRepository;
use App\Services\AudienceService;

class ReSyncSequences
{

    /**
     * @type SequenceRepository
     */
    private $sequenceRepo;
    /**
     * @type AudienceService
     */
    private $audience;

    /**
     * Create the event listener.
     *
     * @param SequenceRepository $sequenceRepo
     * @param AudienceService    $audience
     */
    public function __construct(SequenceRepository $sequenceRepo, AudienceService $audience)
    {
        $this->sequenceRepo = $sequenceRepo;
        $this->audience = $audience;
    }

    /**
     * Handle the event.
     * Re-sync the subscribers for a sequence, this is done by calculating the 2 way difference between
     * the subscribers matching the old filtration criteria, and those matching the new filtration
     * criteria. Subscribe/unsubscribe each one of them accordingly.
     *
     * @param  SequenceTargetingWasAltered $event
     * @return void
     */
    public function handle(SequenceTargetingWasAltered $event)
    {
        $sequence = $event->sequence;

        $oldAudience = $this->sequenceRepo->getSequenceSubscribers($sequence);
        
        $newAudience = $this->audience->getActiveTargetAudience($sequence);

        foreach ($newAudience->diff($oldAudience) as $subscriber) {
            $this->audience->subscribeToSequence($subscriber, $sequence);
        }

        foreach ($oldAudience->diff($newAudience) as $subscriber) {
            $this->audience->unsubscribeFromSequence($subscriber, $sequence);
        }
    }
}
