<?php
/**
 * Portal Layar Masjid (my.layarmasjid.com)
 * Router Utama:
 * - Jika sudah login -> langsung masuk ke /profile
 * - Jika belum login -> langsung masuk ke /login
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['masjid_id'])) {
    header("Location: profile");
    exit;
}

header("Location: login");
exit;
