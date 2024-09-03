<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudyZone extends Model
{
    use HasFactory, HasSpatial, SoftDeletes;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'description',
        'conclusion',
        'coordinates',
        'start_date',
        'end_date',
        'is_visible',
    ];


    protected $casts = [
        'coordinates' => Polygon::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function collaborators(): hasMany
    {
        return $this->hasMany(Collaborator::class);
    }

    // Define un scope para elementos visibles
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
