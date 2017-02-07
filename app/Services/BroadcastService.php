<?php namespace App\Services;

use App\Models\AudienceFilter;
use DB;
use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Broadcast;
use App\Models\Subscriber;
use App\Repositories\Broadcast\BroadcastRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BroadcastService
{

    /**
     * @type MessageService
     */
    private $messageBlocks;
    /**
     * @type SubscriberService
     */
    private $audience;
    /**
     * @type TimezoneService
     */
    private $timezones;
    /**
     * @type FilterGroupService
     */
    private $filterGroups;
    /**
     * @type BroadcastRepository
     */
    private $broadcastRepo;
    /**
     * @type TemplateService
     */
    private $templates;

    /**
     * BroadcastService constructor.
     * @param BroadcastRepository $broadcastRepo
     * @param MessageService      $messageBlocks
     * @param SubscriberService   $audience
     * @param TimezoneService     $timezones
     * @param FilterGroupService  $filterGroups
     * @param TemplateService     $templates
     */
    public function __construct(
        BroadcastRepository $broadcastRepo,
        MessageService $messageBlocks,
        SubscriberService $audience,
        TimezoneService $timezones,
        FilterGroupService $filterGroups,
        TemplateService $templates
    ) {
        $this->messageBlocks = $messageBlocks;
        $this->audience = $audience;
        $this->timezones = $timezones;
        $this->filterGroups = $filterGroups;
        $this->broadcastRepo = $broadcastRepo;
        $this->templates = $templates;
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
     * @param      $id
     * @param Bot  $bot
     * @return Broadcast|null
     */
    public function findById($id, Bot $bot)
    {
        return $this->broadcastRepo->findByIdForBot($id, $bot);
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
        throw new ModelNotFoundException;
    }

    /**
     * Find a broadcast by ID and status, throw exception if it doesn't exist.
     * @param      $id
     * @param Bot  $bot
     * @param      $status
     * @return Broadcast
     */
    public function findByIdAndStatusOrFail($id, $status, Bot $bot)
    {
        $broadcast = $this->findById($id, $bot);

        if ($broadcast && $broadcast->status == $status) {
            return $broadcast;
        }

        throw new ModelNotFoundException;
    }

    /**
     * Create a broadcast
     * @param array $input
     * @param Bot   $bot
     * @return Broadcast
     */
    public function create(array $input, Bot $bot)
    {
        $template = $this->templates->createImplicit($input['template']['messages'], $bot);

        $data = array_merge($this->cleanInput($input, $bot), [
            'bot_id'      => $bot->id,
            'template_id' => $template->id
        ]);

        /** @type Broadcast $broadcast */
        $broadcast = $this->broadcastRepo->create($data);
        $broadcast->template = $template;

        return $broadcast;
    }

    /**
     * Update a broadcast.
     * @param        $id
     * @param array  $input
     * @param Bot    $bot
     * @return Broadcast
     */
    public function update($id, array $input, Bot $bot)
    {
        $broadcast = $this->findByIdAndStatusOrFail($id, 'pending', $bot);

        $data = $this->cleanInput($input, $bot);
        $this->broadcastRepo->update($broadcast, $data);

        $broadcast->template = $this->templates->updateImplicit($broadcast->template_id, $input['template'], $bot);

        return $broadcast;
    }

    /**
     * @param array $input
     * @param Bot   $bot
     * @return array
     */
    private function cleanInput(array $input, Bot $bot)
    {
        $data = array_only($input, [
            'name',
            'notification',
            'timezone',
            'date',
            'time',
            'send_from',
            'send_to',
        ]);

        $data = array_merge($data, [
            'status'   => 'new',
            'filter'   => new AudienceFilter($input['filter'], true),
            'schedule' => $this->calculateScheduleDateTime($data, $bot)
        ]);

        return $data;
    }

    /**
     * Calculate the date/time when a specific timezone subscribers should receive the broadcast messages.
     * @param array $data
     * @param Bot   $bot
     * @return array [Carbon, double|null]
     */
    private function calculateScheduleDateTime(array $data, Bot $bot)
    {
        // start by creating a copy from the original broadcast send at date/time.
        $sendAt = $this->getTimeInUTC($data['date'], $data['time'], $bot->timezone_offset)->copy();

        // If the timezone mode is "same time", then we will send it exactly the same time.
        if ($data['timezone'] == 'same_time') {
            return ['send_at' => $sendAt];
        }

        // Otherwise, we will send it in the first timezone (-12)
        $offset = TimezoneService::UTC_OFFSETS[0];

        $sendAt->setTimezone($this->getPrettyTimezoneOffset($offset));

        // If the timezone mode is limit time, then we will make sure that the send date / time falls in this limit.
        if ($data['timezone'] == 'limit_time') {
            $lowerBound = $sendAt->copy()->hour($data['send_from']);
            $upperBound = $sendAt->copy()->hour($data['send_to']);

            // If the upper bound date is less than the lower bound, then add 1 day to the upper bound to fix it.
            if ($upperBound->lt($lowerBound)) {
                $upperBound->addDay(1);
            }

            // If the send at date/time is less than the lower bound. Send it at the lower bound date/time.
            if ($sendAt->lt($lowerBound)) {
                $sendAt = $lowerBound;
            }

            // If the send at date/time is greater than the upper bound. Send it at the upper bound date/time.
            if ($sendAt->gt($upperBound)) {
                $sendAt = $upperBound;
            }

            // Otherwise, it falls between lower and upper bound. Don't change it.
        }

        return [
            'send_at'    => $sendAt,
            'utc_offset' => $offset
        ];
    }

    /**
     * @param string $date
     * @param string $time
     * @param string $timezoneOffset
     * @return Carbon
     */
    private function getTimeInUTC($date, $time, $timezoneOffset)
    {
        $dateTime = "{$date} {$time}";
        $timezoneOffset = $this->getPrettyTimezoneOffset($timezoneOffset);

        return Carbon::createFromFormat('Y-m-d H:i', $dateTime, $timezoneOffset)->setTimezone('UTC');
    }

    /**
     * Convert a decimal value of UTC offset to the HH:MM format accepted b
     * Carbon::createFromFormat static constructor.
     * I/O: Examples
     * 2 => "+02:00"
     * -9.5 => "-09:30"
     * 5.75 => "+05:45"
     * @param double $timezoneOffset
     * @return string
     */
    private function getPrettyTimezoneOffset($timezoneOffset)
    {
        return ($timezoneOffset >= 0? '+' : '-') . date("H:i", abs($timezoneOffset) * 3600);
    }

    /**
     * @param      $id
     * @param Bot  $bot
     */
    public function delete($id, $bot)
    {
        $broadcast = $this->findByIdAndStatusOrFail($id, 'pending', $bot);
        $this->broadcastRepo->delete($broadcast);
    }

    /**
     * @param Broadcast  $broadcast
     * @param Subscriber $subscriber
     * @param int        $incrementBy
     */
    public function incrementBroadcastSubscriberClicks(Broadcast $broadcast, Subscriber $subscriber, $incrementBy = 1)
    {
        $this->broadcastRepo->updateBroadcastSubscriberClicks($broadcast, $subscriber, $incrementBy);
    }

    /**
     * @param Subscriber $subscriber
     * @param  string    $dateTime
     */
    public function updateBroadcastSubscriberDeliveredAt(Subscriber $subscriber, $dateTime)
    {
        $this->broadcastRepo->updateBroadcastSubscriberDeliveredAt($subscriber, $dateTime);
    }

    /**
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function updateBroadcastSubscriberReadAt(Subscriber $subscriber, $dateTime)
    {
        $this->broadcastRepo->updateBroadcastSubscriberReadAt($subscriber, $dateTime);
    }
}