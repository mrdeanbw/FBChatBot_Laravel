<?php namespace App\Repositories\GreetingText;

use App\Models\GreetingText;
use App\Models\Page;

interface GreetingTextRepository
{

    /**
     * Make a GreetingText object, without actually persisting it.
     * @param string $text
     * @return GreetingText
     */
    public function make($text);

    /**
     * Get the greeting text for a given page.
     * @param Page $page
     * @return GreetingText
     */
    public function getForPage(Page $page);

    /**
     * Update a greeting text's body.
     * @param GreetingText $greetingText
     * @param string       $text
     */
    public function update(GreetingText $greetingText, $text);
}
