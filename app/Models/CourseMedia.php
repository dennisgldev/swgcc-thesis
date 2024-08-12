<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'section_id',
        'file_name',
        'file_path',
        'file_type',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
