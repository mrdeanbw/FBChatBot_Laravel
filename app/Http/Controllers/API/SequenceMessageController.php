<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\SequenceService;
use App\Transformers\BaseTransformer;
use App\Transformers\SequenceMessageTransformer;

class SequenceMessageController extends APIController
{

    /**
     * @type SequenceService
     */
    private $sequences;

    public function __construct(SequenceService $sequences)
    {
        $this->sequences = $sequences;
    }

    /**
     * @param $sequenceId
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($sequenceId, $id)
    {
        $message = $this->sequences->findMessageOrFail($id, $sequenceId);

        return $this->itemResponse($message);
    }

    /**
     * Create a sequence message.
     * @param         $sequenceId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store($sequenceId, Request $request)
    {
        $this->validate($request, $this->validationRules());

        $message = $this->sequences->createMessage($request->all(), $sequenceId, $this->bot());

        return $this->itemResponse($message);
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
        $this->validate($request, $this->validationRules());

        $message = $this->sequences->updateMessage($request->all(), $id, $sequenceId, $this->bot());

        return $this->itemResponse($message);
    }

    /**
     * Delete a sequence message.
     * @param $id
     * @param $sequenceId
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id, $sequenceId)
    {
        $this->sequences->deleteMessage($id, $sequenceId, $this->bot());

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SequenceMessageTransformer();
    }

    private function validationRules()
    {
        return [
            'name'                        => 'bail|required|max:255',
            'conditions'                  => 'bail|required|array',
            'conditions.wait_for'         => 'bail|required|array',
            'conditions.wait_for.days'    => 'bail|required|integer|min:0',
            'conditions.wait_for.hours'   => 'bail|required|integer|between:0,23',
            'conditions.wait_for.minutes' => 'bail|required|array|between:0,59',
            'template'                    => 'bail|required|array',
            'template.messages'           => 'bail|required|array|max:10',
            'template.messages.*'         => 'bail|required|message',
        ];
    }
}
