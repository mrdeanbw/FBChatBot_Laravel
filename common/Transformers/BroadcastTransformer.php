<?php namespace Common\Transformers;

use Common\Models\Broadcast;
use Common\Services\FacebookMessageSender;
use Common\Repositories\Broadcast\BroadcastRepositoryInterface;

class BroadcastTransformer extends BaseTransformer
{

    protected $availableIncludes = ['template', 'filter'];

    public function transform(Broadcast $broadcast)
    {
        $stats = $broadcast->stats;
        if (isset($broadcast->template->messages[0]->stats)) {
            $stats = array_merge($stats, $broadcast->template->messages[0]->stats);
        }

        return [
            'id'               => $broadcast->id,
            'name'             => $broadcast->name,
            'date'             => $broadcast->date,
            'time'             => $broadcast->time,
            'status'           => BroadcastRepositoryInterface::_STATUS_MAP[$broadcast->status],
            'send_mode'        => $broadcast->send_now? 'now' : 'later',
            'timezone'         => $broadcast->timezone,
            'timezone_mode'    => BroadcastRepositoryInterface::_TIMEZONE_MAP[$broadcast->timezone_mode],
            'notification'     => FacebookMessageSender::_NOTIFICATION_MAP[$broadcast->notification],
            'message_type'     => BroadcastRepositoryInterface::_MESSAGE_MAP[$broadcast->message_type],
            'completed_at'     => $broadcast->completed_at? $broadcast->completed_at->toAtomString() : null,
            'stats'            => $stats,
            'send_at'          => $broadcast->send_at? $broadcast->send_at->toAtomString() : null,
            'remaining_target' => $broadcast->remaining_target
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
}