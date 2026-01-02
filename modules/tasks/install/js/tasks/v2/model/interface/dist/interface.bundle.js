/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,ui_vue3_vuex,tasks_v2_const) {
	'use strict';

	/* eslint-disable no-param-reassign */
	class Interface extends ui_vue3_vuex.BuilderModel {
	  static createWithVariables(params) {
	    return Interface.create().setVariables({
	      currentUserId: params.currentUserId,
	      defaultDeadline: params.defaultDeadline
	    });
	  }
	  getName() {
	    return tasks_v2_const.Model.Interface;
	  }
	  getState() {
	    return {
	      currentUserId: this.getVariable('currentUserId', 0),
	      titleFieldOffsetHeight: this.getVariable('titleFieldOffsetHeight', null),
	      defaultDeadline: this.getVariable('defaultDeadline', {
	        defaultDeadlineInSeconds: 0,
	        defaultDeadlineDate: ''
	      }),
	      deletingCheckListIds: {},
	      disableCheckListAnimations: false,
	      checkListCompletionCallbacks: {}
	    };
	  }
	  getGetters() {
	    return {
	      /** @function interface/currentUserId */
	      currentUserId: state => state.currentUserId,
	      /** @function interface/titleFieldOffsetHeight */
	      titleFieldOffsetHeight: state => state.titleFieldOffsetHeight,
	      /** @function interface/defaultDeadline */
	      defaultDeadline: state => state.defaultDeadline,
	      /** @function interface/deletingCheckListIds */
	      deletingCheckListIds: state => state.deletingCheckListIds,
	      /** @function interface/disableCheckListAnimations */
	      disableCheckListAnimations: state => state.disableCheckListAnimations
	    };
	  }
	  getActions() {
	    return {
	      /** @function interface/updateTitleFieldOffsetHeight */
	      updateTitleFieldOffsetHeight: (store, titleFieldOffsetHeight) => {
	        store.commit('setTitleFieldOffsetHeight', titleFieldOffsetHeight);
	      },
	      /** @function interface/updateDefaultDeadline */
	      updateDefaultDeadline: (store, defaultDeadline) => {
	        store.commit('setDefaultDeadline', defaultDeadline);
	      },
	      /** @function interface/addCheckListItemToDeleting */
	      addCheckListItemToDeleting: (store, itemId) => {
	        store.commit('addCheckListItemToDeleting', itemId);
	      },
	      /** @function interface/removeCheckListItemFromDeleting */
	      removeCheckListItemFromDeleting: (store, itemId) => {
	        store.commit('removeCheckListItemFromDeleting', itemId);
	      },
	      /** @function interface/addCheckListCompletionCallback */
	      addCheckListCompletionCallback: (store, {
	        id,
	        callback
	      }) => {
	        store.commit('addCheckListCompletionCallback', {
	          id,
	          callback
	        });
	      },
	      /** @function interface/executeCheckListCompletionCallbacks */
	      executeCheckListCompletionCallbacks: store => {
	        store.commit('executeCheckListCompletionCallbacks');
	      },
	      /** @function interface/setDisableCheckListAnimations */
	      setDisableCheckListAnimations: (store, disableCheckListAnimations) => {
	        store.commit('setDisableCheckListAnimations', disableCheckListAnimations);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      setTitleFieldOffsetHeight: (state, titleFieldOffsetHeight) => {
	        state.titleFieldOffsetHeight = titleFieldOffsetHeight;
	      },
	      setDefaultDeadline: (state, defaultDeadline) => {
	        state.defaultDeadline = defaultDeadline;
	      },
	      addCheckListItemToDeleting: (state, itemId) => {
	        state.deletingCheckListIds[itemId] = itemId;
	      },
	      removeCheckListItemFromDeleting: (state, itemId) => {
	        delete state.deletingCheckListIds[itemId];
	      },
	      addCheckListCompletionCallback: (state, {
	        id,
	        callback
	      }) => {
	        state.checkListCompletionCallbacks[id] = callback;
	      },
	      executeCheckListCompletionCallbacks: state => {
	        Object.values(state.checkListCompletionCallbacks).forEach(cb => cb());
	        state.checkListCompletionCallbacks = {};
	      },
	      setDisableCheckListAnimations: (state, disableCheckListAnimations) => {
	        state.disableCheckListAnimations = disableCheckListAnimations === true;
	      }
	    };
	  }
	}

	exports.Interface = Interface;

}((this.BX.Tasks.V2.Model = this.BX.Tasks.V2.Model || {}),BX.Vue3.Vuex,BX.Tasks.V2.Const));
//# sourceMappingURL=interface.bundle.js.map
