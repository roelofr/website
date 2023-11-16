<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\MailTemplateMessage;
use App\Models\Activity;
use App\Models\Content\MailTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MailTemplateService
{
    /**
     * Send a mail belonging to the given model using the given label and attributes.
     * @param Model $model Model to use to check for overrides (WIP)
     * @param string $label Label to find the message by
     * @param array $params Mail parameters
     * @internal You're likely looking for find<Model>Template
     */
    public function findTemplate(Model $model, string $label, array $params): MailTemplateMessage
    {
        $template = MailTemplate::firstOrFail(['label' => $label]);

        return new MailTemplateMessage($template, $params);
    }

    /**
     * Send a message for the given activity, with the given label.
     */
    public function findActivityTemplate(Activity $activity, string $label, User $user): MailTemplateMessage
    {
        return $this->findTemplate($activity, $label, [
            'activity' => $activity->name,
            'first_name' => $user->first_name,
            'name' => $user->name,
            'start_date' => $activity->start_date->isoFormat('D MMMM YYYY'),
            'start_time' => $activity->start_date->isoFormat('H:MM'),
            'activity_link' => URL::to('activity.show', $activity),
            'enrollment_link' => URL::to('enroll.show', $activity),
            'host' => $activity->role->title,
        ]);
    }
}
