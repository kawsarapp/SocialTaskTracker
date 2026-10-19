<?php

use App\Http\Controllers\GoogleSheetController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/google-sheets/{token}', [GoogleSheetController::class, 'webhook'])
    ->name('webhooks.google-sheets');
