/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports) {
	'use strict';

	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	class Timezone {
	  static getOffset(dateTs, timeZone) {
	    const key = `${dateTs}-${timeZone}`;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache][key]) {
	      const date = new Date(dateTs);
	      const dateInTimezone = new Date(date.toLocaleString('en-US', {
	        timeZone
	      }));
	      const dateInUTC = new Date(date.toLocaleString('en-US', {
	        timeZone: 'UTC'
	      }));
	      babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache][key] = (dateInTimezone.getTime() - dateInUTC.getTime()) / 1000;
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache][key];
	  }
	}
	Object.defineProperty(Timezone, _cache, {
	  writable: true,
	  value: {}
	});

	exports.Timezone = Timezone;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {})));
//# sourceMappingURL=timezone.bundle.js.map
