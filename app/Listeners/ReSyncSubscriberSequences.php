<?php namespace App\Listeners;

use App\Events\SubscriberTagsWereAltered;
use App\Repositories\Sequence\SequenceRepository;
use App\Services\AudienceService;

class ReSyncSubscriberSequences
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
     * Re-sync a subscriber's sequences. i.e., subscribe him to matching sequences, and unsubscribe him from mismatching sequences.
     * @todo check for performance and implement a more efficient way.
     * @param  SubscriberTagsWereAltered $event
     * @return void
     */
    public function handle(SubscriberTagsWereAltered $event)
    {
        $subscriber = $event->subscriber;

        $allSequences = $this->sequenceRepo->getAllForPage($subscriber->page);

        $subscribedSequences = $this->sequenceRepo->getAllForSubscriber($subscriber);

        foreach ($allSequences as $sequence) {

            $isActuallySubscribed = $subscribedSequences->contains($sequence->id);

            $shouldSubscribe = $this->audience->subscriberIsAmongActiveTargetAudience($subscriber, $sequence);

            /**
             * If the subscriber is not subscribed to a sequence that he should subscribe to, then subscribe him.
             */
            if ($shouldSubscribe && ! $isActuallySubscribed) {
                $this->audience->subscribeToSequence($subscriber, $sequence);
            }

            /**
             * If the subscriber is actually subscribed to a sequence that he should not subscribe to, then unsubscribe him.
             */
            if (! $shouldSubscribe && $isActuallySubscribed) {
                $this->audience->unsubscribeFromSequence($subscriber, $sequence);
            }
        }
    }
}
