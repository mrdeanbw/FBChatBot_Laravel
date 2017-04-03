<?php namespace Common\Transformers;

use Common\Models\CardContainer;
use Common\Models\MessageRevision;

class CardContainerTransformer extends BaseTransformer
{

    /**
     * @param CardContainer|MessageRevision $cardContainer
     * @return array
     */
    public function transform($cardContainer)
    {
        return [
            'cards' => $this->transformInclude($cardContainer->cards, new MessageTransformer())
        ];
    }
}