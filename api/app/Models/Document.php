<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_zone_id',
        'name',
        'file',
        'type',
    ];

    public function studyZone()
    {
        return $this->belongsTo(StudyZone::class);
    }
}
