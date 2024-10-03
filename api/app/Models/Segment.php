<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Segment extends Model
{

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'observation_id',
        'position',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'L90',
        'L10',
        'LAmax',
        'LAmin',
        'LAeq',
        'LAeqT',
        'freq_3',
        'spec_3',
        'spec_3_dB',
        'fluctuation',
        'sharpness',
        'loudness', 
        'roughness',
        'spec_3_dBC',
    ];

    protected $casts = [
        'LAeqT' => 'array',
        'freq_3' => 'array',
        'spec_3' => 'array',
        'spec_3_dB' => 'array',
        'spec_3_dBC' => 'array'
    ];

    // segments have hasmany relationship with observations
    public function observation()
    {
        return $this->belongsTo(Observation::class);
    }
}
