<?php namespace App\Repositories\AutoReplyRule;

use App\Models\Page;
use App\Models\AutoReplyRule;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\BaseEloquentRepository;

class EloquentAutoReplyRuleRepository extends BaseEloquentRepository implements AutoReplyRuleRepository
{

    /**
     * Return list of all sequences that belong to a page.
     * @param Page $page
     * @return Collection
     */
    public function getAllForPage(Page $page)
    {
        return $page->autoReplyRules;
    }

    /**
     * Find an auto reply rule by his artificial ID.
     * @param int  $id
     * @param Page $page
     * @return AutoReplyRule|null
     */
    public function findByIdForPage($id, Page $page)
    {
        return $page->autoReplyRules()->find($id);
    }

    /**
     * Delete an auto reply rule.
     * @param AutoReplyRule $rule
     */
    public function delete(AutoReplyRule $rule)
    {
        $rule->delete();
    }

    /**
     * Create a new auto reply rule for a certain page.
     * @param array $data
     * @param Page  $page
     * @return AutoReplyRule
     */
    public function createForPage(array $data, Page $page)
    {
        return $page->autoReplyRules()->create($data);
    }

    /**
     * update a certain auto reply rule.
     * @param AutoReplyRule $rule
     * @param array         $data
     * @return AutoReplyRule
     */
    public function update(AutoReplyRule $rule, array $data)
    {
        $rule->update($data);
    }

    /**
     * Get the first matching auto reply rule.
     * @param string $text
     * @param Page   $page
     * @return AutoReplyRule|null
     */
    public function getMatchingRuleForPage($text, Page $page)
    {
        /** @type Builder $query */
        $query = $page->autoReplyRules();

        // Exact.
        $query->where(function (Builder $query) use ($text) {
            $query->where('mode', 'is')->where('keyword', '=', $text);
        });

        // Substring.
        $query->orWhere(function (Builder $query) use ($text) {
            $query->where('mode', 'contains')->where('keyword', 'LIKE', "%{$text}%");
        });

        // Prefix.
        $query->orWhere(function (Builder $query) use ($text) {
            $query->where('mode', 'begins_with')->where('keyword', 'LIKE', "{$text}%");
        });

        // Include associated template, and return the first result.
        return $query->with('template')->first();
    }
}
