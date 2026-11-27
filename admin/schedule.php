<?php
/**
 * Halaman Pengaturan Jadwal Sholat, Koordinat Peta & Simulasi Bulanan
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
    'tune_fajr' => 1, 'tune_sunrise' => 0, 'tune_dhuhr' => 0, 'tune_asr' => 2, 'tune_maghrib' => 6, 'tune_isha' => 1
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

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Schedule CSS -->
<link rel="stylesheet" href="<?= asset_v('assets/css/schedule.css') ?>">

<form id="formSaveSchedule">
  <div class="row">
    
    <!-- KARTU 1: PETA & TITIK KOORDINAT -->
    <div class="col-lg-7 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-geo-alt-fill text-primary me-1"></i> Titik Koordinat & Lokasi Masjid</span>
          <button type="button" id="btnGetGps" class="btn btn-sm btn-outline-success fw-semibold">
            <i class="bi bi-crosshair me-1"></i> 📍 Gunakan GPS Saya
          </button>
        </div>
        <div class="card-body">
          
          <!-- Pencarian Alamat dengan Suggestion Autocomplete (Google Maps style) -->
          <div class="map-search-wrapper mb-3" id="mapSearchWrapper">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-muted"></i></span>
              <input type="text" id="mapSearchInput" class="form-control border-start-0 border-end-0" placeholder="Ketik nama masjid, jalan, kelurahan, atau kota..." autocomplete="off">
              <button class="btn btn-outline-secondary border-start-0 border-end-0 d-none" type="button" id="btnClearSearch" title="Hapus teks pencarian">
                <i class="bi bi-x-lg"></i>
              </button>
              <button class="btn btn-secondary" type="button" id="btnSearchMap">
                <i class="bi bi-search me-1"></i> Cari
              </button>
            </div>
            
            <!-- Dropdown Saran Lokasi -->
            <div id="mapSuggestions" class="map-suggestions d-none"></div>

            <div id="searchSpinner" class="form-text d-none text-primary mt-1">
              <i class="bi bi-arrow-repeat spin"></i> Mencari saran lokasi...
            </div>
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
            <i class="bi bi-info-circle me-1"></i> Geser pin biru di peta tepat ke titik lokasi masjid Anda untuk menentukan koordinat.
          </div>

        </div>
      </div>
    </div>

    <!-- KARTU 2 & 3: METODE KEMENAG & KOREKSI MENIT -->
    <div class="col-lg-5 mb-4">
      
      <!-- KARTU 2: PARAMETER HISAB & SUDUT WAKTU SHOLAT -->
      <div class="card mb-4">
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

      <!-- KARTU 3: KOREKSI MENIT IHTIYAT -->
      <div class="card mb-4">
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

  // 2. Fitur Pencarian Lokasi & Autocomplete Saran (Google Maps Style)
  const searchInput = document.getElementById('mapSearchInput');
  const btnSearch = document.getElementById('btnSearchMap');
  const btnClear = document.getElementById('btnClearSearch');
  const suggestionsBox = document.getElementById('mapSuggestions');
  const searchSpinner = document.getElementById('searchSpinner');

  let debounceTimer = null;
  let activeSuggestionIdx = -1;
  let currentSuggestions = [];

  function selectLocation(lat, lng, name) {
    if (isNaN(lat) || isNaN(lng)) return;

    map.flyTo([lat, lng], 17, { duration: 1.2 });
    marker.setLatLng([lat, lng]);
    document.getElementById('inputLat').value = lat.toFixed(6);
    document.getElementById('inputLng').value = lng.toFixed(6);
    renderMonthlySchedule();

    if (name) {
      searchInput.value = name;
      btnClear.classList.remove('d-none');
    }
    closeSuggestions();
  }

  function closeSuggestions() {
    suggestionsBox.classList.add('d-none');
    suggestionsBox.innerHTML = '';
    currentSuggestions = [];
    activeSuggestionIdx = -1;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // Fetch saran lokasi dari Nominatim OSM
  async function fetchSuggestions(query) {
    if (!query || query.length < 3) {
      closeSuggestions();
      searchSpinner.classList.add('d-none');
      return;
    }

    searchSpinner.classList.remove('d-none');

    try {
      // Prioritaskan pencarian di Indonesia (countrycodes=id)
      const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=id&limit=5&addressdetails=1`;
      const res = await fetch(url, { headers: { 'Accept-Language': 'id-ID,id;q=0.9' } });
      const data = await res.json();

      if (Array.isArray(data) && data.length > 0) {
        currentSuggestions = data;
        renderSuggestions(data);
      } else {
        suggestionsBox.innerHTML = '<div class="map-suggestion-empty"><i class="bi bi-geo-alt me-1"></i> Tidak ada saran lokasi ditemukan. Coba ketik nama kota/kecamatan.</div>';
        suggestionsBox.classList.remove('d-none');
        currentSuggestions = [];
      }
    } catch (err) {
      closeSuggestions();
    } finally {
      searchSpinner.classList.add('d-none');
    }
  }

  function renderSuggestions(items) {
    activeSuggestionIdx = -1;
    suggestionsBox.innerHTML = items.map((item, idx) => {
      const parts = (item.display_name || '').split(',').map(s => s.trim());
      const mainTitle = item.name || parts[0];
      const subTitle = parts.slice(1).join(', ');

      const isMosque = /masjid|musholla|mosque|surau/i.test(mainTitle) || item.type === 'place_of_worship';
      const iconClass = isMosque ? 'bi-building text-success' : 'bi-geo-alt-fill text-danger';

      return `
        <div class="map-suggestion-item" data-idx="${idx}">
          <i class="bi ${iconClass} map-suggestion-icon"></i>
          <div class="map-suggestion-content">
            <div class="map-suggestion-title">${escapeHtml(mainTitle)}</div>
            <div class="map-suggestion-subtitle">${escapeHtml(subTitle)}</div>
          </div>
        </div>
      `;
    }).join('');

    suggestionsBox.classList.remove('d-none');

    // Event click pada item saran
    suggestionsBox.querySelectorAll('.map-suggestion-item').forEach(el => {
      el.addEventListener('click', function() {
        const idx = parseInt(this.dataset.idx, 10);
        const item = currentSuggestions[idx];
        if (item) {
          const mainTitle = item.name || item.display_name.split(',')[0].trim();
          selectLocation(parseFloat(item.lat), parseFloat(item.lon), mainTitle);
        }
      });
    });
  }

  // Event Input (Ketik untuk trigger autocomplete)
  searchInput.addEventListener('input', function() {
    const val = this.value.trim();
    if (val.length > 0) {
      btnClear.classList.remove('d-none');
    } else {
      btnClear.classList.add('d-none');
      closeSuggestions();
      return;
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      fetchSuggestions(val);
    }, 350);
  });

  // Tombol Hapus Input
  btnClear.addEventListener('click', function() {
    searchInput.value = '';
    btnClear.classList.add('d-none');
    closeSuggestions();
    searchInput.focus();
  });

  // Navigasi Keyboard (Panah Atas / Bawah, Enter, Escape)
  searchInput.addEventListener('keydown', function(e) {
    const items = suggestionsBox.querySelectorAll('.map-suggestion-item');

    if (e.key === 'ArrowDown') {
      if (items.length === 0) return;
      e.preventDefault();
      activeSuggestionIdx = (activeSuggestionIdx + 1) % items.length;
      updateActiveSuggestion(items);
    } else if (e.key === 'ArrowUp') {
      if (items.length === 0) return;
      e.preventDefault();
      activeSuggestionIdx = (activeSuggestionIdx - 1 + items.length) % items.length;
      updateActiveSuggestion(items);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (activeSuggestionIdx >= 0 && currentSuggestions[activeSuggestionIdx]) {
        const item = currentSuggestions[activeSuggestionIdx];
        const mainTitle = item.name || item.display_name.split(',')[0].trim();
        selectLocation(parseFloat(item.lat), parseFloat(item.lon), mainTitle);
      } else {
        btnSearch.click();
      }
    } else if (e.key === 'Escape') {
      closeSuggestions();
    }
  });

  function updateActiveSuggestion(items) {
    items.forEach((item, idx) => {
      if (idx === activeSuggestionIdx) {
        item.classList.add('active');
        item.scrollIntoView({ block: 'nearest' });
      } else {
        item.classList.remove('active');
      }
    });
  }

  // Klik di luar area search & suggestions untuk menutup dropdown
  document.addEventListener('click', function(e) {
    if (!e.target.closest('#mapSearchWrapper')) {
      closeSuggestions();
    }
  });

  // Tombol Cari Manual (Klik 'Cari' atau Tekan Enter saat tidak ada saran yang disorot)
  btnSearch.addEventListener('click', async function() {
    const query = searchInput.value.trim();
    if (!query) return;

    searchSpinner.classList.remove('d-none');
    closeSuggestions();

    try {
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=id&limit=1`);
      const data = await res.json();
      if (data && data.length > 0) {
        const item = data[0];
        const mainTitle = item.name || item.display_name.split(',')[0].trim();
        selectLocation(parseFloat(item.lat), parseFloat(item.lon), mainTitle);
      } else {
        alert('Lokasi tidak ditemukan. Coba masukkan nama kota atau kelurahan yang lebih spesifik.');
      }
    } catch (err) {
      alert('Gagal menghubungi layanan pencarian peta.');
    } finally {
      searchSpinner.classList.add('d-none');
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
