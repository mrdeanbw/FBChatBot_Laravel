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
            'text'    => $text->text,
            'buttons' => $this->transformInclude($text->buttons, new MessageTransformer())
        ];
    }

}