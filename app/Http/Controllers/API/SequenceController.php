<?php namespace App\Http\Controllers\API;

use App\Services\AudienceService;
use DB;
use Illuminate\Http\Request;
use App\Services\SequenceService;
use App\Transformers\BaseTransformer;
use App\Transformers\SequenceTransformer;
use App\Services\Validation\FilterAudienceRuleValidator;

class SequenceController extends APIController
{

    use FilterAudienceRuleValidator;

    /**
     * @type SequenceService
     */
    private $sequences;
    /**
     * @type AudienceService
     */
    private $audience;

    /**
     * SequenceController constructor.
     * @param SequenceService $sequences
     * @param AudienceService $audience
     */
    public function __construct(SequenceService $sequences, AudienceService $audience)
    {
        $this->sequences = $sequences;
        $this->audience = $audience;
    }

    /**
     * List of sequences.
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        $page = $this->page();

        return $this->collectionResponse($this->sequences->all($page));
    }

    /**
     * Return the details of a sequence.
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $page = $this->page();
        $sequence = $this->sequences->findOrFail($id, $page);

        return $this->itemResponse($sequence);
    }

    /**
     * Create a sequence.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $page = $this->page();

        $this->validate($request, [
            'name' => 'required|max:255'
        ]);

        $sequence = $this->sequences->create($request->all(), $page);

        return $this->itemResponse($sequence);
    }

    /**
     * Update a sequence.
     * @param         $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update($id, Request $request)
    {
        $page = $this->page();

        $this->validate(
            $request,
            $this->updateSequenceRules(),
            $this->filterGroupRuleValidationCallback($page)
        );

        $this->sequences->update($id, $request->all(), $page);

        return $this->response->accepted();
    }

    /**
     * Delete a sequence.
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id)
    {
        $page = $this->page();

        $this->sequences->delete($id, $page);

        return $this->response->accepted();
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new SequenceTransformer();
    }

    /**
     * @return array
     */
    private function updateSequenceRules()
    {
        $rules = [
            'name'                          => 'required|max:255',
            'filter_type'                   => 'bail|required|in:and,or',
            'filter_groups'                 => 'bail|array',
            'filter_groups.*'               => 'bail|array',
            'filter_groups.*.type'          => 'bail|required|in:and,or,none',
            'filter_groups.*.rules'         => 'bail|required|array',
            'filter_groups.*.rules.*.key'   => 'bail|required|in:gender,tag',
            'filter_groups.*.rules.*.value' => 'bail|required',
        ];

        return $rules;
    }
}
