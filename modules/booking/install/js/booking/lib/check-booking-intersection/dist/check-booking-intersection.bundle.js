/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,booking_lib_inInterval) {
	'use strict';

	function inBookingInterval(interval) {
	  return (value, include) => {
	    return booking_lib_inInterval.inInterval(value, interval, {
	      include
	    });
	  };
	}
	function checkBookingIntersection(booking, other) {
	  const checkInBookingInterval = inBookingInterval([booking.dateFromTs, booking.dateToTs]);
	  return checkInBookingInterval(other.dateFromTs, booking_lib_inInterval.IncludeBoundaries.left) || checkInBookingInterval(other.dateToTs, booking_lib_inInterval.IncludeBoundaries.right);
	}

	exports.checkBookingIntersection = checkBookingIntersection;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX.Booking.Lib));
//# sourceMappingURL=check-booking-intersection.bundle.js.map
