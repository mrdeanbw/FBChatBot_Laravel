<?php namespace App\Services;

use App\Repositories\Template\TemplateRepositoryInterface;
use DB;
use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Sequence;
use App\Models\Subscriber;
use App\Models\SequenceMessage;
use App\Models\SequenceMessageSchedule;
use App\Events\SequenceTargetingWasAltered;
use App\Repositories\Sequence\SequenceRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MongoDB\BSON\ObjectID;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SequenceService
{

    /**
     * @type MessageService
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
     * @type SequenceRepositoryInterface
     */
    private $sequenceRepo;
    /**
     * @type TemplateRepositoryInterface
     */
    private $templateRepo;

    /**
     * SequenceService constructor.
     * @param SequenceRepositoryInterface $sequenceRepo
     * @param MessageService              $messageBlocks
     * @param TimezoneService             $timezones
     * @param FilterGroupService          $filterGroups
     * @param TemplateRepositoryInterface $templateRepo
     */
    public function __construct(
        SequenceRepositoryInterface $sequenceRepo,
        MessageService $messageBlocks,
        TimezoneService $timezones,
        FilterGroupService $filterGroups,
        TemplateRepositoryInterface $templateRepo
    ) {
        $this->messageBlocks = $messageBlocks;
        $this->timezones = $timezones;
        $this->filterGroups = $filterGroups;
        $this->sequenceRepo = $sequenceRepo;
        $this->templateRepo = $templateRepo;
    }

    /**
     * Return all sequences for page.
     * @param Bot $bot
     * @return \Illuminate\Support\Collection
     */
    public function all(Bot $bot)
    {
        return $this->sequenceRepo->getAllForBot($bot);
    }

    /**
     * Find a sequence for a page.
     * @param             $id
     * @param Bot         $bot
     * @return Sequence
     */
    public function findByIdForBot($id, Bot $bot)
    {
        return $this->sequenceRepo->findByIdForBot($id, $bot);
    }

    /**
     * Find a sequence for page, or thrown an exception if the sequence doesn't exit.
     * @param             $id
     * @param Bot         $page
     * @return Sequence
     */
    public function findByIdForBotOrFail($id, Bot $page)
    {
        if ($sequence = $this->findByIdForBot($id, $page)) {
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
     * Create a sequence
     * @param array $input
     * @param Bot   $bot
     * @return Sequence
     */
    public function create(array $input, Bot $bot)
    {
        $data = [
            'name'     => $input['name'],
            'bot_id'   => $bot->id,
            'filters'  => null,
            'messages' => $this->defaultSequenceMessages($bot),
        ];

        $sequence = $this->sequenceRepo->create($data);

        return $sequence;
    }

    /**
     * Update a sequence.
     * @param      $id
     * @param      $input
     * @param Bot  $page
     * @return Sequence
     */
    public function update($id, $input, Bot $page)
    {
        $data = [
            'name'           => $input['name'],
            'filter_type'    => $input['filter_type'],
            'filter_enabled' => array_get($input, 'filter_enabled', false)
        ];

        $filterGroups = $input['filter_groups'];

        DB::transaction(function () use ($id, $data, $filterGroups, $page) {
            $sequence = $this->findByIdForBotOrFail($id, $page);
            $this->sequenceRepo->update($sequence, $data);
            $this->filterGroups->persist($sequence, $filterGroups);

            // Fresh instance of the sequence
            $sequence = $this->findByIdForBotOrFail($id, $page);
            event(new SequenceTargetingWasAltered($sequence));
        });

        // return a fresh instance.
        return $this->findByIdForBotOrFail($id, $page);
    }

    /**
     * Delete a sequence.
     * @param      $id
     * @param Bot  $page
     */
    public function delete($id, $page)
    {
        $sequence = $this->findByIdForBotOrFail($id, $page);
        DB::transaction(function () use ($sequence) {
            $this->sequenceRepo->delete($sequence);
        });
    }

    /**
     * Add a new message to a sequences.
     * @param array $input
     * @param int   $sequenceId
     * @param Bot   $page
     */
    public function addMessage(array $input, $sequenceId, Bot $page)
    {
        $sequence = $this->findByIdForBotOrFail($sequenceId, $page);

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
     * @param Bot   $page
     */
    public function updateMessage(array $input, $id, $sequenceId, Bot $page)
    {
        DB::transaction(function () use ($input, $id, $sequenceId, $page) {
            $data = array_only($input, ['name', 'days']);
            $data['is_live'] = array_get($input, 'is_live', false);

            $sequence = $this->findByIdForBotOrFail($sequenceId, $page);
            $message = $this->findMessageOrFail($id, $sequence);

            $this->sequenceRepo->updateMessage($message, $data);
            //            $this->messageBlocks->persist($message, $input['messages']);
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
            $sequence = $this->findByIdForBotOrFail($sequenceId, $page);
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
            'status'  => 'pending',
            'send_at' => $previousMessagesWasSentAt->copy()->addDays($message->days)
        ];

        return $this->sequenceRepo->createMessageSchedule($data, $message, $subscriber);
    }

    /**
     * @param Bot $bot
     * @return array
     */
    private function defaultSequenceMessages(Bot $bot)
    {

        $templates = $this->getDefaultTemplates($bot);
        $this->templateRepo->bulkCreate($templates);

        $arr = [
            [
                'order'       => 1,
                'live'        => false,
                'name'        => 'Introduction content + Unsubscribe instructions',
                'conditions'  => ['wait_for' => ['days' => 1, 'hours' => 0, 'minutes' => 0]],
                'template_id' => $templates[0]['_id'],
            ],
            [
                'order'       => 2,
                'live'        => false,
                'name'        => '1st Educational message',
                'conditions'  => ['wait_for' => ['days' => 1, 'hours' => 0, 'minutes' => 0]],
                'template_id' => $templates[1]['_id'],
            ],
            [
                'order'       => 3,
                'live'        => false,
                'name'        => '2nd Educational message',
                'conditions'  => ['wait_for' => ['days' => 2, 'hours' => 0, 'minutes' => 0]],
                'template_id' => $templates[2]['_id'],
            ],
            [
                'order'       => 4,
                'live'        => false,
                'name'        => '3rd Educational message + Soft sell',
                'conditions'  => ['wait_for' => ['days' => 3, 'hours' => 0, 'minutes' => 0]],
                'template_id' => $templates[3]['_id'],
            ],
            [
                'order'       => 5,
                'live'        => false,
                'name'        => '4th Educational message',
                'conditions'  => ['wait_for' => ['days' => 4, 'hours' => 0, 'minutes' => 0]],
                'template_id' => $templates[4]['_id'],
            ],
        ];

        return array_map(function ($item) {
            return new SequenceMessage($item);
        }, $arr);
    }


    /**
     * @param Bot $bot
     * @return array
     */
    private function getDefaultTemplates(Bot $bot)
    {

        return [
            [
                '_id'      => new ObjectID(),
                'bot_id'   => $bot->id,
                'explicit' => false,
                'messages' => [
                    $this->textMessage("Remind your subscriber who you are and why are they getting messages from you. Then deliver valuable information (don't forget to replace these help messages!)."),
                    $this->textMessage('Good idea to mention how to unsubscribe (they can do this by sending the "stop" message).'),
                ]
            ],

            [
                '_id'      => new ObjectID(),
                'bot_id'   => $bot->id,
                'explicit' => false,
                'messages' => [
                    $this->textMessage("First messages are the most important. Focus on being extremely useful.")
                ]
            ],


            [
                '_id'      => new ObjectID(),
                'bot_id'   => $bot->id,
                'explicit' => false,
                'messages' => [
                    $this->textMessage("First messages are the most important. Focus on being extremely useful.")
                ]
            ],


            [
                '_id'      => new ObjectID(),
                'bot_id'   => $bot->id,
                'explicit' => false,
                'messages' => [
                    $this->textMessage("Make your message educational, but find a soft way to mention your product. Something like a P.S. at the end can be a good way to do it.")
                ]
            ],


            [
                '_id'      => new ObjectID(),
                'bot_id'   => $bot->id,
                'explicit' => false,
                'messages' => [
                    $this->textMessage("Keep being incredibly useful. Remember that your subscription base is the most important asset. Take time to build the relationship.")
                ]
            ]
        ];
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