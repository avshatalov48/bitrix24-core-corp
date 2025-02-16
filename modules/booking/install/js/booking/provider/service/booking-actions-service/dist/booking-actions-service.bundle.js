/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_const,booking_core,booking_lib_apiClient) {
	'use strict';

	class BookingActionsService {
	  async getDealData(bookingId) {
	    return Promise.resolve();
	  }
	  async getDocData(bookingId) {
	    return Promise.resolve();
	  }
	  async getMessageData(bookingId) {
	    const status = await new booking_lib_apiClient.ApiClient().post('MessageStatus.get', {
	      bookingId
	    });
	    await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.MessageStatus}/upsert`, {
	      bookingId,
	      status
	    })]);
	  }
	  async sendMessage(bookingId, notificationType) {
	    return new booking_lib_apiClient.ApiClient().post('Message.send', {
	      bookingId,
	      notificationType
	    });
	  }
	  async getVisitData(bookingId) {
	    return Promise.resolve();
	  }
	}
	const bookingActionsService = new BookingActionsService();

	exports.bookingActionsService = bookingActionsService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking.Const,BX.Booking,BX.Booking.Lib));
//# sourceMappingURL=booking-actions-service.bundle.js.map
