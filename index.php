<?php

declare(strict_types=1);

require __DIR__ . '/app/Core/App.php';

use App\Core\App;

$app = new App(__DIR__);
$app->run();
