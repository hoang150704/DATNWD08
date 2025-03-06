<?php

use App\Http\Controllers\Api\Admin\BoxController;
use Illuminate\Support\Facades\Route;

Route::prefix('boxes')->group(function () {
    Route::get('/', [BoxController::class, 'index']); 
    Route::get('/{id}/list_for_delete',[BoxController::class, 'listForDelete']);
    Route::post('/', [BoxController::class, 'store']); 
    Route::get('/{id}', [BoxController::class, 'show']); 
    Route::put('/{id}', [BoxController::class, 'update']); 
    Route::delete('/{id}', [BoxController::class, 'destroy']); 

});
