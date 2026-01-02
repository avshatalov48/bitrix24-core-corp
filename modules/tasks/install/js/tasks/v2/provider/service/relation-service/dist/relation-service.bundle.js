/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,main_core,tasks_v2_core,tasks_v2_const,tasks_v2_lib_apiClient,tasks_v2_provider_service_taskService) {
	'use strict';

	const limit = tasks_v2_const.Limit.RelationList;
	var _meta = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("meta");
	var _addPromises = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addPromises");
	var _requestTasks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestTasks");
	var _getVisibleIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getVisibleIds");
	var _updateStoreRelationTasks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateStoreRelationTasks");
	class RelationService {
	  constructor(_meta2) {
	    Object.defineProperty(this, _updateStoreRelationTasks, {
	      value: _updateStoreRelationTasks2
	    });
	    Object.defineProperty(this, _getVisibleIds, {
	      value: _getVisibleIds2
	    });
	    Object.defineProperty(this, _requestTasks, {
	      value: _requestTasks2
	    });
	    Object.defineProperty(this, _meta, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _addPromises, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta] = _meta2;
	  }
	  async list(taskId, withIds = false) {
	    await Promise.all(babelHelpers.classPrivateFieldLooseBase(this, _addPromises)[_addPromises]);
	    const {
	      tasks,
	      ids
	    } = await babelHelpers.classPrivateFieldLooseBase(this, _requestTasks)[_requestTasks](taskId, withIds);
	    if (withIds) {
	      babelHelpers.classPrivateFieldLooseBase(this, _updateStoreRelationTasks)[_updateStoreRelationTasks](taskId, ids);
	    }
	    tasks.forEach(taskDto => {
	      if (!tasks_v2_provider_service_taskService.taskService.hasStoreTask(taskDto.id)) {
	        tasks_v2_provider_service_taskService.taskService.extractTask({
	          ...taskDto,
	          [babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].relationToField]: taskId
	        });
	        void tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Tasks}/addPartiallyLoaded`, taskDto.id);
	      }
	    });
	  }
	  async setParent(taskId, parentId) {
	    return this.add(parentId, [taskId]);
	  }
	  async add(taskId, taskIds) {
	    this.addStore(taskId, taskIds);
	    if (!tasks_v2_provider_service_taskService.taskService.isRealId(taskId) || taskIds.length === 0) {
	      return null;
	    }
	    try {
	      const promise = tasks_v2_lib_apiClient.apiClient.post(`${babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].controller}.add`, {
	        taskId,
	        taskIds
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _addPromises)[_addPromises].push(promise);
	      await promise;
	      babelHelpers.classPrivateFieldLooseBase(this, _addPromises)[_addPromises] = babelHelpers.classPrivateFieldLooseBase(this, _addPromises)[_addPromises].filter(it => it !== promise);
	    } catch (error) {
	      var _error$errors, _error$errors$;
	      const failedIds = Object.entries(error.data).filter(([, success]) => !success).map(([id]) => Number(id));
	      this.deleteStore(taskId, failedIds);
	      console.error(`${babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].controller}.add error`, error);
	      return (_error$errors = error.errors) == null ? void 0 : (_error$errors$ = _error$errors[0]) == null ? void 0 : _error$errors$.message;
	    }
	    return null;
	  }
	  addStore(taskId, taskIds) {
	    const meta = babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta];
	    const task = tasks_v2_provider_service_taskService.taskService.getStoreTask(taskId);
	    babelHelpers.classPrivateFieldLooseBase(this, _updateStoreRelationTasks)[_updateStoreRelationTasks](taskId, [...((task == null ? void 0 : task[meta.idsField]) || []), ...taskIds]);
	    taskIds.forEach(it => tasks_v2_provider_service_taskService.taskService.updateStoreTask(it, {
	      [meta.relationToField]: taskId
	    }));
	  }
	  async delete(taskId, taskIds) {
	    this.deleteStore(taskId, taskIds);
	    if (!tasks_v2_provider_service_taskService.taskService.isRealId(taskId) || taskIds.length === 0) {
	      return;
	    }
	    try {
	      await tasks_v2_lib_apiClient.apiClient.post(`${babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].controller}.delete`, {
	        taskId,
	        taskIds
	      });
	    } catch (error) {
	      this.addStore(taskId, taskIds);
	      console.error(`${babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].controller}.delete error`, error);
	    }
	  }
	  deleteStore(taskId, taskIds) {
	    const meta = babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta];
	    const task = tasks_v2_provider_service_taskService.taskService.getStoreTask(taskId);
	    babelHelpers.classPrivateFieldLooseBase(this, _updateStoreRelationTasks)[_updateStoreRelationTasks](taskId, task == null ? void 0 : task[meta.idsField].filter(it => !taskIds.includes(it)));
	    taskIds.forEach(it => tasks_v2_provider_service_taskService.taskService.updateStoreTask(it, {
	      [meta.relationToField]: 0
	    }));
	  }
	  areIdsLoaded(taskId) {
	    const meta = babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta];
	    const task = tasks_v2_provider_service_taskService.taskService.getStoreTask(taskId);
	    if (!task || !tasks_v2_provider_service_taskService.taskService.isRealId(taskId)) {
	      return false;
	    }
	    return !task[meta.containsField] || task[meta.containsField] && task[meta.idsField].length > 0;
	  }
	  hasUnloadedIds(taskId) {
	    const ids = tasks_v2_provider_service_taskService.taskService.getStoreTask(taskId)[babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].idsField];
	    return babelHelpers.classPrivateFieldLooseBase(this, _getVisibleIds)[_getVisibleIds](ids).some(it => !tasks_v2_provider_service_taskService.taskService.hasStoreTask(it));
	  }
	}
	async function _requestTasks2(taskId, withIds = false) {
	  if (!tasks_v2_provider_service_taskService.taskService.isRealId(taskId)) {
	    const ids = tasks_v2_provider_service_taskService.taskService.getStoreTask(taskId)[babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].idsField];
	    const {
	      tasks
	    } = await tasks_v2_lib_apiClient.apiClient.post(`${babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].controller}.listByIds`, {
	      taskIds: babelHelpers.classPrivateFieldLooseBase(this, _getVisibleIds)[_getVisibleIds](ids)
	    });
	    return {
	      tasks,
	      ids
	    };
	  }
	  const {
	    tasks,
	    ids
	  } = await tasks_v2_lib_apiClient.apiClient.post(`${babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].controller}.list`, {
	    taskId,
	    relationTaskSelect: ['id', 'title', 'creator', 'responsible', 'deadline', 'rights'],
	    withIds,
	    navigation: {
	      size: limit
	    }
	  });
	  return {
	    tasks,
	    ids
	  };
	}
	function _getVisibleIds2(ids) {
	  return [...ids].sort((a, b) => b - a).slice(0, limit);
	}
	function _updateStoreRelationTasks2(taskId, taskIds) {
	  const meta = babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta];
	  const relationIds = [...new Set(taskIds)];
	  const contains = relationIds.length > 0;
	  void tasks_v2_provider_service_taskService.taskService.updateStoreTask(taskId, {
	    [meta.idsField]: relationIds,
	    [meta.containsField]: contains
	  });
	}

	class SubTasksService extends RelationService {
	  async setParent(taskId, parentId) {
	    var _taskService$getStore;
	    if (parentId === taskId) {
	      return main_core.Loc.getMessage('TASKS_V2_RELATION_PARENT_CANNOT_BE_PARENT');
	    }
	    if ((_taskService$getStore = tasks_v2_provider_service_taskService.taskService.getStoreTask(taskId)) != null && _taskService$getStore.subTaskIds.includes(parentId)) {
	      return main_core.Loc.getMessage('TASKS_V2_RELATION_SUB_TASK_CANNOT_BE_PARENT');
	    }
	    if (!tasks_v2_provider_service_taskService.taskService.isRealId(taskId)) {
	      return this.addStore(parentId, [taskId]);
	    }
	    const currentParentId = tasks_v2_provider_service_taskService.taskService.getStoreTask(taskId).parentId;
	    const error = await this.add(parentId, [taskId]);
	    if (error) {
	      this.addStore(currentParentId, [taskId]);
	    }
	    return error;
	  }
	  async add(taskId, taskIds) {
	    var _taskService$getStore2, _error3;
	    let error = null;
	    if (taskIds.includes(taskId)) {
	      var _error;
	      (_error = error) != null ? _error : error = main_core.Loc.getMessage('TASKS_V2_RELATION_SELF_CANNOT_BE_SUB_TASK');
	    }
	    const parentId = (_taskService$getStore2 = tasks_v2_provider_service_taskService.taskService.getStoreTask(taskId)) == null ? void 0 : _taskService$getStore2.parentId;
	    if (taskIds.includes(parentId)) {
	      var _error2;
	      (_error2 = error) != null ? _error2 : error = main_core.Loc.getMessage('TASKS_V2_RELATION_PARENT_CANNOT_BE_SUB_TASK');
	    }
	    (_error3 = error) != null ? _error3 : error = super.add(taskId, taskIds.filter(id => id !== taskId && id !== parentId));
	    return error;
	  }
	  addStore(id, ids) {
	    ids.forEach(it => {
	      var _taskService$getStore3;
	      return this.deleteStore((_taskService$getStore3 = tasks_v2_provider_service_taskService.taskService.getStoreTask(it)) == null ? void 0 : _taskService$getStore3.parentId, [it]);
	    });
	    super.addStore(id, ids);
	  }
	}

	const subTasksMeta = Object.freeze({
	  idsField: 'subTaskIds',
	  containsField: 'containsSubTasks',
	  relationToField: 'parentId',
	  controller: 'Task.Relation.Child'
	});
	const relatedTasksMeta = Object.freeze({
	  idsField: 'relatedTaskIds',
	  containsField: 'containsRelatedTasks',
	  relationToField: 'relatedToTaskId',
	  controller: 'Task.Relation.Related'
	});

	const subTasksService = new SubTasksService(subTasksMeta);
	const relatedTasksService = new RelationService(relatedTasksMeta);

	exports.subTasksService = subTasksService;
	exports.relatedTasksService = relatedTasksService;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX,BX.Tasks.V2,BX.Tasks.V2.Const,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=relation-service.bundle.js.map
