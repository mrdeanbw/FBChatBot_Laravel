<?php namespace App\Transformers;

use App\Models\WelcomeMessage;

class WelcomeMessageTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['template'];

    public function transform(WelcomeMessage $welcomeMessage)
    {
        return [];
    }
}