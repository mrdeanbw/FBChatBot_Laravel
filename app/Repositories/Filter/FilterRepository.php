<?php namespace App\Repositories\Filter;

use App\Models\AudienceFilterGroup;
use App\Models\AudienceFilterRule;
use App\Models\HasFilterGroupsInterface;
use Illuminate\Support\Collection;

interface FilterRepository
{

    /**
     * Return a model's filter groups and their nested filter rules.
     * @param HasFilterGroupsInterface $model
     * @return Collection
     */
    public function getFilterGroupsAndRulesForModel(HasFilterGroupsInterface $model);

    /**
     * Create a new audience filter group and associate it with a model.
     * @param array                    $data
     * @param HasFilterGroupsInterface $model
     * @return AudienceFilterGroup
     */
    public function createForModel(array $data, HasFilterGroupsInterface $model);

    /**
     * Create a new audience filter rule and associate it with a filter group.
     * @param array               $data
     * @param AudienceFilterGroup $group
     * @return AudienceFilterRule
     */
    public function createRule(array $data, AudienceFilterGroup $group);
}
