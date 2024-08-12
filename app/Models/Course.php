<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'content',
        'cover_image',
        'instructor_id',
        'status',
    ];

    // Definir la relación con el modelo Section
    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function media()
    {
        return $this->hasMany(CourseMedia::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_user', 'course_id', 'user_id');
    }

}
