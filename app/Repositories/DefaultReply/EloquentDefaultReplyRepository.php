<?php namespace App\Repositories\DefaultReply;

use App\Models\Page;
use App\Models\DefaultReply;

class EloquentDefaultReplyRepository implements DefaultReplyRepository
{

    /**
     * Return the default reply associated with a page.
     * @param Page $page
     * @return DefaultReply|null
     */
    public function getForPage(Page $page)
    {
        return $page->defaultReply;
    }

    /**
     * Create default reply.
     * @param Page $page
     * @return DefaultReply
     */
    public function create(Page $page)
    {
        return $page->defaultReply()->create([]);
    }
}
