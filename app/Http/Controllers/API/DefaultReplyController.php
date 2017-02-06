<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\DefaultReplyService;
use App\Transformers\DefaultReplyTransformer;
use App\Services\Validation\MessageValidationHelper;

class DefaultReplyController extends APIController
{

    use MessageValidationHelper;

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
     * Update the default reply.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $rules = $this->validationRules();
        $this->validate($request, $rules);
        
        $defaultReply = $this->defaultReplies->update($request->all(), $this->bot());

        return $this->itemResponse($defaultReply );
    }

    /**
     * @return DefaultReplyTransformer
     */
    protected function transformer()
    {
        return new DefaultReplyTransformer();
    }

    private function validationRules()
    {
        return [
            'template'            => 'bail|required|array',
            'template.messages'   => 'bail|required|array|max:10',
            'template.messages.*' => 'bail|required|message',
        ];
    }

}
