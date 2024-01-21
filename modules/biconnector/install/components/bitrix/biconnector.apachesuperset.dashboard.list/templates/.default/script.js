/* eslint-disable */
(function (exports,main_core,biconnector_apacheSupersetDashboardManager,main_core_events,ui_dialogs_messagebox,biconnector_apacheSupersetAnalytics) {
	'use strict';

	var _templateObject;
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
	var SupersetDashboardGridManager = /*#__PURE__*/function () {
	  function SupersetDashboardGridManager(props) {
	    var _BX$Main$gridManager$;
	    babelHelpers.classCallCheck(this, SupersetDashboardGridManager);
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
	    key: "onUserCredentialsLoaded",
	    value: function onUserCredentialsLoaded() {
	      this.getGrid().tableUnfade();
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
	        var _response$errors$;
	        grid.tableUnfade();
	        if (response.errors && main_core.Type.isStringFilled((_response$errors$ = response.errors[0]) === null || _response$errors$ === void 0 ? void 0 : _response$errors$.message)) {
	          BX.UI.Notification.Center.notify({
	            content: main_core.Text.encode(response.errors[0].message)
	          });
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
	      var _this2 = this;
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_TITLE'), function (messageBox, button) {
	        button.setWaiting();
	        babelHelpers.classPrivateFieldGet(_this2, _dashboardManager).deleteDashboard(dashboardId).then(function () {
	          _this2.getGrid().reload();
	          messageBox.close();
	        })["catch"](function (response) {
	          var _response$errors$2;
	          messageBox.close();
	          if (response.errors && main_core.Type.isStringFilled((_response$errors$2 = response.errors[0]) === null || _response$errors$2 === void 0 ? void 0 : _response$errors$2.message)) {
	            BX.UI.Notification.Center.notify({
	              content: main_core.Text.encode(response.errors[0].message)
	            });
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
	  }]);
	  return SupersetDashboardGridManager;
	}();
	function _subscribeToEvents2() {
	  var _this3 = this;
	  main_core_events.EventEmitter.subscribe('BiConnector:DashboardManager.onUserCredentialsLoaded', this.onUserCredentialsLoaded.bind(this));
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	    var _event$getCompatData = event.getCompatData(),
	      _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	      sliderEvent = _event$getCompatData2[0];
	    if (sliderEvent.getEventId() === 'BIConnector.Superset.DashboardDetail:onDashboardBatchStatusUpdate') {
	      var eventArgs = sliderEvent.getData();
	      if (eventArgs.dashboardList) {
	        _this3.onUpdatedDashboardBatchStatus(eventArgs.dashboardList);
	      }
	    }
	  });
	  main_core_events.EventEmitter.subscribe('BIConnector.Superset.DashboardManager:onDashboardBatchStatusUpdate', function (event) {
	    var data = event.getData();
	    if (!data.dashboardList) {
	      return;
	    }
	    var dashboardList = data.dashboardList;
	    _this3.onUpdatedDashboardBatchStatus(dashboardList);
	  });
	  main_core_events.EventEmitter.subscribe('BX.Rest.Configuration.Install:onFinish', function () {
	    babelHelpers.classPrivateFieldGet(_this3, _grid).reload();
	  });
	}
	main_core.Reflection.namespace('BX.BIConnector').SupersetDashboardGridManager = SupersetDashboardGridManager;

}((this.window = this.window || {}),BX,BX.BIConnector,BX.Event,BX.UI.Dialogs,BX.BIConnector));
//# sourceMappingURL=script.js.map
