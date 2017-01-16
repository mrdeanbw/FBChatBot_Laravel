<?php namespace App\Http\Controllers\API\Build;

use Illuminate\Http\Request;
use App\Services\TemplateService;
use App\Transformers\BaseTransformer;
use App\Transformers\TemplateTransformer;
use App\Http\Controllers\API\APIController;
use App\Services\Validation\MessageBlockRuleValidator;

class TreeController extends APIController
{

    use MessageBlockRuleValidator;

    /**
     * @type TemplateService
     */
    private $templates;

    /**
     * TemplateController constructor.
     *
     * @param TemplateService $templates
     */
    public function __construct(TemplateService $templates)
    {
        $this->templates = $templates;
    }

    /**
     * Return a list of message trees.
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        $page = $this->page();
        $trees = $this->templates->explicitList($page);

        return $this->collectionResponse($trees);
    }

    /**
     * Return the details of a message tree.
     * @param         $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $page = $this->page();
        $tree = $this->templates->findExplicitOrFail($id, $page);

        return $this->itemResponse($tree);
    }

    /**
     * Create a new message tree.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        return $this->persist($request->get('id'), $request);
    }

    /**
     * Update a message tree.
     * @param         $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        return $this->persist($id, $request);
    }

    /**
     * Validate and persist (create/update) a message tree.
     * @param         $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function persist($id, Request $request)
    {
        $page = $this->page();

        $validator = $this->makeMessageTreeValidator($id, $request, $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        if ($id) {
            // updating
            $tree = $this->templates->update($id, $request->all(), $page);
        } else {
            // creating
            $tree = $this->templates->createExplicit($request->all(), $page);
        }

        return $this->itemResponse($tree);
    }

    /**
     * Make the validator for message trees.
     * @param         $id
     * @param Request $request
     * @param         $page
     * @return \Illuminate\Validation\Validator
     */
    protected function makeMessageTreeValidator($id, Request $request, $page)
    {
        $idString = $id? "{$id}" : "NULL";

        $rules = [
            'name' => "bail|required|max:255|unique:templates,name,{$idString},id,page_id,{$page->id}"
        ];

        return $this->makeValidator($request->all(), $rules, $page);
    }


    /** @return BaseTransformer */
    protected function transformer()
    {
        return new TemplateTransformer();
    }
}
