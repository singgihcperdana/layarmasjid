<?php
/**
 * Halaman Onboarding Pendaftaran Masjid Baru (Google Sign-In)
 * File: admin/onboarding.php
 */

require_once __DIR__ . '/../api/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya bisa diakses jika ada pending registrasi dari Google
if (!isset($_SESSION['pending_google_user'])) {
    header("Location: login");
    exit;
}

$pending = $_SESSION['pending_google_user'];
$email     = $pending['email'];
$google_id = $pending['google_id'];
$nama      = $pending['nama'];
$avatar    = $pending['avatar'];

$error = '';

// Ambil daftar 38 Provinsi (A-Z)
$stmtProv = $pdo->query("SELECT id, name FROM provinces ORDER BY name ASC");
$provinces = $stmtProv->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_masjid = trim($_POST['nama_masjid'] ?? '');
    $province_id = trim($_POST['province_id'] ?? '');
    $regency_id  = trim($_POST['regency_id'] ?? '');

    if (empty($nama_masjid)) {
        $error = 'Nama masjid wajib diisi.';
    } elseif (empty($province_id)) {
        $error = 'Silakan pilih Provinsi masjid Anda.';
    } elseif (empty($regency_id)) {
        $error = 'Silakan pilih Kabupaten / Kota masjid Anda.';
    } else {
        try {
            // Ambil koordinat default kabupaten/kota terpilih
            $stmtReg = $pdo->prepare("SELECT name, latitude, longitude FROM regencies WHERE id = ? AND province_id = ? LIMIT 1");
            $stmtReg->execute([$regency_id, $province_id]);
            $regRow = $stmtReg->fetch();

            $lat = $regRow ? (float)$regRow['latitude'] : -6.140000;
            $lng = $regRow ? (float)$regRow['longitude'] : 106.590000;
            $regName = $regRow ? $regRow['name'] : '';

            // 1. Generate ID Masjid 6 digit: PP (Provinsi) + 4 digit acak
            $new_masjid_id = generate_masjid_id($province_id);

            // 2. Simpan Masjid baru (Terkunci dengan province_id & regency_id)
            $stmtNewMasjid = $pdo->prepare("
                INSERT INTO masjid (id, nama, lokasi, province_id, regency_id, latitude, longitude, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmtNewMasjid->execute([$new_masjid_id, $nama_masjid, $regName, $province_id, $regency_id, $lat, $lng]);

            // 3. Simpan default schedule
            $pdo->prepare("
                INSERT INTO schedule (masjid_id, latitude, longitude, timezone, hijri_offset, calc_method, calc_fajr_angle, calc_isha_angle, calc_asr_method, tune_fajr, tune_sunrise, tune_dhuhr, tune_asr, tune_maghrib, tune_isha)
                VALUES (?, ?, ?, 7, 0, 'Kemenag', 20.0, 18.0, 'Standard', 1, 0, 0, 2, 6, 1)
            ")->execute([$new_masjid_id, $lat, $lng]);

            // 4. Simpan default timer
            $pdo->prepare("
                INSERT INTO timer (masjid_id, timer_wait_adzan, timer_adzan, timer_sholat, countdown_beep, kecepatan_running_text, iqomah_fajr, iqomah_dhuhr, iqomah_asr, iqomah_maghrib, iqomah_isha)
                VALUES (?, 1, 5, 10, 10, 5, 10, 10, 10, 5, 10)
            ")->execute([$new_masjid_id]);

            // 5. Simpan User admin baru
            $stmtInsertUser = $pdo->prepare("
                INSERT INTO users (masjid_id, email, google_id, nama, avatar, role, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'admin', NOW(), NOW())
            ");
            $stmtInsertUser->execute([$new_masjid_id, $email, $google_id, $nama, $avatar]);
            $new_user_id = $pdo->lastInsertId();

            // 6. Update file statis sinkronisasi display
            update_sync_file($new_masjid_id);

            // 7. Hapus penampung pending & set sesi login resmi
            unset($_SESSION['pending_google_user']);

            $_SESSION['user_id']     = (int)$new_user_id;
            $_SESSION['email']       = $email;
            $_SESSION['user_nama']   = $nama;
            $_SESSION['user_role']   = 'admin';
            $_SESSION['masjid_id']   = $new_masjid_id;
            $_SESSION['masjid_nama'] = $nama_masjid;

            header("Location: profile");
            exit;

        } catch (Exception $e) {
            $error = 'Terjadi kesalahan saat mendaftarkan masjid: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lengkapi Data Masjid - Layar Masjid</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= asset_v('assets/css/auth.css') ?>">
  <style>
    .onboarding-card {
      width: 100%;
      max-width: 520px;
      padding: 32px;
      border-radius: 18px;
      background: rgba(14, 38, 29, 0.94);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(240, 201, 106, 0.25);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
      color: #fff;
      margin: 30px auto;
    }
    .btn-gold:disabled {
      background: rgba(255, 255, 255, 0.12) !important;
      color: rgba(255, 255, 255, 0.35) !important;
      cursor: not-allowed !important;
      transform: none !important;
      box-shadow: none !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-4">

  <div class="onboarding-card">
    <div class="text-center mb-4">
      <div class="brand-icon mb-2">
        <i class="bi bi-building-check"></i>
      </div>
      <h4 class="fw-bold mb-1" style="color: #f0c96a;">Lengkapi Data Masjid</h4>
      <p class="text-white-50 small mb-0">Tentukan wilayah masjid Anda</p>
    </div>

    <!-- Informasi Profil Google User -->
    <div class="d-flex align-items-center gap-3 p-2 px-3 rounded-3 mb-3 border border-secondary bg-dark bg-opacity-50">
      <?php if (!empty($avatar)): ?>
        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="rounded-circle" width="40" height="40">
      <?php else: ?>
        <i class="bi bi-person-circle fs-3 text-white-50"></i>
      <?php endif; ?>
      <div class="overflow-hidden">
        <div class="fw-semibold text-truncate small"><?= htmlspecialchars($nama) ?></div>
        <div class="text-white-50 text-truncate" style="font-size: 0.78rem;"><?= htmlspecialchars($email) ?></div>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 px-3 rounded-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="nama_masjid" class="form-label text-light small fw-semibold">Nama Masjid</label>
        <div class="input-group">
          <span class="input-group-text bg-transparent text-white-50 border-secondary"><i class="bi bi-building"></i></span>
          <input type="text" class="form-control" id="nama_masjid" name="nama_masjid" value="<?= htmlspecialchars($_POST['nama_masjid'] ?? ('Masjid ' . $nama)) ?>" placeholder="Contoh: Masjid Al Ikhlas" required>
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-sm-6">
          <label for="selectProvince" class="form-label text-light small fw-semibold">Provinsi <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" id="selectProvince" name="province_id" required>
            <option value="">-- Pilih Provinsi --</option>
            <?php foreach ($provinces as $prov): ?>
              <option value="<?= $prov['id'] ?>" <?= (($_POST['province_id'] ?? '') === $prov['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($prov['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6">
          <label for="selectRegency" class="form-label text-light small fw-semibold">Kabupaten / Kota <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" id="selectRegency" name="regency_id" required disabled>
            <option value="">-- Pilih Kab / Kota --</option>
          </select>
        </div>
      </div>

      <div class="alert alert-warning py-2 px-3 rounded-3 mb-4" style="font-size: 0.8rem; background-color: rgba(255, 193, 7, 0.15); border-color: rgba(255, 193, 7, 0.3); color: #ffe69c;">
        <i class="bi bi-lock-fill me-1"></i> <strong>Perhatian:</strong> Wilayah Provinsi &amp; Kabupaten/Kota tidak dapat diubah setelah didaftarkan.
      </div>

      <button type="submit" id="btnSubmitOnboarding" class="btn btn-gold w-100 py-3 fs-6 shadow" disabled>
        <i class="bi bi-check-circle-fill me-1"></i> Daftarkan Masjid &amp; Mulai
      </button>
    </form>
  </div>

  <script>
    const inputNama = document.getElementById('nama_masjid');
    const selectProv = document.getElementById('selectProvince');
    const selectReg = document.getElementById('selectRegency');
    const btnSubmit = document.getElementById('btnSubmitOnboarding');

    function checkFormValidity() {
      const isNamaFilled = inputNama && inputNama.value.trim().length > 0;
      const isProvFilled = selectProv && selectProv.value !== '';
      const isRegFilled  = selectReg && selectReg.value !== '';

      if (isNamaFilled && isProvFilled && isRegFilled) {
        btnSubmit.disabled = false;
      } else {
        btnSubmit.disabled = true;
      }
    }

    inputNama.addEventListener('input', checkFormValidity);
    selectProv.addEventListener('change', async function() {
      const provId = this.value;
      selectReg.innerHTML = '<option value="">-- Memuat Kab / Kota... --</option>';
      selectReg.disabled = true;
      checkFormValidity();

      if (!provId) {
        selectReg.innerHTML = '<option value="">-- Pilih Provinsi Dahulu --</option>';
        checkFormValidity();
        return;
      }

      try {
        const res = await fetch(`api/get_regions.php?type=regencies&province_id=${encodeURIComponent(provId)}`);
        const json = await res.json();
        if (json.status === 'success' && Array.isArray(json.data)) {
          let opts = '<option value="">-- Pilih Kab / Kota --</option>';
          json.data.forEach(item => {
            opts += `<option value="${item.id}">${item.name}</option>`;
          });
          selectReg.innerHTML = opts;
          selectReg.disabled = false;
        } else {
          selectReg.innerHTML = '<option value="">Gagal memuat data</option>';
        }
      } catch (err) {
        selectReg.innerHTML = '<option value="">Gagal memuat data</option>';
      }
      checkFormValidity();
    });

    selectReg.addEventListener('change', checkFormValidity);

    // Jalankan pengecekan saat pertama kali halaman dimuat
    document.addEventListener('DOMContentLoaded', checkFormValidity);
  </script>
</body>
</html>
