<?php namespace App\Http\Controllers\API;

use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Illuminate\Http\Request;
use Common\Services\TemplateService;
use Common\Transformers\BaseTransformer;
use Common\Transformers\TemplateTransformer;

class TemplateController extends APIController
{

    /**
     * @type TemplateService
     */
    private $templates;

    /**
     * TemplateController constructor.
     * @param TemplateService $templates
     */
    public function __construct(TemplateService $templates)
    {
        $this->templates = $templates;
        parent::__construct();
    }

    /**
     * Return a list of message trees.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $paginator = $this->templates->paginateExplicit(
            $this->enabledBot(),
            (int)$request->get('page', 1),
            ['name' => $request->get('name')]
        );

        return $this->paginatorResponse($paginator);
    }

    /**
     * Return the details of a message tree.
     * @param  $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        $id = new ObjectID($id);
        $template = $this->templates->findExplicitOrFail($id, $this->enabledBot());

        return $this->itemResponse($template);
    }

    /**
     * Create a new message tree.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'name'       => "bail|required|max:255|unique_template_name",
            'messages'   => 'bail|required|array|max:10',
            'messages.*' => 'bail|required|message',
        ], true);

        $template = $this->templates->createExplicit($request->all(), $bot);

        return $this->itemResponse($template);
    }

    /**
     * Update a message tree.
     * @param string  $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        $id = new ObjectID($id);
        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'name'       => "bail|required|max:255|unique_template_name:{$id}",
            'messages'   => 'bail|required|array|max:10',
            'messages.*' => 'bail|required|message',
        ], true);
        $template = $this->templates->updateExplicit($id, $request->all(), $bot);

        return $this->itemResponse($template);
    }

    /**
     * @return BaseTransformer
     */
    protected function transformer()
    {
        return new TemplateTransformer();
    }
}
