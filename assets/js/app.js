const prayers = [
  { name: "Subuh", time: "04:36" },
  { name: "Syuruq", time: "05:52" },
  { name: "Dzuhur", time: "11:54" },
  { name: "Ashar", time: "15:14" },
  { name: "Maghrib", time: "17:48" },
  { name: "Isya", time: "18:59" }
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

function tick() {
  const now = new Date();
  updateClock(now);
  updateNextPrayer(now);
}

function startClock() {
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
