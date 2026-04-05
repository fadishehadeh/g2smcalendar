<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\ApprovalController;
use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\DownloadController;
use App\Controllers\EmployeeController;
use App\Controllers\WorkspaceController;
use App\Controllers\WizardController;
use App\Services\WorkspaceService;

final class App
{
    private string $rootPath;
    private array $config;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
        $this->registerAutoloader();
        $this->boot();
    }

    public function run(): void
    {
        $route = $_GET['route'] ?? 'dashboard';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $routes = [
            'login' => [AuthController::class, 'login'],
            'logout' => [AuthController::class, 'logout'],
            'profile' => [AuthController::class, 'profile'],
            'profile.update' => [AuthController::class, 'updateProfile'],
            'profile.password' => [AuthController::class, 'changePassword'],
            'dashboard' => [DashboardController::class, 'index'],
            'clients' => [ClientController::class, 'index'],
            'clients.store' => [ClientController::class, 'store'],
            'employees' => [EmployeeController::class, 'index'],
            'employees.store' => [EmployeeController::class, 'store'],
            'assignments' => [EmployeeController::class, 'assignments'],
            'assignments.store' => [EmployeeController::class, 'storeAssignment'],
            'assignments.remove' => [EmployeeController::class, 'removeAssignment'],
            'calendar' => [CalendarController::class, 'index'],
            'calendar.item' => [CalendarController::class, 'show'],
            'calendar.save' => [CalendarController::class, 'saveItem'],
            'calendar.artwork' => [CalendarController::class, 'attachArtwork'],
            'calendar.status' => [CalendarController::class, 'updateStatus'],
            'calendar.comment' => [CalendarController::class, 'addComment'],
            'wizard' => [WizardController::class, 'index'],
            'wizard.generate' => [WizardController::class, 'generate'],
            'posts' => [WorkspaceController::class, 'posts'],
            'posts.bulk' => [WorkspaceController::class, 'bulkPosts'],
            'approvals' => [WorkspaceController::class, 'approvals'],
            'artwork' => [WorkspaceController::class, 'artwork'],
            'notifications' => [WorkspaceController::class, 'notifications'],
            'activity' => [WorkspaceController::class, 'activity'],
            'settings' => [WorkspaceController::class, 'settings'],
            'settings.test-email' => [WorkspaceController::class, 'sendTestEmail'],
            'approval.review' => [ApprovalController::class, 'review'],
            'download.file' => [DownloadController::class, 'download'],
            'preview.file' => [DownloadController::class, 'preview'],
        ];

        if (!isset($routes[$route])) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not Found']);
            return;
        }

        [$controllerClass, $action] = $routes[$route];
        View::share('currentRoute', $route);

        if (Auth::check()) {
            $workspaceService = new WorkspaceService();
            View::share('shellData', [
                'unread_notifications' => $workspaceService->unreadNotificationsCount(),
            ]);
        }

        $controller = new $controllerClass($this->config, $this->rootPath);

        if ($method === 'POST') {
            Csrf::validate();
        }

        $controller->{$action}();
    }

    private function boot(): void
    {
        Env::load($this->rootPath . '/.env');

        $this->config = [
            'app' => require $this->rootPath . '/config/app.php',
            'database' => require $this->rootPath . '/config/database.php',
        ];

        session_start();

        Database::connect($this->config['database']);
        View::share('config', $this->config);
        View::share('authUser', Auth::user());
        View::share('flash', $_SESSION['flash'] ?? []);
        unset($_SESSION['flash']);
    }

    private function registerAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $this->rootPath . '/app/' . $relative . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
