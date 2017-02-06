<?php namespace App\Repositories\Filter;

use Illuminate\Support\Collection;
use App\Models\AudienceFilterRule;
use App\Models\AudienceFilterGroup;
use App\Models\HasFilterGroupsInterface;
use App\Repositories\BaseDBRepository;

class DBFilterRepository extends BaseDBRepository implements FilterRepository
{

    /**
     * @return string
     */
    public function model()
    {
        return AudienceFilterGroup::class;
    }

    /**
     * Return a model's filter groups and their nested filter rules.
     * @param HasFilterGroupsInterface $model
     * @return Collection
     */
    public function getFilterGroupsAndRulesForModel(HasFilterGroupsInterface $model)
    {
        return $model->filterGroups()->with('rules')->get();
    }

    /**
     * Create a new audience filter group and associate it with a model.
     * @param array                    $data
     * @param HasFilterGroupsInterface $model
     * @return AudienceFilterGroup
     */
    public function createForModel(array $data, HasFilterGroupsInterface $model)
    {
        return $model->filterGroups()->create($data);
    }

    /**
     * Create a new audience filter rule and associate it with a filter group.
     * @param array               $data
     * @param AudienceFilterGroup $group
     * @return AudienceFilterRule
     */
    public function createRule(array $data, AudienceFilterGroup $group)
    {
        return $group->rules()->create($data);
    }

}
