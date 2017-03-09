<?php namespace App\Transformers;

use Common\Models\GreetingText;

class GreetingTextTransformer extends BaseTransformer
{

    public function transform(GreetingText $greetingText)
    {
        return [
            'text' => $greetingText->text
        ];
    }
}