<?php namespace Common\Repositories\Broadcast;

use Common\Models\Bot;
use Common\Models\Broadcast;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use Common\Repositories\AssociatedWithBotRepositoryInterface;

interface BroadcastRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    const STATUS_PENDING = 0;
    const STATUS_RUNNING = 1;
    const STATUS_COMPLETED = 2;
    const _STATUS_MAP = ['pending', 'running', 'completed'];

    const MESSAGE_SUBSCRIPTION = 0;
    const MESSAGE_PROMOTIONAL = 1;
    const MESSAGE_FOLLOW_UP = 2;
    const _MESSAGE_MAP = ['subscription', 'promotional', 'follow_up'];

    const TIMEZONE_BOT = 0;
    const TIMEZONE_SUBSCRIBER = 1;
    const TIMEZONE_CUSTOM = 2;
    const _TIMEZONE_MAP = ['bot', 'subscriber', 'custom'];


    /**
     * Get list of sending-due broadcasts
     * @return Collection
     */
    public function getDueBroadcasts();

    /**
     * @param Broadcast $broadcast
     * @return mixed
     */
    public function markAsRunning(Broadcast $broadcast);

    /**
     * @param Bot      $bot
     * @param ObjectID $broadcastId
     * @param ObjectID $subscriberId
     */
    public function recordClick(Bot $bot, ObjectID $broadcastId, ObjectID $subscriberId);

    /**
     * @param Broadcast $broadcast
     * @param int       $count
     */
    public function setTargetAudienceAndMarkAsCompleted(Broadcast $broadcast, $count);

    /**
     * @param array     $dueSchedules
     * @param Broadcast $broadcast
     * @param int       $count
     * @return mixed
     */
    public function incrementTargetAudienceAndMarkSchedulesAsCompleted(array $dueSchedules, Broadcast $broadcast, $count);
}
