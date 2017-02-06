<?php namespace App\Repositories\Tag;

use App\Models\Bot;
use App\Models\Tag;
use Illuminate\Support\Collection;

class EloquentTagRepository implements TagRepository
{

    /**
     * Find a page tag's by the tag label.
     * @param string $tag
     * @param Bot    $page
     * @return Tag|null
     */
    public function findByLabelForPage($tag, Bot $page)
    {
        return $page->tags()->where('tag', $tag)->first();
    }

    /**
     * Create a tag for a page.
     * @param string $label
     * @param Bot    $page
     * @return mixed
     */
    public function createForPage($label, Bot $page)
    {
        return $page->tags()->create(['tag' => $label]);
    }

    /**
     * Get all tags that belong to a page.
     * @param Bot $page
     * @return Collection
     */
    public function getAllForPage(Bot $page)
    {
        return $page->tags;
    }
}
