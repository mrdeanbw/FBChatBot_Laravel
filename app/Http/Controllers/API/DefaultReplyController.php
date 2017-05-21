<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Common\Services\DefaultReplyService;
use Common\Transformers\DefaultReplyTransformer;

class DefaultReplyController extends APIController
{

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
        parent::__construct();
    }

    /**
     * Update the default reply.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'template'            => 'bail|required|array',
            'template.messages'   => 'bail|array|max:10',
            'template.messages.*' => 'bail|required|array|message',
        ]);

        $defaultReply = $this->defaultReplies->update($request->all(), $this->enabledBot());

        return $this->itemResponse($defaultReply);
    }

    /**
     * @return DefaultReplyTransformer
     */
    protected function transformer()
    {
        return new DefaultReplyTransformer();
    }
}
