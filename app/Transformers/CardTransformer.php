<?php namespace App\Transformers;

use App\Models\Card;

class CardTransformer extends BaseTransformer
{

    public $defaultIncludes = ['buttons'];

    public function transform(Card $card)
    {
        return [
            'id'        => $card->id->__toString(),
            'url'       => $card->url,
            'type'      => $card->type,
            'title'     => $card->title,
            'subtitle'  => $card->subtitle,
            'image_url' => $card->image_url,
            'readonly'  => $card->readonly,
            'buttons'  => $this->transformInclude($card->buttons, new ButtonTransformer())
        ];
    }
}