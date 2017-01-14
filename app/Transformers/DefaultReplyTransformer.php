<?php
namespace App\Transformers;


use App\Models\DefaultReply;

class DefaultReplyTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['message_blocks'];
    
    public function transform(DefaultReply $defaultReply)
    {
        return [
            'id' => $defaultReply->id,
        ];
    }
}