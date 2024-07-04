<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Observation extends Model
{
    use HasFactory, SoftDeletes, HasUuids, HasSpatial;

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
        'user_id',

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
        'coordinates',
        'type',

        'quiet',
        'cleanliness',
        'accessibility',
        'safety',
        'influence',
        'landmark',
        'protection',

        'temperature',
        'pressure',
        'humidity',
        'wind_speed',

        'pleasant',
        'chaotic',
        'vibrant',
        'uneventful',
        'calm',
        'annoying',
        'eventfull',
        'monotonous',
        'overall',

        'path',
    ];

    protected $casts = [
        'Leq' => 'array', //
        'images' => 'array',
        'LAeqT' => 'array',
        'coordinates' => LineString::class,
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
