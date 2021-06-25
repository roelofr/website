<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A mail that will be sent at a later date, or has been
 * sent.
 *
 * @property int $id
 * @property string $group
 * @property string $name
 * @property \Illuminate\Support\Date $scheduled_for
 * @property null|\Illuminate\Support\Date $sent_at
 * @property bool $is_sent
 */
class ScheduledMail extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'group' => 'string',
        'name' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'scheduled_for',
        'sent_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group',
        'name',
        'scheduled_for',
        'sent_at',
    ];

    /**
     * Returns a ScheduledMail with the given name.
     *
     * @return ScheduledMail
     */
    public static function findForModelMail(Model $model, string $name): self
    {
        $objectName = sprintf('%s:%s', \class_basename($model), $model->{$model->primaryKey});

        return static::firstOrNew([
            'group' => $objectName,
            'name' => Str::slug($name),
        ]);
    }

    public function scopeWhereSent(Builder $query): Builder
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeWhereNotSent(Builder $query): Builder
    {
        return $query->whereNull('sent_at');
    }

    public function getIsSentAttribute(): bool
    {
        return $this->sent_at !== null;
    }
}
