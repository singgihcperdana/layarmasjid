// =============================================================================
// VIRTUAL CLOCK & DEBUGGING (Query Param ?datetime=YYYY-MM-DDTHH:mm:ss atau ?time=HH:mm:ss)
// =============================================================================
let timeOffsetMs = 0;
let isMockTimeActive = false;

function initTimeOffset() {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    const dtParam = urlParams.get("datetime") || urlParams.get("date_time") || urlParams.get("time");
    if (dtParam) {
      const cleanStr = dtParam.trim();
      let targetTime = null;

      // 1. Format jam saja, contoh: ?time=17:54:50 atau ?datetime=17:54:50
      if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(cleanStr)) {
        const parts = cleanStr.split(":").map(Number);
        targetTime = new Date();
        targetTime.setHours(parts[0], parts[1], parts[2] || 0, 0);
      } else {
        // 2. Format ISO / Tanggal lengkap, contoh: ?datetime=2026-11-10T22:33:55 atau 2026-11-10 22:33:55
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

// Expose ke window untuk kemudahan debugging di Console Browser
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
// KONFIGURASI URUTAN ADZAN, IQOMAH, & SHOLAT (Bisa disesuaikan pengurus masjid)
// =============================================================================
// Default fallback jika config.js / database belum dimuat
let waitAdzan = 1;            // Durasi countdown menuju adzan (menit, diset 1 menit = 01:00)
let countDownAdzanBeep = 10;  // Bunyi beep di detik-detik terakhir menjelang adzan (detik)
let lamaAdzan = 5;            // Durasi tampilan halaman adzan (menit, contoh: 5 menit)
let lamaIqomah = 5;           // Durasi countdown iqomah (menit, contoh: 5 menit = 05:00)
let countDownIqomahBeep = 10; // Bunyi beep di detik-detik terakhir iqomah (detik)
let lamaSholat = 10;          // Durasi layar sholat khusyuk (menit, contoh: 10 menit)

function getTimerConfig() {
  const cfg = window.masjidConfig || {};
  const t = cfg.timer || {};
  return {
    waitAdzan: typeof t.wait_adzan !== "undefined" ? Number(t.wait_adzan) : waitAdzan,
    countDownAdzanBeep: typeof t.countdown_beep !== "undefined" ? Number(t.countdown_beep) : countDownAdzanBeep,
    lamaAdzan: typeof t.adzan !== "undefined" ? Number(t.adzan) : lamaAdzan,
    lamaSholat: typeof t.sholat !== "undefined" ? Number(t.sholat) : lamaSholat
  };
}

// Opsi durasi iqomah khusus per waktu sholat (jika ingin berbeda tiap sholat, default mengacu ke lamaIqomah)
let durasiIqomahPerSholat = {
  "Subuh": 10,
  "Dzuhur": 10,
  "Ashar": 10,
  "Maghrib": 5,
  "Isya": 10
};

// Sholat fardhu yang memiliki adzan & iqomah (Syuruq tidak memiliki adzan)
const ADZAN_PRAYERS = ["Subuh", "Dzuhur", "Ashar", "Maghrib", "Isya"];

// Jadwal sholat (Dihitung dinamis via PrayTimes.js)
let prayers = [
  { name: "Subuh", key: "fajr", time: "04:06" },
  { name: "Syuruq", key: "sunrise", time: "05:27" },
  { name: "Dzuhur", key: "dhuhr", time: "11:43" },
  { name: "Ashar", key: "asr", time: "15:04" },
  { name: "Maghrib", key: "maghrib", time: "17:52" },
  { name: "Isya", key: "isha", time: "19:09" }
];

let lastCalculatedDay = null;

function calculateDailyPrayers(now) {
  const dayKey = `${now.getFullYear()}-${now.getMonth() + 1}-${now.getDate()}`;
  if (lastCalculatedDay === dayKey && prayers.length > 0) {
    return;
  }

  const cfg = window.masjidConfig || {};
  const setting = cfg.setting || {};
  const lat = setting.latitude || -6.14;
  const lng = setting.longitude || 106.59;
  const tz = setting.timeZone || 7;
  const dst = setting.dst || 0;

  if (window.prayTimes) {
    if (cfg.prayTimesAdjust) {
      window.prayTimes.adjust(cfg.prayTimesAdjust);
    }
    if (cfg.prayTimesTune) {
      window.prayTimes.tune(cfg.prayTimesTune);
    }

    const times = window.prayTimes.getTimes(now, [lat, lng], tz, dst, "24h");
    const labels = cfg.prayName || {
      fajr: "Subuh",
      sunrise: "Syuruq",
      dhuhr: "Dzuhur",
      asr: "Ashar",
      maghrib: "Maghrib",
      isha: "Isya"
    };

    prayers = [
      { name: labels.fajr || "Subuh", key: "fajr", time: times.fajr },
      { name: labels.sunrise || "Syuruq", key: "sunrise", time: times.sunrise },
      { name: labels.dhuhr || "Dzuhur", key: "dhuhr", time: times.dhuhr },
      { name: labels.asr || "Ashar", key: "asr", time: times.asr },
      { name: labels.maghrib || "Maghrib", key: "maghrib", time: times.maghrib },
      { name: labels.isha || "Isya", key: "isha", time: times.isha }
    ];

    renderScheduleTable();
    lastCalculatedDay = dayKey;
  }
}

function renderScheduleTable() {
  const list = document.getElementById("schedule-list");
  if (!list) return;

  list.innerHTML = prayers
    .map(
      (p, idx) => `
    <div class="schedule-row ${idx === 0 ? "active" : ""}">
      <span class="prayer-name">${p.name}</span>
      <span class="prayer-time">${p.time}</span>
    </div>`
    )
    .join("");
}

function applyMasjidConfig() {
  const cfg = window.masjidConfig || {};
  const s = cfg.setting || {};

  // 1. Identitas Masjid di Header
  if (s.nama) {
    const nameEl = document.querySelector(".content-mosque-name");
    if (nameEl) nameEl.textContent = s.nama;
  }
  if (s.lokasi) {
    const locEl = document.querySelector(".content-address span");
    if (locEl) locEl.textContent = s.lokasi;
  }
  if (s.kontak_info) {
    const infoEl = document.getElementById("content-extra-text");
    if (infoEl) infoEl.textContent = s.kontak_info;
  }
  if (s.logo) {
    const logoEl = document.getElementById("content-brand-logo");
    if (logoEl) logoEl.src = s.logo;
  }

  // 2. Running Text (Ticker Marquee)
  if (Array.isArray(cfg.running_text) && cfg.running_text.length > 0) {
    const tickerTrack = document.querySelector(".content-ticker-text");
    if (tickerTrack) {
      tickerTrack.innerHTML = cfg.running_text
        .map((t) => `<span class="content-ticker-item">${t}</span>`)
        .join("");
    }
  }

  // 3. Slides dari Config
  if (Array.isArray(cfg.slides) && cfg.slides.length > 0) {
    const globalInterval = cfg.timer?.slide_interval || 10;
    slides = cfg.slides.map((item, idx) => ({
      id: item.id || idx + 1,
      type: item.type || (item.ayat ? "text" : "image"),
      src: item.content || item.src || "",
      alt: item.alt || `Slide informasi ${idx + 1}`,
      duration: typeof item.duration === "number" && item.duration > 0 ? item.duration : globalInterval,
      ayat: item.ayat || item.arabic || "",
      arti: item.arti || item.terjemahan || item.translation || "",
      surah: item.surah || item.surat || item.source || ""
    }));
  }
}

let slides = [
  { src: "assets/img/contoh_slide/info-1777620184.png", alt: "Slide informasi wakaf AC" },
  { src: "assets/img/contoh_slide/info-a.png", alt: "Slide informasi 1" },
  { src: "assets/img/contoh_slide/info-2.png", alt: "Slide informasi 2" },
  { src: "assets/img/contoh_slide/info-3.png", alt: "Slide informasi 3" },
  { src: "assets/img/contoh_slide/info-4.png", alt: "Slide informasi 4" },
  { src: "assets/img/contoh_slide/info-5.png", alt: "Slide informasi 5" },
  { src: "assets/img/contoh_slide/info-6.png", alt: "Slide informasi 6" }
];

// Koreksi manual tanggal Hijriyah jika rukyat lokal Kemenag berbeda (+1, 0, -1 hari)
const HIJRI_OFFSET_DAYS = 0;

const dayFormatter = new Intl.DateTimeFormat("id-ID", { weekday: "long" });
const monthFormatter = new Intl.DateTimeFormat("id-ID", { month: "long" });
const hijriFormatter = new Intl.DateTimeFormat("id-ID-u-ca-islamic-umalqura", {
  day: "numeric",
  month: "long",
  year: "numeric"
});

function pad(value) {
  return String(value).padStart(2, "0");
}

function toTitleCase(value) {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

function getPrayerDate(baseDate, timeText) {
  const [hours, minutes] = timeText.split(":").map(Number);
  const next = new Date(baseDate);
  next.setHours(hours, minutes, 0, 0);
  return next;
}

function getHijriDateText(now) {
  const d = new Date(now);

  const maghrib = prayers.find((p) => p.name === "Maghrib");
  const cfg = window.masjidConfig || {};
  const setting = cfg.setting || {};
  let extraDay = typeof setting.hijri_offset !== "undefined" ? Number(setting.hijri_offset) : HIJRI_OFFSET_DAYS;

  if (maghrib) {
    const maghribTime = getPrayerDate(now, maghrib.time);
    if (now >= maghribTime) {
      extraDay += 1;
    }
  }

  if (extraDay !== 0) {
    d.setDate(d.getDate() + extraDay);
  }

  try {
    const raw = hijriFormatter.format(d);
    const cleaned = raw.replace(/\s*(AH|H)\b/gi, "").trim();
    return `${cleaned} H`;
  } catch (err) {
    return "";
  }
}

function updateClock(now) {
  const clockMain = document.getElementById("clock-main");
  const clockSeconds = document.getElementById("clock-seconds");
  const dateDisplay = document.getElementById("date-display");
  const dateHijri = document.getElementById("date-hijri");

  if (!clockMain || !clockSeconds || !dateDisplay) {
    return;
  }

  const hours = pad(now.getHours());
  const minutes = pad(now.getMinutes());
  const seconds = pad(now.getSeconds());

  // Dukungan kustom nama hari (Ahad, dsb.) dan nama bulan dari config.js
  const cfg = window.masjidConfig || {};
  const dayKeys = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
  const currentDayKey = dayKeys[now.getDay()];
  const dayName = (cfg.dayName && cfg.dayName[currentDayKey])
    ? cfg.dayName[currentDayKey]
    : toTitleCase(dayFormatter.format(now));

  const monthKeys = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
  const currentMonthKey = monthKeys[now.getMonth()];
  const monthName = (cfg.monthName && cfg.monthName[currentMonthKey])
    ? cfg.monthName[currentMonthKey]
    : toTitleCase(monthFormatter.format(now));

  clockMain.textContent = `${hours}.${minutes}`;
  clockSeconds.textContent = seconds;
  dateDisplay.textContent = `${dayName}, ${pad(now.getDate())} ${monthName} ${now.getFullYear()}`;

  if (dateHijri) {
    dateHijri.textContent = getHijriDateText(now);
  }
}

function updateNextPrayer(now) {
  const scheduleRows = Array.from(document.querySelectorAll(".schedule-row"));
  let nextIndex = prayers.findIndex((prayer) => getPrayerDate(now, prayer.time) > now);

  if (nextIndex === -1) {
    nextIndex = 0;
  }

  let nextPrayerDate = getPrayerDate(now, prayers[nextIndex].time);
  if (nextPrayerDate <= now) {
    nextPrayerDate.setDate(nextPrayerDate.getDate() + 1);
  }

  scheduleRows.forEach((row, index) => {
    row.classList.toggle("active", index === nextIndex);
  });
}

function setupBrandLogo() {
  const logo = document.getElementById("content-brand-logo");
  const frame = document.getElementById("content-brand-frame");

  if (!logo || !frame) {
    return;
  }

  const applyLogoShape = () => {
    if (!logo.naturalWidth || !logo.naturalHeight) {
      return;
    }

    const ratio = logo.naturalWidth / logo.naturalHeight;
    let shape = "wide";

    if (ratio < 0.85) {
      shape = "tall";
    } else if (ratio <= 1.15) {
      shape = "square";
    }

    frame.dataset.logoShape = shape;
  };

  if (logo.complete) {
    applyLogoShape();
    return;
  }

  logo.addEventListener("load", applyLogoShape, { once: true });
}

let slideIntervalId = null;
let isSlidePaused = false;

function setupSlides() {
  const slidesRoot = document.getElementById("content-stage-slides");
  const dotsRoot = document.getElementById("content-stage-dots");

  if (!slidesRoot || !dotsRoot || slides.length === 0) {
    return;
  }

  const slideElements = slides.map((slide, index) => {
    const slideItem = document.createElement("figure");
    slideItem.className = "content-stage-slide";
    if (index === 0) {
      slideItem.classList.add("active");
    }

    if (slide.type === "text" || slide.ayat) {
      slideItem.classList.add("content-stage-slide--text");
      slideItem.innerHTML = `
        <div class="slide-quran-card">
          <div class="slide-quran-frame">
            <span class="slide-quran-corner slide-quran-corner--tl" aria-hidden="true"></span>
            <span class="slide-quran-corner slide-quran-corner--tr" aria-hidden="true"></span>
            <span class="slide-quran-corner slide-quran-corner--bl" aria-hidden="true"></span>
            <span class="slide-quran-corner slide-quran-corner--br" aria-hidden="true"></span>

            <div class="slide-quran-header">
              <span class="slide-quran-bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</span>
            </div>

            <div class="slide-quran-body">
              <p class="slide-quran-arabic" dir="rtl">${slide.ayat || slide.src}</p>
              <div class="slide-quran-divider" aria-hidden="true">
                <span class="slide-quran-divider-line"></span>
                <span class="slide-quran-divider-star">◈</span>
                <span class="slide-quran-divider-line"></span>
              </div>
              ${slide.arti ? `<p class="slide-quran-translation">"${slide.arti}"</p>` : ""}
            </div>

            ${slide.surah ? `
            <div class="slide-quran-footer">
              <span class="slide-quran-surah-pill">✦ ${slide.surah} ✦</span>
            </div>` : ""}
          </div>
        </div>
      `;
    } else {
      const image = document.createElement("img");
      image.src = slide.src;
      image.alt = slide.alt;
      image.loading = index === 0 ? "eager" : "lazy";
      slideItem.appendChild(image);
    }

    slidesRoot.appendChild(slideItem);
    return slideItem;
  });

  const dotElements = slides.map((_, index) => {
    const dot = document.createElement("span");
    dot.className = "content-stage-dot";
    if (index === 0) {
      dot.classList.add("active");
    }
    dotsRoot.appendChild(dot);
    return dot;
  });

  let activeIndex = 0;

  function scheduleNextSlide() {
    if (slideIntervalId) {
      clearTimeout(slideIntervalId);
    }
    if (slideElements.length <= 1) return;

    const currentSlide = slides[activeIndex];
    const globalInterval = window.masjidConfig?.timer?.slide_interval || 10;
    const durationSec = (currentSlide && typeof currentSlide.duration === "number" && currentSlide.duration > 0)
      ? currentSlide.duration
      : globalInterval;

    slideIntervalId = setTimeout(() => {
      if (!isSlidePaused) {
        slideElements[activeIndex].classList.remove("active");
        dotElements[activeIndex].classList.remove("active");

        activeIndex = (activeIndex + 1) % slideElements.length;

        slideElements[activeIndex].classList.add("active");
        dotElements[activeIndex].classList.add("active");
      }
      scheduleNextSlide();
    }, durationSec * 1000);
  }

  scheduleNextSlide();
}

// =============================================================================
// WEB AUDIO API SOUND GENERATOR (Synthesized Beep & Chime, Tanpa File Eksternal)
// =============================================================================
let audioCtx = null;
let isAudioUnlocked = false;

function unlockAudio() {
  try {
    if (!audioCtx) {
      const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtxClass) return;
      audioCtx = new AudioCtxClass();
    }
    if (audioCtx && audioCtx.state === "suspended") {
      audioCtx.resume().then(() => {
        isAudioUnlocked = true;
        console.info("[LayarMasjid Audio] AudioContext aktif (suara siap dibunyikan).");
      }).catch(() => {});
    } else if (audioCtx && audioCtx.state === "running") {
      isAudioUnlocked = true;
    }
  } catch (e) {
    // Diabaikan jika browser belum mengizinkan
  }
}

// Buka kunci audio saat pengguna berinteraksi pertama kali (klik/tekan tombol di layar)
["click", "keydown", "touchstart", "pointerdown", "mousedown"].forEach((evt) => {
  window.addEventListener(evt, unlockAudio, { passive: true });
});

function playTone(freq = 988, duration = 0.22, type = "triangle", gainVolume = 0.55) {
  try {
    if (!audioCtx) {
      const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtxClass) return;
      audioCtx = new AudioCtxClass();
    }

    const emitSound = () => {
      try {
        const now = audioCtx.currentTime;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, now);

        // Attack lembut (8ms) agar bebas bunyi klik/pop
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.linearRampToValueAtTime(gainVolume, now + 0.008);

        // Sustain bunyi penuh sampai mendekati akhir durasi
        const sustainEnd = now + Math.max(0.03, duration - 0.035);
        gain.gain.setValueAtTime(gainVolume, sustainEnd);
        gain.gain.linearRampToValueAtTime(0.0001, now + duration);

        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now);
        osc.stop(now + duration);
      } catch (err) {
        console.warn("[LayarMasjid Audio] Gagal output sound:", err);
      }
    };

    if (audioCtx.state === "suspended") {
      audioCtx.resume().then(() => {
        if (audioCtx.state === "running") {
          isAudioUnlocked = true;
          emitSound();
        }
      }).catch(() => {
        console.warn("[LayarMasjid Audio] Browser memblokir autoplay suara. Klik 1x pada layar untuk mengaktifkan audio.");
      });
    } else if (audioCtx.state === "running") {
      emitSound();
    }
  } catch (e) {
    // Diabaikan jika browser memerlukan user gesture pertama
  }
}

// Beep di detik-detik terakhir menjelang adzan (setiap detik berbunyi nyaring)
function playBeepAdzan() {
  playTone(988, 0.22, "triangle", 0.55);
}

// Chime saat detik ke-0 (waktu adzan tepat tiba)
function playChimeAzan() {
  playTone(659.25, 0.45, "triangle", 0.5);
  setTimeout(() => playTone(880, 0.75, "triangle", 0.55), 260);
}

// Beep di detik-detik terakhir countdown iqomah (setiap detik berbunyi)
function playBeepIqomah() {
  playTone(880, 0.22, "triangle", 0.55);
}

// Chime penanda waktu iqomah berkumandang (masuk sholat)
function playChimeIqomah() {
  playTone(523.25, 0.5, "triangle", 0.5);
  setTimeout(() => playTone(659.25, 0.85, "triangle", 0.55), 280);
}

// =============================================================================
// STATE MACHINE 5 TAHAPAN (LIVE & PREVIEW)
// =============================================================================
// 1. menjelang_azan (countdown MM:SS dari waitAdzan s.d. 0, beep di 10 detik terakhir)
// 2. saat_azan (halaman adzan selama lamaAdzan)
// 3. countdown_iqomah (countdown MM:SS dari lamaIqomah s.d. 0, beep di 10 detik terakhir)
// 4. saat_sholat (mode sholat khusyuk & hening selama lamaSholat)
// 5. null (kembali ke tampilan awal)

let simulationState = null;

function getPrayerIqomahMinutes(prayerName) {
  const cfg = window.masjidConfig || {};
  if (cfg.iqomahTimer) {
    const map = {
      "Subuh": cfg.iqomahTimer.fajr,
      "Dzuhur": cfg.iqomahTimer.dhuhr,
      "Ashar": cfg.iqomahTimer.asr,
      "Maghrib": cfg.iqomahTimer.maghrib,
      "Isya": cfg.iqomahTimer.isha
    };
    if (typeof map[prayerName] !== "undefined") {
      return Number(map[prayerName]);
    }
  }
  return durasiIqomahPerSholat[prayerName] || lamaIqomah;
}

function getPrayerLifecycleState(now) {
  const timers = getTimerConfig();

  // Mode Simulasi / Preview
  if (simulationState) {
    const s = simulationState;
    s.remainingSec -= 1;

    // Tahap 1: Menjelang Adzan (Countdown MM:SS)
    if (s.phase === "menjelang_azan") {
      if (s.remainingSec <= 0) {
        if (s.autoNext) {
          simulationState = {
            phase: "saat_azan",
            prayerName: s.prayerName,
            remainingSec: s.demoMode ? 5 : timers.lamaAdzan * 60,
            autoNext: s.autoNext,
            demoMode: s.demoMode
          };
        } else {
          simulationState = null;
          return null;
        }
      }
      return {
        phase: "menjelang_azan",
        prayerName: s.prayerName,
        diffSeconds: Math.max(0, s.remainingSec)
      };
    }

    // Tahap 2: Saat Azan
    if (s.phase === "saat_azan") {
      if (s.remainingSec <= 0) {
        if (s.autoNext) {
          simulationState = {
            phase: "countdown_iqomah",
            prayerName: s.prayerName,
            remainingSec: s.demoMode ? 15 : getPrayerIqomahMinutes(s.prayerName) * 60,
            autoNext: s.autoNext,
            demoMode: s.demoMode
          };
        } else {
          simulationState = null;
          return null;
        }
      }
      return {
        phase: "saat_azan",
        prayerName: s.prayerName,
        diffSeconds: s.remainingSec
      };
    }

    // Tahap 3: Countdown Iqomah
    if (s.phase === "countdown_iqomah") {
      if (s.remainingSec <= 0) {
        if (s.autoNext) {
          simulationState = {
            phase: "saat_sholat",
            prayerName: s.prayerName,
            remainingSec: s.demoMode ? 6 : timers.lamaSholat * 60,
            autoNext: false,
            demoMode: s.demoMode
          };
        } else {
          simulationState = null;
          return null;
        }
      }
      return {
        phase: "countdown_iqomah",
        prayerName: s.prayerName,
        diffSeconds: Math.max(0, s.remainingSec)
      };
    }

    // Tahap 4: Saat Sholat
    if (s.phase === "saat_sholat") {
      if (s.remainingSec <= 0) {
        simulationState = null;
        return null; // Tahap 5: Kembali ke tampilan awal
      }
      return {
        phase: "saat_sholat",
        prayerName: s.prayerName,
        diffSeconds: s.remainingSec
      };
    }
  }

  // Waktu Live Aktual
  const fardhPrayers = prayers.filter((p) => ADZAN_PRAYERS.includes(p.name));

  for (const prayer of fardhPrayers) {
    const baseToday = getPrayerDate(now, prayer.time);
    const candidateTimes = [
      baseToday,
      new Date(baseToday.getTime() + 86400000), // esok
      new Date(baseToday.getTime() - 86400000)  // kemarin
    ];

    const waitSec = timers.waitAdzan * 60;
    const adzanSec = timers.lamaAdzan * 60;
    const iqomahSec = getPrayerIqomahMinutes(prayer.name) * 60;
    const sholatSec = timers.lamaSholat * 60;

    for (const pTime of candidateTimes) {
      const deltaSec = Math.floor((now.getTime() - pTime.getTime()) / 1000);

      // 1. Menjelang Adzan: -waitSec s.d. 0 detik (countdown MM:SS mundur ke 00:00)
      if (deltaSec >= -waitSec && deltaSec < 0) {
        return {
          phase: "menjelang_azan",
          prayerName: prayer.name,
          diffSeconds: Math.abs(deltaSec)
        };
      }

      // 2. Saat Azan: 0 s.d. adzanSec
      if (deltaSec >= 0 && deltaSec < adzanSec) {
        return {
          phase: "saat_azan",
          prayerName: prayer.name,
          diffSeconds: deltaSec
        };
      }

      // 3. Countdown Iqomah: adzanSec s.d. (adzanSec + iqomahSec)
      const endIqomah = adzanSec + iqomahSec;
      if (deltaSec >= adzanSec && deltaSec < endIqomah) {
        const remainingIqomah = endIqomah - deltaSec;
        return {
          phase: "countdown_iqomah",
          prayerName: prayer.name,
          diffSeconds: remainingIqomah
        };
      }

      // 4. Saat Sholat: endIqomah s.d. (endIqomah + sholatSec)
      const endSholat = endIqomah + sholatSec;
      if (deltaSec >= endIqomah && deltaSec < endSholat) {
        return {
          phase: "saat_sholat",
          prayerName: prayer.name,
          diffSeconds: endSholat - deltaSec
        };
      }

      // 5. Kembali ke awal (di luar rentang di atas -> return null)
    }
  }

  return null;
}

// =============================================================================
// RENDER OVERLAY (MENAMPILKAN TAHAP AKTIF)
// =============================================================================
let lastSoundKey = null;

function renderLifecycleScreen(state, now) {
  const screen = document.getElementById("adzan-screen");
  if (!screen) return;

  const timers = getTimerConfig();

  // TAHAP 5: KEMBALI KE TAMPILAN AWAL
  if (!state) {
    if (screen.classList.contains("active")) {
      screen.classList.remove("active");
      screen.classList.remove("phase--sholat");
      screen.setAttribute("aria-hidden", "true");
      isSlidePaused = false;
    }
    lastSoundKey = null;
    return;
  }

  // Aktifkan overlay & jeda slide background
  if (!screen.classList.contains("active")) {
    screen.classList.add("active");
    screen.setAttribute("aria-hidden", "false");
    isSlidePaused = true;
  }

  // Update Jam & Tanggal pada Header Overlay
  const adzanClockTime = document.getElementById("adzan-clock-time");
  const adzanClockDate = document.getElementById("adzan-clock-date");
  if (adzanClockTime) {
    adzanClockTime.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
  }
  if (adzanClockDate) {
    const dayName = toTitleCase(dayFormatter.format(now));
    const monthName = toTitleCase(monthFormatter.format(now));
    const hijriText = getHijriDateText(now);
    adzanClockDate.textContent = `${dayName}, ${pad(now.getDate())} ${monthName} ${now.getFullYear()} ${hijriText ? "• " + hijriText : ""}`;
  }

  const phase1 = document.getElementById("phase-countdown-adzan");
  const phase2 = document.getElementById("phase-saat-adzan");
  const phase3 = document.getElementById("phase-countdown-iqomah");
  const phase4 = document.getElementById("phase-saat-sholat");

  // Mode gelap khusus saat sholat
  screen.classList.toggle("phase--sholat", state.phase === "saat_sholat");

  // 1. FASE MENJELANG AZAN (Countdown MM:SS, beep di detik-detik terakhir)
  if (state.phase === "menjelang_azan") {
    if (phase1) phase1.classList.add("active");
    if (phase2) phase2.classList.remove("active");
    if (phase3) phase3.classList.remove("active");
    if (phase4) phase4.classList.remove("active");

    const targetName = document.getElementById("adzan-target-prayer-name");
    const timerDigits = document.getElementById("adzan-timer-digits");
    const timerBox = document.getElementById("adzan-timer-box");

    if (targetName) targetName.textContent = state.prayerName.toUpperCase();

    const m = Math.floor(state.diffSeconds / 60);
    const s = state.diffSeconds % 60;
    if (timerDigits) {
      timerDigits.textContent = `${pad(m)}:${pad(s)}`;
    }

    // Detik-detik terakhir: beri tanda urgent & bunyikan beep tiap detik
    const isUrgent = state.diffSeconds <= timers.countDownAdzanBeep && state.diffSeconds > 0;
    if (timerBox) {
      timerBox.classList.toggle("urgent", isUrgent);
    }

    if (isUrgent) {
      const soundKey = `adzan-beep-${state.diffSeconds}`;
      if (lastSoundKey !== soundKey) {
        lastSoundKey = soundKey;
        playBeepAdzan();
      }
    }
  }

  // 2. FASE SAAT AZAN (Halaman Azan)
  else if (state.phase === "saat_azan") {
    if (phase1) phase1.classList.remove("active");
    if (phase2) phase2.classList.add("active");
    if (phase3) phase3.classList.remove("active");
    if (phase4) phase4.classList.remove("active");

    const curName = document.getElementById("adzan-current-prayer-name");
    if (curName) curName.textContent = state.prayerName.toUpperCase();

    // Bunyikan nada pembuka chime saat awal azan
    const soundKey = `azan-entry-${state.prayerName}`;
    if (lastSoundKey !== soundKey) {
      lastSoundKey = soundKey;
      playChimeAzan();
    }
  }

  // 3. FASE COUNTDOWN IQOMAH (Beep di detik-detik terakhir)
  else if (state.phase === "countdown_iqomah") {
    if (phase1) phase1.classList.remove("active");
    if (phase2) phase2.classList.remove("active");
    if (phase3) phase3.classList.add("active");
    if (phase4) phase4.classList.remove("active");

    const iqName = document.getElementById("iqomah-prayer-name");
    const iqDigits = document.getElementById("iqomah-timer-digits");
    const iqBox = document.getElementById("iqomah-timer-box");

    if (iqName) iqName.textContent = state.prayerName.toUpperCase();

    const m = Math.floor(state.diffSeconds / 60);
    const s = state.diffSeconds % 60;
    if (iqDigits) {
      iqDigits.textContent = `${pad(m)}:${pad(s)}`;
    }

    // Detik-detik terakhir: beri tanda urgent & bunyikan beep tiap detik
    const isUrgent = state.diffSeconds <= timers.countDownAdzanBeep && state.diffSeconds > 0;
    if (iqBox) {
      iqBox.classList.toggle("urgent", isUrgent);
    }

    if (isUrgent) {
      const soundKey = `iqomah-beep-${state.diffSeconds}`;
      if (lastSoundKey !== soundKey) {
        lastSoundKey = soundKey;
        playBeepIqomah();
      }
    }

    // Tepat detik ke-0: bunyikan chime masuk sholat
    if (state.diffSeconds === 0) {
      const soundKey = "iqomah-entry-sholat";
      if (lastSoundKey !== soundKey) {
        lastSoundKey = soundKey;
        playChimeIqomah();
      }
    }
  }

  // 4. FASE SAAT SHOLAT (Layar Khusyuk & Hening)
  else if (state.phase === "saat_sholat") {
    if (phase1) phase1.classList.remove("active");
    if (phase2) phase2.classList.remove("active");
    if (phase3) phase3.classList.remove("active");
    if (phase4) phase4.classList.add("active");
  }
}

// =============================================================================
// PREVIEW CONTROLLER & UJI TAHAPAN (Uji Tombol & Shortcut Keyboard 'A')
// =============================================================================
window.testMenjelangAzan = function(prayerName = "Dzuhur", durationSec) {
  const timers = getTimerConfig();
  const sec = typeof durationSec === "number" ? durationSec : timers.waitAdzan * 60;
  // Menampilkan countdown level menit & detik (sesuai timer.wait_adzan di config)
  // Bunyi beep otomatis berbunyi di 10 detik terakhir
  simulationState = {
    phase: "menjelang_azan",
    prayerName: prayerName,
    remainingSec: sec,
    autoNext: true,
    demoMode: false
  };
  tick();
};

window.testSaatAzan = function(prayerName = "Dzuhur") {
  const timers = getTimerConfig();
  simulationState = {
    phase: "saat_azan",
    prayerName: prayerName,
    remainingSec: timers.lamaAdzan * 60,
    autoNext: false,
    demoMode: false
  };
  tick();
};

window.testCountdownIqomah = function(prayerName = "Dzuhur", durationSec = 15) {
  // Default preview 15 detik (00:15) agar langsung mendengar beep di 10 detik terakhir
  // Bisa dipanggil window.testCountdownIqomah("Dzuhur", getPrayerIqomahMinutes(prayerName) * 60) jika ingin durasi penuh
  simulationState = {
    phase: "countdown_iqomah",
    prayerName: prayerName,
    remainingSec: durationSec,
    autoNext: true,
    demoMode: false
  };
  tick();
};

window.testSaatSholat = function(prayerName = "Dzuhur") {
  const timers = getTimerConfig();
  simulationState = {
    phase: "saat_sholat",
    prayerName: prayerName,
    remainingSec: timers.lamaSholat * 60,
    autoNext: false,
    demoMode: false
  };
  tick();
};

window.testDemoLengkap = function(prayerName = "Dzuhur") {
  // Demo otomatis lengkap ~41 detik (seluruh 5 tahapan berurutan):
  // 15 detik Menjelang Adzan (beep di 10 detik terakhir) ->
  // 5 detik Saat Azan ->
  // 15 detik Countdown Iqomah (beep di 10 detik terakhir) ->
  // 6 detik Saat Sholat ->
  // Kembali ke tampilan normal awal!
  simulationState = {
    phase: "menjelang_azan",
    prayerName: prayerName,
    remainingSec: 15,
    autoNext: true,
    demoMode: true
  };
  tick();
};

window.testStop = function() {
  simulationState = null;
  const screen = document.getElementById("adzan-screen");
  if (screen) {
    screen.classList.remove("active");
    screen.classList.remove("phase--sholat");
    screen.setAttribute("aria-hidden", "true");
  }
  isSlidePaused = false;
  tick();
};

function setupAdzanTestControls() {
  const toolbar = document.getElementById("adzan-test-toolbar");
  const signature = document.querySelector(".sidebar-signature");
  const btnCountdown = document.getElementById("btn-test-countdown");
  const btnAdzan = document.getElementById("btn-test-adzan");
  const btnIqomah = document.getElementById("btn-test-iqomah");
  const btnSholat = document.getElementById("btn-test-sholat");
  const btnDemo = document.getElementById("btn-test-demo");
  const btnClose = document.getElementById("btn-test-close");

  const toggleToolbar = () => {
    if (toolbar) toolbar.classList.toggle("visible");
  };

  // Shortcut keyboard: Tekan huruf 'a' atau 'A' untuk membuka/menutup toolbar
  window.addEventListener("keydown", (e) => {
    if (e.key === "a" || e.key === "A") {
      toggleToolbar();
    }
  });

  if (signature) {
    signature.style.cursor = "pointer";
    signature.title = "Klik atau tekan tombol 'A' untuk membuka kontrol uji coba";
    signature.addEventListener("click", toggleToolbar);
  }

  if (btnCountdown) {
    btnCountdown.addEventListener("click", () => {
      window.testMenjelangAzan("Dzuhur");
    });
  }

  if (btnAdzan) {
    btnAdzan.addEventListener("click", () => {
      window.testSaatAzan("Dzuhur");
    });
  }

  if (btnIqomah) {
    btnIqomah.addEventListener("click", () => {
      window.testCountdownIqomah("Dzuhur", 15);
    });
  }

  if (btnSholat) {
    btnSholat.addEventListener("click", () => {
      window.testSaatSholat("Dzuhur");
    });
  }

  if (btnDemo) {
    btnDemo.addEventListener("click", () => {
      window.testDemoLengkap("Dzuhur");
    });
  }

  if (btnClose) {
    btnClose.addEventListener("click", () => {
      window.testStop();
    });
  }
}

// =============================================================================
// MAIN TICK & INITIALIZATION
// =============================================================================
function tick() {
  const now = getCurrentNow();
  calculateDailyPrayers(now); // Otomatis recalculate jika berganti hari (00:00)
  updateClock(now);
  updateNextPrayer(now);

  const state = getPrayerLifecycleState(now);
  renderLifecycleScreen(state, now);
}

function startClock() {
  initTimeOffset();
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
