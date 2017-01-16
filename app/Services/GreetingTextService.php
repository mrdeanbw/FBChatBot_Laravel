<?php namespace App\Services;

use App\Models\Page;
use App\Models\GreetingText;
use App\Services\Facebook\Thread;
use App\Repositories\GreetingText\GreetingTextRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GreetingTextService
{

    /**
     * @var Thread
     */
    private $messengerThread;
    /**
     * @type GreetingTextRepository
     */
    private $greetingTextRepo;

    /**
     * GreetingTextService constructor.
     *
     * @param GreetingTextRepository $greetingTextRepo
     * @param Thread                 $FacebookThread
     */
    public function __construct(GreetingTextRepository $greetingTextRepo, Thread $FacebookThread)
    {
        $this->messengerThread = $FacebookThread;
        $this->greetingTextRepo = $greetingTextRepo;
    }

    /**
     * Get the default greeting text.
     * @param Page $page
     * @return GreetingText
     */
    public function defaultGreetingText(Page $page)
    {
        return $this->greetingTextRepo->make("Hello {{first_name}}, Welcome to {$page->name}! - Powered By: MrReply.com");
    }

    /**
     * Get a page's greeting text.
     * @param Page $page
     * @return GreetingText
     */
    public function get(Page $page)
    {
        $greetingText = $this->greetingTextRepo->getForPage($page);
        if (! $greetingText) {
            throw  new ModelNotFoundException;
        }

        return $greetingText;
    }

    /**
     * Persist greeting text, and update the Facebook page's greeting text.
     * @param array $input
     * @param Page  $page
     * @return bool
     */
    public function persist(array $input, Page $page)
    {
        $greetingText = $this->get($page);
        $this->greetingTextRepo->update($greetingText, trim($input['text']));

        return $this->updateGreetingTextOnFacebook($page);
    }

    /**
     * Make a request to Facebook API to update the greeting text.
     * @param Page $page
     * @return bool
     */
    public function updateGreetingTextOnFacebook(Page $page)
    {
        $greetingText = $this->get($page);
        $text = $this->normaliseGreetingText($greetingText);

        $response = $this->messengerThread->addGreetingText($page->access_token, $text);

        $success = isset($response->result) && starts_with($response->result, "Successfully");
        if (! $success) {
            \Log::error("Failed to update greeting text[$page->greetingText->id]");
            \Log::error(json_encode($response));
        }

        return $success;
    }

    /**
     * Map our own name placeholder {{SHORT_CODE}} to Facebook processed placeholder.
     * @see https://developers.facebook.com/docs/messenger-platform/thread-settings/greeting-text#personalization
     * @param GreetingText $greetingText
     * @return string
     */
    public function normaliseGreetingText(GreetingText $greetingText)
    {
        return str_replace(
            ['{{first_name}}', '{{last_name}}', '{{full_name}}'],
            ['{{user_first_name}}', '{{user_last_name}}', '{{user_full_name}}'],
            $greetingText->text
        );
    }

}