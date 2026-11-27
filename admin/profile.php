<?php
/**
 * Halaman Pengaturan Profil Masjid & Ganti Password DKM
 * File: admin/profile.php (URL: /admin/profile)
 */
$page_title   = "Profil";
$current_page = "profile";

require_once __DIR__ . '/includes/header.php';

$latitude  = (float)($masjid['latitude'] ?? -6.140000);
$longitude = (float)($masjid['longitude'] ?? 106.590000);
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Schedule CSS (untuk styling peta Leaflet & autocomplete saran lokasi) -->
<link rel="stylesheet" href="<?= asset_v('assets/css/schedule.css') ?>">

<form id="formSaveProfile" enctype="multipart/form-data">
  <div class="row">
    
    <!-- Kolom 1: Profil Identitas Masjid -->
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-building me-1"></i> Profil Identitas Masjid</span>
          <span class="badge bg-success-subtle text-success border border-success-subtle">ID: <?= htmlspecialchars($current_masjid_id) ?></span>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Masjid <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($masjid['nama']) ?>" placeholder="Contoh: Masjid Al Ikhlas" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Alamat Lengkap Masjid <span class="text-danger">*</span></label>
            <textarea class="form-control" name="lokasi" rows="2" placeholder="Contoh: Jl. Contoh No. 12, Kebayoran Baru • Jakarta Selatan" required><?= htmlspecialchars($masjid['lokasi'] ?? '') ?></textarea>
            <div class="form-text">Teks ini tampil di bagian atas layar TV di bawah nama masjid.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Informasi Kontak & Rekening Infaq</label>
            <textarea class="form-control" name="kontak_info" rows="2" placeholder="Contoh: BSI 7123 456 789 a.n. Masjid Al Ikhlas • WA: 0812-3456-7890"><?= htmlspecialchars($masjid['kontak_info'] ?? '') ?></textarea>
            <div class="form-text">Tampil di header layar TV tepat di bawah alamat masjid. Kosongkan jika tidak ingin ditampilkan.</div>
          </div>

          <!-- Kotak Logo Masjid (Bisa Kosong) -->
          <div class="mb-3 p-3 rounded-3 border bg-light">
            <label class="form-label small fw-semibold d-block mb-2">Logo Masjid (Opsional)</label>
            <div class="d-flex align-items-center gap-3">
              <div class="logo-preview-box text-center p-2 rounded border bg-white" style="width: 84px; height: 84px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <?php if (!empty($masjid['logo'])): ?>
                  <img id="logoPreviewImg" src="<?= htmlspecialchars($masjid['logo']) ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                  <span id="noLogoPlaceholder" class="text-muted small d-none" style="font-size: 0.7rem;"><i class="bi bi-image fs-4 d-block text-secondary"></i>Tanpa Logo</span>
                <?php else: ?>
                  <span id="noLogoPlaceholder" class="text-muted small" style="font-size: 0.7rem;"><i class="bi bi-image fs-4 d-block text-secondary"></i>Tanpa Logo</span>
                  <img id="logoPreviewImg" src="" alt="Logo" class="d-none" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <?php endif; ?>
              </div>
              
              <div class="flex-grow-1">
                <input type="file" class="form-control form-control-sm mb-2" id="logoInput" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="checkRemoveLogo" <?= empty($masjid['logo']) ? 'disabled' : '' ?>>
                  <label class="form-check-label small text-danger" for="checkRemoveLogo">
                    Hapus logo / Gunakan teks nama masjid saja
                  </label>
                </div>
                <div class="form-text mt-1" style="font-size: 0.75rem;">Format disarankan: PNG transparan (persegi 1:1). Maks 4 MB.</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Kolom 2: Titik Koordinat & Lokasi Peta Masjid -->
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-geo-alt-fill text-primary me-1"></i> Titik Koordinat &amp; Lokasi Masjid</span>
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
            <i class="bi bi-info-circle me-1"></i> Geser pin biru di peta atau gunakan pencarian untuk menentukan titik lokasi masjid. Koordinat ini digunakan sebagai acuan hisab waktu sholat.
          </div>

        </div>
      </div>
    </div>

    <!-- Tombol Simpan Profil & Koordinat -->
    <div class="col-12 mb-4">
      <button type="submit" class="btn btn-masjid px-4 py-2 fs-6 shadow-sm">
        <i class="bi bi-floppy-fill me-1"></i> Simpan Profil Masjid
      </button>
    </div>

  </div>
</form>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  // Preview Logo saat file dipilih
  const logoInput = document.getElementById('logoInput');
  const logoPreviewImg = document.getElementById('logoPreviewImg');
  const noLogoPlaceholder = document.getElementById('noLogoPlaceholder');
  const checkRemoveLogo = document.getElementById('checkRemoveLogo');

  if (logoInput) {
    logoInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          logoPreviewImg.src = e.target.result;
          logoPreviewImg.classList.remove('d-none');
          if (noLogoPlaceholder) noLogoPlaceholder.classList.add('d-none');
          if (checkRemoveLogo) checkRemoveLogo.checked = false;
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  }

  if (checkRemoveLogo) {
    checkRemoveLogo.addEventListener('change', function() {
      if (this.checked) {
        logoPreviewImg.classList.add('d-none');
        if (noLogoPlaceholder) noLogoPlaceholder.classList.remove('d-none');
        logoInput.value = '';
      } else {
        if (logoPreviewImg.getAttribute('src')) {
          logoPreviewImg.classList.remove('d-none');
          if (noLogoPlaceholder) noLogoPlaceholder.classList.add('d-none');
        }
      }
    });
  }

  // Inisialisasi Peta Leaflet
  let map, marker;
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
    });

    map.on('click', function(e) {
      marker.setLatLng(e.latlng);
      document.getElementById('inputLat').value = e.latlng.lat.toFixed(6);
      document.getElementById('inputLng').value = e.latlng.lng.toFixed(6);
    });
  }

  // Autocomplete Pencarian Lokasi (Nominatim OSM)
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

  async function fetchSuggestions(query) {
    if (!query || query.length < 3) {
      closeSuggestions();
      searchSpinner.classList.add('d-none');
      return;
    }

    searchSpinner.classList.remove('d-none');

    try {
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

  btnClear.addEventListener('click', function() {
    searchInput.value = '';
    btnClear.classList.add('d-none');
    closeSuggestions();
    searchInput.focus();
  });

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

  document.addEventListener('click', function(e) {
    if (!e.target.closest('#mapSearchWrapper')) {
      closeSuggestions();
    }
  });

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

  // Tombol GPS Perangkat
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

  // 1. Simpan Profil Masjid & Koordinat
  document.getElementById('formSaveProfile').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
      const res = await fetch('api/save_profile.php', { method: 'POST', body: formData });
      const json = await res.json();
      if (json.status === 'success') {
        showAlert('success', json.message);
        const newName = formData.get('nama');
        if (newName) {
          document.getElementById('sidebarMasjidName').textContent = newName;
        }
        if (json.logo_url) {
          logoPreviewImg.src = json.logo_url;
          logoPreviewImg.classList.remove('d-none');
          if (noLogoPlaceholder) noLogoPlaceholder.classList.add('d-none');
          checkRemoveLogo.disabled = false;
          checkRemoveLogo.checked = false;
        } else {
          logoPreviewImg.classList.add('d-none');
          if (noLogoPlaceholder) noLogoPlaceholder.classList.remove('d-none');
          checkRemoveLogo.disabled = true;
          checkRemoveLogo.checked = false;
        }
      } else {
        showAlert('danger', json.message);
      }
    } catch (err) {
      showAlert('danger', 'Gagal menyimpan profil masjid.');
    }
  });

  window.addEventListener('DOMContentLoaded', () => {
    initMap();
  });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
