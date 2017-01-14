<?php namespace App\Http\Controllers\API;

use App\Models\Page;
use App\Services\SequenceService;
use App\Services\Validation\MessageBlockRuleValidator;
use App\Transformers\BaseTransformer;
use App\Transformers\SequenceMessageTransformer;
use Illuminate\Http\Request;

class SequenceMessageController extends APIController
{
    use MessageBlockRuleValidator;

    /**
     * @type SequenceService
     */
    private $sequences;

    public function __construct(SequenceService $sequences)
    {
        $this->sequences = $sequences;
    }

    /**
     * @param         $sequenceId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store($sequenceId, Request $request)
    {
        $page = $this->page();

        $validator = $this->validator($request, $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->sequences->addMessage($request->all(), $sequenceId, $page);

        return $this->response->created();
    }

    /**
     * @param         $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update($id, $sequenceId, Request $request)
    {
        $page = $this->page();

        $validator = $this->validator($request, $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->sequences->updateMessage($request->all(), $id, $sequenceId, $page);

        return $this->response->created();
    }

    /**
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id, $sequenceId)
    {
        $page = $this->page();
        
        $this->sequences->deleteMessage($id, $sequenceId, $page);

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SequenceMessageTransformer();
    }


    /**
     * @param Request $request
     * @param         $page
     * @return \Illuminate\Validation\Validator
     */
    protected function validator(Request $request, Page $page)
    {
        $rules = [
            'name' => 'required|max:255',
            'days' => 'required|numeric|min:0',
        ];

        return $this->makeValidator($request->all(), $rules, $page);
    }
}
