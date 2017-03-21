<?php namespace Common\Transformers;

use Common\Models\Page;

class PageTransformer extends BaseTransformer
{

    public function transform(Page $page)
    {
        return [
            'id'          => $page->id,
            'name'        => $page->name,
            'avatar_url'  => $page->avatar_url,
            'url'         => $page->url,
            'bot_id'      => isset($page->bot)? $page->bot->id : null,
            'bot_enabled' => isset($page->bot)? $page->bot->enabled : null,
        ];
    }
}
