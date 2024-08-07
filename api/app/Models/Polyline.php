<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Polylines extends Model
{
    use HasFactory;

    protected $fillable = [];
    /**
     * The observations attatched with the type.
     */
    public function observations(): BelongsTo
    {
        return $this->BelongsTo(Observation::class);
    }
}
