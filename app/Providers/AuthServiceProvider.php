<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models;
use App\Models\Shop as ShopModels;
use App\Models\User;
use App\Policies;
use App\Policies\Shop as ShopPolicies;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models as SpatiePermissionModels;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Models\Activity::class => Policies\ActivityPolicy::class,
        Models\ActivityMessage::class => Policies\ActivityMessagePolicy::class,
        Models\EmailList::class => Policies\EmailListPolicy::class,
        Models\Enrollment::class => Policies\EnrollmentPolicy::class,
        Models\FileBundle::class => Policies\FileBundlePolicy::class,
        Models\FileCategory::class => Policies\FileCategoryPolicy::class,
        Models\FileDownload::class => Policies\FileDownloadPolicy::class,
        Models\JoinSubmission::class => Policies\JoinSubmissionPolicy::class,
        Models\NewsItem::class => Policies\NewsItemPolicy::class,
        Models\Page::class => Policies\PagePolicy::class,
        Models\Payment::class => Policies\PaymentPolicy::class,
        Models\Role::class => Policies\RolePolicy::class,
        Models\Sponsor::class => Policies\SponsorPolicy::class,
        Models\Ticket::class => Policies\TicketPolicy::class,
        Models\User::class => Policies\UserPolicy::class,
        Models\RedirectInstruction::class => Policies\RedirectInstructionPolicy::class,

        // Devices API
        Models\Webcam\Camera::class => Policies\Webcam\CameraPolicy::class,
        Models\Webcam\Device::class => Policies\Webcam\DevicePolicy::class,

        // Shop
        ShopModels\Order::class => ShopPolicies\OrderPolicy::class,
        ShopModels\Category::class => ShopPolicies\CategoryPolicy::class,
        ShopModels\Product::class => ShopPolicies\ProductPolicy::class,
        ShopModels\ProductVariant::class => ShopPolicies\ProductVariantPolicy::class,

        // Gallery
        Models\Gallery\Album::class => Policies\Gallery\AlbumPolicy::class,
        Models\Gallery\Photo::class => Policies\Gallery\PhotoPolicy::class,
        Models\Gallery\PhotoReport::class => Policies\Gallery\PhotoReportPolicy::class,

        // Payments
        Models\Payments\Settlement::class => Policies\Payments\SettlementPolicy::class,

        // Google Wallet
        Models\GoogleWallet\EventClass::class => Policies\GoogleWallet\EventClassPolicy::class,
        Models\GoogleWallet\EventObject::class => Policies\GoogleWallet\EventObjectPolicy::class,

        // Content
        Models\Content\MailTemplate::class => Policies\Content\MailTemplatePolicy::class,

        // Spatie Permissions (both external and local)
        SpatiePermissionModels\Permission::class => Policies\Permissions\PermissionPolicy::class,
        SpatiePermissionModels\Role::class => Policies\Permissions\RolePolicy::class,
        Models\Role::class => Policies\Permissions\RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(GateContract $gate)
    {
        // Bind policies
        $this->registerPolicies();

        // Bind admin
        Gate::define('enter-admin', 'App\Gates\AdminGate@nova');

        // Super Admin mode
        $gate->before([$this, 'hasDirectAdminPermission']);
    }

    /**
     * Check if a user has permission to be super admin. Cannot be inherited from roles.
     *
     * @param User $user User to authenticate
     */
    public function hasDirectAdminPermission(User $user): ?bool
    {
        // Check for direct super-admin permission
        if ($user->hasDirectPermission('super-admin')) {
            return true;
        }

        // Return null otherwise
        return null;
    }
}
