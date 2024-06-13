<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Conscribo\ConscriboUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Updates the user's roles by asking the Conscribo API.
 */
class UpdateConscriboUserJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The user we're updating.
     */
    protected User $user;

    public static function getRolesForConscriboUser(ConscriboUser $conscriboUser): iterable
    {
        $roles = Collection::make();

        $conscriboUser = $conscriboUser->loadMissing([
            'committees:id,name', 'committees.roles:id,name',
            'groups:id,name', 'groups.roles:id,name',
        ]);

        foreach ($conscriboUser->committees as $committee) {
            $roles->push($committee->roles->pluck('name'));
        }

        foreach ($conscriboUser->groups as $group) {
            $roles->push($group->roles->pluck('name'));
        }

        return $roles->collapse()->sort()->values();
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get user
        $user = $this->user;

        // Skip if user e-mail is not verified
        if (! $user->hasVerifiedEmail()) {
            Log::info('A role update job was started, but this user is not verified');

            return;
        }

        // Find the correct user
        $conscriboUser = ConscriboUser::firstWhere('email', $user->email);
        if (! $conscriboUser && $user->conscriboUser === null) {
            Log::info('User {email} not found in Conscribo. Not changed.', ['email' => $user->email]);

            return;
        }

        if (! $conscriboUser && $user->conscriboUser) {
            DB::transaction(fn () => $this->detachUser($user));

            return;
        }

        $user->conscriboUser()->associate($conscriboUser);
        $user->conscribo_id = $conscriboUser->conscribo_id;
        $user->save();

        DB::transaction(fn () => $this->attachOrUpdateUser($user, $conscriboUser));
    }

    private function detachUser(User $user)
    {
        $currentConscriboRoles = self::getRolesForConscriboUser($user->conscriboUser);

        $user->conscriboUser()->dissociate();
        $user->conscribo_id = null;
        $user->save();

        $conscriboRoleIds = Role::query()->whereIn('name', $currentConscriboRoles)->pluck('id');
        $user->roles()->detach($conscriboRoleIds);

        Log::notice('User {email} has been detached from Conscribo. Removed roles: {roles}', [
            'email' => $user->email,
            'roles' => $currentConscriboRoles->values(),
        ]);
    }

    private function attachOrUpdateUser(User $user, ConscriboUser $conscriboUser)
    {
        $user->conscriboUser()->associate($conscriboUser);
        $user->conscribo_id = $conscriboUser->conscribo_id;
        $user->save();

        $conscriboRoles = self::getRolesForConscriboUser($conscriboUser);
        $user->assignRole($conscriboRoles);

        Log::notice('User {email} has been attached to Conscribo via {conscribo_id}. Assgined roles: {roles}', [
            'email' => $user->email,
            'conscribo_id' => $user->conscribo_id,
            'roles' => $conscriboRoles->values(),
        ]);
    }
}
