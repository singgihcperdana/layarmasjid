// =============================================================================
// MAIN ENTRY POINT & ORCHESTRATOR
// Layar Masjid Digital Signage
// =============================================================================

// Virtual Clock & Mock Time Debugging (?datetime=YYYY-MM-DDTHH:mm:ss atau ?time=HH:mm:ss)
let timeOffsetMs = 0;
let isMockTimeActive = false;

function initTimeOffset() {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    const dtParam = urlParams.get("datetime") || urlParams.get("date_time") || urlParams.get("time");
    if (dtParam) {
      const cleanStr = dtParam.trim();
      let targetTime = null;

      if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(cleanStr)) {
        const parts = cleanStr.split(":").map(Number);
        targetTime = new Date();
        targetTime.setHours(parts[0], parts[1], parts[2] || 0, 0);
      } else {
        targetTime = new Date(cleanStr.replace(" ", "T"));
      }

      if (targetTime && !isNaN(targetTime.getTime())) {
        timeOffsetMs = targetTime.getTime() - Date.now();
        isMockTimeActive = true;
        console.info(`[LayarMasjid] Mock Datetime Aktif: ${targetTime.toLocaleString("id-ID")} (offset: ${timeOffsetMs} ms)`);
      } else {
        console.warn(`[LayarMasjid] Format query param datetime tidak dikenali: "${dtParam}"`);
      }
    }
  } catch (err) {
    console.error("[LayarMasjid] Gagal membaca query parameter datetime:", err);
  }
}

function getCurrentNow() {
  return new Date(Date.now() + timeOffsetMs);
}

window.getCurrentNow = getCurrentNow;
window.setMockDateTime = function (dtString) {
  if (!dtString) {
    timeOffsetMs = 0;
    isMockTimeActive = false;
    console.info("[LayarMasjid] Mock time dinonaktifkan (kembali ke waktu sistem).");
    return;
  }
  const cleanStr = dtString.trim();
  let targetTime = null;
  if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(cleanStr)) {
    const parts = cleanStr.split(":").map(Number);
    targetTime = new Date();
    targetTime.setHours(parts[0], parts[1], parts[2] || 0, 0);
  } else {
    targetTime = new Date(cleanStr.replace(" ", "T"));
  }
  if (targetTime && !isNaN(targetTime.getTime())) {
    timeOffsetMs = targetTime.getTime() - Date.now();
    isMockTimeActive = true;
    console.info(`[LayarMasjid] Mock datetime diubah ke: ${targetTime.toLocaleString("id-ID")}`);
  } else {
    console.warn(`[LayarMasjid] Format datetime tidak valid: "${dtString}"`);
  }
};

// =============================================================================
// =============================================================================
// MAIN TICK, LIVE SYNC & INITIALIZATION
// =============================================================================

function getMasjidId() {
  const urlParams = new URLSearchParams(window.location.search);
  let masjidId = urlParams.get("id");
  if (!masjidId) {
    const parts = window.location.pathname.split("/").filter(Boolean);
    const dispIdx = parts.indexOf("display");
    if (dispIdx !== -1 && parts[dispIdx + 1]) {
      masjidId = parts[dispIdx + 1];
    }
  }
  return masjidId || "101";
}

// Helper resolusi path API yang tahan terhadap variasi trailing slash & subdirektori
function getApiUrl(endpoint) {
  const path = window.location.pathname;
  const dispIdx = path.indexOf("/display");
  const base = dispIdx !== -1 ? path.substring(0, dispIdx) : "";
  return `${base}/api/${endpoint}`;
}

let currentSyncHash = null;
let isSyncInProgress = false;

async function loadLiveConfig(syncHash) {
  try {
    const masjidId = getMasjidId();
    const vParam = syncHash ? `&v=${encodeURIComponent(syncHash)}` : `&_t=${Date.now()}`;
    const apiUrl = `${getApiUrl("get_display.php")}?id=${encodeURIComponent(masjidId)}${vParam}`;
    const res = await fetch(apiUrl);
    if (res.ok) {
      const data = await res.json();
      if (data && data.setting) {
        window.masjidConfig = data;
        if (data.sync_hash) {
          currentSyncHash = String(data.sync_hash);
        }
        console.info(`[LayarMasjid] Berhasil memuat data live Masjid ID: ${masjidId} (Sync: ${currentSyncHash})`);
      }
    }
  } catch (err) {
    console.warn("[LayarMasjid] Menggunakan konfigurasi offline/lokal (gagal fetch API):", err);
  }
}

async function checkLiveSync() {
  if (isSyncInProgress) return;

  // Proteksi: Tunda jika layar sedang dalam fase siklus sholat (adzan, iqomah, atau sholat)
  const now = getCurrentNow();
  if (typeof getPrayerLifecycleState === "function") {
    const state = getPrayerLifecycleState(now);
    if (state && (state.phase === "saat_azan" || state.phase === "countdown_iqomah" || state.phase === "saat_sholat")) {
      return;
    }
  }

  try {
    isSyncInProgress = true;
    const masjidId = getMasjidId();
    const checkUrl = `${getApiUrl("check_sync.php")}?id=${encodeURIComponent(masjidId)}&_t=${Date.now()}`;
    const res = await fetch(checkUrl);

    if (res.ok) {
      const json = await res.json();
      if (json && json.sync_hash) {
        const newHash = String(json.sync_hash);
        if (currentSyncHash === null) {
          currentSyncHash = newHash;
        } else if (currentSyncHash !== newHash) {
          console.info(`[LayarMasjid Sync] Perubahan terdeteksi! Memperbarui layar (Hash lama: ${currentSyncHash} -> baru: ${newHash})...`);
          currentSyncHash = newHash;
          triggerPageRefresh();
        }
      }
    }
  } catch (err) {
    // Abaikan jika network offline
  } finally {
    isSyncInProgress = false;
  }
}

// Menampilkan icon sinkronisasi di tengah halaman lalu me-refresh page
function triggerPageRefresh() {
  const overlay = document.getElementById("sync-refresh-overlay");
  if (overlay) {
    overlay.classList.add("active");
  }

  // Tampilkan icon di tengah layar sejenak (800ms) agar transisi terlihat jelas, lalu reload fresh
  setTimeout(() => {
    const url = new URL(window.location.href);
    url.searchParams.set("_r", Date.now());
    window.location.href = url.toString();
  }, 800);
}

window.triggerPageRefresh = triggerPageRefresh;

function tick() {
  const now = getCurrentNow();
  calculateDailyPrayers(now); // Otomatis recalculate jika berganti hari (00:00)
  updateClock(now);
  updateNextPrayer(now);

  const state = getPrayerLifecycleState(now);
  renderLifecycleScreen(state, now);
}

async function startClock() {
  initTimeOffset();
  await loadLiveConfig();
  applyMasjidConfig();
  const initialNow = getCurrentNow();
  calculateDailyPrayers(initialNow);
  setupBrandLogo();
  setupSlides();
  setupAdzanTestControls();
  tick();

  const delayToNextSecond = 1000 - initialNow.getMilliseconds();
  setTimeout(() => {
    tick();
    setInterval(tick, 1000);
  }, delayToNextSecond);

  // Jalankan Auto-Sync Polling setiap 10 Detik
  setInterval(checkLiveSync, 10000);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", startClock, { once: true });
} else {
  startClock();
}
