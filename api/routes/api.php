<?php

use App\Modules\Auth\Http\Controllers\CurrentAccountController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::get('/health', fn (): JsonResponse => response()->json(['status' => 'ok']))
    ->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);

Route::get('/auth/me', [CurrentAccountController::class, 'show'])
    ->middleware(['auth:sanctum', 'cache.headers:no_store']);
