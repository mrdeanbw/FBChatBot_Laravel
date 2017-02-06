<?php namespace App\Transformers;

use App\Models\Text;

class TextTransformer extends BaseTransformer
{

    public $defaultIncludes = ['buttons'];

    public function transform(Text $text)
    {
        return [
            'id'       => $text->id,
            'type'     => $text->type,
            'text'     => $text->text,
            'readonly' => $text->readonly,
        ];
    }

    public function includeButtons(Text $text)
    {
        return $this->collection($text->buttons, new ButtonTransformer(), false);
    }
}