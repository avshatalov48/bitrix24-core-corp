/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_core,booking_lib_apiClient,booking_const) {
	'use strict';

	class CountersService {
	  async fetchData() {
	    try {
	      const counters = await new booking_lib_apiClient.ApiClient().get('Counters.get', {});
	      await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Counters}/set`, counters)]);
	    } catch (error) {
	      console.error('BookingCountersGetRequest: error', error);
	    }
	  }
	}
	const countersService = new CountersService();

	exports.countersService = countersService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking,BX.Booking.Lib,BX.Booking.Const));
//# sourceMappingURL=counters-service.bundle.js.map
