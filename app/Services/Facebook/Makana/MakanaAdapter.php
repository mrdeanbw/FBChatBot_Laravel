<?php
namespace App\Services\Facebook\Makana;

use App\Models\BaseModel;
use App\Models\Button;
use App\Models\Card;
use App\Models\CardContainer;
use App\Models\HasMessageBlocksInterface;
use App\Models\Image;
use App\Models\MessageBlock;
use App\Models\MessageInstance;
use App\Models\Page;
use App\Models\Subscriber;
use App\Models\Text;
use App\Services\URLShortener;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Log;

class MakanaAdapter
{

    CONST NO_HASH_PLACEHOLDER = "MAIN_MENU";

    /**
     * @type Sender
     */
    private $MakanaSender;

    /**
     * MakanaAdapter constructor.
     *
     * @param Sender $MakanaSender
     */
    public function __construct(Sender $MakanaSender)
    {
        $this->MakanaSender = $MakanaSender;
    }


    /**
     * @param            $message
     * @param Subscriber $subscriber
     *
     * @return array
     */
    public function addRecipientHeader($message, Subscriber $subscriber)
    {
        $message['recipient'] = [
            'id' => $subscriber->facebook_id
        ];

        return $message;
    }

    /**
     * @param $message
     * @param $notificationType
     * @return array
     */
    public function addNotificationType($message, $notificationType)
    {
        $message['notification'] = $notificationType;

        return $message;
    }

    /**
     * @param MessageBlock $messageBlock
     * @param Subscriber   $subscriber
     * @return array
     * @throws Exception
     */
    public function mapToFacebookMessage(MessageBlock $messageBlock, Subscriber $subscriber)
    {
        if ($messageBlock->type == 'text') {
            return $this->mapTextBlock($messageBlock, $subscriber);
        }

        if ($messageBlock->type == 'image') {
            return $this->mapImage($messageBlock);
        }

        if ($messageBlock->type == 'card_container') {
            return $this->mapCardContainer($messageBlock, $subscriber);
        }

        throw new Exception("Unknown Message Block");
    }


    /**
     * @param BaseModel $model
     * @return bool|string
     */
    private function getHashForModel(BaseModel $model)
    {
        $hash = URLShortener::encode($model->id);

        return $hash;
    }

    /**
     * @param $modelHash
     * @param $subscriberHash
     * @return string
     */
    function getButtonUrl($modelHash, $subscriberHash)
    {
        return url(config('app.url') . "ba/{$modelHash}/{$subscriberHash}");
    }

    /**
     * @param Collection $messageBlocks
     * @param Subscriber $subscriber
     *
     * @return array
     */
    public function mapButtons(Collection $messageBlocks, $subscriber = null)
    {
        return $messageBlocks->map(function (Button $button) use ($subscriber) {

            if ($subscriber) {
                $instance = $this->createMessageInstance($button, $button->page->id, $subscriber->id);
                $buttonHash = $this->getHashForModel($instance);
            } else {
                $buttonHash = $this->getHashForModel($button);
            }


            if ($button->url) {
                $subscriberHash = $subscriber? $this->getHashForModel($subscriber) : self::NO_HASH_PLACEHOLDER;

                return [
                    "type"  => "web_url",
                    "title" => $button->title,
                    "url"   => $this->getButtonUrl($buttonHash, $subscriberHash)
                ];
            }

            if (! $subscriber) {
                $buttonHash = "MAIN_MENU_{$buttonHash}";
            }

            return [
                'type'    => 'postback',
                'title'   => $button->title,
                'payload' => $buttonHash,
            ];

        })->toArray();
    }

    /**
     * @param Text       $textBlock
     * @param Subscriber $subscriber
     *
     * @return array
     */
    public function mapTextBlock(Text $textBlock, $subscriber)
    {
        $text = $this->evaluateShortcodes($textBlock->text, $subscriber);

        if ($textBlock->message_blocks->isEmpty()) {
            return [
                'message' => [
                    'text' => $text
                ]
            ];
        }

        return [
            'message' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text'          => $text,
                        'buttons'       => $this->mapButtons($textBlock->message_blocks, $subscriber)
                    ]
                ]
            ]
        ];
    }


    /**
     * @param Image $messageBlock
     * @return array
     */
    function mapImage(Image $messageBlock)
    {
        return [
            'message' => [
                'attachment' => [
                    'type'    => 'image',
                    'payload' => [
                        'url' => $messageBlock->image_url
                    ]
                ]
            ]
        ];
    }

    /**
     * @param CardContainer $messageBlock
     * @param Subscriber    $subscriber
     * @return array
     */
    private function mapCardContainer(CardContainer $messageBlock, $subscriber)
    {
        return [
            'message' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements'      => $this->mapCards($messageBlock->message_blocks, $subscriber)
                    ]
                ]
            ]
        ];
    }

    /**
     * @param Collection $messageBlocks
     * @param Subscriber $subscriber
     *
     * @return array
     */
    private function mapCards($messageBlocks, Subscriber $subscriber)
    {
        return $messageBlocks->map(function (Card $card) use ($subscriber) {

            $instance = $this->createMessageInstance($card, $card->page->id, $subscriber->id);
            $cardHash = $this->getHashForModel($instance);
            $subscriberHash = $this->getHashForModel($subscriber);

            return [
                'title'     => $card->title,
                'subtitle'  => $card->subtitle,
                'image_url' => $card->image_url,
                'item_url'  => $this->getButtonUrl($cardHash, $subscriberHash),
                'buttons'   => $this->mapButtons($card->message_blocks, $subscriber)
            ];

        })->toArray();
    }


    /**
     * @param HasMessageBlocksInterface $model
     * @param Subscriber                $subscriber
     * @param string                    $notificationType
     *
     * @return object[]
     */
    public function sendBlocks(HasMessageBlocksInterface $model, Subscriber $subscriber, $notificationType = 'REGULAR')
    {
        $ret = [];
        foreach ($model->message_blocks as $messageBlock) {
            $messageInstance = $this->createMessageInstance($messageBlock, $model->page->id, $subscriber->id);
            $message = $this->mapToFacebookMessage($messageBlock, $subscriber);
            $facebookMessageId = $this->sendMessage($message, $subscriber, $model->page, $notificationType);
            $messageInstance->facebook_id = $facebookMessageId;
            $messageInstance->save();
            $ret[] = $messageInstance;
        }

        return $ret;
    }


    /**
     * @param MessageBlock $messageBlock
     * @param              $pageId
     * @param              $subscriberId
     * @param null         $facebookMessageId
     * @return MessageInstance
     */
    public function createMessageInstance(MessageBlock $messageBlock, $pageId, $subscriberId, $facebookMessageId = null)
    {
        $record = new MessageInstance();
        $record->message_block_id = $messageBlock->id;
        $record->subscriber_id = $subscriberId;
        $record->page_id = $pageId;
        $record->facebook_id = $facebookMessageId;
        $record->sent_at = Carbon::now();
        $record->save();

        return $record;
    }


    /**
     * @param [] $message
     * @param Subscriber $subscriber
     * @param Page       $page
     * @param string     $notificationType
     *
     * @return \object[]
     */
    public function sendMessage($message, Subscriber $subscriber, Page $page, $notificationType = 'REGULAR')
    {
        $message = $this->addRecipientHeader($message, $subscriber);
        $message = $this->addNotificationType($message, $notificationType);

        $response = $this->MakanaSender->send($page->access_token, $message, false);
        
//        Log::debug("[Sending Message] Request:", json_decode(json_encode($message), true));
//        Log::debug("[Sending Message] Response:", json_decode(json_encode($response), true));

        return $response->message_id;
    }

    /**
     * @param            $text
     * @param Subscriber $subscriber
     * @return mixed
     */
    private function evaluateShortcodes($text, Subscriber $subscriber)
    {
        return str_replace([
            '{{first_name}}',
            '{{last_name}}',
            '{{full_name}}'
        ], [
            $subscriber->first_name,
            $subscriber->last_name,
            $subscriber->full_name
        ], $text);
    }

}