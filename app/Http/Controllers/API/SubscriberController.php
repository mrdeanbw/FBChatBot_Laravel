<?php namespace App\Http\Controllers\API;

use MongoDB\BSON\ObjectID;
use Illuminate\Http\Request;
use Common\Services\SubscriberService;
use Common\Transformers\BaseTransformer;
use Common\Transformers\SubscriberTransformer;

class SubscriberController extends APIController
{

    /**
     * @type SubscriberService
     */
    private $audience;

    /**
     * SubscriberController constructor.
     *
     * @param SubscriberService $audience
     */
    public function __construct(SubscriberService $audience)
    {
        $this->audience = $audience;
        parent::__construct();
    }

    /**
     * Return paginated list of subscribers.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        // Parse the filter query string to an array.
        $filter = $request->get('filter', []);
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }

        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'filter.filter'                        => 'bail|array',
            'filter.filter.enabled'                => 'bail|bool',
            'filter.filter.join_type'              => 'bail|required_if:filter.enabled,true|in:and,or',
            'filter.filter.groups'                 => 'bail|array',
            'filter.filter.groups.*'               => 'bail|required|array',
            'filter.filter.groups.*.join_type'     => 'bail|required|in:and,or,none',
            'filter.filter.groups.*.rules'         => 'bail|required|array',
            'filter.filter.groups.*.rules.*.key'   => 'bail|required|in:gender,tag',
            'filter.filter.groups.*.rules.*.value' => 'bail|required|filter_value',
        ]);

        $paginator = $this->audience->paginate(
            $bot,
            (int)$request->get('page', 1),
            $filter,
            $request->get('sorting', []),
            $request->get('count')
        );

        return $this->paginatorResponse($paginator);
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function count(Request $request)
    {
        // Parse the filter query string to an array.
        $filter = $request->get('filter', []);
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }

        $count = $this->audience->count($this->enabledBot(), $filter);

        return $this->arrayResponse(compact('count'));
    }

    /**
     * Return details of a subscriber.
     *
     * @param $id
     *
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $id = new ObjectID($id);
        $bot = $this->enabledBot();
        $subscriber = $this->audience->findForBotOrFail($id, $bot);

        return $this->itemResponse($subscriber);
    }

    /**
     * Update a subscriber.
     * @param         $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update($id, Request $request)
    {
        $id = new ObjectID($id);
        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'tags'   => 'bail|array',
            'tags.*' => 'bail|required|string|bot_tag'
        ]);

        $subscriber = $this->audience->update($request->all(), $id, $bot);

        return $this->itemResponse($subscriber);
    }

    /**
     * Batch update subscribers.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function batchUpdate(Request $request)
    {
        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'add_tags'         => 'bail|array',
            'add_tags.*'       => 'bail|required|string|bot_tag',
            'remove_tags'      => 'bail|array',
            'remove_tags.*'    => 'bail|required|string|bot_tag|incompatible_tags:add_tags,remove_tags',
            'subscribers'      => 'bail|required|array',
            'subscribers.*'    => 'bail|required|array',
            'subscribers.*.id' => 'bail|required',
        ]);

        $this->audience->batchUpdate($request->all(), $bot);

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SubscriberTransformer();
    }
}
