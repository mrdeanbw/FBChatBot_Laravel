<?php namespace Common\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property string    $name
 * @property bool      $explicit
 * @property Message[] $messages
 * @property Message[] $clean_messages
 * @property Bot       $bot
 * @property ObjectID  bot_id
 */
class Template extends BaseModel
{

    use HasEmbeddedArrayModels;

    /**
     * @param array $attributes
     * @param bool  $sync
     * @return BaseModel
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        if ($messages = array_get($attributes, 'messages')) {
            $attributes['messages'] = $this->recursivelyConstructMessage($messages);
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

            if ($message['type'] == 'button' && $buttonMessages = array_get($message, 'messages')) {
                $message['messages'] = $this->recursivelyConstructMessage($buttonMessages);
            }
            $messages[$i] = Message::factory($message);
        }

        return $messages;
    }

    public function getCleanMessagesAttribute()
    {
        return $this->recursivelyRemoveDeletedMessages($this->messages);
    }

    private function recursivelyRemoveDeletedMessages(array $messages)
    {
        return array_filter($messages, function ($message) {
            if ($message->deleted_at) {
                return false;
            }

            if (in_array($message->type, ['text', 'card']) && $message->buttons) {
                $message->buttons = $this->recursivelyRemoveDeletedMessages($message->buttons);
            }

            if ($message->type == 'card_container') {
                $message->cards = $this->recursivelyRemoveDeletedMessages($message->cards);
            }

            if ($message->type == 'button' && $message->messages) {
                $message->messages = $this->recursivelyRemoveDeletedMessages($message->messages);
            }

            return true;
        });
    }


}
