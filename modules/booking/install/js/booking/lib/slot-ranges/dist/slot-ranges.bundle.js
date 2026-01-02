/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,booking_const,booking_lib_duration,booking_lib_timezone) {
	'use strict';

	function applyTimezone(slotRanges, dateTs, timezone) {
	  const minutesInDay = booking_lib_duration.Duration.getUnitDurations().d / booking_lib_duration.Duration.getUnitDurations().i;
	  const timezoneOffset = booking_lib_timezone.Timezone.getOffset(dateTs, timezone);
	  return slotRanges.map(slotRange => {
	    const slotTimezoneOffset = booking_lib_timezone.Timezone.getOffset(dateTs, slotRange.timezone);
	    const minutesOffset = (timezoneOffset - slotTimezoneOffset) / 60;
	    return {
	      ...slotRange,
	      from: slotRange.from + minutesOffset,
	      to: slotRange.to + minutesOffset
	    };
	  }).map(slotRange => {
	    if (slotRange.from > minutesInDay) {
	      return {
	        ...slotRange,
	        from: slotRange.from - minutesInDay,
	        to: slotRange.to - minutesInDay,
	        weekDays: slotRange.weekDays.map(weekDay => getNextDay(weekDay))
	      };
	    }
	    if (slotRange.to < 0) {
	      return {
	        ...slotRange,
	        from: slotRange.from + minutesInDay,
	        to: slotRange.to + minutesInDay,
	        weekDays: slotRange.weekDays.map(weekDay => getPreviousDay(weekDay))
	      };
	    }
	    return slotRange;
	  }).flatMap(slotRange => {
	    if (slotRange.from < 0) {
	      return [{
	        ...slotRange,
	        from: 0
	      }, ...slotRange.weekDays.map(weekDay => ({
	        ...slotRange,
	        from: minutesInDay + slotRange.from,
	        to: minutesInDay,
	        weekDays: [getPreviousDay(weekDay)]
	      }))];
	    }
	    if (slotRange.to > minutesInDay) {
	      return [{
	        ...slotRange,
	        to: minutesInDay
	      }, ...slotRange.weekDays.map(weekDay => ({
	        ...slotRange,
	        from: 0,
	        to: slotRange.to - minutesInDay,
	        weekDays: [getNextDay(weekDay)]
	      }))];
	    }
	    return slotRange;
	  });
	}
	function getNextDay(weekDay) {
	  return booking_const.DateFormat.WeekDays[(booking_const.DateFormat.WeekDays.indexOf(weekDay) + 1) % 7];
	}
	function getPreviousDay(weekDay) {
	  return booking_const.DateFormat.WeekDays[(booking_const.DateFormat.WeekDays.indexOf(weekDay) + 7 - 1) % 7];
	}

	const SlotRanges = {
	  applyTimezone
	};

	exports.SlotRanges = SlotRanges;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX.Booking.Const,BX.Booking.Lib,BX.Booking.Lib));
//# sourceMappingURL=slot-ranges.bundle.js.map
