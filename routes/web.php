<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * Routes for the test project UI.
 *
 * Key pages:
 * - `/dumps`   sources management (upload/delete/list)
 * - `/exports` export generation (single/merged) and downloads
 */
Route::redirect('/', '/dumps');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/dumps', [App\Http\Controllers\DumpController::class, 'index'])->name('dumps.index');
Route::post('/dumps', [App\Http\Controllers\DumpController::class, 'store'])->name('dumps.store');
Route::delete('/dumps/{dump}', [App\Http\Controllers\DumpController::class, 'destroy'])->name('dumps.destroy');

Route::get('/exports', [App\Http\Controllers\ExportController::class, 'index'])->name('exports.index');
Route::post('/exports/generate', [App\Http\Controllers\ExportController::class, 'generate'])->name('exports.generate');
Route::post('/exports/merge', [App\Http\Controllers\ExportController::class, 'merge'])->name('exports.merge');
Route::get('/exports/{exportFile}/download', [App\Http\Controllers\ExportController::class, 'download'])->name('exports.download');
Route::get('/exports/tasks/{task}', [App\Http\Controllers\ExportController::class, 'taskStatus'])->name('exports.taskStatus');


