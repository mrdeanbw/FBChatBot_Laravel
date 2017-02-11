<?php namespace App\Listeners;

use App\Events\SequenceTargetingWasAltered;
use App\Events\SubscriberTagsWereAltered;
use App\Repositories\Sequence\SequenceRepositoryInterface;
use App\Repositories\Subscriber\SubscriberRepositoryInterface;
use App\Services\SubscriberService;

class ReSyncSequences
{

    /**
     * @type SubscriberRepositoryInterface
     */
    protected $subscriberRepo;

    /**
     * @type SequenceRepositoryInterface
     */
    private $sequenceRepo;
    /**
     * @type SubscriberService
     */
    private $audience;

    /**
     * Create the event listener.
     *
     * @param SequenceRepositoryInterface   $sequenceRepo
     * @param SubscriberService             $audience
     * @param SubscriberRepositoryInterface $subscriberRepo
     */
    public function __construct(SequenceRepositoryInterface $sequenceRepo, SubscriberService $audience, SubscriberRepositoryInterface $subscriberRepo)
    {
        $this->sequenceRepo = $sequenceRepo;
        $this->audience = $audience;
        $this->subscriberRepo = $subscriberRepo;
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
        
        $newAudience = $this->subscriberRepo->getActiveTargetAudience($sequence);

        foreach ($newAudience->diff($oldAudience) as $subscriber) {
            $this->audience->subscribeToSequence($subscriber, $sequence);
        }

        foreach ($oldAudience->diff($newAudience) as $subscriber) {
            $this->audience->unsubscribeFromSequence($subscriber, $sequence);
        }
    }
}
