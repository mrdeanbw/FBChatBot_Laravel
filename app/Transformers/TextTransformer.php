<?php namespace App\Transformers;

use App\Models\Text;
use App\Models\MessageRevision;

class TextTransformer extends BaseTransformer
{

    /**
     * @param Text|MessageRevision $text
     * @return array
     */
    public function transform($text)
    {
        return [
            'id'       => $text->id->__toString(),
            'type'     => $text->type,
            'text'     => $text->text,
            'readonly' => $text->readonly,
            'buttons'  => $this->transformInclude($text->buttons, new MessageTransformer())
        ];
    }

}