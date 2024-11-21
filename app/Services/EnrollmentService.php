<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EnrollmentServiceContract;
use App\Enums\Models\BarcodeType;
use App\Exceptions\EnrollmentFailedException;
use App\Helpers\Str;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\States\Enrollment as States;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\EnrollmentTransferred;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use LogicException;
use RuntimeException;

class EnrollmentService implements EnrollmentServiceContract
{
    public function __construct(private BarcodeService $barcodeService)
    {
        // intentionally left blank
    }

    public function getEnrollment(User $user, Activity $activity): ?Enrollment
    {
        return $user
            ->enrollments()
            ->whereNotState('state', [
                States\Cancelled::class,
            ])
            ->whereHas('activity', fn ($query) => $query->where('id', $activity->id))
            ->first();
    }

    public function canEnroll(User $user, Activity $activity): bool
    {
        // Check if the user is already enrolled
        if ($this->getEnrollment($user, $activity) !== null) {
            return false;
        }

        // Check if the activity has ended
        if ($activity->end_date < Date::now()) {
            return false;
        }

        // Check if there are any slots left
        if ($activity->available_seats === 0) {
            return false;
        }

        // Check if enrollments of the activity are open
        if (! $activity->enrollment_open) {
            return false;
        }

        // Check if there are any tickets left that this user can buy
        $tickets = $this->findTicketsForActivity($user, $activity);
        if ($tickets->isEmpty()) {
            return false;
        }

        // Should be fine at this point
        return true;
    }

    public function findTicketsForActivity(User $user, Activity $activity): Collection
    {
        $activity->loadMissing('tickets');

        /** @var Ticket $ticket */
        return $activity->tickets
            ->filter->isAvailableFor($user)
            ->values();
    }

    public function createEnrollment(User $user, Activity $activity, Ticket $ticket): Enrollment
    {
        throw_unless($user = Auth::user(), new LogicException('There is no user logged in'));

        throw_if($this->getEnrollment($user, $activity), new EnrollmentFailedException("You're already enrolled for this activity"));

        throw_unless($ticket->isAvailableFor($user), new EnrollmentFailedException('This ticket is not available'));

        $ticket->refresh();

        throw_if($ticket->quantity_available === 0, new EnrollmentFailedException('This ticket is sold out'));

        throw_if($ticket->activity->refresh()->available_seats === 0, new EnrollmentFailedException('This activity is sold out'));

        throw_unless($this->canEnroll($user, $activity), new EnrollmentFailedException('You cannot enroll for this activity'));

        /** @var Enrollment $enrollment */
        $enrollment = $activity->enrollments()->make();
        $enrollment->ticket()->associate($ticket);
        $enrollment->user()->associate($user);

        // Assign price
        $enrollment->price = $ticket->price;
        $enrollment->total_price = $ticket->total_price;

        // Assign expiration
        $expirationPeriod = $user == null ? Config::get('gumbo.tickets.expiration.anonymous') : Config::get('gumbo.tickets.expiration.authenticated');
        $enrollment->expire = Date::now()->add($expirationPeriod);
        $enrollment->save();

        return $enrollment;
    }

    public function canTransfer(User $user, Enrollment $enrollment): bool
    {
        if ($enrollment->state instanceof States\Cancelled || $enrollment->trashed() || $enrollment->consumed()) {
            return false;
        }

        $activity = $enrollment->activity;
        if ($activity->start_date < Date::now()) {
            return false;
        }

        return true;
    }

    public function transferEnrollment(User $user, Enrollment $enrollment, User $reciever): Enrollment
    {
        // Get current
        $giver = $enrollment->user;

        // Sanity check
        if ($reciever->is($giver)) {
            throw new LogicException('Cannot transfer an enrollment to the same user');
        }

        // Transfer enrollment
        $enrollment->user()->associate($reciever);

        // Check expire, making sure it's at least 12 hours
        if (! $enrollment->is_stable) {
            $enrollment->expire = max($enrollment->expire, now()->addHours(12));
        }

        // Make sure transfer token is unset
        $enrollment->transfer_secret = null;

        // Wipe form data if it's sensitive
        if ($enrollment->is_form_exportable === false) {
            $enrollment->form = [];
        }

        // Save changes
        $enrollment->save();

        // Send mails
        $giver->notify(new EnrollmentTransferred($enrollment, $giver));
        $reciever->notify(new EnrollmentTransferred($enrollment, $giver));

        // Return it
        return $enrollment;
    }

    /**
     * Generates a new unique barcode code for the enrollment.
     */
    public function updateBarcode(User $user, Enrollment $enrollment): void
    {
        if ($enrollment->barcode_generated === false) {
            return;
        }

        // Try to generate a new code 4 times
        for ($i = 0; $i < 4; $i++) {
            $triedCode = sprintf('%03X%05X%s', $enrollment->activity_id, $enrollment->id, Str::upper(Str::random(12)));

            if (! Enrollment::withoutGlobalScopes()->where('barcode', $triedCode)->exists()) {
                $enrollment->barcode = sprintf('%03X%05X%s', $enrollment->activity_id, $enrollment->id, Str::upper(Str::random(12)));
                $enrollment->barcode_type = BarcodeType::QRCODE;
                $enrollment->save();

                return;
            }
        }

        throw new RuntimeException('Could not generate a unique ticket code');
    }

    public function getBarcodeImage(User $user, Enrollment $enrollment, int $size = 128): string
    {
        return App::make(BarcodeService::class)->toBase64($enrollment->barcode_type ?? BarcodeType::QRCODE, $enrollment->barcode, $size);
    }
}
