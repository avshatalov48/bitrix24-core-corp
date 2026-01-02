/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,ui_vue3_vuex,tasks_v2_const) {
	'use strict';

	/* eslint-disable no-param-reassign */
	const aliasFields = {
	  [tasks_v2_const.TaskField.DatePlan]: new Set(['startPlanTs', 'endPlanTs', 'matchesSubTasksTime']),
	  [tasks_v2_const.TaskField.SubTasks]: new Set(['containsSubTasks']),
	  [tasks_v2_const.TaskField.RelatedTasks]: new Set(['containsRelatedTasks'])
	};
	var _setFieldsFilled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setFieldsFilled");
	var _getAliasField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAliasField");
	class Tasks extends ui_vue3_vuex.BuilderEntityModel {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getAliasField, {
	      value: _getAliasField2
	    });
	    Object.defineProperty(this, _setFieldsFilled, {
	      value: _setFieldsFilled2
	    });
	  }
	  getName() {
	    return tasks_v2_const.Model.Tasks;
	  }
	  getState() {
	    return {
	      partiallyLoadedIds: new Set()
	    };
	  }
	  getElementState() {
	    return {
	      id: 0,
	      title: '',
	      isImportant: false,
	      description: '',
	      creatorId: 0,
	      createdTs: Date.now(),
	      responsibleId: 0,
	      deadlineTs: 0,
	      startPlanTs: null,
	      endPlanTs: null,
	      fileIds: [],
	      checklist: [],
	      containsChecklist: false,
	      storyPoints: '',
	      epicId: 0,
	      accomplicesIds: [],
	      auditorsIds: [],
	      tags: [],
	      status: 'pending',
	      statusChangedTs: Date.now(),
	      needsControl: false,
	      filledFields: {},
	      parentId: 0,
	      subTaskIds: [],
	      containsSubTasks: false,
	      relatedTaskIds: [],
	      containsRelatedTasks: false,
	      rights: {
	        edit: true,
	        deadline: true,
	        datePlan: true,
	        delegate: true
	      },
	      inFavorite: [],
	      inMute: []
	    };
	  }
	  getGetters() {
	    return {
	      /** @function tasks/wasFieldFilled */
	      wasFieldFilled: state => (id, fieldName) => {
	        var _state$collection$id$, _state$collection$id$2;
	        return (_state$collection$id$ = (_state$collection$id$2 = state.collection[id].filledFields) == null ? void 0 : _state$collection$id$2[fieldName]) != null ? _state$collection$id$ : false;
	      },
	      /** @function tasks/isPartiallyLoaded */
	      isPartiallyLoaded: state => id => {
	        return state.partiallyLoadedIds.has(id);
	      }
	    };
	  }
	  getActions() {
	    return {
	      /** @function tasks/setFieldFilled */
	      setFieldFilled: (store, {
	        id,
	        fieldName
	      }) => {
	        store.commit('setFieldFilled', {
	          id,
	          fieldName
	        });
	      },
	      /** @function tasks/clearFieldsFilled */
	      clearFieldsFilled: (store, id) => {
	        store.commit('clearFieldsFilled', id);
	      },
	      /** @function tasks/addPartiallyLoaded */
	      addPartiallyLoaded: (store, id) => {
	        store.commit('addPartiallyLoaded', id);
	      },
	      /** @function tasks/removePartiaalyLoaded */
	      removePartiallyLoaded: (store, id) => {
	        store.commit('removePartiallyLoaded', id);
	      }
	    };
	  }
	  getMutations() {
	    return {
	      upsert: (state, task) => {
	        ui_vue3_vuex.BuilderEntityModel.defaultModel.getMutations(this).upsert(state, task);
	        babelHelpers.classPrivateFieldLooseBase(this, _setFieldsFilled)[_setFieldsFilled](state, task.id);
	      },
	      update: (state, {
	        id,
	        fields
	      }) => {
	        ui_vue3_vuex.BuilderEntityModel.defaultModel.getMutations(this).update(state, {
	          id,
	          fields
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _setFieldsFilled)[_setFieldsFilled](state, id);
	      },
	      setFieldFilled: (state, {
	        id,
	        fieldName
	      }) => {
	        var _state$collection$id, _state$collection$id$3;
	        ((_state$collection$id$3 = (_state$collection$id = state.collection[id]).filledFields) != null ? _state$collection$id$3 : _state$collection$id.filledFields = {})[fieldName] = true;
	      },
	      clearFieldsFilled: (state, id) => {
	        if (!state.collection[id]) {
	          return;
	        }
	        state.collection[id].filledFields = {};
	        babelHelpers.classPrivateFieldLooseBase(this, _setFieldsFilled)[_setFieldsFilled](state, id);
	      },
	      addPartiallyLoaded: (state, id) => {
	        state.partiallyLoadedIds.add(id);
	      },
	      removePartiallyLoaded: (state, id) => {
	        state.partiallyLoadedIds.delete(id);
	      }
	    };
	  }
	}
	function _setFieldsFilled2(state, id) {
	  var _task$rights, _task$filledFields;
	  const task = state.collection[id];
	  const canEdit = task == null ? void 0 : (_task$rights = task.rights) == null ? void 0 : _task$rights.edit;
	  (_task$filledFields = task.filledFields) != null ? _task$filledFields : task.filledFields = {};
	  Object.entries(task).forEach(([fieldName, value]) => {
	    const isFilled = Boolean(value) && (!Array.isArray(value) || value.length > 0);
	    if (isFilled) {
	      var _babelHelpers$classPr;
	      task.filledFields[(_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getAliasField)[_getAliasField](fieldName)) != null ? _babelHelpers$classPr : fieldName] = true;
	    }
	    if (!isFilled && !canEdit) {
	      task.filledFields[fieldName] = false;
	    }
	  });
	}
	function _getAliasField2(fieldName) {
	  var _Object$entries$find;
	  return (_Object$entries$find = Object.entries(aliasFields).find(([, alias]) => alias.has(fieldName))) == null ? void 0 : _Object$entries$find[0];
	}

	exports.Tasks = Tasks;

}((this.BX.Tasks.V2.Model = this.BX.Tasks.V2.Model || {}),BX.Vue3.Vuex,BX.Tasks.V2.Const));
//# sourceMappingURL=tasks.bundle.js.map
