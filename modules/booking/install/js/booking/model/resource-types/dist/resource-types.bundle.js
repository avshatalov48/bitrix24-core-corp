/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	class ResourceTypes extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.ResourceTypes;
	  }
	  getState() {
	    return {
	      collection: {}
	    };
	  }
	  getElementState() {
	    return {
	      id: 0,
	      moduleId: '',
	      name: '',
	      code: ''
	    };
	  }
	  getGetters() {
	    return {
	      /** @function resourceTypes/get */
	      get: state => Object.values(state.collection),
	      /** @function resourceTypes/getById */
	      getById: state => id => state.collection[id]
	    };
	  }
	  getActions() {
	    return {
	      /** @function resourceTypes/upsert */
	      upsert: (store, resourceType) => {
	        store.commit('upsert', resourceType);
	      },
	      /** @function resourceTypes/upsertMany */
	      upsertMany: (store, resourceTypes) => {
	        resourceTypes.forEach(resourceType => store.commit('upsert', resourceType));
	      }
	    };
	  }
	  getMutations() {
	    return {
	      upsert: (state, resourceType) => {
	        var _state$collection, _resourceType$id, _state$collection$_re;
	        (_state$collection$_re = (_state$collection = state.collection)[_resourceType$id = resourceType.id]) != null ? _state$collection$_re : _state$collection[_resourceType$id] = resourceType;
	        Object.assign(state.collection[resourceType.id], resourceType);
	      }
	    };
	  }
	}

	exports.ResourceTypes = ResourceTypes;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=resource-types.bundle.js.map
