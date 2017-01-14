<?php
//
//namespace App\Http\Controllers\API\Build;
//
//use App\Http\Controllers\API\APIController;
//use App\Models\Template;
//use App\Services\TemplateService;
//use App\Services\Validation\MessageBlockRuleValidator;
//use App\Transformers\BaseTransformer;
//use App\Transformers\TemplateTransformer;
//use Illuminate\Http\Request;
//use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
//
//class TemplateController extends APIController
//{
//
//    use MessageBlockRuleValidator;
//
//    /**
//     * @type TemplateService
//     */
//    private $templates;
//
//    /**
//     * TemplateController constructor.
//     * @param TemplateService $templates
//     */
//    public function __construct(TemplateService $templates)
//    {
//        $this->templates = $templates;
//    }
//
//    /**
//     * @return \Dingo\Api\Http\Response
//     */
//    public function index()
//    {
//        $page = $this->page();
//
//        return $this->collectionResponse($this->templates->templates($page));
//    }
//
//    /**
//     * @param         $id
//     * @return \Dingo\Api\Http\Response
//     */
//    public function show($id)
//    {
//        $page = $this->page();
//        $template = $this->templates->findTemplate($id, $page);
//
//        return $this->itemResponse($template);
//    }
//
//    /**
//     * @param Request $request
//     * @return \Symfony\Component\HttpFoundation\Response
//     */
//    public function store(Request $request)
//    {
//        return $this->persist(null, $request);
//    }
//
//    /**
//     * @param         $id
//     * @param Request $request
//     * @return \Symfony\Component\HttpFoundation\Response
//     */
//    public function update($id, Request $request)
//    {
//        return $this->persist($id, $request);
//    }
//
//    /**
//     * @param         $id
//     * @param Request $request
//     * @return \Symfony\Component\HttpFoundation\Response
//     */
//    protected function persist($id, Request $request)
//    {
//        $page = $this->page();
//
//        $validator = $this->validator($id, $request, $page);
//
//        if ($validator->fails()) {
//            return $this->errorsResponse($validator->errors());
//        }
//
//        if ($id) {
//            $this->templates->updateTemplate($id, $page, $request->all());
//        } else {
//            $this->templates->createTemplate($page, $request->all());
//        }
//
//        return response([]);
//    }
//
//    /**
//     * @param         $id
//     * @param Request $request
//     * @param         $page
//     * @return \Illuminate\Validation\Validator
//     */
//    protected function validator($id, Request $request, $page)
//    {
//        $idString = $id? "{$id}" : "NULL";
//
//        $rules = [
//            'name' => "bail|required|max:255|unique:templates,name,{$idString},id,page_id,{$page->id}"
//        ];
//
//        return $this->makeValidator($request->all(), $rules, $page);
//    }
//
//
//    /** @return BaseTransformer */
//    protected function transformer()
//    {
//        return new TemplateTransformer();
//    }
//}
