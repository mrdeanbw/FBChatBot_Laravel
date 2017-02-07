<?php namespace App\Services;

use App\Models\Page;
use App\Models\Template;
use Exception;
use Carbon\Carbon;
use App\Models\Card;
use App\Models\Bot;
use App\Models\Text;
use App\Models\Image;
use App\Models\Button;
use App\Models\BaseModel;
use App\Models\Subscriber;
use App\Models\Message;
use App\Models\CardContainer;
use App\Services\Facebook\Sender;
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
     * @param Message    $message
     * @param Subscriber $subscriber
     * @return array
     * @throws Exception
     */
    public function mapToFacebookMessage(Message $message, Subscriber $subscriber)
    {
        if ($message->type == 'text') {
            return $this->mapTextBlock($message, $subscriber);
        }

        if ($message->type == 'image') {
            return $this->mapImage($message);
        }

        if ($message->type == 'card_container') {
            return $this->mapCardContainer($message, $subscriber);
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
     * Return the URL to a main menu button.
     * @param $buttonId
     * @return string
     */
    protected function getMainMenuButtonUrl($buttonId)
    {
        return url(config('app.url') . "mb/{$buttonId}");
    }

    /**
     * Return the URL to the hashed block.
     * @param $modelHash
     * @param $subscriberHash
     * @return string
     */
    protected function getBlockURL($modelHash, $subscriberHash)
    {
        return url(config('app.url') . "ba/{$modelHash}/{$subscriberHash}");
    }

    /**
     * Map Buttons to Facebook call to actions.
     * @param Bot $bot
     * @return array
     */
    public function mapMainMenuButtons(Bot $bot)
    {
        return array_map(function (Button $button) use ($bot) {

            // If the button has a URL action, then we map it to Facebook's web_url.
            if ($button->url) {
                return [
                    "type"  => "web_url",
                    "title" => $button->title,
                    "url"   => $this->getMainMenuButtonUrl($button->id)
                ];
            }

            // Otherwise, we map it to Facebook's postback.
            return [
                'type'    => 'postback',
                'title'   => $button->title,
                'payload' => "{$bot->id}:MM:{$button->id}",
            ];

        }, $bot->main_menu->buttons);
    }


    /**
     * Map Buttons to Facebook call to actions.
     * @param array           $buttons
     * @param Subscriber|null $subscriber
     * @return array
     */
    public function mapButtons(array $buttons, $subscriber = null)
    {
        return array_map(function (Button $button) use ($subscriber) {
            /**
             * If the button is being to sent a subscriber, we create a message instance for it.
             * The generated hash will be for the message instance itself.
             * Otherwise (in case of main menu buttons) the hash will be for the button itself.
             *
             * We differentiate between the two cases by using a special values for main menu hash.
             */
            if ($subscriber) {
                //                $instance = $this->createMessageInstance($button, $subscriber);
                //                $buttonHash = $this->getHashForModel($instance);
            } else {
                //                $buttonHash = $this->getHashForModel($button);
            }

            // If the button has a URL action, then we map it to Facebook's web_url.
            if ($button->url) {
                $subscriberHash = $subscriber? $this->getHashForModel($subscriber) : self::NO_HASH_PLACEHOLDER;

                // @todo Add "http" prefix, if it is not included.
                //            if ($data['url'] && preg_match("#https?://#", $data['url']) === 0) {
                //                $data['url'] = "http://{$data['url']}";
                //            }

                return [
                    "type"  => "web_url",
                    "title" => $button->title,
                    "url"   => $this->getMainMenuButtonUrl($buttonHash, $subscriberHash)
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

        }, $buttons);
    }

    /**
     * Map text blocks to Facebook messages.
     * @param Text       $message
     * @param Subscriber $subscriber
     * @return array
     */
    public function mapTextBlock(Text $message, Subscriber $subscriber)
    {
        $body = $this->evaluateShortcodes($message->text, $subscriber);

        // If the message has no buttons, then we simply map it to Facebook text messages.
        if (! $message->buttons) {
            return [
                'message' => [
                    'text' => $body
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
                        'text'          => $body,
                        'buttons'       => $this->mapButtons($message->buttons, $subscriber)
                    ]
                ]
            ]
        ];
    }


    /**
     * Map image blocks to Facebook attachment.
     * @param Image $image
     * @return array
     */
    function mapImage(Image $image)
    {
        return [
            'message' => [
                'attachment' => [
                    'type'    => 'image',
                    'payload' => [
                        'url' => $image->image_url
                    ]
                ]
            ]
        ];
    }

    /**
     * Map card container to Facebook generic template.
     * @param CardContainer $cardContainer
     * @param Subscriber    $subscriber
     * @return array
     */
    private function mapCardContainer(CardContainer $cardContainer, Subscriber $subscriber)
    {
        return [
            'message' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements'      => $this->mapCards($cardContainer->cards, $subscriber)
                    ]
                ]
            ]
        ];
    }

    /**
     * Map card blocks to Facebook generic template element.
     * @todo modify to reflect Facebook API changes https://developers.facebook.com/docs/messenger-platform/send-api-reference/generic-template
     * @param array      $cards
     * @param Subscriber $subscriber
     * @return array
     */
    private function mapCards(array $cards, Subscriber $subscriber)
    {
        return array_map(function (Card $card) use ($subscriber) {

            //            $instance = $this->createMessageInstance($card, $subscriber);

            $ret = [
                'title'     => $card->title,
                'subtitle'  => $card->subtitle,
                'image_url' => $card->image_url,
                'buttons'   => $this->mapButtons($card->buttons, $subscriber)
            ];

            // @todo Add "http" prefix, if it is not included.
            //            if ($data['url'] && preg_match("#https?://#", $data['url']) === 0) {
            //                $data['url'] = "http://{$data['url']}";
            //            }

            // If the card has a URL.
            if ($card->url) {
                //                $cardHash = $this->getHashForModel($instance);
                //                $subscriberHash = $this->getHashForModel($subscriber);
                //                $ret['item_url'] = $this->getMainMenuButtonUrl($cardHash, $subscriberHash);
                $ret['item_url'] = $card->url;
            }

            return $ret;

        }, $cards);
    }


    /**
     * @todo make sure this is always fired from job queue??
     * Send message blocks to a subscriber, using Facebook API.
     * @param Template   $template
     * @param Subscriber $subscriber
     * @param Bot        $bot
     * @param string     $notificationType
     * @return \object[]
     * @throws Exception
     */
    public function sendTemplate(Template $template, Subscriber $subscriber, Bot $bot, $notificationType = 'REGULAR')
    {
        $ret = [];

        foreach ($template->messages as $message) {

            // Create a message instance for this message block (to keep track of read/click stats)
            //            $messageInstance = $this->createMessageInstance($message, $subscriber);

            // Map our message block representation to the accepted format by Facebook API.
            $message = $this->mapToFacebookMessage($message, $subscriber);

            // Send the message.
            $facebookMessageId = $this->sendMessage($message, $subscriber, $bot->page, $notificationType);

            // Update the message instance
            //            $this->updateMessageInstance($messageInstance, ['facebook_id' => $facebookMessageId]);

//            $ret[] = $messageInstance;
        }

        return $ret;
    }


    /**
     * @param Message     $messageBlock
     * @param Subscriber  $subscriber
     * @param string|null $facebookMessageId
     * @return MessageInstance
     */
    public function createMessageInstance(Message $messageBlock, Subscriber $subscriber, $facebookMessageId = null)
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