/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core,main_date,tasks_v2_lib_timezone) {
	'use strict';

	var _calculateRangeTs;
	const settings = main_core.Extension.getSettings('tasks.v2.lib.calendar').calendarSettings;
	const holidays = new Set(settings.HOLIDAYS.map(({
	  M,
	  D
	}) => `${M}.${D}`));
	const weekends = new Set(settings.WEEKEND.map(it => ({
	  SU: 0,
	  MO: 1,
	  TU: 2,
	  WE: 3,
	  TH: 4,
	  FR: 5,
	  SA: 6
	})[it]));
	const {
	  H: startH,
	  M: startM
	} = settings.HOURS.START;
	const {
	  H: endH,
	  M: endM
	} = settings.HOURS.END;
	const dayDuration = main_date.DurationFormat.getUnitDurations().d;
	const workdayDuration = (endH * 60 + endM - (startH * 60 + startM)) * 60000;
	const workWeekDuration = workdayDuration * (7 - weekends.size);
	const calendar = new (_calculateRangeTs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calculateRangeTs"), class {
	  constructor() {
	    Object.defineProperty(this, _calculateRangeTs, {
	      value: _calculateRangeTs2
	    });
	  }
	  get workdayDuration() {
	    return workdayDuration;
	  }
	  get workdayStart() {
	    return settings.HOURS.START;
	  }
	  get defaultTime() {
	    return `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;
	  }
	  formatDateTime(timestamp, {
	    forceYear
	  } = {}) {
	    if (!timestamp) {
	      return '';
	    }
	    const showYear = forceYear || new Date(timestamp).getFullYear() !== new Date().getFullYear();
	    const format = main_core.Loc.getMessage('TASKS_V2_DATE_TIME_FORMAT', {
	      '#DATE#': main_date.DateTimeFormat.getFormat(showYear ? 'LONG_DATE_FORMAT' : 'DAY_MONTH_FORMAT'),
	      '#TIME#': main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT')
	    });
	    const offset = tasks_v2_lib_timezone.timezone.getOffset(timestamp);
	    return main_date.DateTimeFormat.format(format, (timestamp + offset) / 1000);
	  }
	  calculateDuration(startTs, end) {
	    const dayEnd = this.setHours(startTs, endH, endM);
	    if (end < dayEnd) {
	      return end - startTs;
	    }
	    let start = this.setHours(startTs + dayDuration, startH, startM);
	    let duration = dayEnd - startTs;
	    while (start < end) {
	      if (this.isWorkDay(start)) {
	        duration += Math.min(start + workdayDuration, end) - start;
	      }
	      start += dayDuration;
	    }
	    return duration;
	  }
	  calculateStartTs(startTs, endTs, duration) {
	    return startTs != null ? startTs : babelHelpers.classPrivateFieldLooseBase(this, _calculateRangeTs)[_calculateRangeTs](endTs, duration, true);
	  }
	  calculateEndTs(startTs, endTs, durationTs) {
	    if (!startTs) {
	      return endTs;
	    }
	    let duration = durationTs;
	    let start = startTs;
	    const daysUntilNextMonday = (1 + 7 - new Date(start - tasks_v2_lib_timezone.timezone.getOffset(start)).getDay()) % 7;
	    const nextMondayTs = this.setHours(start + dayDuration * daysUntilNextMonday, startH, startM);
	    const mondayDuration = this.calculateDuration(start, nextMondayTs);
	    if (duration <= mondayDuration) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _calculateRangeTs)[_calculateRangeTs](start, duration);
	    }
	    duration -= mondayDuration;
	    start = nextMondayTs;
	    const beforeSkip = new Date(start).setHours(0, 0, 0);
	    const weeks = Math.max(Math.floor(duration / workWeekDuration) - 1, 0);
	    duration -= workWeekDuration * weeks;
	    start = this.setHours(start + dayDuration * weeks * 7, startH, startM);
	    const afterSkip = new Date(start).setHours(0, 0, 0) - dayDuration;
	    const fromYear = new Date(beforeSkip).getFullYear();
	    const toYear = new Date(afterSkip).getFullYear();
	    const missedHolidays = Array.from({
	      length: toYear - fromYear + 1
	    }, (_, i) => i + fromYear).flatMap(year => settings.HOLIDAYS.map(({
	      M,
	      D
	    }) => new Date(year, M - 1, D).getTime())).filter(it => beforeSkip <= it && it <= afterSkip && !weekends.has(new Date(it).getDay()));
	    if (missedHolidays.length > 0) {
	      return this.calculateEndTs(start, endTs, duration + workdayDuration * missedHolidays.length);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _calculateRangeTs)[_calculateRangeTs](start, duration);
	  }
	  clampWorkDateTime(timestamp) {
	    if (!timestamp) {
	      return null;
	    }
	    let workday = timestamp;
	    while (!this.isWorkDay(workday)) {
	      workday = this.setHours(workday + dayDuration, startH, startM);
	    }
	    const dayStart = this.setHours(workday, startH, startM);
	    const dayEnd = this.setHours(workday, endH, endM);
	    return Math.min(Math.max(workday, dayStart), dayEnd);
	  }
	  isWorkDay(timestamp) {
	    const date = new Date(timestamp);
	    return !weekends.has(date.getUTCDay()) && !holidays.has(`${date.getUTCMonth() + 1}.${date.getUTCDate()}`);
	  }
	  setHours(timestamp, hours, minutes) {
	    return new Date(timestamp).setHours(hours, minutes, 0, 0) - tasks_v2_lib_timezone.timezone.getOffset(timestamp);
	  }
	  createDateFromUtc(date) {
	    return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), date.getUTCHours(), date.getUTCMinutes());
	  }
	})();
	function _calculateRangeTs2(anchorTs, durationTs, backwards) {
	  const direction = backwards ? -1 : 1;
	  const anchorHours = backwards ? settings.HOURS.END : settings.HOURS.START;
	  const oppositeHours = backwards ? settings.HOURS.START : settings.HOURS.END;
	  let anchor = anchorTs;
	  let duration = durationTs;
	  while (duration > 0) {
	    if (this.isWorkDay(anchor)) {
	      const dayLength = Math.abs(anchor - this.setHours(anchor, oppositeHours.H, oppositeHours.M));
	      const opposite = anchor + Math.min(dayLength, duration, workdayDuration) * direction;
	      duration -= Math.abs(anchor - opposite);
	      if (duration === 0) {
	        return opposite;
	      }
	    }
	    anchor = this.setHours(anchor + dayDuration * direction, anchorHours.H, anchorHours.M);
	  }
	  return anchorTs;
	}

	exports.calendar = calendar;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX,BX.Main,BX.Tasks.V2.Lib));
//# sourceMappingURL=calendar.bundle.js.map
