<?php
namespace App\Services\Validation;

use App\Models\Page;
use App\Services\TagService;

trait FilterAudienceRuleValidator
{

    /**
     * @param Page $page
     * @return callable
     */
    public function filterGroupRuleValidationCallback(Page $page)
    {
        /** @type TagService $tags */
        $tags = app(TagService::class);

        $tagList = $tags->tagList($page)->toArray();

        return function ($validator, $input) use ($tagList) {

            foreach (array_get($input, 'filter_groups', []) as $group) {

                foreach (array_get($group, 'rules', []) as $rule) {

                    if (array_get($rule, 'key') == 'gender' && ! in_array(array_get($rule, 'value'), ['male', 'female'])) {
                        $validator->errors()->add('groups', "The gender has to be either male or female.");

                        return $validator;
                    }

                    if (array_get($rule, 'key') == 'tag' && ! in_array(array_get($rule, 'value'), $tagList)) {
                        $validator->errors()->add('groups', "You must select a valid tag.");

                        return $validator;
                    }
                }

            }

            return $validator;
        };
    }
}