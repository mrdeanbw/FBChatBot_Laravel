<?php namespace App\Http\Controllers\API;

class TagController extends APIController
{

    /**
     * Return a list of page tags.
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        return $this->arrayResponse($this->bot()->tags);
    }

    protected function transformer()
    {
        return null;
    }
}
