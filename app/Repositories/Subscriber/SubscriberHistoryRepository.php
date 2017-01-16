<?php namespace App\Repositories\Subscriber;

use App\Models\Page;
use Carbon\Carbon;

interface SubscriberHistoryRepository
{

    /**
     * Return the number of subscription actions in a given time period (today, yesterday.. etc) or on a given date.
     * @param Carbon|string $date
     * @param Page          $page
     * @return int
     */
    public function subscriptionCountForPage($date, Page $page);

    /**
     * Return the number of subscription actions in a given time period (today, yesterday.. etc) or on a given date.
     * @param Carbon|string $date
     * @param Page          $page
     * @return int
     */
    public function unsubscriptionCountForPage($date, Page $page);

}
