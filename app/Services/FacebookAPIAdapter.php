<?php namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Card;
use App\Models\Page;
use App\Models\Text;
use App\Models\Image;
use App\Models\Button;
use App\Models\BaseModel;
use App\Models\Subscriber;
use App\Models\MessageBlock;
use App\Models\CardContainer;
use App\Models\MessageInstance;
use App\Services\Facebook\Sender;
use App\Models\HasMessageBlocksInterface;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\MessageInstance\MessageInstanceRepository;

class FacebookAPIAdapter
{

    CONST NO_HASH_PLACEHOLDER = "MAIN_MENU";

    /**
     * @type Sender
     */
    private $FacebookSender;
    /**
     * @type MessageInstanceRepository
     */
    private $messageInstanceRepo;

    /**
     * FacebookAPIAdapter constructor.
     *
     * @param MessageInstanceRepository $messageInstanceRepo
     * @param Sender                    $FacebookSender
     */
    public function __construct(MessageInstanceRepository $messageInstanceRepo, Sender $FacebookSender)
    {
        $this->FacebookSender = $FacebookSender;
        $this->messageInstanceRepo = $messageInstanceRepo;
    }

    /**
     * Add recipient information to the message.
     * @param array      $message
     * @param Subscriber $subscriber
     * @return array
     */
    public function addRecipientHeader(array $message, Subscriber $subscriber)
    {
        $message['recipient'] = [
            'id' => $subscriber->facebook_id
        ];

        return $message;
    }

    /**
     * Add the notification type to the message.
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
     * Map message block to the array format accepted by Facebook API.
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
     * Generate the encrypted code for the model ID.
     * @param BaseModel $model
     * @return bool|string
     */
    private function getHashForModel(BaseModel $model)
    {
        $hash = SimpleEncryptionService::encode($model->id);

        return $hash;
    }

    /**
     * Return the URL to the hashed block.
     * @param $modelHash
     * @param $subscriberHash
     * @return string
     */
    function getBlockURL($modelHash, $subscriberHash)
    {
        return url(config('app.url') . "ba/{$modelHash}/{$subscriberHash}");
    }

    /**
     * Map Buttons to Facebook call to actions.
     * @param Collection      $messageBlocks
     * @param Subscriber|null $subscriber
     * @return array
     */
    public function mapButtons(Collection $messageBlocks, $subscriber = null)
    {
        return $messageBlocks->map(function (Button $button) use ($subscriber) {

            /**
             * If the button is being to sent a subscriber, we create a message instance for it.
             * The generated hash will be for the message instance itself.
             * Otherwise (in case of main menu buttons) the hash will be for the button itself.
             *
             * We differentiate between the two cases by using a special values for main menu hash.
             */
            if ($subscriber) {
                $instance = $this->createMessageInstance($button, $subscriber);
                $buttonHash = $this->getHashForModel($instance);
            } else {
                $buttonHash = $this->getHashForModel($button);
            }

            // If the button has a URL action, then we map it to Facebook's web_url.
            if ($button->url) {
                $subscriberHash = $subscriber? $this->getHashForModel($subscriber) : self::NO_HASH_PLACEHOLDER;

                return [
                    "type"  => "web_url",
                    "title" => $button->title,
                    "url"   => $this->getBlockURL($buttonHash, $subscriberHash)
                ];
            }

            if (! $subscriber) {
                $buttonHash = "MAIN_MENU_{$buttonHash}";
            }

            // Otherwise, we map it to Facebook's postback.
            return [
                'type'    => 'postback',
                'title'   => $button->title,
                'payload' => $buttonHash,
            ];

        })->toArray();
    }

    /**
     * Map text blocks to Facebook messages.
     * @param Text       $textBlock
     * @param Subscriber $subscriber
     * @return array
     */
    public function mapTextBlock(Text $textBlock, Subscriber $subscriber)
    {
        $text = $this->evaluateShortcodes($textBlock->text, $subscriber);

        // If the message has no buttons, then we simply map it to Facebook text messages.
        if ($textBlock->message_blocks->isEmpty()) {
            return [
                'message' => [
                    'text' => $text
                ]
            ];
        }

        // Otherwise, we map it to Facebook templates.
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
     * Map image blocks to Facebook attachment.
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
     * Map card container to Facebook generic template.
     * @param CardContainer $messageBlock
     * @param Subscriber    $subscriber
     * @return array
     */
    private function mapCardContainer(CardContainer $messageBlock, Subscriber $subscriber)
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
     * Map card blocks to Facebook generic template element.
     * @todo modify to reflect Facebook API changes https://developers.facebook.com/docs/messenger-platform/send-api-reference/generic-template
     * @param Collection $messageBlocks
     * @param Subscriber $subscriber
     * @return array
     */
    private function mapCards($messageBlocks, Subscriber $subscriber)
    {
        return $messageBlocks->map(function (Card $card) use ($subscriber) {

            $instance = $this->createMessageInstance($card, $subscriber);

            $ret = [
                'title'     => $card->title,
                'subtitle'  => $card->subtitle,
                'image_url' => $card->image_url,
                'buttons'   => $this->mapButtons($card->message_blocks, $subscriber)
            ];

            // If the card has a URL.
            if ($card->url) {
                $cardHash = $this->getHashForModel($instance);
                $subscriberHash = $this->getHashForModel($subscriber);
                $ret['item_url'] = $this->getBlockURL($cardHash, $subscriberHash);
            }

            return $ret;

        })->toArray();
    }


    /**
     * Send message blocks to a subscriber, using Facebook API.
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

            // Create a message instance for this message block (to keep track of read/click stats)
            $messageInstance = $this->createMessageInstance($messageBlock, $subscriber);

            // Map our message block representation to the accepted format by Facebook API.
            $message = $this->mapToFacebookMessage($messageBlock, $subscriber);

            // Send the message.
            $facebookMessageId = $this->sendMessage($message, $subscriber, $model->page, $notificationType);

            // Update the message instance
            $this->updateMessageInstance($messageInstance, ['facebook_id' => $facebookMessageId]);

            $ret[] = $messageInstance;
        }

        return $ret;
    }


    /**
     * @param MessageBlock $messageBlock
     * @param Subscriber   $subscriber
     * @param string|null  $facebookMessageId
     * @return MessageInstance
     */
    public function createMessageInstance(MessageBlock $messageBlock, Subscriber $subscriber, $facebookMessageId = null)
    {
        $data = [
            'facebook_id' => $facebookMessageId,
            'sent_at'     => Carbon::now()
        ];

        return $this->messageInstanceRepo->create($data, $messageBlock, $subscriber);
    }

    /**
     * @param MessageInstance $messageInstance
     * @param array           $data
     */
    private function updateMessageInstance(MessageInstance $messageInstance, array $data)
    {
        $this->messageInstanceRepo->update($messageInstance, $data);
    }


    /**
     * Add recipient header, notification type and send the message through Facebook API.
     * @param array      $message
     * @param Subscriber $subscriber
     * @param Page       $page
     * @param string     $notificationType
     * @return \object[]
     */
    public function sendMessage(array $message, Subscriber $subscriber, Page $page, $notificationType = 'REGULAR')
    {
        $message = $this->addRecipientHeader($message, $subscriber);
        $message = $this->addNotificationType($message, $notificationType);

        $response = $this->FacebookSender->send($page->access_token, $message, false);

        //        Log::debug("[Sending Message] Request:", json_decode(json_encode($message), true));
        //        Log::debug("[Sending Message] Response:", json_decode(json_encode($response), true));

        return $response->message_id;
    }

    /**
     * Evaluate supported shortcodes
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