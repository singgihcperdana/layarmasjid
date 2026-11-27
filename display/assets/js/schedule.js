// =============================================================================
// SCHEDULE & CLOCK MODULE (Perhitungan PrayTimes, Jam, Kalender Hijriyah, & Identitas)
// Layar Masjid Digital Signage
// =============================================================================

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

function calculateDailyPrayers(now, forceRecalculate = false) {
  const dayKey = `${now.getFullYear()}-${now.getMonth() + 1}-${now.getDate()}`;
  if (!forceRecalculate && lastCalculatedDay === dayKey && prayers.length > 0) {
    return;
  }

  const cfg = window.masjidConfig || {};
  const setting = cfg.setting || {};
  const lat = setting.latitude || -6.14;
  const lng = setting.longitude || 106.59;
  const tz = setting.timeZone || 7;
  const dst = setting.dst || 0;

  if (window.prayTimes) {
    if (setting.imsak_minutes) {
      window.prayTimes.adjust({ imsak: `${setting.imsak_minutes} min` });
    }
    if (cfg.prayTimesAdjust) {
      window.prayTimes.adjust(cfg.prayTimesAdjust);
    }
    if (cfg.prayTimesTune) {
      window.prayTimes.tune(cfg.prayTimesTune);
    }

    const times = window.prayTimes.getTimes(now, [lat, lng], tz, dst, "24h");
    const labels = cfg.prayName || {
      imsak: "Imsak",
      fajr: "Subuh",
      sunrise: "Syuruq",
      dhuhr: "Dzuhur",
      asr: "Ashar",
      maghrib: "Maghrib",
      isha: "Isya"
    };

    const imsakMode = setting.imsak_mode || "auto";
    const isImsakActive = imsakMode === "always" || (imsakMode === "auto" && isRamadanMonth(now, times.maghrib));

    prayers = [];
    if (isImsakActive && times.imsak) {
      prayers.push({ name: labels.imsak || "Imsak", key: "imsak", time: times.imsak });
    }
    prayers.push(
      { name: labels.fajr || "Subuh", key: "fajr", time: times.fajr },
      { name: labels.sunrise || "Syuruq", key: "sunrise", time: times.sunrise },
      { name: labels.dhuhr || "Dzuhur", key: "dhuhr", time: times.dhuhr },
      { name: labels.asr || "Ashar", key: "asr", time: times.asr },
      { name: labels.maghrib || "Maghrib", key: "maghrib", time: times.maghrib },
      { name: labels.isha || "Isya", key: "isha", time: times.isha }
    );

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
  const extraInfoBox = document.getElementById("content-extra-info");
  const infoEl = document.getElementById("content-extra-text");
  if (s.kontak_info && s.kontak_info.trim() !== "") {
    if (infoEl) infoEl.textContent = s.kontak_info;
    if (extraInfoBox) extraInfoBox.style.display = "";
  } else {
    if (extraInfoBox) extraInfoBox.style.display = "none";
  }
  const logoEl = document.getElementById("content-brand-logo");
  const frameEl = document.getElementById("content-brand-frame");
  if (s.logo && s.logo.trim() !== "") {
    if (logoEl) {
      const basePath = s.logo.startsWith("uploads/") ? `../${s.logo}` : s.logo;
      const v = window.masjidConfig?.sync_hash ? `?v=${window.masjidConfig.sync_hash}` : "";
      logoEl.src = `${basePath}${v}`;
      logoEl.style.display = "";
    }
    if (frameEl) frameEl.style.display = "";
  } else {
    // Logo kosong: sembunyikan bingkai logo agar nama masjid melebar penuh
    if (logoEl) {
      logoEl.removeAttribute("src");
      logoEl.style.display = "none";
    }
    if (frameEl) frameEl.style.display = "none";
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

  updateTickerSpeed();
}

function updateTickerSpeed() {
  const tickerTrack = document.querySelector(".content-ticker-text");
  if (!tickerTrack) return;

  const cfg = window.masjidConfig || {};
  const t = cfg.timer || {};
  const s = cfg.setting || {};
  const userSpeed = Number(t.kecepatan_running_text || t.running_text_speed || s.kecepatan_running_text || 5);
  const speedScale = Math.min(10, Math.max(1, isNaN(userSpeed) ? 5 : userSpeed));

  // Kecepatan referensi pada resolusi Full HD 1920 (skala 1 = 45 px/s s.d. skala 10 = 180 px/s)
  const basePxPerSec = 45 + ((speedScale - 1) * 15);

  // Normalisasi skala layar terhadap lebar 1920px agar kecepatan optik konsisten di semua resolusi
  const screenScale = Math.max(0.4, window.innerWidth / 1920);
  const effectivePxPerSec = basePxPerSec * screenScale;

  // Jarak tempuh = panjang teks + lebar container
  const textWidth = tickerTrack.scrollWidth || 1200;
  const containerWidth = tickerTrack.parentElement ? tickerTrack.parentElement.clientWidth : window.innerWidth;
  const totalDistance = textWidth + containerWidth;

  const durationSec = Math.max(8, Math.round(totalDistance / effectivePxPerSec));
  tickerTrack.style.setProperty("--ticker-duration", `${durationSec}s`);
  tickerTrack.style.animationDuration = `${durationSec}s`;
}

window.addEventListener("resize", updateTickerSpeed);

function getHijriDateObject(now, maghribTimeStr = null) {
  const d = new Date(now);
  const cfg = window.masjidConfig || {};
  const setting = cfg.setting || {};
  let extraDay = typeof setting.hijri_offset !== "undefined" ? Number(setting.hijri_offset) : HIJRI_OFFSET_DAYS;

  const mTime = maghribTimeStr || prayers.find((p) => p.key === "maghrib" || p.name === "Maghrib")?.time;
  if (mTime) {
    const maghribDate = getPrayerDate(now, mTime);
    if (now >= maghribDate) {
      extraDay += 1;
    }
  }

  if (extraDay !== 0) {
    d.setDate(d.getDate() + extraDay);
  }
  return d;
}

function isRamadanMonth(now, maghribTimeStr = null) {
  const d = getHijriDateObject(now, maghribTimeStr);
  try {
    const hijriMonthFormatter = new Intl.DateTimeFormat("en-u-ca-islamic-umalqura-nu-latn", { month: "numeric" });
    if (Number(hijriMonthFormatter.format(d)) === 9) {
      return true;
    }
  } catch (err) {}
  try {
    const raw = hijriFormatter.format(d);
    return /ramadan|ramadhan/i.test(raw);
  } catch (err) {
    return false;
  }
}

function getHijriDateText(now) {
  const d = getHijriDateObject(now);
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
