<?php namespace App\Transformers;

use App\Models\CardContainer;
use App\Models\MessageRevision;

class CardContainerTransformer extends BaseTransformer
{

    /**
     * @param CardContainer|MessageRevision $cardContainer
     * @return array
     */
    public function transform($cardContainer)
    {
        return [
            'cards'    => $this->transformInclude($cardContainer->cards, new MessageTransformer())
        ];
    }
}