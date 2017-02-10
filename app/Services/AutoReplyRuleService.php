<?php namespace App\Services;

use App\Models\Bot;
use App\Models\AutoReplyRule;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface;

class AutoReplyRuleService
{

    /**
     * @type AutoReplyRuleRepositoryInterface
     */
    private $autoReplyRuleRepo;

    /**
     * AutoReplyRuleService constructor.
     * @param AutoReplyRuleRepositoryInterface $autoReplyRuleRepo
     */
    public function __construct(AutoReplyRuleRepositoryInterface $autoReplyRuleRepo)
    {
        $this->autoReplyRuleRepo = $autoReplyRuleRepo;
    }

    /**
     * @param      $ruleId
     * @param Bot  $bot
     * @return AutoReplyRule
     */
    private function findOrFail($ruleId, Bot $bot)
    {
        $rule = $this->autoReplyRuleRepo->findByIdForBot($ruleId, $bot);

        if (! $rule) {
            throw new ModelNotFoundException;
        }

        return $rule;
    }

    /**
     * @param Bot   $bot
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return Paginator
     */
    public function paginate(Bot $bot, $page = 1, $filterBy = [], $orderBy = [], $perPage = 20)
    {
        if ($keyword = array_get($filterBy, 'keyword')) {
            $filterBy = [
                [
                    'type'      => 'contains',
                    'attribute' => 'keyword',
                    'value'     => $keyword
                ]
            ];
        } else {
            $filterBy = [];
        }

        $orderBy = $orderBy?: ['_id' => 'asc'];

        return $this->autoReplyRuleRepo->paginateForBot($bot, $page, $filterBy, $orderBy, $perPage);
    }

    /**
     * @param array $input
     * @param Bot   $bot
     * @return AutoReplyRule
     */
    public function create(array $input, Bot $bot)
    {
        $data = [
            'action'      => 'send',
            'mode'        => $input['mode'],
            'keyword'     => $input['keyword'],
            'template_id' => $input['template']['id'],
            'bot_id'      => $bot->id,
            'readonly'    => false,
        ];

        return $this->autoReplyRuleRepo->create($data);
    }

    /**
     * @param string $id
     * @param array  $input
     * @param Bot    $bot
     * @return AutoReplyRule
     */
    public function update($id, array $input, Bot $bot)
    {
        $rule = $this->findOrFail($id, $bot);

        if ($rule->readonly) {
            throw new BadRequestHttpException("Default rules cannot be edited.");
        }

        $data = [
            'mode'        => $input['mode'],
            'keyword'     => $input['keyword'],
            'template_id' => $input['template']['id'],
        ];

        return $this->autoReplyRuleRepo->update($rule, $data);
    }

    /**
     * Delete an auto reply rule.
     * @param      $ruleId
     * @param Bot  $page
     */
    public function delete($ruleId, Bot $page)
    {
        $rule = $this->findOrFail($ruleId, $page);

        if ($rule->readonly) {
            throw new BadRequestHttpException("Default rules cannot be edited.");
        }

        $this->autoReplyRuleRepo->delete($rule);
    }

    /**
     * @param string $text
     * @param Bot    $page
     * @return AutoReplyRule
     */
    public function getMatchingRule($text, Bot $page)
    {
        return $this->autoReplyRuleRepo->getMatchingRuleForPage($text, $page);
    }

    /**
     * Create the default (subscription/unsubscription) auto reply rules.
     * @param Bot $bot
     * @return bool
     */
    public function createDefaultAutoReplyRules(Bot $bot)
    {
        // The default subscription / unsubscription auto reply rules.
        $defaultRules = [
            'subscribe'   => ['start', 'subscribe'],
            'unsubscribe' => ['stop', 'unsubscribe']
        ];

        $bot_id = $bot->id;

        // Exact Match
        $mode = 'is';

        // Non-editable
        $readonly = true;

        $data = [];

        // Loop and save everyone of them
        foreach ($defaultRules as $action => $keywords) {
            foreach ($keywords as $keyword) {
                $data[] = compact('mode', 'action', 'keyword', 'readonly', 'bot_id');
            }
        }

        return $this->autoReplyRuleRepo->bulkCreate($data);
    }
}