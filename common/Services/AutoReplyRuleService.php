<?php namespace Common\Services;

use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\AutoReplyRule;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Common\Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface;

class AutoReplyRuleService
{

    /**
     * @type AutoReplyRuleRepositoryInterface
     */
    private $autoReplyRuleRepo;
    /**
     * @var TemplateService
     */
    private $templates;

    /**
     * AutoReplyRuleService constructor.
     *
     * @param AutoReplyRuleRepositoryInterface $autoReplyRuleRepo
     * @param TemplateService                  $templates
     * @internal param TemplateRepositoryInterface $templateRepo
     */
    public function __construct(AutoReplyRuleRepositoryInterface $autoReplyRuleRepo, TemplateService $templates)
    {
        $this->templates = $templates;
        $this->autoReplyRuleRepo = $autoReplyRuleRepo;
    }

    /**
     * @param ObjectID $ruleId
     * @param Bot      $bot
     * @return AutoReplyRule
     */
    private function findOrFail(ObjectID $ruleId, Bot $bot)
    {
        /** @var AutoReplyRule $rule */
        $rule = $this->autoReplyRuleRepo->findByIdForBot($ruleId, $bot->_id);
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
            $filterBy[] = ['operator' => 'prefix', 'key' => 'keywords', 'value' => $keyword];
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
            'mode'        => array_search($input['mode'], AutoReplyRuleRepositoryInterface::_MATCH_MODE_MAP),
            'keywords'    => $input['keywords'],
            'template_id' => new ObjectID($input['template']['id']),
            'bot_id'      => $bot->_id,
            'readonly'    => false,
        ];

        /** @var AutoReplyRule $ret */
        $ret = $this->autoReplyRuleRepo->create($data);

        return $ret;
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
        $id = new ObjectID($id);
        $rule = $this->findOrFail($id, $bot);

        if ($rule->readonly) {
            throw new BadRequestHttpException("Default rules cannot be edited.");
        }

        $data = [
            'mode'        => array_search($input['mode'], AutoReplyRuleRepositoryInterface::_MATCH_MODE_MAP),
            'keywords'    => $input['keywords'],
            'template_id' => new ObjectID($input['template']['id']),
        ];

        $this->autoReplyRuleRepo->update($rule, $data);

        return $rule;
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
     * Create the default (subscription/unsubscription) auto reply rules.
     * @param Bot      $bot
     * @param ObjectID $confirmUnsubscriptionTemplateId
     * @return bool
     */
    public function createDefaultAutoReplyRules(Bot $bot, ObjectID $confirmUnsubscriptionTemplateId)
    {
        $data = [
            [
                'mode'      => AutoReplyRuleRepositoryInterface::MATCH_MODE_IS,
                'keywords'  => ['start', 'subscribe'],
                'bot_id'    => $bot->_id,
                'readonly'  => true,
                'subscribe' => true
            ],
            [
                'mode'        => AutoReplyRuleRepositoryInterface::MATCH_MODE_IS,
                'keywords'    => ['stop', 'unsubscribe'],
                'bot_id'      => $bot->_id,
                'readonly'    => true,
                'template_id' => $confirmUnsubscriptionTemplateId,
            ]
        ];

        return $this->autoReplyRuleRepo->bulkCreate($data);
    }
}