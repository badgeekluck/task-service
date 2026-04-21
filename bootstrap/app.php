<?php

use App\Exceptions\Task\InvalidStatusTransitionException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Domain exception → 422 Unprocessable Entity
        // Tamamlanan/iptal edilen görevi güncellemeye çalışmak
        $exceptions->render(function (InvalidStatusTransitionException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors'  => ['status' => [$e->getMessage()]],
                ], 422);
            }
        });

        // Model bulunamadı → 404 (varsayılan Laravel mesajı yerine temiz JSON)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Kayıt bulunamadı.',
                ], 404);
            }
        });

        // Yetki hatası → 403 (Policy red)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bu işlem için yetkiniz yok.',
                ], 403);
            }
        });

    })
    ->booted(function (): void {
        // API genel: 60 istek/dk — kullanıcı bazlı veya IP bazlı
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                        ->by($request->user()?->id ?: $request->ip());
        });

        // Auth endpoint'leri: kaba kuvvet koruması — 10 istek/dk IP bazlı
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    })
    ->create();
