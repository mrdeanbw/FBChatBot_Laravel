<?php namespace App\Repositories\Broadcast;

use Illuminate\Support\Collection;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface BroadcastRepositoryInterface extends AssociatedWithBotRepositoryInterface
{
    
    /**
     * Get list of sending-due broadcasts
     * @return Collection
     */
    public function getDueBroadcasts();
}
