<?php namespace App\Services;

use DB;
use Carbon\Carbon;
use App\Models\Page;
use App\Models\Sequence;
use App\Models\Subscriber;
use App\Models\SequenceMessage;
use App\Models\SequenceMessageSchedule;
use App\Events\SequenceTargetingWasAltered;
use App\Repositories\Sequence\SequenceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     * @type SequenceRepository
     */
    private $sequenceRepo;

    /**
     * SequenceService constructor.
     * @param SequenceRepository  $sequenceRepo
     * @param MessageBlockService $messageBlocks
     * @param TimezoneService     $timezones
     * @param FilterGroupService  $filterGroups
     */
    public function __construct(
        SequenceRepository $sequenceRepo,
        MessageBlockService $messageBlocks,
        TimezoneService $timezones,
        FilterGroupService $filterGroups
    ) {
        $this->messageBlocks = $messageBlocks;
        $this->timezones = $timezones;
        $this->filterGroups = $filterGroups;
        $this->sequenceRepo = $sequenceRepo;
    }

    /**
     * Return all sequences for page.
     * @param Page $page
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(Page $page)
    {
        return $this->sequenceRepo->getAllForPage($page);
    }

    /**
     * Find a sequence for a page.
     * @param             $id
     * @param Page        $page
     * @return Sequence
     */
    public function find($id, Page $page)
    {
        return $this->sequenceRepo->findByIdForPage($id, $page);
    }

    /**
     * Find a sequence for page, or thrown an exception if the sequence doesn't exit.
     * @param             $id
     * @param Page        $page
     * @return Sequence
     */
    public function findOrFail($id, Page $page)
    {
        if ($sequence = $this->find($id, $page)) {
            return $sequence;
        }
        throw new ModelNotFoundException;
    }

    /**
     * Find a sequence message by ID, throw an exception if it doesn't exist.
     * @param          $id
     * @param Sequence $sequence
     * @return SequenceMessage
     */
    private function findMessageOrFail($id, Sequence $sequence)
    {
        if ($message = $this->sequenceRepo->findSequenceMessageById($id, $sequence)) {
            return $message;
        }

        throw new ModelNotFoundException;
    }

    /**
     * Update a sequence.
     * @param      $id
     * @param      $input
     * @param Page $page
     * @return Sequence
     */
    public function update($id, $input, Page $page)
    {
        $data = [
            'name'           => $input['name'],
            'filter_type'    => $input['filter_type'],
            'filter_enabled' => array_get($input, 'filter_enabled', false)
        ];

        $filterGroups = $input['filter_groups'];

        DB::transaction(function () use ($id, $data, $filterGroups, $page) {
            $sequence = $this->findOrFail($id, $page);
            $this->sequenceRepo->update($sequence, $data);
            $this->filterGroups->persist($sequence, $filterGroups);

            // Fresh instance of the sequence
            $sequence = $this->findOrFail($id, $page);
            event(new SequenceTargetingWasAltered($sequence));
        });

        // return a fresh instance.
        return $this->findOrFail($id, $page);
    }

    /**
     * Create a sequence
     * @param      $input
     * @param Page $page
     * @return Sequence
     */
    public function create($input, Page $page)
    {
        $data = [
            'name' => $input['name']
        ];
        $sequence = new Sequence();

        DB::transaction(function () use ($sequence, $data, $page) {
            $this->sequenceRepo->create($data, $page);
            $this->createDefaultSequenceMessages($sequence);
        });


        return $sequence;
    }

    /**
     * Create the default sequence messages.
     * @param Sequence $sequence
     */
    private function createDefaultSequenceMessages(Sequence $sequence)
    {
        $messages = $this->getDefaultSequenceMessages();
        $this->persistMessages($messages, $sequence);
    }

    /**
     * Persist the messages for a sequence.
     * @param array    $messages
     * @param Sequence $sequence
     */
    private function persistMessages(array $messages, Sequence $sequence)
    {
        foreach ($messages as $data) {
            $clean = array_only($data, ['name', 'days', 'order']);
            $clean['is_live'] = array_get($data, 'is_live', false);

            $message = $this->sequenceRepo->createMessage($data, $sequence);
            $this->messageBlocks->persist($message, $data['message_blocks']);
        }
    }

    /**
     * Delete a sequence.
     * @param      $id
     * @param Page $page
     */
    public function delete($id, $page)
    {
        $sequence = $this->findOrFail($id, $page);
        DB::transaction(function () use ($sequence) {
            $this->sequenceRepo->delete($sequence);
        });
    }

    /**
     * Add a new message to a sequences.
     * @param array $input
     * @param int   $sequenceId
     * @param Page  $page
     */
    public function addMessage(array $input, $sequenceId, Page $page)
    {
        $sequence = $this->findOrFail($sequenceId, $page);

        // The order of the message to be added, is the order of the last message + 1
        // If no previous messages exist, then the order of this message is 1.
        $lastMessage = $this->sequenceRepo->getLastSequenceMessage($sequence);
        $input['order'] = $lastMessage? $lastMessage->order + 1 : 1;

        DB::transaction(function () use ($input, $sequence) {
            $this->persistMessages([$input], $sequence);
        });
    }

    /**
     * Update a sequence message.
     * @param array $input
     * @param int   $id
     * @param int   $sequenceId
     * @param Page  $page
     */
    public function updateMessage(array $input, $id, $sequenceId, Page $page)
    {
        DB::transaction(function () use ($input, $id, $sequenceId, $page) {
            $data = array_only('name', 'days');
            $data['is_live'] = array_get($input, 'is_live', false);

            $sequence = $this->findOrFail($sequenceId, $page);
            $message = $this->findMessageOrFail($id, $sequence);

            $this->sequenceRepo->updateMessage($message, $data);
            $this->messageBlocks->persist($message, $input['message_blocks']);
        });
    }

    /**
     * Delete a sequence message.
     * @param $id
     * @param $sequenceId
     * @param $page
     */
    public function deleteMessage($id, $sequenceId, $page)
    {
        DB::transaction(function () use ($id, $sequenceId, $page) {
            $sequence = $this->findOrFail($sequenceId, $page);
            $message = $this->findMessageOrFail($id, $sequence);
            $this->sequenceRepo->deleteMessage($message);
        });
    }


    /**
     * Schedule the next sequence message to be sent to a subscriber.
     * Schedule message data = send date of previous message + time period to be waited before sending this message
     * @param SequenceMessage $message
     * @param Subscriber      $subscriber
     * @param                 $previousMessagesWasSentAt (or subscribed at for first message).
     * @return SequenceMessageSchedule
     */
    public function scheduleMessage(SequenceMessage $message, Subscriber $subscriber, Carbon $previousMessagesWasSentAt)
    {
        $data = [
            'status' => 'pending',
            'send_at' => $previousMessagesWasSentAt->copy()->addDays($message->days)
        ];
        
        return $this->sequenceRepo->createMessageSchedule($data, $message, $subscriber);
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

}