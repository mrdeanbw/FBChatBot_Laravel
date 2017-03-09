<?php namespace Common\Models;

class CardContainer extends Message
{
    public $type = 'card_container';
    /** @type Card[] */
    public $cards;

    /**
     * Text constructor.
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        $this->cards = [];
        foreach (array_pull($data, 'cards', []) as $card) {
            $this->cards[] = new Card($card, $strict);
        }

        parent::__construct($data, $strict);
    }
}
