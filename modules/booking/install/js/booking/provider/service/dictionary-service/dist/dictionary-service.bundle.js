/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_core,booking_lib_apiClient) {
	'use strict';

	var _response = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("response");
	class DictionaryDataExtractor {
	  constructor(response) {
	    Object.defineProperty(this, _response, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _response)[_response] = response;
	  }
	  getCounters() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].counters;
	  }
	  getNotifications() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].notifications;
	  }
	  getNotificationTemplates() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].notificationTemplateTypes;
	  }
	  getPushCommands() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].pushCommands;
	  }
	  getBookings() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].bookings;
	  }
	}

	class DictionaryService {
	  async fetchData() {
	    try {
	      const data = await new booking_lib_apiClient.ApiClient().get('Dictionary.get', {});
	      const extractor = new DictionaryDataExtractor(data);
	      return Promise.all([booking_core.Core.getStore().dispatch('dictionary/setCounters', extractor.getCounters()), booking_core.Core.getStore().dispatch('dictionary/setNotifications', extractor.getNotifications()), booking_core.Core.getStore().dispatch('dictionary/setNotificationTemplates', extractor.getNotificationTemplates()), booking_core.Core.getStore().dispatch('dictionary/setPushCommands', extractor.getPushCommands()), booking_core.Core.getStore().dispatch('dictionary/setBookings', extractor.getBookings())]);
	    } catch (error) {
	      console.error('BookingDictionaryGetRequest: error', error);
	    }
	  }
	}
	const dictionaryService = new DictionaryService();

	exports.dictionaryService = dictionaryService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking,BX.Booking.Lib));
//# sourceMappingURL=dictionary-service.bundle.js.map
