// =============================================================================
// AUDIO MODULE (Web Audio API Synthesizer - Beep & Chime Tanpa File Eksternal)
// Layar Masjid Digital Signage
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
