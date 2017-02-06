<?php namespace App\Transformers;

use App\Models\GreetingText;

class GreetingTextTransformer extends BaseTransformer
{

    public function transform(GreetingText $greetingText)
    {
        return [
            'text' => $greetingText->text
        ];
    }
}