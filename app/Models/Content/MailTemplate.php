<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Casts\Content\MailTemplateParams;
use App\Models\Traits\HasResponsibleUsers;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A template of a message, which cannot be customized and is
 * sourced from the Markdown files in resources/markdown/mails.
 *
 * @property int $id
 * @property string $label
 * @property string $subject
 * @property string $body
 * @property null|string $footnote
 * @property Collection $params
 * @property null|int $created_by_id
 * @property null|int $updated_by_id
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 * @property-read null|User $createdBy
 * @property-read null|User $updatedBy
 * @method static \Database\Factories\Content\MailTemplateFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|MailTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MailTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MailTemplate query()
 * @mixin \Eloquent
 */
class MailTemplate extends Model
{
    use HasFactory;
    use HasResponsibleUsers;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'params' => '{}',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'params' => MailTemplateParams::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'subject',
        'body',
        'params',
    ];
}
