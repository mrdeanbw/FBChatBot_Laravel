<?php
namespace App\Transformers;

use App\Models\Page;

class PageTransformer extends BaseTransformer
{

    public function transform(Page $page)
    {
        // remote facebook pages may not have an ID.
        return [
            'id'                  => is_null($page->id)? null : (int)$page->id,
            'facebook_id'         => $page->facebook_id,
            'name'                => $page->name,
            'avatar_url'          => $page->avatar_url,
            'url'                 => $page->url,
            'bot_enabled'         => is_null($page->bot_enabled)? null : (bool)$page->bot_enabled,
            'bot_timezone'        => is_null($page->bot_timezone)? null : (int)$page->bot_timezone,
            'bot_timezone_string' => $page->bot_timezone_string,
            'plan'                => $page->plan,
            'subscriber_count'    => $page->activeSubscribers()->count(),
            'payment_plan'        => $page->payment_plan,
            'is_active'           => $page->is_active
        ];
    }
}