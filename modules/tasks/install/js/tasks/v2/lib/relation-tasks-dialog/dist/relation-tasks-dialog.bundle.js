/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core_events,tasks_v2_lib_entitySelectorDialog,tasks_v2_lib_relationError,tasks_v2_provider_service_taskService,main_core,tasks_v2_const,tasks_v2_provider_service_relationService) {
	'use strict';

	let _ = t => t,
	  _t;
	var _meta = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("meta");
	var _taskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("taskId");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _onUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUpdateOnce");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	var _createFooter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createFooter");
	var _addTaskItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addTaskItems");
	var _updateTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTask");
	var _add = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("add");
	var _selectableItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectableItems");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _mapIdsToItemIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mapIdsToItemIds");
	var _task = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("task");
	class RelationTasksDialog {
	  constructor(meta) {
	    Object.defineProperty(this, _task, {
	      get: _get_task,
	      set: void 0
	    });
	    Object.defineProperty(this, _mapIdsToItemIds, {
	      value: _mapIdsToItemIds2
	    });
	    Object.defineProperty(this, _items, {
	      get: _get_items,
	      set: void 0
	    });
	    Object.defineProperty(this, _selectableItems, {
	      get: _get_selectableItems,
	      set: void 0
	    });
	    Object.defineProperty(this, _add, {
	      value: _add2
	    });
	    Object.defineProperty(this, _updateTask, {
	      value: _updateTask2
	    });
	    Object.defineProperty(this, _addTaskItems, {
	      value: _addTaskItems2
	    });
	    Object.defineProperty(this, _createFooter, {
	      value: _createFooter2
	    });
	    Object.defineProperty(this, _createDialog, {
	      value: _createDialog2
	    });
	    Object.defineProperty(this, _meta, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _taskId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _onUpdateOnce, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta] = meta;
	  }
	  setTaskId(taskId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId] = taskId;
	    return this;
	  }
	  showTo(targetNode, targetContainer) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _dialog))[_dialog]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].isLoaded()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _addTaskItems)[_addTaskItems](babelHelpers.classPrivateFieldLooseBase(this, _task)[_task][babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].idsField]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].selectItemsByIds(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].setSelectableByIds(babelHelpers.classPrivateFieldLooseBase(this, _selectableItems)[_selectableItems]);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getPopup().setTargetContainer(targetContainer);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].showTo(targetNode);
	  }
	  getDialog() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog];
	  }
	  onUpdateOnce(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = callback;
	    return this;
	  }
	}
	function _createDialog2() {
	  const handleClose = () => {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].isLoaded()) {
	      return;
	    }
	    const ids = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems().map(item => Number(item.getId()));
	    if (ids.sort().join(',') !== babelHelpers.classPrivateFieldLooseBase(this, _task)[_task][babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].idsField].sort().join(',')) {
	      void babelHelpers.classPrivateFieldLooseBase(this, _updateTask)[_updateTask]();
	    }
	  };
	  return new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	    context: 'tasks-card',
	    multiple: true,
	    enableSearch: true,
	    width: 500,
	    entities: [{
	      id: tasks_v2_const.EntitySelectorEntity.Task
	    }],
	    preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _items)[_items],
	    popupOptions: {
	      events: {
	        onClose: handleClose
	      }
	    },
	    footer: babelHelpers.classPrivateFieldLooseBase(this, _createFooter)[_createFooter]()
	  });
	}
	function _createFooter2() {
	  const footer = main_core.Tag.render(_t || (_t = _`
			<span class="ui-selector-footer-link ui-selector-footer-link-add">${0}</span>
		`), babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].footerText);
	  main_core.Event.bind(footer, 'click', () => {
	    var _this$getDialog$getAc, _this$getDialog$getAc2;
	    return main_core_events.EventEmitter.emit(tasks_v2_const.EventName.OpenCompactCard, {
	      [tasks_v2_const.TaskField.Title]: (_this$getDialog$getAc = (_this$getDialog$getAc2 = this.getDialog().getActiveTab()).getLastSearchQuery) == null ? void 0 : _this$getDialog$getAc.call(_this$getDialog$getAc2).getQuery(),
	      [babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].relationToField]: babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]
	    });
	  });
	  return footer;
	}
	function _addTaskItems2(ids) {
	  const itemIds = new Set(babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getItems().map(it => it.getId()));
	  ids.filter(id => !itemIds.has(id)).forEach(id => {
	    const task = tasks_v2_provider_service_taskService.taskService.getStoreTask(id);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].addItem({
	      id,
	      entityId: tasks_v2_const.EntitySelectorEntity.Task,
	      title: task.title,
	      selected: true,
	      sort: 0,
	      tabs: ['recents']
	    });
	  });
	}
	async function _updateTask2() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4;
	  const selectedItems = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems();
	  const newTaskIds = selectedItems.map(item => Number(item.getId()));
	  const currentTaskIds = babelHelpers.classPrivateFieldLooseBase(this, _task)[_task][babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].idsField];
	  const idsToDelete = currentTaskIds.filter(id => !newTaskIds.includes(id));
	  const idsToAdd = newTaskIds.filter(id => !currentTaskIds.includes(id));
	  await Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].service.delete(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId], idsToDelete), babelHelpers.classPrivateFieldLooseBase(this, _add)[_add](idsToAdd)]);
	  (_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce))[_onUpdateOnce]) == null ? void 0 : _babelHelpers$classPr3.call(_babelHelpers$classPr4);
	  babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = null;
	}
	async function _add2(ids) {
	  const error = await babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].service.add(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId], ids);
	  if (error) {
	    void tasks_v2_lib_relationError.relationError.setTaskId(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]).showError(error, babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].id);
	  }
	}
	function _get_selectableItems() {
	  const selectableIds = [];
	  const unselectableIds = [];
	  babelHelpers.classPrivateFieldLooseBase(this, _task)[_task][babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].idsField].forEach(id => {
	    const task = tasks_v2_provider_service_taskService.taskService.getStoreTask(id);
	    if (!task || task.rights.detachParent || task.rights.detachRelated) {
	      selectableIds.push(id);
	    } else {
	      unselectableIds.push(id);
	    }
	  });
	  return {
	    selectable: babelHelpers.classPrivateFieldLooseBase(this, _mapIdsToItemIds)[_mapIdsToItemIds](selectableIds),
	    unselectable: babelHelpers.classPrivateFieldLooseBase(this, _mapIdsToItemIds)[_mapIdsToItemIds](unselectableIds)
	  };
	}
	function _get_items() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _mapIdsToItemIds)[_mapIdsToItemIds](babelHelpers.classPrivateFieldLooseBase(this, _task)[_task][babelHelpers.classPrivateFieldLooseBase(this, _meta)[_meta].idsField]);
	}
	function _mapIdsToItemIds2(ids) {
	  return ids.map(id => [tasks_v2_const.EntitySelectorEntity.Task, id]);
	}
	function _get_task() {
	  return tasks_v2_provider_service_taskService.taskService.getStoreTask(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]);
	}

	const subTasksMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.SubTasks,
	  idsField: 'subTaskIds',
	  relationToField: 'parentId',
	  footerText: main_core.Loc.getMessage('TASKS_V2_SUB_TASKS_CREATE'),
	  service: tasks_v2_provider_service_relationService.subTasksService
	});
	const relatedTasksMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.RelatedTasks,
	  idsField: 'relatedTaskIds',
	  relationToField: 'relatedToTaskId',
	  footerText: main_core.Loc.getMessage('TASKS_V2_RELATED_TASKS_CREATE'),
	  service: tasks_v2_provider_service_relationService.relatedTasksService
	});

	const subTasksDialog = new RelationTasksDialog(subTasksMeta);
	const relatedTasksDialog = new RelationTasksDialog(relatedTasksMeta);

	exports.subTasksDialog = subTasksDialog;
	exports.relatedTasksDialog = relatedTasksDialog;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX.Event,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=relation-tasks-dialog.bundle.js.map
