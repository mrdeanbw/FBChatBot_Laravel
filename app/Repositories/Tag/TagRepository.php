<?php namespace App\Repositories\Tag;

use App\Models\Tag;
use App\Models\Bot;
use Illuminate\Support\Collection;

interface TagRepository
{

    /**
     * Find a page tag's by the tag label.
     * @param string $tag
     * @param Bot    $page
     * @return Tag|null
     */
    public function findByLabelForPage($tag, Bot $page);

    /**
     * Create a tag for a page.
     * @param string $label
     * @param Bot    $page
     * @return mixed
     */
    public function createForPage($label, Bot $page);

    /**
     * Get all tags that belong to a page.
     * @param Bot $page
     * @return Collection
     */
    public function getAllForPage(Bot $page);
}
