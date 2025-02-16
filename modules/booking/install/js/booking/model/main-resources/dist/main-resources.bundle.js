/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	class MainResources extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.MainResources;
	  }
	  getState() {
	    return {
	      resources: []
	    };
	  }
	  getGetters() {
	    return {
	      resources: state => state.resources
	    };
	  }
	  getActions() {
	    return {
	      setMainResources({
	        commit
	      }, resourcesIds) {
	        commit('setMainResources', resourcesIds);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      setMainResources(state, resourcesIds) {
	        state.resources = resourcesIds;
	      }
	    };
	  }
	}

	exports.MainResources = MainResources;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=main-resources.bundle.js.map
