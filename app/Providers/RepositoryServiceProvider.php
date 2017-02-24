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
            Repositories\Bot\BotRepositoryInterface::class                       => Repositories\Bot\DBBotRepository::class,
            Repositories\User\UserRepositoryInterface::class                     => Repositories\User\DBUserRepository::class,
            Repositories\Sequence\SequenceRepositoryInterface::class             => Repositories\Sequence\DBSequenceRepository::class,
            Repositories\BugRepository\BugRepositoryInterface::class             => Repositories\BugRepository\DBBugRepository::class,
            Repositories\Template\TemplateRepositoryInterface::class             => Repositories\Template\DBTemplateRepository::class,
            Repositories\Broadcast\BroadcastRepositoryInterface::class           => Repositories\Broadcast\DBBroadcastRepository::class,
            Repositories\Subscriber\SubscriberRepositoryInterface::class         => Repositories\Subscriber\DBSubscriberRepository::class,
            Repositories\Sequence\SequenceScheduleRepositoryInterface::class     => Repositories\Sequence\DBSequenceScheduleRepository::class,
            Repositories\CommentRepository\CommentRepositoryInterface::class       => Repositories\CommentRepository\DBCommentRepository::class,
            Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface::class     => Repositories\AutoReplyRule\DBAutoReplyRuleRepository::class,
            Repositories\MessagePreview\MessagePreviewRepositoryInterface::class   => Repositories\MessagePreview\DBMessagePreviewRepository::class,
            Repositories\SentMessage\SentMessageRepositoryInterface::class         => Repositories\SentMessage\DBSentSentMessageRepository::class,
            Repositories\MessageRevision\MessageRevisionRepositoryInterface::class => Repositories\MessageRevision\DBMessageRevisionRepository::class,
        ];

        foreach ($interfaceToConcreteMap as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }
}
