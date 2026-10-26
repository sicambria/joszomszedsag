<?php

namespace App;

use App\Notifications\ResetPassword as ResetPassword;
use App\Notifications\VerifyEmail;
use App\Support\RoleIds;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    public $user_roles;

    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'first_name', 'last_name', 'display_name', 'email', 'phone_number',
        'password', 'avatar', 'settings', 'date_birth', 'description',
        'facebook_profile', 'facebook_group', 'role_id', 'home_address_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // Cache roles per-request: the original Role::all() on every
        // instantiation caused N+1 queries and broke artisan when DB was down.
        static $cached = null;
        if ($cached === null) {
            try {
                $cached = Role::all();
            } catch (\Throwable $e) {
                $cached = collect();
            }
        }
        foreach ($cached as $role) {
            $this->user_roles[$role->id] = $role->name;
        }
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isAdmin(): bool
    {
        return (int) $this->role_id === RoleIds::ADMIN;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail());
    }

    public function canOpen()
    {
        // @todo: dead code — original body was a no-op query. Kept intentionally
        // side-effect free until open-request visibility rules are specified.
        return false;
    }

    public function getVolunteerDataByAddress()
    {
        //$volunteer_data = App\UserVolunteerData::where('user_id' => )
    }

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($model) {
            $old = self::find($model->id);

            logModelChange($old->attributes, $model->attributes, $model->id, 'Felhasználó: ');
        });

        static::created(function ($model) {
            saveLog($model->id, 'Új '.$model->role_id.' regisztrálva '.$model->email.' emaillel');
        });
    }
}
