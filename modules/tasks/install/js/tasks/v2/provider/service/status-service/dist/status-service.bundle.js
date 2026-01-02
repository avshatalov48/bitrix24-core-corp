/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,tasks_v2_core,tasks_v2_const,tasks_v2_lib_apiClient,tasks_v2_provider_service_taskService) {
	'use strict';

	var _updateStatus, _updateStoreTask, _getStoreTask;
	const statusService = new (_updateStatus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateStatus"), _updateStoreTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateStoreTask"), _getStoreTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getStoreTask"), class {
	  constructor() {
	    Object.defineProperty(this, _getStoreTask, {
	      value: _getStoreTask2
	    });
	    Object.defineProperty(this, _updateStoreTask, {
	      value: _updateStoreTask2
	    });
	    Object.defineProperty(this, _updateStatus, {
	      value: _updateStatus2
	    });
	  }
	  async start(id) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateStatus)[_updateStatus](id, 'Task.Status.start', tasks_v2_const.TaskStatus.InProgress);
	  }
	  async disapprove(id) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateStatus)[_updateStatus](id, 'Task.Status.disapprove', tasks_v2_const.TaskStatus.Pending);
	  }
	  async defer(id) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateStatus)[_updateStatus](id, 'Task.Status.defer', tasks_v2_const.TaskStatus.Deferred);
	  }
	  async approve(id) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateStatus)[_updateStatus](id, 'Task.Status.approve', tasks_v2_const.TaskStatus.Completed);
	  }
	  async pause(id) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateStatus)[_updateStatus](id, 'Task.Status.pause', tasks_v2_const.TaskStatus.Pending);
	  }
	  async complete(id) {
	    const status = babelHelpers.classPrivateFieldLooseBase(this, _getStoreTask)[_getStoreTask](id).needsControl ? tasks_v2_const.TaskStatus.SupposedlyCompleted : tasks_v2_const.TaskStatus.Completed;
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateStatus)[_updateStatus](id, 'Task.Status.complete', status);
	  }
	  async renew(id) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateStatus)[_updateStatus](id, 'Task.Status.renew', tasks_v2_const.TaskStatus.Pending);
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	})();
	async function _updateStatus2(id, action, status) {
	  const taskBeforeUpdate = babelHelpers.classPrivateFieldLooseBase(this, _getStoreTask)[_getStoreTask](id);
	  await babelHelpers.classPrivateFieldLooseBase(this, _updateStoreTask)[_updateStoreTask](id, {
	    status
	  });
	  if (!tasks_v2_provider_service_taskService.taskService.isRealId(id)) {
	    return;
	  }
	  try {
	    const data = await tasks_v2_lib_apiClient.apiClient.post(action, {
	      task: {
	        id
	      }
	    });
	    await tasks_v2_provider_service_taskService.taskService.extractTask(data);
	  } catch (error) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateStoreTask)[_updateStoreTask](id, taskBeforeUpdate);
	    console.error(`StatusService: ${action} error`, error);
	  }
	}
	async function _updateStoreTask2(id, fields) {
	  await this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	    id,
	    fields
	  });
	}
	function _getStoreTask2(id) {
	  return {
	    ...this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](id)
	  };
	}

	exports.statusService = statusService;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX.Tasks.V2,BX.Tasks.V2.Const,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=status-service.bundle.js.map
