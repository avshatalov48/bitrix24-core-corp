/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core) {
	'use strict';

	class Limit {
	  show(featureId = 'booking') {
	    return new Promise((resolve, reject) => {
	      main_core.Runtime.loadExtension('ui.info-helper').then(({
	        FeaturePromotersRegistry
	      }) => {
	        FeaturePromotersRegistry.getPromoter({
	          featureId
	        }).show();
	        resolve();
	      }).catch(error => {
	        reject(error);
	      });
	    });
	  }
	}
	const limit = new Limit();

	exports.limit = limit;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX));
//# sourceMappingURL=limit.bundle.js.map
