<?php

namespace App\Http;

use App\Http\Middleware\AuditMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CanAny;
use App\Http\Middleware\CheckMainModulePermission;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckRoleOrPermission;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnforcePasswordChange;
use App\Http\Middleware\EnsureRoutePermission;
use App\Http\Middleware\EnsureSignatureSetup;
use App\Http\Middleware\PerformanceMonitorMiddleware;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SetAppUrlByRequest;
use App\Http\Middleware\SuperAdminAccess;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidationErrorsToFlasher;
use App\Http\Middleware\VerifyCsrfToken;
use Flasher\Laravel\Middleware\FlasherMiddleware;
use Flasher\Laravel\Middleware\SessionMiddleware;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        PerformanceMonitorMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            CheckMainModulePermission::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            AuditMiddleware::class,
            EnsureRoutePermission::class,
            SetAppUrlByRequest::class,
            EnforcePasswordChange::class,
            FlasherMiddleware::class,
            SessionMiddleware::class,
            ValidationErrorsToFlasher::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'auth.session' => AuthenticateSession::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'canany' => CanAny::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'precognitive' => HandlePrecognitiveRequests::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'require_signature' => EnsureSignatureSetup::class,
        'role' => CheckRole::class,
        'role_id' => CheckRole::class,
        'permission' => CheckPermission::class,
        'role_or_permission' => CheckRoleOrPermission::class,

        // ✅ Super Admin Access
        'superadmin' => SuperAdminAccess::class,
        'auto_permission' => EnsureRoutePermission::class,

        // 🌐 Auto App URL (الذي أنشأناه)
        'set_app_url' => SetAppUrlByRequest::class,
    ];
}
