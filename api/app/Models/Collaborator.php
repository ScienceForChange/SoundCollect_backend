<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collaborator extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_zone_id',
        'collaborator_name',
        'logo',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    public function studyZone(): BelongsTo
    {
        return $this->belongsTo(StudyZone::class);
    }
}
