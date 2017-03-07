<?php namespace App\Repositories\MessageRevision;

use App\Models\Bot;
use App\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface MessageRevisionRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    /**
     * @param ObjectID $messageId
     * @param Bot      $bot
     * @return Collection
     */
    public function getMessageRevisions(ObjectID $messageId, Bot $bot);
    
    /**
     * @param ObjectID   $id
     * @param Bot        $bot
     * @param Subscriber $subscriber
     */
    public function recordMainMenuButtonClick(ObjectID $id, Bot $bot, Subscriber $subscriber = null);
}
