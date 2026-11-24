<?php
/**
 * Halaman Pengaturan Jadwal Sholat, Koordinat Peta & Simulasi Bulanan
 * File: schedule.php (URL: /schedule)
 */
$page_title   = "Jadwal Sholat";
$current_page = "schedule";

require_once __DIR__ . '/includes/header.php';

// Ambil Pengaturan Jadwal & Koordinat
$stmt = $pdo->prepare("SELECT * FROM masjid_setting WHERE masjid_id = ? LIMIT 1");
$stmt->execute([$current_masjid_id]);
$setting = $stmt->fetch() ?: [
    'tune_fajr' => 1, 'tune_sunrise' => 0, 'tune_dhuhr' => 0, 'tune_asr' => 2, 'tune_maghrib' => 6, 'tune_isha' => 1,
    'calc_fajr_angle' => 20.0, 'calc_isha_angle' => 18.0, 'calc_asr_method' => 'Standard'
];

$latitude  = (float)($masjid['latitude'] ?? -6.14);
$longitude = (float)($masjid['longitude'] ?? 106.59);
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
  #map {
    height: 300px;
    width: 100%;
    border-radius: 12px;
    border: 1px solid #ced4da;
    z-index: 1;
  }
  .table-monthly-header th {
    background-color: #0a4d3a !important;
    color: #ffffff !important;
    text-align: center;
    font-weight: 700;
    padding: 12px 8px;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    border: none;
    border-bottom: 2px solid #d4af37 !important;
  }
  .table-monthly td {
    text-align: center;
    padding: 9px 8px;
    font-size: 0.88rem;
    vertical-align: middle;
  }
  .table-monthly tr.row-today td {
    background-color: #fbf8eb !important;
    border-top: 1.5px solid #d4af37 !important;
    border-bottom: 1.5px solid #d4af37 !important;
    color: #0a4d3a;
    font-weight: 700;
  }
  .table-monthly tr.row-today td:first-child {
    border-left: 4px solid #0a4d3a !important;
  }
  .today-badge {
    background-color: #0a4d3a;
    color: #ffffff;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .today-tag {
    font-size: 0.68rem;
    background: #d4af37;
    color: #051f18;
    padding: 1px 5px;
    border-radius: 4px;
    font-weight: 800;
    letter-spacing: 0.4px;
    text-transform: uppercase;
  }
  .month-nav-btn {
    background: #ffffff;
    color: #0a4d3a;
    border: 1.5px solid #0a4d3a;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 8px;
    font-size: 0.88rem;
    transition: all 0.2s ease;
  }
  .month-nav-btn:hover {
    background: #0a4d3a;
    color: #ffffff;
    border-color: #0a4d3a;
  }
  .month-display-card {
    background: linear-gradient(135deg, #051f18 0%, #0a4d3a 100%);
    color: #ffffff;
    font-weight: 700;
    padding: 7px 20px;
    border-radius: 8px;
    font-size: 0.92rem;
    display: inline-flex;
    align-items: center;
    border: 1px solid rgba(212, 175, 55, 0.4);
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
  }
</style>

<form id="formSaveSchedule">
  <div class="row">
    
    <!-- KARTU 1: PETA & TITIK KOORDINAT -->
    <div class="col-lg-7 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-geo-alt-fill text-danger me-1"></i> Titik Koordinat & Lokasi Masjid</span>
          <button type="button" id="btnGetGps" class="btn btn-sm btn-outline-success fw-semibold">
            <i class="bi bi-crosshair me-1"></i> 📍 Gunakan GPS Saya
          </button>
        </div>
        <div class="card-body">
          
          <!-- Pencarian Alamat -->
          <div class="mb-3">
            <div class="input-group">
              <input type="text" id="mapSearchInput" class="form-control" placeholder="Ketik nama jalan, kelurahan, atau kota...">
              <button class="btn btn-secondary" type="button" id="btnSearchMap">
                <i class="bi bi-search me-1"></i> Cari
              </button>
            </div>
            <div id="searchSpinner" class="form-text d-none text-primary"><i class="bi bi-arrow-repeat spin"></i> Mencari lokasi...</div>
          </div>

          <!-- Peta Leaflet -->
          <div id="map" class="mb-3 shadow-sm"></div>

          <!-- Input Latitude & Longitude -->
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small fw-semibold">Latitude (Garis Lintang)</label>
              <input type="number" step="0.000001" class="form-control" id="inputLat" name="latitude" value="<?= htmlspecialchars($latitude) ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Longitude (Garis Bujur)</label>
              <input type="number" step="0.000001" class="form-control" id="inputLng" name="longitude" value="<?= htmlspecialchars($longitude) ?>" required>
            </div>
          </div>
          <div class="form-text mt-2" style="font-size: 0.8rem;">
            <i class="bi bi-info-circle me-1"></i> Geser pin merah di peta tepat ke atap masjid Anda untuk mengunci koordinat.
          </div>

        </div>
      </div>
    </div>

    <!-- KARTU 2 & 3: METODE KEMENAG & KOREKSI MENIT -->
    <div class="col-lg-5 mb-4">
      
      <!-- KARTU 2: METODE PERHITUNGAN KEMENAG RI -->
      <div class="card mb-4">
        <div class="card-header">
          <i class="bi bi-gear-fill me-1"></i> Metode Perhitungan (Kemenag RI)
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

      <!-- KARTU 3: KOREKSI MENIT IHTIYAT -->
      <div class="card mb-4">
        <div class="card-header">
          <i class="bi bi-sliders2 me-1"></i> Koreksi Menit Waktu Sholat (+ / - Ihtiyat)
        </div>
        <div class="card-body">
          <p class="small text-muted mb-3" style="font-size: 0.82rem;">Sesuaikan jika ada perbedaan menit dengan kalender rujukan lokal setempat.</p>
          <div class="row g-2">
            <div class="col-4">
              <label class="form-label small fw-semibold">Subuh</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center tune-input" name="tune_fajr" value="<?= (int)$setting['tune_fajr'] ?>">
                <span class="input-group-text">m</span>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Syuruq</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center tune-input" name="tune_sunrise" value="<?= (int)$setting['tune_sunrise'] ?>">
                <span class="input-group-text">m</span>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Dzuhur</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center tune-input" name="tune_dhuhr" value="<?= (int)$setting['tune_dhuhr'] ?>">
                <span class="input-group-text">m</span>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Ashar</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center tune-input" name="tune_asr" value="<?= (int)$setting['tune_asr'] ?>">
                <span class="input-group-text">m</span>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Maghrib</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center tune-input" name="tune_maghrib" value="<?= (int)$setting['tune_maghrib'] ?>">
                <span class="input-group-text">m</span>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label small fw-semibold">Isya'</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center tune-input" name="tune_isha" value="<?= (int)$setting['tune_isha'] ?>">
                <span class="input-group-text">m</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tombol Simpan -->
      <button type="submit" class="btn btn-masjid w-100 py-3 fs-6 shadow">
        <i class="bi bi-floppy-fill me-1"></i> Simpan Pengaturan Jadwal
      </button>

    </div>

    <!-- KARTU 4: SIMULASI JADWAL SHOLAT BULANAN -->
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header bg-white py-3">
          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <div>
              <h6 class="mb-0 fw-bold text-success"><i class="bi bi-calendar3 me-1"></i> Simulasi Jadwal Sholat Bulanan</h6>
              <div class="text-muted small mt-1">Jadwal otomatis berubah realtime saat Anda menggeser peta atau mengubah menit koreksi.</div>
            </div>

            <!-- Tombol Navigasi Bulan & Display -->
            <div class="d-flex align-items-center gap-2">
              <button type="button" id="btnPrevMonth" class="month-nav-btn shadow-sm">
                <i class="bi bi-chevron-left me-1"></i> Prev
              </button>
              <span id="currentMonthLabel" class="month-display-card">
                <i class="bi bi-calendar3 me-2" style="color: #e5c05b;"></i> November 2026
              </span>
              <button type="button" id="btnNextMonth" class="month-nav-btn shadow-sm">
                Next <i class="bi bi-chevron-right ms-1"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped table-monthly mb-0">
              <thead class="table-monthly-header">
                <tr>
                  <th style="width: 80px;">Tgl</th>
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

<!-- Leaflet JS & PrayTimes Library -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="display/assets/js/PrayTimes.js"></script>

<script>
  const namaBulanIndo = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
  ];

  let currentViewDate = new Date();
  let map, marker;

  // 1. Inisialisasi Peta Leaflet
  function initMap() {
    let lat = parseFloat(document.getElementById('inputLat').value) || -6.14;
    let lng = parseFloat(document.getElementById('inputLng').value) || 106.59;

    map = L.map('map').setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker.on('dragend', function (e) {
      const position = marker.getLatLng();
      document.getElementById('inputLat').value = position.lat.toFixed(6);
      document.getElementById('inputLng').value = position.lng.toFixed(6);
      renderMonthlySchedule();
    });

    map.on('click', function(e) {
      marker.setLatLng(e.latlng);
      document.getElementById('inputLat').value = e.latlng.lat.toFixed(6);
      document.getElementById('inputLng').value = e.latlng.lng.toFixed(6);
      renderMonthlySchedule();
    });
  }

  // 2. Fitur Pencarian Lokasi (Nominatim OSM)
  document.getElementById('btnSearchMap').addEventListener('click', async function() {
    const query = document.getElementById('mapSearchInput').value.trim();
    if (!query) return;

    const spinner = document.getElementById('searchSpinner');
    spinner.classList.remove('d-none');

    try {
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`);
      const data = await res.json();
      if (data && data.length > 0) {
        const lat = parseFloat(data[0].lat);
        const lng = parseFloat(data[0].lon);
        map.setView([lat, lng], 16);
        marker.setLatLng([lat, lng]);
        document.getElementById('inputLat').value = lat.toFixed(6);
        document.getElementById('inputLng').value = lng.toFixed(6);
        renderMonthlySchedule();
      } else {
        alert('Lokasi tidak ditemukan. Coba masukkan nama kota atau kelurahan yang lebih spesifik.');
      }
    } catch (err) {
      alert('Gagal menghubungi layanan pencarian peta.');
    } finally {
      spinner.classList.add('d-none');
    }
  });

  document.getElementById('mapSearchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('btnSearchMap').click();
    }
  });

  // 3. Tombol GPS Perangkat
  document.getElementById('btnGetGps').addEventListener('click', function() {
    if (!navigator.geolocation) {
      alert('Browser Anda tidak mendukung deteksi lokasi GPS.');
      return;
    }
    const btn = this;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Mendeteksi...';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
      function(pos) {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        map.setView([lat, lng], 17);
        marker.setLatLng([lat, lng]);
        document.getElementById('inputLat').value = lat.toFixed(6);
        document.getElementById('inputLng').value = lng.toFixed(6);
        renderMonthlySchedule();
        btn.innerHTML = oldHtml;
        btn.disabled = false;
      },
      function(err) {
        alert('Gagal mendapatkan lokasi GPS. Pastikan izin lokasi (GPS) di browser Anda telah diaktifkan.');
        btn.innerHTML = oldHtml;
        btn.disabled = false;
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  });

  // 4. Render Simulasi Jadwal Sholat Bulanan
  function renderMonthlySchedule() {
    const lat = parseFloat(document.getElementById('inputLat').value) || -6.14;
    const lng = parseFloat(document.getElementById('inputLng').value) || 106.59;

    const fajrAngle = parseFloat(document.getElementById('calcFajr').value) || 20;
    const ishaAngle = parseFloat(document.getElementById('calcIsha').value) || 18;
    const asrMethod = document.getElementById('calcAsr').value || 'Standard';

    const tuneFajr    = parseInt(document.querySelector('input[name="tune_fajr"]').value) || 0;
    const tuneSunrise = parseInt(document.querySelector('input[name="tune_sunrise"]').value) || 0;
    const tuneDhuhr   = parseInt(document.querySelector('input[name="tune_dhuhr"]').value) || 0;
    const tuneAsr     = parseInt(document.querySelector('input[name="tune_asr"]').value) || 0;
    const tuneMaghrib = parseInt(document.querySelector('input[name="tune_maghrib"]').value) || 0;
    const tuneIsha    = parseInt(document.querySelector('input[name="tune_isha"]').value) || 0;

    // Inisialisasi engine PrayTimes
    const pt = new PrayTimes('MWL');
    pt.adjust({
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
    document.getElementById('currentMonthLabel').innerHTML = `<i class="bi bi-calendar3 me-2" style="color: #e5c05b;"></i> ${namaBulanIndo[month]} ${year}`;

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

  // Re-render saat ada perubahan input angka atau pilihan
  ['inputLat', 'inputLng', 'calcFajr', 'calcIsha', 'calcAsr'].forEach(id => {
    document.getElementById(id).addEventListener('change', renderMonthlySchedule);
    document.getElementById(id).addEventListener('input', renderMonthlySchedule);
  });

  document.querySelectorAll('.tune-input').forEach(input => {
    input.addEventListener('change', renderMonthlySchedule);
    input.addEventListener('input', renderMonthlySchedule);
  });

  // 5. Simpan Pengaturan via AJAX
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
    initMap();
    renderMonthlySchedule();
  });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
