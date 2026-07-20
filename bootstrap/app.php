<?php

use Auth\Role\Domain\Exceptions\HierarchyViolationException;
use Auth\Role\Domain\Exceptions\MaxRolesExceededException;
use Auth\Role\Domain\Exceptions\SoDViolationException;
use Auth\Role\Domain\Exceptions\SuperAdminExclusiveException;
use Auth\User\Domain\Exceptions\AccountLockedException;
use Auth\User\Domain\Exceptions\InvalidCredentialsException;
use Auth\User\Domain\Exceptions\PasswordReusedException;
use Auth\User\Infrastructure\Http\Middleware\Rbac2Middleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Shared\Domain\Exceptions\DomainException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rbac2' => Rbac2Middleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidCredentialsException $e, Request $request) {
            return response()->json([
                'errors' => [['code' => $e->errorCode(), 'message' => $e->getMessage(), 'field' => null]],
            ], 401);
        });

        $exceptions->render(function (AccountLockedException $e, Request $request) {
            return response()->json([
                'errors' => [['code' => $e->errorCode(), 'message' => $e->getMessage(), 'field' => null]],
                'locked_until' => $e->lockedUntil()->format(DATE_ATOM),
                'retry_after' => $e->retryAfterSeconds(),
            ], 423);
        });

        $exceptions->render(function (SoDViolationException $e, Request $request) {
            return response()->json([
                'errors' => [['code' => $e->errorCode(), 'message' => $e->getMessage(), 'field' => null]],
                'conflicting_roles' => $e->conflictingRoles(),
            ], 409);
        });

        $exceptions->render(function (HierarchyViolationException $e, Request $request) {
            return response()->json([
                'errors' => [['code' => $e->errorCode(), 'message' => $e->getMessage(), 'field' => null]],
            ], 403);
        });

        $exceptions->render(function (MaxRolesExceededException|SuperAdminExclusiveException $e, Request $request) {
            return response()->json([
                'errors' => [['code' => $e->errorCode(), 'message' => $e->getMessage(), 'field' => null]],
            ], 409);
        });

        $exceptions->render(function (PasswordReusedException $e, Request $request) {
            return response()->json([
                'errors' => [['code' => $e->errorCode(), 'message' => $e->getMessage(), 'field' => null]],
            ], 422);
        });

        $exceptions->render(function (DomainException $e, Request $request) {
            return response()->json([
                'errors' => [['code' => $e->errorCode(), 'message' => $e->getMessage(), 'field' => null]],
            ], 422);
        });
    })->create();
