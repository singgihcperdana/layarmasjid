<?php
/**
 * Halaman Pengaturan Jadwal Sholat & Simulasi Bulanan
 * File: schedule.php (URL: /schedule)
 */
$page_title   = "Jadwal Sholat";
$current_page = "schedule";

require_once __DIR__ . '/includes/header.php';

// Ambil Pengaturan Jadwal & Koordinat dari Tabel schedule
$stmt = $pdo->prepare("SELECT * FROM schedule WHERE masjid_id = ? LIMIT 1");
$stmt->execute([$current_masjid_id]);
$setting = $stmt->fetch() ?: [
    'latitude' => -6.140000, 'longitude' => 106.590000, 'timezone' => 7, 'hijri_offset' => 0,
    'calc_method' => 'Kemenag', 'calc_fajr_angle' => 20.0, 'calc_isha_angle' => 18.0, 'calc_asr_method' => 'Standard',
    'tune_fajr' => 1, 'tune_sunrise' => 0, 'tune_dhuhr' => 0, 'tune_asr' => 2, 'tune_maghrib' => 6, 'tune_isha' => 1,
    'imsak_mode' => 'auto', 'imsak_minutes' => 10
];

$tune_configs = [
    'tune_fajr'    => ['label' => 'Subuh',   'val' => (int)$setting['tune_fajr']],
    'tune_sunrise' => ['label' => 'Syuruq',  'val' => (int)$setting['tune_sunrise']],
    'tune_dhuhr'   => ['label' => 'Dzuhur',  'val' => (int)$setting['tune_dhuhr']],
    'tune_asr'     => ['label' => 'Ashar',   'val' => (int)$setting['tune_asr']],
    'tune_maghrib' => ['label' => 'Maghrib', 'val' => (int)$setting['tune_maghrib']],
    'tune_isha'    => ['label' => 'Isya\'',  'val' => (int)$setting['tune_isha']],
];

$latitude  = (float)($setting['latitude'] ?? -6.14);
$longitude = (float)($setting['longitude'] ?? 106.59);
?>

<!-- Schedule CSS -->
<link rel="stylesheet" href="<?= asset_v('assets/css/schedule.css') ?>">

<!-- Banner Ringkasan Lokasi Koordinat Masjid -->
<div class="card mb-4 bg-light border shadow-sm">
  <div class="card-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="d-flex align-items-center gap-2 text-dark">
      <i class="bi bi-geo-alt-fill text-success fs-5"></i>
      <span class="fw-semibold">Titik Koordinat Masjid:</span>
      <span class="badge bg-white text-dark border px-2 py-1 font-monospace"><?= htmlspecialchars($latitude) ?>, <?= htmlspecialchars($longitude) ?></span>
      <span class="text-muted small ms-1">(UTC+<?= (int)$setting['timezone'] ?>)</span>
    </div>
    <a href="profile" class="btn btn-sm btn-outline-success fw-semibold">
      <i class="bi bi-pencil-square me-1"></i> Ubah Lokasi di Halaman Profil
    </a>
  </div>
</div>

<form id="formSaveSchedule">
  <!-- Hidden inputs koordinat & zona waktu -->
  <input type="hidden" name="latitude" id="inputLat" value="<?= htmlspecialchars($latitude) ?>">
  <input type="hidden" name="longitude" id="inputLng" value="<?= htmlspecialchars($longitude) ?>">
  <input type="hidden" name="timezone" value="<?= htmlspecialchars($setting['timezone'] ?? 7) ?>">
  <input type="hidden" name="hijri_offset" value="<?= htmlspecialchars($setting['hijri_offset'] ?? 0) ?>">

  <div class="row">
    
    <!-- KOLOM KIRI: PARAMETER HISAB & PENGATURAN IMSAK -->
    <div class="col-lg-6 mb-4">
      
      <!-- KARTU 1: PARAMETER HISAB & SUDUT WAKTU SHOLAT -->
      <div class="card mb-4 shadow-sm">
        <div class="card-header">
          <i class="bi bi-gear-fill me-1"></i> Parameter Hisab &amp; Sudut Waktu Sholat
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Sudut Fajar / Subuh</label>
              <select class="form-select form-select-sm" id="calcFajr" name="calc_fajr_angle">
                <option value="20" <?= ((float)$setting['calc_fajr_angle'] == 20.0) ? 'selected' : '' ?>>20° (Kemenag RI)</option>
                <option value="18" <?= ((float)$setting['calc_fajr_angle'] == 18.0) ? 'selected' : '' ?>>18° (Kemenag Lama / MWL)</option>
                <option value="19.5" <?= ((float)$setting['calc_fajr_angle'] == 19.5) ? 'selected' : '' ?>>19.5° (Umm Al-Qura)</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Sudut Isya</label>
              <select class="form-select form-select-sm" id="calcIsha" name="calc_isha_angle">
                <option value="18" <?= ((float)$setting['calc_isha_angle'] == 18.0) ? 'selected' : '' ?>>18° (Kemenag RI)</option>
                <option value="17" <?= ((float)$setting['calc_isha_angle'] == 17.0) ? 'selected' : '' ?>>17° (MWL)</option>
                <option value="15" <?= ((float)$setting['calc_isha_angle'] == 15.0) ? 'selected' : '' ?>>15° (ISNA)</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Perhitungan Waktu Ashar</label>
              <select class="form-select form-select-sm" id="calcAsr" name="calc_asr_method">
                <option value="Standard" <?= ($setting['calc_asr_method'] === 'Standard') ? 'selected' : '' ?>>Standar (Syafi'i, Maliki, Hanbali - Bayangan 1x)</option>
                <option value="Hanafi" <?= ($setting['calc_asr_method'] === 'Hanafi') ? 'selected' : '' ?>>Hanafi (Bayangan 2x)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- KARTU 2: PENGATURAN WAKTU IMSAK -->
      <div class="card shadow-sm">
        <div class="card-header">
          <i class="bi bi-moon-stars-fill text-warning me-1"></i> Pengaturan Waktu Imsak
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mode Tampilan Waktu Imsak</label>
            <select class="form-select form-select-sm" id="imsakMode" name="imsak_mode">
              <option value="auto" <?= (($setting['imsak_mode'] ?? 'auto') === 'auto') ? 'selected' : '' ?>>Otomatis — Hanya muncul saat bulan Ramadan (Disarankan)</option>
              <option value="always" <?= (($setting['imsak_mode'] ?? '') === 'always') ? 'selected' : '' ?>>Selalu Tampil — Sepanjang tahun</option>
              <option value="off" <?= (($setting['imsak_mode'] ?? '') === 'off') ? 'selected' : '' ?>>Nonaktif — Sembunyikan waktu Imsak</option>
            </select>
          </div>
          <div id="boxImsakMinutes" style="<?= (($setting['imsak_mode'] ?? 'auto') === 'off') ? 'display: none;' : '' ?>">
            <label class="form-label small fw-semibold">Jeda Waktu Imsak Sebelum Subuh</label>
            <div class="input-group input-group-sm" style="max-width: 200px;">
              <input type="number" class="form-control text-center fw-bold" name="imsak_minutes" id="imsakMinutes" min="1" max="60" value="<?= (int)($setting['imsak_minutes'] ?? 10) ?>" required>
              <span class="input-group-text">Menit</span>
            </div>
            <div class="form-text" style="font-size: 0.78rem;">
              <i class="bi bi-info-circle me-1"></i> Standar Kemenag RI adalah 10 menit sebelum Subuh. Anda dapat men-tune jeda ini sesuai kebutuhan masjid.
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- KOLOM KANAN: KOREKSI MENIT (IHTIYAT) & TOMBOL SIMPAN -->
    <div class="col-lg-6 mb-4">
      
      <!-- KARTU 3: KOREKSI MENIT IHTIYAT -->
      <div class="card mb-4 shadow-sm">
        <div class="card-header">
          <i class="bi bi-sliders2 me-1"></i> Koreksi Menit Waktu Sholat (+ / - Ihtiyat)
        </div>
        <div class="card-body">
          <p class="small text-muted mb-3" style="font-size: 0.82rem;">Sesuaikan jika ada perbedaan menit dengan kalender rujukan lokal setempat.</p>
          <div class="row g-3">
            <?php foreach ($tune_configs as $field => $cfg): 
              $sign = ($cfg['val'] < 0) ? '-' : '+';
              $abs_val = abs($cfg['val']);
            ?>
            <div class="col-6">
              <label class="form-label small fw-semibold mb-1"><?= $cfg['label'] ?></label>
              <div class="input-group input-group-sm">
                <button type="button" class="btn btn-sm tune-toggle-btn <?= $sign === '-' ? 'btn-tune-minus' : 'btn-tune-plus' ?>" data-target="<?= $field ?>" title="Klik untuk beralih antara + dan -">
                  <?= $sign ?>
                </button>
                <input type="number" class="form-control text-center tune-num" min="0" max="60" value="<?= $abs_val ?>" data-target="<?= $field ?>">
                <span class="input-group-text tune-unit">menit</span>
                <input type="hidden" name="<?= $field ?>" id="<?= $field ?>" class="tune-input" value="<?= $cfg['val'] ?>">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Tombol Simpan Pengaturan -->
      <button type="submit" class="btn btn-masjid w-100 py-3 fs-6 shadow">
        <i class="bi bi-floppy-fill me-1"></i> Simpan Pengaturan Jadwal
      </button>

    </div>

    <!-- KARTU 4: SIMULASI JADWAL SHOLAT BULANAN -->
    <div class="col-12 mb-4">
      <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <div>
              <h5 class="mb-0 fw-bold" style="color: #0a4d3a; font-size: 1.25rem;">Simulasi Jadwal Sholat Bulanan</h5>
            </div>

            <!-- Tombol Navigasi Bulan & Display -->
            <div class="d-flex align-items-center gap-2">
              <button type="button" id="btnPrevMonth" class="month-nav-btn shadow-sm">
                <i class="bi bi-chevron-left me-1"></i> Prev
              </button>
              <span id="currentMonthLabel" class="month-display-card">
                November 2026
              </span>
              <button type="button" id="btnNextMonth" class="month-nav-btn shadow-sm">
                Next <i class="bi bi-chevron-right ms-1"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-monthly-wrap">
            <table class="table table-bordered table-hover table-striped table-monthly mb-0">
              <thead class="table-monthly-header">
                <tr>
                  <th style="width: 80px;">Tgl</th>
                  <th>Imsak</th>
                  <th>Subuh</th>
                  <th>Syuruq</th>
                  <th>Dzuhur</th>
                  <th>Ashar</th>
                  <th>Maghrib</th>
                  <th>Isya'</th>
                </tr>
              </thead>
              <tbody id="monthlyTableBody">
                <!-- Baris tanggal 1 s.d. 30/31 akan digenerate otomatis oleh PrayTimes.js -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</form>

<!-- PrayTimes Library -->
<script src="display/assets/js/PrayTimes.js"></script>

<script>
  const namaBulanIndo = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
  ];

  let currentViewDate = new Date();

  // Render Simulasi Jadwal Sholat Bulanan
  function renderMonthlySchedule() {
    const lat = parseFloat(document.getElementById('inputLat').value) || -6.14;
    const lng = parseFloat(document.getElementById('inputLng').value) || 106.59;

    const fajrAngle = parseFloat(document.getElementById('calcFajr').value) || 20;
    const ishaAngle = parseFloat(document.getElementById('calcIsha').value) || 18;
    const asrMethod = document.getElementById('calcAsr').value || 'Standard';
    const imsakMinutesInput = document.getElementById('imsakMinutes');
    const imsakMinutes = imsakMinutesInput ? (parseInt(imsakMinutesInput.value) || 10) : 10;

    const tuneFajr    = parseInt(document.querySelector('input[name="tune_fajr"]').value) || 0;
    const tuneSunrise = parseInt(document.querySelector('input[name="tune_sunrise"]').value) || 0;
    const tuneDhuhr   = parseInt(document.querySelector('input[name="tune_dhuhr"]').value) || 0;
    const tuneAsr     = parseInt(document.querySelector('input[name="tune_asr"]').value) || 0;
    const tuneMaghrib = parseInt(document.querySelector('input[name="tune_maghrib"]').value) || 0;
    const tuneIsha    = parseInt(document.querySelector('input[name="tune_isha"]').value) || 0;

    // Inisialisasi engine PrayTimes
    const pt = new PrayTimes('MWL');
    pt.adjust({
      imsak: `${imsakMinutes} min`,
      fajr: fajrAngle,
      isha: ishaAngle,
      asr: asrMethod
    });
    pt.tune({
      fajr: tuneFajr,
      sunrise: tuneSunrise,
      dhuhr: tuneDhuhr,
      asr: tuneAsr,
      maghrib: tuneMaghrib,
      isha: tuneIsha
    });

    const year = currentViewDate.getFullYear();
    const month = currentViewDate.getMonth(); // 0 - 11

    // Update Label Bulan (e.g. "November 2026")
    document.getElementById('currentMonthLabel').textContent = `${namaBulanIndo[month]} ${year}`;

    // Cari jumlah hari dalam bulan ini
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const today = new Date();
    const isCurrentMonthYear = today.getFullYear() === year && today.getMonth() === month;
    const todayDate = today.getDate();

    const tbody = document.getElementById('monthlyTableBody');
    let html = '';

    for (let d = 1; d <= daysInMonth; d++) {
      const dateObj = new Date(year, month, d);
      const times = pt.getTimes(dateObj, [lat, lng], 'auto');
      const isToday = isCurrentMonthYear && d === todayDate;

      html += `
        <tr class="${isToday ? 'row-today' : ''}">
          <td>
            ${isToday ? `
              <span class="today-badge shadow-sm">
                ${d < 10 ? '0' + d : d}
                <span class="today-tag">Hari Ini</span>
              </span>
            ` : `
              <span class="text-secondary fw-semibold">${d < 10 ? '0' + d : d}</span>
            `}
          </td>
          <td class="fw-semibold text-warning-emphasis bg-warning-subtle bg-opacity-25">${times.imsak || '--:--'}</td>
          <td>${times.fajr || '--:--'}</td>
          <td>${times.sunrise || '--:--'}</td>
          <td>${times.dhuhr || '--:--'}</td>
          <td>${times.asr || '--:--'}</td>
          <td>${times.maghrib || '--:--'}</td>
          <td>${times.isha || '--:--'}</td>
        </tr>
      `;
    }

    tbody.innerHTML = html;
  }

  // Navigasi Bulan (Prev / Next)
  document.getElementById('btnPrevMonth').addEventListener('click', function() {
    currentViewDate.setMonth(currentViewDate.getMonth() - 1);
    renderMonthlySchedule();
  });

  document.getElementById('btnNextMonth').addEventListener('click', function() {
    currentViewDate.setMonth(currentViewDate.getMonth() + 1);
    renderMonthlySchedule();
  });

  // Re-render saat ada perubahan input hisab atau jeda imsak
  ['calcFajr', 'calcIsha', 'calcAsr', 'imsakMinutes'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('change', renderMonthlySchedule);
      el.addEventListener('input', renderMonthlySchedule);
    }
  });

  // Toggle Tanda (+ / -) 1-Klik
  document.querySelectorAll('.tune-toggle-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const field = this.dataset.target;
      const currentSign = this.textContent.trim();
      const newSign = (currentSign === '+') ? '-' : '+';
      
      this.textContent = newSign;
      if (newSign === '-') {
        this.classList.remove('btn-tune-plus');
        this.classList.add('btn-tune-minus');
      } else {
        this.classList.remove('btn-tune-minus');
        this.classList.add('btn-tune-plus');
      }

      updateTuneHidden(field);
    });
  });

  // Sinkronisasi tanda (+/-) dan angka menit ke input hidden & render simulasi
  function updateTuneHidden(field) {
    const btn = document.querySelector(`.tune-toggle-btn[data-target="${field}"]`);
    const sign = btn ? btn.textContent.trim() : '+';
    const inputNum = document.querySelector(`.tune-num[data-target="${field}"]`);
    const num = Math.abs(parseInt(inputNum ? inputNum.value : 0) || 0);
    const hidden = document.getElementById(field);
    if (hidden) {
      hidden.value = (sign === '-' ? -1 : 1) * num;
    }
    renderMonthlySchedule();
  }

  document.querySelectorAll('.tune-num').forEach(input => {
    input.addEventListener('input', function() {
      updateTuneHidden(this.dataset.target);
    });
    input.addEventListener('change', function() {
      updateTuneHidden(this.dataset.target);
    });
  });

  // Toggle Tampilan Input Jeda Imsak
  const imsakModeSelect = document.getElementById('imsakMode');
  const boxImsakMinutes = document.getElementById('boxImsakMinutes');
  if (imsakModeSelect && boxImsakMinutes) {
    imsakModeSelect.addEventListener('change', function() {
      boxImsakMinutes.style.display = (this.value === 'off') ? 'none' : 'block';
    });
  }

  // Simpan Pengaturan via AJAX
  document.getElementById('formSaveSchedule').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
      const res = await fetch('api/save_schedule.php', { method: 'POST', body: formData });
      const json = await res.json();
      if (json.status === 'success') {
        showAlert('success', json.message);
      } else {
        showAlert('danger', json.message);
      }
    } catch (err) {
      showAlert('danger', 'Gagal menyimpan pengaturan jadwal.');
    }
  });

  // Inisialisasi saat halaman dimuat
  window.addEventListener('DOMContentLoaded', () => {
    renderMonthlySchedule();
  });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
