<?php

namespace App\Models;

use App\Enums\OTP\OTP;
use App\Notifications\NewPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['profile'];

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'uuid';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'avatar_id',
        'autocalibration',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the parent userable model (citizen profile, client profile, etc).
     */
    public function profile(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'profile_type', 'profile_id');
    }

    /**
     * Get user´s profile type
     */
    public function getProfileTypeAttribute(): string
    {
        return $this->profile->getMorphClass();
    }

    public function activeOtp(OTP $type)
    {
        return $this->otp()->where('expire_at', '>', Carbon::now())->where('is_used', false)->where('type', $type)->first();
    }

    public function otp(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function sendEmailOtpNotification(VerificationCode $otp): void
    {
        $this->notify(new \App\Notifications\Otp($otp));
    }

    public function sendNewPasswordNotification($newPassword): void
    {
        $this->notify(new NewPassword($newPassword));
    }

    /**
     * Sigo el código de Qualud para calcular el nivel del usuario para tanto hacerlo rápido
     * como para que sea igual a como se calcula en la app y no haya incoherencias.
     * https://github.com/ScienceForChange/SoundCollect_frontend/blob/2232722258f77046d48cd94ca5f3930bbfabba56/src/app/services/user-service.ts#L71
     */
    public function calculatedLevel()
    {
        $points = 0;
        $sameDayExtraPoints = ['day' => '1970-01-01', 'used' => false];

        $observations = $this->observations()->get()->toArray();

            foreach ($observations as $observation) {
                if ( count((array) $observation['images']) >= 1 ) {
                    $points += 2;
                } else {
                    $points += 1;
                }

                $date1 = Carbon::parse($sameDayExtraPoints['day']);
                $date2 = $observation['created_at'];

                if ( $date1->isSameDay($date2) ) {
                    if (! $sameDayExtraPoints['used'] ) {
                        $points += 3;
                        $sameDayExtraPoints['used'] = true;
                    }
                } else {
                    $sameDayExtraPoints = ['day' => $observation['created_at'], 'used' => false];
                }
        }

        // TODO: cambiar esto por un evento o al menos quitar la lógica de aquí pero de momento aquí se quda.
        if( (!$this->is_expert) && $points >= 21 ) $this->update(['is_expert', true]);

        return $points;
    }
}
