<?php namespace App\Repositories\Subscriber;

use App\Models\Bot;
use App\Repositories\BaseDBRepository;
use Carbon\Carbon;

class DBSubscriberHistoryRepository extends BaseDBRepository implements SubscriberHistoryRepository
{

    public function model()
    {
        return SubscriptionHistory::class;
    }

    /**
     * Return the number of subscription actions in a given time period (today, yesterday.. etc) or on a given date.
     * @param Carbon|string $date
     * @param Bot           $page
     * @return int
     */
    public function subscriptionCountForPage($date, Bot $page)
    {
        return $page->subscriptionHistory()->where('action', 'subscribed')->date('action_at', $date)->count();
    }

    /**
     * Return the number of subscription actions in a given time period (today, yesterday.. etc) or on a given date.
     * @param Carbon|string $date
     * @param Bot           $page
     * @return int
     */
    public function unsubscriptionCountForPage($date, Bot $page)
    {
        return $page->subscriptionHistory()->where('action', 'unsubscribed')->date('action_at', $date)->count();
    }
}
