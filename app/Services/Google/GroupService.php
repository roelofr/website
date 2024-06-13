<?php

declare(strict_types=1);

namespace App\Services\Google;

use App\Models\Google\GoogleMailList;
use Closure;
use Google\Service\Directory\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class GroupService
{
    public function __construct(
        private \Google\Service\Directory $directoryService
    ) {
        //
    }

    /**
     * Finds a Google Group based on either the directory_id,
     * email, or any of the aliases.
     */
    public function find(GoogleMailList $mailList): ?Group
    {
        $groups = $this->directoryService->groups;

        $searchParameters = Collection::make()
            ->push($mailList->directory_id)
            ->push($mailList->email)
            ->merge($mailList->aliases)
            ->filter();

        foreach ($searchParameters as $searchKey) {
            /** @var Group $found */
            $found = $this->handleFind(fn () => $groups->get($searchKey));

            if ($found) {
                return $found;
            }

            if ($searchKey === $mailList->directory_id) {
                Log::warning('Directory ID {directory_id} for list {id} is missing in Google!', [
                    'id' => $mailList->id,
                    'email' => $mailList->email,
                    'directory_id' => $mailList->directory_id,
                ]);
            }
        }

        return null;
    }

    /**
     * Finds the group for the mail list by the directory_id.
     */
    public function get(GoogleMailList $mailList): Group
    {
        $groups = $this->directoryService->groups;

        if (! $mailList->directory_id) {
            throw new InvalidArgumentException('Mail list is missing a directory ID!');
        }

        Log::debug('Looking up Google Group for model {id} with Directory ID {directory_id}', [
            'id' => $mailList->id,
            'directory_id' => $mailList->directory_id,
        ]);

        $found = $this->handleFind(fn () => $groups->get($mailList->directory_id));

        if ($found) {
            return $found;
        }

        Log::warning('Directory ID {directory_id} for list {id} is missing in Google!', [
            'id' => $mailList->id,
            'email' => $mailList->email,
            'directory_id' => $mailList->directory_id,
        ]);

        throw new RuntimeException("Failed to find Google Group for model {$mailList->id} with Directory ID {$mailList->directory_id}!");
    }

    private function handleFind(Closure $callable)
    {
        try {
            return $callable();
        } catch (\Google\Service\Exception $googleException) {
            if ($googleException->getCode() != 404) {
                throw new RuntimeException($googleException->getMessage(), 0, $googleException);
            }
        }

        return null;
    }
}
