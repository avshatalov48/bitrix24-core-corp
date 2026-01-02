/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core,ui_vue3_vuex,tasks_v2_model_checkList,tasks_v2_model_crmItems,tasks_v2_model_epics,tasks_v2_model_flows,tasks_v2_model_groups,tasks_v2_model_interface,tasks_v2_model_stages,tasks_v2_model_tasks,tasks_v2_model_users,tasks_v2_provider_pull_pullManager) {
	'use strict';

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _store = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("store");
	var _builder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("builder");
	var _initPromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPromise");
	var _pullManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pullManager");
	var _initStore = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initStore");
	var _initPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPull");
	class CoreApplication {
	  constructor() {
	    Object.defineProperty(this, _initPull, {
	      value: _initPull2
	    });
	    Object.defineProperty(this, _initStore, {
	      value: _initStore2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: main_core.Extension.getSettings('tasks.v2.core')
	    });
	    Object.defineProperty(this, _store, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _builder, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _initPromise, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pullManager, {
	      writable: true,
	      value: null
	    });
	  }
	  getParams() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _params)[_params];
	  }
	  getStore() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _store)[_store];
	  }
	  init() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    // eslint-disable-next-line no-async-promise-executor
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _initPromise))[_initPromise]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_initPromise] = new Promise(async resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _store)[_store] = await babelHelpers.classPrivateFieldLooseBase(this, _initStore)[_initStore]();
	      babelHelpers.classPrivateFieldLooseBase(this, _initPull)[_initPull]();
	      resolve();
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _initPromise)[_initPromise];
	  }
	  async addDynamicModel(vuexBuilderModel) {
	    if (!(babelHelpers.classPrivateFieldLooseBase(this, _builder)[_builder] instanceof ui_vue3_vuex.Builder)) {
	      throw new TypeError('Builder has not been init');
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _store)[_store].hasModule(vuexBuilderModel.getName())) {
	      return;
	    }
	    await babelHelpers.classPrivateFieldLooseBase(this, _builder)[_builder].addDynamicModel(vuexBuilderModel);
	  }
	  removeDynamicModel(vuexModelName) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _builder)[_builder] instanceof ui_vue3_vuex.Builder && babelHelpers.classPrivateFieldLooseBase(this, _store)[_store].hasModule(vuexModelName)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _builder)[_builder].removeDynamicModel(vuexModelName);
	    }
	  }
	}
	async function _initStore2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _builder)[_builder] = ui_vue3_vuex.Builder.init();
	  babelHelpers.classPrivateFieldLooseBase(this, _builder)[_builder].addModel(tasks_v2_model_checkList.CheckList.create()).addModel(tasks_v2_model_crmItems.CrmItems.create()).addModel(tasks_v2_model_epics.Epics.create()).addModel(tasks_v2_model_flows.Flows.create()).addModel(tasks_v2_model_groups.Groups.create()).addModel(tasks_v2_model_interface.Interface.createWithVariables(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params])).addModel(tasks_v2_model_stages.Stages.create()).addModel(tasks_v2_model_tasks.Tasks.create()).addModel(tasks_v2_model_users.Users.create());
	  const builderResult = await babelHelpers.classPrivateFieldLooseBase(this, _builder)[_builder].build();
	  return builderResult.store;
	}
	function _initPull2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _pullManager)[_pullManager] = new tasks_v2_provider_pull_pullManager.PullManager({
	    currentUserId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _pullManager)[_pullManager].initQueueManager();
	}
	const Core = new CoreApplication();

	exports.Core = Core;

}((this.BX.Tasks.V2 = this.BX.Tasks.V2 || {}),BX,BX.Vue3.Vuex,BX.Tasks.V2.Model,BX.Tasks.V2.Model,BX.Tasks.V2.Model,BX.Tasks.V2.Model,BX.Tasks.V2.Model,BX.Tasks.V2.Model,BX.Tasks.V2.Model,BX.Tasks.V2.Model,BX.Tasks.V2.Model,BX.Tasks.V2.Provider.Pull));
//# sourceMappingURL=core.bundle.js.map
