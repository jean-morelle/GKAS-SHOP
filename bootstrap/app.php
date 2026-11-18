<?php

use App\Http\Middleware\EnsureAccountUsable;
use App\Http\Middleware\EnsureShopAvailable;
use App\Http\Middleware\EnsureStoreAbility;
use App\Http\Middleware\EnsureStoreMember;
use App\Http\Middleware\EnsureStoreOperational;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'store.member' => EnsureStoreMember::class,
            'store.operational' => EnsureStoreOperational::class,
            'store.can' => EnsureStoreAbility::class,
            'shop.available' => EnsureShopAvailable::class,
        ]);
        $middleware->appendToGroup('web', EnsureAccountUsable::class);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn ($request) => $request->user()->homeRoute());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
