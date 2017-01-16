<?php namespace App\Repositories\AutoReplyRule;

use App\Models\Page;
use App\Models\AutoReplyRule;
use Illuminate\Support\Collection;

interface AutoReplyRuleRepository
{

    /**
     * Return list of all sequences that belong to a page.
     * @param Page $page
     * @return Collection
     */
    public function getAllForPage(Page $page);

    /**
     * Find an auto reply rule by his artificial ID.
     * @param int  $id
     * @param Page $page
     * @return AutoReplyRule|null
     */
    public function findByIdForPage($id, Page $page);

    /**
     * Delete an auto reply rule.
     * @param AutoReplyRule $rule
     */
    public function delete(AutoReplyRule $rule);

    /**
     * Create a new auto reply rule for a certain page.
     * @param array $data
     * @param Page  $page
     * @return AutoReplyRule
     */
    public function createForPage(array $data, Page $page);

    /**
     * update a certain auto reply rule.
     * @param AutoReplyRule $rule
     * @param array         $data
     */
    public function update(AutoReplyRule $rule, array $data);

    /**
     * Get the first matching auto reply rule.
     * @param string $text
     * @param Page $page
     * @return AutoReplyRule|null
     */
    public function getMatchingRuleForPage($text, Page $page);
}
