<?php
/**
 * Halaman Pengaturan Timer & Siklus Layar TV
 * File: timer.php (URL: /timer)
 */
$page_title   = "Timer";
$current_page = "timer";

require_once __DIR__ . '/includes/header.php';

// Ambil Pengaturan Timer dari Database
$stmt = $pdo->prepare("SELECT * FROM timer WHERE masjid_id = ? LIMIT 1");
$stmt->execute([$current_masjid_id]);
$setting = $stmt->fetch() ?: [
    'kecepatan_running_text' => 5,
    'timer_wait_adzan'       => 1,
    'timer_adzan'            => 5,
    'timer_sholat'           => 10,
    'countdown_beep'         => 10,
    'iqomah_fajr'            => 10,
    'iqomah_dhuhr'           => 10,
    'iqomah_asr'             => 10,
    'iqomah_maghrib'         => 5,
    'iqomah_isha'            => 10
];

$speed_val   = (int)($setting['kecepatan_running_text'] ?? 5);
$wait_adzan  = (int)($setting['timer_wait_adzan'] ?? 1);
$adzan_val   = (int)($setting['timer_adzan'] ?? 5);
$sholat_val  = (int)($setting['timer_sholat'] ?? 10);
$beep_val    = (int)($setting['countdown_beep'] ?? 10);

$iq_fajr     = (int)($setting['iqomah_fajr'] ?? 10);
$iq_dhuhr    = (int)($setting['iqomah_dhuhr'] ?? 10);
$iq_asr      = (int)($setting['iqomah_asr'] ?? 10);
$iq_maghrib  = (int)($setting['iqomah_maghrib'] ?? 5);
$iq_isha     = (int)($setting['iqomah_isha'] ?? 10);
?>

<!-- Timer CSS -->
<link rel="stylesheet" href="<?= asset_v('assets/css/timer.css') ?>">

<form id="formSaveTimer">
  <div class="row">
    
    <!-- KOLOM KIRI: SIKLUS WAKTU SHOLAT & RUNNING TEXT -->
    <div class="col-lg-7 mb-4">
      
      <!-- KARTU 1: KECEPATAN RUNNING TEXT -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="timer-card-title">
            <i class="bi bi-broadcast text-success me-1"></i> 1. Kecepatan Running Text
          </span>
          <span class="badge bg-success-subtle text-success border border-success-subtle" id="badgeSpeedVal">
            Kecepatan: Level <?= $speed_val ?>
          </span>
        </div>
        <div class="card-body">
          <label class="form-label small fw-semibold text-muted">
            Atur kelajuan jalannya teks informasi di bagian bawah layar TV:
          </label>
          <div class="d-flex align-items-center gap-3">
            <span class="small fw-bold text-muted">Lambat (1)</span>
            <input type="range" class="form-range flex-grow-1" id="rangeSpeed" name="kecepatan_running_text" min="1" max="10" step="1" value="<?= $speed_val ?>">
            <span class="small fw-bold text-muted">Cepat (10)</span>
          </div>
        </div>
      </div>

      <!-- KARTU 2: DURASI SIKLUS WAKTU SHOLAT -->
      <div class="card mb-4">
        <div class="card-header">
          <span class="timer-card-title">
            <i class="bi bi-hourglass-split text-success me-1"></i> 2. Durasi Siklus Waktu Sholat
          </span>
        </div>
        <div class="card-body">
          <p class="small text-muted mb-4">
            Pengaturan urutan fase perpindahan layar TV otomatis dari sebelum adzan, saat adzan, hingga sholat berjamaah:
          </p>

          <!-- Fase 1: Menjelang Adzan -->
          <div class="timeline-step">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-bold text-dark">Menjelang Adzan</span>
              <span class="badge-phase bg-warning-subtle text-warning-emphasis border border-warning-subtle">Fase Peringatan</span>
            </div>
            <div class="text-muted small mb-2">Layar TV memunculkan hitung mundur peringatan bahwa waktu sholat segera masuk.</div>
            <div class="input-group input-group-sm" style="max-width: 220px;">
              <input type="number" class="form-control text-center fw-bold" name="timer_wait_adzan" min="0" max="60" value="<?= $wait_adzan ?>" required>
              <span class="input-group-text input-unit-label">Menit</span>
            </div>
          </div>

          <!-- Fase 2: Lama Tampilan Adzan -->
          <div class="timeline-step">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-bold text-dark">Lama Adzan</span>
              <span class="badge-phase bg-info-subtle text-info-emphasis border border-info-subtle">Fase Adzan</span>
            </div>
            <div class="text-muted small mb-2">Durasi tampilan adzan berkumandang (mari menjawab adzan & memanjatkan doa).</div>
            <div class="input-group input-group-sm" style="max-width: 220px;">
              <input type="number" class="form-control text-center fw-bold" name="timer_adzan" min="0" max="60" value="<?= $adzan_val ?>" required>
              <span class="input-group-text input-unit-label">Menit</span>
            </div>
          </div>

          <!-- Fase 3: Lamanya Sholat -->
          <div class="timeline-step mb-0" style="border-left-color: #d4af37;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-bold text-dark">Lamanya Sholat (Layar Hening)</span>
              <span class="badge-phase bg-dark text-white">Layar Hening / Gelap</span>
            </div>
            <div class="text-muted small mb-2">Layar TV beralih menjadi mode gelap/standby saat sholat berjamaah agar tidak menyilaukan dan jamaah khusyuk.</div>
            <div class="input-group input-group-sm" style="max-width: 220px;">
              <input type="number" class="form-control text-center fw-bold" name="timer_sholat" min="0" max="60" value="<?= $sholat_val ?>" required>
              <span class="input-group-text input-unit-label">Menit</span>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- KOLOM KANAN: WAKTU IQOMAH & ALARM BEEP -->
    <div class="col-lg-5 mb-4">
      
      <!-- KARTU 3: WAKTU MENJELANG IQOMAH PER SHOLAT -->
      <div class="card mb-4">
        <div class="card-header">
          <span class="timer-card-title">
            <i class="bi bi-clock-history text-success me-1"></i> 3. Menjelang Iqomah
          </span>
        </div>
        <div class="card-body">
          <p class="small text-muted mb-3">
            Waktu hitung mundur jeda sholat sunnah menuju iqomah sholat fardhu (dapat dibedakan per sholat):
          </p>

          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">Subuh</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center fw-bold" name="iqomah_fajr" min="0" max="60" value="<?= $iq_fajr ?>" required>
                <span class="input-group-text input-unit-label">Menit</span>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">Dzuhur</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center fw-bold" name="iqomah_dhuhr" min="0" max="60" value="<?= $iq_dhuhr ?>" required>
                <span class="input-group-text input-unit-label">Menit</span>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">Ashar</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center fw-bold" name="iqomah_asr" min="0" max="60" value="<?= $iq_asr ?>" required>
                <span class="input-group-text input-unit-label">Menit</span>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">Maghrib</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center fw-bold" name="iqomah_maghrib" min="0" max="60" value="<?= $iq_maghrib ?>" required>
                <span class="input-group-text input-unit-label">Menit</span>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Isya'</label>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control text-center fw-bold" name="iqomah_isha" min="0" max="60" value="<?= $iq_isha ?>" required>
                <span class="input-group-text input-unit-label">Menit</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- KARTU 4: ALARM BEEP HITUNG MUNDUR -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="timer-card-title">
            <i class="bi bi-volume-up-fill text-success me-1"></i> 4. Alarm Beep Countdown
          </span>
          <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnTestBeep">
            <i class="bi bi-play-circle-fill me-1"></i> Tes Suara Beep
          </button>
        </div>
        <div class="card-body">
          <label class="form-label small fw-semibold text-secondary">
            Bunyi Beep di Detik-detik Terakhir:
          </label>
          <div class="input-group input-group-sm mb-2" style="max-width: 200px;">
            <input type="number" class="form-control text-center fw-bold" name="countdown_beep" min="0" max="60" value="<?= $beep_val ?>" required>
            <span class="input-group-text input-unit-label">Detik</span>
          </div>
          <div class="form-text small" style="font-size: 0.8rem;">
            <i class="bi bi-info-circle me-1"></i> TV akan membunyikan nada <em>beep</em> setiap detik pada sisa detik terakhir menjelang adzan & iqomah. Isi <strong>0</strong> jika ingin mode hening tanpa suara beep.
          </div>
        </div>
      </div>

      <!-- TOMBOL SIMPAN PENGATURAN -->
      <button type="submit" class="btn btn-masjid w-100 py-3 fs-6 shadow">
        <i class="bi bi-floppy-fill me-1"></i> Simpan Pengaturan Timer
      </button>

    </div>

  </div>
</form>

<script>
  // 1. Slider Kecepatan Running Text
  const rangeSpeed = document.getElementById('rangeSpeed');
  const badgeSpeedVal = document.getElementById('badgeSpeedVal');

  function updateSpeedVisual(val) {
    badgeSpeedVal.textContent = `Kecepatan: Level ${val}`;
  }

  rangeSpeed.addEventListener('input', function() {
    updateSpeedVisual(this.value);
  });

  // Jalankan saat load
  updateSpeedVisual(rangeSpeed.value);

  // 2. Synthesizer Tes Suara Beep (Web Audio API)
  let testAudioCtx = null;
  document.getElementById('btnTestBeep').addEventListener('click', function() {
    try {
      const AudioClass = window.AudioContext || window.webkitAudioContext;
      if (!testAudioCtx) {
        testAudioCtx = new AudioClass();
      }
      if (testAudioCtx.state === 'suspended') {
        testAudioCtx.resume();
      }

      // Bunyikan 3 nada beep berturut-turut untuk simulasi
      let count = 0;
      const playSingleBeep = () => {
        if (count >= 3) return;
        const now = testAudioCtx.currentTime;
        const osc = testAudioCtx.createOscillator();
        const gain = testAudioCtx.createGain();

        osc.type = "sine";
        osc.frequency.setValueAtTime(880, now); // 880 Hz

        gain.gain.setValueAtTime(0, now);
        gain.gain.linearRampToValueAtTime(0.35, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.18);

        osc.connect(gain);
        gain.connect(testAudioCtx.destination);

        osc.start(now);
        osc.stop(now + 0.2);

        count++;
        setTimeout(playSingleBeep, 400);
      };

      playSingleBeep();
    } catch (err) {
      alert('Browser Anda memblokir pemutaran audio otomatis.');
    }
  });

  // 3. Simpan Pengaturan via AJAX
  document.getElementById('formSaveTimer').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    btn.disabled = true;

    try {
      const formData = new FormData(this);
      const res = await fetch('api/save_timer.php', {
        method: 'POST',
        body: formData
      });
      const json = await res.json();
      if (json.status === 'success') {
        showAlert('success', json.message);
      } else {
        showAlert('danger', json.message || 'Gagal menyimpan data');
      }
    } catch (err) {
      showAlert('danger', 'Terjadi kesalahan jaringan atau server');
    } finally {
      btn.innerHTML = oldHtml;
      btn.disabled = false;
    }
  });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
