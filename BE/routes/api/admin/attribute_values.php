<?php

use App\Http\Controllers\Api\Admin\AttributeValueController;
use Illuminate\Support\Facades\Route;

Route::prefix('attribute_values')->group(function () {
    Route::get('/list/{id}', [AttributeValueController::class, 'index']); 
    Route::get('/list', [AttributeValueController::class, 'list']);
    Route::get('/update/{id}', [AttributeValueController::class, 'show']);
    Route::post('/create', [AttributeValueController::class, 'store']);
    Route::put('/update/{id}', [AttributeValueController::class, 'update']);
    Route::delete('/delete/{id}', [AttributeValueController::class, 'destroy']);
});