<?php

namespace App\Nova\Resources;

use App\Models\File as FileModel;
use Benjaminhirsch\NovaSlugField\Slug;
use Benjaminhirsch\NovaSlugField\TextWithSlug;
use DanielDeWit\NovaPaperclip\PaperclipFile;
use DanielDeWit\NovaPaperclip\PaperclipImage;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Panel;

class File extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = FileModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'title',
        'slug',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),

            // Title and slug
            TextWithSlug::make('Title', 'title')
                ->slug('slug')
                ->rules('required', 'min:4')
                ->help('File title, does not need to be a filename'),
            Slug::make('Slug', 'slug')
                ->nullable(false),

            // Add multi selects
            BelongsTo::make('Uploaded by', 'owner_id', User::class)
                ->onlyOnDetail(),

            // Show timestamps
            DateTime::make('Created at', 'created_at')->onlyOnDetail(),
            DateTime::make('Updated at', 'created_at')->onlyOnDetail(),

            new Panel('File information', [
                // Paperclip file
                PaperclipFile::make('File', 'file')
                    ->mimes(['pdf'])
                    ->rules('required', 'mimes:pdf')
                    ->deletable(false)
                    ->readonly(function () {
                        return $this->exists && $this->file;
                    })
                    ->help('File the users will download, immutable once set'),

                // Thumbnail
                PaperclipImage::make('Thumbnail', 'thumbnail')
                    ->mimes(['png', 'jpg'])
                    ->rules('mimes:png,jpg')
                    ->deletable(true)
                    ->help('Screenshot, will be auto-generated if missing'),
            ]),

            new Panel('File Settings', [
                Boolean::make('Pulled or superseded', 'pulled')
                    ->help('Indicates the file has been replaced by a different version, or is no longer applicable')
                    ->rules('required_with:replacement'),
                BelongsTo::make('Replacement file', 'replacement', File::class)
                    ->help('If pulled, indicates which file replaces it')
                    ->nullable(),
            ]),

            new Panel('File metadata', [
                // Make extra data
                Text::make('Contents', 'contents')
                    ->onlyOnDetail(),
                Number::make('Page count', 'pages')
                    ->onlyOnDetail(),
                Code::make('Metadata', 'file_meta')
                    ->json()
                    ->onlyOnDetail(),
            ])
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
