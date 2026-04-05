<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class ApprovalController extends Controller
{
    public function review(): void
    {
        Auth::requireRole(['client']);
        $this->redirect('calendar', ['item_id' => (int) ($_GET['item_id'] ?? 0)]);
    }
}
