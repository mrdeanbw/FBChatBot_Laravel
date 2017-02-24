<?php namespace App\Transformers;

use App\Models\Card;
use App\Models\MessageRevision;

class CardTransformer extends BaseTransformer
{

    /**
     * @param Card|MessageRevision $card
     * @return array
     */
    public function transform($card)
    {
        return [
            'id'        => $card->id->__toString(),
            'url'       => $card->url,
            'type'      => $card->type,
            'title'     => $card->title,
            'subtitle'  => $card->subtitle,
            'image_url' => $card->image_url,
            'readonly'  => $card->readonly,
            'buttons'   => $this->transformInclude($card->buttons, new MessageTransformer())
        ];
    }
}