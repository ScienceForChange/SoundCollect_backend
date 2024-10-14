<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Observation extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

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

    protected $fillable = [
        'Leq',
        'LAeqT',
        'LAmax',
        'LAmin',
        'L90',
        'L10',
        'sharpness_S',
        'loudness_N',
        'roughtness_R',
        'fluctuation_strength_F',
        'images',
        'latitude',
        'longitude',
        'quiet',
        'cleanliness',
        'accessibility',
        'safety',
        'influence',
        'landmark',
        'protection',
        'wind_speed',
        'humidity',
        'temperature',
        'pressure',
        'pleasant',
        'chaotic',
        'vibrant',
        'uneventful',
        'calm',
        'annoying',
        'eventful',
        'monotonous',
        'overall',
        'user_id',
        'path',
        'user_local_time',
    ];

    protected $casts = [
        'images' => 'array',
        'LAeqT' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sounds attatched with the observation.
     */
    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Type::class);
    }

    //add segment observation relationship
    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class);
    }
}
