<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\SubscriberService;
use App\Transformers\BaseTransformer;
use App\Transformers\SubscriberTransformer;

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
    }

    /**
     * Return paginated list of subscribers.
     *
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        // Parse the filter query string to an array.
        $filter = $request->get('filter', []);
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }

        $paginator = $this->audience->paginate(
            $this->bot(),
            $request->get('page'),
            $filter,
            $request->get('sorting', []),
            $request->get('count')
        );

        return $this->paginatorResponse($paginator);
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
        $page = $this->bot();
        $subscriber = $this->audience->findForBotOrFail($id, $page);

        return $this->itemResponse($subscriber);
    }

    /**
     * Update a subscriber.
     *
     * @param         $id
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function update($id, Request $request)
    {
        $this->validate($request, [
            'tags'      => 'bail|array|subscriber_tags',
            'sequences' => 'bail|array|subscriber_sequences',
        ]);

        $subscriber = $this->audience->update($request->all(), $id, $this->bot());

        return $this->itemResponse($subscriber);
    }

    /**
     * Batch update subscribers.
     *
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function batchUpdate(Request $request)
    {
        $this->validate($request, [
            'actions'          => 'bail|required|array|button_actions',
            'subscribers'      => 'bail|required|array',
            'subscribers.*'    => 'bail|required|array',
            'subscribers.*.id' => 'bail|required',
        ]);
        
        $this->audience->batchUpdate($request->all(), $this->bot());

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SubscriberTransformer();
    }
}
