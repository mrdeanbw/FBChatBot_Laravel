<?php namespace App\Transformers;

use App\Models\Message;

class MessageTransformer extends BaseTransformer
{

    public function transform(Message $message)
    {
        switch ($message->type) {
            case 'text':
                return (new TextTransformer)->transform($message);

            case 'image':
                return (new ImageTransformer)->transform($message);

            case 'card_container':
                return (new CardContainerTransformer)->transform($message);

            case 'card':
                return (new CardTransformer)->transform($message);

            case 'button':
                return (new ButtonTransformer)->transform($message);

            default:
                throw new \Exception("Unknown Message Type");
        }

    }

}