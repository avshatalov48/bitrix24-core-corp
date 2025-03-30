/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	var _updateSlotRangesTimezone = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateSlotRangesTimezone");
	class Resources extends ui_vue3_vuex.BuilderModel {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _updateSlotRangesTimezone, {
	      value: _updateSlotRangesTimezone2
	    });
	  }
	  getName() {
	    return booking_const.Model.Resources;
	  }
	  getState() {
	    return {
	      collection: {}
	    };
	  }
	  getElementState() {
	    return {
	      id: 0,
	      type: '',
	      name: '',
	      description: '',
	      linkedResources: [],
	      slotRanges: [],
	      workLoad: null,
	      counter: null,
	      isMain: false,
	      isConfirmationNotificationOn: true,
	      isFeedbackNotificationOn: true,
	      isInfoNotificationOn: false,
	      isDelayedNotificationOn: false,
	      isReminderNotificationOn: false,
	      templateTypeConfirmation: '',
	      templateTypeFeedback: '',
	      templateTypeInfo: '',
	      templateTypeDelayed: '',
	      templateTypeReminder: '',
	      createdBy: 0,
	      createdAt: 0,
	      updatedAt: 0
	    };
	  }
	  getGetters() {
	    return {
	      /** @function resources/get */
	      get: state => Object.values(state.collection),
	      /** @function resources/getById */
	      getById: state => id => state.collection[id],
	      /** @function resources/getByIds */
	      getByIds: state => ids => {
	        return ids.map(id => state.collection[id]);
	      }
	    };
	  }
	  getActions() {
	    return {
	      /** @function resources/insertMany */
	      insertMany: (store, resources) => {
	        resources.forEach(resource => store.commit('insert', resource));
	      },
	      /** @function resources/upsert */
	      upsert: (store, resource) => {
	        store.commit('upsert', resource);
	      },
	      /** @function resources/upsertMany */
	      upsertMany: (store, resources) => {
	        resources.forEach(resource => store.commit('upsert', resource));
	      },
	      /** @function resources/delete */
	      delete: (store, resourceId) => {
	        store.commit('delete', resourceId);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      insert: (state, resource) => {
	        var _state$collection, _resource$id, _state$collection$_re;
	        resource.slotRanges = babelHelpers.classPrivateFieldLooseBase(this, _updateSlotRangesTimezone)[_updateSlotRangesTimezone](resource.slotRanges);
	        (_state$collection$_re = (_state$collection = state.collection)[_resource$id = resource.id]) != null ? _state$collection$_re : _state$collection[_resource$id] = resource;
	      },
	      upsert: (state, resource) => {
	        var _state$collection2, _resource$id2, _state$collection2$_r;
	        resource.slotRanges = babelHelpers.classPrivateFieldLooseBase(this, _updateSlotRangesTimezone)[_updateSlotRangesTimezone](resource.slotRanges);
	        (_state$collection2$_r = (_state$collection2 = state.collection)[_resource$id2 = resource.id]) != null ? _state$collection2$_r : _state$collection2[_resource$id2] = resource;
	        Object.assign(state.collection[resource.id], resource);
	      },
	      delete: (state, resourceId) => {
	        delete state.collection[resourceId];
	      }
	    };
	  }
	}
	function _updateSlotRangesTimezone2(slotRanges) {
	  return slotRanges.map(slotRange => {
	    return {
	      ...slotRange,
	      timezone: slotRange.timezone === '' ? Intl.DateTimeFormat().resolvedOptions().timeZone : slotRange.timezone
	    };
	  });
	}

	exports.Resources = Resources;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=resources.bundle.js.map
