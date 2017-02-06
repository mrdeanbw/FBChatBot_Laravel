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
            Repositories\Bot\BotRepositoryInterface::class               => Repositories\Bot\DBBotRepository::class,
            Repositories\Tag\TagRepository::class                        => Repositories\Tag\EloquentTagRepository::class,
            Repositories\User\UserRepositoryInterface::class             => Repositories\User\DBUserRepository::class,
            Repositories\Filter\FilterRepository::class                  => Repositories\Filter\DBFilterRepository::class,
            Repositories\Sequence\SequenceRepositoryInterface::class     => Repositories\Sequence\DBSequenceRepository::class,
            Repositories\Template\TemplateRepositoryInterface::class     => Repositories\Template\DBTemplateRepository::class,
            Repositories\Broadcast\BroadcastRepository::class            => Repositories\Broadcast\DBBroadcastRepository::class,
            Repositories\Subscriber\SubscriberRepositoryInterface::class => Repositories\Subscriber\DBSubscriberRepository::class,
            Repositories\PaymentPlan\PaymentPlanRepository::class        => Repositories\PaymentPlan\EloquentPaymentPlanRepository::class,
            Repositories\AutoReplyRule\AutoReplyRuleRepositoryInterface::class => Repositories\AutoReplyRule\DBAutoReplyRuleRepository::class,
            Repositories\Subscriber\SubscriberHistoryRepository::class         => Repositories\Subscriber\DBSubscriberHistoryRepository::class,
            Repositories\MessagePreview\MessagePreviewRepository::class        => Repositories\MessagePreview\DBMessagePreviewRepository::class,
            Repositories\MessageInstance\MessageInstanceRepository::class      => Repositories\MessageInstance\EloquentMessageInstanceRepository::class,
        ];

        foreach ($interfaceToConcreteMap as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }
}
