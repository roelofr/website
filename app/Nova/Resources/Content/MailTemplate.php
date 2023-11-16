<?php

declare(strict_types=1);

namespace App\Nova\Resources\Content;

use App\Nova\Resources\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields;

class MailTemplate extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\Models\Content\MailTemplate';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'subject';

    /**
     * Name of the group.
     *
     * @var string
     */
    public static $group = 'Bestuurszaken';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'label',
        'subject',
    ];

    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function fields(Request $request)
    {
        return [
            Fields\ID::make()->sortable(),

            Fields\Text::make('Label', 'label'),

            Fields\Text::make('Onderwerp', 'subject'),

            Fields\Markdown::make('Bericht template', 'template'),
        ];
    }
}
