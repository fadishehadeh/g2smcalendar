<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    public function __construct(
        protected array $config,
        protected string $rootPath
    ) {
    }

    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    protected function redirect(string $route, array $params = []): never
    {
        $url = $this->config['app']['base_url'] . '/index.php?route=' . urlencode($route);
        if ($params !== []) {
            $url .= '&' . http_build_query($params);
        }

        header('Location: ' . $url);
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }
}
