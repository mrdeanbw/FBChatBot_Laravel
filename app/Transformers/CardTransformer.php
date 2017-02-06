<?php namespace App\Transformers;

use App\Models\Card;

class CardTransformer extends BaseTransformer
{

    public $defaultIncludes = ['buttons'];

    public function transform(Card $card)
    {
        return [
            'id'        => $card->id,
            'url'       => $card->url,
            'type'      => $card->type,
            'title'     => $card->title,
            'subtitle'  => $card->subtitle,
            'image_url' => $card->image_url,
            'readonly'  => $card->readonly,
        ];
    }

    public function includeButtons(Card $card)
    {
        return $this->collection($card->buttons, new ButtonTransformer(), false);
    }
}