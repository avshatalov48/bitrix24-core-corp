/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_const,main_core,booking_core,booking_lib_apiClient) {
	'use strict';

	class FavoritesService {
	  async set(favoritesIds) {
	    const currentIds = booking_core.Core.getStore().getters['favorites/get'];
	    const favoritesAdded = favoritesIds.filter(it => !currentIds.includes(it));
	    const favoritesDeleted = currentIds.filter(it => !favoritesIds.includes(it));
	    void booking_core.Core.getStore().dispatch(`${booking_const.Model.Favorites}/set`, favoritesIds);
	    if (main_core.Type.isArrayFilled(favoritesAdded)) {
	      await favoritesService.add(favoritesAdded);
	    }
	    if (main_core.Type.isArrayFilled(favoritesDeleted)) {
	      await favoritesService.delete(favoritesDeleted);
	    }
	  }
	  async add(favoritesIds) {
	    if (!main_core.Type.isArrayFilled(favoritesIds)) {
	      return;
	    }
	    try {
	      await new booking_lib_apiClient.ApiClient().post('Favorites.add', {
	        resourcesIds: favoritesIds
	      });
	    } catch (error) {
	      console.error('FavoritesService: add error', error);
	    }
	  }
	  async delete(favoritesIds) {
	    if (!main_core.Type.isArrayFilled(favoritesIds)) {
	      return;
	    }
	    try {
	      await new booking_lib_apiClient.ApiClient().post('Favorites.delete', {
	        resourcesIds: favoritesIds
	      });
	    } catch (error) {
	      console.error('FavoritesService: delete error', error);
	    }
	  }
	}
	const favoritesService = new FavoritesService();

	exports.favoritesService = favoritesService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking.Const,BX,BX.Booking,BX.Booking.Lib));
//# sourceMappingURL=favorites-service.bundle.js.map
