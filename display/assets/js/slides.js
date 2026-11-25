// =============================================================================
// SLIDES CAROUSEL MODULE (Engine Player Slide Informasi, Quran/Hadits, & Info)
// Layar Masjid Digital Signage
// =============================================================================

let slides = [
  { src: "assets/img/contoh_slide/info-1777620184.png", alt: "Slide informasi wakaf AC" },
  { src: "assets/img/contoh_slide/info-a.png", alt: "Slide informasi 1" },
  { src: "assets/img/contoh_slide/info-2.png", alt: "Slide informasi 2" },
  { src: "assets/img/contoh_slide/info-3.png", alt: "Slide informasi 3" },
  { src: "assets/img/contoh_slide/info-4.png", alt: "Slide informasi 4" },
  { src: "assets/img/contoh_slide/info-5.png", alt: "Slide informasi 5" },
  { src: "assets/img/contoh_slide/info-6.png", alt: "Slide informasi 6" }
];

let slideIntervalId = null;
let isSlidePaused = false;

function pauseSlides() {
  isSlidePaused = true;
}

function resumeSlides() {
  isSlidePaused = false;
}

function initSlidesConfig() {
  const cfg = window.masjidConfig || {};
  if (Array.isArray(cfg.slides) && cfg.slides.length > 0) {
    const globalInterval = cfg.timer?.slide_interval || 10;
    slides = cfg.slides.map((item, idx) => ({
      ...item,
      id: item.id || idx + 1,
      type: item.type || (item.ayat ? "quranHadist" : (typeof item.content === "string" && item.content.match(/\.(png|jpe?g|webp|gif|svg)$/i) ? "image" : "info")),
      src: item.content || item.src || "",
      alt: item.alt || item.title || `Slide informasi ${idx + 1}`,
      duration: typeof item.duration === "number" && item.duration > 0 ? item.duration : globalInterval,
      ayat: item.ayat || item.arabic || "",
      arti: item.arti || item.terjemahan || item.translation || "",
      surah: item.surah || item.surat || item.source || "",
      title: item.title || item.judul || "",
      content: item.content || item.text || item.src || ""
    }));
  }
}

function setupSlides() {
  if (slideIntervalId) {
    clearTimeout(slideIntervalId);
    slideIntervalId = null;
  }

  initSlidesConfig();

  const slidesRoot = document.getElementById("content-stage-slides");
  const dotsRoot = document.getElementById("content-stage-dots");

  if (!slidesRoot || !dotsRoot || slides.length === 0) {
    return;
  }

  slidesRoot.innerHTML = "";
  dotsRoot.innerHTML = "";

  const slideElements = slides.map((slide, index) => {
    const slideItem = document.createElement("figure");
    slideItem.className = "content-stage-slide";
    if (index === 0) {
      slideItem.classList.add("active");
    }

    // Template 1: Dalil Quran & Hadits
    if (slide.type === "quranHadist" || slide.type === "quran" || slide.ayat) {
      slideItem.classList.add("content-stage-slide--text");
      slideItem.innerHTML = `
        <div class="slide-quran-card">
          <div class="slide-quran-frame">
            <div class="slide-quran-body">
              <p class="slide-quran-arabic" dir="rtl">${slide.ayat || slide.src}</p>
              <div class="slide-quran-divider" aria-hidden="true">
                <span class="slide-quran-divider-line"></span>
                <span class="slide-quran-divider-star">◈</span>
                <span class="slide-quran-divider-line"></span>
              </div>
              ${slide.arti ? `<p class="slide-quran-translation">"${slide.arti}"</p>` : ""}
              ${slide.surah ? `
              <div class="slide-quran-footer">
                <span class="slide-quran-surah-pill">✦ ${slide.surah} ✦</span>
              </div>` : ""}
            </div>
          </div>
        </div>
      `;
    }
    // Template 2: Custom / Free Text / Info Kajian
    else if (
      slide.type === "info" ||
      slide.type === "text" ||
      slide.type === "custom" ||
      slide.type === "html" ||
      slide.type === "free" ||
      (typeof slide.content === "string" && slide.content.length > 0 && !slide.content.match(/\.(png|jpe?g|webp|gif|svg)$/i))
    ) {
      slideItem.classList.add("content-stage-slide--custom");
      slideItem.innerHTML = `
        <div class="slide-custom-card">
          <div class="slide-custom-frame">
            <div class="slide-custom-content">
              ${slide.title ? `<h2 class="slide-custom-title">${slide.title}</h2>` : ""}
              ${slide.content ? `<div class="slide-custom-body">${slide.content}</div>` : ""}
            </div>
          </div>
        </div>
      `;
    }
    // Template 3: Gambar Banner
    else {
      const image = document.createElement("img");
      const srcUrl = slide.src && slide.src.startsWith("uploads/") ? `../${slide.src}` : slide.src;
      const v = window.masjidConfig?.sync_hash ? `?v=${window.masjidConfig.sync_hash}` : "";
      image.src = srcUrl + v;
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
