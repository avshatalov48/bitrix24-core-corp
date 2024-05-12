/* eslint-disable */
(function (exports,main_core,main_date,biconnector_apacheSupersetDashboardManager,main_core_events,ui_dialogs_messagebox,biconnector_apacheSupersetAnalytics) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7;
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _dashboardManager = /*#__PURE__*/new WeakMap();
	var _grid = /*#__PURE__*/new WeakMap();
	var _subscribeToEvents = /*#__PURE__*/new WeakSet();
	var _notifyErrors = /*#__PURE__*/new WeakSet();
	var _buildDashboardTitleEditor = /*#__PURE__*/new WeakSet();
	var _getTitlePreview = /*#__PURE__*/new WeakSet();
	var _setDateModifyNow = /*#__PURE__*/new WeakSet();
	var SupersetDashboardGridManager = /*#__PURE__*/function () {
	  function SupersetDashboardGridManager(props) {
	    var _BX$Main$gridManager$;
	    babelHelpers.classCallCheck(this, SupersetDashboardGridManager);
	    _classPrivateMethodInitSpec(this, _setDateModifyNow);
	    _classPrivateMethodInitSpec(this, _getTitlePreview);
	    _classPrivateMethodInitSpec(this, _buildDashboardTitleEditor);
	    _classPrivateMethodInitSpec(this, _notifyErrors);
	    _classPrivateMethodInitSpec(this, _subscribeToEvents);
	    _classPrivateFieldInitSpec(this, _dashboardManager, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _dashboardManager, new biconnector_apacheSupersetDashboardManager.DashboardManager());
	    babelHelpers.classPrivateFieldSet(this, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(props.gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	    _classPrivateMethodGet(this, _subscribeToEvents, _subscribeToEvents2).call(this);
	  }
	  babelHelpers.createClass(SupersetDashboardGridManager, [{
	    key: "onUpdatedDashboardBatchStatus",
	    value: function onUpdatedDashboardBatchStatus(dashboardList) {
	      var _iterator = _createForOfIteratorHelper(dashboardList),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var dashboard = _step.value;
	          this.updateDashboardStatus(dashboard.id, dashboard.status);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }
	  }, {
	    key: "getGrid",
	    value: function getGrid() {
	      return babelHelpers.classPrivateFieldGet(this, _grid);
	    }
	    /**
	     * @param params LoginPopupParams
	     * @param openedFrom
	     */
	  }, {
	    key: "showLoginPopup",
	    value: function showLoginPopup(params) {
	      var openedFrom = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'unknown';
	      var grid = this.getGrid();
	      if (params.type === 'CUSTOM') {
	        grid.tableFade();
	      }
	      babelHelpers.classPrivateFieldGet(this, _dashboardManager).processEditDashboard({
	        id: params.dashboardId,
	        type: params.type,
	        editLink: params.editUrl
	      }, function () {
	        grid.tableUnfade();
	      }, function (popupType) {
	        biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('edit', 'report_edit', {
	          c_sub_section: popupType,
	          c_element: openedFrom,
	          type: params.type.toLowerCase(),
	          p1: biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(params.appId),
	          p2: params.dashboardId,
	          status: 'success'
	        });
	      }, function (popupType) {
	        biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('edit', 'report_edit', {
	          c_sub_section: popupType,
	          c_element: openedFrom,
	          type: params.type.toLowerCase(),
	          p1: biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(params.appId),
	          p2: params.dashboardId,
	          status: 'error'
	        });
	      });
	    }
	  }, {
	    key: "restartDashboardLoad",
	    value: function restartDashboardLoad(dashboardId) {
	      var _this = this;
	      var row = babelHelpers.classPrivateFieldGet(this, _grid).getRows().getById(dashboardId);
	      if (row) {
	        var btn = row.node.querySelector('#restart-dashboard-load-btn');
	        if (main_core.Type.isDomNode(btn)) {
	          var isDisabled = btn.getAttribute('disabled');
	          if (isDisabled) {
	            return;
	          }
	          btn.setAttribute('disabled', 'true');
	          main_core.Dom.addClass(btn, 'dashboard-status-label-error-btn__loading');
	        }
	      }
	      babelHelpers.classPrivateFieldGet(this, _dashboardManager).restartDashboardImport(dashboardId).then(function (response) {
	        var _response$data;
	        var dashboardIds = response === null || response === void 0 ? void 0 : (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.restartedDashboardIds;
	        if (!dashboardIds) {
	          return;
	        }
	        var _iterator2 = _createForOfIteratorHelper(dashboardIds),
	          _step2;
	        try {
	          for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	            var restartedDashboardId = _step2.value;
	            _this.updateDashboardStatus(restartedDashboardId, 'L');
	          }
	        } catch (err) {
	          _iterator2.e(err);
	        } finally {
	          _iterator2.f();
	        }
	      });
	    }
	  }, {
	    key: "setDashboardStatusReady",
	    value: function setDashboardStatusReady(dashboardId) {
	      var row = babelHelpers.classPrivateFieldGet(this, _grid).getRows().getById(dashboardId);
	      if (row) {
	        var label = row.node.getElementsByClassName('dashboard-status-label')[0];
	        main_core.Dom.addClass(label, 'ui-label-success');
	        main_core.Dom.removeClass(label, 'ui-label-primary');
	        label.querySelector('span').innerText = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_READY');
	      }
	    }
	  }, {
	    key: "updateDashboardStatus",
	    value: function updateDashboardStatus(dashboardId, status) {
	      var row = babelHelpers.classPrivateFieldGet(this, _grid).getRows().getById(dashboardId);
	      if (row) {
	        var labelWrapper = row.node.querySelector('.dashboard-status-label-wrapper');
	        var label = labelWrapper.querySelector('.dashboard-status-label');
	        var reloadBtn = labelWrapper.querySelector('#restart-dashboard-load-btn');
	        switch (status) {
	          case biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_READY:
	            if (reloadBtn) {
	              reloadBtn.remove();
	            }
	            main_core.Dom.addClass(label, 'ui-label-success');
	            main_core.Dom.removeClass(label, 'ui-label-primary');
	            main_core.Dom.removeClass(label, 'ui-label-danger');
	            label.querySelector('span').innerText = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_READY');
	            break;
	          case biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_LOAD:
	            if (reloadBtn) {
	              reloadBtn.remove();
	            }
	            main_core.Dom.addClass(label, 'ui-label-primary');
	            main_core.Dom.removeClass(label, 'ui-label-success');
	            main_core.Dom.removeClass(label, 'ui-label-danger');
	            label.querySelector('span').innerText = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_LOAD');
	            break;
	          case biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_FAILED:
	            if (!reloadBtn) {
	              var createdReloadBtn = this.createReloadBtn(dashboardId);
	              main_core.Dom.append(createdReloadBtn, labelWrapper);
	            }
	            main_core.Dom.addClass(label, 'ui-label-danger');
	            main_core.Dom.removeClass(label, 'ui-label-success');
	            main_core.Dom.removeClass(label, 'ui-label-primary');
	            label.querySelector('span').innerText = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_FAILED');
	            break;
	        }
	      }
	    }
	  }, {
	    key: "createReloadBtn",
	    value: function createReloadBtn(dashboardId) {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"restart-dashboard-load-btn\" onclick=\"BX.BIConnector.SupersetDashboardGridManager.Instance.restartDashboardLoad(", ")\" class=\"dashboard-status-label-error-btn\">\n\t\t\t\t<div class=\"ui-icon-set --refresh-5 dashboard-status-label-error-icon\"></div>\n\t\t\t</div>\n\t\t"])), dashboardId);
	    }
	  }, {
	    key: "duplicateDashboard",
	    value: function duplicateDashboard(dashboardId) {
	      var _this2 = this;
	      var analyticInfo = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var grid = this.getGrid();
	      grid.tableFade();
	      return babelHelpers.classPrivateFieldGet(this, _dashboardManager).duplicateDashboard(dashboardId).then(function (response) {
	        var gridRealtime = grid.getRealtime();
	        var newDashboard = response.data.dashboard;
	        gridRealtime.addRow({
	          id: newDashboard.id,
	          prepend: true,
	          columns: newDashboard.columns,
	          actions: newDashboard.actions
	        });
	        var editableData = grid.getParam('EDITABLE_DATA');
	        if (BX.type.isPlainObject(editableData)) {
	          editableData[newDashboard.id] = {
	            TITLE: newDashboard.title
	          };
	        }
	        grid.tableUnfade();
	        var counterTotalTextContainer = grid.getCounterTotal().querySelector('.main-grid-panel-content-text');
	        counterTotalTextContainer.textContent++;
	        BX.UI.Hint.init(BX('biconnector-dashboard-grid'));
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_COPY_NOTIFICATION_ADDED')
	        });
	        if (analyticInfo !== null) {
	          biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('edit', 'report_copy', {
	            type: analyticInfo.type,
	            p1: biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(analyticInfo.appId),
	            p2: dashboardId,
	            status: 'success',
	            c_element: analyticInfo.from
	          });
	        }
	      })["catch"](function (response) {
	        grid.tableUnfade();
	        if (response.errors) {
	          _classPrivateMethodGet(_this2, _notifyErrors, _notifyErrors2).call(_this2, response.errors);
	        }
	        if (analyticInfo !== null) {
	          biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('edit', 'report_copy', {
	            type: analyticInfo.type,
	            p1: biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(analyticInfo.appId),
	            p2: dashboardId,
	            status: 'error',
	            c_element: analyticInfo.from
	          });
	        }
	      });
	    }
	  }, {
	    key: "exportDashboard",
	    value: function exportDashboard(dashboardId) {
	      var analyticInfo = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var grid = this.getGrid();
	      grid.tableFade();
	      return babelHelpers.classPrivateFieldGet(this, _dashboardManager).exportDashboard(dashboardId, function () {
	        grid.tableUnfade();
	        if (analyticInfo !== null) {
	          biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('edit', 'report_export', {
	            type: analyticInfo.type,
	            p1: biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(analyticInfo.appId),
	            p2: dashboardId,
	            status: 'success',
	            c_element: analyticInfo.from
	          });
	        }
	      }, function () {
	        grid.tableUnfade();
	        if (analyticInfo !== null) {
	          biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('edit', 'report_export', {
	            type: analyticInfo.type,
	            p1: biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(analyticInfo.appId),
	            p2: dashboardId,
	            status: 'error',
	            c_element: analyticInfo.from
	          });
	        }
	      });
	    }
	  }, {
	    key: "deleteDashboard",
	    value: function deleteDashboard(dashboardId) {
	      var _this3 = this;
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_TITLE'), function (messageBox, button) {
	        button.setWaiting();
	        babelHelpers.classPrivateFieldGet(_this3, _dashboardManager).deleteDashboard(dashboardId).then(function () {
	          _this3.getGrid().reload();
	          messageBox.close();
	        })["catch"](function (response) {
	          messageBox.close();
	          if (response.errors) {
	            _classPrivateMethodGet(_this3, _notifyErrors, _notifyErrors2).call(_this3, response.errors);
	          }
	        });
	      }, main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_CAPTION_YES'), function (messageBox) {
	        return messageBox.close();
	      }, main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_CAPTION_NO'));
	    }
	  }, {
	    key: "createEmptyDashboard",
	    value: function createEmptyDashboard() {
	      BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('new', 'report_new', {
	        type: 'custom',
	        c_element: 'new_button'
	      });
	      var grid = this.getGrid();
	      grid.tableFade();
	      babelHelpers.classPrivateFieldGet(this, _dashboardManager).createEmptyDashboard().then(function (response) {
	        grid.tableUnfade();
	        var gridRealtime = grid.getRealtime();
	        var newDashboard = response.data.dashboard;
	        gridRealtime.addRow({
	          id: newDashboard.id,
	          prepend: true,
	          columns: newDashboard.columns,
	          actions: newDashboard.actions
	        });
	        var editableData = grid.getParam('EDITABLE_DATA');
	        if (BX.type.isPlainObject(editableData)) {
	          editableData[newDashboard.id] = {
	            TITLE: newDashboard.title
	          };
	        }
	        grid.tableUnfade();
	        var counterTotalTextContainer = grid.getCounterTotal().querySelector('.main-grid-panel-content-text');
	        counterTotalTextContainer.textContent++;
	      })["catch"](function (response) {
	        grid.tableUnfade();
	        if (response.errors) {
	          BX.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_CREATE_EMPTY_NOTIFICATION_ERROR')
	          });
	        }
	      });
	    }
	  }, {
	    key: "renameDashboard",
	    value: function renameDashboard(dashboardId) {
	      var _row$getCellById,
	        _this4 = this,
	        _row$getCellById2;
	      var grid = this.getGrid();
	      var row = grid.getRows().getById(dashboardId);
	      if (!row) {
	        return;
	      }
	      var rowNode = row.getNode();
	      main_core.Dom.removeClass(rowNode, 'dashboard-title-edited');
	      var wrapper = (_row$getCellById = row.getCellById('TITLE')) === null || _row$getCellById === void 0 ? void 0 : _row$getCellById.querySelector('.dashboard-title-wrapper');
	      if (!wrapper) {
	        return;
	      }
	      var editor = _classPrivateMethodGet(this, _buildDashboardTitleEditor, _buildDashboardTitleEditor2).call(this, dashboardId, row.getEditData().TITLE, function () {
	        _this4.cancelRenameDashboard(dashboardId);
	      }, function (innerTitle) {
	        var oldTitle = _classPrivateMethodGet(_this4, _getTitlePreview, _getTitlePreview2).call(_this4, dashboardId).querySelector('a').innerText;
	        _classPrivateMethodGet(_this4, _getTitlePreview, _getTitlePreview2).call(_this4, dashboardId).querySelector('a').innerText = innerTitle;
	        var rowEditData = row.getEditData();
	        rowEditData.TITLE = innerTitle;
	        var editableData = grid.getParam('EDITABLE_DATA');
	        if (BX.type.isPlainObject(editableData)) {
	          editableData[row.getId()] = rowEditData;
	        }
	        main_core.Dom.addClass(rowNode, 'dashboard-title-edited');
	        var msg = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_CHANGE_TITLE_SUCCESS', {
	          '#NEW_TITLE#': main_core.Text.encode(innerTitle)
	        });
	        BX.UI.Notification.Center.notify({
	          content: msg
	        });
	        _this4.cancelRenameDashboard(dashboardId);
	        _classPrivateMethodGet(_this4, _setDateModifyNow, _setDateModifyNow2).call(_this4, dashboardId);
	        babelHelpers.classPrivateFieldGet(_this4, _dashboardManager).renameDashboard(dashboardId, innerTitle)["catch"](function (response) {
	          if (response.errors) {
	            _classPrivateMethodGet(_this4, _notifyErrors, _notifyErrors2).call(_this4, response.errors);
	          }
	          _classPrivateMethodGet(_this4, _getTitlePreview, _getTitlePreview2).call(_this4, dashboardId).querySelector('a').innerText = oldTitle;
	          rowEditData.TITLE = oldTitle;
	        });
	      });
	      var preview = wrapper.querySelector('.dashboard-title-preview');
	      if (preview) {
	        main_core.Dom.style(preview, 'display', 'none');
	      }
	      main_core.Dom.append(editor, wrapper);
	      var editBtn = (_row$getCellById2 = row.getCellById('EDIT_URL')) === null || _row$getCellById2 === void 0 ? void 0 : _row$getCellById2.querySelector('a');
	      var actionsClickHandler = function actionsClickHandler() {
	        main_core.Event.unbind(row.getActionsButton(), 'click', actionsClickHandler);
	        if (editBtn) {
	          main_core.Event.unbind(editBtn, 'click', actionsClickHandler);
	        }
	        _this4.cancelRenameDashboard(dashboardId);
	      };
	      main_core.Event.bind(row.getActionsButton(), 'click', actionsClickHandler);
	      if (editBtn) {
	        main_core.Event.bind(editBtn, 'click', actionsClickHandler);
	      }
	    }
	  }, {
	    key: "cancelRenameDashboard",
	    value: function cancelRenameDashboard(dashboardId) {
	      var _row$getCellById3, _row$getCellById4;
	      var row = this.getGrid().getRows().getById(dashboardId);
	      if (!row) {
	        return;
	      }
	      var editSection = (_row$getCellById3 = row.getCellById('TITLE')) === null || _row$getCellById3 === void 0 ? void 0 : _row$getCellById3.querySelector('.dashboard-title-edit');
	      var previewSection = (_row$getCellById4 = row.getCellById('TITLE')) === null || _row$getCellById4 === void 0 ? void 0 : _row$getCellById4.querySelector('.dashboard-title-preview');
	      if (editSection) {
	        main_core.Dom.remove(editSection);
	      }
	      if (previewSection) {
	        main_core.Dom.style(previewSection, 'display', 'flex');
	      }
	    }
	  }]);
	  return SupersetDashboardGridManager;
	}();
	function _subscribeToEvents2() {
	  var _this5 = this;
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	    var _event$getCompatData = event.getCompatData(),
	      _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	      sliderEvent = _event$getCompatData2[0];
	    if (sliderEvent.getEventId() === 'BIConnector.Superset.DashboardDetail:onDashboardBatchStatusUpdate') {
	      var eventArgs = sliderEvent.getData();
	      if (eventArgs.dashboardList) {
	        _this5.onUpdatedDashboardBatchStatus(eventArgs.dashboardList);
	      }
	    }
	  });
	  main_core_events.EventEmitter.subscribe('BIConnector.Superset.DashboardManager:onDashboardBatchStatusUpdate', function (event) {
	    var data = event.getData();
	    if (!data.dashboardList) {
	      return;
	    }
	    var dashboardList = data.dashboardList;
	    _this5.onUpdatedDashboardBatchStatus(dashboardList);
	  });
	  main_core_events.EventEmitter.subscribe('BX.Rest.Configuration.Install:onFinish', function () {
	    babelHelpers.classPrivateFieldGet(_this5, _grid).reload();
	  });
	}
	function _notifyErrors2(errors) {
	  if (errors[0] && errors[0].message) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(errors[0].message)
	    });
	  }
	}
	function _buildDashboardTitleEditor2(id, title, onCancel, onSave) {
	  var input = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input class=\"main-grid-editor main-grid-editor-text\" type=\"text\">\n\t\t"])));
	  input.value = title;
	  var saveInputValue = function saveInputValue() {
	    var value = input.value;
	    main_core.Dom.removeClass(input, 'dashboard-title-input-danger');
	    if (value.trim() === '') {
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_CHANGE_TITLE_ERROR_EMPTY')
	      });
	      main_core.Dom.addClass(input, 'dashboard-title-input-danger');
	      return;
	    }
	    main_core.Dom.style(buttons, 'display', 'none');
	    main_core.Dom.attr(input, 'disabled', true);
	    onSave(input.value);
	  };
	  main_core.Event.bind(input, 'keydown', function (event) {
	    if (event.keyCode === 13) {
	      saveInputValue();
	      event.preventDefault();
	    } else if (event.keyCode === 27) {
	      onCancel();
	      event.preventDefault();
	    }
	  });
	  var applyButton = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a>\n\t\t\t\t<i\n\t\t\t\t\tclass=\"ui-icon-set --check\"\n\t\t\t\t\tstyle=\"--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);\"\n\t\t\t\t></i>\n\t\t\t</a>\n\t\t"])));
	  var cancelButton = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a>\n\t\t\t\t<i\n\t\t\t\t\tclass=\"ui-icon-set --cross-60\"\n\t\t\t\t\tstyle=\"--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);\"\n\t\t\t\t></i>\n\t\t\t</a>\n\t\t"])));
	  var buttons = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"dashboard-title-wrapper__buttons\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), applyButton, cancelButton);
	  main_core.Event.bind(cancelButton, 'click', function () {
	    onCancel();
	  });
	  main_core.Event.bind(applyButton, 'click', saveInputValue);
	  return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"dashboard-title-wrapper__item dashboard-title-edit\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"dashboard-title-wrapper__buttons-wrapper\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), input, buttons);
	}
	function _getTitlePreview2(dashboardId) {
	  var _row$getCellById5;
	  var grid = this.getGrid();
	  var row = grid.getRows().getById(dashboardId);
	  if (!row) {
	    return null;
	  }
	  var wrapper = (_row$getCellById5 = row.getCellById('TITLE')) === null || _row$getCellById5 === void 0 ? void 0 : _row$getCellById5.querySelector('.dashboard-title-wrapper');
	  if (!wrapper) {
	    return null;
	  }
	  var previewSection = wrapper.querySelector('.dashboard-title-preview');
	  if (previewSection) {
	    return previewSection;
	  }
	  return null;
	}
	function _setDateModifyNow2(dashboardId) {
	  var _babelHelpers$classPr;
	  var dateModifyCell = (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _grid).getRows().getById(dashboardId)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.getCellById('DATE_MODIFY');
	  if (!dateModifyCell) {
	    return;
	  }
	  var cellContent = dateModifyCell.querySelector('.main-grid-cell-content span');
	  var date = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('FORMAT_DATETIME'), Math.floor(Date.now() / 1000));
	  var readableDate = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DATE_MODIFY_NOW');
	  var newCellContent = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span data-hint=\"", "\" data-hint-no-icon data-hint-interactivity>", "</span>\n\t\t"])), date, readableDate);
	  main_core.Dom.replace(cellContent, newCellContent);
	  BX.UI.Hint.init(dateModifyCell);
	}
	main_core.Reflection.namespace('BX.BIConnector').SupersetDashboardGridManager = SupersetDashboardGridManager;

}((this.window = this.window || {}),BX,BX.Main,BX.BIConnector,BX.Event,BX.UI.Dialogs,BX.BIConnector));
//# sourceMappingURL=script.js.map
