<?php namespace App\Services;

use App\Models\Bot;
use App\Models\Button;
use App\Models\Template;
use App\Models\Broadcast;
use App\Models\Subscriber;
use MongoDB\BSON\ObjectID;

class MessagePayloadEncoder
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
     * @type Template
     */
    protected $template;
    /**
     * @type Broadcast
     */
    protected $broadcast;
    /**
     * @type ObjectID
     */
    protected $sentMessageId;
    /**
     * @type array
     */
    protected $buttonPath = [];

    /**
     * FacebookMessageMapper constructor.
     * @param Bot $bot
     */
    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Subscriber setter
     * @param Subscriber $subscriber
     * @return FacebookMessageMapper
     */
    public function setSubscriber(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;

        return $this;
    }

    /**
     * Template Setter
     * @param Template $template
     * @return FacebookMessageMapper
     */
    public function setTemplate(Template $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Template Setter
     * @param Broadcast $broadcast
     * @return FacebookMessageMapper
     */
    public function setBroadcast(Broadcast $broadcast)
    {
        $this->broadcast = $broadcast;

        return $this->setTemplate($broadcast->template);
    }

    /**
     * @param array $buttonPath
     * @return FacebookMessageMapper
     */
    public function setButtonPath(array $buttonPath)
    {
        $this->buttonPath = $buttonPath;

        return $this;
    }

    /**
     * @param ObjectID $id
     * @return FacebookMessageMapper
     */
    public function setSentMessageInstanceId(ObjectID $id)
    {
        $this->sentMessageId = $id;

        return $this;
    }

    /**
     * Return a card payload.
     * @param ObjectID $id
     * @param ObjectID $cardContainerId
     * @return string
     */
    protected function abstractCard(ObjectID $id, ObjectID $cardContainerId)
    {
        $payload = $this->bot->id;
        $payload .= ':';
        $payload .= $this->subscriber->id;
        $payload .= ':';
        $payload .= $this->template? $this->template->id : implode(':', $this->buttonPath);
        $payload .= ':' . 'messages';
        $payload .= ':' . $cardContainerId;
        $payload .= ':' . 'cards';
        $payload .= ':' . $id;

        return $payload;
    }

    /**
     * Return a card payload.
     * @param ObjectID $id
     * @param ObjectID $cardContainerId
     * @return string
     */
    public function card(ObjectID $id, ObjectID $cardContainerId)
    {
        $payload = $this->abstractCard($id, $cardContainerId);

        $payload .= '|' . $this->sentMessageId;

        if ($this->broadcast) {
            $payload .= '|' . $this->broadcast->id;
        }

        return $payload;
    }

    /**
     * Return a text button payload.
     * @param ObjectID $id
     * @param ObjectID $textId
     * @return string
     */
    public function textButton(ObjectID $id, ObjectID $textId)
    {
        $payload = $this->bot->id;
        $payload .= ':';
        $payload .= $this->subscriber->id;
        $payload .= ':';
        $payload .= $this->template? $this->template->id : implode(':', $this->buttonPath);
        $payload .= ':' . 'messages';
        $payload .= ':' . $textId;
        $payload .= ':' . 'buttons';
        $payload .= ':' . $id;

        $payload .= '|' . $this->sentMessageId;

        if ($this->broadcast) {
            $payload .= '|' . $this->broadcast->id;
        }

        return $payload;
    }

    /**
     * Return a card button payload.
     * @param ObjectID $id
     * @param ObjectID $cardId
     * @param ObjectID $cardContainerId
     * @return string
     */
    public function cardButton(ObjectID $id, ObjectID $cardId, ObjectID $cardContainerId)
    {
        $payload = $this->abstractCard($cardId, $cardContainerId);
        $payload .= ':' . 'buttons';
        $payload .= ':' . $id;

        $payload .= '|' . $this->sentMessageId;

        if ($this->broadcast) {
            $payload .= '|' . $this->broadcast->id;
        }


        return $payload;
    }

    /**
     * Return the URL to a main menu button.
     * @param Button $button
     * @return string
     */
    public function mainMenuUrl(Button $button)
    {
        return url(config('app.url') . "mb/{$this->bot->id}/{$button->id}/{$button->last_revision_id}");
    }

    /**
     * Return the URL to a button/card..
     * @param string $payload
     * @return string
     */
    public function url($payload)
    {
        return url(config('app.url') . "ba/{$payload}");
    }

}