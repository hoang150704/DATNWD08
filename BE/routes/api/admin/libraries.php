<?php

use App\Http\Controllers\Api\Admin\LibraryController;
use Illuminate\Support\Facades\Route;

Route::apiResource('libraries', LibraryController::class);