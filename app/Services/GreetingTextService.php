<?php

namespace App\Services;

use App\Models\GreetingText;
use App\Models\Page;
use App\Services\Facebook\Makana\Thread;

class GreetingTextService
{

    /**
     * @var Thread
     */
    private $MakanaThread;

    /**
     * GreetingTextService constructor.
     *
     * @param Thread $MakanaThread
     */
    public function __construct(Thread $MakanaThread)
    {
        $this->MakanaThread = $MakanaThread;
    }

    /**
     * @param Page $page
     *
     * @return GreetingText
     */
    public function defaultGreetingText(Page $page)
    {
        $ret = new GreetingText();
        $ret->text = "Hello {{first_name}}, Welcome to {$page->name}! - Powered By: MrReply.com";

        return $ret;
    }

    /**
     * @param Page $page
     *
     * @return GreetingText
     */
    public function get(Page $page)
    {
        return $page->greetingText()->firstOrFail();
    }

    /**
     * @param array $input
     * @param       $page
     *
     * @return bool
     */
    public function persist($input, $page)
    {
        $greetingText = $this->get($page);
        $greetingText->text = trim($input['text']);
        $greetingText->save();

        return $this->updateGreetingTextOnFacebook($page);
    }

    /**
     * @param GreetingText $greetingText
     *
     * @return string
     */
    public function normaliseGreetingText(GreetingText $greetingText)
    {
        return str_replace(['{{first_name}}', '{{last_name}}', '{{full_name}}'], ['{{user_first_name}}', '{{user_last_name}}', '{{user_full_name}}'], $greetingText->text);
    }

    /**
     * @param Page $page
     *
     * @return bool
     */
    public function updateGreetingTextOnFacebook(Page $page)
    {
        $greetingText = $this->normaliseGreetingText($page->greetingText);
        $response = $this->MakanaThread->addGreetingText($page->access_token, $greetingText);

        $success = isset($response->result) && starts_with($response->result, "Successfully");
        if (! $success) {
            \Log::error("Failed to update greeting text[$page->greetingText->id]");
            \Log::error(json_encode($response));
        }

        return $success;
    }
}