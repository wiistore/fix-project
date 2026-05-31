<?php

declare(strict_types=1);

class Controller
{
    protected function view(string $path, array $data = []): void
    {
        $viewFile = $this->viewPath($path);

        if (!file_exists($viewFile)) {
            Response::abort(404, 'View tidak ditemukan: ' . $path);
        }

        // Kirim data ke view
        extract($data, EXTR_SKIP);

        require $viewFile;
    }

    protected function model(string $modelName)
    {
        $modelFile = APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $modelName . '.php';

        if (!file_exists($modelFile)) {
            throw new RuntimeException('Model tidak ditemukan: ' . $modelName);
        }

        require_once $modelFile;

        if (!class_exists($modelName)) {
            throw new RuntimeException('Class model tidak ditemukan: ' . $modelName);
        }

        return new $modelName();
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    protected function requireLogin(): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
        }
    }

    protected function requireRole($roles): void
    {
        $this->requireLogin();

        $user = Session::user();
        $userRole = $user['role'] ?? null;
        $allowedRoles = is_array($roles) ? $roles : [$roles];

        if (!in_array($userRole, $allowedRoles, true)) {
            $this->redirect('/403');
        }
    }

    private function viewPath(string $path): string
    {
        $path = trim($path, '/');
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $path . '.php';
    }
}