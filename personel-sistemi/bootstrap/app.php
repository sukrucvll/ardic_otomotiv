<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // Giriş yapmış kullanıcı login sayfasına giderse personel listesine yönlendirilir
        $middleware->redirectUsersTo('/employees');

        // Canlıda uygulama nginx'in arkasında çalışır (konteyner yalnızca 127.0.0.1'de
        // dinler); HTTPS ve gerçek istemci IP'si X-Forwarded-* başlıklarından okunur
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Sayfa uzun süre açık kalıp CSRF token'ı/oturum geçersizleşince (419)
        // ham "Page Expired" sayfası yerine kullanıcıyı uygun yere yönlendir
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return $request->user()
                ? back()->with('error', 'Sayfanın süresi doldu. Lütfen işlemi tekrar deneyin.')
                : redirect()->route('login')->with('error', 'Oturumunuzun süresi doldu. Lütfen tekrar giriş yapın.');
        });
    })->create();
