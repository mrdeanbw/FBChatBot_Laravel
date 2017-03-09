<?php namespace Common\Repositories\MessageRevision;

use Common\Models\Bot;
use Common\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use Common\Repositories\AssociatedWithBotRepositoryInterface;

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
