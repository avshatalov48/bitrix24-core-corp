/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_const,booking_core,booking_lib_apiClient) {
	'use strict';

	class BookingActionsService {
	  async getDocData(bookingId) {
	    return Promise.resolve();
	  }
	  async getMessageData(bookingId) {
	    const status = await booking_lib_apiClient.apiClient.post('MessageStatus.get', {
	      bookingId
	    });
	    await Promise.all([this.$store.dispatch(`${booking_const.Model.MessageStatus}/upsert`, {
	      bookingId,
	      status
	    })]);
	  }
	  async sendMessage(bookingId, notificationType) {
	    var _booking$messages;
	    const message = await booking_lib_apiClient.apiClient.post('Message.send', {
	      bookingId,
	      notificationType
	    });
	    const booking = this.$store.getters[`${booking_const.Model.Bookings}/getById`](bookingId);
	    void this.$store.dispatch(`${booking_const.Model.Bookings}/update`, {
	      id: booking.id,
	      booking: {
	        ...booking,
	        messages: [...((_booking$messages = booking.messages) != null ? _booking$messages : []), message]
	      }
	    });
	  }
	  get $store() {
	    return booking_core.Core.getStore();
	  }
	}
	const bookingActionsService = new BookingActionsService();

	exports.bookingActionsService = bookingActionsService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking.Const,BX.Booking,BX.Booking.Lib));
//# sourceMappingURL=booking-actions-service.bundle.js.map
