<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonResponseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Ruta para obtener el usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas protegidas por autenticación para la gestión de roles
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
});

// Rutas protegidas por autenticación para la gestión de usuarios
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}/edit', [UserController::class, 'edit']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

// Rutas protegidas por autenticación para la gestión de cursos
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);

    // Nueva ruta para la inscripción en un curso
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::get('/courses/{course}/is-enrolled', [EnrollmentController::class, 'isEnrolled']);

    // Rutas adicionales para inscripciones y finalización de cursos
    Route::get('/enrolled-courses', [EnrollmentController::class, 'getMyCourses']);
    Route::post('/courses/{id}/finalize', [EnrollmentController::class, 'finalizeCourse']);

    Route::post('/courses/{id}/enroll', [CourseController::class, 'enroll'])->middleware('auth');
    Route::post('/courses/{id}/finalize', [CourseController::class, 'finalize'])->middleware('auth');
});

// Rutas para manejar respuestas de lecciones
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/lessons/responses', [LessonResponseController::class, 'store']);
    Route::get('/lessons/{lesson}/responses', [LessonResponseController::class, 'show']);
});

// Rutas para enviar respuestas a lecciones
Route::middleware('auth:sanctum')->post('/lessons/{lesson}/submit', [LessonResponseController::class, 'submit']);
