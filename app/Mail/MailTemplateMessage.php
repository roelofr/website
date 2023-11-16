<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Content\MailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MailTemplateMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public readonly MailTemplate $mailTemplate;

    public readonly Collection $params;

    private readonly string $mailParamSeparator;

    /**
     * @param MailTemplate $mailTemplate template to render
     * @param iterable<string,mixed> $params template parameters
     */
    public function __construct(MailTemplate $mailTemplate, iterable $params)
    {
        $params = Collection::make($params);

        // Validate if all mailTemplate params are supplied
        $mailTemplateParams = $mailTemplate->params->pluck('name');

        // Diff the parameters, throwing a fit if some are missing.
        $missingParams = $mailTemplateParams->diff($params->keys());

        if ($missingParams->isNotEmpty()) {
            Log::error('A MailTemplateMessage was created for {template} with params {params}, but {missing-params} is not given.', [
                'template' => $mailTemplate,
                'params' => $params,
                'missing-params' => $missingParams,
            ]);

            // Add missing params
            $params = $missingParams
                ->mapWithKeys(fn ($val) => [$val => null])
                ->merge($params);

            // If debug is enabled, throw the error
            $exception = new InvalidArgumentException("Mail template [{$mailTemplate->label}] is missing parameters [{$missingParams->join(', ')}].");
            report($exception);

            if (App::hasDebugModeEnabled()) {
                throw $exception;
            }
        }

        // Assign to model
        $this->params = $params;
        $this->mailTemplate = $mailTemplate;
        $this->mailParamSeparator = Str::snake((string) Str::uuid());
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mailTemplate = $this->mailTemplate;
        $mailParams = $this->params;

        // Prep data
        $templateParams = $mailTemplate->params->pluck('name');
        $templateBody = $mailTemplate->body;

        // Determine parameter values
        $paramsSource = $templateParams->map(fn (string $name) => "{{$name}}")->all();
        $paramsIntermediary = $templateParams->map(fn (string $name) => "{$this->mailParamSeparator}_{$name}")->all();
        $paramsDest = $templateParams->map(fn (string $name) => $mailParams[$name])->all();

        // Compute the values
        $preparedSubject = str_replace($paramsSource, $paramsDest, $mailTemplate->subject);
        $placeholderBody = str_replace($paramsSource, $paramsIntermediary, $mailTemplate->body);
        $placeholderFootnote = $mailTemplate->footnote ? str_replace($paramsSource, $paramsIntermediary, $mailTemplate->footnote) : null;

        // Set subject with replacements
        $this->subject($preparedSubject);

        // Render all Markdown views
        $this->markdown('mail::from-template', [
            // Always supply the template
            'template' => $mailTemplate,

            // Prep a body with placeholders
            'body' => $placeholderBody,

            // Prep the footnote
            'footnote' => $placeholderFootnote,
        ]);
    }

    protected function buildMarkdownView()
    {
        // Load params again
        $mailTemplate = $this->mailTemplate;
        $mailParams = $this->params;
        $templateParams = $mailTemplate->params->pluck('name');

        // Prep replacements
        $intermediateKeys = $templateParams->map(fn (string $name) => "{$this->mailParamSeparator}_{$name}")->all();
        $actualKeys = $templateParams->map(fn (string $name) => e($mailParams[$name]))->all();

        // Let the parent build the view
        $data = parent::buildMarkdownView();

        // Replace HTML and text with actual values
        return [
            'html' => new HtmlString(str_replace($intermediateKeys, $actualKeys, (string) $data['html'])),
            'text' => new HtmlString(str_replace($intermediateKeys, $actualKeys, (string) $data['text'])),
        ];
    }
}
