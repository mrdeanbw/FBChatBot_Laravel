<?php namespace Common\Models;

/**
 * @property string    $name
 * @property bool      $explicit
 * @property Message[] $messages
 * @property Message[] $clean_messages
 * @property Bot       $bot
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
        return array_filter($this->messages, function ($message) {
            return empty($message->deleted_at);
        });
    }

}
