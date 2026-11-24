<?php
/**
 * Header & Sidebar Layout Template Admin
 */
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../api/db.php';

// Ambil info masjid untuk header
$stmt = $pdo->prepare("SELECT * FROM masjid WHERE id = ? LIMIT 1");
$stmt->execute([$current_masjid_id]);
$masjid = $stmt->fetch();

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
  <style>
    :root {
      --primary-green: #0a4d3a;
      --deep-green: #051f18;
      --gold-accent: #f0c96a;
      --gold-dark: #d4a737;
      --sidebar-width: 270px;
    }
    body {
      background-color: #f4f7f5;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: #2b3a33;
      overflow-x: hidden;
    }
    .app-wrapper {
      display: flex;
      min-height: 100vh;
      width: 100%;
    }
    .sidebar {
      width: var(--sidebar-width);
      background: linear-gradient(180deg, #051f18 0%, #083327 50%, #0a4d3a 100%);
      color: #ffffff;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      position: sticky;
      top: 0;
      height: 100vh;
      border-right: 1px solid rgba(240, 201, 106, 0.25);
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
      z-index: 1030;
      transition: all 0.3s ease;
    }
    .sidebar-header {
      padding: 24px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .sidebar-nav {
      padding: 16px 12px;
      flex-grow: 1;
      overflow-y: auto;
    }
    .sidebar-nav .nav-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      color: rgba(255, 255, 255, 0.78);
      font-weight: 600;
      font-size: 0.95rem;
      border-radius: 10px;
      margin-bottom: 6px;
      transition: all 0.2s ease;
      border: 1px solid transparent;
      text-decoration: none;
    }
    .sidebar-nav .nav-link:hover {
      color: var(--gold-accent);
      background: rgba(255, 255, 255, 0.06);
    }
    .sidebar-nav .nav-link.active {
      color: #051f18;
      background: linear-gradient(135deg, var(--gold-accent) 0%, var(--gold-dark) 100%);
      box-shadow: 0 4px 14px rgba(240, 201, 106, 0.35);
      border-color: var(--gold-accent);
    }
    .sidebar-nav .nav-link i {
      font-size: 1.15rem;
    }
    .sidebar-footer {
      padding: 16px 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(0, 0, 0, 0.15);
    }
    .main-content {
      flex-grow: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      background: #f4f7f5;
    }
    .top-header {
      background: #ffffff;
      border-bottom: 1px solid #e1e7e4;
      padding: 14px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1020;
    }
    .card {
      border: 1px solid rgba(10, 77, 58, 0.12);
      border-radius: 14px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
      margin-bottom: 24px;
    }
    .card-header {
      background: #ffffff;
      border-bottom: 1px solid rgba(10, 77, 58, 0.1);
      font-weight: 700;
      color: var(--primary-green);
      border-top-left-radius: 14px !important;
      border-top-right-radius: 14px !important;
      padding: 14px 18px;
    }
    .btn-masjid {
      background: linear-gradient(135deg, var(--gold-accent), var(--gold-dark));
      color: #051f18;
      font-weight: 700;
      border: none;
      border-radius: 8px;
    }
    .btn-masjid:hover {
      background: linear-gradient(135deg, #fce29d, var(--gold-accent));
      color: #051f18;
    }
    .badge-masjid-id {
      background: rgba(240, 201, 106, 0.18);
      color: var(--gold-accent);
      border: 1px solid rgba(240, 201, 106, 0.5);
      font-size: 0.78rem;
      padding: 3px 8px;
      border-radius: 20px;
      font-weight: 600;
    }
    @media (max-width: 991.98px) {
      .sidebar {
        position: fixed;
        left: -100%;
      }
      .sidebar.show {
        left: 0;
      }
      .sidebar-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1025;
        display: none;
      }
      .sidebar-backdrop.show {
        display: block;
      }
    }
  </style>
</head>
<body>

  <div class="app-wrapper">

    <!-- Backdrop untuk Mobile -->
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <!-- SIDEBAR NAVIGASI -->
    <aside id="sidebar" class="sidebar">
      <div class="sidebar-header">
        <i class="bi bi-moon-stars-fill text-warning fs-3"></i>
        <div class="overflow-hidden">
          <div class="fw-bold text-white text-truncate" id="sidebarMasjidName"><?= htmlspecialchars($masjid['nama'] ?? 'Layar Masjid') ?></div>
          <span class="badge-masjid-id">ID: <?= htmlspecialchars($current_masjid_id) ?></span>
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
      </nav>

      <div class="sidebar-footer">
        <a href="display/<?= htmlspecialchars($current_masjid_id) ?>" target="_blank" class="btn btn-masjid w-100 mb-2 shadow-sm">
          <i class="bi bi-tv me-1"></i> Buka Layar TV
        </a>
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

        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted d-none d-sm-inline">Pengurus DKM: <strong><?= htmlspecialchars($current_masjid_nama) ?></strong></span>
          <a href="display/<?= htmlspecialchars($current_masjid_id) ?>" target="_blank" class="btn btn-sm btn-masjid d-none d-sm-inline-flex">
            <i class="bi bi-box-arrow-up-right me-1"></i> Pratinjau TV
          </a>
        </div>
      </header>

      <!-- Content Container -->
      <main class="p-3 p-md-4">
