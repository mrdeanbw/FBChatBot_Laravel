<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\API\APIController;
use App\Services\AudienceService;
use App\Transformers\BaseTransformer;
use App\Transformers\SubscriberTransformer;
use Illuminate\Http\Request;

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
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $paginator = $this->audience->paginate($this->page(), $request->get('count'), $request->get('filter', []), $request->get('sorting', []));

        return $this->paginatorResponse($paginator);
    }

    /**
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
     * @param         $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update($id, Request $request)
    {
        $page = $this->page();

        $this->validateUpdate($request, $page);

        $this->audience->update($request->all(), $id, $page);

        return $this->response->accepted();
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function batchUpdate(Request $request)
    {
        $page = $this->page();

        $this->validateUpdate($request, $page, true);

        $this->audience->batchUpdate($request->all(), $request->get('subscribers', []), $page);

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SubscriberTransformer();
    }

    /**
     * @param Request $request
     * @param         $page
     * @param bool    $batchUpdate
     */
    private function validateUpdate(Request $request, $page, $batchUpdate = false)
    {
        $callback = function ($validator, $input) {
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

        if ($batchUpdate) {
            $rules['subscribers'] = 'bail|array';
            $rules['subscribers.*'] = 'bail|integer|exists:subscribers,id,page_id,' . $page->id;
        }

        $this->validate($request, $rules, $callback);
    }
}
