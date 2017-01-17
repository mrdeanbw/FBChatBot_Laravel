<?php namespace App\Repositories\Page;

use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Collection;

interface PageRepository
{

    /**
     * Make a page model without actually persisting it.
     * @param array $data
     * @return Page
     */
    public function makePage(array $data);

    /**
     * Save a page model that has been made (by the makePage function).
     * @param Page $page
     */
    public function saveMadePage(Page $page);

    /**
     * Get the list of active pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getActiveForUser(User $user);

    /**
     * Get the list of inactive pages that belong to a user.
     * @param User $user
     * @return Collection
     */
    public function getInactiveForUser(User $user);

    /**
     * Find a page by its Facebook id.
     * @param $id
     * @return Page
     */
    public function findByFacebookId($id);

    /**
     * Update the page.
     * @param Page  $page
     * @param array $data
     */
    public function update(Page $page, array $data);
}
