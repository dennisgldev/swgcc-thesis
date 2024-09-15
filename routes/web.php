<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Página principal
Route::get('/', function () {
    return view('welcome');
});

// Rutas de autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user', [AuthController::class, 'user'])->middleware('auth');

// Rutas para la gestión de cursos (Laravel)
// Route::middleware(['auth', 'role:Administrador,Docente,Estudiante'])->group(function () {
//     Route::resource('courses', CourseController::class)->only(['index', 'show']);
// });

//Route::middleware(['auth', 'role:Docente'])->group(function () {
//    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
//    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
//    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
//    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
//    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
//});

// Catch-all para Vue.js
Route::get('/{any}', function () {
    Log::info('Catch-all route triggered');
    return view('welcome'); // Asegúrate de que 'welcome' cargue tu archivo Vue.js
})->where('any', '.*');
