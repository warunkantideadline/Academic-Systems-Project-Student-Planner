<?php
// config/auth.php

function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'nama'  => $_SESSION['user_nama']  ?? 'User',
        'username' => $_SESSION['user_username'] ?? '',
    ];
}

function loginUser(array $user): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_nama']     = $user['nama'];
    $_SESSION['user_username'] = $user['username'];
}

function logoutUser(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}