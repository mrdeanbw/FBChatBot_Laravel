<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use App\Transformers\BaseTransformer;
use Dingo\Api\Exception\ValidationHttpException;
use Dingo\Api\Routing\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

abstract class APIController extends Controller
{

    use Helpers {
        user as APIUser;
    }

    /**
     * @type Page
     */
    protected $page;

    /**
     * @return Page
     */
    protected function page()
    {
        if ($this->page) {
            return $this->page;
        }

        $pageId = $this->getPageIdFromUrlParameters(app('request'));

        if (! $pageId) {
            $this->response->errorBadRequest("Page Not Specified.");
        }

        return $this->page = $this->user()->pages()->findOrFail($pageId);
    }

    /**
     * @return User
     */
    protected function user()
    {
        return $this->APIUser();
    }


    /** @return BaseTransformer */
    protected abstract function transformer();

    /**
     * @param Collection $collection
     * @return \Dingo\Api\Http\Response
     */
    public function collectionResponse(Collection $collection)
    {
        return $this->response->collection($collection, $this->transformer());
    }

    /**
     * @param Paginator $paginator
     * @return \Dingo\Api\Http\Response
     */
    public function paginatorResponse($paginator)
    {
        return $this->response->paginator($paginator, $this->transformer());
    }

    /**
     * @param $array
     * @return \Dingo\Api\Http\Response
     */
    public function arrayResponse($array)
    {
        return $this->response->array(['data' => $array]);
    }

    /**
     * @param $model
     * @return \Dingo\Api\Http\Response
     */
    public function itemResponse($model)
    {
        return $this->response->item($model, $this->transformer());
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function getPageIdFromUrlParameters(Request $request)
    {
        $routeParameters = $request->route()[2];

        $pageId = array_get($routeParameters, 'pageId');

        if (! $pageId) {
            $pageId = array_get($routeParameters, 'id');

            return $pageId;
        }

        return $pageId;
    }


    /**
     * @param Request       $request
     * @param array         $rules
     * @param callable|null $callback
     * @param array         $messages
     * @param array         $customAttributes
     */
    public function validate(Request $request, array $rules, $callback = null, array $messages = [], array $customAttributes = [])
    {
        $input = $request->all();

        $validator = \Validator::make($input, $rules, $messages, $customAttributes);

        $validator->after(function ($validator) use ($callback, $input) {
            if ($callback) {
                $validator = $callback($validator, $input);
            }

            return $validator;
        });


        if ($validator->fails()) {
            $this->errorsResponse($validator->errors());
        }
    }
    /**
     * @param $errors
     */
    protected function errorsResponse($errors)
    {
        throw new ValidationHttpException($errors);
    }
}