<?php namespace Common\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property array     $stats
 * @property ObjectID  $message_id
 * @property ImageFile $file
 * @property Card[]    $cards
 * @property string    type
 * @property Button[]  buttons
 * @property ObjectID  bot_id
 * @property array     clicks
 */
class MessageRevision extends BaseModel
{

    use HasEmbeddedArrayModels;

    /**
     * @param array $attributes
     * @param bool  $sync
     * @return BaseModel
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        if ($file = array_get($attributes, 'file')) {
            $attributes['file'] = new ImageFile($file);
        }

        if ($cards = array_get($attributes, 'cards')) {
            $attributes['cards'] = $this->recursivelyConstructMessage($cards);
        }

        if ($buttons = array_get($attributes, 'buttons')) {
            $attributes['buttons'] = $this->recursivelyConstructMessage($buttons);
        }

        return parent::setRawAttributes($attributes, $sync);
    }


    /**
     * @param array $messages
     * @return array
     */
    private function recursivelyConstructMessage(array $messages)
    {
        foreach ($messages as $i => $message) {
            if (in_array($message['type'], ['text', 'card']) && $buttons = array_get($message, 'buttons')) {
                $message['buttons'] = $this->recursivelyConstructMessage($buttons);
            }
            if ($message['type'] === 'card_container' && $cards = array_get($message, 'cards')) {
                $message['cards'] = $this->recursivelyConstructMessage($cards);
            }
            if (in_array($message['type'], ['image', 'card']) && $file = array_get($message, 'file')) {
                $message['file'] = new ImageFile($file);
            }
            $messages[$i] = Message::factory($message);
        }

        return $messages;
    }
}
