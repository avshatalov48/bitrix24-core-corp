/**
 * @module calendar/ajax/booking
 */
jn.define('calendar/ajax/booking', (require, exports, module) => {
	const { BaseAjax } = require('calendar/ajax/base');

	const BookingActions = {
		BOOKING_INFO: 'bookingInfo',
	};

	/**
	 * @class BookingAjax
	 */
	class BookingAjax extends BaseAjax
	{
		getEndpoint()
		{
			return 'booking.api_v1.CalendarData';
		}

		/**
		 * @param bookingId {number}
		 * @returns {Promise<Object, void>}
		 */
		bookingInfo(bookingId)
		{
			return this.fetch(BookingActions.BOOKING_INFO, { bookingId }, true);
		}
	}

	module.exports = { BookingAjax: new BookingAjax() };
});
