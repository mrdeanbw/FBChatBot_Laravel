<?php namespace Common\Transformers;

use Common\Models\Message;
use Common\Models\MessageRevision;

class MessageTransformer extends BaseTransformer
{

    /**
     * @param Message|MessageRevision $message
     * @return array
     * @throws \Exception
     */
    public function transform($message)
    {
        switch ($message->type) {
            case 'text':
                $ret = (new TextTransformer)->transform($message);
                break;

            case 'image':
                $ret = (new ImageTransformer)->transform($message);
                break;

            case 'card_container':
                $ret = (new CardContainerTransformer)->transform($message);
                break;

            case 'card':
                $ret = (new CardTransformer)->transform($message);
                break;

            case 'button':
                $ret = (new ButtonTransformer)->transform($message);
                break;

            default:
                throw new \Exception("Unknown Message Type");
        }

        if (isset($message->stats)) {
            $ret['stats'] = $message->stats;
        }

        if (is_a($message, MessageRevision::class)) {
            $objectId = $message->_id;
            $ret['created_at'] = $message->created_at->toAtomString();
        } else {
            $objectId = $message->id;
        }

        $ret['id'] = $objectId->__toString();
        $ret['type'] = $message->type;
        $ret['readonly'] = $message->readonly;

        return $ret;
    }

}