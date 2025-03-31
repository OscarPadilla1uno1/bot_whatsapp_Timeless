<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;

Route::get('/vroom', [App\Http\Controllers\VroomController::class, 'index']);

