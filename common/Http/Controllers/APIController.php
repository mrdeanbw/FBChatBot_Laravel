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
    protected function transformer()
    {
        return null;
    }

    /**
     * A wrapper around Dingo collection response.
     * @param Collection      $collection
     * @param BaseTransformer $transformer
     * @return \Dingo\Api\Http\Response
     * @throws \Exception
     */
    public function collectionResponse(Collection $collection, BaseTransformer $transformer = null)
    {
        $transformer = $this->normalizeAndValidateTransformer($transformer);

        return $this->response->collection($collection, $transformer);
    }

    /**
     * A wrapper around Dingo pagination response.
     * @param Paginator       $paginator
     * @param BaseTransformer $transformer
     * @return \Dingo\Api\Http\Response
     * @throws \Exception
     */
    public function paginatorResponse($paginator, BaseTransformer $transformer = null)
    {
        $transformer = $this->normalizeAndValidateTransformer($transformer);

        return $this->response->paginator($paginator, $transformer);
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
     * @param                 $model
     * @param BaseTransformer $transformer
     * @return \Dingo\Api\Http\Response
     * @throws \Exception
     */
    public function itemResponse($model, BaseTransformer $transformer = null)
    {
        $transformer = $this->normalizeAndValidateTransformer($transformer);

        return $this->response->item($model, $transformer);
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

    /**
     * @param BaseTransformer $transformer
     * @return BaseTransformer
     * @throws \Exception
     */
    private function normalizeAndValidateTransformer(BaseTransformer $transformer = null)
    {
        $transformer = $transformer?: $this->transformer();
        if (is_null($transformer)) {
            throw new \Exception("Transformer not set.");
        }

        return $transformer;
    }
}