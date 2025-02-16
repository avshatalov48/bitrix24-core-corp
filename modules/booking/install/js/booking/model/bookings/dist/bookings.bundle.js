/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	function dateToTsRange(dateTs) {
	  const dateFrom = dateTs;
	  const dateTo = new Date(dateTs).setDate(new Date(dateTs).getDate() + 1);
	  return [dateFrom, dateTo];
	}

	class Bookings extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.Bookings;
	  }
	  getState() {
	    return {
	      collection: {}
	    };
	  }
	  getElementState() {
	    return {
	      id: 0,
	      resourcesIds: [],
	      clientId: 0,
	      counter: 0,
	      name: '',
	      dateFromTs: 0,
	      dateToTs: 0,
	      timezoneFrom: Intl.DateTimeFormat().resolvedOptions().timeZone,
	      timezoneTo: Intl.DateTimeFormat().resolvedOptions().timeZone,
	      rrule: '',
	      isConfirmed: false,
	      visitStatus: 'unknown'
	    };
	  }
	  getGetters() {
	    return {
	      /** @function bookings/get */
	      get: (state, getters, rootState, rootGetters) => {
	        const deletingBookings = rootGetters[`${booking_const.Model.Interface}/deletingBookings`];
	        return Object.values(state.collection).filter(({
	          id
	        }) => !deletingBookings[id]);
	      },
	      /** @function bookings/getById */
	      getById: state => id => state.collection[id],
	      /** @function bookings/getByIds */
	      getByIds: (state, getters) => ids => {
	        return getters.get.filter(booking => ids.includes(booking.id));
	      },
	      /** @function bookings/getByDateAndResources */
	      getByDateAndResources: (state, getters) => {
	        return (dateTs, resourcesIds) => {
	          return getters.getByDate(dateTs).filter(booking => {
	            return resourcesIds.some(resourceId => booking.resourcesIds.includes(resourceId));
	          });
	        };
	      },
	      /** @function bookings/getByDateAndIds */
	      getByDateAndIds: (state, getters) => {
	        return (dateTs, ids) => {
	          return getters.getByDate(dateTs).filter(booking => ids.includes(booking.id));
	        };
	      },
	      /** @function bookings/getByDate */
	      getByDate: (state, getters) => dateTs => {
	        const [dateFrom, dateTo] = dateToTsRange(dateTs);
	        return getters.get.filter(({
	          dateToTs,
	          dateFromTs
	        }) => dateToTs > dateFrom && dateTo > dateFromTs);
	      }
	    };
	  }
	  getActions() {
	    return {
	      /** @function bookings/add */
	      add: (store, booking) => {
	        store.commit('add', booking);
	      },
	      /** @function bookings/insertMany */
	      insertMany: (store, bookings) => {
	        bookings.forEach(booking => store.commit('insert', booking));
	      },
	      /** @function bookings/upsert */
	      upsert: (store, booking) => {
	        store.commit('upsert', booking);
	      },
	      /** @function bookings/upsertMany */
	      upsertMany: (store, bookings) => {
	        bookings.forEach(booking => store.commit('upsert', booking));
	      },
	      /** @function bookings/update */
	      update: (store, payload) => {
	        store.commit('update', payload);
	      },
	      /** @function bookings/delete */
	      delete: (store, bookingId) => {
	        store.commit('delete', bookingId);
	      },
	      deleteMany: (store, bookingIds) => {
	        store.commit('deleteMany', bookingIds);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      add: (state, booking) => {
	        state.collection[booking.id] = booking;
	      },
	      insert: (state, booking) => {
	        var _state$collection, _booking$id, _state$collection$_bo;
	        (_state$collection$_bo = (_state$collection = state.collection)[_booking$id = booking.id]) != null ? _state$collection$_bo : _state$collection[_booking$id] = booking;
	      },
	      upsert: (state, booking) => {
	        var _state$collection2, _booking$id2, _state$collection2$_b;
	        (_state$collection2$_b = (_state$collection2 = state.collection)[_booking$id2 = booking.id]) != null ? _state$collection2$_b : _state$collection2[_booking$id2] = booking;
	        Object.assign(state.collection[booking.id], booking);
	      },
	      update: (state, {
	        id,
	        booking
	      }) => {
	        const updatedBooking = {
	          ...state.collection[id],
	          ...booking
	        };
	        delete state.collection[id];
	        state.collection[booking.id] = updatedBooking;
	      },
	      delete: (state, bookingId) => {
	        delete state.collection[bookingId];
	      },
	      deleteMany: (state, bookingIds) => {
	        for (const id of bookingIds) {
	          delete state.collection[id];
	        }
	      }
	    };
	  }
	}

	exports.Bookings = Bookings;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=bookings.bundle.js.map
