<?php namespace Common\Repositories\Inbox;

use Common\Models\InboxMessage;
use Common\Repositories\DBAssociatedWithBotRepository;

class DBInboxRepository extends DBAssociatedWithBotRepository implements InboxRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return InboxMessage::class;
    }
}
