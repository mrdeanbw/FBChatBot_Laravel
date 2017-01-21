<?php namespace App\Repositories\MessagePreview;

use App\Models\Page;
use App\Models\MessagePreview;

interface MessagePreviewRepository
{

    /**
     * Create a message preview instance.
     * @param Page $page
     * @return MessagePreview
     */
    public function create(Page $page);

    /**
     * Return a fresh instance of the message preview model.
     * @param MessagePreview $messagePreview
     * @return MessagePreview
     */
    public function fresh(MessagePreview $messagePreview);
}
