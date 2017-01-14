<?php

namespace App\Services;

use App\Models\AudienceFilterGroup;
use App\Models\AudienceFilterRule;
use App\Models\HasFilterGroupsInterface;
use App\Models\Page;

class FilterGroupService
{

    private $tags;

    /**
     * MessageBlockService constructor.
     * @param TagService $tags
     */
    public function __construct(TagService $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @param HasFilterGroupsInterface $model
     * @param                          $filterGroups
     */
    public function persist(HasFilterGroupsInterface $model, $filterGroups)
    {
        $model->deleteFilterGroups();

        foreach ($filterGroups as $data) {
            $group = new AudienceFilterGroup();
            $group->type = $data['type'];
            $model->filterGroups()->save($group);
            $this->persistRules($data['rules'], $group);
        }
    }

    /**
     * @param                     $rules
     * @param AudienceFilterGroup $group
     */
    private function persistRules($rules, AudienceFilterGroup $group)
    {
        foreach ($rules as $data) {
            $rule = new AudienceFilterRule();
            $rule->key = $data['key'];
            switch ($rule->key) {
                case 'tag':
                    $rule->value = $data['value'];
                    break;
                default:
                    $rule->value = $data['value'];
            }
            $group->rules()->save($rule);
        }
    }
}