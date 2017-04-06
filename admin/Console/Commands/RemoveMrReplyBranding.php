<?php namespace Admin\Console\Commands;

use Carbon\Carbon;
use Common\Models\Template;
use Illuminate\Console\Command;
use Common\Jobs\UpdateGreetingTextOnFacebook;
use Common\Repositories\Bot\BotRepositoryInterface;
use MongoDB\BSON\ObjectID;

class RemoveMrReplyBranding extends Command
{

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'admin-scripts:remove-branding';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Remove Mr. Reply branding from welcome message and greeting text!';

    /**
     * @var BotRepositoryInterface
     */
    private $botRepo;

    /**
     * RemoveMrReplyBranding constructor.
     * @param BotRepositoryInterface $botRepo
     */
    public function __construct(BotRepositoryInterface $botRepo)
    {
        parent::__construct();
        $this->botRepo = $botRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bots = $this->botRepo->getAll();
        $ids = [];
        foreach ($bots as $bot) {
            $update = [];
            foreach ($bot->greeting_text as $i => $greetingText) {
                $update["greeting_text.{$i}.text"] = trim(str_replace('- Powered By: MrReply.com', '', $greetingText->text));
            }
            $this->botRepo->update($bot, $update);

            // if the bot is enabled, update greeting text on Facebook.
            if ($bot->enabled) {
                dispatch(new UpdateGreetingTextOnFacebook($bot, new ObjectID(null)));
            }
            $ids[] = $bot->welcome_message->template_id;
        }

        Template::whereIn('_id', $ids)->where('messages.readonly', true)->update(['messages.$.deleted_at' => mongo_date()]);
        // Update messages service to allow for saving the deleted_at readonly message
        // Update frontend.
        // Update validation.
    }
}
