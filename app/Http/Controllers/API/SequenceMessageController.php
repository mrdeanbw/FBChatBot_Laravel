<?php namespace App\Http\Controllers\API;

use App\Models\Bot;
use Illuminate\Http\Request;
use App\Services\SequenceService;
use App\Transformers\BaseTransformer;
use App\Transformers\SequenceMessageTransformer;
use App\Services\Validation\MessageValidationHelper;

class SequenceMessageController extends APIController
{
    use MessageValidationHelper;

    /**
     * @type SequenceService
     */
    private $sequences;

    public function __construct(SequenceService $sequences)
    {
        $this->sequences = $sequences;
    }

    /**
     * Create a sequence message.
     * @param         $sequenceId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store($sequenceId, Request $request)
    {
        $page = $this->bot();

        $validator = $this->validator($request, $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->sequences->addMessage($request->all(), $sequenceId, $page);

        return $this->response->created();
    }

    /**
     * Update a sequence message.
     * @param         $id
     * @param         $sequenceId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update($id, $sequenceId, Request $request)
    {
        $page = $this->bot();

        $validator = $this->validator($request, $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        $this->sequences->updateMessage($request->all(), $id, $sequenceId, $page);

        return $this->response->created();
    }

    /**
     * Delete a sequence message.
     * @param $id
     * @param $sequenceId
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id, $sequenceId)
    {
        $page = $this->bot();
        
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
    protected function validator(Request $request, Bot $page)
    {
        $rules = [
            'name' => 'required|max:255',
            'days' => 'required|numeric|min:0',
        ];

        return $this->makeValidator($request->all(), $rules, $page);
    }
}
