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
// MAIN TICK & INITIALIZATION
// =============================================================================
async function loadLiveConfig() {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    let masjidId = urlParams.get("id");
    if (!masjidId) {
      const parts = window.location.pathname.split("/").filter(Boolean);
      const dispIdx = parts.indexOf("display");
      if (dispIdx !== -1 && parts[dispIdx + 1]) {
        masjidId = parts[dispIdx + 1];
      }
    }
    if (!masjidId) masjidId = "101";

    const apiUrl = `../api/get_display.php?id=${encodeURIComponent(masjidId)}`;
    const res = await fetch(apiUrl);
    if (res.ok) {
      const data = await res.json();
      if (data && data.setting) {
        window.masjidConfig = data;
        console.info(`[LayarMasjid] Berhasil memuat data live Masjid ID: ${masjidId}`);
      }
    }
  } catch (err) {
    console.warn("[LayarMasjid] Menggunakan konfigurasi offline/lokal (gagal fetch API):", err);
  }
}

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
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", startClock, { once: true });
} else {
  startClock();
}
