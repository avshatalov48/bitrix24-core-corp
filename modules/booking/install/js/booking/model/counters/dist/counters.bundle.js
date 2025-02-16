/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	class Counters extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.Counters;
	  }
	  getState() {
	    return {
	      counters: {
	        total: 0,
	        unConfirmed: 0,
	        delayed: 0
	      }
	    };
	  }
	  getGetters() {
	    return {
	      /** @function counters/get */
	      get: state => state.counters
	    };
	  }
	  getActions() {
	    return {
	      /** @function counters/set */
	      set: (store, counters) => {
	        store.commit('set', counters);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      set: (state, counters) => {
	        state.counters = counters;
	      }
	    };
	  }
	}

	exports.Counters = Counters;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=counters.bundle.js.map
