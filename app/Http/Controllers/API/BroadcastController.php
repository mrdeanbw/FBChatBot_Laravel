<?php namespace App\Http\Controllers\API;

use MongoDB\BSON\ObjectID;
use Illuminate\Http\Request;
use Common\Models\Broadcast;
use Common\Jobs\SendDueBroadcast;
use Common\Services\BroadcastService;
use Common\Transformers\BroadcastTransformer;
use Common\Services\Validation\FilterAudienceRuleValidator;

class BroadcastController extends APIController
{

    use FilterAudienceRuleValidator;

    /**
     * @type BroadcastService
     */
    private $broadcasts;

    /**
     * BroadcastController constructor.
     * @param BroadcastService $broadcasts
     */
    public function __construct(BroadcastService $broadcasts)
    {
        $this->broadcasts = $broadcasts;
        parent::__construct();
    }

    /**
     * Retrieve the list of pending broadcasts.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pending(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $broadcasts = $this->broadcasts->paginatePending($this->enabledBot(), $page);

        return $this->paginatorResponse($broadcasts);
    }

    /**
     * Retrieve the list of non pending broadcasts.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function nonPending(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $broadcasts = $this->broadcasts->paginateNonPending($this->enabledBot(), $page);

        return $this->paginatorResponse($broadcasts);
    }

    /**
     * Return the details of a broadcast.
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show($id)
    {
        $id = new ObjectID($id);
        $broadcast = $this->broadcasts->broadcastWithDetailedStats($id, $this->enabledBot());

        return $this->itemResponse($broadcast);
    }

    /**
     * Create a broadcast.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $bot = $this->enabledBot();

        $this->validateForBot($bot, $request, $this->validationRules());

        $broadcast = $this->broadcasts->create($request->all(), $bot);

        if ($broadcast->send_now) {
            $this->sendBroadcast($broadcast);
        }

        return $this->itemResponse($broadcast);
    }

    /**
     * Update a broadcast.
     * @param         $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        $id = new ObjectID($id);
        $bot = $this->enabledBot();

        $this->validateForBot($bot, $request, $this->validationRules());

        $broadcast = $this->broadcasts->update($id, $request->all(), $bot);

        if ($broadcast->send_now) {
            $this->sendBroadcast($broadcast);
        }

        return $this->itemResponse($broadcast);
    }

    /**
     * Delete a broadcast.
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function destroy($id)
    {
        $id = new ObjectID($id);
        $this->broadcasts->delete($id, $this->enabledBot());

        return $this->response->accepted();
    }

    /**
     * @return array
     */
    private function validationRules()
    {
        return [
            'template'                      => 'bail|required|array',
            'template.messages'             => 'bail|required|array|max:10',
            'template.messages.*'           => 'bail|required|array|message',
            'name'                          => 'bail|required|max:255',
            'message_type'                  => 'bail|required|in:subscription,promotional,follow_up',
            'filter'                        => 'bail|required|array',
            'filter.enabled'                => 'bail|required',
            'filter.join_type'              => 'bail|required_if:filter.enabled,true|in:and,or',
            'filter.groups'                 => 'bail|array',
            'filter.groups.*'               => 'bail|required|array',
            'filter.groups.*.join_type'     => 'bail|required|in:and,or,none',
            'filter.groups.*.rules'         => 'bail|required|array',
            'filter.groups.*.rules.*.key'   => 'bail|required|in:gender,tag',
            'filter.groups.*.rules.*.value' => 'bail|required|filter_value',
            'send_mode'                     => 'bail|required|in:now,later',
            'date'                          => 'bail|required_if:send_mode,later|date_format:Y-m-d',
            'time'                          => 'bail|required_if:send_mode,later|date_format:H:i',
            'timezone_mode'                 => 'bail|required|in:bot,subscriber,custom',
            'timezone'                      => 'bail|required_if:timezone_mode,custom|timezone',
            'notification'                  => 'bail|required|in:REGULAR,SILENT_PUSH,NO_PUSH',
        ];
    }

    /**
     * @param Broadcast $broadcast
     */
    private function sendBroadcast(Broadcast $broadcast)
    {
        $job = (new SendDueBroadcast($broadcast))->onQueue('onetry');
        dispatch($job);
    }

    protected function transformer()
    {
        return new BroadcastTransformer();
    }
}
