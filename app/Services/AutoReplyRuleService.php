<?php

namespace App\Services;

use App\Models\AutoReplyRule;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AutoReplyRuleService
{

    /**
     * @param Page $page
     * @return Collection
     */
    public function all(Page $page)
    {
        return $page->autoReplyRules;
    }

    /**
     * @param      $ruleId
     * @param Page $page
     */
    public function delete($ruleId, Page $page)
    {
        $rule = $this->find($ruleId, $page);

        if ($rule->is_disabled) {
            throw new BadRequestHttpException("Default rules cannot be edited.");
        }

        $rule->delete();
    }

    /**
     * @param      $input
     * @param Page $page
     * @return AutoReplyRule
     */
    public function create($input, Page $page)
    {
        $rule = new AutoReplyRule();
        $rule->mode = $input['mode'];
        $rule->keyword = $input['keyword'];
        $rule->action = 'send';
        $rule->template_id = $input['template']['id'];

        $page->autoReplyRules()->save($rule);

        return $rule->fresh();
    }

    /**
     * @param      $id
     * @param      $input
     * @param Page $page
     * @return AutoReplyRule
     */
    public function update($id, $input, Page $page)
    {
        $rule = $this->find($id, $page);
        if ($rule->is_disabled) {
            throw new BadRequestHttpException("Default rules cannot be edited.");
        }

        $rule->mode = $input['mode'];
        $rule->keyword = $input['keyword'];
        $rule->template_id = $input['template']['id'];
        $rule->save();
    }

    /**
     * @param $ruleId
     * @param $page
     * @return AutoReplyRule
     */
    private function find($ruleId, $page)
    {
        return $page->autoReplyRules()->findOrFail($ruleId);
    }

    /**
     * @param      $message
     * @param Page $page
     * @return AutoReplyRule
     */
    public function matching($message, Page $page)
    {
        return $page->autoReplyRules()->where(function ($query) use ($message) {
            $query->whereMode('is')->where('keyword', '=', $message);
        })->orWhere(function ($query) use ($message) {
            $query->whereMode('contains')->where('keyword', 'LIKE', "%{$message}%");
        })->orWhere(function ($query) use ($message) {
            $query->whereMode('begins_with')->where('keyword', 'LIKE', "{$message}%");
        })->with('template')->first();
    }
}