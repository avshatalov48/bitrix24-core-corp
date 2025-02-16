/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,booking_core,booking_const,booking_provider_service_mainPageService,booking_provider_service_favoritesService) {
	'use strict';

	async function hideResources(ids) {
	  const store = booking_core.Core.getStore();
	  await store.dispatch(`${booking_const.Model.Interface}/setResourcesIds`, ids);
	  booking_provider_service_mainPageService.mainPageService.clearCache(ids);
	  if (store.getters[`${booking_const.Model.Interface}/isEditingBookingMode`]) {
	    await store.dispatch(`${booking_const.Model.Favorites}/set`, ids);
	    return;
	  }
	  await booking_provider_service_favoritesService.favoritesService.set(ids);
	}

	exports.hideResources = hideResources;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX.Booking,BX.Booking.Const,BX.Booking.Provider.Service,BX.Booking.Provider.Service));
//# sourceMappingURL=resources.bundle.js.map
