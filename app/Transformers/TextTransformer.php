<?php namespace App\Transformers;

use App\Models\Text;

class TextTransformer extends BaseTransformer
{

    public function transform(Text $text)
    {
        return [
            'id'       => $text->id,
            'type'     => $text->type,
            'text'     => $text->text,
            'readonly' => $text->readonly,
            'buttons'  => $this->transformInclude($text->buttons, new ButtonTransformer())
        ];
    }

}