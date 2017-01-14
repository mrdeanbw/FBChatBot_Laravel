<?php
namespace App\Services;

use App\Models\Page;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagService
{

    /**
     * @param      $tag
     * @param Page $page
     * @return integer
     */
    public function getTagID($tag, Page $page)
    {
        return $this->getTagIDs([$tag], $page)[0];
    }

    /**
     * @param      $tags
     * @param Page $page
     * @return array
     */
    public function getTagIDs($tags, Page $page)
    {
        $ret = [];
        foreach ($tags as $tagName) {
            /** @type Tag $tag */
            $tag = $page->tags()->whereTag($tagName)->firstOrFail();
            $ret[] = $tag->id;
        }

        return $ret;
    }

    /**
     * @param      $tags
     * @param Page $page
     * @return array
     */
    public function createTags($tags, Page $page)
    {
        $ret = [];
        foreach ($tags as $tagName) {
            /** @type Tag $tag */
            $tag = $page->tags()->firstOrNew(['tag' => $tagName]);
            $tag->save();
            $ret[] = $tag->id;
        }

        return $ret;
    }


    /**
     * @param Page $page
     * @return Collection
     */
    public function tagList(Page $page)
    {
        return $page->tags()->pluck('tag');
    }

}