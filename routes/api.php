<?php

use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Route;

Route::apiResource('imports', ImportController::class)->only(['index', 'store', 'show']);
