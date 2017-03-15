<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
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
     *
     * @param BroadcastService $broadcasts
     */
    public function __construct(BroadcastService $broadcasts)
    {
        $this->broadcasts = $broadcasts;
    }

    /**
     * Retrieve the list of broadcasts.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        $broadcasts = $this->broadcasts->all($this->bot());

        return $this->collectionResponse($broadcasts);
    }

    /**
     * Return the details of a broadcast.
     *
     * @param         $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show($id)
    {
        $broadcast = $this->broadcasts->broadcastWithDetailedStats($id, $this->bot());

        return $this->itemResponse($broadcast);
    }

    /**
     * Create a broadcast.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $bot = $this->bot();

        $this->validate($request, $this->validationRules(), $this->filterGroupRuleValidationCallback($bot));

        $broadcast = $this->broadcasts->create($request->all(), $bot);

        return $this->itemResponse($broadcast);
    }

    /**
     * Update a broadcast.
     *
     * @param         $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        $bot = $this->bot();

        $this->validate($request, $this->validationRules(), $this->filterGroupRuleValidationCallback($bot));

        $broadcast = $this->broadcasts->update($id, $request->all(), $bot);

        return $this->itemResponse($broadcast);
    }

    /**
     * Delete a broadcast.
     *
     * @param         $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function destroy($id)
    {
        $this->broadcasts->delete($id, $this->bot());

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
            'template.messages.*'           => 'bail|required|message',
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
            'filter.groups.*.rules.*.value' => 'bail|required',
            'send_mode'                     => 'bail|required|in:now,later',
            'date'                          => 'bail|required_if:send_mode,later|date_format:Y-m-d',
            'time'                          => 'bail|required_if:send_mode,later|date_format:H:i',
            'timezone_mode'                 => 'bail|required|in:bot,subscriber,custom',
            'timezone'                      => 'bail|required_if:timezone_mode,custom|timezone',
            'limit_time'                    => 'bail|required|array',
            'limit_time.enabled'            => 'bail|required|boolean',
            'limit_time.from'               => 'bail|required_if:limit_time.enabled,true|integer|between:1,24',
            'limit_time.to'                 => 'bail|required_if:limit_time.enabled,true|integer|between:1,24',
            'notification'                  => 'bail|required|in:REGULAR,SILENT_PUSH,NO_PUSH',
        ];
    }

    protected function transformer()
    {
        return new BroadcastTransformer();
    }

}
