<?php namespace App\Services;

use Exception;
use App\Models\Bot;
use App\Models\Card;
use App\Models\Text;
use App\Models\Image;
use App\Models\Button;
use App\Models\Message;
use App\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use App\Models\CardContainer;

class FacebookMessageMapper
{

    /**
     * @type Bot
     */
    protected $bot;
    /**
     * @type Subscriber
     */
    protected $subscriber;
    /**
     * @type MessagePayloadEncoder
     */
    public $payloadEncoder;

    /**
     * FacebookMessageMapper constructor.
     * @param Bot $bot
     */
    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
        $this->payloadEncoder = new MessagePayloadEncoder($bot);;
    }

    /**
     * Subscriber setter
     * @param Subscriber $subscriber
     * @return FacebookMessageMapper
     */
    public function forSubscriber(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
        $this->payloadEncoder->setSubscriber($subscriber);

        return $this;
    }

    /**
     * Map Buttons to Facebook call to actions.
     * @return array
     */
    public function mapMainMenuButtons()
    {
        return array_map(function (Button $button) {

            // If the button has a URL action, then we map it to Facebook's web_url.
            if ($button->url) {
                return [
                    "type"  => "web_url",
                    "title" => $button->title,
                    "url"   => $this->payloadEncoder->mainMenuUrl($button)
                ];
            }

            // Otherwise, we map it to Facebook's postback.
            return [
                'type'    => 'postback',
                'title'   => $button->title,
                'payload' => "MM:{$this->bot->id}:{$button->id}|{$button->last_revision_id}",
            ];

        }, $this->bot->main_menu->buttons);
    }

    /**
     * Map message block to the array format accepted by Facebook API.
     * @param Message $message
     * @return array
     * @throws Exception
     */
    public function toFacebookMessage(Message $message)
    {
        if (! $this->subscriber) {
            throw new Exception("Subscriber not defined");
        }

        if ($message->type == 'text') {
            return $this->mapTextBlock($message);
        }

        if ($message->type == 'image') {
            return $this->mapImage($message);
        }

        if ($message->type == 'card_container') {
            return $this->mapCardContainer($message);
        }

        throw new Exception("Unknown Message Block");
    }

    /**
     * Map text blocks to Facebook messages.
     * @param Message|Text $message
     * @return array
     */
    protected function mapTextBlock(Text $message)
    {
        $body = $this->evaluateShortcodes($message->text, $this->subscriber);

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
                        'buttons'       => $this->mapTextButtons($message->buttons, $message->id)
                    ]
                ]
            ]
        ];
    }

    /**
     * Map image blocks to Facebook attachment.
     * @param Message|Image $image
     * @return array
     */
    protected function mapImage(Image $image)
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
     * @param Message|CardContainer $cardContainer
     * @return array
     */
    protected function mapCardContainer(CardContainer $cardContainer)
    {
        return [
            'message' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements'      => $this->mapCards($cardContainer->cards, $cardContainer->id)
                    ]
                ]
            ]
        ];
    }

    /**
     * Map card blocks to Facebook generic template element.
     * @param Card[]   $cards
     * @param ObjectID $cardContainerId
     * @return array
     */
    protected function mapCards(array $cards, $cardContainerId)
    {
        return array_map(function (Card $card) use ($cardContainerId) {

            $ret = [
                'title'     => $card->title,
                'subtitle'  => $card->subtitle,
                'image_url' => $card->image_url,
                'buttons'   => $this->mapCardButtons($card->buttons, $card->id, $cardContainerId)
            ];

            // If the card has a URL.
            if ($card->url) {
                $payload = $this->payloadEncoder->card($card->id, $cardContainerId);
                $ret['default_action'] = [
                    'type' => 'web_url',
                    'url'  => $this->payloadEncoder->url($payload)
                ];
            }

            return $ret;

        }, $cards);
    }

    /**
     * @param array    $buttons
     * @param ObjectID $cardId
     * @param ObjectID $cardContainerId
     * @return array
     */
    protected function mapCardButtons(array  $buttons, $cardId, $cardContainerId)
    {
        return array_map(function (Button $button) use ($cardId, $cardContainerId) {
            $payload = $this->payloadEncoder->cardButton($button->id, $cardId, $cardContainerId);

            return $this->mapButton($button, $payload);
        }, $buttons);
    }

    /**
     * @param array    $buttons
     * @param ObjectID $textId
     * @return array
     */
    protected function mapTextButtons(array  $buttons, $textId)
    {
        return array_map(function (Button $button) use ($textId) {
            $payload = $this->payloadEncoder->textButton($button->id, $textId);

            return $this->mapButton($button, $payload);
        }, $buttons);
    }

    /**
     * Map Buttons to Facebook call to actions.
     * @param Button $button
     * @param string $payload
     * @return array
     */
    protected function mapButton(Button $button, $payload)
    {
        // If the button has a URL action, then we map it to Facebook's web_url.
        if ($button->url) {
            return [
                "type"  => "web_url",
                "title" => $button->title,
                "url"   => $this->payloadEncoder->url($payload)
            ];
        }

        // Otherwise, we map it to Facebook's postback.
        return [
            'type'    => 'postback',
            'title'   => $button->title,
            'payload' => $payload,
        ];

    }

    /**
     * Evaluate supported shortcodes
     * @param            $text
     * @param Subscriber $subscriber
     * @return mixed
     */
    protected function evaluateShortcodes($text, Subscriber $subscriber)
    {
        return str_replace(
            ['{{first_name}}', '{{last_name}}', '{{full_name}}'],
            [$subscriber->first_name, $subscriber->last_name, $subscriber->full_name],
            $text
        );
    }
}