<?php

declare(strict_types=1);

namespace App\Policies\Content;

use App\Models\Content\MailTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MailTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('content-admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MailTemplate $mailTemplate): bool
    {
        return $user->hasPermissionTo('content-admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MailTemplate $mailTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MailTemplate $mailTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MailTemplate $mailTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MailTemplate $mailTemplate): bool
    {
        return false;
    }
}
