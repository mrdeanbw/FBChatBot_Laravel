<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\TemplateService;
use App\Transformers\BaseTransformer;
use App\Transformers\TemplateTransformer;
use App\Services\Validation\MessageValidationHelper;

class TemplateController extends APIController
{

    use MessageValidationHelper;

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
        $bot = $this->bot();
        $trees = $this->templates->explicitTemplates($bot);

        return $this->collectionResponse($trees);
    }

    /**
     * Return the details of a message tree.
     * @param         $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $bot = $this->bot();
        $tree = $this->templates->findExplicitOrFail($id, $bot);

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
        $bot = $this->bot();

        $validator = $this->makeMessageTreeValidator($id, $request, $bot);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        if ($id) {
            // updating
            $tree = $this->templates->updateExplicit($id, $request->all(), $bot);
        } else {
            // creating
            $tree = $this->templates->createExplicit($request->all(), $bot);
        }

        return $this->itemResponse($tree);
    }

    /**
     * Make the validator for message trees.
     * @param         $id
     * @param Request $request
     * @param         $bot
     * @return \Illuminate\Validation\Validator
     */
    protected function makeMessageTreeValidator($id, Request $request, $bot)
    {
        $idString = $id? "{$id}" : "NULL";

        $rules = [
            'name' => "bail|required|max:255|unique:templates,name,{$idString},id,page_id,{$bot->id}"
        ];

        return $this->makeValidator($request->all(), $rules, $bot);
    }


    /** @return BaseTransformer */
    protected function transformer()
    {
        return new TemplateTransformer();
    }
}
