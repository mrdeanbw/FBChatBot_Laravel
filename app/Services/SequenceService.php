<?php namespace App\Services;

use App\Models\Page;
use App\Models\Sequence;
use App\Models\SequenceMessage;
use App\Models\SequenceMessageSchedule;
use App\Models\Subscriber;
use Carbon\Carbon;
use DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SequenceService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;
    /**
     * @type TimezoneService
     */
    private $timezones;
    /**
     * @type FilterGroupService
     */
    private $filterGroups;
    /**
     * @type AudienceService
     */
    private $audience;

    /**
     * SequenceService constructor.
     * @param MessageBlockService $messageBlocks
     * @param TimezoneService     $timezones
     * @param FilterGroupService  $filterGroups
     * @param AudienceService     $audience
     */
    public function __construct(
        MessageBlockService $messageBlocks,
        TimezoneService $timezones,
        FilterGroupService $filterGroups,
        AudienceService $audience
    ) {
        $this->messageBlocks = $messageBlocks;
        $this->timezones = $timezones;
        $this->filterGroups = $filterGroups;
        $this->audience = $audience;
    }

    /**
     * @param Page $page
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(Page $page)
    {
        return $page->sequences;
    }

    /**
     * @param             $id
     * @param Page        $page
     * @return Sequence
     */
    public function find($id, Page $page)
    {
        return $page->sequences()->findOrFail($id);
    }

    /**
     * @param      $id
     * @param      $input
     * @param Page $page
     */
    public function update($id, $input, Page $page)
    {
        DB::beginTransaction();

        $sequence = $this->find($id, $page);

        $sequence->name = $input['name'];
        $sequence->filter_type = $input['filter_type'];
        $sequence->filter_enabled = array_get($input, 'filter_enabled', false);
        $this->filterGroups->persist($sequence, $input['filter_groups']);

        $sequence->save();

        $this->updateSequenceSubscribers($sequence);

        DB::commit();
    }

    /**
     * @param      $input
     * @param Page $page
     * @return Sequence
     */
    public function create($input, Page $page)
    {
        DB::beginTransaction();

        $sequence = new Sequence();
        $sequence->name = $input['name'];
        $page->sequences()->save($sequence);

        $this->createDefaultSequenceMessages($sequence, $page);

        DB::commit();

        return $sequence;
    }


    /**
     * @param          $messages
     * @param Sequence $sequence
     * @param Page     $page
     */
    private function persistMessages($messages, Sequence $sequence, Page $page)
    {
        foreach ((array)$messages as $data) {
            $message = new SequenceMessage();
            $message->name = $data['name'];
            $message->days = $data['days'];
            $message->order = $data['order'];
            $message->is_live = array_get($data, 'is_live', false);
            $sequence->messages()->save($message);

            $this->messageBlocks->persist($message, $data['message_blocks'], $page);
        }
    }

    /**
     * @param      $id
     * @param Page $page
     */
    public function delete($id, $page)
    {
        $sequence = $this->find($id, $page);
        DB::beginTransaction();
        $sequence->delete();
        DB::commit();
    }

    /**
     * @param Sequence $sequence
     * @param Page     $page
     */
    private function createDefaultSequenceMessages(Sequence $sequence, Page $page)
    {
        $messages = $this->getDefaultSequenceMessages();
        $this->persistMessages($messages, $sequence, $page);
    }

    /**
     * @return array
     */
    private function getDefaultSequenceMessages()
    {
        return [
            [
                'name'           => 'Introduction content + Unsubscribe instructions',
                'days'           => 1,
                'order'          => 1,
                'message_blocks' => $this->getDefaultMessageBlocks(1),
            ],
            [
                'name'           => '1st Educational message',
                'days'           => 1,
                'order'          => 2,
                'message_blocks' => $this->getDefaultMessageBlocks(2),
            ],
            [
                'name'           => '2nd Educational message',
                'days'           => 2,
                'order'          => 3,
                'message_blocks' => $this->getDefaultMessageBlocks(3),
            ],
            [
                'name'           => '3rd Educational message + Soft sell',
                'days'           => 3,
                'order'          => 4,
                'message_blocks' => $this->getDefaultMessageBlocks(4),
            ],
            [
                'name'           => '4th Educational message',
                'days'           => 4,
                'order'          => 5,
                'message_blocks' => $this->getDefaultMessageBlocks(5),
            ],
        ];
    }


    /**
     * @param $order
     * @return array
     */
    private function getDefaultMessageBlocks($order)
    {
        switch ($order) {
            case 1:
                return [
                    $this->textMessage("Remind your subscriber who you are and why are they getting messages from you. Then deliver valuable information (don't forget to replace these help messages!)."),
                    $this->textMessage('Good idea to mention how to unsubscribe (they can do this by sending the "stop" message).'),
                ];

            case 2:
                return [$this->textMessage("First messages are the most important. Focus on being extremely useful.")];

            case 3:
                return [$this->textMessage("First messages are the most important. Focus on being extremely useful.")];

            case 4:
                return [$this->textMessage("Make your message educational, but find a soft way to mention your product. Something like a P.S. at the end can be a good way to do it.")];

            case 5:
                return [$this->textMessage("Keep being incredibly useful. Remember that your subscription base is the most important asset. Take time to build the relationship.")];

            default:
                throw new HttpException(500, "Unknown Sequence Message");
        }
    }

    /**
     * @param $text
     * @return array
     */
    private function textMessage($text)
    {
        return [
            'type' => 'text',
            'text' => $text
        ];
    }

    /**
     * @param      $input
     * @param      $sequenceId
     * @param Page $page
     */
    public function addMessage($input, $sequenceId, Page $page)
    {
        DB::beginTransaction();

        $sequence = $this->find($sequenceId, $page);
        $lastOrder = $sequence->unorderedMessages()->orderBy('order', 'desc')->first();
        $input['order'] = $lastOrder? ($lastOrder->order + 1) : 1;
        $this->persistMessages([$input], $sequence, $page);

        DB::commit();
    }

    /**
     * @param $input
     * @param $id
     * @param $sequenceId
     * @param $page
     */
    public function updateMessage($input, $id, $sequenceId, $page)
    {
        DB::beginTransaction();

        $sequence = $this->find($sequenceId, $page);
        $message = $sequence->messages()->findOrFail($id);
        $message->name = $input['name'];
        $message->days = $input['days'];
        $message->is_live = array_get($input, 'is_live', false);
        $message->save();

        $this->messageBlocks->persist($message, $input['message_blocks'], $page);

        DB::commit();
    }

    /**
     * @param $id
     * @param $sequenceId
     * @param $page
     */
    public function deleteMessage($id, $sequenceId, $page)
    {
        DB::beginTransaction();

        $sequence = $this->find($sequenceId, $page);
        $message = $sequence->messages()->findOrFail($id);
        $message->delete();

        DB::commit();
    }


    /**
     * @param SequenceMessage $message
     * @param Subscriber      $subscriber
     * @param                 $previousMessagesWasSentAt (or subscribed at for first message).
     * @return SequenceMessageSchedule
     */
    public function scheduleMessage(SequenceMessage $message, Subscriber $subscriber, Carbon $previousMessagesWasSentAt)
    {
        $schedule = new SequenceMessageSchedule();
        $schedule->subscriber_id = $subscriber->id;
        $schedule->status = 'pending';
        $schedule->sequence_message_id = $message->id;
        $schedule->send_at = $previousMessagesWasSentAt->addMinutes($message->days);
        //        $schedule->send_at = $previousMessagesWasSentAt->addDays($message->days);
        $schedule->save();

        return $schedule;
    }

    /**
     * @param Subscriber $subscriber
     * @param Sequence   $sequence
     */
    public function subscribe(Subscriber $subscriber, Sequence $sequence)
    {
        $subscriber->sequences()->attach($sequence);
        $this->scheduleMessage($sequence->messages()->first(), $subscriber, Carbon::now());
        // @todo if resubscribing, handle properly.
    }

    /**
     * @param Subscriber $subscriber
     * @param Sequence   $sequence
     */
    public function unsubscribe(Subscriber $subscriber, Sequence $sequence)
    {
        $subscriber->sequenceSchedules()->whereSequenceId($sequence->id)->whereStatus('pending')->delete();
        $subscriber->sequences()->detach($sequence);
    }

    /**
     * @param $sequence
     */
    private function updateSequenceSubscribers(Sequence $sequence)
    {
        $oldAudience = $sequence->subscribers;
        $newAudience = $this->audience->targetAudienceQuery($sequence)->get();

        foreach ($newAudience->diff($oldAudience) as $subscriber) {
            $this->subscribe($subscriber, $sequence);
        }

        foreach ($oldAudience->diff($newAudience) as $subscriber) {
            $this->unsubscribe($subscriber, $sequence);
        }
    }


    /**
     * @param Subscriber $subscriber
     */
    public function reSyncSequences(Subscriber $subscriber)
    {
        $matchingSequences = [];

        foreach ($subscriber->page->sequences as $sequence) {

            $subscribed = $subscriber->sequences->contains($sequence->id);

            if ($shouldSubscribe = $this->audience->targetAudienceQuery($sequence)->whereId($subscriber->id)->exists()) {
                $matchingSequences[] = $sequence->id;
            }

            if ($shouldSubscribe && ! $subscribed) {
                $this->subscribe($subscriber, $sequence);
            }

            if (! $shouldSubscribe && $subscribed) {
                $this->unsubscribe($subscriber, $sequence);
            }
        }

        $subscriber->sequences()->sync($matchingSequences);
    }

}