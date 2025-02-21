<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    public $incrementing = false;
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'location',
        'password',
        'admin',
        'last_login_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'admin',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime'
    ];

    /**
     * A user has one social login.
     */
    public function socialiteLogin(): BelongsTo
    {
        return $this->belongsTo(SociaLogin::class, 'user_id', 'id');
    }

    /**
     * A user has many time tracking entries.
     */
    public function timeTrackings()
    {
        return $this->hasMany(MondayTimeTracking::class, 'user_id', 'id');
    }

    public function assignedItems()
    {
        return $this->belongsToMany(MondayItem::class, 'monday_assignments', 'user_id', 'item_id');
    }

    /**
     * A user has many time tracking entries.
     */
    public function schedules()
    {
        return $this->hasMany(UserSchedule::class, 'user_id', 'id');
    }
}
