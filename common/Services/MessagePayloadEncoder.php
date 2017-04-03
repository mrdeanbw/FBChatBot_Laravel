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
    protected $broadcastId = null;
    /**
     * @var string
     */
    protected $baseUrl;

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
        $this->broadcastId = $broadcast->id;

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
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function card(ObjectID $id, ObjectID $lastRevisionId = null)
    {
        $payload = '';
        $payload .= "i:{$id}|";
        $payload .= "r:{$lastRevisionId}|";
        $payload .= "m:{$this->sentMessageId}";
        $encrypted = EncryptionService::Instance()->encrypt($payload);

        return url("{$this->baseUrl}c/c/{$encrypted}");
    }

    /**
     * Return a text button payload.
     * @param Button        $button
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function textButton(Button $button, ObjectID $lastRevisionId = null)
    {
        if (! $button->url) {
            return $this->textPostbackButton($button->id, $lastRevisionId);
        }

        $payload = '';
        $payload .= "i:{$button->id}|";
        $payload .= "r:{$lastRevisionId}|";
        $payload .= "m:{$this->sentMessageId}|";
        $payload .= "s:{$this->subscriber->id}";
        $encrypted = EncryptionService::Instance()->encrypt($payload);

        return url("{$this->baseUrl}c/tb/{$encrypted}");
    }

    /**
     * Return a text button payload.
     * @param ObjectID      $id
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function textPostbackButton(ObjectID $id, ObjectID $lastRevisionId = null)
    {
        $payload = "tb|";
        $payload .= "i:{$id}|";
        $payload .= "r:{$lastRevisionId}|";
        $payload .= "m:{$this->sentMessageId}";

        return $payload;
    }

    /**
     * Return a card button payload.
     * @param Button        $button
     * @param ObjectID      $cardId
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function cardButton(Button $button, ObjectID $cardId, ObjectID $lastRevisionId = null)
    {
        if (! $button->url) {
            return $this->cardPostbackButton($button->id, $cardId, $lastRevisionId);
        }

        $payload = '';
        $payload .= "i:{$button->id}|";
        $payload .= "c:{$cardId}|";
        $payload .= "r:{$lastRevisionId}|";
        $payload .= "m:{$this->sentMessageId}|";
        $payload .= "s:{$this->subscriber->id}";
        $encrypted = EncryptionService::Instance()->encrypt($payload);

        return url("{$this->baseUrl}c/cb/{$encrypted}");
    }

    /**
     * Return a text button payload.
     * @param ObjectID      $id
     * @param ObjectID      $cardId
     * @param ObjectID|null $lastRevisionId
     * @return string
     */
    public function cardPostbackButton(ObjectID $id, ObjectID $cardId, ObjectID $lastRevisionId = null)
    {
        $payload = "cb|";
        $payload .= "i:{$id}|";
        $payload .= "c:{$cardId}|";
        $payload .= "r:{$lastRevisionId}|";
        $payload .= "m:{$this->sentMessageId}|";
        $payload .= "s:{$this->subscriber->id}";

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
