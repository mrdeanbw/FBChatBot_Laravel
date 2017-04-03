<?php namespace Common\Repositories\Template;

use Common\Models\Bot;
use Common\Models\Template;
use Common\Repositories\AssociatedWithBotRepositoryInterface;
use MongoDB\BSON\ObjectID;

interface TemplateRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    /**
     * Find an explicit template by id for bot.
     * @param int $id
     * @param Bot $bot
     * @return Template|null
     */
    public function findExplicitByIdForBot($id, Bot $bot);


    /**
     * @param string        $value
     * @param Bot           $bot
     * @param ObjectID|null $exception
     * @return mixed
     */
    public function nameExists($value, Bot $bot, ObjectID $exception = null);
}
