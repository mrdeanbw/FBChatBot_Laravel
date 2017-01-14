<?php
namespace App\Services\Validation;

use App\Models\Page;

trait MessageBlockRuleValidator
{

    /**
     * @param array $rules
     * @param Page  $page
     *
     * @return array
     */
    public function rules($rules = [], Page $page)
    {
        $messageBlockValidationRules = [
            'message_blocks'   => 'bail|required|array|max:10',
            'message_blocks.*' => 'bail|required|message_block',
        ];

        return array_merge($messageBlockValidationRules, $rules);
    }

    /**
     * @param Page $page
     *
     * @return array
     */
    public function mainMenuRules(Page $page)
    {
        $mainMenuValidationRules = [
            'message_blocks'   => 'bail|required|array|max:5',
            'message_blocks.*' => 'bail|message_block:button',
        ];

        return $mainMenuValidationRules;
    }

    /**
     * @param               $input
     * @param               $rules
     * @param Page          $page
     * @param null|callable $callback
     *
     * @return \Illuminate\Validation\Validator
     */
    public function makeValidator($input, $rules, Page $page, $callback = null)
    {
        $validator = \Validator::make($input, array_merge($rules, $this->rules($rules, $page)));

        $validator->after(function ($validator) use ($callback, $input) {

            if ($callback) {
                $validator = $callback($validator, $input);
            }

            if ($this->incompatibleTags(array_get($input, 'message_blocks', []))) {
                $validator->errors()->add('message_blocks', "You cannot add the same tag to 'Tag' and 'Untag' ");
            }

            //            if ($this->incompatibleSequences(array_get($input, 'message_blocks', []))) {
            //                $validator->errors()->add('message_blocks', "A single sequence can't be selected for both 'Subscribe' and 'Unsubscribe' ");
            //            }

            return $validator;
        });

        return $validator;
    }

    /**
     * @param               $input
     * @param Page          $page
     *
     * @return \Illuminate\Validation\Validator
     */
    public function runMainMenuValidation($input, Page $page)
    {
        $validator = \Validator::make($input, $this->mainMenuRules($page));

        $validator->after(function ($validator) use ($input) {

            $blocks = array_get($input, 'message_blocks', []);

            for ($i = 0; $i < count($blocks); $i++) {
                if (array_get($blocks[$i], 'is_disabled', false)) {
                    unset($blocks[$i]);
                }
            }

            if (count($blocks) > 4) {
                $validator->errors()->add('message_blocks', "Your menu cannot have more than 5 buttons");
            }

            if ($this->incompatibleTags(array_get($input, 'message_blocks', []))) {
                $validator->errors()->add('message_blocks', "You cannot add the same tag to 'Tag' and 'Untag' ");
            }

            //            if ($this->incompatibleSequences(array_get($input, 'message_blocks', []))) {
            //                $validator->errors()->add('message_blocks', "A single sequence can't be selected for both 'Subscribe' and 'Unsubscribe' ");
            //            }

        });

        return $validator;
    }

    public function incompatibleTags($blocks)
    {
        foreach ($blocks as $block) {

            if (array_get($block, 'type') == 'button') {
                $tagTags = array_get($block, 'tag', []);
                $untagTags = array_get($block, 'untag', []);
                if (array_intersect($tagTags, $untagTags)) {
                    return true;
                }
            }

            if ($this->incompatibleTags(array_get($block, 'message_blocks', []))) {
                return true;
            }
        }

        return false;
    }


    //    public function incompatibleSequences($blocks)
    //    {
    //        foreach ($blocks as $block) {
    //
    //            if (array_get($block, 'type') == 'button') {
    //                $subscribeIds = extract_attribute(array_get($block, 'subscribe', []));
    //                $unSubscribeIds = extract_attribute(array_get($block, 'unsubscribe', []));
    //                if (array_intersect($subscribeIds, $unSubscribeIds)) {
    //                    return true;
    //                }
    //            }
    //
    //            if ($this->incompatibleSequences(array_get($block, 'message_blocks', []))) {
    //                return true;
    //            }
    //        }
    //
    //        return false;
    //    }
}