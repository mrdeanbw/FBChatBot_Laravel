<?php namespace App\Services;

use App\Models\Tag;
use App\Models\Page;
use App\Repositories\Tag\TagRepository;
use Illuminate\Database\Eloquent\Collection;

class TagService
{

    /**
     * @type TagRepository
     */
    private $tagRepo;

    /**
     * TagService constructor.
     * @param TagRepository $tagRepo
     */
    public function __construct(TagRepository $tagRepo)
    {
        $this->tagRepo = $tagRepo;
    }

    /**
     * @param string $label
     * @param Page   $page
     * @return Tag|null
     */
    private function findByLabel($label, Page $page)
    {
        return $this->tagRepo->findByLabelForPage($label, $page);
    }

    /**
     * @param array $tags
     * @param Page  $page
     * @return array
     */
    public function getOrCreateTags(array $tags, Page $page)
    {
        $ret = [];

        foreach ($tags as $label) {

            $tag = $this->findByLabel($label, $page);

            if (! $tag) {
                $tag = $this->tagRepo->createForPage($label, $page);
            }

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
        return $this->tagRepo->getAllForPage($page)->pluck('tag');
    }

}