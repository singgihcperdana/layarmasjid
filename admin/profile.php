<?php
/**
 * Halaman Pengaturan Profil Masjid & Ganti Password DKM
 * File: admin/profile.php (URL: /admin/profile)
 */
$page_title   = "Profil";
$current_page = "profile";

require_once __DIR__ . '/includes/header.php';

$latitude    = (float)($masjid['latitude'] ?? -6.140000);
$longitude   = (float)($masjid['longitude'] ?? 106.590000);
$province_id = $masjid['province_id'] ?? '';
$regency_id  = $masjid['regency_id'] ?? '';
$is_region_locked = !empty($province_id) && !empty($regency_id);

// Ambil daftar 38 Provinsi (urut abjad A-Z)
$stmtProv = $pdo->query("SELECT id, name FROM provinces ORDER BY name ASC");
$provinces = $stmtProv->fetchAll(PDO::FETCH_ASSOC);

// Cari nama provinsi aktif
$province_name = '';
foreach ($provinces as $p) {
    if ($p['id'] === $province_id) {
        $province_name = $p['name'];
        break;
    }
}

// Ambil daftar Kabupaten/Kota jika provinsi sudah terpilih
$regencies = [];
if (!empty($province_id)) {
    $stmtReg = $pdo->prepare("SELECT id, name, latitude, longitude FROM regencies WHERE province_id = ? ORDER BY name ASC");
    $stmtReg->execute([$province_id]);
    $regencies = $stmtReg->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil info kabupaten terpilih untuk batas radius wilayah
$selected_regency = null;
$max_radius_km = 35;
if (!empty($regency_id)) {
    foreach ($regencies as $r) {
        if ($r['id'] === $regency_id) {
            $selected_regency = $r;
            break;
        }
    }
    if (!$selected_regency) {
        $stmtRegCur = $pdo->prepare("SELECT id, name, latitude, longitude FROM regencies WHERE id = ? LIMIT 1");
        $stmtRegCur->execute([$regency_id]);
        $selected_regency = $stmtRegCur->fetch();
    }

    if ($selected_regency) {
        $regName    = $selected_regency['name'];
        $isCity     = (stripos($regName, 'Kota') === 0);
        $provNum    = (int)$province_id;
        $isJavaBali = in_array($provNum, [31, 32, 33, 34, 35, 36, 51]);

        if ($isCity) {
            $max_radius_km = 15;
        } elseif ($isJavaBali) {
            $max_radius_km = 35;
        } else {
            $max_radius_km = 55;
        }
    }
}
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
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-semibold mb-0">Nama Masjid <span class="text-danger">*</span></label>
              <small id="charCountNama" class="text-muted fw-semibold" style="font-size: 0.75rem;">0 / 35</small>
            </div>
            <input type="text" class="form-control" id="inputNama" name="nama" value="<?= htmlspecialchars($masjid['nama']) ?>" placeholder="Contoh: Masjid Al Ikhlas" maxlength="35" required>
          </div>

          <!-- Pilihan Wilayah: Provinsi & Kabupaten/Kota (Terkunci Permanen) -->
          <div class="p-3 mb-3 rounded-3 border bg-light">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label small fw-semibold mb-0 text-dark">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i> Wilayah Administrasi Masjid
              </label>
              <?php if ($is_region_locked): ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.72rem;">
                  <i class="bi bi-lock-fill me-1"></i> Terkunci Permanen
                </span>
              <?php endif; ?>
            </div>
            
            <div class="row g-2">
              <div class="col-sm-6">
                <label class="form-label small text-muted mb-1" style="font-size: 0.78rem;">Provinsi</label>
                <select class="form-select form-select-sm" id="selectProvince" <?= $is_region_locked ? 'disabled' : 'name="province_id" required' ?>>
                  <option value="">-- Pilih Provinsi --</option>
                  <?php foreach ($provinces as $prov): ?>
                    <option value="<?= $prov['id'] ?>" <?= ($province_id === $prov['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($prov['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if ($is_region_locked): ?>
                  <input type="hidden" name="province_id" value="<?= htmlspecialchars($province_id) ?>">
                <?php endif; ?>
              </div>
              <div class="col-sm-6">
                <label class="form-label small text-muted mb-1" style="font-size: 0.78rem;">Kabupaten / Kota</label>
                <select class="form-select form-select-sm" id="selectRegency" <?= $is_region_locked ? 'disabled' : 'name="regency_id" required' ?>>
                  <option value="">-- Pilih Kab / Kota --</option>
                  <?php foreach ($regencies as $reg): ?>
                    <option value="<?= $reg['id'] ?>" 
                            data-lat="<?= $reg['latitude'] ?>" 
                            data-lng="<?= $reg['longitude'] ?>" 
                            <?= ($regency_id === $reg['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($reg['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if ($is_region_locked): ?>
                  <input type="hidden" name="regency_id" value="<?= htmlspecialchars($regency_id) ?>">
                <?php endif; ?>
              </div>
            </div>

            <?php if ($is_region_locked): ?>
              <div class="form-text mt-2 text-muted" style="font-size: 0.75rem;">
                <i class="bi bi-lock-fill me-1"></i> Wilayah Provinsi &amp; Kabupaten/Kota tidak dapat diubah setelah didaftarkan.
              </div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-semibold mb-0">Alamat Lengkap Masjid <span class="text-danger">*</span></label>
              <small id="charCountLokasi" class="text-muted fw-semibold" style="font-size: 0.75rem;">0 / 80</small>
            </div>
            <textarea class="form-control" id="inputLokasi" name="lokasi" rows="2" placeholder="Contoh: Jl. Contoh No. 12, Kebayoran Baru • Jakarta Selatan" maxlength="80" required><?= htmlspecialchars($masjid['lokasi'] ?? '') ?></textarea>
            <div class="form-text">Teks ini tampil di bagian atas layar TV di bawah nama masjid (maksimal 80 karakter).</div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-semibold mb-0">Informasi Kontak & Rekening Infaq</label>
              <small id="charCountKontak" class="text-muted fw-semibold" style="font-size: 0.75rem;">0 / 80</small>
            </div>
            <textarea class="form-control" id="inputKontak" name="kontak_info" rows="2" placeholder="Contoh: BSI 7123 456 789 a.n. Masjid Al Ikhlas • WA: 0812-3456-7890" maxlength="80"><?= htmlspecialchars($masjid['kontak_info'] ?? '') ?></textarea>
            <div class="form-text">Tampil di header layar TV tepat di bawah alamat masjid (maksimal 80 karakter). Kosongkan jika tidak ingin ditampilkan.</div>
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
            <i class="bi bi-crosshair me-1"></i> Gunakan GPS Saya
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
          <div id="map" class="mb-2 shadow-sm"></div>
          <?php if ($selected_regency): ?>
            <div class="form-text mb-3 text-muted" style="font-size: 0.78rem;">
              <i class="bi bi-shield-check text-success me-1"></i> Titik koordinat dibatasi dalam wilayah <strong><?= htmlspecialchars($selected_regency['name']) ?></strong> (maks. radius <?= $max_radius_km ?> km dari pusat daerah).
            </div>
          <?php endif; ?>

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
<!-- Turf.js untuk Geofencing & Masking Inverse Polygon -->
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

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

  // Inisialisasi Peta Leaflet & Batasan Radius Wilayah
  const PROVINCE_NAME      = <?= json_encode($province_name) ?>;
  const PROVINCE_ID        = <?= json_encode($province_id) ?>;
  const REGENCY_ID          = <?= json_encode($regency_id) ?>;
  const REGENCY_CENTER_LAT = <?= (!empty($selected_regency['latitude'])) ? (float)$selected_regency['latitude'] : 'null' ?>;
  const REGENCY_CENTER_LNG = <?= (!empty($selected_regency['longitude'])) ? (float)$selected_regency['longitude'] : 'null' ?>;
  const REGENCY_NAME       = <?= json_encode($selected_regency['name'] ?? '') ?>;
  const MAX_RADIUS_KM      = <?= (int)$max_radius_km ?>; // Batas radius toleransi maksimal dari pusat kabupaten

  let currentKotaFeature = null;

  // Hitung jarak Haversine (km)
  function calculateDistanceKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  // Validasi radius fallback
  function isCoordWithinRegency(lat, lng) {
    if (REGENCY_CENTER_LAT !== null && REGENCY_CENTER_LNG !== null) {
      const dist = calculateDistanceKm(lat, lng, REGENCY_CENTER_LAT, REGENCY_CENTER_LNG);
      if (dist > MAX_RADIUS_KM) {
        return {
          valid: false,
          distance: Math.round(dist)
        };
      }
    }
    return { valid: true, distance: 0 };
  }

  // Validasi Utama: Turf.js Point-In-Polygon dengan fallback ke Radius
  function isCoordAllowed(lat, lng) {
    if (currentKotaFeature && typeof turf !== 'undefined' && turf.booleanPointInPolygon) {
      try {
        const pt = turf.point([lng, lat]);
        const isInside = turf.booleanPointInPolygon(pt, currentKotaFeature);
        if (!isInside) {
          return {
            valid: false,
            message: `Titik koordinat berada di luar batas wilayah ${REGENCY_NAME}.`
          };
        }
        return { valid: true };
      } catch (err) {
        console.warn('turf.booleanPointInPolygon error:', err);
      }
    }

    const radiusCheck = isCoordWithinRegency(lat, lng);
    if (!radiusCheck.valid) {
      return {
        valid: false,
        message: `Titik koordinat terlalu jauh (${radiusCheck.distance} km) dari wilayah ${REGENCY_NAME}.`
      };
    }
    return { valid: true };
  }

  // Fungsi Fetch & Filter GeoJSON Wilayah Kabupaten/Kota
  async function loadAndApplyGeoJsonBoundary(provName, regName) {
    if (!regName) return;

    // Bersihkan nama target untuk pencocokan case-insensitive
    const cleanTarget = regName.toLowerCase()
      .replace(/^(kabupaten|kab\.|kota)\s+/i, '')
      .trim();
    const cleanRegCode = (REGENCY_ID || '').replace(/[^0-9]/g, '');

    // Sumber GeoJSON: Coba file lokal (cepat) dan fallback ke Raw GitHub ardian28
    const sources = [
      'assets/geojson/kabupaten.json',
      'https://raw.githubusercontent.com/ardian28/GeoJson-Indonesia-38-Provinsi/main/Kabupaten/38%20Provinsi%20Indonesia%20-%20Kabupaten.json'
    ];

    let geoJsonData = null;
    for (const url of sources) {
      try {
        const res = await fetch(url);
        if (res.ok) {
          geoJsonData = await res.json();
          break;
        }
      } catch (e) {
        // Coba sumber berikutnya
      }
    }

    if (!geoJsonData || !geoJsonData.features || !geoJsonData.features.length) {
      console.warn('GeoJSON batas wilayah tidak berhasil dimuat, menggunakan proteksi radius.');
      return;
    }

    // 1. Prioritas Utama: Cocokkan berdasarkan kode KDPKAB (misal: "33.03" vs "3303")
    let kotaFeature = geoJsonData.features.find(f => {
      const p = f.properties || {};
      if (p.KDPKAB) {
        const code = String(p.KDPKAB).replace(/[^0-9]/g, '');
        if (code === cleanRegCode) return true;
      }
      return false;
    });

    // 2. Fallback: Cocokkan berdasarkan nama wilayah WADMKK / NAMOBJ
    if (!kotaFeature) {
      kotaFeature = geoJsonData.features.find(f => {
        const p = f.properties || {};
        const rawWadm = (p.WADMKK || p.NAMOBJ || p.nama || '').toLowerCase();
        const cleanWadm = rawWadm.replace(/^(kabupaten|kab\.|kota)\s+/i, '').trim();
        return cleanWadm === cleanTarget || rawWadm.includes(cleanTarget);
      });
    }

    if (!kotaFeature) {
      console.warn(`Poligon untuk "${regName}" (ID: ${cleanRegCode}) tidak ditemukan dalam GeoJSON.`);
      return;
    }

    currentKotaFeature = kotaFeature;
    console.log(`[GeoJSON Bound] Berhasil memuat poligon untuk: ${regName}`, kotaFeature.properties);

    try {
      // 1. Hitung Bounding Box & Kunci Kamera Peta
      const geoLayer = L.geoJSON(kotaFeature);
      const bounds = geoLayer.getBounds();
      map.setMaxBounds(bounds.pad(0.08));
      map.options.maxBoundsViscosity = 1.0;
      map.setMinZoom(Math.max(9, map.getBoundsZoom(bounds)));

      // 2. Efek Fokus Visual: Inverse Masking Gelap di Luar Wilayah (Turf.js)
      if (typeof turf !== 'undefined' && turf.mask) {
        try {
          const mask = turf.mask(kotaFeature);
          L.geoJSON(mask, {
            style: {
              fillColor: '#000000',
              fillOpacity: 0.65, // Gelap 65% di luar wilayah kota/kabupaten
              stroke: false,
              interactive: false
            }
          }).addTo(map);
        } catch (err) {
          console.warn('turf.mask error:', err);
        }
      }

      // 3. Garis Batas (Border) Tegas di Tepi Kota
      L.geoJSON(kotaFeature, {
        style: {
          color: '#198754',
          weight: 2.5,
          opacity: 0.95,
          fillColor: '#198754',
          fillOpacity: 0.05,
          dashArray: '5, 5'
        },
        interactive: false
      }).addTo(map);

    } catch (err) {
      console.warn('Gagal menerapkan layer boundary GeoJSON:', err);
    }
  }

  let map, marker;
  let lastValidPos = { lat: -6.14, lng: 106.59 };

  function initMap() {
    let lat = parseFloat(document.getElementById('inputLat').value) || -6.14;
    let lng = parseFloat(document.getElementById('inputLng').value) || 106.59;
    lastValidPos = { lat: lat, lng: lng };

    map = L.map('map').setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);

    // Tampilkan lingkaran batas wilayah toleransi jika data daerah tersedia
    if (REGENCY_CENTER_LAT !== null && REGENCY_CENTER_LNG !== null) {
      L.circle([REGENCY_CENTER_LAT, REGENCY_CENTER_LNG], {
        radius: MAX_RADIUS_KM * 1000,
        color: '#198754',
        weight: 1.5,
        fillColor: '#198754',
        fillOpacity: 0.04,
        dashArray: '6, 8'
      }).addTo(map);
    }

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker.on('dragstart', function() {
      const cur = marker.getLatLng();
      lastValidPos = { lat: cur.lat, lng: cur.lng };
    });

    marker.on('dragend', function (e) {
      const position = marker.getLatLng();
      const check = isCoordAllowed(position.lat, position.lng);
      if (!check.valid) {
        alert(check.message || `Peringatan: Titik koordinat berada di luar wilayah ${REGENCY_NAME}. Lokasi masjid harus berada di dalam batas wilayah terdaftar.`);
        marker.setLatLng([lastValidPos.lat, lastValidPos.lng]);
        document.getElementById('inputLat').value = lastValidPos.lat.toFixed(6);
        document.getElementById('inputLng').value = lastValidPos.lng.toFixed(6);
        return;
      }
      lastValidPos = { lat: position.lat, lng: position.lng };
      document.getElementById('inputLat').value = position.lat.toFixed(6);
      document.getElementById('inputLng').value = position.lng.toFixed(6);
    });

    map.on('click', function(e) {
      const check = isCoordAllowed(e.latlng.lat, e.latlng.lng);
      if (!check.valid) {
        alert(check.message || `Peringatan: Titik klik berada di luar wilayah ${REGENCY_NAME}. Lokasi masjid harus berada di dalam batas wilayah terdaftar.`);
        return;
      }
      lastValidPos = { lat: e.latlng.lat, lng: e.latlng.lng };
      marker.setLatLng(e.latlng);
      document.getElementById('inputLat').value = e.latlng.lat.toFixed(6);
      document.getElementById('inputLng').value = e.latlng.lng.toFixed(6);
    });

    // Panggil pembatas poligon GeoJSON & Masking inverse
    loadAndApplyGeoJsonBoundary(PROVINCE_NAME, REGENCY_NAME);
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

    const check = isCoordAllowed(lat, lng);
    if (!check.valid) {
      alert(check.message || `Peringatan: Lokasi "${name || 'terpilih'}" berada di luar wilayah ${REGENCY_NAME}. Silakan pilih lokasi di dalam wilayah yang terdaftar.`);
      closeSuggestions();
      return;
    }

    lastValidPos = { lat: lat, lng: lng };
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

  // Cascading Dropdown Provinsi -> Kabupaten/Kota
  const selectProv = document.getElementById('selectProvince');
  const selectReg = document.getElementById('selectRegency');

  if (selectProv && selectReg) {
    selectProv.addEventListener('change', async function() {
      const provId = this.value;
      selectReg.innerHTML = '<option value="">-- Memuat Kab / Kota... --</option>';
      selectReg.disabled = true;

      if (!provId) {
        selectReg.innerHTML = '<option value="">-- Pilih Provinsi Dahulu --</option>';
        return;
      }

      try {
        const res = await fetch(`api/get_regions.php?type=regencies&province_id=${encodeURIComponent(provId)}`);
        const json = await res.json();
        if (json.status === 'success' && Array.isArray(json.data)) {
          let opts = '<option value="">-- Pilih Kab / Kota --</option>';
          json.data.forEach(item => {
            opts += `<option value="${item.id}" data-lat="${item.latitude || ''}" data-lng="${item.longitude || ''}">${escapeHtml(item.name)}</option>`;
          });
          selectReg.innerHTML = opts;
          selectReg.disabled = false;
        } else {
          selectReg.innerHTML = '<option value="">Gagal memuat data</option>';
        }
      } catch (err) {
        selectReg.innerHTML = '<option value="">Gagal memuat data</option>';
      }
    });

    selectReg.addEventListener('change', function() {
      const selectedOpt = this.options[this.selectedIndex];
      if (selectedOpt && selectedOpt.dataset.lat && selectedOpt.dataset.lng) {
        const lat = parseFloat(selectedOpt.dataset.lat);
        const lng = parseFloat(selectedOpt.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng)) {
          selectLocation(lat, lng, selectedOpt.textContent.trim());
        }
      }
    });
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

        const check = isCoordAllowed(lat, lng);
        if (!check.valid) {
          alert(check.message || `Peringatan: Lokasi GPS terdeteksi berada di luar wilayah ${REGENCY_NAME}. Pastikan perangkat Anda berada di sekitar area masjid yang terdaftar.`);
          btn.innerHTML = oldHtml;
          btn.disabled = false;
          return;
        }

        lastValidPos = { lat: lat, lng: lng };
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

  // Validasi Input Manual Latitude & Longitude
  ['inputLat', 'inputLng'].forEach(id => {
    document.getElementById(id).addEventListener('change', function() {
      const lat = parseFloat(document.getElementById('inputLat').value);
      const lng = parseFloat(document.getElementById('inputLng').value);
      if (!isNaN(lat) && !isNaN(lng)) {
        const check = isCoordAllowed(lat, lng);
        if (!check.valid) {
          alert(check.message || `Peringatan: Koordinat yang dimasukkan berada di luar wilayah ${REGENCY_NAME}.`);
          document.getElementById('inputLat').value = lastValidPos.lat.toFixed(6);
          document.getElementById('inputLng').value = lastValidPos.lng.toFixed(6);
          return;
        }
        lastValidPos = { lat: lat, lng: lng };
        marker.setLatLng([lat, lng]);
        map.setView([lat, lng], 16);
      }
    });
  });

  // Counter Karakter Realtime
  function setupCharCounter(inputId, counterId, max) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    if (!input || !counter) return;

    function update() {
      const len = input.value.length;
      counter.textContent = `${len} / ${max}`;
      if (len >= max) {
        counter.classList.remove('text-muted', 'text-warning');
        counter.classList.add('text-danger');
      } else if (len >= max - 5) {
        counter.classList.remove('text-muted', 'text-danger');
        counter.classList.add('text-warning');
      } else {
        counter.classList.remove('text-danger', 'text-warning');
        counter.classList.add('text-muted');
      }
    }

    input.addEventListener('input', update);
    update();
  }

  setupCharCounter('inputNama', 'charCountNama', 35);
  setupCharCounter('inputLokasi', 'charCountLokasi', 80);
  setupCharCounter('inputKontak', 'charCountKontak', 80);

  // 1. Simpan Profil Masjid & Koordinat
  document.getElementById('formSaveProfile').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const nama = (formData.get('nama') || '').trim();
    const lokasi = (formData.get('lokasi') || '').trim();
    const kontak = (formData.get('kontak_info') || '').trim();

    if (nama.length > 35) {
      showAlert('danger', 'Nama masjid maksimal 35 karakter.');
      return;
    }
    if (lokasi.length > 80) {
      showAlert('danger', 'Alamat lengkap masjid maksimal 80 karakter.');
      return;
    }
    if (kontak.length > 80) {
      showAlert('danger', 'Informasi kontak & rekening maksimal 80 karakter.');
      return;
    }

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
