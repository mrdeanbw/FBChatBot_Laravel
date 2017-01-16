<?php //namespace App\Http\Controllers\API;
//
//use App\Models\Page;
//use Illuminate\Http\Request;
//use App\Services\WidgetService;
//use App\Transformers\WidgetTransformer;
//use App\Services\Validation\MessageBlockRuleValidator;
//
//abstract class WidgetController extends APIController
//{
//
//    use MessageBlockRuleValidator;
//
//    /**
//     * @type WidgetService
//     */
//    private $widgets;
//
//    /**
//     * SequenceController constructor.
//     * @param WidgetService $widgets
//     */
//    public function __construct(WidgetService $widgets)
//    {
//        $this->widgets = $widgets;
//    }
//
//    /**
//     * @return \Dingo\Api\Http\Response
//     */
//    public function index()
//    {
//        $page = $this->page();
//
//        return $this->collectionResponse($this->widgets->all($page));
//    }
//
//    /**
//     * @param $id
//     * @return \Dingo\Api\Http\Response
//     */
//    public function show($id)
//    {
//        $page = $this->page();
//        $sequence = $this->widgets->find($id, $page);
//
//        return $this->itemResponse($sequence);
//    }
//
//    /**
//     * @param Request $request
//     * @return \Dingo\Api\Http\Response
//     */
//    public function store(Request $request)
//    {
//        $page = $this->page();
//
//        $validator = $this->validator($request, $page);
//
//        if ($validator->fails()) {
//            return $this->errorsResponse($validator->errors());
//        }
//
//        $this->widgets->create($request->all(), $page);
//
//        return $this->response->created();
//    }
//
//    /**
//     * @param         $id
//     * @param Request $request
//     * @return \Dingo\Api\Http\Response
//     */
//    public function update($id, Request $request)
//    {
//        $page = $this->page();
//
//        $validator = $this->validator($request, $page);
//
//        if ($validator->fails()) {
//            return $this->errorsResponse($validator->errors());
//        }
//
//        $this->widgets->update($id, $request->all(), $page);
//
//        return $this->response->created();
//    }
//
//    /**
//     * @param $id
//     * @return \Dingo\Api\Http\Response
//     */
//    public function destroy($id)
//    {
//        $page = $this->page();
//
//        $this->widgets->delete($id, $page);
//
//        return $this->response->accepted();
//    }
//
//    /**
//     * @param Request $request
//     * @param         $page
//     * @return \Illuminate\Validation\Validator
//     */
//    protected function validator(Request $request, Page $page)
//    {
//        $rules = [
//            'name'                 => 'bail|required|max:255',
//            'type'                 => 'bail|required|in:button',
//            'sequence'             => 'bail|array',
//            'sequence.id'          => 'bail|exists:sequences,id,page_id,' . $page->id,
//            'widget_options'       => 'bail|array',
//            'widget_options.color' => 'bail|required_if:type,button|in:blue,white',
//            'widget_options.size'  => 'bail|required_if:type,button|in:standard,large,xlarge',
//        ];
//
//        return $this->makeValidator($request->all(), $rules, $page);
//    }
//
//    protected function transformer()
//    {
//        return new WidgetTransformer();
//    }
//}
