<?php namespace App\Models;

class CardContainer extends Message
{
    public $type = 'card_container';
    /** @type Card[] */
    public $cards;

    /**
     * Text constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->cards = [];
        foreach (array_pull($data, 'cards', []) as $card) {
            $this->cards[] = new Card($card);
        }

        parent::__construct($data);
    }
}
