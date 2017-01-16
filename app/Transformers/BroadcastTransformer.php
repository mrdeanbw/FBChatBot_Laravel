<?php
namespace App\Transformers;

use App\Models\Broadcast;
use DB;

class BroadcastTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['message_blocks', 'filter_groups'];

    public function transform(Broadcast $broadcast)
    {
        return [
            'id'             => (int)$broadcast->id,
            'name'           => $broadcast->name,
            'timezone'       => $broadcast->timezone,
            'notification'   => $broadcast->notification,
            'date'           => $broadcast->date,
            'time'           => $broadcast->time,
            'send_from'      => (int)$broadcast->send_from,
            'send_to'        => (int)$broadcast->send_to,
            'status'         => $broadcast->status,
            'send_at'        => $broadcast->send_at? $broadcast->send_at->toDateTimeString() : null,
            'filter_enabled' => (boolean)$broadcast->filter_enabled,
            'filter_type'    => $broadcast->filter_type,
            'created_at'     => $broadcast->created_at->toDateTimeString(),
            'stats'          => $this->broadcastStats($broadcast),
            'target_count'   => $broadcast->activeTargetAudienceCount(),
        ];
    }

    /**
     * @param Broadcast $broadcast
     * @return array
     */
    private function broadcastStats(Broadcast $broadcast)
    {
        return [
            'sent'      => (int)DB::table('broadcast_subscriber')->where('broadcast_id', $broadcast->id)->count(),
            'delivered' => (int)DB::table('broadcast_subscriber')->where('broadcast_id', $broadcast->id)->whereNotNull('delivered_at')->count(),
            'read'      => (int)DB::table('broadcast_subscriber')->where('broadcast_id', $broadcast->id)->whereNotNull('read_at')->count(),
            'clicks'    => [
                'total'  => (int)DB::table('broadcast_subscriber')->where('broadcast_id', $broadcast->id)->sum('clicks'),
                'unique' => (int)DB::table('broadcast_subscriber')->where('broadcast_id', $broadcast->id)->where('clicks', '>', 0)->count()
            ]
        ];
    }
}