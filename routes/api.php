<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonResponseController;
use App\Http\Controllers\Admin\PermissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí es donde puedes registrar rutas de API para tu aplicación.
| Estas rutas son cargadas por el RouteServiceProvider dentro de un grupo que
| se asigna al grupo de middleware "api". ¡Haz algo grandioso!
|
*/

// Ruta para obtener el usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas protegidas por autenticación para la gestión de roles y permisos
Route::middleware(['auth:sanctum', 'can:panel de gestión de roles y permisos'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::get('/permissions', [PermissionController::class, 'index']);
});

// Ruta para obtener los permisos del usuario autenticado
Route::middleware('auth:sanctum')->get('/user/permissions', function (Request $request) {
    return response()->json([
        'permissions' => $request->user()->getAllPermissions()->pluck('name')
    ]);
});

// Rutas protegidas por autenticación para la gestión de usuarios
Route::middleware(['auth:sanctum', 'can:panel de gestión de usuarios'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}/edit', [UserController::class, 'edit']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

// Ruta para cambiar la contraseña del usuario autenticado
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);

// Rutas protegidas por autenticación para la gestión de cursos y reportería
Route::middleware(['auth:sanctum', 'can:gestión de cursos y reportería'])->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
    Route::get('/enrolled-courses', [EnrollmentController::class, 'getMyCourses']);
});

// Rutas protegidas por autenticación para ver cursos e inscribirse
Route::middleware(['auth:sanctum', 'can:ver cursos'])->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses-with-enrollment', [CourseController::class, 'getCoursesWithEnrollments']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::get('/enrolled-courses', [EnrollmentController::class, 'getMyCourses']); // Ruta para obtener cursos inscritos
});

// Rutas protegidas por autenticación para la inscripción y finalización de cursos
Route::middleware(['auth:sanctum', 'can:inscribirse a cursos'])->group(function () {
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::get('/courses/{course}/is-enrolled', [EnrollmentController::class, 'isEnrolled']);
    Route::post('/courses/{id}/finalize', [CourseController::class, 'finalize']);
});

// Rutas para manejar respuestas de lecciones
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/lessons/responses', [LessonResponseController::class, 'store']);
    Route::get('/lessons/{lesson}/responses', [LessonResponseController::class, 'show']);
    Route::post('/lessons/{lesson}/submit', [LessonResponseController::class, 'submit']);
});
