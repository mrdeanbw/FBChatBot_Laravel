<?php namespace App\Http\Controllers\API\Build;

use Illuminate\Http\Request;
use App\Services\DefaultReplyService;
use App\Http\Controllers\API\APIController;
use App\Transformers\DefaultReplyTransformer;
use App\Services\Validation\MessageBlockRuleValidator;

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
     * Return the details of the default reply associated with the page.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show()
    {
        $page = $this->page();
        $defaultReply = $this->defaultReplies->get($page);

        return $this->itemResponse($defaultReply);
    }
    
    /**
     * Update the default reply.
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

        $this->defaultReplies->update($request->all(), $page);

        return response([]);
    }

    /**
     * @return DefaultReplyTransformer
     */
    protected function transformer()
    {
        return new DefaultReplyTransformer();
    }

}
