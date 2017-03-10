<?php namespace Common\Transformers;

use Common\Models\SubscriptionHistory;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;

class SubscriptionHistoryTransformer extends BaseTransformer
{

    public function transform(SubscriptionHistory $record)
    {
        return [
            'action'    => $this->getAction($record->action),
            'action_at' => carbon_date($record->action_at)->toAtomString()
        ];
    }

    /**
     * @param $action
     * @return string
     */
    protected function getAction($action)
    {
        if ($action == SubscriberRepositoryInterface::ACTION_SUBSCRIBED) {
            return 'subscribed';
        }

        if ($action == SubscriberRepositoryInterface::ACTION_UNSUBSCRIBED) {
            return 'unsubscribed';
        }

        return null;
    }
}