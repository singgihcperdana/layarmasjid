// =============================================================================
// ADZAN & PRAYER LIFECYCLE STATE MACHINE
// Layar Masjid Digital Signage
// =============================================================================
// 1. menjelang_azan (countdown MM:SS dari waitAdzan s.d. 0, beep di 10 detik terakhir)
// 2. saat_azan (halaman adzan selama lamaAdzan)
// 3. countdown_iqomah (countdown MM:SS dari lamaIqomah s.d. 0, beep di 10 detik terakhir)
// 4. saat_sholat (mode sholat khusyuk & hening selama lamaSholat)
// 5. null (kembali ke tampilan awal)

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
    countDownIqomahBeep: typeof t.countdown_beep !== "undefined" ? Number(t.countdown_beep) : countDownIqomahBeep,
    lamaAdzan: typeof t.adzan !== "undefined" ? Number(t.adzan) : lamaAdzan,
    lamaSholat: typeof t.sholat !== "undefined" ? Number(t.sholat) : lamaSholat,
    lamaJumat: typeof t.jumat !== "undefined" ? Number(t.jumat) : 45
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

    // Tahap 4: Saat Sholat (termasuk Mode Jum'at)
    if (s.phase === "saat_sholat") {
      if (s.remainingSec <= 0) {
        simulationState = null;
        return null; // Tahap 5: Kembali ke tampilan awal
      }
      return {
        phase: "saat_sholat",
        isJumat: !!s.isJumat,
        prayerName: s.prayerName,
        diffSeconds: s.remainingSec
      };
    }
  }

  // Waktu Live Aktual
  const fardhPrayers = prayers.filter((p) => ADZAN_PRAYERS.includes(p.name));
  const isFriday = now.getDay() === 5;

  for (const prayer of fardhPrayers) {
    const isFridayDhuhr = isFriday && prayer.name === "Dzuhur";
    const baseToday = getPrayerDate(now, prayer.time);
    const candidateTimes = [
      baseToday,
      new Date(baseToday.getTime() + 86400000), // esok
      new Date(baseToday.getTime() - 86400000)  // kemarin
    ];

    const waitSec   = timers.waitAdzan * 60;
    const adzanSec  = timers.lamaAdzan * 60;
    const iqomahSec = getPrayerIqomahMinutes(prayer.name) * 60;
    const sholatSec = timers.lamaSholat * 60;
    const jumatSec  = timers.lamaJumat * 60;

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

      // Khusus Hari Jum'at pada waktu Dzuhur: Langsung masuk ke Mode Jum'at (Layar Hening Khutbah & Sholat)
      if (isFridayDhuhr && jumatSec > 0) {
        if (deltaSec >= 0 && deltaSec < jumatSec) {
          return {
            phase: "saat_sholat",
            isJumat: true,
            prayerName: prayer.name,
            diffSeconds: jumatSec - deltaSec
          };
        }
        continue;
      }

      // 2. Saat Azan (Hari Biasa): 0 s.d. adzanSec
      if (deltaSec >= 0 && deltaSec < adzanSec) {
        return {
          phase: "saat_azan",
          prayerName: prayer.name,
          diffSeconds: deltaSec
        };
      }

      // 3. Countdown Iqomah (Hari Biasa): adzanSec s.d. (adzanSec + iqomahSec)
      const endIqomah = adzanSec + iqomahSec;
      if (deltaSec >= adzanSec && deltaSec < endIqomah) {
        const remainingIqomah = endIqomah - deltaSec;
        return {
          phase: "countdown_iqomah",
          prayerName: prayer.name,
          diffSeconds: remainingIqomah
        };
      }

      // 4. Saat Sholat (Hari Biasa): endIqomah s.d. (endIqomah + sholatSec)
      const endSholat = endIqomah + sholatSec;
      if (deltaSec >= endIqomah && deltaSec < endSholat) {
        return {
          phase: "saat_sholat",
          isJumat: false,
          prayerName: prayer.name,
          diffSeconds: endSholat - deltaSec
        };
      }
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
      resumeSlides();
    }
    lastSoundKey = null;
    return;
  }

  // Aktifkan overlay & jeda slide background
  if (!screen.classList.contains("active")) {
    screen.classList.add("active");
    screen.setAttribute("aria-hidden", "false");
    pauseSlides();
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

  const phaseElements = {
    menjelang_azan: document.getElementById("phase-countdown-adzan"),
    saat_azan: document.getElementById("phase-saat-adzan"),
    countdown_iqomah: document.getElementById("phase-countdown-iqomah"),
    saat_sholat: document.getElementById("phase-saat-sholat")
  };

  // Toggle status aktif tiap fase secara seragam
  Object.entries(phaseElements).forEach(([phaseKey, el]) => {
    if (el) el.classList.toggle("active", phaseKey === state.phase);
  });

  // Mode gelap khusus saat sholat
  screen.classList.toggle("phase--sholat", state.phase === "saat_sholat");

  // 1. FASE MENJELANG AZAN (Countdown MM:SS, beep di detik-detik terakhir)
  if (state.phase === "menjelang_azan") {
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
    const isUrgent = state.diffSeconds <= timers.countDownIqomahBeep && state.diffSeconds > 0;
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

  // 4. FASE SAAT SHOLAT / MODE JUM'AT (Mode Khusyuk & Hening)
  else if (state.phase === "saat_sholat") {
    const bannerText = document.getElementById("sholat-banner-text");
    const hadithArabic = document.getElementById("sholat-hadith-arabic");
    const hadithText = document.getElementById("sholat-hadith-text");

    if (state.isJumat) {
      if (bannerText) bannerText.textContent = "DENGARKAN KHUTBAH • HP DINONAKTIFKAN";
      if (hadithArabic) hadithArabic.textContent = "إِذَا قُلْتَ لِصَاحِبِكَ يَوْمَ الْجُمُعَةِ: «أَنْصِتْ»، وَالإِمَامُ يَخْطُبُ، فَقَدْ لَغَوْتَ";
      if (hadithText) {
        hadithText.innerHTML = `&ldquo;Jika engkau berkata kepada sahabatmu pada hari Jum'at: 'Diamlah!', saat imam sedang berkhutbah, maka sungguh engkau telah berbuat sia-sia.&rdquo;<br><span class="sholat-hadith-source">(HR. Bukhari &amp; Muslim)</span>`;
      }
    } else {
      if (bannerText) bannerText.textContent = "MODE HENING • HP DINONAKTIFKAN";
      if (hadithArabic) hadithArabic.textContent = "سَوُّوا صُفُوفَكُمْ ؛ فَإنَّ تَسْوِيَةَ الصَّفِّ مِنْ تَمَامِ الصَّلاَةِ";
      if (hadithText) {
        hadithText.innerHTML = `&ldquo;Luruskan shaf-shaf kalian, karena meluruskan shaf termasuk kesempurnaan sholat.&rdquo;<br><span class="sholat-hadith-source">(HR. Bukhari &amp; Muslim)</span>`;
      }
    }

    // Bunyikan nada masuk sholat/Jum'at di awal
    const soundKey = state.isJumat ? "jumat-entry" : "sholat-entry";
    if (lastSoundKey !== soundKey) {
      lastSoundKey = soundKey;
      playChimeIqomah();
    }
  }
}
