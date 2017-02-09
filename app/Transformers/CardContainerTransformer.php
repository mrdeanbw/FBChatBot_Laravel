<?php namespace App\Transformers;

use App\Models\CardContainer;

class CardContainerTransformer extends BaseTransformer
{

    public $defaultIncludes = ['cards'];

    public function transform(CardContainer $cardContainer)
    {
        return [
            'id'       => $cardContainer->id->__toString(),
            'type'     => $cardContainer->type,
            'readonly' => $cardContainer->readonly,
            'cards'    => $this->transformInclude($cardContainer->cards, new CardTransformer())
        ];
    }
}