<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonResponse extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'lesson_id', 'response', 'score'];

    // Definir las relaciones si las tienes, por ejemplo:
    public function answers()
    {
        return $this->hasMany(LessonResponseAnswer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
