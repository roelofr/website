<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Payments\PaymentPaid;
use App\Listeners;
use App\Listeners\AddVerifiedPermission;
use App\Listeners\CheckConscriboWhenVerified;
use App\Listeners\EnrollmentStateChangeListener;
use App\Listeners\MediaUploadListener;
use App\Models;
use App\Observers;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\MediaLibrary\Events\MediaHasBeenAdded;
use Spatie\ModelStates\Events\StateChanged;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        MediaHasBeenAdded::class => [
            MediaUploadListener::class,
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Verified::class => [
            CheckConscriboWhenVerified::class,
            AddVerifiedPermission::class,
        ],
        StateChanged::class => [
            EnrollmentStateChangeListener::class,
        ],
        PaymentPaid::class => [
            Listeners\Shop\PaymentPaidListener::class,
            Listeners\Enrollments\PaymentPaidListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Register observers
        Models\Activity::observe(Observers\ActivityObserver::class);
        Models\ActivityMessage::observe(Observers\ActivityMessageObserver::class);
        Models\Enrollment::observe(Observers\EnrollmentObserver::class);
        Models\FileBundle::observe(Observers\FileBundleObserver::class);
        Models\NewsItem::observe(Observers\NewsItemObserver::class);
        Models\Sponsor::observe(Observers\SponsorObserver::class);
        Models\User::observe(Observers\UserObserver::class);

        Models\Payment::observe(Observers\PaymentObserver::class);
    }

    /**
     * Auto-discover events.
     *
     * @return true
     */
    public function shouldDiscoverEvents()
    {
        return true;
    }
}
