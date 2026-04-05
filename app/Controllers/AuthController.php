<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

final class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if (Auth::attempt($email, $password)) {
                $this->redirect('dashboard');
            }

            $this->flash('error', 'Invalid credentials.');
        }

        $this->view('auth/login', ['title' => 'Login']);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('login');
    }

    public function profile(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $user = Database::fetch(
            'SELECT users.id, users.name, users.email, users.status, users.last_login_at, roles.name AS role_name
             FROM users
             JOIN roles ON roles.id = users.role_id
             WHERE users.id = :id',
            ['id' => Auth::user()['id']]
        );

        $this->view('auth/profile', [
            'title' => 'My Profile',
            'profileUser' => $user,
        ]);
    }

    public function updateProfile(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $userId = (int) (Auth::user()['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($name === '' || $email === '') {
            $this->flash('error', 'Name and email are required.');
            $this->redirect('profile');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Enter a valid email address.');
            $this->redirect('profile');
        }

        $existing = Database::fetch(
            'SELECT id FROM users WHERE email = :email AND id <> :id',
            ['email' => $email, 'id' => $userId]
        );

        if ($existing) {
            $this->flash('error', 'That email address is already in use.');
            $this->redirect('profile');
        }

        Database::query(
            'UPDATE users SET name = :name, email = :email WHERE id = :id',
            ['name' => $name, 'email' => $email, 'id' => $userId]
        );

        $authUser = Auth::user();
        if (($authUser['role_name'] ?? '') === 'client') {
            Database::query(
                'UPDATE clients SET contact_name = :name, contact_email = :email WHERE client_user_id = :user_id',
                ['name' => $name, 'email' => $email, 'user_id' => $userId]
            );
        }

        Auth::refreshUser($userId);
        $this->flash('success', 'Profile updated.');
        $this->redirect('profile');
    }

    public function changePassword(): void
    {
        Auth::requireRole(['master_admin', 'employee', 'client']);
        $userId = (int) (Auth::user()['id'] ?? 0);
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->flash('error', 'Complete all password fields.');
            $this->redirect('profile');
        }

        if ($newPassword !== $confirmPassword) {
            $this->flash('error', 'New password and confirmation do not match.');
            $this->redirect('profile');
        }

        if (strlen($newPassword) < 8) {
            $this->flash('error', 'New password must be at least 8 characters.');
            $this->redirect('profile');
        }

        $user = Database::fetch('SELECT password FROM users WHERE id = :id', ['id' => $userId]);
        if (!$user || !password_verify($currentPassword, (string) $user['password'])) {
            $this->flash('error', 'Current password is incorrect.');
            $this->redirect('profile');
        }

        Database::query(
            'UPDATE users SET password = :password WHERE id = :id',
            ['password' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]
        );

        Auth::refreshUser($userId);
        $this->flash('success', 'Password changed.');
        $this->redirect('profile');
    }
}
