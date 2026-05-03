const prayers = [
  { name: "Subuh", time: "04:36" },
  { name: "Syuruq", time: "05:52" },
  { name: "Dzuhur", time: "11:54" },
  { name: "Ashar", time: "15:14" },
  { name: "Maghrib", time: "17:48" },
  { name: "Isya", time: "18:59" }
];

const slides = [
  { src: "/layarmasjid/assets/img/contoh_slide/info-a.png", alt: "Slide informasi 1" },
  { src: "/layarmasjid/assets/img/contoh_slide/info-2.png", alt: "Slide informasi 2" },
  { src: "/layarmasjid/assets/img/contoh_slide/info-3.png", alt: "Slide informasi 3" },
  { src: "/layarmasjid/assets/img/contoh_slide/info-4.png", alt: "Slide informasi 4" },
  { src: "/layarmasjid/assets/img/contoh_slide/info-5.png", alt: "Slide informasi 5" },
  { src: "/layarmasjid/assets/img/contoh_slide/info-6.png", alt: "Slide informasi 6" },
  { src: "/layarmasjid/assets/img/contoh_slide/ig_0bdab9806755ebf80169f450625bd081918be5a5e42634f9c9.png", alt: "Slide informasi Instagram" },
  { src: "/layarmasjid/assets/img/contoh_slide/info-1777620184.png", alt: "Slide informasi wakaf AC" }
];

const dayFormatter = new Intl.DateTimeFormat("id-ID", { weekday: "long" });
const monthFormatter = new Intl.DateTimeFormat("id-ID", { month: "long" });

function pad(value) {
  return String(value).padStart(2, "0");
}

function toTitleCase(value) {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

function updateClock(now) {
  const clockMain = document.getElementById("clock-main");
  const clockSeconds = document.getElementById("clock-seconds");
  const dateDisplay = document.getElementById("date-display");

  if (!clockMain || !clockSeconds || !dateDisplay) {
    return;
  }

  const hours = pad(now.getHours());
  const minutes = pad(now.getMinutes());
  const seconds = pad(now.getSeconds());
  const dayName = toTitleCase(dayFormatter.format(now));
  const monthName = toTitleCase(monthFormatter.format(now));

  clockMain.textContent = `${hours}.${minutes}`;
  clockSeconds.textContent = seconds;
  dateDisplay.textContent = `${dayName}, ${pad(now.getDate())} ${monthName} ${now.getFullYear()}`;
}

function getPrayerDate(baseDate, timeText) {
  const [hours, minutes] = timeText.split(":").map(Number);
  const next = new Date(baseDate);
  next.setHours(hours, minutes, 0, 0);
  return next;
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

    const image = document.createElement("img");
    image.src = slide.src;
    image.alt = slide.alt;
    image.loading = index === 0 ? "eager" : "lazy";

    slideItem.appendChild(image);
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

  setInterval(() => {
    slideElements[activeIndex].classList.remove("active");
    dotElements[activeIndex].classList.remove("active");

    activeIndex = (activeIndex + 1) % slideElements.length;

    slideElements[activeIndex].classList.add("active");
    dotElements[activeIndex].classList.add("active");
  }, 3000);
}

function tick() {
  const now = new Date();
  updateClock(now);
  updateNextPrayer(now);
}

function startClock() {
  setupSlides();
  tick();

  const delayToNextSecond = 1000 - new Date().getMilliseconds();
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
