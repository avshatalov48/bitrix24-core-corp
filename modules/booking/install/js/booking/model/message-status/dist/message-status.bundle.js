/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	class MessageStatus extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.MessageStatus;
	  }
	  getState() {
	    return {
	      collection: {}
	    };
	  }
	  getElementState() {
	    return {
	      title: '',
	      description: '',
	      semantic: '',
	      isDisabled: true
	    };
	  }
	  getGetters() {
	    return {
	      getById: state => bookingId => {
	        return state.collection[bookingId];
	      }
	    };
	  }
	  getActions() {
	    return {
	      /** @function message-status/upsert */
	      upsert: (store, payload) => {
	        store.commit('upsert', payload);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      upsert: (state, payload) => {
	        var _state$collection, _state$collection$boo;
	        const {
	          bookingId,
	          status
	        } = payload;
	        // eslint-disable-next-line no-param-reassign
	        (_state$collection$boo = (_state$collection = state.collection)[bookingId]) != null ? _state$collection$boo : _state$collection[bookingId] = status;
	        Object.assign(state.collection[bookingId], status);
	      }
	    };
	  }
	}

	exports.MessageStatus = MessageStatus;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=message-status.bundle.js.map
