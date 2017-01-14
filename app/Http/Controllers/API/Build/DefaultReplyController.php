<?php namespace App\Http\Controllers\API\Build;

use App\Http\Controllers\API\APIController;
use App\Services\DefaultReplyService;
use App\Services\Validation\MessageBlockRuleValidator;
use App\Transformers\DefaultReplyTransformer;
use Illuminate\Http\Request;

class DefaultReplyController extends APIController
{

    use MessageBlockRuleValidator;

    /**
     * @type DefaultReplyService
     */
    private $defaultReplies;

    /**
     * DefaultReplyController constructor.
     * @param DefaultReplyService $defaultReplies
     */
    public function __construct(DefaultReplyService $defaultReplies)
    {
        $this->defaultReplies = $defaultReplies;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show()
    {
        $page = $this->page();

        return $this->itemResponse($this->defaultReplies->get($page));
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $page = $this->page();

        $validator = $this->makeValidator($request->all(), [], $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->defaultReplies->persist($request->all(), $page);

        return response([]);
    }


    protected function transformer()
    {
        return new DefaultReplyTransformer();
    }

}
