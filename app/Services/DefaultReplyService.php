<?php namespace App\Services;

use App\Repositories\DefaultReply\DefaultReplyRepository;
use DB;
use App\Models\Page;
use App\Models\DefaultReply;

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
     * @type DefaultReplyRepository
     */
    private $defaultReplyRepo;

    /**
     * DefaultReplyService constructor.
     * @param DefaultReplyRepository $defaultReplyRepo
     * @param MessageBlockService    $messageBlockService
     * @param TemplateService        $templates
     */
    public function __construct(
        DefaultReplyRepository $defaultReplyRepo,
        MessageBlockService $messageBlockService,
        TemplateService $templates
    ) {
        $this->messageBlocks = $messageBlockService;
        $this->templates = $templates;
        $this->defaultReplyRepo = $defaultReplyRepo;
    }

    /**
     * Get the default reply for this page.
     * @param Page $page
     * @return DefaultReply
     */
    public function get(Page $page)
    {
        return $this->defaultReplyRepo->getForPage($page);
    }

    /**
     * Update the default reply.
     * @param array $input
     * @param       $page
     */
    public function update($input, $page)
    {
        DB::transaction(function () use ($input, $page) {
            $defaultReply = $this->get($page);
            $this->messageBlocks->persist($defaultReply, $input['message_blocks']);
        });
    }
}