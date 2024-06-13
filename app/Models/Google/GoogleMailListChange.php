<?php

declare(strict_types=1);

namespace App\Models\Google;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

/**
 * App\Models\Google\GoogleMailListChange.
 *
 * @property int $id
 * @property int $google_mail_list_id
 * @property string $action
 * @property array $data
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property null|\Illuminate\Support\Carbon $started_at
 * @property null|\Illuminate\Support\Carbon $finished_at
 * @property-read null|\App\Models\Google\GoogleMailList $mailList
 * @method static \Database\Factories\Google\GoogleMailListChangeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailListChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailListChange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailListChange query()
 * @mixin \Eloquent
 */
class GoogleMailListChange extends Model
{
    use HasFactory;

    protected $casts = [
        'data' => 'encrypted:array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $fillable = [
        'google_mail_list_id',
        'action',
        'started_at',
        'finished_at',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (GoogleMailListChange $change) {
            $change->data ??= [];
        });
    }

    public function mailList(): BelongsTo
    {
        return $this->belongsTo(GoogleMailList::class);
    }

    public function addChange(string $type, string $action, string $value): self
    {
        if (! $this->started_at) {
            $this->update(['started_at' => Date::now()]);
        }

        $data = $this->data ?? [];
        $data[] = [
            'index' => count($data),
            'time' => Date::now(),
            'type' => $type,
            'action' => $action,
            'value' => $value,
        ];
        $this->data = $data;

        return $this;
    }
}
