<?php namespace App\Transformers;

use Common\Models\WelcomeMessage;

class WelcomeMessageTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['template'];

    public function transform(WelcomeMessage $welcomeMessage)
    {
        return [];
    }
}