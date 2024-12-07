this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,tasks_flow_editForm,ui_dialogs_messagebox,ui_infoHelper,pull_queuemanager,tasks_flow_teamPopup,tasks_flow_taskQueue,tasks_clue,ui_manual,main_core_events,main_core) {
	'use strict';

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _grid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("grid");
	var _clueMyTasks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clueMyTasks");
	var _rowIdForMyTasksAhaMoment = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rowIdForMyTasksAhaMoment");
	var _notificationList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("notificationList");
	var _addedFlowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addedFlowId");
	var _reload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("reload");
	var _updateRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateRow");
	var _removeRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeRow");
	var _isRowExist = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRowExist");
	var _isFirstPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFirstPage");
	var _getRowById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRowById");
	var _getFirstRowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFirstRowId");
	var _getCell = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCell");
	var _subscribeToPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToPull");
	var _subscribeToGridEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToGridEvents");
	var _onBeforePull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforePull");
	var _onPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPull");
	var _onBeforeQueueExecute = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeQueueExecute");
	var _onQueueExecute = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onQueueExecute");
	var _getMapIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMapIds");
	var _onReload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReload");
	var _executeQueue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeQueue");
	var _commentReadAll = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commentReadAll");
	var _onFlowAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFlowAdd");
	var _onFlowUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFlowUpdate");
	var _onFlowDelete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFlowDelete");
	var _afterRowUpdated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("afterRowUpdated");
	var _recognizeFlowId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("recognizeFlowId");
	var _recognizeTaskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("recognizeTaskId");
	var _getEntityIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEntityIds");
	var _identifyFlowItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("identifyFlowItems");
	var _identifyTaskItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("identifyTaskItems");
	var _convertTaskItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("convertTaskItems");
	var _uniqueItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uniqueItems");
	var _findTaskAddAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("findTaskAddAction");
	var _findTaskRemoveAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("findTaskRemoveAction");
	var _addTaskRemoveItemToMap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addTaskRemoveItemToMap");
	var _isCurrentUserCreatorOfTheTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCurrentUserCreatorOfTheTask");
	var _showAhaOnMyTasksColumn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showAhaOnMyTasksColumn");
	var _getBindElementForAhaOnCell = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBindElementForAhaOnCell");
	var _consoleError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("consoleError");
	var _clearAnalyticsParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearAnalyticsParams");
	var _activateHint = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activateHint");
	var _highlightAddedFlow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightAddedFlow");
	var _showFlowCreationWizard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showFlowCreationWizard");
	class Grid {
	  constructor(_params2) {
	    Object.defineProperty(this, _showFlowCreationWizard, {
	      value: _showFlowCreationWizard2
	    });
	    Object.defineProperty(this, _highlightAddedFlow, {
	      value: _highlightAddedFlow2
	    });
	    Object.defineProperty(this, _activateHint, {
	      value: _activateHint2
	    });
	    Object.defineProperty(this, _clearAnalyticsParams, {
	      value: _clearAnalyticsParams2
	    });
	    Object.defineProperty(this, _consoleError, {
	      value: _consoleError2
	    });
	    Object.defineProperty(this, _getBindElementForAhaOnCell, {
	      value: _getBindElementForAhaOnCell2
	    });
	    Object.defineProperty(this, _showAhaOnMyTasksColumn, {
	      value: _showAhaOnMyTasksColumn2
	    });
	    Object.defineProperty(this, _isCurrentUserCreatorOfTheTask, {
	      value: _isCurrentUserCreatorOfTheTask2
	    });
	    Object.defineProperty(this, _addTaskRemoveItemToMap, {
	      value: _addTaskRemoveItemToMap2
	    });
	    Object.defineProperty(this, _findTaskRemoveAction, {
	      value: _findTaskRemoveAction2
	    });
	    Object.defineProperty(this, _findTaskAddAction, {
	      value: _findTaskAddAction2
	    });
	    Object.defineProperty(this, _uniqueItems, {
	      value: _uniqueItems2
	    });
	    Object.defineProperty(this, _convertTaskItems, {
	      value: _convertTaskItems2
	    });
	    Object.defineProperty(this, _identifyTaskItems, {
	      value: _identifyTaskItems2
	    });
	    Object.defineProperty(this, _identifyFlowItems, {
	      value: _identifyFlowItems2
	    });
	    Object.defineProperty(this, _getEntityIds, {
	      value: _getEntityIds2
	    });
	    Object.defineProperty(this, _recognizeTaskId, {
	      value: _recognizeTaskId2
	    });
	    Object.defineProperty(this, _recognizeFlowId, {
	      value: _recognizeFlowId2
	    });
	    Object.defineProperty(this, _afterRowUpdated, {
	      value: _afterRowUpdated2
	    });
	    Object.defineProperty(this, _onFlowDelete, {
	      value: _onFlowDelete2
	    });
	    Object.defineProperty(this, _onFlowUpdate, {
	      value: _onFlowUpdate2
	    });
	    Object.defineProperty(this, _onFlowAdd, {
	      value: _onFlowAdd2
	    });
	    Object.defineProperty(this, _commentReadAll, {
	      value: _commentReadAll2
	    });
	    Object.defineProperty(this, _executeQueue, {
	      value: _executeQueue2
	    });
	    Object.defineProperty(this, _onReload, {
	      value: _onReload2
	    });
	    Object.defineProperty(this, _getMapIds, {
	      value: _getMapIds2
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
	    Object.defineProperty(this, _subscribeToGridEvents, {
	      value: _subscribeToGridEvents2
	    });
	    Object.defineProperty(this, _subscribeToPull, {
	      value: _subscribeToPull2
	    });
	    Object.defineProperty(this, _getCell, {
	      value: _getCell2
	    });
	    Object.defineProperty(this, _getFirstRowId, {
	      value: _getFirstRowId2
	    });
	    Object.defineProperty(this, _getRowById, {
	      value: _getRowById2
	    });
	    Object.defineProperty(this, _isFirstPage, {
	      value: _isFirstPage2
	    });
	    Object.defineProperty(this, _isRowExist, {
	      value: _isRowExist2
	    });
	    Object.defineProperty(this, _removeRow, {
	      value: _removeRow2
	    });
	    Object.defineProperty(this, _updateRow, {
	      value: _updateRow2
	    });
	    Object.defineProperty(this, _reload, {
	      value: _reload2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _clueMyTasks, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _rowIdForMyTasksAhaMoment, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _notificationList, {
	      writable: true,
	      value: new Set()
	    });
	    Object.defineProperty(this, _addedFlowId, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = _params2;
	    babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid] = BX.Main.gridManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].gridId).instance;
	    this.instantPullHandlers = {
	      comment_read_all: babelHelpers.classPrivateFieldLooseBase(this, _commentReadAll)[_commentReadAll],
	      flow_add: babelHelpers.classPrivateFieldLooseBase(this, _onFlowAdd)[_onFlowAdd],
	      flow_update: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      flow_delete: babelHelpers.classPrivateFieldLooseBase(this, _onFlowDelete)[_onFlowDelete]
	    };
	    this.delayedPullFlowHandlers = {};
	    this.delayedPullTasksHandlers = {
	      comment_add: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      task_add: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      task_update: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      task_view: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate],
	      task_remove: babelHelpers.classPrivateFieldLooseBase(this, _onFlowUpdate)[_onFlowUpdate]
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToPull)[_subscribeToPull]();
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToGridEvents)[_subscribeToGridEvents]();
	    babelHelpers.classPrivateFieldLooseBase(this, _clearAnalyticsParams)[_clearAnalyticsParams]();
	    babelHelpers.classPrivateFieldLooseBase(this, _activateHint)[_activateHint]();
	    babelHelpers.classPrivateFieldLooseBase(this, _showFlowCreationWizard)[_showFlowCreationWizard]();
	  }
	  activateFlow(flowId) {
	    // eslint-disable-next-line promise/catch-or-return
	    main_core.ajax.runAction('tasks.flow.Flow.activate', {
	      data: {
	        flowId
	      }
	    }).then(() => {});
	  }
	  removeFlow(flowId) {
	    const message = new ui_dialogs_messagebox.MessageBox({
	      message: main_core.Loc.getMessage('TASKS_FLOW_LIST_CONFIRM_REMOVE_MESSAGE'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('TASKS_FLOW_LIST_CONFIRM_REMOVE_BUTTON'),
	      popupOptions: {
	        id: `tasks-flow-remove-confirm-${flowId}`
	      },
	      onOk: () => {
	        message.close();
	        babelHelpers.classPrivateFieldLooseBase(this, _updateRow)[_updateRow](flowId, 'remove');
	      },
	      onCancel: () => {
	        message.close();
	      }
	    });
	    message.show();
	  }
	  showTeam(flowId, bindElement) {
	    tasks_flow_teamPopup.TeamPopup.showInstance({
	      flowId,
	      bindElement
	    });
	  }
	  showTaskQueue(flowId, type, bindElement) {
	    tasks_flow_taskQueue.TaskQueue.showInstance({
	      flowId,
	      type,
	      bindElement
	    });
	  }
	  showFlowLimit() {
	    ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	      code: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].flowLimitCode
	    }).show();
	  }
	  showNotificationHint(notificationId, textHint) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].has(notificationId)) {
	      BX.UI.Notification.Center.notify({
	        id: notificationId,
	        content: textHint,
	        width: 'auto'
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].add(notificationId);
	      main_core_events.EventEmitter.subscribeOnce('UI.Notification.Balloon:onClose', baseEvent => {
	        const closingBalloon = baseEvent.getTarget();
	        if (closingBalloon.getId() === notificationId) {
	          babelHelpers.classPrivateFieldLooseBase(this, _notificationList)[_notificationList].delete(notificationId);
	        }
	      });
	    }
	  }
	  showGuide(demoSuffix) {
	    ui_manual.Manual.show({
	      manualCode: 'flows',
	      urlParams: {
	        utm_source: 'portal',
	        utm_medium: 'referral'
	      },
	      analytics: {
	        tool: 'tasks',
	        category: 'flows',
	        event: 'flow_guide_view',
	        c_section: 'tasks',
	        c_sub_section: 'flows_grid',
	        c_element: 'guide_button',
	        p1: `isDemo_${demoSuffix}`
	      }
	    });
	  }
	}
	function _reload2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].reload(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUrl);
	}
	function _updateRow2(flowId, action) {
	  babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].updateRow(flowId, {
	    action,
	    currentPage: babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getCurrentPage()
	  }, babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUrl, babelHelpers.classPrivateFieldLooseBase(this, _afterRowUpdated)[_afterRowUpdated].bind(this));
	}
	function _removeRow2(rowId) {
	  babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].removeRow(rowId);
	}
	function _isRowExist2(rowId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getRowById)[_getRowById](rowId) !== null;
	}
	function _isFirstPage2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getCurrentPage() === 1;
	}
	function _getRowById2(rowId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getRows().getById(rowId);
	}
	function _getFirstRowId2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getRows().getFirst().getId();
	}
	function _getCell2(rowId, columnId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getRowById)[_getRowById](rowId).getCellById(columnId);
	}
	function _subscribeToPull2() {
	  new pull_queuemanager.QueueManager({
	    loadItemsDelay: 300,
	    moduleId: 'tasks',
	    userId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId,
	    additionalData: {},
	    events: {
	      onBeforePull: event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onBeforePull)[_onBeforePull](event);
	      },
	      onPull: event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onPull)[_onPull](event);
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
	function _subscribeToGridEvents2() {
	  main_core_events.EventEmitter.subscribe('Grid::updated', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _activateHint)[_activateHint]();
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightAddedFlow)[_highlightAddedFlow]();
	  });
	}
	function _onBeforePull2(event) {
	  const {
	    pullData: {
	      command,
	      params
	    }
	  } = event.data;
	  if (this.instantPullHandlers[command]) {
	    const flowId = babelHelpers.classPrivateFieldLooseBase(this, _recognizeFlowId)[_recognizeFlowId](params);
	    this.instantPullHandlers[command].apply(this, [params, flowId]);
	  }
	}
	function _onPull2(event) {
	  const {
	    pullData: {
	      command,
	      params
	    },
	    promises
	  } = event.data;
	  if (Object.keys(this.delayedPullFlowHandlers).includes(command)) {
	    const flowId = babelHelpers.classPrivateFieldLooseBase(this, _recognizeFlowId)[_recognizeFlowId](params);
	    if (flowId) {
	      promises.push(Promise.resolve({
	        data: {
	          id: flowId,
	          action: command,
	          actionParams: params
	        }
	      }));
	    }
	  }
	  if (Object.keys(this.delayedPullTasksHandlers).includes(command)) {
	    const taskId = babelHelpers.classPrivateFieldLooseBase(this, _recognizeTaskId)[_recognizeTaskId](params);
	    if (taskId) {
	      promises.push(Promise.resolve({
	        data: {
	          id: taskId,
	          action: command,
	          actionParams: params
	        }
	      }));
	    }
	  }
	}
	function _onBeforeQueueExecute2(items) {
	  return Promise.resolve();
	}
	async function _onQueueExecute2(items) {
	  const flowItems = babelHelpers.classPrivateFieldLooseBase(this, _identifyFlowItems)[_identifyFlowItems](items);
	  const taskItems = babelHelpers.classPrivateFieldLooseBase(this, _identifyTaskItems)[_identifyTaskItems](items);
	  if (taskItems.length === 0) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _executeQueue)[_executeQueue](flowItems, this.delayedPullFlowHandlers);
	  }
	  let mapIds = await babelHelpers.classPrivateFieldLooseBase(this, _getMapIds)[_getMapIds](babelHelpers.classPrivateFieldLooseBase(this, _getEntityIds)[_getEntityIds](taskItems));
	  const taskRemoveItem = babelHelpers.classPrivateFieldLooseBase(this, _findTaskRemoveAction)[_findTaskRemoveAction](taskItems);
	  if (taskRemoveItem) {
	    mapIds = babelHelpers.classPrivateFieldLooseBase(this, _addTaskRemoveItemToMap)[_addTaskRemoveItemToMap](taskRemoveItem, mapIds);
	  }
	  const convertedTaskItems = babelHelpers.classPrivateFieldLooseBase(this, _convertTaskItems)[_convertTaskItems](taskItems, mapIds);
	  const taskAddItem = babelHelpers.classPrivateFieldLooseBase(this, _findTaskAddAction)[_findTaskAddAction](convertedTaskItems);
	  if (taskAddItem && babelHelpers.classPrivateFieldLooseBase(this, _isCurrentUserCreatorOfTheTask)[_isCurrentUserCreatorOfTheTask](taskAddItem)) {
	    const {
	      data: {
	        id
	      }
	    } = taskAddItem;
	    babelHelpers.classPrivateFieldLooseBase(this, _rowIdForMyTasksAhaMoment)[_rowIdForMyTasksAhaMoment] = id;
	  }
	  const allItems = [...flowItems, ...convertedTaskItems];
	  return babelHelpers.classPrivateFieldLooseBase(this, _executeQueue)[_executeQueue](babelHelpers.classPrivateFieldLooseBase(this, _uniqueItems)[_uniqueItems](allItems), {
	    ...this.delayedPullFlowHandlers,
	    ...this.delayedPullTasksHandlers
	  });
	}
	function _getMapIds2(taskIds) {
	  return new Promise(resolve => {
	    // eslint-disable-next-line promise/catch-or-return
	    main_core.ajax.runComponentAction('bitrix:tasks.flow.list', 'getMapIds', {
	      mode: 'class',
	      data: {
	        taskIds
	      }
	    }).then(response => {
	      resolve(main_core.Type.isArray(response.data) ? {} : response.data);
	    }).catch(error => {
	      babelHelpers.classPrivateFieldLooseBase(this, _consoleError)[_consoleError]('getMapIds', error);
	    });
	  });
	}
	function _onReload2(event) {}
	function _executeQueue2(items, handlers) {
	  return new Promise((resolve, reject) => {
	    items.forEach(item => {
	      const {
	        data: {
	          action,
	          actionParams,
	          id
	        }
	      } = item;
	      if (handlers[action]) {
	        handlers[action].apply(this, [actionParams, id]);
	      }
	    });
	    resolve();
	  });
	}
	function _commentReadAll2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _reload)[_reload]();
	}
	function _onFlowAdd2(data, flowId) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](flowId)) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isFirstPage)[_isFirstPage]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId] = flowId;
	    babelHelpers.classPrivateFieldLooseBase(this, _reload)[_reload]();
	  }
	}
	function _onFlowUpdate2(data, flowId) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](flowId)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _updateRow)[_updateRow](flowId, 'update');
	}
	function _onFlowDelete2(data, flowId) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](flowId)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _removeRow)[_removeRow](flowId);
	}
	function _afterRowUpdated2(id, data, grid, response) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _rowIdForMyTasksAhaMoment)[_rowIdForMyTasksAhaMoment]) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks] && babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks].isShown()) {
	      const bindElement = babelHelpers.classPrivateFieldLooseBase(this, _getBindElementForAhaOnCell)[_getBindElementForAhaOnCell](babelHelpers.classPrivateFieldLooseBase(this, _rowIdForMyTasksAhaMoment)[_rowIdForMyTasksAhaMoment], 'MY_TASKS', '.tasks-flow__list-my-tasks span');
	      if (bindElement) {
	        babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks].adjustPosition(bindElement);
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks].close();
	      }
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isAhaShownOnMyTasksColumn === false && babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showAhaOnMyTasksColumn)[_showAhaOnMyTasksColumn](babelHelpers.classPrivateFieldLooseBase(this, _rowIdForMyTasksAhaMoment)[_rowIdForMyTasksAhaMoment]);
	    }
	  }
	}
	function _recognizeFlowId2(pullData) {
	  if ('FLOW_ID' in pullData) {
	    return parseInt(pullData.FLOW_ID, 10);
	  }
	  return 0;
	}
	function _recognizeTaskId2(pullData) {
	  if ('TASK_ID' in pullData) {
	    return parseInt(pullData.TASK_ID, 10);
	  }
	  if ('taskId' in pullData) {
	    return parseInt(pullData.taskId, 10);
	  }
	  if ('entityXmlId' in pullData && pullData.entityXmlId.indexOf('TASK_') === 0) {
	    return parseInt(pullData.entityXmlId.slice(5), 10);
	  }
	  return 0;
	}
	function _getEntityIds2(pullItems) {
	  const entityIds = [];
	  pullItems.forEach(item => {
	    const {
	      data: {
	        id
	      }
	    } = item;
	    entityIds.push(id);
	  });
	  return entityIds;
	}
	function _identifyFlowItems2(pullItems) {
	  return pullItems.filter(item => {
	    const {
	      data: {
	        action
	      }
	    } = item;
	    return Object.keys(this.delayedPullFlowHandlers).includes(action);
	  });
	}
	function _identifyTaskItems2(pullItems) {
	  return pullItems.filter(item => {
	    const {
	      data: {
	        action
	      }
	    } = item;
	    return Object.keys(this.delayedPullTasksHandlers).includes(action);
	  });
	}
	function _convertTaskItems2(pullItems, mapIds) {
	  const tasksItems = [];

	  // Replace the task id with the flow id.
	  pullItems.forEach(item => {
	    const {
	      data: {
	        id
	      }
	    } = item;
	    if (id in mapIds) {
	      // eslint-disable-next-line no-param-reassign,unicorn/consistent-destructuring
	      item.data.id = mapIds[id];
	      tasksItems.push(item);
	    }
	  });
	  return tasksItems;
	}
	function _uniqueItems2(items) {
	  const uniqueItems = items.reduce((accumulator, currentItem) => {
	    if (!accumulator[currentItem.data.id]) {
	      accumulator[currentItem.data.id] = currentItem;
	    }
	    return accumulator;
	  }, {});
	  return Object.values(uniqueItems);
	}
	function _findTaskAddAction2(pullItems) {
	  return pullItems.find(item => item.data.action === 'task_add');
	}
	function _findTaskRemoveAction2(pullItems) {
	  return pullItems.find(item => item.data.action === 'task_remove');
	}
	function _addTaskRemoveItemToMap2(pullItem, mapIds) {
	  var _pullItem$data$action;
	  // eslint-disable-next-line no-param-reassign
	  mapIds[pullItem.data.id] = (_pullItem$data$action = pullItem.data.actionParams) == null ? void 0 : _pullItem$data$action.FLOW_ID;
	  return mapIds;
	}
	function _isCurrentUserCreatorOfTheTask2(pullItem) {
	  var _pullItem$data$action2, _pullItem$data$action3;
	  const createdBy = (_pullItem$data$action2 = pullItem.data.actionParams) == null ? void 0 : (_pullItem$data$action3 = _pullItem$data$action2.AFTER) == null ? void 0 : _pullItem$data$action3.CREATED_BY;
	  return parseInt(createdBy, 10) === parseInt(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId, 10);
	}
	function _showAhaOnMyTasksColumn2(rowId) {
	  const bindElement = babelHelpers.classPrivateFieldLooseBase(this, _getBindElementForAhaOnCell)[_getBindElementForAhaOnCell](rowId, 'MY_TASKS', '.tasks-flow__list-my-tasks span');
	  if (bindElement) {
	    babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks] = new tasks_clue.Clue({
	      id: `my_tasks_${babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId}`,
	      autoSave: true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _clueMyTasks)[_clueMyTasks].show(tasks_clue.Clue.SPOT.MY_TASKS, bindElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isAhaShownOnMyTasksColumn = true;
	  }
	}
	function _getBindElementForAhaOnCell2(rowId, columnId, selector) {
	  var _babelHelpers$classPr;
	  return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getCell)[_getCell](rowId, columnId)) == null ? void 0 : _babelHelpers$classPr.querySelector(selector);
	}
	function _consoleError2(action, error) {
	  // eslint-disable-next-line no-console
	  console.error(`BX.Tasks.Flow.Grid: ${action} error`, error);
	}
	function _clearAnalyticsParams2() {
	  const uri = new main_core.Uri(window.location.href);
	  const section = uri.getQueryParam('ta_sec');
	  if (section) {
	    uri.removeQueryParam('ta_cat', 'ta_sec', 'ta_sub', 'ta_el', 'p1', 'p2', 'p3', 'p4', 'p5');
	    window.history.replaceState(null, null, uri.toString());
	  }
	}
	function _activateHint2() {
	  BX.UI.Hint.init(babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getContainer());
	}
	function _highlightAddedFlow2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId] !== null && babelHelpers.classPrivateFieldLooseBase(this, _isRowExist)[_isRowExist](babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId])) {
	    const rowNode = babelHelpers.classPrivateFieldLooseBase(this, _getRowById)[_getRowById](babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId]).getNode();
	    main_core.Dom.addClass(rowNode, 'tasks-flow__list-flow-highlighted');
	    babelHelpers.classPrivateFieldLooseBase(this, _addedFlowId)[_addedFlowId] = null;
	  }
	}
	function _showFlowCreationWizard2() {
	  const uri = new main_core.Uri(window.location.href);
	  const demoFlowId = uri.getQueryParam('demo_flow');
	  if (demoFlowId) {
	    uri.removeQueryParam('demo_flow');
	    window.history.replaceState(null, null, uri.toString());
	    tasks_flow_editForm.EditForm.createInstance({
	      flowId: demoFlowId,
	      demoFlow: 'Y'
	    });
	  }
	  const createFlow = uri.getQueryParam('create_flow');
	  if (createFlow) {
	    uri.removeQueryParam('create_flow');
	    window.history.replaceState(null, null, uri.toString());
	    tasks_flow_editForm.EditForm.createInstance({
	      guideFlow: 'Y'
	    });
	  }
	}

	var _props = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("props");
	var _filter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filter");
	var _MIN_QUERY_LENGTH = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("MIN_QUERY_LENGTH");
	var _fields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fields");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _updateFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFields");
	var _unSubscribeToEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unSubscribeToEvents");
	var _subscribeToEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToEvents");
	var _inputFilterHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFilterHandler");
	var _applyFilterHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyFilterHandler");
	var _counterClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterClickHandler");
	var _toggleByField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleByField");
	var _isFilteredByFieldValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFilteredByFieldValue");
	var _isFilteredByField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFilteredByField");
	var _setActive = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setActive");
	class Filter {
	  constructor(props) {
	    Object.defineProperty(this, _setActive, {
	      value: _setActive2
	    });
	    Object.defineProperty(this, _isFilteredByField, {
	      value: _isFilteredByField2
	    });
	    Object.defineProperty(this, _isFilteredByFieldValue, {
	      value: _isFilteredByFieldValue2
	    });
	    Object.defineProperty(this, _toggleByField, {
	      value: _toggleByField2
	    });
	    Object.defineProperty(this, _counterClickHandler, {
	      value: _counterClickHandler2
	    });
	    Object.defineProperty(this, _applyFilterHandler, {
	      value: _applyFilterHandler2
	    });
	    Object.defineProperty(this, _inputFilterHandler, {
	      value: _inputFilterHandler2
	    });
	    Object.defineProperty(this, _subscribeToEvents, {
	      value: _subscribeToEvents2
	    });
	    Object.defineProperty(this, _unSubscribeToEvents, {
	      value: _unSubscribeToEvents2
	    });
	    Object.defineProperty(this, _updateFields, {
	      value: _updateFields2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _props, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _filter, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _MIN_QUERY_LENGTH, {
	      writable: true,
	      value: 3
	    });
	    Object.defineProperty(this, _fields, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _props)[_props] = props;
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  hasFilteredFields() {
	    const filteredFields = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFilterFieldsValues();
	    const fields = Object.values(filteredFields);
	    for (const field of fields) {
	      if (this.isArrayFieldFiller(field) || this.isStringFieldFilled(field)) {
	        return true;
	      }
	    }
	    return false;
	  }
	  isFilterActive() {
	    const isPresetApplied = !['default_filter', 'tmp_filter'].includes(babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getPreset().getCurrentPresetId());
	    const isSearchFilled = !this.isSearchEmpty();
	    const hasFilledFields = this.hasFilteredFields();
	    return isPresetApplied || isSearchFilled || hasFilledFields;
	  }
	  isArrayFieldFiller(field) {
	    return main_core.Type.isArrayFilled(field);
	  }
	  isStringFieldFilled(field) {
	    return field !== 'NONE' && main_core.Type.isStringFilled(field);
	  }
	  isSearchEmpty() {
	    const query = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getSearch().getSearchString();
	    return !query || query.length < babelHelpers.classPrivateFieldLooseBase(this, _MIN_QUERY_LENGTH)[_MIN_QUERY_LENGTH];
	  }
	}
	function _init2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter] = BX.Main.filterManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _props)[_props].filterId);
	  babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields]();
	  babelHelpers.classPrivateFieldLooseBase(this, _subscribeToEvents)[_subscribeToEvents]();
	}
	function _updateFields2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields] = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFilterFieldsValues();
	}
	function _unSubscribeToEvents2() {
	  main_core_events.EventEmitter.unsubscribe('BX.Filter.Search:input', babelHelpers.classPrivateFieldLooseBase(this, _inputFilterHandler)[_inputFilterHandler].bind(this));
	  main_core_events.EventEmitter.unsubscribe('BX.Main.Filter:apply', babelHelpers.classPrivateFieldLooseBase(this, _applyFilterHandler)[_applyFilterHandler].bind(this));
	  main_core_events.EventEmitter.unsubscribe('Tasks.Toolbar:onItem', babelHelpers.classPrivateFieldLooseBase(this, _counterClickHandler)[_counterClickHandler].bind(this));
	}
	function _subscribeToEvents2() {
	  main_core_events.EventEmitter.subscribe('BX.Filter.Search:input', babelHelpers.classPrivateFieldLooseBase(this, _inputFilterHandler)[_inputFilterHandler].bind(this));
	  main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', babelHelpers.classPrivateFieldLooseBase(this, _applyFilterHandler)[_applyFilterHandler].bind(this));
	  main_core_events.EventEmitter.subscribe('Tasks.Toolbar:onItem', babelHelpers.classPrivateFieldLooseBase(this, _counterClickHandler)[_counterClickHandler].bind(this));
	}
	function _inputFilterHandler2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _setActive)[_setActive](this.isFilterActive());
	}
	function _applyFilterHandler2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields]();
	}
	function _counterClickHandler2(baseEvent) {
	  const data = baseEvent.getData();
	  if (data.counter && data.counter.filter) {
	    babelHelpers.classPrivateFieldLooseBase(this, _toggleByField)[_toggleByField]({
	      [data.counter.filterField]: data.counter.filterValue
	    });
	  }
	}
	function _toggleByField2(field) {
	  const name = Object.keys(field)[0];
	  const value = field[name];
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isFilteredByFieldValue)[_isFilteredByFieldValue](name, value)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getApi().extendFilter({
	      [name]: value
	    });
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFilterFields().forEach(field => {
	    if (field.getAttribute('data-name') === name) {
	      babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFields().deleteField(field);
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getSearch().apply();
	}
	function _isFilteredByFieldValue2(field, value) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _isFilteredByField)[_isFilteredByField](field) && babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields][field] === value;
	}
	function _isFilteredByField2(field) {
	  if (!Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields]).includes(field)) {
	    return false;
	  }
	  if (main_core.Type.isArray(babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields][field])) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields][field].length > 0;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields][field] !== '';
	}
	function _setActive2(isActive) {
	  const wrap = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].popupBindElement;
	  if (isActive) {
	    main_core.Dom.removeClass(wrap, 'main-ui-filter-default-applied');
	    main_core.Dom.addClass(wrap, 'main-ui-filter-search--showed');
	  } else {
	    main_core.Dom.addClass(wrap, 'main-ui-filter-default-applied');
	    main_core.Dom.removeClass(wrap, 'main-ui-filter-search--showed');
	  }
	}

	exports.Grid = Grid;
	exports.Filter = Filter;

}((this.BX.Tasks.Flow = this.BX.Tasks.Flow || {}),BX.Tasks.Flow,BX.UI.Dialogs,BX.UI,BX.Pull,BX.Tasks.Flow,BX.Tasks.Flow,BX.Tasks,BX.UI.Manual,BX.Event,BX));
//# sourceMappingURL=script.js.map
