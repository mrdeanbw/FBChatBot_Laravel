<?php namespace Common\Repositories\MessagePreview;

use Common\Models\MessagePreview;
use Common\Repositories\DBAssociatedWithBotRepository;

class DBMessagePreviewRepository extends DBAssociatedWithBotRepository implements MessagePreviewRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return MessagePreview::class;
    }
}
