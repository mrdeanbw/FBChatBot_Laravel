<?php namespace App\Transformers;

use App\Models\DefaultReply;

class DefaultReplyTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['template'];

    public function transform(DefaultReply $defaultReply)
    {
        return [];
    }
}