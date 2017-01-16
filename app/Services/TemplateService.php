<?php namespace App\Services;

use DB;
use App\Models\Page;
use App\Models\Template;
use App\Repositories\Template\TemplateRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TemplateService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;
    /**
     * @type TemplateRepository
     */
    private $templateRepo;

    /**
     * TemplateService constructor.
     * @param TemplateRepository  $templateRepo
     * @param MessageBlockService $messageBlocks
     */
    public function __construct(TemplateRepository $templateRepo, MessageBlockService $messageBlocks)
    {
        $this->messageBlocks = $messageBlocks;
        $this->templateRepo = $templateRepo;
    }

    /**
     * @param Page $page
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function explicitList(Page $page)
    {
        return $this->templateRepo->explicitTemplatesForPage($page);
    }

    /**
     * @param      $id
     * @param Page $page
     * @return Template
     */
    public function findExplicitOrFail($id, Page $page)
    {
        if ($template = $this->templateRepo->FindExplicitByIdForPage($id, $page)) {
            return $template;
        }
        throw new ModelNotFoundException;
    }

    /**
     * @param      $id
     * @param Page $page
     * @return Template
     */
    public function findOrFail($id, Page $page)
    {
        if ($template = $this->templateRepo->findByIdForPage($id, $page)) {
            return $template;
        }
        throw new ModelNotFoundException;
    }

    /**
     * Create a new explicit template.
     * @param Page  $page
     * @param array $input
     * @return Template
     */
    public function createExplicit(array $input, Page $page)
    {
        $template = DB::transaction(function () use ($input, $page) {
            $template = $this->templateRepo->create(['name' => $input['name'], 'is_explicit' => 1], $page);
            $this->messageBlocks->persist($template, $input['message_blocks']);

            return $template;
        });

        return $template;
    }

    /**
     * Update a message template.
     * @param       $id
     * @param Page  $page
     * @param array $input
     * @return Template
     */
    public function update($id, array $input, Page $page)
    {
        $template = $this->findOrFail($id, $page);

        DB::transaction(function () use ($input, $template) {
            $this->templateRepo->update($template, ['name' => $input['name']]);
            $this->messageBlocks->persist($template, $input['message_blocks']);
        });

        return $template->fresh();
    }
}