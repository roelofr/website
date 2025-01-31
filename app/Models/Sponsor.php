<?php

declare(strict_types=1);

namespace App\Models;

use Advoor\NovaEditorJs\NovaEditorJsCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/**
 * App\Models\Sponsor.
 *
 * @property int $id
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property null|\Illuminate\Support\Carbon $deleted_at
 * @property string $name Sponsor name
 * @property string $slug
 * @property null|string $cover
 * @property string $url URL of sponsor landing page
 * @property null|\Illuminate\Support\Carbon $starts_at
 * @property null|\Illuminate\Support\Carbon $ends_at
 * @property null|int $has_page
 * @property int $view_count Number of showings
 * @property null|string $caption
 * @property null|string $logo_gray
 * @property null|string $logo_color
 * @property null|string $contents_title
 * @property null|\Advoor\NovaEditorJs\NovaEditorJsData $contents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SponsorClick> $clicks
 * @property-read mixed $click_count
 * @property-read null|\Illuminate\Support\HtmlString $content_html
 * @property-read bool $is_active
 * @property-read bool $is_classic
 * @property-read null|string $logo_color_url
 * @property-read null|string $logo_gray_url
 * @method static \Database\Factories\SponsorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SluggableModel findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static Builder|Sponsor newModelQuery()
 * @method static Builder|Sponsor newQuery()
 * @method static Builder|Sponsor onlyTrashed()
 * @method static Builder|Sponsor query()
 * @method static Builder|Sponsor whereAvailable()
 * @method static \Illuminate\Database\Eloquent\Builder|SluggableModel whereSlug(string $slug)
 * @method static Builder|Sponsor withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|SluggableModel withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @method static Builder|Sponsor withoutTrashed()
 * @mixin \Eloquent
 */
class Sponsor extends SluggableModel
{
    use HasFactory;
    use SoftDeletes;

    public const LOGO_DISK = 'public';

    public const LOGO_PATH = 'sponsors/logos';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'view_count' => 'int',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'contents' => NovaEditorJsCast::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'url',
        'start_at',
        'ends_at',
        'caption',
        'logo_gray',
        'logo_color',
        'contents',
    ];

    /**
     * Generate the slug based on the display_title property.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'unique' => true,
                'onUpdate' => false,
            ],
        ];
    }

    /**
     * Returns sponsors that are available right now.
     */
    public function scopeWhereAvailable(Builder $builder): Builder
    {
        return $builder
            // Require logos
            ->whereNotNull('logo_color')
            ->whereNotNull('logo_gray')

            // Require an URL to be set
            ->whereNotNull('url')

            // Require to have started, and not ended yet
            ->where('starts_at', '<', now())
            ->where(static function ($query) {
                $query->where('ends_at', '>', now())
                    ->orWhereNull('ends_at');
            });
    }

    /**
     * Returns if this should be a classic view.
     */
    public function getIsClassicAttribute(): bool
    {
        return ! $this->cover || ! $this->caption;
    }

    /**
     * Returns if this sponsor is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->starts_at < now()
            && ($this->ends_at === null || $this->ends_at > now());
    }

    /**
     * Returns URL to the grayscale (currentColor) logo.
     *
     * @throws InvalidArgumentException
     */
    public function getLogoGrayUrlAttribute(): ?string
    {
        if (! $this->logo_gray) {
            return null;
        }

        return Storage::disk(self::LOGO_DISK)->url($this->logo_gray);
    }

    /**
     * Returns URL to the full color logo.
     *
     * @throws InvalidArgumentException
     */
    public function getLogoColorUrlAttribute(): ?string
    {
        if (! $this->logo_color) {
            return null;
        }

        return Storage::disk(self::LOGO_DISK)->url($this->logo_color);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(SponsorClick::class);
    }

    /**
     * Returns the number of clicks.
     */
    public function getClickCountAttribute()
    {
        return $this->clicks()->sum('count');
    }

    /**
     * Converts contents to HTML.
     */
    public function getContentHtmlAttribute(): ?HtmlString
    {
        return $this->contents?->toHtml();
    }
}
