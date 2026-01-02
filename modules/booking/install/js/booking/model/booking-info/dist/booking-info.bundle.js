/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	class BookingInfo extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.BookingInfo;
	  }
	  getState() {
	    return {
	      bookingId: this.getVariable('bookingId', null),
	      resources: [],
	      services: [],
	      client: {},
	      note: ''
	    };
	  }
	  getGetters() {
	    return {
	      /** @function booking-info/resources */
	      resources: state => state.resources,
	      /** @function booking-info/services */
	      services: state => state.services,
	      /** @function booking-info/client */
	      client: state => state.client,
	      /** @function booking-info/note */
	      note: state => state.note
	    };
	  }
	  getActions() {
	    return {
	      setBookingInfo({
	        commit
	      }, bookingInfo) {
	        commit('setBookingInfo', bookingInfo);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      setBookingInfo(state, bookingInfo) {
	        state.id = bookingInfo.id;
	        state.note = bookingInfo.note;
	        state.resources = bookingInfo.resources;
	        state.services = bookingInfo.services;
	        state.client = bookingInfo.client;
	      }
	    };
	  }
	}

	exports.BookingInfo = BookingInfo;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=booking-info.bundle.js.map
