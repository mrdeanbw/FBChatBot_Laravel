<?php namespace App\Repositories\MessagePreview;

use App\Models\Page;
use App\Models\MessagePreview;

class EloquentMessagePreviewRepository implements MessagePreviewRepository
{

    /**
     * Create a message preview instance.
     * @param Page $page
     * @return MessagePreview
     */
    public function create(Page $page)
    {
        return $page->messagePreviews()->create([]);
    }
}
