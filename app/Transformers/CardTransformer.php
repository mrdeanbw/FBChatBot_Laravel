<?php namespace App\Transformers;

use Common\Models\Card;
use Common\Models\MessageRevision;

class CardTransformer extends BaseTransformer
{

    /**
     * @param Card|MessageRevision $card
     * @return array
     */
    public function transform($card)
    {
        return [
            'url'       => $card->url,
            'title'     => $card->title,
            'subtitle'  => $card->subtitle,
            'image_url' => $card->image_url,
            'buttons'   => $this->transformInclude($card->buttons, new MessageTransformer())
        ];
    }
}