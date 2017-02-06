<?php namespace App\Repositories\Subscriber;

use App\Models\Bot;
use Carbon\Carbon;

interface SubscriberHistoryRepository
{

    /**
     * Return the number of subscription actions in a given time period (today, yesterday.. etc) or on a given date.
     * @param Carbon|string $date
     * @param Bot           $page
     * @return int
     */
    public function subscriptionCountForPage($date, Bot $page);

    /**
     * Return the number of subscription actions in a given time period (today, yesterday.. etc) or on a given date.
     * @param Carbon|string $date
     * @param Bot           $page
     * @return int
     */
    public function unsubscriptionCountForPage($date, Bot $page);

}
