<?php namespace Common\Services;

use Exception;
use Common\Models\Bot;
use Common\Models\Card;
use Common\Models\Text;
use Common\Models\Image;
use Common\Models\Button;
use Common\Models\Message;
use MongoDB\BSON\ObjectID;
use Common\Models\Subscriber;
use Common\Models\CardContainer;
use Intervention\Image\ImageManagerStatic;

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
                'payload' => "mm|r:{$button->last_revision_id}",
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
                        'buttons'       => $this->mapTextButtons($message->buttons, $message->id, $message->last_revision_id)
                    ]
                ]
            ]
        ];
    }

    protected function getRandomOnlineImage($localImagePath = null)
    {
        if (! $localImagePath) {
            return 'https://unsplash.it/400/?random';
        }

        $localImage = ImageManagerStatic::make($localImagePath);
        $width = $localImage->width();
        $height = $localImage->height();

        return "https://unsplash.it/{$width}/{$height}/?random";
    }

    /**
     * Map image blocks to Facebook attachment.
     * @param Message|Image $image
     * @return array
     */
    protected function mapImage(Image $image)
    {
        $imageUrl = $image->image_url;
        if (app()->environment('local')) {
            $imageUrl = $this->getRandomOnlineImage(isset($image->file->path)? $image->file->path : null);
        }

        return [
            'message' => [
                'attachment' => [
                    'type'    => 'image',
                    'payload' => [
                        'url' => $imageUrl
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
                        'elements'      => $this->mapCards($cardContainer->cards, $cardContainer->id, $cardContainer->last_revision_id)
                    ]
                ]
            ]
        ];
    }

    /**
     * Map card blocks to Facebook generic template element.
     * @param Card[]        $cards
     * @param ObjectID      $cardContainerId
     * @param ObjectID|null $lastRevisionId
     * @return array
     */
    protected function mapCards(array $cards, ObjectID $cardContainerId, ObjectID $lastRevisionId = null)
    {
        return array_map(function (Card $card) use ($cardContainerId, $lastRevisionId) {

            $imageUrl = $card->image_url;
            if ($imageUrl && app()->environment('local')) {
                $imageUrl = $this->getRandomOnlineImage(isset($card->file->path)? $card->file->path : null);
            }

            $ret = [
                'title'     => $card->title,
                'subtitle'  => $card->subtitle,
                'image_url' => $imageUrl,
            ];

            if ($card->buttons) {
                $ret['buttons'] = $this->mapCardButtons($card->buttons, $card->id, $cardContainerId, $lastRevisionId);
            }

            // If the card has a URL.
            if ($card->url) {
                $ret['default_action'] = [
                    'type' => 'web_url',
                    'url'  => $this->payloadEncoder->card($card->id, $cardContainerId, $lastRevisionId)
                ];
            }

            return $ret;

        }, $cards);
    }

    /**
     * @param array         $buttons
     * @param ObjectID      $cardId
     * @param ObjectID      $cardContainerId
     * @param ObjectID|null $lastRevisionId
     * @return array
     */
    protected function mapCardButtons(array $buttons, ObjectID $cardId, ObjectID $cardContainerId, ObjectID $lastRevisionId = null)
    {
        return array_map(function (Button $button) use ($cardId, $lastRevisionId, $cardContainerId) {
            $payload = $this->payloadEncoder->cardButton($button, $cardId, $cardContainerId, $lastRevisionId);

            return $this->mapButton($button, $payload);
        }, $buttons);
    }

    /**
     * @param array         $buttons
     * @param ObjectID      $textId
     * @param ObjectID|null $lastRevisionId
     * @return array
     */
    protected function mapTextButtons(array $buttons, ObjectID $textId, ObjectID $lastRevisionId = null)
    {
        return array_map(function (Button $button) use ($lastRevisionId, $textId) {
            $payload = $this->payloadEncoder->textButton($button, $textId, $lastRevisionId);

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
                'type'  => 'web_url',
                'title' => $button->title,
                'url'   => $payload,
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
     * @param string     $text
     * @param Subscriber $subscriber
     * @return string
     */
    protected function evaluateShortcodes($text, Subscriber $subscriber)
    {
        return replace_text_vars($text, [
            'first_name' => $subscriber->first_name,
            'last_name'  => $subscriber->last_name,
            'full_name'  => $subscriber->full_name,
            'page_name'  => $this->bot->page->name,
        ], 320);
    }
}