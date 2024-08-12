<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'course_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Relación con Lesson
    public function lessons()
    {
        return $this->hasOne(Lesson::class);
    }

    public function media()
    {
        return $this->hasMany(CourseMedia::class);
    }
}
