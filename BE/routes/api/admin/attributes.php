<?php

use App\Http\Controllers\Api\Admin\AttributeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('attributes', AttributeController::class);