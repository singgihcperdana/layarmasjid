<?php
/**
 * Router Admin DKM (my.layarmasjid.com/admin)
 * - Jika sudah login -> redirect ke /admin/profile
 * - Jika belum login -> redirect ke /admin/login
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
