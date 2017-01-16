<?php namespace App\Services;

use App\Models\AudienceFilterGroup;
use App\Models\HasFilterGroupsInterface;
use App\Repositories\Filter\FilterRepository;

class FilterGroupService
{

    /**
     * @type TagService
     */
    private $tags;
    /**
     * @type FilterRepository
     */
    private $filterRepo;

    /**
     * MessageBlockService constructor.
     * @param FilterRepository $filterRepo
     * @param TagService       $tags
     */
    public function __construct(FilterRepository $filterRepo, TagService $tags)
    {
        $this->tags = $tags;
        $this->filterRepo = $filterRepo;
    }

    /**
     * Persist the filter groups.
     * Approach: delete all filter groups along with
     * their rules and create new ones from the input.
     * @todo benchmark performance, improve it or change the approach if necessary.
     * @param HasFilterGroupsInterface $model
     * @param array                    $filterGroups
     */
    public function persist(HasFilterGroupsInterface $model, array $filterGroups)
    {
        $model->deleteFilterGroups();

        foreach ($filterGroups as $data) {
            $group = $this->filterRepo->createForModel(['type' => $data['type']], $model);
            $this->persistRules($data['rules'], $group);
        }
    }

    /**
     * Persist the filter rules for a certain group.
     * @param array               $rules
     * @param AudienceFilterGroup $group
     */
    private function persistRules(array $rules, AudienceFilterGroup $group)
    {
        foreach ($rules as $data) {
            $clean = array_only($data, ['key', 'value']);
            $this->filterRepo->createRule($clean, $group);
        }
    }
}