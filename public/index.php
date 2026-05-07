<?php

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use App\Controllers\AffiliateController;
use App\Controllers\ApiController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\CandidateController;
use App\Controllers\CommunityController;
use App\Controllers\DashboardController;
use App\Controllers\DepartmentController;
use App\Controllers\PersonController;
use App\Controllers\PositionController;
use App\Controllers\ReportController;
use App\Controllers\UserController;

require dirname(__DIR__) . '/src/bootstrap.php';

$routes = [
    'GET' => [
        '/' => [DashboardController::class, 'index'],

        '/login' => [AuthController::class, 'showLogin'],
        '/forgot-password' => [AuthController::class, 'showForgotPassword'],
        '/reset-password' => [AuthController::class, 'showResetPassword'],
        '/verify-email' => [AuthController::class, 'verifyEmail'],

        '/departments' => [DepartmentController::class, 'index'],

        '/users' => [UserController::class, 'index'],
        '/users/create' => [UserController::class, 'createForm'],
        '/users/edit' => [UserController::class, 'editForm'],

        '/communities' => [CommunityController::class, 'index'],
        '/positions' => [PositionController::class, 'index'],

        '/persons' => [PersonController::class, 'index'],
        '/persons/create' => [PersonController::class, 'createForm'],
        '/persons/edit' => [PersonController::class, 'editForm'],

        '/candidate-assignments' => [CandidateController::class, 'index'],
        '/affiliate-assignments' => [AffiliateController::class, 'index'],

        '/reports' => [ReportController::class, 'index'],
        '/reports/export' => [ReportController::class, 'export'],

        '/audit-logs' => [AuditController::class, 'index'],

        '/api/municipalities' => [ApiController::class, 'municipalities'],
        '/api/communities' => [ApiController::class, 'communities'],
        '/api/leaders' => [ApiController::class, 'leaders'],
    ],
    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/logout' => [AuthController::class, 'logout'],
        '/forgot-password' => [AuthController::class, 'sendResetLink'],
        '/reset-password' => [AuthController::class, 'resetPassword'],

        '/users/create' => [UserController::class, 'store'],
        '/users/edit' => [UserController::class, 'update'],
        '/users/toggle' => [UserController::class, 'toggle'],
        '/users/resend' => [UserController::class, 'resendVerification'],

        '/communities/create' => [CommunityController::class, 'store'],
        '/communities/edit' => [CommunityController::class, 'update'],
        '/communities/delete' => [CommunityController::class, 'delete'],

        '/positions/create' => [PositionController::class, 'store'],
        '/positions/edit' => [PositionController::class, 'update'],
        '/positions/delete' => [PositionController::class, 'delete'],

        '/persons/create' => [PersonController::class, 'store'],
        '/persons/edit' => [PersonController::class, 'update'],

        '/candidate-assignments/create' => [CandidateController::class, 'store'],
        '/candidate-assignments/edit' => [CandidateController::class, 'update'],
        '/candidate-assignments/delete' => [CandidateController::class, 'delete'],
        '/candidate-assignments/confirm' => [CandidateController::class, 'confirm'],

        '/affiliate-assignments/create' => [AffiliateController::class, 'store'],
        '/affiliate-assignments/edit' => [AffiliateController::class, 'update'],
        '/affiliate-assignments/delete' => [AffiliateController::class, 'delete'],
    ],
];

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = current_path();

if (!isset($routes[$method][$path])) {
    http_response_code(404);
    echo '404 - Ruta no encontrada';
    exit;
}

[$class, $action] = $routes[$method][$path];
call_user_func([$class, $action]);
