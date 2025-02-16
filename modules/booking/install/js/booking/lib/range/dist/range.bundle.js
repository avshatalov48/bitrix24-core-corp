/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports) {
	'use strict';

	function range(start, stop, step = 1) {
	  const result = [];
	  for (let i = start; i <= stop; i += step) {
	    result.push(i);
	  }
	  return result;
	}

	exports.range = range;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {})));
//# sourceMappingURL=range.bundle.js.map
