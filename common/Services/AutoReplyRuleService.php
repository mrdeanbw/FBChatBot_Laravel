<?php namespace Common\Services;

use Common\Models\Bot;
use Common\Models\AutoReplyRule;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Common\Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface;

class AutoReplyRuleService
{

    protected $matchModeMap = [
        'is'          => AutoReplyRuleRepositoryInterface::MATCH_MODE_IS,
        'begins_with' => AutoReplyRuleRepositoryInterface::MATCH_MODE_PREFIX,
        'contains'    => AutoReplyRuleRepositoryInterface::MATCH_MODE_CONTAINS,
    ];
    /**
     * @type AutoReplyRuleRepositoryInterface
     */
    private $autoReplyRuleRepo;

    /**
     * AutoReplyRuleService constructor.
     *
     * @param AutoReplyRuleRepositoryInterface $autoReplyRuleRepo
     */
    public function __construct(AutoReplyRuleRepositoryInterface $autoReplyRuleRepo)
    {
        $this->autoReplyRuleRepo = $autoReplyRuleRepo;
    }

    /**
     * @param      $ruleId
     * @param Bot  $bot
     *
     * @return AutoReplyRule
     */
    private function findOrFail($ruleId, Bot $bot)
    {
        $rule = $this->autoReplyRuleRepo->findByIdForBot($ruleId, $bot);

        if (! $rule) {
            throw new NotFoundHttpException;
        }

        return $rule;
    }

    /**
     * @param Bot   $bot
     * @param int   $page
     * @param array $filter
     * @param array $orderBy
     * @param int   $perPage
     *
     * @return Paginator
     */
    public function paginate(Bot $bot, $page = 1, $filter = [], $orderBy = [], $perPage = 20)
    {
        $filterBy = [];
        if ($keyword = array_get($filter, 'keyword')) {
            $filterBy[] = ['operator' => 'prefix', 'key' => 'keyword', 'value' => $keyword];
        }

        $orderBy = $orderBy?: ['_id' => 'asc'];

        return $this->autoReplyRuleRepo->paginateForBot($bot, $page, $filterBy, $orderBy, $perPage);
    }

    /**
     * @param array $input
     * @param Bot   $bot
     *
     * @return AutoReplyRule
     */
    public function create(array $input, Bot $bot)
    {
        $data = [
            'action'      => 'send',
            'mode'        => $this->matchModeMap[$input['mode']],
            'keyword'     => $input['keyword'],
            'template_id' => $input['template']['id'],
            'bot_id'      => $bot->_id,
            'readonly'    => false,
        ];

        return $this->autoReplyRuleRepo->create($data);
    }

    /**
     * @param string $id
     * @param array  $input
     * @param Bot    $bot
     *
     * @return AutoReplyRule
     */
    public function update($id, array $input, Bot $bot)
    {
        $rule = $this->findOrFail($id, $bot);

        if ($rule->readonly) {
            throw new BadRequestHttpException("Default rules cannot be edited.");
        }

        $data = [
            'mode'        => $this->matchModeMap[$input['mode']],
            'keyword'     => $input['keyword'],
            'template_id' => $input['template']['id'],
        ];

        $this->autoReplyRuleRepo->update($rule, $data);

        return $rule;
    }

    /**
     * Delete an auto reply rule.
     *
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
     * Create the default (subscription/unsubscription) auto reply rules.
     *
     * @param Bot $bot
     *
     * @return bool
     */
    public function createDefaultAutoReplyRules(Bot $bot)
    {
        // The default subscription / unsubscription auto reply rules.
        $defaultRules = [
            'subscribe'   => ['start', 'subscribe'],
            'unsubscribe' => ['stop', 'unsubscribe']
        ];

        $bot_id = $bot->_id;

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