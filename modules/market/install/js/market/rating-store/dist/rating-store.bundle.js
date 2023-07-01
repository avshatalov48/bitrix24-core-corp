this.BX = this.BX || {};
(function (exports,ui_vue3_pinia) {
	'use strict';

	const ratingStore = ui_vue3_pinia.defineStore('rating-store', {
	  actions: {
	    isActiveStar: function (currentStar, rating) {
	      return currentStar <= parseInt(rating, 10);
	    },
	    getAppRating: function (value) {
	      return value ? value : 0;
	    }
	  }
	});

	exports.ratingStore = ratingStore;

}((this.BX.Market = this.BX.Market || {}),BX.Vue3.Pinia));
