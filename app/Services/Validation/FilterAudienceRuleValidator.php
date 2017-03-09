<?php namespace App\Services\Validation;

use Common\Models\Bot;

trait FilterAudienceRuleValidator
{

    /**
     * @param Bot $bot
     * @return callable
     */
    public function filterGroupRuleValidationCallback(Bot $bot)
    {
        return function ($validator, $input) use ($bot) {

            foreach (array_get($input, 'filter.groups', []) as $group) {

                foreach (array_get($group, 'rules', []) as $rule) {

                    if (array_get($rule, 'key') == 'gender' && ! in_array(array_get($rule, 'value'), ['male', 'female'])) {
                        $validator->errors()->add('groups', "The gender has to be either male or female.");

                        return $validator;
                    }

                    if (array_get($rule, 'key') == 'tag' && ! in_array(array_get($rule, 'value'), $bot->tags)) {
                        $validator->errors()->add('groups', "You must select a valid tag.");

                        return $validator;
                    }
                }

            }

            return $validator;
        };
    }
}