<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\BroadcastService;
use App\Transformers\BroadcastTransformer;
use App\Services\Validation\MessageBlockRuleValidator;
use App\Services\Validation\FilterAudienceRuleValidator;

class BroadcastController extends APIController
{

    use MessageBlockRuleValidator, FilterAudienceRuleValidator;

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
    }

    /**
     * Retrieve the list of broadcasts.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        $page = $this->page();

        return $this->collectionResponse($this->broadcasts->all($page));
    }

    /**
     * Delete a broadcast.
     * @param         $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function destroy($id)
    {
        $page = $this->page();

        $this->broadcasts->delete($id, $page);

        return $this->response->accepted();
    }

    /**
     * Update a broadcast.
     * @param         $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        $page = $this->page();

        $validator = $this->makeBroadcastValidator($request, $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->broadcasts->update($id, $request->all(), $page);

        return $this->response->accepted();
    }

    /**
     * Create a broadcast.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $page = $this->page();

        $validator = $this->makeBroadcastValidator($request, $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->broadcasts->create($request->all(), $page);

        return $this->response->created();
    }

    /**
     * Return the details of a broadcast.
     * @param         $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show($id)
    {
        $page = $this->page();
        $broadcast = $this->broadcasts->find($page, $id);

        return $this->itemResponse($broadcast);
    }

    protected function transformer()
    {
        return new BroadcastTransformer();
    }

    /**
     * @param Request $request
     * @param         $page
     * @return \Illuminate\Validation\Validator
     */
    private function makeBroadcastValidator(Request $request, $page)
    {
        $validator = $this->makeValidator(
            $request->all(),
            $this->broadcastValidationRules(),
            $page,
            $this->filterGroupRuleValidationCallback($page)
        );

        return $validator;
    }

    /**
     * @return array
     */
    private function broadcastValidationRules()
    {
        return [
            'name'                          => 'bail|required|max:255',
            'timezone'                      => 'bail|required|in:same_time,time_travel,limit_time',
            'notification'                  => 'bail|required|in:regular,silent_push,no_push',
            'date'                          => 'bail|required|date_format:Y-m-d',
            'time'                          => 'bail|required|date_format:H:i',
            'send_from'                     => 'bail|required_if:timezone,limit_time|integer|between:1,24',
            'send_to'                       => 'bail|required_if:timezone,limit_time|integer|between:1,24',
            'filter_type'                   => 'bail|required|in:and,or',
            'filter_groups'                 => 'bail|array',
            'filter_groups.*'               => 'bail|array',
            'filter_groups.*.type'          => 'bail|required|in:and,or,none',
            'filter_groups.*.rules'         => 'bail|required|array',
            'filter_groups.*.rules.*.key'   => 'bail|required|in:gender,tag',
            'filter_groups.*.rules.*.value' => 'bail|required',
        ];
    }

}
