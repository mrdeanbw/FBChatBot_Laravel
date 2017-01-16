<?php namespace App\Repositories\Tag;

use App\Models\Page;
use App\Models\Tag;
use Illuminate\Support\Collection;

class EloquentTagRepository implements TagRepository
{

    /**
     * Find a page tag's by the tag label.
     * @param string $tag
     * @param Page   $page
     * @return Tag|null
     */
    public function findByLabelForPage($tag, Page $page)
    {
        return $page->tags()->where('tag', $tag)->first();
    }

    /**
     * Create a tag for a page.
     * @param string $label
     * @param Page   $page
     * @return mixed
     */
    public function createForPage($label, Page $page)
    {
        return $page->tags()->create(['tag' => $label]);
    }

    /**
     * Get all tags that belong to a page.
     * @param Page $page
     * @return Collection
     */
    public function getAllForPage(Page $page)
    {
        return $page->tags;
    }
}
