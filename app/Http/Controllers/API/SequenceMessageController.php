<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Common\Services\SequenceService;
use Common\Transformers\BaseTransformer;
use Common\Transformers\SequenceMessageTransformer;
use MongoDB\BSON\ObjectID;

class SequenceMessageController extends APIController
{

    /**
     * @type SequenceService
     */
    private $sequences;

    public function __construct(SequenceService $sequences)
    {
        $this->sequences = $sequences;
        parent::__construct();
    }

    /**
     * @param $sequenceId
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($sequenceId, $id)
    {
        $id = new ObjectID($id);
        $sequenceId = new ObjectID($sequenceId);
        $sequence = $this->sequences->findByIdForBot($sequenceId, $this->enabledBot());
        $message = $this->sequences->findMessageOrFail($id, $sequence);

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
        $sequenceId = new ObjectID($sequenceId);
        $this->validate($request, $this->allValidationRules());

        $message = $this->sequences->createMessage($request->all(), $sequenceId, $this->enabledBot());

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
        $id = new ObjectID($id);
        $sequenceId = new ObjectID($sequenceId);
        $this->validate($request, $this->allValidationRules());

        $message = $this->sequences->updateMessage($request->all(), $id, $sequenceId, $this->enabledBot());

        return $this->itemResponse($message);
    }

    /**
     * Update a sequence message.
     * @param         $id
     * @param         $sequenceId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function updateConditions($id, $sequenceId, Request $request)
    {
        $id = new ObjectID($id);
        $sequenceId = new ObjectID($sequenceId);
        $this->validate($request, $this->conditionsValidationRules());

        $message = $this->sequences->updateMessageConditions($request->all(), $id, $sequenceId, $this->enabledBot());

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
        $id = new ObjectID($id);
        $sequenceId = new ObjectID($sequenceId);
        $message = $this->sequences->deleteMessage($id, $sequenceId, $this->enabledBot());

        return $this->itemResponse($message);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SequenceMessageTransformer();
    }

    /**
     * @return array
     */
    private function conditionsValidationRules()
    {
        return [
            'conditions'                  => 'bail|required|array',
            'conditions.wait_for'         => 'bail|required|array',
            'conditions.wait_for.days'    => 'bail|required|integer|min:0',
            'conditions.wait_for.hours'   => 'bail|required|integer|between:0,23',
            'conditions.wait_for.minutes' => 'bail|required|integer|between:0,59',
        ];
    }

    /**
     * @return array
     */
    private function allValidationRules()
    {
        return array_merge([
            'name'                => 'bail|required|max:255',
            'template'            => 'bail|required|array',
            'template.messages'   => 'bail|required|array|max:10',
            'template.messages.*' => 'bail|required|message',
        ], $this->conditionsValidationRules());
    }
}
