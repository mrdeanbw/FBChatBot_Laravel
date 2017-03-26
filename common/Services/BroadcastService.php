<?php namespace Common\Services;

use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Broadcast;
use Common\Models\AudienceFilter;
use Common\Models\BroadcastSchedule;
use Dingo\Api\Exception\ValidationHttpException;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;

class BroadcastService
{

    use LoadsAssociatedModels;

    /**
     * @type TemplateService
     */
    private $templates;
    /**
     * @type TimezoneService
     */
    private $timezones;
    /**
     * @type BroadcastRepositoryInterface
     */
    private $broadcastRepo;
    /**
     * @type SentMessageService
     */
    private $sentMessages;
    /**
     * @type SubscriberRepositoryInterface
     */
    private $subscriberRepo;

    /**
     * BroadcastService constructor.
     *
     * @param TemplateService               $templates
     * @param TimezoneService               $timezones
     * @param SentMessageService            $sentMessages
     * @param BroadcastRepositoryInterface  $broadcastRepo
     * @param SubscriberRepositoryInterface $subscriberRepo
     */
    public function __construct(
        TemplateService $templates,
        TimezoneService $timezones,
        SentMessageService $sentMessages,
        BroadcastRepositoryInterface $broadcastRepo,
        SubscriberRepositoryInterface $subscriberRepo
    ) {
        $this->timezones = $timezones;
        $this->templates = $templates;
        $this->sentMessages = $sentMessages;
        $this->broadcastRepo = $broadcastRepo;
        $this->subscriberRepo = $subscriberRepo;
    }

    /**
     * @param Bot $bot
     * @return \Illuminate\Support\Collection
     */
    public function all(Bot $bot)
    {
        return $this->broadcastRepo->getAllForBot($bot);
    }

    /**
     * @param Bot $bot
     * @param int $page
     * @param int $perPage
     * @return \Illuminate\Pagination\Paginator
     */
    public function paginatePending(Bot $bot, $page, $perPage = 10)
    {
        $filter = [['key' => 'status', 'operator' => '=', 'value' => BroadcastRepositoryInterface::STATUS_PENDING]];
        $order = ['_id' => 'desc'];

        return $this->broadcastRepo->paginateForBot($bot, $page, $filter, $order, $perPage);
    }

    /**
     * @param Bot $bot
     * @param int $page
     * @param int $perPage
     * @return \Illuminate\Pagination\Paginator
     */
    public function paginateNonPending(Bot $bot, $page, $perPage = 10)
    {
        $filter = [['key' => 'status', 'operator' => '!=', 'value' => BroadcastRepositoryInterface::STATUS_PENDING]];
        $order = ['_id' => 'desc'];

        return $this->broadcastRepo->paginateForBot($bot, $page, $filter, $order, $perPage);
    }

    /**
     * @param      $id
     * @param Bot  $bot
     * @return Broadcast|null
     */
    public function findById($id, Bot $bot)
    {
        /** @type Broadcast $ret */
        $ret = $this->broadcastRepo->findByIdForBot($id, $bot);

        return $ret;
    }

    /**
     * Find a broadcast by ID, throw exception if it doesn't exist.
     * @param      $id
     * @param Bot  $bot
     * @return Broadcast|null
     */
    public function findByIdOrFail($id, Bot $bot)
    {
        if ($broadcast = $this->findById($id, $bot)) {
            return $broadcast;
        }
        throw new NotFoundHttpException;
    }

    /**
     * Find a broadcast by ID and status, throw exception if it doesn't exist.
     * @param     $id
     * @param Bot $bot
     * @param int $status
     * @return Broadcast
     */
    public function findByIdAndStatusOrFail($id, $status, Bot $bot)
    {
        $broadcast = $this->findById($id, $bot);

        if ($broadcast && $broadcast->status == $status) {
            return $broadcast;
        }

        throw new NotFoundHttpException;
    }

    /**
     * Create a broadcast
     *
     * @param array $input
     * @param Bot   $bot
     *
     * @return Broadcast
     */
    public function create(array $input, Bot $bot)
    {
        $template = $this->templates->setVersioning(false)->createImplicit($input['template']['messages'], $bot->_id);

        $data = array_merge($this->cleanInput($input, $bot), [
            'bot_id'            => $bot->_id,
            'template_id'       => $template->_id,
            'stats'             => [
                'clicked' => [
                    'total'          => 0,
                    'per_subscriber' => 0
                ],
            ],
            'subscriber_clicks' => []
        ]);

        /** @type Broadcast $broadcast */
        $broadcast = $this->broadcastRepo->create($data);
        $broadcast->template = $template;

        return $broadcast;
    }

    /**
     * Update a broadcast.
     *
     * @param        $id
     * @param array  $input
     * @param Bot    $bot
     *
     * @return Broadcast
     */
    public function update($id, array $input, Bot $bot)
    {
        $broadcast = $this->findByIdAndStatusOrFail($id, BroadcastRepositoryInterface::STATUS_PENDING, $bot);

        $data = $this->cleanInput($input, $bot);
        $this->broadcastRepo->update($broadcast, $data);

        $broadcast->template = $this->templates->setVersioning(false)->updateImplicit($broadcast->template_id, $input['template'], $bot);

        return $broadcast;
    }

    /**
     * @param array $input
     * @param Bot   $bot
     *
     * @return array
     */
    private function cleanInput(array $input, Bot $bot)
    {
        $timezoneMode = array_search($input['timezone_mode'], BroadcastRepositoryInterface::_TIMEZONE_MAP);
        $timezone = $this->cleanTimezone($bot, $timezoneMode, array_get($input, 'timezone'));

        if ($this->isDateTimeInThePast($input['date'], $input['time'], $timezone)) {
            throw new ValidationHttpException(['date' => ["The selected date & time is in the past."]]);
        }

        $audienceFilter = new AudienceFilter($input['filter'], true);
        $messageType = array_search($input['message_type'], BroadcastRepositoryInterface::_MESSAGE_MAP);
        $targetAudienceCount = $this->matchingSubscriberCount($messageType, $audienceFilter);
        if (! $targetAudienceCount) {
            throw new ValidationHttpException(['filter' => ["This broadcast cannot be sent/scheduled because there is no matching subscribers at the moment."]]);
        }

        $data = [
            'name'             => $input['name'],
            'date'             => $input['date'],
            'time'             => $input['time'],
            'filter'           => $audienceFilter,
            'status'           => BroadcastRepositoryInterface::STATUS_PENDING,
            'send_now'         => $input['send_mode'] == 'now',
            'timezone'         => $timezone,
            'timezone_mode'    => $timezoneMode,
            'message_type'     => $messageType,
            'notification'     => array_search($input['notification'], FacebookMessageSender::_NOTIFICATION_MAP),
            'schedules'        => [],
            'completed_at'     => null,
            'remaining_target' => $targetAudienceCount,
        ];

        if ($data['send_now']) {
            $data['send_at'] = Carbon::now();
        } else {
            $data['send_at'] = $timezone? Carbon::createFromFormat('Y-m-d H:i', "{$input['date']} {$input['time']}", $timezone)->setTimezone('UTC') : null;
        }

        if (! $data['send_at']) {
            $data['schedules'] = $this->calculateRunSchedules($data);
            $data['send_at'] = $data['schedules'][0]->send_at;
        }

        return $data;
    }

    /**
     * @param Bot    $bot
     * @param int    $timezoneMode
     * @param string $selectedTimezone
     * @return string|null
     */
    private function cleanTimezone(Bot $bot, $timezoneMode, $selectedTimezone)
    {
        if ($timezoneMode == BroadcastRepositoryInterface::TIMEZONE_CUSTOM) {
            return $selectedTimezone;
        }

        if ($timezoneMode == BroadcastRepositoryInterface::TIMEZONE_BOT) {
            return $bot->timezone;
        }

        return null;
    }

    /**
     * @param array $broadcast
     * @return array
     */
    protected function calculateRunSchedules(array $broadcast)
    {
        $dateTime = "{$broadcast['date']} {$broadcast['time']}";
        $schedule = [];
        foreach (TimezoneService::UTC_OFFSETS as $offset) {
            $sendAt = Carbon::createFromFormat('Y-m-d H:i', $dateTime)->addHours(-$offset);
            // For filling in the form
            $copy = $sendAt->copy()->addMinutes(15);

            if ($copy->isPast()) {
                $sendAt->addDay(1);
            }
            $schedule[] = new BroadcastSchedule([
                'utc_offset' => $offset,
                'send_at'    => $sendAt,
                'status'     => BroadcastRepositoryInterface::STATUS_PENDING
            ]);
        }

        return $schedule;
    }

    /**
     * @param      $id
     * @param Bot  $bot
     */
    public function delete($id, $bot)
    {
        $broadcast = $this->findByIdAndStatusOrFail($id, BroadcastRepositoryInterface::STATUS_PENDING, $bot);
        $this->broadcastRepo->delete($broadcast);
    }

    /**
     * @param     $broadcastId
     * @param Bot $bot
     * @return Broadcast
     */
    public function broadcastWithDetailedStats($broadcastId, Bot $bot)
    {
        $broadcast = $this->findByIdOrFail($broadcastId, $bot);
        if ($broadcast->status == BroadcastRepositoryInterface::STATUS_PENDING) {
            return $broadcast;
        }

        $this->loadModelsIfNotLoaded($broadcast, ['template']);


        foreach ($broadcast->template->messages as $i => $message) {
            if (! $i) {
                $this->sentMessages->setFullMessageStats($message, $message->id);
            } else {
                $this->sentMessages->setMessageClickableStats($message, $message->id);
            }
        }

        return $broadcast;
    }

    /**
     * @param string      $date
     * @param string      $time
     * @param string|null $timezone
     * @return bool
     */
    private function isDateTimeInThePast($date, $time, $timezone)
    {
        $dateTime = "{$date} {$time}";
        $carbon = $timezone?
            Carbon::createFromFormat('Y-m-d H:i', $dateTime, $timezone) :
            Carbon::createFromFormat('Y-m-d H:i', $dateTime)->addHours(-TimezoneService::UTC_OFFSETS[0]);

        // For filling in the form
        $carbon->addMinutes(15);

        return $carbon->isPast();
    }

    /**
     * @param int            $messageType
     * @param AudienceFilter $audienceFilter
     * @param array          $timezones
     * @return int
     */
    public function matchingSubscriberCount($messageType, AudienceFilter $audienceFilter, $timezones = [])
    {
        switch ($messageType) {
            case BroadcastRepositoryInterface::MESSAGE_PROMOTIONAL:
                $lastInteractionAtFilterValue = 'last_24_hours';
                break;
            case BroadcastRepositoryInterface::MESSAGE_FOLLOW_UP:
                $lastInteractionAtFilterValue = 'not:last_24_hours';
                break;
            default:
                $lastInteractionAtFilterValue = null;
        }

        $filter = [
            ['operator' => 'subscriber', 'filter' => $audienceFilter],
            ['key' => 'active', 'operator' => '=', 'value' => true],
        ];

        if ($lastInteractionAtFilterValue) {
            $filter[] = ['key' => 'last_interaction_at', 'operator' => 'date', 'value' => $lastInteractionAtFilterValue];
        }

        if ($timezones) {
            $filter[] = ['key' => 'timezone', 'operator' => 'in', 'value' => $timezones];
        }

        return $this->subscriberRepo->count($filter);
    }
}