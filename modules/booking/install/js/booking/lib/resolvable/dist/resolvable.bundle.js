/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports) {
	'use strict';

	function Resolvable() {
	  const promise = new Promise(resolve => {
	    this.resolve = resolve;
	  });
	  promise.resolve = this.resolve;
	  return promise;
	}

	exports.Resolvable = Resolvable;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {})));
//# sourceMappingURL=resolvable.bundle.js.map
