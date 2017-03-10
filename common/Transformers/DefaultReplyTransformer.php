<?php namespace Common\Transformers;

use Common\Models\DefaultReply;

class DefaultReplyTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['template'];

    public function transform(DefaultReply $defaultReply)
    {
        return [];
    }
}