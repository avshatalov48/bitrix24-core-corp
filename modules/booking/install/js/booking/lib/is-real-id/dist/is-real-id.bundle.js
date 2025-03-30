/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports) {
	'use strict';

	function isRealId(id) {
	  return Number.isInteger(id) || /^[1-9]\d*$/.test(id);
	}

	exports.isRealId = isRealId;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {})));
//# sourceMappingURL=is-real-id.bundle.js.map
