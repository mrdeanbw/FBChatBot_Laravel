<?php namespace App\Repositories\MessagePreview;

use App\Models\MessagePreview;
use App\Repositories\DBAssociatedWithBotRepository;

class DBMessagePreviewBaseRepository extends DBAssociatedWithBotRepository implements MessagePreviewRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return MessagePreview::class;
    }
}
