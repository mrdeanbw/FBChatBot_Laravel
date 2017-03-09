<?php namespace App\Transformers;

use Common\Models\Message;
use Common\Models\Broadcast;
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
            'id'           => $broadcast->id,
            'name'         => $broadcast->name,
            'timezone'     => $broadcast->timezone,
            'notification' => $broadcast->notification,
            'date'         => $broadcast->date,
            'time'         => $broadcast->time,
            'send_from'    => $broadcast->send_from,
            'send_to'      => $broadcast->send_to,
            'status'       => $this->getStatus($broadcast->status),
            'created_at'   => $broadcast->created_at->toAtomString(),
            'completed_at' => $broadcast->completed_at? $broadcast->completed_at->toAtomString() : null,
            'stats'        => $stats,
        ];
    }

    /**
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

    /**
     * @param $status
     * @return string
     */
    protected function getStatus($status)
    {
        switch ($status) {
            case BroadcastRepositoryInterface::STATUS_PENDING:
                return 'pending';

            case BroadcastRepositoryInterface::STATUS_RUNNING:
                return 'running';

            case BroadcastRepositoryInterface::STATUS_COMPLETED:
                return 'completed';

            default:
                return null;
        }
    }
}