/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_date) {
	'use strict';

	class Duration {
	  constructor(milliseconds) {
	    this.milliseconds = Math.abs(milliseconds);
	  }
	  static createFromSeconds(seconds) {
	    return new Duration(seconds * Duration.getUnitDurations().s);
	  }
	  static createFromMinutes(minutes) {
	    return new Duration(minutes * Duration.getUnitDurations().i);
	  }
	  get seconds() {
	    return Math.floor(this.milliseconds / Duration.getUnitDurations().s);
	  }
	  get minutes() {
	    return Math.floor(this.milliseconds / Duration.getUnitDurations().i);
	  }
	  get hours() {
	    return Math.floor(this.milliseconds / Duration.getUnitDurations().H);
	  }
	  get days() {
	    return Math.floor(this.milliseconds / Duration.getUnitDurations().d);
	  }

	  /**
	   * Duration in months (considering that a month is 31 days)
	   */
	  get months() {
	    return Math.floor(this.milliseconds / Duration.getUnitDurations().m);
	  }

	  /**
	   * Duration in years (considering that a year is 365 days)
	   */
	  get years() {
	    return Math.floor(this.milliseconds / Duration.getUnitDurations().Y);
	  }

	  /**
	   * Available units: `s` - seconds, `i` - minutes, `H` - hours, `d` - days, `m` - months, `Y` - years.
	   *
	   * If not pass format string then:
	   * - Duration will be formatted automatically with 'Y m d H i s'
	   * @example '1 day 2 hours 20 minutes'
	   * - Units will be taken with mod:
	   * @example result will be '1 hour 30 minutes' instead of '1 hour 90 minutes 3600 seconds'
	   * - Zero units will not be shown
	   * @example result will be '1 hour' instead of '1 hour 0 minutes 0 seconds'
	   */
	  format(formatStr = '') {
	    if (formatStr === '') {
	      return this.formatAllUnits('Y m d H i s', true).replaceAll(/\s+/g, ' ').trim();
	    }
	    return this.formatAllUnits(formatStr, false);
	  }
	  formatAllUnits(formatStr, mod) {
	    // eslint-disable-next-line unicorn/better-regex
	    return formatStr.replaceAll(/([YmdHis])/g, unitStr => this.formatUnit(unitStr, mod));
	  }
	  formatUnit(unitStr, mod) {
	    const value = mod ? this.getUnitPropertyModByFormat(unitStr) : this.getUnitPropertyByFormat(unitStr);
	    if (mod && value === 0) {
	      return '';
	    }
	    const now = Date.now() / 1000;
	    const unitDuration = value * this.getUnitDuration(unitStr) / 1000;
	    return main_date.DateTimeFormat.format(`${unitStr}diff`, now - unitDuration, now);
	  }
	  getUnitPropertyByFormat(unitStr) {
	    const props = {
	      s: this.seconds,
	      i: this.minutes,
	      H: this.hours,
	      d: this.days,
	      m: this.months,
	      Y: this.years
	    };
	    return props[unitStr];
	  }
	  getUnitPropertyModByFormat(unitStr) {
	    const propsMod = {
	      s: this.seconds % 60,
	      i: this.minutes % 60,
	      H: this.hours % 24,
	      d: this.days % 31,
	      m: this.months % 12,
	      Y: this.years
	    };
	    return propsMod[unitStr];
	  }
	  getUnitDuration(unitStr) {
	    return Duration.getUnitDurations()[unitStr];
	  }
	  static getUnitDurations() {
	    return {
	      s: 1000,
	      i: 60000,
	      H: 3600000,
	      d: 86400000,
	      m: 2678400000,
	      Y: 31536000000
	    };
	  }
	}

	exports.Duration = Duration;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX.Main));
//# sourceMappingURL=duration.bundle.js.map
