<?php namespace Common\Transformers;

use Common\Models\Message;
use Common\Models\Broadcast;
use Common\Services\FacebookAPIAdapter;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;

class BroadcastTransformer extends BaseTransformer
{

    protected $availableIncludes = ['template', 'filter'];

    public function transform(Broadcast $broadcast)
    {
        $stats = $broadcast->stats;
        if (in_array($broadcast->status, [BroadcastRepositoryInterface::STATUS_RUNNING, BroadcastRepositoryInterface::STATUS_COMPLETED])) {
            $this->loadModelsIfNotLoaded($broadcast, ['template']);
            $stats = array_merge($stats, $this->getMessageStats($broadcast->template->messages[0]));
        }

        return [
            'id'            => $broadcast->id,
            'name'          => $broadcast->name,
            'date'          => $broadcast->date,
            'time'          => $broadcast->time,
            'status'        => BroadcastRepositoryInterface::_STATUS_MAP[$broadcast->status],
            'send_mode'     => $broadcast->send_now? 'now' : 'later',
            'timezone'      => $broadcast->timezone,
            'timezone_mode' => BroadcastRepositoryInterface::_TIMEZONE_MAP[$broadcast->timezone_mode],
            'notification'  => FacebookAPIAdapter::_NOTIFICATION_MAP[$broadcast->notification],
            'message_type'  => BroadcastRepositoryInterface::_MESSAGE_MAP[$broadcast->message_type],
            'completed_at'  => $broadcast->completed_at? $broadcast->completed_at->toAtomString() : null,
            'stats'         => $stats,
            'send_at'       => $broadcast->send_at? $broadcast->send_at->toAtomString() : null
        ];
    }

    /**\
     * @param Broadcast $broadcast
     * @return \League\Fractal\Resource\Item
     */
    public function includeFilter(Broadcast $broadcast)
    {
        return $this->item($broadcast->filter, new AudienceFilterTransformer(), false);
    }

    /**
     * @param Message $message
     * @return array
     */
    public function getMessageStats(Message $message)
    {
        /** @type SentMessageRepositoryInterface $sentMessageRepo */
        $sentMessageRepo = app(SentMessageRepositoryInterface::class);

        return [
            'sent'      => $sentMessageRepo->totalSentForMessage($message->id),
            'delivered' => $sentMessageRepo->totalDeliveredForMessage($message->id),
            'read'      => $sentMessageRepo->totalReadForMessage($message->id),
        ];
    }
}