<?php namespace App\Repositories\GreetingText;

use App\Models\GreetingText;
use App\Models\Page;
use App\Repositories\BaseEloquentRepository;

class EloquentGreetingTextRepository extends BaseEloquentRepository implements GreetingTextRepository
{
    
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

    /**
     * Create a greeting text for a page.
     * @param array $data
     * @param Page  $page
     * @return GreetingText
     */
    public function create(array $data, Page $page)
    {
        return $page->greetingText->create($data);
    }
}
