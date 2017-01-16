<?php namespace App\Repositories\Tag;

use App\Models\Tag;
use App\Models\Page;
use Illuminate\Support\Collection;

interface TagRepository
{

    /**
     * Find a page tag's by the tag label.
     * @param string $tag
     * @param Page   $page
     * @return Tag|null
     */
    public function findByLabelForPage($tag, Page $page);

    /**
     * Create a tag for a page.
     * @param string $label
     * @param Page   $page
     * @return mixed
     */
    public function createForPage($label, Page $page);

    /**
     * Get all tags that belong to a page.
     * @param Page $page
     * @return Collection
     */
    public function getAllForPage(Page $page);
}
