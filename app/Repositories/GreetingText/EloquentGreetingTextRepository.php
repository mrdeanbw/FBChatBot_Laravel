<?php namespace App\Repositories\GreetingText;

use App\Models\GreetingText;
use App\Models\Page;
use App\Repositories\BaseEloquentRepository;

class EloquentGreetingTextRepository extends BaseEloquentRepository implements GreetingTextRepository
{

    /**
     * Make a GreetingText object, without actually persisting it.
     * @param string $text
     * @return GreetingText
     */
    public function make($text)
    {
        $ret = new GreetingText();
        $ret->text = $text;

        return $ret;
    }

    /**
     * Get the greeting text for a given page.
     * @param Page $page
     * @return GreetingText
     */
    public function getForPage(Page $page)
    {
        return $page->greetingText;
    }

    /**
     * Update a greeting text's body.
     * @param GreetingText $greetingText
     * @param string       $text
     */
    public function update(GreetingText $greetingText, $text)
    {
        $greetingText->text = $text;
        $greetingText->save();
    }
}
