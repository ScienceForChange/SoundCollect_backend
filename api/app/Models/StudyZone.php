<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyZone extends Model
{
    use HasFactory, HasSpatial, SoftDeletes;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'conclusion',
        'coordinates',
        'start_date',
        'end_date',
        'deleted'
    ];


    protected $casts = [
        'coordinates' => Polygon::class
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
