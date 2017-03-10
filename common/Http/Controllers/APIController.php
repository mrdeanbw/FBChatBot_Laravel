<?php namespace Common\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Common\Transformers\BaseTransformer;
use Dingo\Api\Exception\ValidationHttpException;

abstract class APIController extends Controller
{

    use Helpers;

    /**
     * @return BaseTransformer
     */
    protected abstract function transformer();

    /**
     * A wrapper around Dingo collection response.
     * @param Collection $collection
     * @return \Dingo\Api\Http\Response
     */
    public function collectionResponse(Collection $collection)
    {
        return $this->response->collection($collection, $this->transformer());
    }

    /**
     * A wrapper around Dingo pagination response.
     * @param Paginator $paginator
     * @return \Dingo\Api\Http\Response
     */
    public function paginatorResponse($paginator)
    {
        return $this->response->paginator($paginator, $this->transformer());
    }

    /**
     * A wrapper around Dingo array response.
     * @param $array
     * @return \Dingo\Api\Http\Response
     */
    public function arrayResponse($array)
    {
        return $this->response->array(['data' => $array]);
    }

    /**
     * A wrapper around Dingo item response.
     * @param $model
     * @return \Dingo\Api\Http\Response
     */
    public function itemResponse($model)
    {
        return $this->response->item($model, $this->transformer());
    }
    
    /**
     * A helper method to make the Validator.
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

        /**
         * If a callback is provided, call it.
         */
        $validator->after(function ($validator) use ($callback, $input) {
            if ($callback) {
                $validator = $callback($validator, $input);
            }

            return $validator;
        });


        /**
         * If the validation fails, terminate the request and return the error messages.
         */
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