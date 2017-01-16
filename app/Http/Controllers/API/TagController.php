<?php namespace App\Http\Controllers\API;

class TagController extends APIController
{

    /**
     * Return a list of page tags.
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        $tags = $this->page()->tags()->pluck('tag')->toArray();

        return $this->arrayResponse($tags);
    }

    protected function transformer()
    {
        return null;
    }
}
