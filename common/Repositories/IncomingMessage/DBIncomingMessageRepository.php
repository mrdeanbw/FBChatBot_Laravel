<?php namespace Common\Repositories\IncomingMessage;

use Common\Models\IncomingMessage;
use Common\Repositories\DBAssociatedWithBotRepository;

class DBIncomingMessageRepository extends DBAssociatedWithBotRepository implements IncomingMessageRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return IncomingMessage::class;
    }

}
