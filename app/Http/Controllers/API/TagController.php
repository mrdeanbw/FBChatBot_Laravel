<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\API\APIController;
use App\Models\Page;
use App\Transformers\BaseTransformer;
use App\Transformers\TagTransformer;
use Illuminate\Http\Request;

class TagController extends APIController
{

    public function index()
    {
        $tags = $this->page()->tags()->pluck('tag')->toArray();

        return $this->arrayResponse($tags);
    }


    public function update(Request $request)
    {
        return response([]);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
    }
}
