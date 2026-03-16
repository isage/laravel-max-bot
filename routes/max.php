<?php

use Blacky0892\Max\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post(
    config('max.route.path', '/max/webhook'),
    WebhookController::class
);
