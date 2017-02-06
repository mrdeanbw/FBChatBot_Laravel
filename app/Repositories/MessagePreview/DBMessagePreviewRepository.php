<?php namespace App\Repositories\MessagePreview;

use App\Models\MessagePreview;
use App\Repositories\BaseDBRepository;

class DBMessagePreviewRepository extends BaseDBRepository implements MessagePreviewRepository
{

    /**
     * @return string
     */
    public function model()
    {
        return MessagePreview::class;
    }
}
