<?php namespace App\Repositories\Template;

use App\Models\Page;
use App\Models\Template;
use Illuminate\Support\Collection;

interface TemplateRepository
{

    /**
     * Find a template for a given page
     * @param int  $templateId
     * @param Page $page
     * @return Template|null
     */
    public function findByIdForPage($templateId, Page $page);

    /**
     * Create a template for page.
     * @param array $data
     * @param Page  $page
     * @return Template
     */
    public function create(array $data, Page $page);

    /**
     * Update a template.
     * @param Template $template
     * @param array    $data
     */
    public function update(Template $template, array $data);

    /**
     * Return a list of all explicit templates for a page.
     * @param Page $page
     * @return Collection
     */
    public function explicitTemplatesForPage(Page $page);

    /**
     * Find an explicit template by id for page.
     * @param int  $id
     * @param Page $page
     * @return Template|null
     */
    public function FindExplicitByIdForPage($id, Page $page);

}
