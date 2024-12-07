/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_integration_analytics,ui_notification,main_popup,main_core_events,pull_queuemanager,crm_kanban_sort,main_core) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var NAMESPACE = main_core.Reflection.namespace('BX.CRM.Kanban.Actions');
	var _grid = /*#__PURE__*/new WeakMap();
	var _params = /*#__PURE__*/new WeakMap();
	var _isShowNotify = /*#__PURE__*/new WeakMap();
	var _isApplyFilterAfterAction = /*#__PURE__*/new WeakMap();
	var _useIgnorePostfixForCode = /*#__PURE__*/new WeakMap();
	var _analyticsData = /*#__PURE__*/new WeakMap();
	var _prepareExecute = /*#__PURE__*/new WeakSet();
	var _onSuccess = /*#__PURE__*/new WeakSet();
	var _handleErrorOnSimpleAction = /*#__PURE__*/new WeakSet();
	var _handleSuccessOnSimpleAction = /*#__PURE__*/new WeakSet();
	var _notify = /*#__PURE__*/new WeakSet();
	var _getPreparedNotifyCode = /*#__PURE__*/new WeakSet();
	var _getPreparedNotifyContent = /*#__PURE__*/new WeakSet();
	var _onFailure = /*#__PURE__*/new WeakSet();
	var _prepareAnalyticsData = /*#__PURE__*/new WeakSet();
	var SimpleAction = /*#__PURE__*/function () {
	  function SimpleAction(_grid2, _params2) {
	    babelHelpers.classCallCheck(this, SimpleAction);
	    _classPrivateMethodInitSpec(this, _prepareAnalyticsData);
	    _classPrivateMethodInitSpec(this, _onFailure);
	    _classPrivateMethodInitSpec(this, _getPreparedNotifyContent);
	    _classPrivateMethodInitSpec(this, _getPreparedNotifyCode);
	    _classPrivateMethodInitSpec(this, _notify);
	    _classPrivateMethodInitSpec(this, _handleSuccessOnSimpleAction);
	    _classPrivateMethodInitSpec(this, _handleErrorOnSimpleAction);
	    _classPrivateMethodInitSpec(this, _onSuccess);
	    _classPrivateMethodInitSpec(this, _prepareExecute);
	    _classPrivateFieldInitSpec(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isShowNotify, {
	      writable: true,
	      value: true
	    });
	    _classPrivateFieldInitSpec(this, _isApplyFilterAfterAction, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _useIgnorePostfixForCode, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _analyticsData, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid, _grid2);
	    babelHelpers.classPrivateFieldSet(this, _params, _params2);
	  }
	  babelHelpers.createClass(SimpleAction, [{
	    key: "showNotify",
	    value: function showNotify() {
	      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      babelHelpers.classPrivateFieldSet(this, _isShowNotify, value);
	      return this;
	    }
	  }, {
	    key: "applyFilterAfterAction",
	    value: function applyFilterAfterAction() {
	      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      babelHelpers.classPrivateFieldSet(this, _isApplyFilterAfterAction, value);
	      return this;
	    }
	  }, {
	    key: "setIgnorePostfixForCode",
	    value: function setIgnorePostfixForCode() {
	      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      babelHelpers.classPrivateFieldSet(this, _useIgnorePostfixForCode, value);
	      return this;
	    }
	  }, {
	    key: "execute",
	    value: function execute() {
	      var _this = this;
	      _classPrivateMethodGet(this, _prepareExecute, _prepareExecute2).call(this);
	      if (babelHelpers.classPrivateFieldGet(this, _params).action === 'status') {
	        _classPrivateMethodGet(this, _prepareAnalyticsData, _prepareAnalyticsData2).call(this);
	        babelHelpers.classPrivateFieldGet(this, _grid).registerAnalyticsCloseEvent(babelHelpers.classPrivateFieldGet(this, _analyticsData), BX.Crm.Integration.Analytics.Dictionary.STATUS_ATTEMPT);
	      }
	      return new Promise(function (resolve, reject) {
	        babelHelpers.classPrivateFieldGet(_this, _grid).ajax(babelHelpers.classPrivateFieldGet(_this, _params), function (data) {
	          return _classPrivateMethodGet(_this, _onSuccess, _onSuccess2).call(_this, data, resolve);
	        }, function (error) {
	          return _classPrivateMethodGet(_this, _onFailure, _onFailure2).call(_this, error, reject);
	        });
	      });
	    }
	  }]);
	  return SimpleAction;
	}();
	function _prepareExecute2() {
	  if (babelHelpers.classPrivateFieldGet(this, _grid).isMultiSelectMode()) {
	    babelHelpers.classPrivateFieldGet(this, _grid).resetMultiSelectMode();
	  }
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _params).eventId) && pull_queuemanager.QueueManager) {
	    // eslint-disable-next-line no-param-reassign
	    babelHelpers.classPrivateFieldGet(this, _params).eventId = pull_queuemanager.QueueManager.registerRandomEventId();
	  }
	}
	function _onSuccess2(data, resolve) {
	  if (!data || data.error) {
	    babelHelpers.classPrivateFieldGet(this, _grid).registerAnalyticsCloseEvent(babelHelpers.classPrivateFieldGet(this, _analyticsData), BX.Crm.Integration.Analytics.Dictionary.STATUS_ERROR);
	    _classPrivateMethodGet(this, _handleErrorOnSimpleAction, _handleErrorOnSimpleAction2).call(this, data, resolve);
	  } else {
	    babelHelpers.classPrivateFieldGet(this, _grid).registerAnalyticsCloseEvent(babelHelpers.classPrivateFieldGet(this, _analyticsData), BX.Crm.Integration.Analytics.Dictionary.STATUS_SUCCESS);
	    _classPrivateMethodGet(this, _handleSuccessOnSimpleAction, _handleSuccessOnSimpleAction2).call(this, data, resolve);
	  }
	  babelHelpers.classPrivateFieldSet(this, _analyticsData, null);
	}
	function _handleErrorOnSimpleAction2(data, callback) {
	  var grid = babelHelpers.classPrivateFieldGet(this, _grid);
	  var gridData = grid.getData();
	  var params = babelHelpers.classPrivateFieldGet(this, _params);
	  if (params.action === 'status') {
	    grid.stopActionPanel();
	    grid.onApplyFilter();
	    if (grid.getTypeInfoParam('showPersonalSetStatusNotCompletedText')) {
	      var messageCode = gridData.isDynamicEntity ? 'CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_DYNAMIC_MSGVER_1' : null;
	      if (!messageCode) {
	        var codeVer = "CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_".concat(gridData.entityType);
	        var codeVer1 = "".concat(codeVer, "_MSGVER_1");
	        var codeVer2 = "".concat(codeVer, "_MSGVER_2");
	        messageCode = BX.Loc.hasMessage(codeVer2) ? codeVer2 : codeVer1;
	      }
	      BX.Kanban.Utils.showErrorDialog(main_core.Loc.getMessage(messageCode));
	      callback(new Error(main_core.Loc.getMessage(messageCode)));
	    } else {
	      BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
	      callback(new Error(data.error));
	    }
	  } else {
	    BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
	    callback(new Error(data.error));
	  }
	}
	function _handleSuccessOnSimpleAction2(data, callback) {
	  var grid = babelHelpers.classPrivateFieldGet(this, _grid);
	  var params = babelHelpers.classPrivateFieldGet(this, _params);
	  if (babelHelpers.classPrivateFieldGet(this, _isApplyFilterAfterAction)) {
	    grid.onApplyFilter();
	  }
	  grid.stopActionPanel();
	  if (babelHelpers.classPrivateFieldGet(this, _isShowNotify)) {
	    var code = grid.getData().entityType;
	    if (code.startsWith('DYNAMIC')) {
	      code = 'DYNAMIC';
	    }

	    // @todo replace to useIgnorePostfixForCode check later
	    if (params.action === 'delete' && params.ignore === 'Y') {
	      code = "".concat(code, "_IGNORE");
	    } else {
	      code = "".concat(code, "_").concat(params.action.toUpperCase());
	    }
	    _classPrivateMethodGet(this, _notify, _notify2).call(this, code);
	  }
	  callback(data);
	}
	function _notify2(code) {
	  // eslint-disable-next-line no-param-reassign
	  code = _classPrivateMethodGet(this, _getPreparedNotifyCode, _getPreparedNotifyCode2).call(this, code);
	  var content = _classPrivateMethodGet(this, _getPreparedNotifyContent, _getPreparedNotifyContent2).call(this, code);
	  if (main_core.Type.isStringFilled(content)) {
	    ui_notification.UI.Notification.Center.notify({
	      content: content
	    });
	  }
	}
	function _getPreparedNotifyCode2(code) {
	  if (code === 'DEAL_CHANGECATEGORY') {
	    // eslint-disable-next-line no-param-reassign
	    code = 'DEAL_CHANGECATEGORY_LINK2';
	  } else if (code === 'DYNAMIC_CHANGECATEGORY') {
	    // eslint-disable-next-line no-param-reassign
	    code = 'DYNAMIC_CHANGECATEGORY_LINK2';
	  }

	  // eslint-disable-next-line no-param-reassign
	  code = "CRM_KANBAN_NOTIFY_".concat(code);
	  var msgVer1Codes = ['CRM_KANBAN_NOTIFY_LEAD_STATUS', 'CRM_KANBAN_NOTIFY_DYNAMIC_STATUS', 'CRM_KANBAN_NOTIFY_INVOICE_STATUS', 'CRM_KANBAN_NOTIFY_QUOTE_DELETE', 'CRM_KANBAN_NOTIFY_QUOTE_SETASSIGNED'];
	  if (msgVer1Codes.includes(code)) {
	    // eslint-disable-next-line no-param-reassign
	    code = "".concat(code, "_MSGVER_1");
	  }
	  var msgVer2Codes = ['CRM_KANBAN_NOTIFY_QUOTE_STATUS'];
	  if (msgVer2Codes.includes(code)) {
	    // eslint-disable-next-line no-param-reassign
	    code = "".concat(code, "_MSGVER_2");
	  }
	  return code;
	}
	function _getPreparedNotifyContent2(code) {
	  var content = main_core.Loc.getMessage(code);
	  if (!main_core.Type.isStringFilled(content)) {
	    return null;
	  }
	  var params = babelHelpers.classPrivateFieldGet(this, _params);
	  if (main_core.Type.isPlainObject(params)) {
	    Object.entries(params).forEach(function (entryData) {
	      content = content.replace("#".concat(entryData[0], "#"), entryData[1]);
	    });
	  }
	  return content;
	}
	function _onFailure2(error, callback) {
	  babelHelpers.classPrivateFieldGet(this, _grid).registerAnalyticsCloseEvent(babelHelpers.classPrivateFieldGet(this, _analyticsData), BX.Crm.Integration.Analytics.Dictionary.STATUS_ERROR);
	  babelHelpers.classPrivateFieldSet(this, _analyticsData, null);
	  BX.Kanban.Utils.showErrorDialog("Error: ".concat(error), true);
	  callback(new Error(error));
	}
	function _prepareAnalyticsData2() {
	  var _babelHelpers$classPr = babelHelpers.slicedToArray(babelHelpers.classPrivateFieldGet(this, _params).entity_id, 1),
	    entityId = _babelHelpers$classPr[0];
	  var item = babelHelpers.classPrivateFieldGet(this, _grid).getItem(entityId);
	  var targetColumn = babelHelpers.classPrivateFieldGet(this, _grid).getColumn(babelHelpers.classPrivateFieldGet(this, _params).status);
	  var type = targetColumn ? targetColumn.getData().type : babelHelpers.classPrivateFieldGet(this, _params).type;
	  babelHelpers.classPrivateFieldSet(this, _analyticsData, babelHelpers.classPrivateFieldGet(this, _grid).getDefaultAnalyticsCloseEvent(item, type, babelHelpers.classPrivateFieldGet(this, _params).entity_id.toString()));
	  babelHelpers.classPrivateFieldGet(this, _analyticsData).c_element = BX.Crm.Integration.Analytics.Dictionary.ELEMENT_WON_TOP_ACTIONS;
	  if (type === 'LOOSE') {
	    babelHelpers.classPrivateFieldGet(this, _analyticsData).c_element = BX.Crm.Integration.Analytics.Dictionary.ELEMENT_LOSE_TOP_ACTIONS;
	  }
	}
	NAMESPACE.SimpleAction = SimpleAction;

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var NAMESPACE$1 = main_core.Reflection.namespace('BX.CRM.Kanban.Actions');
	var _grid$1 = /*#__PURE__*/new WeakMap();
	var _dropZone = /*#__PURE__*/new WeakMap();
	var _deletedItems = /*#__PURE__*/new WeakMap();
	var _ids = /*#__PURE__*/new WeakMap();
	var _showNotify = /*#__PURE__*/new WeakMap();
	var _applyFilterAfterAction = /*#__PURE__*/new WeakMap();
	var _action = /*#__PURE__*/new WeakMap();
	var _onResolve = /*#__PURE__*/new WeakSet();
	var _getDeletedItems = /*#__PURE__*/new WeakSet();
	var _prepareDropZone = /*#__PURE__*/new WeakSet();
	var _prepareGrid = /*#__PURE__*/new WeakSet();
	var _unHideUndeletedItems = /*#__PURE__*/new WeakSet();
	var _showResult = /*#__PURE__*/new WeakSet();
	var _getDeleteTitle = /*#__PURE__*/new WeakSet();
	var _onDeletionCancelClick = /*#__PURE__*/new WeakSet();
	var _showActionError = /*#__PURE__*/new WeakSet();
	var _restoreItemInColumn = /*#__PURE__*/new WeakSet();
	var _onReject = /*#__PURE__*/new WeakSet();
	var DeleteAction = /*#__PURE__*/function () {
	  function DeleteAction(_grid2, params) {
	    babelHelpers.classCallCheck(this, DeleteAction);
	    _classPrivateMethodInitSpec$1(this, _onReject);
	    _classPrivateMethodInitSpec$1(this, _restoreItemInColumn);
	    _classPrivateMethodInitSpec$1(this, _showActionError);
	    _classPrivateMethodInitSpec$1(this, _onDeletionCancelClick);
	    _classPrivateMethodInitSpec$1(this, _getDeleteTitle);
	    _classPrivateMethodInitSpec$1(this, _showResult);
	    _classPrivateMethodInitSpec$1(this, _unHideUndeletedItems);
	    _classPrivateMethodInitSpec$1(this, _prepareGrid);
	    _classPrivateMethodInitSpec$1(this, _prepareDropZone);
	    _classPrivateMethodInitSpec$1(this, _getDeletedItems);
	    _classPrivateMethodInitSpec$1(this, _onResolve);
	    _classPrivateFieldInitSpec$1(this, _grid$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _dropZone, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _deletedItems, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _ids, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec$1(this, _showNotify, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _applyFilterAfterAction, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _action, {
	      writable: true,
	      value: SimpleAction
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid$1, _grid2);
	    if (!main_core.Type.isArrayFilled(params.ids)) {
	      throw new Error('Param ids must be filled array');
	    }
	    babelHelpers.classPrivateFieldSet(this, _ids, params.ids);
	    babelHelpers.classPrivateFieldSet(this, _showNotify, main_core.Type.isBoolean(params.showNotify) ? params.showNotify : true);
	    babelHelpers.classPrivateFieldSet(this, _applyFilterAfterAction, main_core.Type.isBoolean(params.applyFilterAfterAction) ? params.applyFilterAfterAction : false);
	  }
	  babelHelpers.createClass(DeleteAction, [{
	    key: "setDropZone",
	    value: function setDropZone(dropZone) {
	      babelHelpers.classPrivateFieldSet(this, _dropZone, dropZone);
	      return this;
	    }
	  }, {
	    key: "execute",
	    value: function execute() {
	      var _this = this;
	      var actionParams = {
	        action: 'delete',
	        id: babelHelpers.classPrivateFieldGet(this, _ids)
	      };
	      new (babelHelpers.classPrivateFieldGet(this, _action))(babelHelpers.classPrivateFieldGet(this, _grid$1), actionParams).showNotify(babelHelpers.classPrivateFieldGet(this, _showNotify)).applyFilterAfterAction(babelHelpers.classPrivateFieldGet(this, _applyFilterAfterAction)).execute().then(function (response) {
	        return _classPrivateMethodGet$1(_this, _onResolve, _onResolve2).call(_this, response);
	      }, function (response) {
	        return _classPrivateMethodGet$1(_this, _onReject, _onReject2).call(_this, response);
	      })["catch"](function () {
	        _classPrivateMethodGet$1(_this, _showActionError, _showActionError2).call(_this);
	      });
	    }
	  }]);
	  return DeleteAction;
	}();
	function _onResolve2(response) {
	  var dropZone = babelHelpers.classPrivateFieldGet(this, _dropZone);
	  if (dropZone) {
	    _classPrivateMethodGet$1(this, _prepareDropZone, _prepareDropZone2).call(this);
	  }
	  _classPrivateMethodGet$1(this, _prepareGrid, _prepareGrid2).call(this);
	  _classPrivateMethodGet$1(this, _unHideUndeletedItems, _unHideUndeletedItems2).call(this, response);
	  _classPrivateMethodGet$1(this, _showResult, _showResult2).call(this, response);
	}
	function _getDeletedItems2() {
	  var _this2 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _deletedItems) === null) {
	    var grid = babelHelpers.classPrivateFieldGet(this, _grid$1);
	    var ids = babelHelpers.classPrivateFieldGet(this, _ids);
	    ids.forEach(function (id) {
	      var item = grid.getItem(id);
	      if (item) {
	        if (babelHelpers.classPrivateFieldGet(_this2, _deletedItems) === null) {
	          babelHelpers.classPrivateFieldSet(_this2, _deletedItems, []);
	        }
	        babelHelpers.classPrivateFieldGet(_this2, _deletedItems).push(item);
	      }
	    });
	  }
	  return babelHelpers.classPrivateFieldGet(this, _deletedItems);
	}
	function _prepareDropZone2() {
	  var dropZone = babelHelpers.classPrivateFieldGet(this, _dropZone);
	  dropZone.empty();
	  dropZone.getDropZoneArea().hide();
	  dropZone.droppedItems = [];
	}
	function _prepareGrid2() {
	  var grid = babelHelpers.classPrivateFieldGet(this, _grid$1);
	  grid.dropZonesShow = false;
	  grid.resetMultiSelectMode();
	  grid.resetActionPanel();
	  grid.resetDragMode();
	}
	function _unHideUndeletedItems2(data) {
	  var _this3 = this;
	  var deletedItems = _classPrivateMethodGet$1(this, _getDeletedItems, _getDeletedItems2).call(this);
	  var deletedIds = data.deletedIds,
	    errors = data.errors;
	  var undeletedItems = deletedItems.filter(function (item) {
	    return !deletedIds.includes(Number(item.getId()));
	  });
	  if (main_core.Type.isArrayFilled(undeletedItems)) {
	    undeletedItems.forEach(function (item) {
	      return _classPrivateMethodGet$1(_this3, _restoreItemInColumn, _restoreItemInColumn2).call(_this3, item);
	    });
	    errors.forEach(function (_ref) {
	      var content = _ref.message,
	        id = _ref.data.id;
	      ui_notification.UI.Notification.Center.notify({
	        content: content,
	        actions: [{
	          title: main_core.Loc.getMessage('CRM_KANBAN_OPEN_ITEM'),
	          events: {
	            click: function click() {
	              BX.fireEvent(babelHelpers.classPrivateFieldGet(_this3, _grid$1).getItem(id).link, 'click');
	            }
	          }
	        }]
	      });
	    });
	  }
	}
	function _showResult2(data) {
	  var _this4 = this;
	  var deletedItems = _classPrivateMethodGet$1(this, _getDeletedItems, _getDeletedItems2).call(this);
	  var deletedIds = data.deletedIds;
	  var removedItems = deletedItems.filter(function (item) {
	    return deletedIds.includes(Number(item.getId()));
	  });
	  if (!main_core.Type.isArrayFilled(removedItems)) {
	    return;
	  }
	  var balloonOptions = {
	    content: _classPrivateMethodGet$1(this, _getDeleteTitle, _getDeleteTitle2).call(this, removedItems)
	  };
	  var grid = babelHelpers.classPrivateFieldGet(this, _grid$1);
	  if (grid.getTypeInfoParam('isRecyclebinEnabled')) {
	    balloonOptions.actions = [{
	      title: main_core.Loc.getMessage('CRM_KANBAN_DELETE_CANCEL'),
	      events: {
	        click: function click() {
	          return _classPrivateMethodGet$1(_this4, _onDeletionCancelClick, _onDeletionCancelClick2).call(_this4, balloon, removedItems);
	        }
	      }
	    }];
	  }
	  var balloon = ui_notification.UI.Notification.Center.notify(balloonOptions);
	}
	function _getDeleteTitle2(removedItems) {
	  var ids = babelHelpers.classPrivateFieldGet(this, _ids);
	  if (ids.length === 1) {
	    return main_core.Loc.getMessage('CRM_KANBAN_DELETE_SUCCESS', {
	      '#ELEMENT_NAME#': removedItems[0].getData().name
	    });
	  }
	  var difference = ids.length - removedItems.length;
	  if (difference === 0) {
	    return main_core.Loc.getMessage('CRM_KANBAN_DELETE_SUCCESS_MULTIPLE');
	  }
	  return main_core.Loc.getMessage('CRM_KANBAN_DELETE_SUCCESS_MULTIPLE_WITH_ERRORS', {
	    '#COUNT#': difference
	  });
	}
	function _onDeletionCancelClick2(balloon, removedItems) {
	  var _this5 = this;
	  balloon.close();
	  var grid = babelHelpers.classPrivateFieldGet(this, _grid$1);
	  var entityIds = babelHelpers.classPrivateFieldGet(this, _ids);
	  var _grid$getData = grid.getData(),
	    entityTypeId = _grid$getData.entityTypeInt;
	  main_core.ajax.runComponentAction('bitrix:crm.kanban', 'restore', {
	    mode: 'ajax',
	    data: {
	      entityIds: entityIds,
	      entityTypeId: entityTypeId
	    }
	  }).then(function (_ref2) {
	    var data = _ref2.data;
	    if (!main_core.Type.isPlainObject(data)) {
	      return;
	    }
	    var ids = Object.values(data).filter(function (id) {
	      return main_core.Type.isNumber(id);
	    });
	    if (main_core.Type.isArrayFilled(ids)) {
	      babelHelpers.classPrivateFieldGet(_this5, _grid$1).loadNew(ids, false, true, true, true).then(function (response) {
	        var autoHideDelay = 6000;
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CRM_KANBAN_DELETE_RESTORE_SUCCESS'),
	          autoHideDelay: autoHideDelay
	        });
	      }, function () {
	        _classPrivateMethodGet$1(_this5, _showActionError, _showActionError2).call(_this5);
	      })["catch"](function () {
	        _classPrivateMethodGet$1(_this5, _showActionError, _showActionError2).call(_this5);
	      });
	    }
	  }, function (response) {
	    return _classPrivateMethodGet$1(_this5, _onReject, _onReject2).call(_this5, response);
	  })["catch"](function () {
	    _classPrivateMethodGet$1(_this5, _showActionError, _showActionError2).call(_this5);
	  });
	}
	function _showActionError2() {
	  ui_notification.UI.Notification.Center.notify({
	    content: main_core.Loc.getMessage('CRM_KANBAN_ACTION_ERROR')
	  });
	}
	function _restoreItemInColumn2(item) {
	  var lastPosition = item.getLastPosition();
	  if (!lastPosition.columnId) {
	    return;
	  }
	  var data = item.getData();
	  data.columnId = lastPosition.columnId;
	  data.targetId = lastPosition.targetId;
	  var grid = babelHelpers.classPrivateFieldGet(this, _grid$1);
	  var price = parseFloat(data.price);
	  grid.getColumn(item.columnId).incPrice(price);
	  grid.updateItem(item.getId(), data);
	  grid.unhideItem(item);
	}
	function _onReject2(response) {
	  var content = response.errors[0].message;
	  ui_notification.UI.Notification.Center.notify({
	    content: content
	  });
	}
	NAMESPACE$1.DeleteAction = DeleteAction;

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10;
	var TYPE_VIEW = 'view';
	var TYPE_EDIT = 'edit';
	var FieldsSelector = /*#__PURE__*/function () {
	  function FieldsSelector(options) {
	    var _this$options$headers, _this$options$default;
	    babelHelpers.classCallCheck(this, FieldsSelector);
	    this.popup = null;
	    this.fields = null;
	    this.fieldsPopupItems = null;
	    this.options = options;
	    this.type = this.options.hasOwnProperty('type') ? this.options.type : TYPE_VIEW;
	    this.selectedFields = this.options.hasOwnProperty('selectedFields') ? this.options.selectedFields.slice(0) : [];
	    this.enableHeadersSections = Boolean(this.options.headersSections);
	    this.headersSections = (_this$options$headers = this.options.headersSections) !== null && _this$options$headers !== void 0 ? _this$options$headers : {};
	    this.defaultHeaderSectionId = (_this$options$default = this.options.defaultHeaderSectionId) !== null && _this$options$default !== void 0 ? _this$options$default : null;
	    this.fieldVisibleClass = 'crm-kanban-popup-field-search-list-item-visible';
	    this.fieldHiddenClass = 'crm-kanban-popup-field-search-list-item-hidden';
	  }
	  babelHelpers.createClass(FieldsSelector, [{
	    key: "show",
	    value: function show() {
	      if (!this.popup) {
	        this.popup = this.createPopup();
	      }
	      if (this.fields) {
	        this.popup.setContent(this.getFieldsLayout());
	      } else {
	        this.loadPopupContent(this.popup);
	      }
	      this.popup.show();
	    }
	  }, {
	    key: "createPopup",
	    value: function createPopup() {
	      var _this = this;
	      return main_popup.PopupManager.create({
	        id: 'kanban_custom_fields_' + this.type,
	        className: 'crm-kanban-popup-field',
	        titleBar: main_core.Loc.getMessage('CRM_KANBAN_CUSTOM_FIELDS_' + this.type.toUpperCase()),
	        cacheable: false,
	        closeIcon: true,
	        lightShadow: true,
	        overlay: true,
	        draggable: true,
	        closeByEsc: true,
	        contentColor: 'white',
	        maxHeight: window.innerHeight - 50,
	        events: {
	          onClose: function onClose() {
	            _this.fieldsPopupItems = null;
	            _this.popup = null;
	          }
	        },
	        buttons: [new BX.UI.SaveButton({
	          color: BX.UI.Button.Color.PRIMARY,
	          state: this.fields ? '' : BX.UI.Button.State.DISABLED,
	          onclick: function onclick() {
	            var selectedFields = _this.fields ? _this.fields.filter(function (field) {
	              return _this.selectedFields.indexOf(field.NAME) >= 0;
	            }) : [];
	            if (selectedFields.length) {
	              _this.popup.close();
	              _this.executeCallback(selectedFields);
	            } else {
	              ui_notification.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('CRM_KANBAN_POPUP_AT_LEAST_ONE_FIELD'),
	                autoHide: true,
	                autoHideDelay: 2000
	              });
	            }
	          }
	        }), new BX.UI.CancelButton({
	          onclick: function onclick() {
	            _this.popup.close();
	          }
	        })]
	      });
	    }
	  }, {
	    key: "loadPopupContent",
	    value: function loadPopupContent(popup) {
	      var _this2 = this;
	      var loaderContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-kanban-popup-field-loader\"></div>"])));
	      var loader = new BX.Loader({
	        target: loaderContainer,
	        size: 80
	      });
	      loader.show();
	      popup.setContent(loaderContainer);
	      BX.ajax.runComponentAction('bitrix:crm.kanban', 'getFields', {
	        mode: 'ajax',
	        data: {
	          entityType: this.options.entityTypeName,
	          viewType: this.type
	        }
	      }).then(function (response) {
	        loader.destroy();
	        _this2.fields = response.data;
	        popup.setContent(_this2.getFieldsLayout());
	        popup.getButtons().forEach(function (button) {
	          return button.setDisabled(false);
	        });
	        popup.adjustPosition();
	      })["catch"](function (response) {
	        BX.Kanban.Utils.showErrorDialog(response.errors.pop().message);
	      });
	      return popup;
	    }
	  }, {
	    key: "getFieldsLayout",
	    value: function getFieldsLayout() {
	      var _this3 = this;
	      var sectionsWithFields = this.distributeFieldsBySections(this.fields);
	      var container = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-kanban-popup-field\"></div>"])));
	      var headerWrapper = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-kanban-popup-field-search-header-wrapper\">\n\t\t\t\t<div class=\"ui-form-row-inline\"></div>\n\t\t\t</div>\n\t\t"])));
	      container.prepend(headerWrapper);
	      this.preparePopupContentHeaderSections(headerWrapper);
	      this.preparePopupContentHeaderSearch(headerWrapper);
	      this.getSections().map(function (section) {
	        var sectionWrapperId = _this3.getSectionWrapperNameBySectionName(section.name);
	        var sectionWrapper = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div \n\t\t\t\t\tclass=\"crm-kanban-popup-field-search-section\" \n\t\t\t\t\tdata-crm-kanban-popup-field-search-section=\"", "\">\n\t\t\t\t</div>\n\t\t\t"])), sectionWrapperId);
	        main_core.Dom.append(sectionWrapper, container);
	        var sectionName = section.name;
	        if (sectionsWithFields.hasOwnProperty(sectionName) && sectionsWithFields[sectionName].length) {
	          main_core.Dom.append(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-kanban-popup-field-title\">", "</div>"])), main_core.Text.encode(section.title)), sectionWrapper);
	          main_core.Dom.append(main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-kanban-popup-field-wrapper\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>"])), sectionsWithFields[sectionName].map(function (field) {
	            var label = field.LABEL;
	            if (!label.length && section['elements'] && section['elements'][field.NAME] && section['elements'][field.NAME]['title'] && section['elements'][field.NAME]['title'].length) {
	              label = section['elements'][field.NAME]['title'];
	            }
	            var encodedLabel = main_core.Text.encode(label);
	            return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t<div class=\"crm-kanban-popup-field-item\" title=\"", "\">\n\t\t\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\t\t\tid=\"cf_", "\" \n\t\t\t\t\t\t\t\t\t\ttype=\"checkbox\" \n\t\t\t\t\t\t\t\t\t\tname=\"", "\"\n\t\t\t\t\t\t\t\t\t\tclass=\"crm-kanban-popup-field-item-input\"\n\t\t\t\t\t\t\t\t\t\tdata-label=\"", "\"\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t\t<label for=\"cf_", "\" class=\"crm-kanban-popup-field-item-label\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t\t</div>"])), encodedLabel, main_core.Text.encode(field.ID), main_core.Text.encode(field.NAME), encodedLabel, _this3.selectedFields.indexOf(field.NAME) >= 0 ? 'checked' : '', _this3.onFieldClick.bind(_this3), main_core.Text.encode(field.ID), encodedLabel);
	          })), sectionWrapper);
	        }
	      });
	      return container;
	    }
	  }, {
	    key: "preparePopupContentHeaderSections",
	    value: function preparePopupContentHeaderSections(headerWrapper) {
	      if (!this.enableHeadersSections) {
	        return;
	      }
	      var headerSectionsWrapper = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<div class=\"ui-form-content crm-kanban-popup-field-search-section-wrapper\"></div>\n\t\t\t</div>\n\t\t"])));
	      headerWrapper.firstElementChild.appendChild(headerSectionsWrapper);
	      var headersSections = this.getHeadersSections();
	      for (var key in headersSections) {
	        var itemClass = 'crm-kanban-popup-field-search-section-item-icon' + (headersSections[key].selected ? " crm-kanban-popup-field-search-section-item-icon-active" : '');
	        var headerSectionItem = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-kanban-popup-field-search-section-item\" data-kanban-popup-filter-section-button=\"", "\">\n\t\t\t\t\t<div class=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), key, itemClass, main_core.Text.encode(headersSections[key].name));
	        headerSectionsWrapper.firstElementChild.appendChild(headerSectionItem);
	        if (this.type !== TYPE_VIEW) {
	          break;
	        }
	        main_core.Event.bind(headerSectionItem, 'click', this.onFilterSectionClick.bind(this, headerSectionItem));
	      }
	    }
	  }, {
	    key: "onFilterSectionClick",
	    value: function onFilterSectionClick(item) {
	      var activeClass = 'crm-kanban-popup-field-search-section-item-icon-active';
	      var sectionId = item.dataset.kanbanPopupFilterSectionButton;
	      var sections = document.querySelectorAll("[data-crm-kanban-popup-field-search-section=\"".concat(sectionId, "\"]"));
	      if (main_core.Dom.hasClass(item.firstElementChild, activeClass)) {
	        main_core.Dom.removeClass(item.firstElementChild, activeClass);
	        this.filterSectionsToggle(sections, 'hide');
	      } else {
	        main_core.Dom.addClass(item.firstElementChild, activeClass);
	        this.filterSectionsToggle(sections, 'show');
	      }
	    }
	  }, {
	    key: "filterSectionsToggle",
	    value: function filterSectionsToggle(sections, action) {
	      Array.from(sections).map(function (section) {
	        action === 'show' ? main_core.Dom.show(section) : main_core.Dom.hide(section);
	      });
	    }
	  }, {
	    key: "preparePopupContentHeaderSearch",
	    value: function preparePopupContentHeaderSearch(headerWrapper) {
	      var searchForm = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t<div class=\"ui-form-content crm-kanban-popup-field-search-input-wrapper\">\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-before-icon ui-ctl-after-icon\">\n\t\t\t\t\t\t<div class=\"ui-ctl-before ui-ctl-icon-search\"></div>\n\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\"></button>\n\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element crm-kanban-popup-field-search-section-input\">\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])));
	      headerWrapper.firstElementChild.appendChild(searchForm);
	      var inputs = searchForm.getElementsByClassName('crm-kanban-popup-field-search-section-input');
	      if (inputs.length) {
	        var input = inputs[0];
	        main_core.Event.bind(input, 'input', this.onFilterSectionSearchInput.bind(this, input));
	        main_core.Event.bind(input.previousElementSibling, 'click', this.onFilterSectionSearchInputClear.bind(this, input));
	      }
	    }
	  }, {
	    key: "onFilterSectionSearchInput",
	    value: function onFilterSectionSearchInput(input) {
	      var _this4 = this;
	      var search = input.value;
	      if (search.length) {
	        search = search.toLowerCase();
	      }
	      this.getFieldsPopupItems().map(function (item) {
	        var title = item.innerText.toLowerCase();
	        if (search.length && title.indexOf(search) === -1) {
	          main_core.Dom.removeClass(item, _this4.fieldVisibleClass);
	          main_core.Dom.addClass(item, _this4.fieldHiddenClass);
	        } else {
	          main_core.Dom.removeClass(item, _this4.fieldHiddenClass);
	          main_core.Dom.addClass(item, _this4.fieldVisibleClass);
	          item.style.display = 'block';
	        }
	      });
	    }
	  }, {
	    key: "getFieldsPopupItems",
	    value: function getFieldsPopupItems() {
	      if (!main_core.Type.isArray(this.fieldsPopupItems)) {
	        this.fieldsPopupItems = Array.from(this.popup.getPopupContainer().querySelectorAll('.crm-kanban-popup-field-item'));
	        this.prepareAnimation();
	      }
	      return this.fieldsPopupItems;
	    }
	  }, {
	    key: "prepareAnimation",
	    value: function prepareAnimation() {
	      var _this5 = this;
	      this.fieldsPopupItems.map(function (item) {
	        main_core.Event.bind(item, 'animationend', _this5.onAnimationEnd.bind(_this5, item));
	      });
	    }
	  }, {
	    key: "onAnimationEnd",
	    value: function onAnimationEnd(item) {
	      item.style.display = main_core.Dom.hasClass(item, this.fieldHiddenClass) ? 'none' : 'block';
	    }
	  }, {
	    key: "onFilterSectionSearchInputClear",
	    value: function onFilterSectionSearchInputClear(input) {
	      if (input.value.length) {
	        input.value = '';
	        this.onFilterSectionSearchInput(input);
	      }
	    }
	  }, {
	    key: "getSectionWrapperNameBySectionName",
	    value: function getSectionWrapperNameBySectionName(name) {
	      var headerSections = this.getHeadersSections();
	      for (var id in headerSections) {
	        if (this.headersSections[id].sections && this.headersSections[id].sections.includes(name)) {
	          return this.headersSections[id].id;
	        }
	      }
	      return this.headersSections[this.defaultHeaderSectionId] && this.defaultHeaderSectionId ? this.headersSections[this.defaultHeaderSectionId].id : null;
	    }
	  }, {
	    key: "getHeadersSections",
	    value: function getHeadersSections() {
	      var _this$headersSections;
	      return (_this$headersSections = this.headersSections) !== null && _this$headersSections !== void 0 ? _this$headersSections : {};
	    }
	  }, {
	    key: "distributeFieldsBySections",
	    value: function distributeFieldsBySections(fields) {
	      // remove ignored fields from result:
	      var ignoredFields = this.getIgnoredFields();
	      fields = fields.filter(function (item) {
	        return !(ignoredFields.hasOwnProperty(item.NAME) && ignoredFields[item.NAME]);
	      });
	      var fieldsBySections = {};
	      var defaultSectionName = '';
	      var sections = this.options.hasOwnProperty('sections') ? this.options.sections : [];
	      for (var i = 0; i < sections.length; i++) {
	        var section = sections[i];
	        var sectionName = section.name;
	        fieldsBySections[sectionName] = [];
	        if (main_core.Type.isPlainObject(section.elements)) {
	          fieldsBySections[sectionName] = this.filterFieldsByList(fields, section.elements);
	        } else if (section.hasOwnProperty('elementsRule')) {
	          fieldsBySections[sectionName] = this.filterFieldsByRule(fields, new RegExp(section.elementsRule));
	        } else if (section.elements === '*') {
	          defaultSectionName = sectionName;
	        }
	      }
	      if (defaultSectionName !== '') {
	        fieldsBySections[defaultSectionName] = this.filterNotUsedFields(fields, fieldsBySections);
	      }
	      return fieldsBySections;
	    }
	  }, {
	    key: "filterFieldsByList",
	    value: function filterFieldsByList(fields, whiteList) {
	      return fields.filter(function (item) {
	        return whiteList.hasOwnProperty(item.NAME);
	      });
	    }
	  }, {
	    key: "filterFieldsByRule",
	    value: function filterFieldsByRule(fields, rule) {
	      return fields.filter(function (item) {
	        return item.NAME.match(rule);
	      });
	    }
	  }, {
	    key: "filterNotUsedFields",
	    value: function filterNotUsedFields(fields, alreadyUsedFieldsBySection) {
	      var alreadyUsedFieldsNames = Object.values(alreadyUsedFieldsBySection).reduce(function (prevFields, sectionFields) {
	        return prevFields.concat(sectionFields.map(function (item) {
	          return item.NAME;
	        }));
	      }, []);
	      return fields.filter(function (item) {
	        return alreadyUsedFieldsNames.indexOf(item.NAME) < 0;
	      });
	    }
	  }, {
	    key: "getSections",
	    value: function getSections() {
	      return this.options.hasOwnProperty('sections') ? this.options.sections : [];
	    }
	  }, {
	    key: "getIgnoredFields",
	    value: function getIgnoredFields() {
	      var fields = Object.assign({}, this.options.ignoredFields);
	      var extraFields = [];
	      if (this.type === TYPE_EDIT) {
	        extraFields = ['ID', 'CLOSED', 'DATE_CREATE', 'DATE_MODIFY', 'COMMENTS', 'OPPORTUNITY'];
	      } else {
	        extraFields = ['PHONE', 'EMAIL', 'WEB', 'IM'];
	      }
	      extraFields.forEach(function (fieldName) {
	        return fields[fieldName] = true;
	      });
	      return fields;
	    }
	  }, {
	    key: "executeCallback",
	    value: function executeCallback(selectedFields) {
	      if (!this.options.hasOwnProperty('onSelect') || !main_core.Type.isFunction(this.options.onSelect)) {
	        return;
	      }
	      var callbackPayload = {};
	      selectedFields.forEach(function (field) {
	        callbackPayload[field.NAME] = field.LABEL ? field.LABEL : '';
	      });
	      this.options.onSelect(callbackPayload);
	    }
	  }, {
	    key: "onFieldClick",
	    value: function onFieldClick(event) {
	      var fieldName = event.target.name;
	      if (event.target.checked && this.selectedFields.indexOf(fieldName) < 0) {
	        this.selectedFields.push(fieldName);
	      }
	      if (!event.target.checked && this.selectedFields.indexOf(fieldName) >= 0) {
	        this.selectedFields.splice(this.selectedFields.indexOf(fieldName), 1);
	      }
	    }
	  }]);
	  return FieldsSelector;
	}();

	var ViewMode = {
	  MODE_STAGES: 'STAGES',
	  MODE_ACTIVITIES: 'ACTIVITIES',
	  MODE_DEADLINES: 'DEADLINES',
	  getDefault: function getDefault() {
	    return this.MODE_STAGES;
	  },
	  getAll: function getAll() {
	    return [this.MODE_STAGES, this.MODE_ACTIVITIES, this.MODE_DEADLINES];
	  },
	  normalize: function normalize(mode) {
	    return this.getAll().includes(mode) ? mode : this.getDefault();
	  }
	};
	Object.freeze(ViewMode);

	var PullOperation = /*#__PURE__*/function () {
	  babelHelpers.createClass(PullOperation, null, [{
	    key: "createInstance",
	    value: function createInstance(data) {
	      return new PullOperation(data.grid).setItemId(data.itemId).setAction(data.action).setActionParams(data.actionParams);
	    }
	  }]);
	  function PullOperation(grid) {
	    babelHelpers.classCallCheck(this, PullOperation);
	    this.grid = grid;
	  }
	  babelHelpers.createClass(PullOperation, [{
	    key: "setItemId",
	    value: function setItemId(itemId) {
	      this.itemId = itemId;
	      return this;
	    }
	  }, {
	    key: "getItemId",
	    value: function getItemId() {
	      return this.itemId;
	    }
	  }, {
	    key: "setAction",
	    value: function setAction(action) {
	      this.action = action;
	      return this;
	    }
	  }, {
	    key: "getAction",
	    value: function getAction() {
	      return this.action;
	    }
	  }, {
	    key: "setActionParams",
	    value: function setActionParams(actionParams) {
	      this.actionParams = actionParams;
	      return this;
	    }
	  }, {
	    key: "getActionParams",
	    value: function getActionParams() {
	      return this.actionParams;
	    }
	  }, {
	    key: "execute",
	    value: function execute() {
	      var action = this.getAction();
	      if (action === 'updateItem') {
	        this.updateItem();
	        return;
	      }
	      if (action === 'addItem') {
	        this.addItem();
	      }
	    }
	  }, {
	    key: "updateItem",
	    value: function updateItem() {
	      var _this$grid$itemMoving, _this$grid$itemMoving2, _this$grid$itemMoving3;
	      var params = this.getActionParams();
	      var item = this.grid.getItem(params.item.id);
	      var paramsItem = params.item;
	      if (!item) {
	        return;
	      }
	      var _this$grid$getData = this.grid.getData(),
	        viewMode = _this$grid$getData.viewMode;
	      if ([ViewMode.MODE_ACTIVITIES, ViewMode.MODE_DEADLINES].includes(viewMode)) {
	        item.useAnimation = false;
	        this.grid.insertItem(item);
	        return;
	      }
	      var insertItemParams = {};
	      var _paramsItem$data = paramsItem.data,
	        lastActivity = _paramsItem$data.lastActivity,
	        newColumnId = _paramsItem$data.columnId,
	        price = _paramsItem$data.price;
	      if (main_core.Type.isObjectLike(lastActivity) && lastActivity.timestamp !== item.data.lastActivity.timestamp) {
	        insertItemParams.canShowLastActivitySortTour = true;
	      }
	      var oldPrice = parseFloat(item.data.price);
	      var oldColumnId = item.columnId;
	      for (var key in paramsItem.data) {
	        if (key in item.data) {
	          item.data[key] = paramsItem.data[key];
	        }
	      }
	      item.rawData = paramsItem.rawData;
	      item.setActivityExistInnerHtml();
	      item.useAnimation = true;
	      item.setChangedInPullRequest();
	      this.grid.resetMultiSelectMode();
	      var newColumn = this.grid.getColumn(newColumnId);
	      var newPrice = parseFloat(price);
	      insertItemParams.newColumnId = newColumnId;
	      this.grid.insertItem(item, insertItemParams);
	      item.columnId = newColumnId;
	      if (!this.grid.getTypeInfoParam('showTotalPrice')) {
	        return;
	      }
	      if (oldColumnId === newColumnId) {
	        if (oldPrice < newPrice) {
	          newColumn.incPrice(newPrice - oldPrice);
	          newColumn.renderSubTitle();
	        } else if (oldPrice > newPrice) {
	          newColumn.decPrice(oldPrice - newPrice);
	          newColumn.renderSubTitle();
	        }
	        return;
	      }
	      var groupIds = (_this$grid$itemMoving = (_this$grid$itemMoving2 = this.grid.itemMoving) === null || _this$grid$itemMoving2 === void 0 ? void 0 : (_this$grid$itemMoving3 = _this$grid$itemMoving2.dropEvent) === null || _this$grid$itemMoving3 === void 0 ? void 0 : _this$grid$itemMoving3.groupIds) !== null && _this$grid$itemMoving !== void 0 ? _this$grid$itemMoving : [];
	      if (!groupIds.includes(item.id)) {
	        var oldColumn = this.grid.getColumn(oldColumnId);
	        oldColumn.decPrice(oldPrice);
	        oldColumn.renderSubTitle();
	      }
	      if (newColumn) {
	        newColumn.incPrice(newPrice);
	        newColumn.renderSubTitle();
	      }
	    }
	  }, {
	    key: "addItem",
	    value: function addItem() {
	      var params = this.getActionParams();
	      var oldItem = this.grid.getItem(params.item.id);
	      if (oldItem) {
	        return;
	      }
	      var column = this.grid.getColumn(params.item.data.columnId);
	      if (!column) {
	        return;
	      }
	      var sorter = crm_kanban_sort.Sorter.createWithCurrentSortType(column.getItems());
	      var beforeItem = sorter.calcBeforeItemByParams(params.item.data.sort);
	      if (beforeItem) {
	        params.item.targetId = beforeItem.getId();
	      }
	      this.grid.addItem(params.item);
	    }
	  }]);
	  return PullOperation;
	}();

	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var EventName = {
	  itemUpdated: 'ITEMUPDATED',
	  itemAdded: 'ITEMADDED',
	  itemDeleted: 'ITEMDELETED',
	  stageAdded: 'STAGEADDED',
	  stageUpdated: 'STAGEUPDATED',
	  stageDeleted: 'STAGEDELETED'
	};
	var _onBeforeQueueExecute = /*#__PURE__*/new WeakSet();
	var _onQueueExecute = /*#__PURE__*/new WeakSet();
	var _onReload = /*#__PURE__*/new WeakSet();
	var _onBeforePull = /*#__PURE__*/new WeakSet();
	var _onPull = /*#__PURE__*/new WeakSet();
	var _onPullItemUpdated = /*#__PURE__*/new WeakSet();
	var _onPullItemAdded = /*#__PURE__*/new WeakSet();
	var _getPullData = /*#__PURE__*/new WeakSet();
	var _onPullItemDeleted = /*#__PURE__*/new WeakSet();
	var _onPullStageChanged = /*#__PURE__*/new WeakSet();
	var _onPullStageDeleted = /*#__PURE__*/new WeakSet();
	var PullManager = function PullManager(_grid) {
	  var _data$additionalPullT,
	    _this = this;
	  babelHelpers.classCallCheck(this, PullManager);
	  _classPrivateMethodInitSpec$2(this, _onPullStageDeleted);
	  _classPrivateMethodInitSpec$2(this, _onPullStageChanged);
	  _classPrivateMethodInitSpec$2(this, _onPullItemDeleted);
	  _classPrivateMethodInitSpec$2(this, _getPullData);
	  _classPrivateMethodInitSpec$2(this, _onPullItemAdded);
	  _classPrivateMethodInitSpec$2(this, _onPullItemUpdated);
	  _classPrivateMethodInitSpec$2(this, _onPull);
	  _classPrivateMethodInitSpec$2(this, _onBeforePull);
	  _classPrivateMethodInitSpec$2(this, _onReload);
	  _classPrivateMethodInitSpec$2(this, _onQueueExecute);
	  _classPrivateMethodInitSpec$2(this, _onBeforeQueueExecute);
	  if (!BX.PULL) {
	    console.info('BX.PULL is not initialized');
	    return;
	  }
	  this.grid = _grid;
	  var _data = _grid.getData();
	  var _options = {
	    moduleId: _data.moduleId,
	    pullTag: _data.pullTag,
	    additionalPullTags: (_data$additionalPullT = _data.additionalPullTags) !== null && _data$additionalPullT !== void 0 ? _data$additionalPullT : [],
	    userId: _data.userId,
	    additionalData: {
	      viewMode: _data.viewMode
	    },
	    events: {
	      onBeforePull: function onBeforePull(event) {
	        _classPrivateMethodGet$2(_this, _onBeforePull, _onBeforePull2).call(_this, event);
	      },
	      onPull: function onPull(event) {
	        _classPrivateMethodGet$2(_this, _onPull, _onPull2).call(_this, event);
	      }
	    },
	    callbacks: {
	      onBeforeQueueExecute: function onBeforeQueueExecute(items) {
	        return _classPrivateMethodGet$2(_this, _onBeforeQueueExecute, _onBeforeQueueExecute2).call(_this, items);
	      },
	      onQueueExecute: function onQueueExecute(items) {
	        return _classPrivateMethodGet$2(_this, _onQueueExecute, _onQueueExecute2).call(_this, items);
	      },
	      onReload: function onReload() {
	        _classPrivateMethodGet$2(_this, _onReload, _onReload2).call(_this);
	      }
	    }
	  };
	  this.queueManager = new pull_queuemanager.QueueManager(_options);
	};
	function _onBeforeQueueExecute2(items) {
	  var _this2 = this;
	  items.forEach(function (item) {
	    var data = item.data;
	    var operation = PullOperation.createInstance({
	      grid: _this2.grid,
	      itemId: data.id,
	      action: data.action,
	      actionParams: data.actionParams
	    });
	    operation.execute(); // change to async and use Promise.all in return
	  });

	  return Promise.resolve();
	}
	function _onQueueExecute2(items) {
	  var ids = [];
	  items.forEach(function (_ref) {
	    var id = _ref.id,
	      action = _ref.data.action;
	    if (action === 'addItem' || action === 'updateItem') {
	      ids.push(parseInt(id, 10));
	    }
	  });
	  if (ids.length === 0) {
	    return Promise.resolve();
	  }
	  return this.grid.loadNew(ids, false, true, true, true);
	}
	function _onReload2() {
	  this.grid.reload();
	}
	function _onBeforePull2(event) {
	  var _event$data = event.data,
	    options = _event$data.options,
	    pullData = _event$data.pullData;
	  if (!pullData.command.startsWith(options.pullTag) && options.additionalData.viewMode !== ViewMode.MODE_ACTIVITIES) {
	    event.preventDefault();
	  }
	}
	function _onPull2(event) {
	  var params = event.data.pullData.params;
	  if (params.eventName === EventName.itemUpdated) {
	    _classPrivateMethodGet$2(this, _onPullItemUpdated, _onPullItemUpdated2).call(this, event);
	    return;
	  }
	  if (params.eventName === EventName.itemAdded) {
	    _classPrivateMethodGet$2(this, _onPullItemAdded, _onPullItemAdded2).call(this, event);
	    return;
	  }
	  if (params.eventName === EventName.itemDeleted) {
	    _classPrivateMethodGet$2(this, _onPullItemDeleted, _onPullItemDeleted2).call(this, event);
	    return;
	  }
	  if (params.eventName === EventName.stageAdded) {
	    _classPrivateMethodGet$2(this, _onPullStageChanged, _onPullStageChanged2).call(this, event);
	    return;
	  }
	  if (params.eventName === EventName.stageUpdated) {
	    _classPrivateMethodGet$2(this, _onPullStageChanged, _onPullStageChanged2).call(this, event);
	    return;
	  }
	  if (params.eventName === EventName.stageDeleted) {
	    _classPrivateMethodGet$2(this, _onPullStageDeleted, _onPullStageDeleted2).call(this, event);
	  }
	}
	function _onPullItemUpdated2(event) {
	  var _event$data2 = event.data,
	    params = _event$data2.pullData.params,
	    promises = _event$data2.promises;
	  var item = this.grid.getItem(params.item.id);
	  if (item) {
	    promises.push(Promise.resolve({
	      data: _classPrivateMethodGet$2(this, _getPullData, _getPullData2).call(this, 'updateItem', params)
	    }));
	    return;
	  }

	  // eslint-disable-next-line no-param-reassign
	  params.eventName = EventName.itemAdded;
	  _classPrivateMethodGet$2(this, _onPullItemAdded, _onPullItemAdded2).call(this, event);
	}
	function _onPullItemAdded2(event) {
	  var _event$data3 = event.data,
	    params = _event$data3.pullData.params,
	    promises = _event$data3.promises;
	  var itemId = params.item.id;
	  var oldItem = this.grid.getItem(itemId);
	  if (oldItem) {
	    event.preventDefault();
	    return;
	  }
	  promises.push(Promise.resolve({
	    data: _classPrivateMethodGet$2(this, _getPullData, _getPullData2).call(this, 'addItem', params)
	  }));
	}
	function _getPullData2(action, actionParams) {
	  var id = actionParams.item.id;
	  return {
	    id: id,
	    action: action,
	    actionParams: actionParams
	  };
	}
	function _onPullItemDeleted2(event) {
	  var _this3 = this;
	  var params = event.data.pullData.params;
	  if (!main_core.Type.isPlainObject(params.item)) {
	    return;
	  }
	  var _params$item = params.item,
	    id = _params$item.id,
	    columnId = _params$item.data.columnId;

	  /**
	   * Delay so that the element has time to be rendered before deletion,
	   * if an event for changing the element came before. Ticket #141983
	   */
	  var delay = this.queueManager.hasInQueue(id) ? this.queueManager.getLoadItemsDelay() : 0;
	  setTimeout(function () {
	    _this3.queueManager.deleteFromQueue(id);
	    var grid = _this3.grid;
	    var item = grid.getItem(id);
	    if (!item) {
	      return;
	    }
	    grid.removeItem(id);
	    if (grid.getTypeInfoParam('showTotalPrice')) {
	      var column = grid.getColumn(columnId);
	      column.decPrice(item.data.price);
	      column.renderSubTitle();
	    }
	  }, delay);
	  event.preventDefault();
	}
	function _onPullStageChanged2(event) {
	  event.preventDefault();
	  this.grid.onApplyFilter();
	}
	function _onPullStageDeleted2(event) {
	  event.preventDefault();
	  var params = event.data.pullData.params;
	  this.grid.removeColumn(params.stage.id);
	}

	exports.DeleteAction = DeleteAction;
	exports.SimpleAction = SimpleAction;
	exports.FieldsSelector = FieldsSelector;
	exports.PullManager = PullManager;
	exports.ViewMode = ViewMode;

}((this.BX.Crm.Kanban = this.BX.Crm.Kanban || {}),BX.Crm.Integration.Analytics,BX,BX.Main,BX.Event,BX.Pull,BX.CRM.Kanban,BX));
//# sourceMappingURL=kanban.js.map
