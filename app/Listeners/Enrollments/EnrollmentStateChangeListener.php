<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EnrollmentCancellationReason;
use App\Models\Enrollment;
use App\Models\States\Enrollment as States;
use App\Services\MailTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Spatie\ModelStates\Events\StateChanged;

class EnrollmentStateChangeListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(private readonly MailTemplateService $templateService)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(StateChanged $event): void
    {
        // Don't act on enrollments
        if (! $event->model instanceof Enrollment) {
            return;
        }

        // Don't re-run events when we've already seen them.
        if ($event->initialState && get_class($event->initialState) === get_class($event->initialState)) {
            return;
        }

        // Get shorthand
        $enrollment = $event->model;
        $finalState = $enrollment->state;
        $user = $enrollment->user;

        $mailTemplate = null;

        // Handle payment completion
        if ($finalState instanceof States\Paid) {
            $this->sendTemplateMail($enrollment, 'enrollment-paid');

            return;
        }

        // Handle non-payment confirmation (Paid extends Confirmed)
        if ($finalState instanceof States\Confirmed) {
            $this->sendTemplateMail($enrollment, 'enrollment-confirmed');

            return;
        }

        // Handle cancellation
        if ($finalState instanceof States\Cancelled) {
            $this->sendTemplateMail($enrollment, match ($enrollment->deleted_reason) {
                EnrollmentCancellationReason::TIMEOUT => 'enrollment-cancelled-timeout',
                EnrollmentCancellationReason::ADMIN => 'enrollment-cancelled-removed',
                default => 'enrollment-cancelled',
            });

            return;
        }
    }

    /**
     * Sends the requested template, without queueing it any further.
     */
    private function sendTemplateMail(Enrollment $enrollment, string $template): void
    {
        $template = $this->templateService->findActivityTemplate(
            $enrollment->activity,
            $template,
            $enrollment->user,
        );

        Mail::to($enrollment->user)
            ->send($template);
    }
}
