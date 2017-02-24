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
            'id'       => $cardContainer->id->__toString(),
            'type'     => $cardContainer->type,
            'readonly' => $cardContainer->readonly,
            'cards'    => $this->transformInclude($cardContainer->cards, new MessageTransformer())
        ];
    }
}