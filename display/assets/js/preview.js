// =============================================================================
// PREVIEW CONTROLLER & UJI TAHAPAN (Uji Tombol & Shortcut Keyboard 'A')
// Layar Masjid Digital Signage
// =============================================================================

window.testMenjelangAzan = function(prayerName = "Dzuhur", durationSec) {
  const timers = getTimerConfig();
  const sec = typeof durationSec === "number" ? durationSec : timers.waitAdzan * 60;
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
    isJumat: false,
    prayerName: prayerName,
    remainingSec: timers.lamaSholat * 60,
    autoNext: false,
    demoMode: false
  };
  tick();
};

window.testModeJumat = function(durationSec) {
  const timers = getTimerConfig();
  const sec = typeof durationSec === "number" ? durationSec : timers.lamaJumat * 60;
  simulationState = {
    phase: "saat_sholat",
    isJumat: true,
    prayerName: "Dzuhur",
    remainingSec: sec,
    autoNext: false,
    demoMode: false
  };
  tick();
};

window.testMenjelangJumat = function(durationSec = 15) {
  simulationState = {
    phase: "menjelang_azan",
    isJumat: true,
    prayerName: "Dzuhur",
    remainingSec: durationSec,
    autoNext: true,
    demoMode: true
  };
  tick();
};

window.testDemoLengkap = function(prayerName = "Dzuhur") {
  // Demo otomatis lengkap seluruh tahapan:
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
  resumeSlides();
  tick();
};

function setupAdzanTestControls() {
  const toolbar = document.getElementById("adzan-test-toolbar");
  const signature = document.querySelector(".sidebar-signature");
  const btnCountdown = document.getElementById("btn-test-countdown");
  const btnAdzan = document.getElementById("btn-test-adzan");
  const btnIqomah = document.getElementById("btn-test-iqomah");
  const btnSholat = document.getElementById("btn-test-sholat");
  const btnMenjelangJumat = document.getElementById("btn-test-menjelang-jumat");
  const btnJumat = document.getElementById("btn-test-jumat");
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

  if (btnMenjelangJumat) {
    btnMenjelangJumat.addEventListener("click", () => {
      window.testMenjelangJumat(15);
    });
  }

  if (btnJumat) {
    btnJumat.addEventListener("click", () => {
      window.testModeJumat();
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
