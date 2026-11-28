<?php
/**
 * Header & Sidebar Layout Template Admin
 */
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../api/db.php';

// Ambil info masjid untuk header
$stmt = $pdo->prepare("SELECT * FROM masjid WHERE id = ? LIMIT 1");
$stmt->execute([$current_masjid_id]);
$masjid = $stmt->fetch();

// Ambil info email user untuk footer sidebar
$user_email = $_SESSION['email'] ?? null;
if (empty($user_email) && isset($current_masjid_id)) {
    $stmtEmail = $pdo->prepare("SELECT email FROM users WHERE masjid_id = ? LIMIT 1");
    $stmtEmail->execute([$current_masjid_id]);
    $user_email = $stmtEmail->fetchColumn();
    if ($user_email) {
        $_SESSION['email'] = $user_email;
    }
}

$page_title = $page_title ?? 'Panel Pengurus';
$current_page = $current_page ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($masjid['nama'] ?? 'Layar Masjid') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Layar Masjid Admin Global CSS -->
  <link rel="stylesheet" href="<?= asset_v('assets/css/admin.css') ?>">
</head>
<body>

  <div class="app-wrapper">

    <!-- Backdrop untuk Mobile -->
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <!-- SIDEBAR NAVIGASI -->
    <aside id="sidebar" class="sidebar">
      <div class="sidebar-header">
        <div class="d-flex flex-column justify-content-center w-100 min-w-0" style="height: 100%;">
          <div class="sidebar-masjid-name text-truncate" id="sidebarMasjidName" title="<?= htmlspecialchars($masjid['nama'] ?? 'Layar Masjid') ?>"><?= htmlspecialchars($masjid['nama'] ?? 'Layar Masjid') ?></div>
          <div class="mt-1 d-flex align-items-center">
            <button type="button" class="badge-masjid-id" id="btnCopyMasjidId" data-id="<?= htmlspecialchars($current_masjid_id) ?>" title="Klik untuk salin ID Masjid">
              <span id="badgeMasjidIdText">ID Masjid: <?= htmlspecialchars($current_masjid_id) ?></span>
              <i class="bi bi-copy ms-1" id="copyIcon" style="font-size: 0.78rem;"></i>
            </button>
          </div>
        </div>
      </div>

      <nav class="sidebar-nav nav flex-column">
        <a class="nav-link <?= $current_page === 'profile' ? 'active' : '' ?>" href="profile">
          <i class="bi bi-building"></i>
          <span>Profil</span>
        </a>
        <a class="nav-link <?= $current_page === 'schedule' ? 'active' : '' ?>" href="schedule">
          <i class="bi bi-compass"></i>
          <span>Jadwal Sholat</span>
        </a>
        <a class="nav-link <?= $current_page === 'timer' ? 'active' : '' ?>" href="timer">
          <i class="bi bi-stopwatch"></i>
          <span>Timer</span>
        </a>
        <a class="nav-link <?= $current_page === 'slides' ? 'active' : '' ?>" href="slides">
          <i class="bi bi-images"></i>
          <span>Konten Slide</span>
        </a>
        <a class="nav-link <?= $current_page === 'running_text' ? 'active' : '' ?>" href="running_text">
          <i class="bi bi-broadcast"></i>
          <span>Teks Berjalan</span>
        </a>
        <a class="nav-link <?= in_array($current_page, ['password', 'account']) ? 'active' : '' ?>" href="password">
          <i class="bi bi-key"></i>
          <span>Ganti Password</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <?php if (!empty($user_email)): ?>
          <div class="d-flex align-items-center gap-2 mb-2 px-1 text-white-50 overflow-hidden" style="font-size: 0.8rem;" title="<?= htmlspecialchars($user_email) ?>">
            <i class="bi bi-person-circle fs-6 text-warning flex-shrink-0"></i>
            <span class="text-truncate text-light opacity-75"><?= htmlspecialchars($user_email) ?></span>
          </div>
        <?php endif; ?>
        <a href="logout" class="btn btn-sm btn-outline-light w-100 opacity-75">
          <i class="bi bi-box-arrow-right me-1"></i> Keluar
        </a>
      </div>
    </aside>

    <!-- AREA KONTEN UTAMA -->
    <div class="main-content">
      
      <!-- Top Header -->
      <header class="top-header">
        <div class="d-flex align-items-center gap-3">
          <button type="button" id="sidebarToggle" class="btn btn-sm btn-outline-success d-lg-none">
            <i class="bi bi-list fs-5"></i>
          </button>
          <h5 class="mb-0 fw-bold text-success"><?= htmlspecialchars($page_title) ?></h5>
        </div>

        <div>
          <a href="display/<?= htmlspecialchars($current_masjid_id) ?>" target="_blank" class="btn btn-sm btn-masjid shadow-sm px-3 py-1 d-inline-flex align-items-center">
            <i class="bi bi-tv me-1"></i> Buka Layar TV
          </a>
        </div>
      </header>

      <!-- Content Container -->
      <main class="p-3 p-md-4">
