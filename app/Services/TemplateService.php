<?php namespace App\Services;

use App\Models\Page;
use App\Models\Template;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TemplateService
{

    /**
     * @type MessageBlockService
     */
    private $messageBlocks;

    public function __construct(MessageBlockService $messageBlocks)
    {
        $this->messageBlocks = $messageBlocks;
    }

    /**
     * @param Page $page
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function explicit(Page $page)
    {
        return $page->templates()->whereIsExplicit(1)->get();
    }

    /**
     * @param      $id
     * @param Page $page
     * @return Template
     */
    public function findExplicit($id, Page $page)
    {
        return $page->templates()->whereIsExplicit(1)->findOrFail($id);
    }

    /**
     * @param      $id
     * @param Page $page
     * @return Template
     */
    public function find($id, Page $page)
    {
        return $page->templates()->findOrFail($id);
    }

    /**
     * @param Page $page
     * @param      $input
     * @return Template
     */
    public function create(Page $page, $input)
    {
        DB::beginTransaction();

        $template = new Template();
        $template->name = $input['name'];
        $template->is_explicit = 1;
        $page->templates()->save($template);
        $this->messageBlocks->persist($template, $input['message_blocks'], $page);

        DB::commit();

        return $template;
    }

    /**
     * @param      $id
     * @param Page $page
     * @param      $input
     * @return Template
     */
    public function update($id, Page $page, $input)
    {
        DB::beginTransaction();

        $template = $this->find($id, $page);
        $template->name = $input['name'];
        $template->is_explicit = 1;
        $template->save();
        $this->messageBlocks->persist($template, $input['message_blocks'], $page);

        DB::commit();

        return $template->fresh();
    }
}