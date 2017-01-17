<?php namespace App\Repositories\Page;

use App\Models\Page;
use App\Models\User;
use App\Repositories\BaseEloquentRepository;
use Illuminate\Support\Collection;

class EloquentPageRepository extends BaseEloquentRepository implements PageRepository
{

    /**
     * Make a page model without actually persisting it.
     * @param array $data
     * @return Page
     */
    public function makePage(array $data)
    {
        return new Page($data);
    }

    /**
     * Save a page model that has been made (by the makePage function).
     * @param Page $page
     */
    public function saveMadePage(Page $page)
    {
        $page->save();
    }

    /**
     * Get the list of active pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getActiveForUser(User $user)
    {
        return $user->pages()->whereIsActive(1)->get();
    }

    /**
     * Get the list of inactive pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getInactiveForUser(User $user)
    {
        return $user->pages()->whereIsActive(0)->get();
    }

    /**
     * Find a page by its Facebook id.
     * @param $id
     * @return Page
     */
    public function findByFacebookId($id)
    {
        return Page::whereFacebookId($id)->first();
    }

    /**
     * Update the page.
     * @param Page  $page
     * @param array $data
     */
    public function update(Page $page, array $data)
    {
        $page->update($data);
    }
}
