<?php
namespace App\Transformers;


use App\Models\WelcomeMessage;

class WelcomeMessageTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['message_blocks'];
    
    public function transform(WelcomeMessage $welcomeMessage)
    {
        return [
            'id' => $welcomeMessage->id,
        ];
    }
}