/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	class Dictionary extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.Dictionary;
	  }
	  getState() {
	    return {
	      counters: [],
	      notifications: [],
	      pushCommands: [],
	      notificationTemplates: []
	    };
	  }
	  getGetters() {
	    return {
	      /** @function dictionary/getNotifications */
	      getNotifications: state => state.notifications,
	      /** @function dictionary/getCounters */
	      getCounters: state => state.counters,
	      /** @function dictionary/getPushCommands */
	      getPushCommands: state => state.pushCommands,
	      /** @function dictionary/getNotificationTemplates */
	      getNotificationTemplates: state => state.notificationTemplates,
	      /** @function dictionary/getBookingVisitStatuses */
	      getBookingVisitStatuses: state => state.bookings.visitStatuses
	    };
	  }
	  getActions() {
	    return {
	      /** @function dictionary/setNotifications */
	      setNotifications: (store, items) => {
	        store.commit('set', {
	          key: 'notifications',
	          items
	        });
	      },
	      /** @function dictionary/setCounters */
	      setCounters: (store, items) => {
	        store.commit('set', {
	          key: 'counters',
	          items
	        });
	      },
	      /** @function dictionary/setPushCommands */
	      setPushCommands: (store, items) => {
	        store.commit('set', {
	          key: 'pushCommands',
	          items
	        });
	      },
	      /** @function dictionary/setNotificationTemplates */
	      setNotificationTemplates: (store, items) => {
	        store.commit('set', {
	          key: 'notificationTemplates',
	          items
	        });
	      },
	      /** @function dictionary/setBookings */
	      setBookings: (store, items) => {
	        store.commit('set', {
	          key: 'bookings',
	          items
	        });
	      }
	    };
	  }
	  getMutations() {
	    return {
	      set: (state, payload) => {
	        state[payload.key] = payload.items;
	      }
	    };
	  }
	}

	exports.Dictionary = Dictionary;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=dictionary.bundle.js.map
