<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\AudienceService;
use App\Transformers\BaseTransformer;
use App\Transformers\SubscriberTransformer;

class SubscriberController extends APIController
{

    /**
     * @type AudienceService
     */
    private $audience;

    /**
     * SubscriberController constructor.
     * @param AudienceService $audience
     */
    public function __construct(AudienceService $audience)
    {
        $this->audience = $audience;
    }

    /**
     * Return paginated list of subscribers.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        /**
         * Parse the filter query string to an array.
         */
        $filter = $request->get('filter', '[]');
        $filter = json_decode($filter, true);

        $paginator = $this->audience->paginate(
            $this->page(),
            $request->get('count'),
            $filter,
            $request->get('sorting', [])
        );

        return $this->paginatorResponse($paginator);
    }

    /**
     * Return details of a subscriber.
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $page = $this->page();
        $subscriber = $this->audience->find($id, $page);

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
        $page = $this->page();

        $this->validateUpdateRequest($request, $page);

        $this->audience->update($request->all(), $id, $page);

        return $this->response->accepted();
    }

    /**
     * Batch update subscribers.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function batchUpdate(Request $request)
    {
        $page = $this->page();

        $this->validateUpdateRequest($request, $page, true);

        $this->audience->batchUpdate($request->all(), $request->get('subscribers', []), $page);

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SubscriberTransformer();
    }

    /**
     * Validate the request for updating subscriber(s);
     * @param Request $request
     * @param         $page
     * @param bool    $isBatchUpdate
     */
    private function validateUpdateRequest(Request $request, $page, $isBatchUpdate = false)
    {
        $this->validate(
            $request,
            $this->updateSubscriberValidationRules($page, $isBatchUpdate),
            $this->updateSubscriberValidationCallback()
        );
    }

    /**
     * @param $page
     * @param $isBatchUpdate
     * @return array
     */
    private function updateSubscriberValidationRules($page, $isBatchUpdate)
    {
        $rules = [
            'subscribe'      => 'bail|array',
            'subscribe.id'   => 'bail|exists:sequences,id,page_id,' . $page->id,
            'unsubscribe'    => 'bail|array',
            'unsubscribe.id' => 'bail|exists:sequences,id,page_id,' . $page->id,
            'tag'            => 'bail|array',
            'tag.*'          => 'bail|required|max:255',
            'untag'          => 'bail|array',
            'untag.*'        => 'bail|required|max:255',
        ];

        if ($isBatchUpdate) {
            $rules['subscribers'] = 'bail|array';
            $rules['subscribers.*'] = 'bail|integer|exists:subscribers,id,page_id,' . $page->id;

            return $rules;
        }

        return $rules;
    }

    /**
     * A callback to check for incompatible tags/sequences.
     * @return \Closure
     */
    private function updateSubscriberValidationCallback()
    {
        return function ($validator, $input) {

            $tag = array_get($input, 'tag', []);
            $untag = array_get($input, 'untag', []);

            if (array_intersect($tag, $untag)) {
                $validator->errors()->add('tag', "You cannot add the same tag to 'Tag' and 'Untag' actions.");
            }

            $subscribeIds = extract_attribute(array_get($input, 'subscribe', []));
            $unSubscribeIds = extract_attribute(array_get($input, 'unsubscribe', []));

            if (array_intersect($subscribeIds, $unSubscribeIds)) {
                $validator->errors()->add('subscribe', "A single sequence can't be selected for both 'Subscribe' and 'Unsubscribe' actions.");
            }

            return $validator;
        };
    }
}
