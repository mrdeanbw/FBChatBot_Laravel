<?php namespace App\Repositories\DefaultReply;

use App\Models\DefaultReply;
use App\Models\Page;

interface DefaultReplyRepository
{

    /**
     * Return the default reply associated with a page.
     * @param Page $page
     * @return DefaultReply|null
     */
    public function getForPage(Page $page);

    /**
     * Create default reply.
     * @param Page $page
     * @return DefaultReply
     */
    public function create(Page $page);
}
