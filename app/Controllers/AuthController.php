<?php
// app/Controllers/AuthController.php

require_once __DIR__ . '/../Auth.php';

class AuthController {
    public function showLogin() {
        if (Auth::check()) {
            redirect('dashboard');
        }
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            set_flash('danger', '安全驗證碼 (CSRF) 錯誤，請重試');
            redirect('login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            set_flash('danger', '請輸入帳號與密碼');
            redirect('login');
        }

        if (Auth::login($username, $password)) {
            set_flash('success', '歡迎登入 ESG 永續管理平台');
            redirect('dashboard');
        } else {
            set_flash('danger', '帳號或密碼錯誤，若連續錯誤 5 次將被系統自動鎖定');
            redirect('login');
        }
    }

    public function handleLogout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            http_response_code(405);
            exit('Method Not Allowed');
        }
        Auth::logout();
        redirect('login');
    }

}
