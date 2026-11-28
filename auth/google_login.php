<?php
/**
 * Inisialisasi Redirect Login dengan Akun Google (OAuth 2.0)
 * File: auth/google_login.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../api/env.php';

$client_id     = trim(env('GOOGLE_CLIENT_ID', ''));
$redirect_uri  = trim(env('GOOGLE_REDIRECT_URI', 'http://localhost/layarmasjid/auth/google_callback.php'));

if (empty($client_id) || $client_id === 'MASUKKAN_GOOGLE_CLIENT_ID_DI_SINI') {
    die("Google Client ID belum dikonfigurasi di file .env. Silakan buka file .env dan masukkan GOOGLE_CLIENT_ID Anda.");
}

// Generate CSRF state token
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Bangun URL otorisasi Google OAuth 2.0
$params = [
    'client_id'             => $client_id,
    'redirect_uri'          => $redirect_uri,
    'response_type'         => 'code',
    'scope'                 => 'openid email profile',
    'state'                 => $state,
    'access_type'           => 'online',
    'prompt'                => 'select_account'
];

$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

header('Location: ' . $auth_url);
exit;
