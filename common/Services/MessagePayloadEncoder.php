<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\Button;
use MongoDB\BSON\ObjectID;
use Common\Models\Broadcast;
use Common\Models\Subscriber;

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
    protected $path = [];
    /**
     * @var ObjectID
     */
    protected $templateId = null;
    /**
     * @var string
     */
    protected $baseUrl;
    /**
     * @var ObjectID
     */
    protected $broadcastId = null;

    /**
     * FacebookMessageMapper constructor.
     * @param Bot $bot
     */
    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
        $this->baseUrl = config('app.url');
    }

    /**
     * Subscriber setter
     * @param Subscriber $subscriber
     * @return MessagePayloadEncoder
     */
    public function setSubscriber(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;

        return $this;
    }

    /**
     * Template Setter
     * @param Broadcast $broadcast
     * @return MessagePayloadEncoder
     */
    public function setBroadcast(Broadcast $broadcast)
    {
        $this->broadcastId = $broadcast->_id;
        $this->templateId = $broadcast->template_id;

        return $this;
    }

    /**
     * @param ObjectID $id
     * @return MessagePayloadEncoder
     */
    public function setSentMessageInstanceId(ObjectID $id)
    {
        $this->sentMessageId = $id;

        return $this;
    }

    /**
     * Return a card payload.
     * @param ObjectID      $id
     * @param ObjectID      $cardContainerId
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function card(ObjectID $id, ObjectID $cardContainerId, ObjectID $lastRevisionId = null)
    {
        $payload = '';
        $payload .= "i:{$id}|";
        $payload .= "m:{$this->sentMessageId}|";
        $payload .= ($this->templateId? "b:{$this->broadcastId}|t:{$this->templateId}|s:{$this->subscriber->id}|o:{$cardContainerId}" : "r:{$lastRevisionId}");
        $encrypted = EncryptionService::Instance()->encrypt($payload);

        return url("{$this->baseUrl}c/c/{$encrypted}");
    }

    /**
     * Return a text button payload.
     * @param Button        $button
     * @param ObjectID      $textId
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function textButton(Button $button, ObjectID $textId, ObjectID $lastRevisionId = null)
    {
        if (! $button->url) {
            return $this->textPostbackButton($button->id, $textId, $lastRevisionId);
        }

        $payload = '';
        $payload .= "i:{$button->id}|";
        $payload .= "m:{$this->sentMessageId}|";
        $payload .= "s:{$this->subscriber->id}|";
        $payload .= ($this->templateId? "b:{$this->broadcastId}|t:{$this->templateId}|o:{$textId}" : "r:{$lastRevisionId}");
        $encrypted = EncryptionService::Instance()->encrypt($payload);

        return url("{$this->baseUrl}c/tb/{$encrypted}");
    }

    /**
     * Return a text button payload.
     * @param ObjectID      $id
     * @param ObjectID      $textId
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function textPostbackButton(ObjectID $id, ObjectID $textId, ObjectID $lastRevisionId = null)
    {
        $payload = "tb|";
        $payload .= "i:{$id}|";
        $payload .= "m:{$this->sentMessageId}|";
        $payload .= ($this->templateId? "b:{$this->broadcastId}|t:{$this->templateId}|o:{$textId}" : "r:{$lastRevisionId}");

        return $payload;
    }

    /**
     * Return a card button payload.
     * @param Button        $button
     * @param ObjectID      $cardId
     * @param ObjectID      $cardContainerId
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function cardButton(Button $button, ObjectID $cardId, ObjectID $cardContainerId, ObjectID $lastRevisionId = null)
    {
        if (! $button->url) {
            return $this->cardPostbackButton($button->id, $cardId, $cardContainerId, $lastRevisionId);
        }

        $payload = '';
        $payload .= "i:{$button->id}|";
        $payload .= "c:{$cardId}|";
        $payload .= "m:{$this->sentMessageId}|";
        $payload .= "s:{$this->subscriber->id}|";
        $payload .= ($this->templateId? "b:{$this->broadcastId}|t:{$this->templateId}|o:{$cardContainerId}" : "r:{$lastRevisionId}");
        $encrypted = EncryptionService::Instance()->encrypt($payload);

        return url("{$this->baseUrl}c/cb/{$encrypted}");
    }

    /**
     * Return a text button payload.
     * @param ObjectID      $id
     * @param ObjectID      $cardId
     * @param ObjectID      $cardContainerId
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function cardPostbackButton(ObjectID $id, ObjectID $cardId, ObjectID $cardContainerId, ObjectID $lastRevisionId = null)
    {
        $payload = "cb|";
        $payload .= "i:{$id}|";
        $payload .= "c:{$cardId}|";
        $payload .= "m:{$this->sentMessageId}|";
        $payload .= "s:{$this->subscriber->id}|";
        $payload .= ($this->templateId? "b:{$this->broadcastId}|t:{$this->templateId}|o:{$cardContainerId}" : "r:{$lastRevisionId}");

        return $payload;
    }

    /**
     * Return the URL to a main menu button.
     * @param Button $button
     * @return string
     */
    public function mainMenuUrl(Button $button)
    {
        $jumbled =
            substr($button->last_revision_id, 00, 06) .
            substr($button->last_revision_id, 18, 24) .
            substr($button->last_revision_id, 06, 12) .
            substr($button->last_revision_id, 12, 18);

        $encrypted = EncryptionService::Instance()->encrypt($jumbled);

        return url("{$this->baseUrl}c/mb/{$encrypted}");
    }
}
