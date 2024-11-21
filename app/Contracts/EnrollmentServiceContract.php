<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\EnrollmentFailedException;
use App\Facades\Enroll;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

interface EnrollmentServiceContract
{
    /**
     * Returns the current active enrollment for this activity for the current
     * user. If no user is logged in, returns null.
     */
    public function getEnrollment(User $user, Activity $activity): ?Enrollment;

    /**
     * Returns a list of tickets available for the current user for the given
     * activity.  If no user is logged in, a non-member user will be assumed.
     *
     * @return Collection<Ticket>
     */
    public function findTicketsForActivity(User $user, Activity $activity): Collection;

    /**
     * Checks if the user can enroll in this activity.  If no user is logged
     * in, a non-member user will be assumed.
     */
    public function canEnroll(User $user, Activity $activity): bool;

    /**
     * Enroll the user into the activity. Throw an error if it fails.
     * @throws EnrollmentFailedException
     */
    public function createEnrollment(User $user, Activity $activity, Ticket $ticket): Enrollment;

    /**
     * Checks if the user can transfer their enrollment to this activity.
     */
    public function canTransfer(User $user, Enrollment $enrollment): bool;

    /**
     * Transfers an enrollment to the new user, sending proper mails and
     * invoicing jobs.
     */
    public function transferEnrollment(User $user, Enrollment $enrollment, User $reciever): Enrollment;

    /**
     * Generates a new unique barcode code for the enrollment.
     * Should not run if the enrollment has a custom barcode.
     */
    public function updateBarcode(User $user, Enrollment $enrollment): void;

    /**
     * Generates an image for the barcode of the given enrollment, to be printed on the ticket.
     *
     * @param Enrollment $enrollment The enrollment to generate the image for
     * @param int $size the size of the image, if a vector image is created
     * @return string The barcode as a base64 encoded image
     */
    public function getBarcodeImage(User $user, Enrollment $enrollment, int $size = 128): string;
}
