<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonResponseAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_response_id', 'answer_id'];

    // Definir las relaciones si las tienes, por ejemplo:
    public function lessonResponse()
    {
        return $this->belongsTo(LessonResponse::class);
    }
}
