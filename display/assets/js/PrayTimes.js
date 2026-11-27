//--------------------- PrayTimes.js ---------------------
// Islamic Prayer Times Calculator
// By: Hamid Zarrabi-Zadeh
// License: GNU LGPL v3.0 / MIT
// Official Website: http://praytimes.org
//-------------------------------------------------------

(function () {
  //---------------------- Math Class ----------------------
  var DMath = {
    dtr: function (d) {
      return (d * Math.PI) / 180.0;
    },
    rtd: function (r) {
      return (r * 180.0) / Math.PI;
    },

    sin: function (d) {
      return Math.sin(this.dtr(d));
    },
    cos: function (d) {
      return Math.cos(this.dtr(d));
    },
    tan: function (d) {
      return Math.tan(this.dtr(d));
    },

    arcsin: function (d) {
      return this.rtd(Math.asin(Math.max(-1.0, Math.min(1.0, d))));
    },
    arccos: function (d) {
      return this.rtd(Math.acos(Math.max(-1.0, Math.min(1.0, d))));
    },
    arctan: function (d) {
      return this.rtd(Math.atan(d));
    },

    arccot: function (x) {
      return this.rtd(Math.atan(1.0 / x));
    },
    arctan2: function (y, x) {
      return this.rtd(Math.atan2(y, x));
    },

    fixAngle: function (a) {
      return this.fix(a, 360);
    },
    fixHour: function (a) {
      return this.fix(a, 24);
    },

    fix: function (a, b) {
      a = a - b * Math.floor(a / b);
      return a < 0 ? a + b : a;
    }
  };

  //---------------------- PrayTimes Class ----------------------
  function PrayTimes(method) {
    // Standard Calculation Methods
    var methods = {
      MWL: {
        name: "Muslim World League",
        params: { fajr: 18, isha: 17 }
      },
      ISNA: {
        name: "Islamic Society of North America (ISNA)",
        params: { fajr: 15, isha: 15 }
      },
      Egypt: {
        name: "Egyptian General Authority of Survey",
        params: { fajr: 19.5, isha: 17.5 }
      },
      Makkah: {
        name: "Umm Al-Qura University, Makkah",
        params: { fajr: 18.5, isha: "90 min" }
      },
      Karachi: {
        name: "University of Islamic Sciences, Karachi",
        params: { fajr: 18, isha: 18 }
      },
      Tehran: {
        name: "Institute of Geophysics, University of Tehran",
        params: { fajr: 17.7, isha: 14, maghrib: 4.5, midnight: "Jafari" }
      },
      Jafari: {
        name: "Shia Ithna-Ashari, Leva Institute, Qum",
        params: { fajr: 16, isha: 14, maghrib: 4, midnight: "Jafari" }
      },
      Kemenag: {
        name: "Kementerian Agama Republik Indonesia",
        params: { fajr: 20, isha: 18, asr: "Standard" }
      }
    };

    // Default settings
    var defaultParams = {
      maghrib: "0 min",
      midnight: "Standard"
    };

    var calcMethod = "Kemenag";

    // Settings
    var setting = {
      imsak: "10 min",
      dhuhr: "0 min",
      asr: "Standard",
      highLats: "NightMiddle"
    };

    var timeFormat = "24h";
    var timeSuffixes = ["am", "pm"];
    var invalidTime = "-----";

    var numIterations = 1;
    var offset = {};

    // Coordinates & Timezone
    var lat, lng, elv, timeZone, jDate;

    // Initialize offsets
    var timeNames = {
      imsak: "Imsak",
      fajr: "Fajr",
      sunrise: "Sunrise",
      dhuhr: "Dhuhr",
      asr: "Asr",
      sunset: "Sunset",
      maghrib: "Maghrib",
      isha: "Isha",
      midnight: "Midnight"
    };

    for (var i in timeNames) {
      offset[i] = 0;
    }

    // Set calculation method
    this.setMethod = function (method) {
      if (methods[method]) {
        this.adjust(methods[method].params);
        calcMethod = method;
      }
    };

    // Adjust settings
    this.adjust = function (params) {
      for (var id in params) {
        setting[id] = params[id];
      }
    };

    // Tune times (offsets in minutes)
    this.tune = function (timeOffsets) {
      for (var i in timeOffsets) {
        offset[i] = Number(timeOffsets[i]) || 0;
      }
      if (typeof timeOffsets.imsak === "undefined" && typeof timeOffsets.fajr !== "undefined") {
        offset.imsak = Number(timeOffsets.fajr) || 0;
      }
    };

    // Return prayer times for a given date
    this.getTimes = function (date, coords, timezone, dst, format) {
      lat = 1 * coords[0];
      lng = 1 * coords[1];
      elv = coords[2] ? 1 * coords[2] : 0;
      timeFormat = format || timeFormat;

      if (date.constructor === Date) {
        date = [date.getFullYear(), date.getMonth() + 1, date.getDate()];
      }

      if (typeof timezone === "undefined" || timezone === "auto") {
        timezone = this.getTimeZone(date);
      }
      if (typeof dst === "undefined" || dst === "auto") {
        dst = 0; // Default 0 untuk Indonesia & wilayah non-DST
      }
      timeZone = 1 * timezone + (1 * dst ? 1 : 0);
      jDate = this.julian(date[0], date[1], date[2]) - lng / (15 * 24);

      return this.computeTimes();
    };

    // Convert float hours to the given format
    this.getFormattedTime = function (time, format, suffixes) {
      if (isNaN(time)) return invalidTime;
      if (format === "Float") return time;

      suffixes = suffixes || timeSuffixes;
      time = DMath.fixHour(time + 0.5 / 60); // round to nearest minute

      var hours = Math.floor(time);
      var minutes = Math.floor((time - hours) * 60);
      var suffix = format === "12h" ? suffixes[hours < 12 ? 0 : 1] : "";
      var hourStr =
        format === "24h"
          ? this.twoDigitsFormat(hours)
          : ((hours + 12 - 1) % 12) + 1;

      return (
        hourStr +
        ":" +
        this.twoDigitsFormat(minutes) +
        (suffix ? " " + suffix : "")
      );
    };

    // Calculation internal methods
    this.midDay = function (time) {
      var eqt = this.sunPosition(jDate + time).equation;
      var noon = DMath.fixHour(12 - eqt);
      return noon;
    };

    this.sunAngleTime = function (angle, time, direction) {
      var decl = this.sunPosition(jDate + time).declination;
      var noon = this.midDay(time);
      var t =
        (1 / 15) *
        DMath.arccos(
          (-DMath.sin(angle) - DMath.sin(decl) * DMath.sin(lat)) /
            (DMath.cos(decl) * DMath.cos(lat))
        );
      return noon + (direction === "ccw" ? -t : t);
    };

    this.asrTime = function (factor, time) {
      var decl = this.sunPosition(jDate + time).declination;
      var angle = -DMath.arccot(factor + DMath.tan(Math.abs(lat - decl)));
      return this.sunAngleTime(angle, time);
    };

    this.sunPosition = function (jd) {
      var D = jd - 2451545.0;
      var g = DMath.fixAngle(357.529 + 0.98560028 * D);
      var q = DMath.fixAngle(280.459 + 0.98564736 * D);
      var L = DMath.fixAngle(
        q + 1.915 * DMath.sin(g) + 0.02 * DMath.sin(2 * g)
      );

      var e = 23.439 - 0.00000036 * D;
      var RA =
        DMath.arctan2(DMath.cos(e) * DMath.sin(L), DMath.cos(L)) / 15.0;
      var eqt = q / 15.0 - DMath.fixHour(RA);
      var decl = DMath.arcsin(DMath.sin(e) * DMath.sin(L));

      return { declination: decl, equation: eqt };
    };

    this.julian = function (year, month, day) {
      if (month <= 2) {
        year -= 1;
        month += 12;
      }
      var A = Math.floor(year / 100);
      var B = 2 - A + Math.floor(A / 4);
      var JD =
        Math.floor(365.25 * (year + 4716)) +
        Math.floor(30.6001 * (month + 1)) +
        day +
        B -
        1524.5;
      return JD;
    };

    // Compute prayer times
    this.computeTimes = function () {
      var times = {
        imsak: 5,
        fajr: 5,
        sunrise: 6,
        dhuhr: 12,
        asr: 13,
        sunset: 18,
        maghrib: 18,
        isha: 18
      };

      for (var i = 1; i <= numIterations; i++) {
        times = this.computeDayTimes(times);
      }

      times = this.adjustTimes(times);

      // Add midnight time
      times.midnight =
        setting.midnight === "Jafari"
          ? times.sunset + this.timeDiff(times.sunset, times.fajr) / 2
          : times.sunset + this.timeDiff(times.sunset, times.sunrise) / 2;

      times = this.tuneTimes(times);
      return this.modifyFormats(times);
    };

    this.computeDayTimes = function (times) {
      var t = this.dayPortion(times);

      var Imsak = this.sunAngleTime(
        this.eval(setting.imsak),
        t.imsak,
        "ccw"
      );
      var Fajr = this.sunAngleTime(
        this.eval(setting.fajr),
        t.fajr,
        "ccw"
      );
      var Sunrise = this.sunAngleTime(
        this.riseSetAngle(),
        t.sunrise,
        "ccw"
      );
      var Dhuhr = this.midDay(t.dhuhr);
      var Asr = this.asrTime(this.asrFactor(setting.asr), t.asr);
      var Sunset = this.sunAngleTime(this.riseSetAngle(), t.sunset);
      var Maghrib = this.sunAngleTime(
        this.eval(setting.maghrib),
        t.maghrib
      );
      var Isha = this.sunAngleTime(this.eval(setting.isha), t.isha);

      return {
        imsak: Imsak,
        fajr: Fajr,
        sunrise: Sunrise,
        dhuhr: Dhuhr,
        asr: Asr,
        sunset: Sunset,
        maghrib: Maghrib,
        isha: Isha
      };
    };

    this.adjustTimes = function (times) {
      var params = setting;
      for (var i in times) {
        times[i] += timeZone - lng / 15;
      }

      if (this.isMin(params.imsak)) {
        times.imsak = times.fajr - this.eval(params.imsak) / 60;
      }
      if (this.isMin(params.maghrib)) {
        times.maghrib = times.sunset + this.eval(params.maghrib) / 60;
      }
      if (this.isMin(params.isha)) {
        times.isha = times.maghrib + this.eval(params.isha) / 60;
      }
      if (params.dhuhr) {
        times.dhuhr += this.eval(params.dhuhr) / 60;
      }

      return times;
    };

    this.asrFactor = function (asrParam) {
      var factors = { Standard: 1, Hanafi: 2 };
      return factors[asrParam] || this.eval(asrParam);
    };

    this.riseSetAngle = function () {
      var angle = 0.833;
      if (elv > 0) {
        angle += 0.0347 * Math.sqrt(elv);
      }
      return angle;
    };

    this.tuneTimes = function (times) {
      for (var i in times) {
        var off = typeof offset[i] !== "undefined" ? offset[i] : (i === "imsak" ? (offset.fajr || 0) : 0);
        times[i] += off / 60.0;
      }
      return times;
    };

    this.modifyFormats = function (times) {
      for (var i in times) {
        times[i] = this.getFormattedTime(times[i], timeFormat);
      }
      return times;
    };

    this.timeDiff = function (time1, time2) {
      return DMath.fixHour(time2 - time1);
    };

    this.dayPortion = function (times) {
      var res = {};
      for (var i in times) {
        res[i] = times[i] / 24;
      }
      return res;
    };

    this.eval = function (str) {
      if (typeof str === "number") return str;
      return 1 * (str + "").split(/[^0-9.+-]/)[0];
    };

    this.isMin = function (arg) {
      return (arg + "").indexOf("min") !== -1;
    };

    this.twoDigitsFormat = function (num) {
      return num < 10 ? "0" + num : num;
    };

    this.getTimeZone = function (date) {
      var year = date[0];
      var t1 = new Date(year, 0, 1);
      var t2 = new Date(year, 6, 1);
      return -Math.max(t1.getTimezoneOffset(), t2.getTimezoneOffset()) / 60;
    };

    this.getDst = function (date) {
      return 1 * (new Date(date[0], date[1] - 1, date[2]).getTimezoneOffset() < this.getTimeZone(date) * 60);
    };

    // Apply initial method
    this.setMethod(method || calcMethod);
  }

  // Global instance
  var prayTimes = new PrayTimes("Kemenag");

  // Export to window
  if (typeof window !== "undefined") {
    window.PrayTimes = PrayTimes;
    window.prayTimes = prayTimes;
  }
  if (typeof module !== "undefined" && module.exports) {
    module.exports = PrayTimes;
  }
})();
