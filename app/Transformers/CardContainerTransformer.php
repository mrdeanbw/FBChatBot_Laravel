<?php namespace App\Transformers;

use App\Models\CardContainer;
use Carbon\Carbon;

class CardContainerTransformer extends BaseTransformer
{

    public $defaultIncludes = ['cards'];

    public function transform(CardContainer $cardContainer)
    {
        return [
            'id'       => $cardContainer->id,
            'type'     => $cardContainer->type,
            'readonly' => $cardContainer->readonly,
            'card'     => $this->transformInclude($cardContainer->cards, new CardTransformer())
        ];
    }
}