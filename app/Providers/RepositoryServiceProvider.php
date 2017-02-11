<?php namespace App\Providers;

use App\Repositories;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $interfaceToConcreteMap = [
            Repositories\Bot\BotRepositoryInterface::class                       => Repositories\Bot\DBBotBaseRepository::class,
            Repositories\User\UserRepositoryInterface::class                     => Repositories\User\DBUserBaseRepository::class,
            Repositories\Sequence\SequenceRepositoryInterface::class             => Repositories\Sequence\DBSequenceBaseRepository::class,
            Repositories\Template\TemplateRepositoryInterface::class             => Repositories\Template\DBTemplateBaseRepository::class,
            Repositories\Broadcast\BroadcastRepositoryInterface::class           => Repositories\Broadcast\DBBroadcastBaseRepository::class,
            Repositories\Subscriber\SubscriberRepositoryInterface::class         => Repositories\Subscriber\DBSubscriberBaseRepository::class,
            Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface::class   => Repositories\AutoReplyRule\DBAutoReplyRuleBaseRepository::class,
            Repositories\MessagePreview\MessagePreviewRepositoryInterface::class => Repositories\MessagePreview\DBMessagePreviewBaseRepository::class,

            Repositories\MessageInstance\MessageHistoryRepositoryInterface::class => Repositories\MessageInstance\DBMessageHistoryRepository::class,
        ];

        foreach ($interfaceToConcreteMap as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }
}
