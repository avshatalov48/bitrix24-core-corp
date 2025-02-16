/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_lib_apiClient) {
	'use strict';

	class OptionService {
	  async setBool(optionName, value) {
	    await new booking_lib_apiClient.ApiClient().post('Option.setBool', {
	      optionName,
	      value
	    });
	  }
	}
	const optionService = new OptionService();

	exports.optionService = optionService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking.Lib));
//# sourceMappingURL=option-service.bundle.js.map
