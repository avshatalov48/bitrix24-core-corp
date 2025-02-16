/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex) {
	'use strict';

	class Counters extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return 'counters';
	  }
	  getState() {
	    return {
	      counters: {
	        unConfirmed: 0
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

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex));
//# sourceMappingURL=counters.bundle.js.map
