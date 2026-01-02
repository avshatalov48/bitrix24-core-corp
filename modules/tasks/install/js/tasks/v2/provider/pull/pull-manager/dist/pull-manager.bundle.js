/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,pull_queuemanager,main_core_events,tasks_v2_core,tasks_v2_provider_service_taskService,tasks_v2_provider_service_relationService,tasks_v2_provider_service_groupService,tasks_v2_provider_service_flowService,tasks_v2_provider_service_userService,main_core,tasks_v2_const) {
	'use strict';

	class BasePullHandler {
	  constructor() {
	    if (new.target === BasePullHandler) {
	      throw new TypeError('BasePullHandler: An abstract class cannot be instantiated');
	    }
	  }
	  getMap() {
	    return {};
	  }
	  getDelayedMap() {
	    return {};
	  }
	}

	function mapPushToModel({
	  AFTER: after,
	  TASK_ID: id
	}) {
	  var _after$STAGE_INFO$id, _after$STAGE_INFO;
	  const task = {
	    id,
	    title: prepareValue(after.TITLE),
	    isImportant: prepareValue(after.PRIORITY, after.PRIORITY === 2),
	    creatorId: prepareValue(after.CREATED_BY),
	    responsibleId: prepareValue(after.RESPONSIBLE_ID),
	    deadlineTs: prepareValue(after.DEADLINE, after.DEADLINE * 1000),
	    checklist: undefined,
	    groupId: prepareValue(after.GROUP_ID),
	    stageId: prepareValue(after.STAGE, (_after$STAGE_INFO$id = (_after$STAGE_INFO = after.STAGE_INFO) == null ? void 0 : _after$STAGE_INFO.id) != null ? _after$STAGE_INFO$id : 0),
	    flowId: prepareValue(after.FLOW_ID),
	    status: prepareValue(after.STATUS, mapStatus(after.STATUS)),
	    statusChangedTs: prepareValue(after.STATUS, Date.now()),
	    accomplicesIds: prepareValue(after.ACCOMPLICES, mapUserIds(after.ACCOMPLICES)),
	    auditorsIds: prepareValue(after.AUDITORS, mapUserIds(after.AUDITORS)),
	    parentId: prepareValue(after.PARENT_ID, Number(after.PARENT_ID) || 0),
	    tags: prepareValue(after.TAGS, after.TAGS ? after.TAGS.split(',') : [])
	  };
	  return Object.fromEntries(Object.entries(task).filter(([, value]) => !main_core.Type.isUndefined(value)));
	}
	function mapStatus(status) {
	  var _$3$4$5$6$status;
	  return (_$3$4$5$6$status = {
	    2: tasks_v2_const.TaskStatus.Pending,
	    3: tasks_v2_const.TaskStatus.InProgress,
	    4: tasks_v2_const.TaskStatus.SupposedlyCompleted,
	    5: tasks_v2_const.TaskStatus.Completed,
	    6: tasks_v2_const.TaskStatus.Deferred
	  }[status]) != null ? _$3$4$5$6$status : tasks_v2_const.TaskStatus.Pending;
	}
	function mapUserIds(users) {
	  if (!users) {
	    return [];
	  }
	  return users.split(',').map(id => Number(id));
	}
	function prepareValue(value, mappedValue = value) {
	  return main_core.Type.isUndefined(value) ? undefined : mappedValue;
	}

	var _handleTaskAdded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleTaskAdded");
	var _handleTaskUpdated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleTaskUpdated");
	var _handleTaskUpdatedDelayed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleTaskUpdatedDelayed");
	var _handleTaskViewed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleTaskViewed");
	var _handleTaskDeleted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleTaskDeleted");
	var _handleDefaultDeadlineChanged = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleDefaultDeadlineChanged");
	var _upsertStage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("upsertStage");
	var _loadTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadTask");
	var _needToLoadTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needToLoadTask");
	var _loadGroup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadGroup");
	var _needToLoadGroup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needToLoadGroup");
	var _loadFlow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFlow");
	var _needToLoadFlow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needToLoadFlow");
	var _loadUsers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadUsers");
	var _needToLoadUsers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needToLoadUsers");
	var _getUsersIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUsersIds");
	var _loadRights = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadRights");
	var _currentUserId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentUserId");
	class TaskPullHandler extends BasePullHandler {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _currentUserId, {
	      get: _get_currentUserId,
	      set: void 0
	    });
	    Object.defineProperty(this, _loadRights, {
	      value: _loadRights2
	    });
	    Object.defineProperty(this, _getUsersIds, {
	      value: _getUsersIds2
	    });
	    Object.defineProperty(this, _needToLoadUsers, {
	      value: _needToLoadUsers2
	    });
	    Object.defineProperty(this, _loadUsers, {
	      value: _loadUsers2
	    });
	    Object.defineProperty(this, _needToLoadFlow, {
	      value: _needToLoadFlow2
	    });
	    Object.defineProperty(this, _loadFlow, {
	      value: _loadFlow2
	    });
	    Object.defineProperty(this, _needToLoadGroup, {
	      value: _needToLoadGroup2
	    });
	    Object.defineProperty(this, _loadGroup, {
	      value: _loadGroup2
	    });
	    Object.defineProperty(this, _needToLoadTask, {
	      value: _needToLoadTask2
	    });
	    Object.defineProperty(this, _loadTask, {
	      value: _loadTask2
	    });
	    Object.defineProperty(this, _upsertStage, {
	      value: _upsertStage2
	    });
	    Object.defineProperty(this, _handleTaskAdded, {
	      writable: true,
	      value: data => {
	        const features = tasks_v2_core.Core.getParams().features;

	        // show task created balloon if miniform feature is enabled
	        const showTaskAddedBalloon = data.AFTER.USER_ID === babelHelpers.classPrivateFieldLooseBase(this, _currentUserId)[_currentUserId] && features.isMiniformEnabled && !features.isV2Enabled;
	        if (showTaskAddedBalloon) {
	          var _data$AFTER$URL;
	          const url = (_data$AFTER$URL = data.AFTER.URL) != null ? _data$AFTER$URL : '';
	          BX.UI.Notification.Center.notify({
	            id: main_core.Text.getRandom(),
	            content: main_core.Loc.getMessage('TASKS_V2_NOTIFY_TASK_CREATED'),
	            actions: [{
	              title: main_core.Loc.getMessage('TASKS_V2_NOTIFY_TASK_DO_VIEW'),
	              events: {
	                click: (event, balloon) => {
	                  balloon.close();
	                  BX.SidePanel.Instance.open(url);
	                }
	              }
	            }]
	          });
	        }
	      }
	    });
	    Object.defineProperty(this, _handleTaskUpdated, {
	      writable: true,
	      value: data => {
	        babelHelpers.classPrivateFieldLooseBase(this, _upsertStage)[_upsertStage](data.AFTER.STAGE_INFO);
	        const task = mapPushToModel(data);
	        if (data.BEFORE.PARENT_ID) {
	          tasks_v2_provider_service_relationService.subTasksService.deleteStore(data.BEFORE.PARENT_ID, [task.id]);
	        }
	        if (task.parentId) {
	          tasks_v2_provider_service_relationService.subTasksService.addStore(task.parentId, [task.id]);
	        }
	      }
	    });
	    Object.defineProperty(this, _handleTaskUpdatedDelayed, {
	      writable: true,
	      value: async data => {
	        const task = mapPushToModel(data);
	        if (babelHelpers.classPrivateFieldLooseBase(this, _needToLoadTask)[_needToLoadTask](data)) {
	          void babelHelpers.classPrivateFieldLooseBase(this, _loadTask)[_loadTask](task);
	        } else {
	          await Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _loadGroup)[_loadGroup](task), babelHelpers.classPrivateFieldLooseBase(this, _loadFlow)[_loadFlow](task), babelHelpers.classPrivateFieldLooseBase(this, _loadUsers)[_loadUsers](task), babelHelpers.classPrivateFieldLooseBase(this, _loadRights)[_loadRights](task)]);
	          const {
	            id,
	            ...fields
	          } = task;
	          void this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	            id,
	            fields
	          });
	        }
	      }
	    });
	    Object.defineProperty(this, _handleTaskViewed, {
	      writable: true,
	      value: data => {}
	    });
	    Object.defineProperty(this, _handleTaskDeleted, {
	      writable: true,
	      value: data => {
	        tasks_v2_provider_service_relationService.subTasksService.deleteStore(data.BEFORE.PARENT_ID, [data.TASK_ID]);
	        main_core_events.EventEmitter.emit(tasks_v2_const.EventName.CloseFullCard, {
	          taskId: data.TASK_ID
	        });
	        void this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/delete`, data.TASK_ID);
	      }
	    });
	    Object.defineProperty(this, _handleDefaultDeadlineChanged, {
	      writable: true,
	      value: data => {
	        void this.$store.dispatch(`${tasks_v2_const.Model.Interface}/updateDefaultDeadline`, {
	          defaultDeadlineDate: data.defaultDeadlineDate,
	          defaultDeadlineInSeconds: data.deadline
	        });
	      }
	    });
	  }
	  getMap() {
	    return {
	      task_add: babelHelpers.classPrivateFieldLooseBase(this, _handleTaskAdded)[_handleTaskAdded],
	      task_update: babelHelpers.classPrivateFieldLooseBase(this, _handleTaskUpdated)[_handleTaskUpdated],
	      task_view: babelHelpers.classPrivateFieldLooseBase(this, _handleTaskViewed)[_handleTaskViewed],
	      task_remove: babelHelpers.classPrivateFieldLooseBase(this, _handleTaskDeleted)[_handleTaskDeleted],
	      default_deadline_changed: babelHelpers.classPrivateFieldLooseBase(this, _handleDefaultDeadlineChanged)[_handleDefaultDeadlineChanged]
	    };
	  }
	  getDelayedMap() {
	    return {
	      task_update: babelHelpers.classPrivateFieldLooseBase(this, _handleTaskUpdatedDelayed)[_handleTaskUpdatedDelayed]
	    };
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	}
	function _upsertStage2(stageDto) {
	  if (stageDto) {
	    const stage = tasks_v2_provider_service_groupService.GroupMappers.mapStageDtoToModel(stageDto);
	    void this.$store.dispatch(`${tasks_v2_const.Model.Stages}/upsert`, stage);
	  }
	}
	function _loadTask2(task) {
	  void tasks_v2_provider_service_taskService.taskService.getById(task.id);
	}
	function _needToLoadTask2(data) {
	  const notPushableFields = new Set(['DESCRIPTION', 'UF_TASK_WEBDAV_FILES']);
	  return Object.keys(data.AFTER).some(field => notPushableFields.has(field));
	}
	async function _loadGroup2(task) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _needToLoadGroup)[_needToLoadGroup](task)) {
	    await tasks_v2_provider_service_groupService.groupService.getGroup(task.groupId);
	  }
	}
	function _needToLoadGroup2(task) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _needToLoadFlow)[_needToLoadFlow](task)) {
	    return false;
	  }
	  return task.groupId && !this.$store.getters[`${tasks_v2_const.Model.Groups}/getById`](task.groupId);
	}
	async function _loadFlow2(task) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _needToLoadFlow)[_needToLoadFlow](task)) {
	    await tasks_v2_provider_service_flowService.flowService.getFlow(task.flowId);
	  }
	}
	function _needToLoadFlow2(task) {
	  return Boolean(task.flowId) && !this.$store.getters[`${tasks_v2_const.Model.Flows}/getById`](task.flowId);
	}
	async function _loadUsers2(task) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _needToLoadUsers)[_needToLoadUsers](task)) {
	    await tasks_v2_provider_service_userService.userService.list(babelHelpers.classPrivateFieldLooseBase(this, _getUsersIds)[_getUsersIds](task));
	  }
	}
	function _needToLoadUsers2(task) {
	  return !tasks_v2_provider_service_userService.userService.hasUsers(babelHelpers.classPrivateFieldLooseBase(this, _getUsersIds)[_getUsersIds](task));
	}
	function _getUsersIds2(task) {
	  var _task$accomplicesIds, _task$auditorsIds;
	  return [task.creatorId, task.responsibleId, ...((_task$accomplicesIds = task.accomplicesIds) != null ? _task$accomplicesIds : []), ...((_task$auditorsIds = task.auditorsIds) != null ? _task$auditorsIds : [])].filter(id => id);
	}
	async function _loadRights2(task) {
	  await tasks_v2_provider_service_taskService.taskService.getRights(task.id);
	}
	function _get_currentUserId() {
	  return this.$store.getters[`${tasks_v2_const.Model.Interface}/currentUserId`];
	}

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _loadItemsDelay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadItemsDelay");
	var _handlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlers");
	var _onBeforePull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforePull");
	var _onPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPull");
	var _onBeforeQueueExecute = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeQueueExecute");
	var _onQueueExecute = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onQueueExecute");
	var _onReload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReload");
	var _executeQueue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeQueue");
	class PullManager {
	  constructor(_params2) {
	    Object.defineProperty(this, _executeQueue, {
	      value: _executeQueue2
	    });
	    Object.defineProperty(this, _onReload, {
	      value: _onReload2
	    });
	    Object.defineProperty(this, _onQueueExecute, {
	      value: _onQueueExecute2
	    });
	    Object.defineProperty(this, _onBeforeQueueExecute, {
	      value: _onBeforeQueueExecute2
	    });
	    Object.defineProperty(this, _onPull, {
	      value: _onPull2
	    });
	    Object.defineProperty(this, _onBeforePull, {
	      value: _onBeforePull2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loadItemsDelay, {
	      writable: true,
	      value: 500
	    });
	    Object.defineProperty(this, _handlers, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = _params2;
	    babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers] = new Set([new TaskPullHandler()]);
	  }
	  initQueueManager() {
	    return new pull_queuemanager.QueueManager({
	      moduleId: tasks_v2_const.Module.Tasks,
	      userId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId,
	      config: {
	        loadItemsDelay: babelHelpers.classPrivateFieldLooseBase(this, _loadItemsDelay)[_loadItemsDelay]
	      },
	      additionalData: {},
	      events: {
	        onBeforePull: baseEvent => {
	          babelHelpers.classPrivateFieldLooseBase(this, _onBeforePull)[_onBeforePull](baseEvent);
	        },
	        onPull: baseEvent => {
	          babelHelpers.classPrivateFieldLooseBase(this, _onPull)[_onPull](baseEvent);
	        }
	      },
	      callbacks: {
	        onBeforeQueueExecute: items => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _onBeforeQueueExecute)[_onBeforeQueueExecute](items);
	        },
	        onQueueExecute: items => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _onQueueExecute)[_onQueueExecute](items);
	        },
	        onReload: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _onReload)[_onReload]();
	        }
	      }
	    });
	  }
	}
	function _onBeforePull2(baseEvent) {
	  const {
	    pullData: {
	      command,
	      params
	    }
	  } = baseEvent.data;
	  for (const handler of babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers]) {
	    var _handler$getMap$comma, _handler$getMap;
	    (_handler$getMap$comma = (_handler$getMap = handler.getMap())[command]) == null ? void 0 : _handler$getMap$comma.call(_handler$getMap, params);
	  }
	}
	function _onPull2(baseEvent) {
	  const {
	    pullData: {
	      command,
	      params
	    },
	    promises
	  } = baseEvent.data;
	  for (const handler of babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers]) {
	    if (handler.getDelayedMap()[command]) {
	      var _params$entityId;
	      promises.push(Promise.resolve({
	        data: {
	          id: (_params$entityId = params.entityId) != null ? _params$entityId : main_core.Text.getRandom(),
	          command,
	          params
	        }
	      }));
	    }
	  }
	}
	function _onBeforeQueueExecute2(items) {
	  return Promise.resolve();
	}
	async function _onQueueExecute2(items) {
	  await babelHelpers.classPrivateFieldLooseBase(this, _executeQueue)[_executeQueue](items);
	}
	function _onReload2(event) {}
	function _executeQueue2(items) {
	  return new Promise(resolve => {
	    items.forEach(item => {
	      const {
	        data: {
	          command,
	          params
	        }
	      } = item;
	      for (const handler of babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers]) {
	        var _handler$getDelayedMa, _handler$getDelayedMa2;
	        (_handler$getDelayedMa = (_handler$getDelayedMa2 = handler.getDelayedMap())[command]) == null ? void 0 : _handler$getDelayedMa.call(_handler$getDelayedMa2, params);
	      }
	    });
	    resolve();
	  });
	}

	exports.PullManager = PullManager;

}((this.BX.Tasks.V2.Provider.Pull = this.BX.Tasks.V2.Provider.Pull || {}),BX.Pull,BX.Event,BX.Tasks.V2,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Provider.Service,BX,BX.Tasks.V2.Const));
//# sourceMappingURL=pull-manager.bundle.js.map
