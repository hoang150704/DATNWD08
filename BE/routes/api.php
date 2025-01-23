<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    // Route::get('/list', [CategoryController::class, 'getAllCategories']);
    // Route::get('/update/{slug}', [CategoryController::class, 'edit']);
    // Route::post('/add', [CategoryController::class, 'store']);
    // Route::post('/renderslug', [CategoryController::class, 'renderSlug']);
    // Route::put('/update/{id}', [CategoryController::class, 'update']);
    Route::delete('/delete/{id}', [CategoryController::class, 'destroy']);
    
    
});
