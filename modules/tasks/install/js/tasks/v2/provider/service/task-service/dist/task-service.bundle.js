/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,tasks_v2_provider_service_checkListService,main_core,main_core_events,tasks_v2_const,tasks_v2_core,tasks_v2_lib_apiClient,tasks_v2_provider_service_fileService,tasks_v2_provider_service_relationService,tasks_v2_provider_service_groupService,tasks_v2_provider_service_flowService,tasks_v2_provider_service_userService) {
	'use strict';

	function mapModelToDto(task) {
	  var _task$accomplicesIds, _task$auditorsIds, _task$tags;
	  return {
	    id: task.id,
	    title: prepareValue(task.title),
	    description: prepareValue(task.description, mapDescription(task.description)),
	    creator: prepareValue(task.creatorId, {
	      id: task.creatorId
	    }),
	    createdTs: prepareValue(task.createdTs, Math.floor(task.createdTs / 1000)),
	    responsible: prepareValue(task.responsibleId, {
	      id: task.responsibleId
	    }),
	    deadlineTs: prepareValue(task.deadlineTs, Math.floor(task.deadlineTs / 1000)),
	    needsControl: prepareValue(task.needsControl),
	    startPlanTs: prepareValue(task.startPlanTs, Math.floor(task.startPlanTs / 1000)),
	    endPlanTs: prepareValue(task.endPlanTs, Math.floor(task.endPlanTs / 1000)),
	    fileIds: prepareValue(task.fileIds),
	    checklist: prepareValue(task.checklist),
	    parent: prepareValue(task.parentId, {
	      id: task.parentId
	    }),
	    dependsOn: prepareValue(task.relatedTaskIds),
	    group: prepareValue(task.groupId, {
	      id: task.groupId
	    }),
	    stage: prepareValue(task.stageId, {
	      id: task.stageId
	    }),
	    flow: prepareValue(task.flowId, {
	      id: task.flowId
	    }),
	    priority: prepareValue(task.isImportant, task.isImportant ? 'high' : 'low'),
	    status: prepareValue(task.status),
	    statusChangedTs: prepareValue(task.statusChangedTs, Math.floor(task.statusChangedTs / 1000)),
	    accomplices: prepareValue(task.accomplicesIds, (_task$accomplicesIds = task.accomplicesIds) == null ? void 0 : _task$accomplicesIds.map(id => ({
	      id
	    }))),
	    auditors: prepareValue(task.auditorsIds, (_task$auditorsIds = task.auditorsIds) == null ? void 0 : _task$auditorsIds.map(id => ({
	      id
	    }))),
	    tags: prepareValue(task.tags, mapTags((_task$tags = task.tags) != null ? _task$tags : [])),
	    chatId: prepareValue(task.chatId),
	    crmItemIds: prepareValue(task.crmItemIds),
	    allowsChangeDeadline: prepareValue(task.allowsChangeDeadline),
	    allowsChangeDatePlan: prepareValue(task.allowsChangeDatePlan),
	    matchesWorkTime: prepareValue(task.matchesWorkTime),
	    matchesSubTasksTime: prepareValue(task.matchesSubTasksTime),
	    source: prepareValue(task.source)
	  };
	}
	function mapDtoToModel(taskDto) {
	  var _taskDto$creator, _taskDto$responsible, _taskDto$checklist, _taskDto$parentId, _taskDto$group, _taskDto$stage$id, _taskDto$stage, _taskDto$flow, _taskDto$accomplices$, _taskDto$accomplices, _taskDto$auditors$map, _taskDto$auditors, _taskDto$tags;
	  const task = {
	    id: taskDto.id,
	    title: taskDto.title,
	    isImportant: taskDto.priority === 'high',
	    description: taskDto.description,
	    creatorId: (_taskDto$creator = taskDto.creator) == null ? void 0 : _taskDto$creator.id,
	    createdTs: taskDto.createdTs * 1000,
	    responsibleId: (_taskDto$responsible = taskDto.responsible) == null ? void 0 : _taskDto$responsible.id,
	    deadlineTs: taskDto.deadlineTs * 1000,
	    needsControl: taskDto.needsControl,
	    startPlanTs: taskDto.startPlanTs * 1000,
	    endPlanTs: taskDto.endPlanTs * 1000,
	    fileIds: taskDto.fileIds,
	    checklist: (_taskDto$checklist = taskDto.checklist) != null ? _taskDto$checklist : [],
	    containsChecklist: taskDto.containsChecklist,
	    parentId: (_taskDto$parentId = taskDto.parentId) != null ? _taskDto$parentId : 0,
	    containsSubTasks: taskDto.containsSubTasks,
	    containsRelatedTasks: taskDto.containsRelatedTasks,
	    groupId: (_taskDto$group = taskDto.group) == null ? void 0 : _taskDto$group.id,
	    stageId: (_taskDto$stage$id = (_taskDto$stage = taskDto.stage) == null ? void 0 : _taskDto$stage.id) != null ? _taskDto$stage$id : 0,
	    flowId: (_taskDto$flow = taskDto.flow) == null ? void 0 : _taskDto$flow.id,
	    status: taskDto.status,
	    statusChangedTs: taskDto.statusChangedTs * 1000,
	    accomplicesIds: (_taskDto$accomplices$ = (_taskDto$accomplices = taskDto.accomplices) == null ? void 0 : _taskDto$accomplices.map(({
	      id
	    }) => id)) != null ? _taskDto$accomplices$ : [],
	    auditorsIds: (_taskDto$auditors$map = (_taskDto$auditors = taskDto.auditors) == null ? void 0 : _taskDto$auditors.map(({
	      id
	    }) => id)) != null ? _taskDto$auditors$map : [],
	    chatId: taskDto.chatId,
	    crmItemIds: prepareValue(taskDto.crmItemIds),
	    allowsChangeDeadline: prepareValue(taskDto.allowsChangeDeadline),
	    allowsChangeDatePlan: prepareValue(taskDto.allowsChangeDatePlan),
	    matchesWorkTime: prepareValue(taskDto.matchesWorkTime),
	    matchesSubTasksTime: prepareValue(taskDto.matchesSubTasksTime),
	    rights: prepareValue(taskDto.rights),
	    tags: prepareValue(taskDto.tags, (_taskDto$tags = taskDto.tags) == null ? void 0 : _taskDto$tags.map(({
	      name
	    }) => name)),
	    inFavorite: taskDto.inFavorite || [],
	    inMute: taskDto.inMute || [],
	    archiveLink: prepareValue(taskDto.archiveLink)
	  };
	  return Object.fromEntries(Object.entries(task).filter(([, value]) => value));
	}
	function mapModelToSliderData(task, checkLists) {
	  var _task$fileIds;
	  const accomplices = tasks_v2_provider_service_checkListService.CheckListMappers.getUserIdsFromChecklists(checkLists, 'accomplices');
	  const auditors = tasks_v2_provider_service_checkListService.CheckListMappers.getUserIdsFromChecklists(checkLists, 'auditors');
	  const data = {
	    TITLE: prepareValue(task.title),
	    DESCRIPTION: prepareValue(task.description, mapDescription(task.description)),
	    RESPONSIBLE_ID: prepareValue(task.responsibleId),
	    GROUP_ID: prepareValue(task.groupId),
	    DEADLINE_TS: prepareValue(task.deadlineTs, Math.floor(task.deadlineTs / 1000)),
	    IS_IMPORTANT: prepareValue(task.isImportant, task.isImportant ? 'Y' : null),
	    FILE_IDS: prepareValue(task.fileIds, ((_task$fileIds = task.fileIds) == null ? void 0 : _task$fileIds.length) > 0 ? task.fileIds : null),
	    CHECKLIST: tasks_v2_provider_service_checkListService.CheckListMappers.mapModelToSliderData(checkLists),
	    ACCOMPLICES: prepareValue(accomplices, accomplices.length > 0 ? accomplices : null),
	    AUDITORS: prepareValue(auditors, auditors.length > 0 ? auditors : null)
	  };
	  return Object.fromEntries(Object.entries(data).filter(([, value]) => value));
	}
	function prepareValue(value, mappedValue = value) {
	  return main_core.Type.isUndefined(value) ? undefined : mappedValue;
	}

	// TODO: Temporary. Remove when removing old full card
	function mapDescription(description) {
	  return description == null ? void 0 : description.replaceAll(/\[p]\n|\[p]\[\/p]|\[\/p]/gi, '').trim();
	}
	function mapTags(tags) {
	  return tags.map(name => ({
	    name
	  }));
	}

	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	class TaskGetExtractor {
	  constructor(data) {
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = data;
	  }
	  getTask() {
	    return mapDtoToModel(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]);
	  }
	  getFlow() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].flow ? tasks_v2_provider_service_flowService.FlowMappers.mapDtoToModel(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].flow) : null;
	  }
	  getGroup() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].group ? tasks_v2_provider_service_groupService.GroupMappers.mapDtoToModel(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].group) : null;
	  }
	  getStages() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].stage ? [tasks_v2_provider_service_groupService.GroupMappers.mapStageDtoToModel(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].stage)] : [];
	  }
	  getUsers() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6;
	    return [(_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].creator) != null ? _babelHelpers$classPr : [], (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].responsible) != null ? _babelHelpers$classPr2 : [], ...((_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) == null ? void 0 : _babelHelpers$classPr4.accomplices) != null ? _babelHelpers$classPr3 : []), ...((_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) == null ? void 0 : _babelHelpers$classPr6.auditors) != null ? _babelHelpers$classPr5 : [])].map(userDto => tasks_v2_provider_service_userService.UserMappers.mapDtoToModel(userDto));
	  }
	}

	var _updateFields, _updateTaskBefore, _updatePromises, _updateServerTaskDebounced, _updateTaskDebounced, _updateTask, _updateTaskFields, _updateScrumFields, _updateDeadlineFields, _updateDatePlanFields, _getTaskFields, _getFilteredFields, _hasChanges, _insertStoreTask, _deleteStoreTask;
	const separateFields = {
	  scrumFields: new Set(['storyPoints', 'epicId']),
	  deadlineFields: new Set(['deadlineTs']),
	  datePlanFields: new Set(['startPlanTs', 'endPlanTs', 'matchesWorkTime', 'matchesSubTasksTime'])
	};
	const taskService = new (_updateFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFields"), _updateTaskBefore = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTaskBefore"), _updatePromises = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updatePromises"), _updateServerTaskDebounced = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateServerTaskDebounced"), _updateTaskDebounced = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTaskDebounced"), _updateTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTask"), _updateTaskFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTaskFields"), _updateScrumFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateScrumFields"), _updateDeadlineFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateDeadlineFields"), _updateDatePlanFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateDatePlanFields"), _getTaskFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTaskFields"), _getFilteredFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFilteredFields"), _hasChanges = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hasChanges"), _insertStoreTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("insertStoreTask"), _deleteStoreTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deleteStoreTask"), class {
	  constructor() {
	    Object.defineProperty(this, _deleteStoreTask, {
	      value: _deleteStoreTask2
	    });
	    Object.defineProperty(this, _insertStoreTask, {
	      value: _insertStoreTask2
	    });
	    Object.defineProperty(this, _hasChanges, {
	      value: _hasChanges2
	    });
	    Object.defineProperty(this, _getFilteredFields, {
	      value: _getFilteredFields2
	    });
	    Object.defineProperty(this, _getTaskFields, {
	      value: _getTaskFields2
	    });
	    Object.defineProperty(this, _updateDatePlanFields, {
	      value: _updateDatePlanFields2
	    });
	    Object.defineProperty(this, _updateDeadlineFields, {
	      value: _updateDeadlineFields2
	    });
	    Object.defineProperty(this, _updateScrumFields, {
	      value: _updateScrumFields2
	    });
	    Object.defineProperty(this, _updateTaskFields, {
	      value: _updateTaskFields2
	    });
	    Object.defineProperty(this, _updateTask, {
	      value: _updateTask2
	    });
	    Object.defineProperty(this, _updateTaskDebounced, {
	      value: _updateTaskDebounced2
	    });
	    Object.defineProperty(this, _updateFields, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _updateTaskBefore, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _updatePromises, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _updateServerTaskDebounced, {
	      writable: true,
	      value: {}
	    });
	  }
	  async getById(id) {
	    try {
	      const data = await tasks_v2_lib_apiClient.apiClient.post('Task.get', {
	        task: {
	          id
	        }
	      });
	      await this.extractTask(data);
	      await this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/removePartiallyLoaded`, id);
	    } catch (error) {
	      console.error('TaskService: getById error', error);
	    }
	  }
	  async getRights(id) {
	    try {
	      const {
	        rights
	      } = await tasks_v2_lib_apiClient.apiClient.post('Task.Access.get', {
	        task: {
	          id
	        }
	      });
	      await this.updateStoreTask(id, {
	        rights
	      });
	    } catch (error) {
	      console.error('TaskService: getRights error', error);
	    }
	  }
	  async add(task) {
	    try {
	      const data = await tasks_v2_lib_apiClient.apiClient.post('Task.add', {
	        task: mapModelToDto(task)
	      });
	      await this.extractTask(data);
	      main_core_events.EventEmitter.emit(tasks_v2_const.EventName.TaskAdd, {
	        ...new TaskGetExtractor(data).getTask(),
	        relatedToTaskId: task.relatedToTaskId
	      });
	      if (task.containsRelatedTasks) {
	        void tasks_v2_provider_service_relationService.relatedTasksService.list(data.id, true);
	      }
	      if (task.relatedToTaskId) {
	        void tasks_v2_provider_service_relationService.relatedTasksService.add(task.relatedToTaskId, [data.id]);
	      }
	      if (task.parentId) {
	        tasks_v2_provider_service_relationService.subTasksService.addStore(task.parentId, [data.id]);
	      }
	      return [data.id, null];
	    } catch (error) {
	      var _error$errors, _error$errors$;
	      console.error('TaskService: add error', error);
	      return [0, new Error((_error$errors = error.errors) == null ? void 0 : (_error$errors$ = _error$errors[0]) == null ? void 0 : _error$errors$.message)];
	    }
	  }
	  async update(id, fields) {
	    const taskBeforeUpdate = this.getStoreTask(id);
	    await this.updateStoreTask(id, fields);
	    if (!this.isRealId(id)) {
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _hasChanges)[_hasChanges](taskBeforeUpdate, fields)) {
	      main_core_events.EventEmitter.emit(tasks_v2_const.EventName.TaskUpdate, {
	        id,
	        ...fields
	      });
	    }
	    try {
	      await babelHelpers.classPrivateFieldLooseBase(this, _updateTaskDebounced)[_updateTaskDebounced](id, fields, taskBeforeUpdate);
	    } catch (error) {
	      await this.updateStoreTask(id, taskBeforeUpdate);
	      console.error('TaskService: update error', error);
	    }
	  }
	  async delete(id) {
	    const taskBeforeDelete = this.getStoreTask(id);
	    await babelHelpers.classPrivateFieldLooseBase(this, _deleteStoreTask)[_deleteStoreTask](id);
	    if (!this.isRealId(id)) {
	      return;
	    }
	    try {
	      await tasks_v2_lib_apiClient.apiClient.post('Task.delete', {
	        task: {
	          id
	        }
	      });
	    } catch (error) {
	      void babelHelpers.classPrivateFieldLooseBase(this, _insertStoreTask)[_insertStoreTask](taskBeforeDelete);
	      console.error('TaskService: delete error', error);
	    }
	  }
	  async extractTask(data) {
	    const extractor = new TaskGetExtractor(data);
	    await Promise.all([this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/upsert`, extractor.getTask()), this.$store.dispatch(`${tasks_v2_const.Model.Flows}/upsert`, extractor.getFlow()), this.$store.dispatch(`${tasks_v2_const.Model.Groups}/insert`, extractor.getGroup()), this.$store.dispatch(`${tasks_v2_const.Model.Stages}/upsertMany`, extractor.getStages()), this.$store.dispatch(`${tasks_v2_const.Model.Users}/upsertMany`, extractor.getUsers())]);
	    await tasks_v2_provider_service_fileService.fileService.get(data.id).sync(data.fileIds);
	  }
	  isRealId(id) {
	    return Number.isInteger(id) && id > 0;
	  }
	  async updateStoreTask(id, fields) {
	    if (this.hasStoreTask(id)) {
	      await this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	        id,
	        fields
	      });
	    }
	  }
	  hasStoreTask(id) {
	    return this.getStoreTask(id) !== null;
	  }
	  getStoreTask(id) {
	    const task = this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](id);
	    return task ? {
	      ...task
	    } : null;
	  }
	  async addFavorite(task, userId) {
	    const favoriteIdsCurrent = [...this.$store.state.tasks.collection[task.id].inFavorite];
	    await this.updateStoreTask(task.id, {
	      inFavorite: [...favoriteIdsCurrent, userId]
	    });
	    try {
	      const response = await tasks_v2_lib_apiClient.apiClient.post('Task.Favorite.add', {
	        task: {
	          id: task.id
	        }
	      });
	      const isSuccess = response === true;
	      if (isSuccess) {
	        console.log('REQUEST SUCCESS');
	      } else {
	        await this.updateStoreTask(task.id, {
	          inFavorite: favoriteIdsCurrent
	        });
	      }
	      return isSuccess;
	    } catch (error) {
	      console.error('TaskService error', error);
	      await this.updateStoreTask(task.id, {
	        inFavorite: favoriteIdsCurrent
	      });
	      return false;
	    }
	  }
	  async removeFavorite(task, userId) {
	    const favoriteIdsCurrent = [...this.$store.state.tasks.collection[task.id].inFavorite];
	    await this.updateStoreTask(task.id, {
	      inFavorite: favoriteIdsCurrent.filter(favoriteId => String(favoriteId) !== String(userId))
	    });
	    try {
	      const response = await tasks_v2_lib_apiClient.apiClient.post('Task.Favorite.delete', {
	        task: {
	          id: task.id
	        }
	      });
	      const isSuccess = response === true;
	      if (isSuccess) {
	        console.log('REQUEST SUCCESS');
	      } else {
	        await this.updateStoreTask(task.id, {
	          inFavorite: favoriteIdsCurrent
	        });
	      }
	      return isSuccess;
	    } catch (error) {
	      console.error('TaskService error', error);
	      await this.updateStoreTask(task.id, {
	        inFavorite: favoriteIdsCurrent
	      });
	      return false;
	    }
	  }
	  async muteTask(task, userId) {
	    const muteIdsCurrent = [...this.$store.state.tasks.collection[task.id].inMute];
	    await this.updateStoreTask(task.id, {
	      inMute: [...muteIdsCurrent, userId]
	    });
	    try {
	      const response = await tasks_v2_lib_apiClient.apiClient.post('Task.Attention.mute', {
	        task: {
	          id: task.id
	        }
	      });
	      const isSuccess = response === true;
	      if (isSuccess) {
	        console.log('REQUEST SUCCESS');
	      } else {
	        await this.updateStoreTask(task.id, {
	          inMute: muteIdsCurrent
	        });
	      }
	      return isSuccess;
	    } catch (error) {
	      console.error('TaskService error', error);
	      await this.updateStoreTask(task.id, {
	        inMute: muteIdsCurrent
	      });
	      return false;
	    }
	  }
	  async unmuteTask(task, userId) {
	    const muteIdsCurrent = [...this.$store.state.tasks.collection[task.id].inMute];
	    await this.updateStoreTask(task.id, {
	      inMute: muteIdsCurrent.filter(muteId => String(muteId) !== String(userId))
	    });
	    try {
	      const response = await tasks_v2_lib_apiClient.apiClient.post('Task.Attention.unmute', {
	        task: {
	          id: task.id
	        }
	      });
	      const isSuccess = response === true;
	      if (isSuccess) {
	        console.log('REQUEST SUCCESS');
	      } else {
	        await this.updateStoreTask(task.id, {
	          inMute: muteIdsCurrent
	        });
	      }
	      return isSuccess;
	    } catch (error) {
	      console.error('TaskService error', error);
	      await this.updateStoreTask(task.id, {
	        inMute: muteIdsCurrent
	      });
	      return false;
	    }
	  }
	  async setAuditors(taskId, auditorsIds) {
	    const auditorsIdsCurrent = [...this.$store.state.tasks.collection[taskId].auditorsIds];
	    const filledFieldsCurrent = {
	      ...this.$store.state.tasks.collection[taskId].filledFields
	    };
	    await this.updateStoreTask(taskId, {
	      filledFields: {
	        ...filledFieldsCurrent,
	        auditorsIds: true
	      },
	      auditorsIds: [...auditorsIds]
	    });
	    const auditorsNew = auditorsIds.map(auditorId => ({
	      id: auditorId
	    }));
	    const taskNew = {
	      id: taskId,
	      auditors: auditorsNew
	    };
	    try {
	      const response = await tasks_v2_lib_apiClient.apiClient.post('Task.Stakeholder.Auditor.set', {
	        task: taskNew
	      });
	      const isSuccess = Boolean(response);
	      if (isSuccess) {
	        await this.updateStoreTask(taskId, {
	          auditorsIds: response.auditors.map(auditor => auditor.id)
	        });
	      } else {
	        await this.updateStoreTask(taskId, {
	          auditorsIds: [...auditorsIdsCurrent]
	        });
	      }
	      return isSuccess;
	    } catch (error) {
	      console.error('TaskService error', error);
	      await this.updateStoreTask(taskId, {
	        auditorsIds: [...auditorsIdsCurrent]
	      });
	      return false;
	    }
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	})();
	async function _updateTaskDebounced2(id, fields, taskBeforeUpdate) {
	  var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6;
	  babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields][id] = {
	    ...babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields][id],
	    ...fields
	  };
	  (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _updateTaskBefore)[_updateTaskBefore])[id]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[id] = taskBeforeUpdate;
	  (_babelHelpers$classPr4 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises])[id]) != null ? _babelHelpers$classPr4 : _babelHelpers$classPr3[id] = new Resolvable();
	  (_babelHelpers$classPr6 = (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _updateServerTaskDebounced)[_updateServerTaskDebounced])[id]) != null ? _babelHelpers$classPr6 : _babelHelpers$classPr5[id] = main_core.Runtime.debounce(babelHelpers.classPrivateFieldLooseBase(this, _updateTask)[_updateTask], 500, this);
	  babelHelpers.classPrivateFieldLooseBase(this, _updateServerTaskDebounced)[_updateServerTaskDebounced][id](id);
	  await babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises][id];
	}
	async function _updateTask2(id) {
	  const fields = babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields][id];
	  delete babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields][id];
	  const taskBeforeUpdate = babelHelpers.classPrivateFieldLooseBase(this, _updateTaskBefore)[_updateTaskBefore][id];
	  delete babelHelpers.classPrivateFieldLooseBase(this, _updateTaskBefore)[_updateTaskBefore][id];
	  const promise = babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises][id];
	  delete babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises][id];
	  await babelHelpers.classPrivateFieldLooseBase(this, _updateTaskFields)[_updateTaskFields](id, fields, taskBeforeUpdate);
	  await babelHelpers.classPrivateFieldLooseBase(this, _updateScrumFields)[_updateScrumFields](id, fields, taskBeforeUpdate);
	  await babelHelpers.classPrivateFieldLooseBase(this, _updateDeadlineFields)[_updateDeadlineFields](id, fields, taskBeforeUpdate);
	  await babelHelpers.classPrivateFieldLooseBase(this, _updateDatePlanFields)[_updateDatePlanFields](id, fields, taskBeforeUpdate);
	  promise.resolve();
	}
	async function _updateTaskFields2(id, fields, taskBeforeUpdate) {
	  const taskFields = babelHelpers.classPrivateFieldLooseBase(this, _getTaskFields)[_getTaskFields](fields);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hasChanges)[_hasChanges](taskBeforeUpdate, taskFields)) {
	    const data = await tasks_v2_lib_apiClient.apiClient.post('Task.update', {
	      task: mapModelToDto({
	        id,
	        ...taskFields
	      })
	    });
	    await this.extractTask(data);
	  }
	}
	async function _updateScrumFields2(id, fields, taskBeforeUpdate) {
	  const scrumFields = babelHelpers.classPrivateFieldLooseBase(this, _getFilteredFields)[_getFilteredFields](fields, separateFields.scrumFields);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hasChanges)[_hasChanges](taskBeforeUpdate, scrumFields)) {
	    await tasks_v2_lib_apiClient.apiClient.post('Scrum.updateTask', {
	      taskId: id,
	      fields: scrumFields
	    });
	  }
	}
	async function _updateDeadlineFields2(id, fields, taskBeforeUpdate) {
	  const deadlineFields = babelHelpers.classPrivateFieldLooseBase(this, _getFilteredFields)[_getFilteredFields](fields, separateFields.deadlineFields);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hasChanges)[_hasChanges](taskBeforeUpdate, deadlineFields)) {
	    await tasks_v2_lib_apiClient.apiClient.post('Task.Deadline.update', {
	      task: mapModelToDto({
	        id,
	        ...deadlineFields
	      })
	    });
	  }
	}
	async function _updateDatePlanFields2(id, fields, taskBeforeUpdate) {
	  const datePlanFields = babelHelpers.classPrivateFieldLooseBase(this, _getFilteredFields)[_getFilteredFields](fields, separateFields.datePlanFields);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hasChanges)[_hasChanges](taskBeforeUpdate, datePlanFields)) {
	    await tasks_v2_lib_apiClient.apiClient.post('Task.Plan.update', {
	      task: mapModelToDto({
	        id,
	        ...datePlanFields
	      })
	    });
	  }
	}
	function _getTaskFields2(task) {
	  return Object.fromEntries(Object.entries(task).filter(([field]) => {
	    return Object.values(separateFields).every(set => !set.has(field));
	  }));
	}
	function _getFilteredFields2(fields, filterSet) {
	  return Object.fromEntries(Object.entries(fields).filter(([field]) => filterSet.has(field)));
	}
	function _hasChanges2(task, fields) {
	  return Object.entries(fields).some(([field, value]) => JSON.stringify(task[field]) !== JSON.stringify(value));
	}
	async function _insertStoreTask2(task) {
	  await this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/insert`, task);
	}
	async function _deleteStoreTask2(id) {
	  await this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/delete`, id);
	}
	function Resolvable() {
	  const promise = new Promise(resolve => {
	    this.resolve = resolve;
	  });
	  promise.resolve = this.resolve;
	  return promise;
	}

	const TaskMappers = {
	  mapModelToDto,
	  mapDtoToModel,
	  mapModelToSliderData
	};

	exports.TaskMappers = TaskMappers;
	exports.taskService = taskService;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX.Tasks.V2.Provider.Service,BX,BX.Event,BX.Tasks.V2.Const,BX.Tasks.V2,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=task-service.bundle.js.map
