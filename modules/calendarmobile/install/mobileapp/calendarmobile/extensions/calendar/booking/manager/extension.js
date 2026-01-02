/**
 * @module calendar/booking/manager
 */
jn.define('calendar/booking/manager', (require, exports, module) => {
	const { BookingModel } = require('calendar/booking/model');

	/**
	 * @class BookingManager
	 */
	class BookingManager
	{
		constructor()
		{
			this.bookings = [];
		}

		setBooking(bookingInfo)
		{
			const booking = new BookingModel(bookingInfo);
			this.bookings[booking.getId()] = booking;
		}

		getBooking(id)
		{
			return this.bookings[id] || null;
		}
	}

	module.exports = { BookingManager: new BookingManager() };
});
