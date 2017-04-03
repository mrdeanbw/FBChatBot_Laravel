<?php namespace Common\Jobs;

use Common\Models\Bot;
use Common\Models\Button;
use MongoDB\BSON\ObjectID;
use Common\Models\Template;
use Common\Models\Subscriber;
use Common\Services\WebAppAdapter;

class CarryOutCardButtonActions extends BaseJob
{

    /**
     * @var Button
     */
    private $button;
    /**
     * @var Bot
     */
    private $bot;
    /**
     * @var ObjectID
     */
    private $subscriberId;
    /**
     * @var ObjectID
     */
    private $sentMessageId;
    /**
     * @var int
     */
    private $buttonIndex;
    /**
     * @var
     */
    private $cardIndex;

    /**
     * SendBroadcast constructor.
     * @param Button   $button
     * @param int      $buttonIndex
     * @param int      $cardIndex
     * @param Bot      $bot
     * @param ObjectID $subscriberId
     * @param ObjectID $sentMessageId
     */
    public function __construct(Button $button, $buttonIndex, $cardIndex, Bot $bot, ObjectID $subscriberId, ObjectID $sentMessageId)
    {
        $this->bot = $bot;
        $this->button = $button;
        $this->cardIndex = $cardIndex;
        $this->buttonIndex = $buttonIndex;
        $this->subscriberId = $subscriberId;
        $this->sentMessageId = $sentMessageId;
    }

    /**
     * Execute the job.
     * @param WebAppAdapter $adapter
     * @internal param FacebookMessageSender $FacebookMessageSender
     */
    public function handle(WebAppAdapter $adapter)
    {
        $adapter->carryOutCardButtonActions($this->button, $this->bot, $this->subscriberId, $this->sentMessageId, $this->buttonIndex, $this->cardIndex);
    }
}