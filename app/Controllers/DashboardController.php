<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\WorkspaceService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $workspace = new WorkspaceService();

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $workspace->dashboardStats(),
            'activities' => $workspace->recentActivity(),
            'notifications' => $workspace->notifications(),
            'pendingItems' => $workspace->pendingActionItems(),
        ]);
    }
}
