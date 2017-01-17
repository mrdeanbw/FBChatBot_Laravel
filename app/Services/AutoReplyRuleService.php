<?php namespace App\Services;

use App\Models\Page;
use App\Models\AutoReplyRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\AutoReplyRule\AutoReplyRuleRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AutoReplyRuleService
{

    /**
     * @type AutoReplyRuleRepository
     */
    private $autoReplyRuleRepo;

    /**
     * AutoReplyRuleService constructor.
     * @param AutoReplyRuleRepository $autoReplyRuleRepo
     */
    public function __construct(AutoReplyRuleRepository $autoReplyRuleRepo)
    {
        $this->autoReplyRuleRepo = $autoReplyRuleRepo;
    }

    /**
     * Get all auto reply rules associated with a page.
     * @param Page $page
     * @return Collection
     */
    public function all(Page $page)
    {
        return $this->autoReplyRuleRepo->getAllForPage($page);
    }

    /**
     * @param $ruleId
     * @param $page
     * @return AutoReplyRule
     */
    private function find($ruleId, $page)
    {
        return $this->autoReplyRuleRepo->findByIdForPage($ruleId, $page);
    }

    /**
     * @param      $ruleId
     * @param Page $page
     * @return AutoReplyRule
     */
    private function findOrFail($ruleId, Page $page)
    {
        $rule = $this->find($ruleId, $page);

        if (! $rule) {
            throw new ModelNotFoundException;
        }

        return $rule;
    }

    /**
     * Delete an auto reply rule.
     * @param      $ruleId
     * @param Page $page
     */
    public function delete($ruleId, Page $page)
    {
        $rule = $this->findOrFail($ruleId, $page);

        if ($rule->is_disabled) {
            throw new BadRequestHttpException("Default rules cannot be edited.");
        }

        $this->autoReplyRuleRepo->delete($rule);
    }

    /**
     * @param      $input
     * @param Page $page
     * @return AutoReplyRule
     */
    public function create($input, Page $page)
    {
        $data = [
            'mode'        => $input['mode'],
            'keyword'     => $input['keyword'],
            'action'      => 'send',
            'template_id' => $input['template']['id'],
        ];

        return $this->autoReplyRuleRepo->createForPage($data, $page);
    }

    /**
     * @param      $id
     * @param      $input
     * @param Page $page
     * @return AutoReplyRule
     */
    public function update($id, $input, Page $page)
    {
        $rule = $this->findOrFail($id, $page);

        if ($rule->is_disabled) {
            throw new BadRequestHttpException("Default rules cannot be edited.");
        }

        $data = [
            'mode'        => $input['mode'],
            'keyword'     => $input['keyword'],
            'template_id' => $input['template']['id'],
        ];

        $this->autoReplyRuleRepo->update($rule, $data);
    }

    /**
     * @param string $text
     * @param Page   $page
     * @return AutoReplyRule
     */
    public function getMatchingRule($text, Page $page)
    {
        return $this->autoReplyRuleRepo->getMatchingRuleForPage($text, $page);
    }

    /**
     * Create the default (subscription/unsubscription) auto reply rules.
     * @param Page $page
     */
    public function createDefaultAutoReplyRules(Page $page)
    {
        // The default subscription / unsubscription auto reply rules.
        $defaultRules = [
            'subscribe'   => ['start', 'subscribe'],
            'unsubscribe' => ['stop', 'unsubscribe']
        ];

        // Exact Match
        $mode = 'is';
        
        // Non-editable
        $is_disabled = true;

        // Loop and save everyone of them
        foreach ($defaultRules as $action => $keywords) {
            foreach ($keywords as $keyword) {
                $data = compact('mode', 'action', 'keyword', 'is_disabled');
                $this->autoReplyRuleRepo->createForPage($data, $page);
            }
        }
    }
}