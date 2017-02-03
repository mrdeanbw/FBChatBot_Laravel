<?php namespace App\Providers;

use App\Repositories;
use Illuminate\Support\ServiceProvider;

class EloquentRepositoryServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $interfaceToConcreteMap = [
            Repositories\Tag\TagRepository::class                         => Repositories\Tag\EloquentTagRepository::class,
            Repositories\User\UserRepository::class                       => Repositories\User\EloquentUserRepository::class,
            Repositories\Page\PageRepository::class                       => Repositories\Page\EloquentPageRepository::class,
            Repositories\Filter\FilterRepository::class                   => Repositories\Filter\EloquentFilterRepository::class,
            Repositories\Sequence\SequenceRepository::class               => Repositories\Sequence\EloquentSequenceRepository::class,
            Repositories\MainMenu\MainMenuRepository::class               => Repositories\MainMenu\EloquentMainMenuRepository::class,
            Repositories\Template\TemplateRepository::class               => Repositories\Template\EloquentTemplateRepository::class,
            Repositories\Broadcast\BroadcastRepository::class             => Repositories\Broadcast\EloquentBroadcastRepository::class,
            Repositories\Subscriber\SubscriberRepository::class           => Repositories\Subscriber\EloquentSubscriberRepository::class,
            Repositories\PaymentPlan\PaymentPlanRepository::class         => Repositories\PaymentPlan\EloquentPaymentPlanRepository::class,
            Repositories\MessageBlock\MessageBlockRepository::class       => Repositories\MessageBlock\EloquentMessageBlockRepository::class,
            Repositories\DefaultReply\DefaultReplyRepository::class       => Repositories\DefaultReply\EloquentDefaultReplyRepository::class,
            Repositories\GreetingText\GreetingTextRepository::class       => Repositories\GreetingText\EloquentGreetingTextRepository::class,
            Repositories\AutoReplyRule\AutoReplyRuleRepository::class     => Repositories\AutoReplyRule\EloquentAutoReplyRuleRepository::class,
            Repositories\Subscriber\SubscriberHistoryRepository::class    => Repositories\Subscriber\EloquentSubscriberHistoryRepository::class,
            Repositories\WelcomeMessage\WelcomeMessageRepository::class   => Repositories\WelcomeMessage\EloquentWelcomeMessageRepository::class,
            Repositories\MessagePreview\MessagePreviewRepository::class   => Repositories\MessagePreview\EloquentMessagePreviewRepository::class,
            Repositories\MessageInstance\MessageInstanceRepository::class => Repositories\MessageInstance\EloquentMessageInstanceRepository::class,
            Repositories\BugRepository\BugRepositoryInterface::class         => Repositories\BugRepository\DBBugRepository::class,
            Repositories\CommentRepository\CommentRepositoryInterface::class => Repositories\CommentRepository\DBCommentRepository::class,
        ];

        foreach ($interfaceToConcreteMap as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }
}
