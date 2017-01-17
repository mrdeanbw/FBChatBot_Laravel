<?php namespace App\Repositories\GreetingText;

use App\Models\GreetingText;
use App\Models\Page;

interface GreetingTextRepository
{

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

    /**
     * Create a greeting text for a page.
     * @param array $data
     * @param Page  $page
     * @return GreetingText
     */
    public function create(array $data, Page $page);
}
