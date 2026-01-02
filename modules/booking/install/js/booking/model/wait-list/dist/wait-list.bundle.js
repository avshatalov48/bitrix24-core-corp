/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	/* eslint-disable no-param-reassign */
	class WaitList extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.WaitList;
	  }
	  getState() {
	    return {
	      collection: {}
	    };
	  }
	  getElementState() {
	    return {
	      id: 0,
	      createdBy: 0,
	      createdAt: 0,
	      updatedAt: 0,
	      clients: [],
	      note: '',
	      externalData: []
	    };
	  }
	  getGetters() {
	    return {
	      /** @function wait-list/get */
	      get: (state, getters, rootState, rootGetters) => {
	        const deletingWaitListItems = rootGetters[`${booking_const.Model.Interface}/deletingWaitListItems`];
	        return Object.values(state.collection).filter(({
	          id
	        }) => !deletingWaitListItems[id]);
	      },
	      getById: state => id => state.collection[id]
	    };
	  }
	  getActions() {
	    return {
	      /** @function wait-list/add */
	      add: (store, waitListItem) => {
	        store.commit('add', waitListItem);
	      },
	      /** @function wait-list/insertMany */
	      insertMany: (store, waitListItems) => {
	        waitListItems.forEach(waitListItem => store.commit('insert', waitListItem));
	      },
	      /** @function wait-list/upsert */
	      upsert: (store, waitListItem) => {
	        store.commit('upsert', waitListItem);
	      },
	      /** @function wait-list/upsertMany */
	      upsertMany: (store, waitListItems) => {
	        waitListItems.forEach(waitListItem => store.commit('upsert', waitListItem));
	      },
	      /** @function wait-list/update */
	      update: (store, payload) => {
	        store.commit('update', payload);
	      },
	      /** @function wait-list/delete */
	      delete: (store, waitListItemId) => {
	        store.commit('delete', waitListItemId);
	      },
	      /** @function wait-list/deleteMany */
	      deleteMany: (store, waitListItemIds) => {
	        store.commit('deleteMany', waitListItemIds);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      add: (state, waitListItem) => {
	        state.collection[waitListItem.id] = waitListItem;
	      },
	      insert: (state, waitListItem) => {
	        var _state$collection, _waitListItem$id, _state$collection$_wa;
	        (_state$collection$_wa = (_state$collection = state.collection)[_waitListItem$id = waitListItem.id]) != null ? _state$collection$_wa : _state$collection[_waitListItem$id] = waitListItem;
	      },
	      upsert: (state, waitListItem) => {
	        var _state$collection2, _waitListItem$id2, _state$collection2$_w;
	        (_state$collection2$_w = (_state$collection2 = state.collection)[_waitListItem$id2 = waitListItem.id]) != null ? _state$collection2$_w : _state$collection2[_waitListItem$id2] = waitListItem;
	        Object.assign(state.collection[waitListItem.id], waitListItem);
	      },
	      update: (state, {
	        id,
	        waitListItem
	      }) => {
	        const updatedWaitListItem = {
	          ...state.collection[id],
	          ...waitListItem
	        };
	        delete state.collection[id];
	        state.collection[waitListItem.id] = updatedWaitListItem;
	      },
	      delete: (state, waitListItemId) => {
	        delete state.collection[waitListItemId];
	      },
	      deleteMany: (state, waitListItemIds) => {
	        for (const id of waitListItemIds) {
	          delete state.collection[id];
	        }
	      }
	    };
	  }
	}

	exports.WaitList = WaitList;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=wait-list.bundle.js.map
