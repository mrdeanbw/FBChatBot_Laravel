<?php namespace App\Services;

use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Broadcast;
use App\Models\AudienceFilter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\Broadcast\BroadcastRepositoryInterface;

class BroadcastService
{

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
     * BroadcastService constructor.
     *
     * @param TemplateService              $templates
     * @param TimezoneService              $timezones
     * @param BroadcastRepositoryInterface $broadcastRepo
     */
    public function __construct(TemplateService $templates, TimezoneService $timezones, BroadcastRepositoryInterface $broadcastRepo)
    {
        $this->timezones = $timezones;
        $this->templates = $templates;
        $this->broadcastRepo = $broadcastRepo;
    }

    /**
     * @param Bot $bot
     *
     * @return \Illuminate\Support\Collection
     */
    public function all(Bot $bot)
    {
        return $this->broadcastRepo->getAllForBot($bot);
    }

    /**
     * @param      $id
     * @param Bot  $bot
     *
     * @return Broadcast|null
     */
    public function findById($id, Bot $bot)
    {
        return $this->broadcastRepo->findByIdForBot($id, $bot);
    }

    /**
     * Find a broadcast by ID, throw exception if it doesn't exist.
     *
     * @param      $id
     * @param Bot  $bot
     *
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
     *
     * @param      $id
     * @param Bot  $bot
     * @param      $status
     *
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
     *
     * @param array $input
     * @param Bot   $bot
     *
     * @return Broadcast
     */
    public function create(array $input, Bot $bot)
    {
        $template = $this->templates->createImplicit($input['template']['messages'], $bot);

        $data = array_merge($this->cleanInput($input, $bot), [
            'bot_id'      => $bot->_id,
            'template_id' => $template->_id
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
        $broadcast = $this->findByIdAndStatusOrFail($id, 'pending', $bot);

        $data = $this->cleanInput($input, $bot);
        $this->broadcastRepo->update($broadcast, $data);

        $broadcast->template = $this->templates->updateImplicit($broadcast->template_id, $input['template'], $bot);

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
        $data = array_only($input, [
            'name',
            'notification',
            'timezone',
            'date',
            'time',
            'send_from',
            'send_to',
        ]);

        $data = array_merge(
            $data,
            [
                'status'       => 'pending',
                'completed_at' => null,
                'filter'       => new AudienceFilter($input['filter'], true),
            ],
            $this->calculateFirstScheduleDateTime($data, $bot)
        );

        $data['notification'] = strtoupper($data['notification']);

        return $data;
    }

    /**
     * Calculate the date/time when a specific timezone subscribers should receive the broadcast messages.
     *
     * @param array $data
     * @param Bot   $bot
     *
     * @return array [Carbon, double|null]
     */
    private function calculateFirstScheduleDateTime(array $data, Bot $bot)
    {
        // start by creating a copy from the original broadcast send at date/time.
        $sendAt = $this->getTimeInUTC($data['date'], $data['time'], $bot->timezone_offset)->copy();

        // If the timezone mode is "same time", then we will send it exactly the same time.
        if ($data['timezone'] == 'same_time') {
            return ['next_send_at' => $sendAt];
        }

        // Otherwise, we will send it in the first timezone (-12)
        $offset = TimezoneService::UTC_OFFSETS[0];

        $sendAt->setTimezone($this->getPrettyTimezoneOffset($offset));


        // If the timezone mode is limit time, then we will make sure that the send date / time falls in this limit.
        if ($data['timezone'] == 'limit_time') {
            $sendAt = $this->applyLimitTime($sendAt, $data['send_from'], $data['send_to']);
        }

        return [
            'next_send_at'    => $sendAt,
            'next_utc_offset' => $offset
        ];
    }

    /**
     * @param Broadcast $broadcast
     *
     * @return array
     */
    public function calculateNextScheduleDateTime(Broadcast $broadcast)
    {
        if ($broadcast->timezone == 'same_time') {
            return ['next_send_at' => null];
        }

        $sendAt = null;

        if ($nextTimezone = $this->timezones->getNext($broadcast->next_utc_offset)) {

            $minutes = 60.0 * ($nextTimezone - $broadcast->next_utc_offset);

            $sendAt = $broadcast->next_send_at->addMinutes((int)$minutes);

            if ($broadcast->timezone == 'limit_time') {
                $sendAt = $this->applyLimitTime($sendAt, $broadcast->send_from, $broadcast->send_to);
            }
        }


        return [
            'next_send_at'    => $sendAt,
            'next_utc_offset' => $nextTimezone
        ];
    }

    /**
     * @param string $date
     * @param string $time
     * @param string $timezoneOffset
     *
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
     *
     * @param double $timezoneOffset
     *
     * @return string
     */
    private function getPrettyTimezoneOffset($timezoneOffset)
    {
        return ($timezoneOffset >= 0 ? '+' : '-') . date("H:i", abs($timezoneOffset) * 3600);
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
     * If the timezone mode is limit time, then we will make sure that the send date / time falls in this limit.
     *
     * @param Carbon $dateTime
     * @param int    $from
     * @param int    $to
     *
     * @return Carbon
     */
    private function applyLimitTime(Carbon $dateTime, $from, $to)
    {
        $lowerBound = $dateTime->copy()->hour($from);
        $upperBound = $dateTime->copy()->hour($to);

        // If the upper bound date is less than the lower bound, then add 1 day to the upper bound to fix it.
        if ($upperBound->lt($lowerBound)) {
            $upperBound->addDay(1);
        }

        // If the send at date/time is less than the lower bound. Send it at the lower bound date/time.
        if ($dateTime->lt($lowerBound)) {
            $dateTime = $lowerBound;
        }

        // If the send at date/time is greater than the upper bound. Send it at the upper bound date/time.
        if ($dateTime->gt($upperBound)) {
            $dateTime = $upperBound;
        }

        // Otherwise, it falls between lower and upper bound. Don't change it.
        return $dateTime;
    }
}