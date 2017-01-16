<?php namespace App\Repositories\Subscriber;

use App\Models\Page;
use App\Repositories\BaseEloquentRepository;
use Carbon\Carbon;

class EloquentSubscriberHistoryRepository extends BaseEloquentRepository implements SubscriberHistoryRepository
{

    /**
     * Return the number of subscription actions in a given time period (today, yesterday.. etc) or on a given date.
     * @param Carbon|string $date
     * @param Page          $page
     * @return int
     */
    public function subscriptionCountForPage($date, Page $page)
    {
        return $page->subscriptionHistory()->where('action', 'subscribed')->date('action_at', $date)->count();
    }

    /**
     * Return the number of subscription actions in a given time period (today, yesterday.. etc) or on a given date.
     * @param Carbon|string $date
     * @param Page          $page
     * @return int
     */
    public function unsubscriptionCountForPage($date, Page $page)
    {
        return $page->subscriptionHistory()->where('action', 'unsubscribed')->date('action_at', $date)->count();
    }
}
