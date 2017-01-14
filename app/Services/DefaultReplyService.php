<?php

namespace App\Services;

use App\Models\Page;
use App\Models\DefaultReply;
use DB;

class DefaultReplyService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;
    /**
     * @type TemplateService
     */
    private $templates;

    /**
     * DefaultReplyService constructor.
     * @param MessageBlockService $messageBlockService
     * @param TemplateService     $templates
     */
    public function __construct(MessageBlockService $messageBlockService, TemplateService $templates)
    {
        $this->messageBlocks = $messageBlockService;
        $this->templates = $templates;
    }

    /**
     * @param Page $page
     * @return DefaultReply
     */
    public function get(Page $page)
    {
//        return $page->defaultReply()->with('blocks.blocks')->first();
        return $page->defaultReply()->first();
    }

    /**
     * @param array $input
     * @param       $page
     */
    public function persist($input, $page)
    {
        \DB::beginTransaction();

        $defaultReply = $this->get($page);

        $this->messageBlocks->persist($defaultReply, $input['message_blocks'], $page);
        
        \DB::commit();

    }
}