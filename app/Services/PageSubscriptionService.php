<?php namespace App\Services;

use DB;
use Exception;
use App\Models\Page;
use App\Models\PaymentPlan;

class PageSubscriptionService
{

    /**
     * @param      $token
     * @param Page $page
     * @throws Exception
     */
    public function newSubscription($token, Page $page)
    {
        if ($page->subscription()) {
            throw new Exception("You are already subscribed to Pro plan.");
        }

        DB::transaction(function () use ($page, $token) {
            $this->enableDisabledBlocks($page);
            $page->newSubscription('default', $this->getStripePlanId($page))->create($token);
        });
    }

    /**
     * @param Page $page
     * @return string
     */
    private function getStripePlanId(Page $page)
    {
        $subscriberCount = $page->activeSubscribers()->count();

        return PaymentPlan::where('name', 'pro')->where('subscribers', '>=', $subscriberCount)->orderBy('subscribers')->firstOrFail()->stripe_id;
    }

    /**
     * @param Page $page
     */
    private function enableDisabledBlocks(Page $page)
    {
        if ($mainMenuDisabledBlock = $page->mainMenu->messageBlocks()->whereIsDisabled(1)->first()) {
            $mainMenuDisabledBlock->is_disabled = false;
            $mainMenuDisabledBlock->save();
        }

        if ($welcomeMessageDisabledBlock = $page->welcomeMessage->messageBlocks()->whereIsDisabled(1)->first()) {
            $welcomeMessageDisabledBlock->is_disabled = false;
            $welcomeMessageDisabledBlock->save();
        }
    }
}