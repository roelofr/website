<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use Notifiable, HasRoles, SoftDeletes, MustVerifyEmail;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'insert',
        'last_name',
        'email',
        'password',
        'alias',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'email_verified_at',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'insert' => null,
        'alias' => null,
    ];

    /**
     * A user might've uploaded files
     *
     * @return HasMany
     */
    public function files() : HasMany
    {
        return $this->hasMany(File::class, 'owner_id');
    }

    /**
     * A user can download files
     *
     * @return BelongsToMany
     */
    public function downloads() : BelongsToMany
    {
        return $this->belongsToMany(File::class, 'file_downloads')
            ->as('download')
            ->using(FileDownload::class);
    }

    /**
     * Returns full name of the user
     *
     * @return string|null
     */
    public function getNameAttribute() : ?string
    {
        $name = collect([
            $this->first_name,
            $this->insert,
            $this->last_name
        ])->filer()->implode(' ');

        return $name !== '' ? $name : null;
    }
}
