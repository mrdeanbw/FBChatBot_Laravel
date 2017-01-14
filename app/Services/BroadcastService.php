<?php namespace App\Services;

use App\Models\Broadcast;
use App\Models\BroadcastSchedule;
use App\Models\HasFilterGroupsInterface;
use App\Models\Page;
use App\Models\Subscriber;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;

class BroadcastService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;
    /**
     * @type AudienceService
     */
    private $audience;
    /**
     * @type TagService
     */
    private $tags;
    /**
     * @type TimezoneService
     */
    private $timezones;
    /**
     * @type FilterGroupService
     */
    private $filterGroups;

    /**
     * BroadcastService constructor.
     * @param MessageBlockService $messageBlocks
     * @param AudienceService     $audience
     * @param TagService          $tags
     * @param TimezoneService     $timezones
     * @param FilterGroupService  $filterGroups
     */
    public function __construct(
        MessageBlockService $messageBlocks,
        AudienceService $audience,
        TagService $tags,
        TimezoneService $timezones,
        FilterGroupService $filterGroups
    ) {
        $this->messageBlocks = $messageBlocks;
        $this->audience = $audience;
        $this->tags = $tags;
        $this->timezones = $timezones;
        $this->filterGroups = $filterGroups;
    }

    /**
     * @param Page $page
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(Page $page)
    {
        return $page->broadcasts;
    }

    /**
     * @param Page        $page
     * @param             $id
     * @param null|string $status
     * @return Broadcast
     */
    public function find(Page $page, $id, $status = null)
    {
        //        $query = $page->broadcasts()->with('blocks.blocks.blocks')->with('groups.rules');
        $query = $page->broadcasts();
        if ($status) {
            $query->whereStatus($status);
        }
        $broadcast = $query->findOrFail($id);

        return $broadcast;
    }

    /**
     * @param      $id
     * @param      $input
     * @param Page $page
     */
    public function update($id, $input, Page $page)
    {
        DB::beginTransaction();

        $broadcast = $this->find($page, $id, 'pending');
        $this->persist($input, $page, $broadcast);

        DB::commit();
    }

    /**
     * @param      $input
     * @param Page $page
     * @return Broadcast
     */
    public function create($input, Page $page)
    {
        DB::beginTransaction();

        $broadcast = new Broadcast();
        $this->persist($input, $page, $broadcast);

        DB::commit();

        return $broadcast;
    }


    /**
     * @param      $input
     * @param Page $page
     * @param      $broadcast
     */
    private function persist($input, Page $page, Broadcast $broadcast)
    {
        $this->setBroadcastAttributes($input, $page, $broadcast);

        $page->broadcasts()->save($broadcast);

        $broadcast->schedule()->delete();

        BroadcastSchedule::insert($this->getBroadcastSchedules($broadcast));

        $this->messageBlocks->persist($broadcast, $input['message_blocks'], $page);

        $this->filterGroups->persist($broadcast, $input['filter_groups']);
    }

    /**
     * @param      $input
     * @param Page $page
     * @param      $broadcast
     */
    private function setBroadcastAttributes($input, Page $page, Broadcast $broadcast)
    {
        $broadcast->name = $input['name'];
        $broadcast->notification = $input['notification'];
        $broadcast->timezone = $input['timezone'];
        $broadcast->date = $input['date'];
        $broadcast->time = $input['time'];
        $broadcast->filter_type = $input['filter_type'];
        $broadcast->filter_enabled = 1;
        $broadcast->send_from = $input['send_from'];
        $broadcast->send_to = $input['send_to'];
        $broadcast->send_at = $this->getTimeInUTC($input['date'], $input['time'], $page->bot_timezone);
    }

    /**
     * @param $broadcast
     * @return array
     */
    private function getBroadcastSchedules(Broadcast $broadcast)
    {
        $now = Carbon::now();
        $timezoneOffsets = $this->timezones->utcOffsets();

        $sendingSchedule = [];

        foreach ($timezoneOffsets as $offset) {
            $sendAt = $broadcast->send_at->copy();

            if ($broadcast->timezone != 'same_time') {
                $sendAt->setTimezone($this->getPrettyTimezoneOffset($offset));
            }

            if ($broadcast->timezone == 'limit_time') {
                $lowerBound = $sendAt->copy()->hour($broadcast->send_from);
                $upperBound = $sendAt->copy()->hour($broadcast->send_to);
                if ($upperBound->lt($lowerBound)) {
                    $upperBound->addDay(1);
                }
                if (! $sendAt->between($lowerBound, $upperBound)) {
                    $sendAt = $lowerBound;
                }
            }

            $sendingSchedule[] = [
                'broadcast_id' => $broadcast->id,
                'timezone'     => $offset,
                'send_at'      => $sendAt,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];

        }

        return $sendingSchedule;
    }


    /**
     * @param string $date
     * @param string $time
     * @param string $timezoneOffset
     * @return Carbon
     */
    private function getTimeInUTC($date, $time, $timezoneOffset)
    {
        return Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", $this->getPrettyTimezoneOffset($timezoneOffset))->setTimezone('UTC');
    }

    /**
     * @param $timezoneOffset
     * @return string
     */
    private function getPrettyTimezoneOffset($timezoneOffset)
    {
        return ($timezoneOffset >= 0? '+' : '-') . date("H:i", abs($timezoneOffset) * 3600);
    }


    /**
     * @param HasFilterGroupsInterface $model
     * @return Builder
     */
    public function targetAudienceQuery(HasFilterGroupsInterface $model)
    {
        return Subscriber::where(function ($query) use ($model) {
            foreach ($model->filter_groups as $group) {
                $methodPrefix = $model->filter_type == 'or'? 'or' : '';
                $methodName = "{$methodPrefix}Where";
                $query->{$methodName}($this->filterAudienceGroups($group));
            }
        });
    }

    /**
     * @param $group
     * @return \Closure
     */
    private function filterAudienceGroups($group)
    {
        return function ($query) use ($group) {
            /** @type Subscriber $query */
            foreach ($group->rules as $rule) {
                switch ($rule->key) {
                    case 'gender':
                        $query->where('gender', '=', $rule->value, $group->type);
                        break;

                    case 'tag':
                        $query->has('tags', '>=', 1, $group->type, function ($tagQuery) use ($rule) {
                            /** @type \App\Models\Tag $tagQuery */
                            $tagQuery->whereId($rule->value);
                        });
                }
            }
        };
    }

    /**
     * @param      $id
     * @param Page $page
     */
    public function delete($id, $page)
    {
        $broadcast = $this->find($page, $id, 'pending');
        DB::beginTransaction();
        $broadcast->delete();
        DB::commit();
    }


    /**
     * @return Builder
     */
    public function dueBroadcastsQuery()
    {
        return Broadcast::whereStatus('pending')->whereHas('schedule', function ($query) {
            $query->where('send_at', '<=', Carbon::now()->toDateTimeString());
        });
    }
}