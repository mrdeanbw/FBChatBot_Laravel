<?php namespace App\Transformers;

use App\Models\CardContainer;

class CardContainerTransformer extends BaseTransformer
{

    public $defaultIncludes = ['cards'];

    public function transform(CardContainer $cardContainer)
    {
        return [
            'id'        => $cardContainer->id,
            'type'      => $cardContainer->type,
            'readonly'  => $cardContainer->readonly,
        ];
    }

    public function includeCards(CardContainer $cardContainer)
    {
        return $this->collection($cardContainer->cards, new CardTransformer(), false);
    }
}