/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	class Favorites extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.Favorites;
	  }
	  getState() {
	    return {
	      ids: []
	    };
	  }
	  getGetters() {
	    return {
	      /** @function favorites/get */
	      get: state => state.ids
	    };
	  }
	  getActions() {
	    return {
	      /** @function favorites/set */
	      set: (store, ids) => {
	        store.commit('set', ids);
	      },
	      /** @function favorites/add */
	      add: (store, id) => {
	        store.commit('add', id);
	      },
	      /** @function favorites/addMany */
	      addMany: (store, ids) => {
	        store.commit('addMany', ids);
	      },
	      /** @function favorites/delete */
	      delete: (store, id) => {
	        store.commit('delete', id);
	      },
	      /** @function favorites/deleteMany */
	      deleteMany: (store, ids) => {
	        store.commit('deleteMany', ids);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      set: (state, ids) => {
	        state.ids = ids;
	      },
	      add: (state, id) => {
	        state.ids = [...state.ids, id];
	      },
	      addMany: (state, ids) => {
	        const uniqueIds = ids.filter(id => !state.ids.includes(id));
	        state.ids = [...state.ids, ...uniqueIds];
	      },
	      delete: (state, id) => {
	        state.ids = state.ids.filter(it => it !== id);
	      },
	      deleteMany: (state, ids) => {
	        state.ids = state.ids.filter(id => !ids.includes(id));
	      }
	    };
	  }
	}

	exports.Favorites = Favorites;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=favorites.bundle.js.map
