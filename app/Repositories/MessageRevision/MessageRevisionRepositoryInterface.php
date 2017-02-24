<?php namespace App\Repositories\MessageRevision;

use App\Models\Bot;
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
}
