<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = []): void
    {
        $viewData = array_merge(self::$shared, $data);
        extract($viewData, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/' . $template . '.php';
    }
}
