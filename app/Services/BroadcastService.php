<?php namespace App\Services;

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
     * BroadcastService constructor.
     * @param BroadcastRepository $broadcastRepo
     * @param MessageService      $messageBlocks
     * @param SubscriberService   $audience
     * @param TimezoneService     $timezones
     * @param FilterGroupService  $filterGroups
     */
    public function __construct(
        BroadcastRepository $broadcastRepo,
        MessageService $messageBlocks,
        SubscriberService $audience,
        TimezoneService $timezones,
        FilterGroupService $filterGroups
    ) {
        $this->messageBlocks = $messageBlocks;
        $this->audience = $audience;
        $this->timezones = $timezones;
        $this->filterGroups = $filterGroups;
        $this->broadcastRepo = $broadcastRepo;
    }

    /**
     * @param Bot $page
     * @return \Illuminate\Support\Collection
     */
    public function all(Bot $page)
    {
        return $this->broadcastRepo->getAllForPage($page);
    }

    /**
     * @param      $id
     * @param Bot  $page
     * @return Broadcast|null
     */
    public function findById($id, Bot $page)
    {
        return $this->broadcastRepo->findByIdForPage($id, $page);
    }

    /**
     * Find a broadcast by ID, throw exception if it doesn't exist.
     * @param      $id
     * @param Bot  $page
     * @return Broadcast|null
     */
    public function findByIdOrFail($id, Bot $page)
    {
        if ($broadcast = $this->findById($id, $page)) {
            return $broadcast;
        }
        throw new ModelNotFoundException;
    }

    /**
     * Find a broadcast by ID and status, throw exception if it doesn't exist.
     * @param      $id
     * @param Bot  $page
     * @param      $status
     * @return Broadcast|null
     */
    public function findByIdAndStatusOrFail($id, Bot $page, $status)
    {
        if (! ($broadcast = $this->findById($id, $page))) {
            throw new ModelNotFoundException;
        }

        if ($broadcast->status != $status) {
            throw new ModelNotFoundException;
        }

        return $broadcast;
    }

    /**
     * Create a broadcast
     * @param array $input
     * @param Bot   $page
     * @return Broadcast
     */
    public function create(array $input, Bot $page)
    {
        $broadcast = DB::transaction(function () use ($input, $page) {

            $data = $this->cleanInput($input, $page->bot_timezone);
            $broadcast = $this->broadcastRepo->createForPage($data, $page);

            $this->filterGroups->persist($broadcast, $input['filter_groups']);
//            $this->messageBlocks->persist($broadcast, $input['messages']);

            $this->createBroadcastSchedules($broadcast);

            return $broadcast;
        });

        return $broadcast;
    }

    /**
     * Update a broadcast.
     * @param      $id
     * @param      $input
     * @param Bot  $page
     */
    public function update($id, $input, Bot $page)
    {
        DB::transaction(function () use ($id, $input, $page) {

            $broadcast = $this->findByIdAndStatusOrFail($id, $page, 'pending');

            $data = $this->cleanInput($input, $page->bot_timezone);
            $this->broadcastRepo->update($broadcast, $data);

            $this->filterGroups->persist($broadcast, $input['filter_groups']);
//            $this->messageBlocks->persist($broadcast, $input['messages']);

            $this->createBroadcastSchedules($broadcast);
        });
    }

    /**
     * @param array $input
     * @param int   $inputTimezoneUTCOffset
     * @return array
     */
    private function cleanInput(array $input, $inputTimezoneUTCOffset)
    {
        $data = array_only($input, ['name', 'notification', 'timezone', 'date', 'time', 'send_from', 'send_to', 'filter_type']);

        // Broadcast targeting is always enabled.
        $data['filter_enabled'] = 1;

        // Convert the input date & time into UTC (server time).
        $data['send_at'] = $this->getTimeInUTC($input['date'], $input['time'], $inputTimezoneUTCOffset);

        return $data;
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
     * Create the broadcast schedule.
     * For every UTC offset timezone, create a broadcast schedule record with the send date/time in UTC.
     * In case of updates, we simply delete old schedules and regenerate them.
     * @todo Don't call this method every time the broadcast is saved, but rather when the date/time changes.
     * @param Broadcast $broadcast
     */
    private function createBroadcastSchedules(Broadcast $broadcast)
    {
        // Delete old schedule.
        $this->broadcastRepo->deleteBroadcastSchedule($broadcast);

        // Generate the list of broadcast schedules.
        $schedule = [];
        foreach ($this->timezones->utcOffsets() as $offset) {
            $schedule[] = [
                'timezone' => $offset,
                'send_at'  => $this->calculateTimezoneSendAtDateTime($broadcast, $offset),
            ];
        }

        // Persist them.
        $this->broadcastRepo->createBroadcastSchedule($schedule, $broadcast);
    }

    /**
     * Calculate the date/time when a specific timezone subscribers should receive the broadcast messages.
     * @param Broadcast $broadcast
     * @param double    $offset
     * @return Carbon
     */
    private function calculateTimezoneSendAtDateTime(Broadcast $broadcast, $offset)
    {
        // start by creating a copy from the original broadcast send at date/time.
        $sendAt = $broadcast->send_at->copy();

        // If the timezone mode is "same time", then we will send it exactly the same time.
        if ($broadcast->timezone == 'same_time') {
            return $sendAt;
        }

        // Otherwise, we will send it in every user's timezone.
        $sendAt->setTimezone($this->getPrettyTimezoneOffset($offset));

        // If the timezone mode is limit time, then we will make sure that the send date / time falls in this limit.
        if ($broadcast->timezone == 'limit_time') {
            $lowerBound = $sendAt->copy()->hour($broadcast->send_from);
            $upperBound = $sendAt->copy()->hour($broadcast->send_to);

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

        return $sendAt;
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
     * @param Bot  $page
     */
    public function delete($id, $page)
    {
        $broadcast = $this->findByIdAndStatusOrFail($id, $page, 'pending');
        DB::transaction(function () use ($broadcast) {
            $this->broadcastRepo->delete($broadcast);
        });
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