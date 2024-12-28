/* eslint-disable */
(function (exports,main_core,main_date,main_popup,biconnector_apacheSupersetDashboardManager,main_core_events,ui_dialogs_messagebox,biconnector_apacheSupersetAnalytics,ui_entitySelector,ui_tour,spotlight,biconnector_entitySelector) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _dashboardManager = /*#__PURE__*/new WeakMap();
	var _grid = /*#__PURE__*/new WeakMap();
	var _filter = /*#__PURE__*/new WeakMap();
	var _tagSelectorDialog = /*#__PURE__*/new WeakMap();
	var _topMenuGuideSpotlight = /*#__PURE__*/new WeakMap();
	var _lastPinnedRowId = /*#__PURE__*/new WeakMap();
	var _properties = /*#__PURE__*/new WeakMap();
	var _subscribeToEvents = /*#__PURE__*/new WeakSet();
	var _initHints = /*#__PURE__*/new WeakSet();
	var _onSupersetStatusChange = /*#__PURE__*/new WeakSet();
	var _showTopMenuGuide = /*#__PURE__*/new WeakSet();
	var _showDraftGuide = /*#__PURE__*/new WeakSet();
	var _colorPinnedRows = /*#__PURE__*/new WeakSet();
	var _notifyErrors = /*#__PURE__*/new WeakSet();
	var _onNewDashboardCreated = /*#__PURE__*/new WeakSet();
	var _buildDashboardTitleEditor = /*#__PURE__*/new WeakSet();
	var _getTitlePreview = /*#__PURE__*/new WeakSet();
	var _setDateModifyNow = /*#__PURE__*/new WeakSet();
	var _switchTopMenuAction = /*#__PURE__*/new WeakSet();
	/**
	 * @namespace BX.BIConnector
	 */
	var SupersetDashboardGridManager = /*#__PURE__*/function () {
	  function SupersetDashboardGridManager(props) {
	    var _BX$Main$gridManager$,
	      _this = this;
	    babelHelpers.classCallCheck(this, SupersetDashboardGridManager);
	    _classPrivateMethodInitSpec(this, _switchTopMenuAction);
	    _classPrivateMethodInitSpec(this, _setDateModifyNow);
	    _classPrivateMethodInitSpec(this, _getTitlePreview);
	    _classPrivateMethodInitSpec(this, _buildDashboardTitleEditor);
	    _classPrivateMethodInitSpec(this, _onNewDashboardCreated);
	    _classPrivateMethodInitSpec(this, _notifyErrors);
	    _classPrivateMethodInitSpec(this, _colorPinnedRows);
	    _classPrivateMethodInitSpec(this, _showDraftGuide);
	    _classPrivateMethodInitSpec(this, _showTopMenuGuide);
	    _classPrivateMethodInitSpec(this, _onSupersetStatusChange);
	    _classPrivateMethodInitSpec(this, _initHints);
	    _classPrivateMethodInitSpec(this, _subscribeToEvents);
	    _classPrivateFieldInitSpec(this, _dashboardManager, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _filter, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _tagSelectorDialog, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _topMenuGuideSpotlight, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _lastPinnedRowId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _properties, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _dashboardManager, new biconnector_apacheSupersetDashboardManager.DashboardManager());
	    babelHelpers.classPrivateFieldSet(this, _properties, props);
	    babelHelpers.classPrivateFieldSet(this, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(props.gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	    babelHelpers.classPrivateFieldSet(this, _filter, BX.Main.filterManager.getById(props.gridId));
	    _classPrivateMethodGet(this, _subscribeToEvents, _subscribeToEvents2).call(this);
	    if (babelHelpers.classPrivateFieldGet(this, _properties).isNeedShowTopMenuGuide && !(main_popup.PopupWindowManager !== null && main_popup.PopupWindowManager !== void 0 && main_popup.PopupWindowManager.isAnyPopupShown()) && babelHelpers.classPrivateFieldGet(this, _grid).getRows().getBodyFirstChild().actionsButton) {
	      babelHelpers.classPrivateFieldSet(this, _topMenuGuideSpotlight, new BX.SpotLight({
	        targetElement: babelHelpers.classPrivateFieldGet(this, _grid).getRows().getBodyFirstChild().actionsButton,
	        targetVertex: 'middle-center',
	        events: {
	          onTargetEnter: function onTargetEnter() {
	            return babelHelpers.classPrivateFieldGet(_this, _topMenuGuideSpotlight).close();
	          }
	        }
	      }));
	      babelHelpers.classPrivateFieldGet(this, _topMenuGuideSpotlight).show();
	      _classPrivateMethodGet(this, _showTopMenuGuide, _showTopMenuGuide2).call(this);
	    }
	    _classPrivateMethodGet(this, _colorPinnedRows, _colorPinnedRows2).call(this);
	    _classPrivateMethodGet(this, _initHints, _initHints2).call(this);
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
	  }, {
	    key: "getFilter",
	    value: function getFilter() {
	      return babelHelpers.classPrivateFieldGet(this, _filter);
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
	    key: "showLockedByParamsPopup",
	    value: function showLockedByParamsPopup() {
	      ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_LOCKED_PARAM_DASHBOARD_OPEN_DESCRIPTION'), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_LOCKED_PARAM_DASHBOARD_OPEN_TITLE'), function (messageBox) {
	        messageBox.close();
	      }, main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_LOCKED_PARAM_DASHBOARD_CLOSE_BUTTON'));
	    }
	  }, {
	    key: "restartDashboardLoad",
	    value: function restartDashboardLoad(dashboardId) {
	      var _this2 = this;
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
	            _this2.updateDashboardStatus(restartedDashboardId, biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_LOAD);
	          }
	        } catch (err) {
	          _iterator2.e(err);
	        } finally {
	          _iterator2.f();
	        }
	      })["catch"]();
	    }
	  }, {
	    key: "updateDashboardStatus",
	    value: function updateDashboardStatus(dashboardId, status) {
	      var row = babelHelpers.classPrivateFieldGet(this, _grid).getRows().getById(dashboardId);
	      if (row) {
	        var labelWrapper = row.node.querySelector('.dashboard-status-label-wrapper');
	        var label = labelWrapper.querySelector('.dashboard-status-label');
	        var reloadBtn = labelWrapper.querySelector('#restart-dashboard-load-btn');
	        var labelClass = '';
	        var labelTitle = '';
	        switch (status) {
	          case biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_READY:
	            labelClass = 'ui-label-lightgreen';
	            labelTitle = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_READY');
	            break;
	          case biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_DRAFT:
	            labelClass = 'ui-label-default';
	            labelTitle = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_DRAFT');
	            break;
	          case biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_LOAD:
	            labelClass = 'ui-label-primary';
	            labelTitle = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_LOAD');
	            break;
	          case biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_FAILED:
	            labelClass = 'ui-label-danger';
	            labelTitle = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_FAILED');
	            break;
	          case biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_COMPUTED_NOT_LOAD:
	            labelClass = 'ui-label-danger';
	            labelTitle = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_NOT_LOAD');
	            break;
	        }
	        if (labelClass === '') {
	          return;
	        }
	        if (reloadBtn) {
	          reloadBtn.remove();
	        }
	        if (status === biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_FAILED) {
	          var createdReloadBtn = this.createReloadBtn(dashboardId);
	          main_core.Dom.append(createdReloadBtn, labelWrapper);
	        }
	        var labelStatuses = ['ui-label-lightgreen', 'ui-label-default', 'ui-label-primary', 'ui-label-danger'];
	        main_core.Dom.addClass(label, labelClass);
	        labelStatuses.forEach(function (uiStatus) {
	          if (uiStatus !== labelClass) {
	            main_core.Dom.removeClass(label, uiStatus);
	          }
	        });
	        label.querySelector('span').innerText = labelTitle;
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
	      var _this3 = this;
	      var analyticInfo = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var grid = this.getGrid();
	      grid.tableFade();
	      return babelHelpers.classPrivateFieldGet(this, _dashboardManager).duplicateDashboard(dashboardId).then(function (response) {
	        var gridRealtime = grid.getRealtime();
	        var newDashboard = response.data.dashboard;
	        gridRealtime.addRow({
	          id: newDashboard.id,
	          columns: newDashboard.columns,
	          actions: newDashboard.actions,
	          insertAfter: babelHelpers.classPrivateFieldGet(_this3, _lastPinnedRowId)
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
	        _classPrivateMethodGet(_this3, _initHints, _initHints2).call(_this3);
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
	          _classPrivateMethodGet(_this3, _notifyErrors, _notifyErrors2).call(_this3, response.errors);
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
	      var grid = this.getGrid();
	      grid.tableFade();
	      return babelHelpers.classPrivateFieldGet(this, _dashboardManager).exportDashboard(dashboardId, 'grid_menu');
	    }
	  }, {
	    key: "publish",
	    value: function publish(dashboardId) {
	      var _this4 = this;
	      babelHelpers.classPrivateFieldGet(this, _dashboardManager).toggleDraft(dashboardId, true).then(function () {
	        _this4.updateDashboardStatus(dashboardId, biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_READY);
	        babelHelpers.classPrivateFieldGet(_this4, _grid).updateRow(dashboardId);
	      })["catch"](function () {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_PUBLISH_NOTIFICATION_ERROR')
	        });
	      });
	    }
	  }, {
	    key: "setDraft",
	    value: function setDraft(dashboardId) {
	      var _this5 = this;
	      babelHelpers.classPrivateFieldGet(this, _dashboardManager).toggleDraft(dashboardId, false).then(function () {
	        _this5.updateDashboardStatus(dashboardId, biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_DRAFT);
	        babelHelpers.classPrivateFieldGet(_this5, _grid).updateRow(dashboardId, null, null, function (result) {
	          _classPrivateMethodGet(_this5, _showDraftGuide, _showDraftGuide2).call(_this5, babelHelpers.classPrivateFieldGet(_this5, _grid).getRows().getById(dashboardId).node);
	        });
	      })["catch"](function () {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_SET_DRAFT_NOTIFICATION_ERROR')
	        });
	      });
	    }
	  }, {
	    key: "deleteDashboard",
	    value: function deleteDashboard(dashboardId) {
	      var _this6 = this;
	      var messageBox = new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_TITLE'),
	        buttons: [new BX.UI.Button({
	          color: BX.UI.Button.Color.DANGER,
	          text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_CAPTION_YES'),
	          onclick: function onclick(button) {
	            button.setWaiting();
	            babelHelpers.classPrivateFieldGet(_this6, _dashboardManager).deleteDashboard(dashboardId).then(function () {
	              _this6.getGrid().reload();
	              messageBox.close();
	            })["catch"](function (response) {
	              messageBox.close();
	              if (response.errors) {
	                _classPrivateMethodGet(_this6, _notifyErrors, _notifyErrors2).call(_this6, response.errors);
	              }
	            });
	          }
	        }), new BX.UI.CancelButton({
	          text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_POPUP_CAPTION_NO'),
	          onclick: function onclick(button) {
	            return messageBox.close();
	          }
	        })]
	      });
	      messageBox.show();
	    }
	  }, {
	    key: "openCreationSlider",
	    value: function openCreationSlider() {
	      babelHelpers.classPrivateFieldGet(this, _dashboardManager).openCreationSlider();
	    }
	  }, {
	    key: "renameDashboard",
	    value: function renameDashboard(dashboardId) {
	      var _row$getCellById,
	        _this7 = this,
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
	        _this7.cancelRenameDashboard(dashboardId);
	      }, function (innerTitle) {
	        var oldTitle = _classPrivateMethodGet(_this7, _getTitlePreview, _getTitlePreview2).call(_this7, dashboardId).querySelector('a').innerText;
	        _classPrivateMethodGet(_this7, _getTitlePreview, _getTitlePreview2).call(_this7, dashboardId).querySelector('a').innerText = innerTitle;
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
	        _this7.cancelRenameDashboard(dashboardId);
	        _classPrivateMethodGet(_this7, _setDateModifyNow, _setDateModifyNow2).call(_this7, dashboardId);
	        babelHelpers.classPrivateFieldGet(_this7, _dashboardManager).renameDashboard(dashboardId, innerTitle)["catch"](function (response) {
	          if (response.errors) {
	            _classPrivateMethodGet(_this7, _notifyErrors, _notifyErrors2).call(_this7, response.errors);
	          }
	          _classPrivateMethodGet(_this7, _getTitlePreview, _getTitlePreview2).call(_this7, dashboardId).querySelector('a').innerText = oldTitle;
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
	        _this7.cancelRenameDashboard(dashboardId);
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
	  }, {
	    key: "handleTagClick",
	    value: function handleTagClick(tagJson) {
	      var tag = JSON.parse(tagJson);
	      this.handleFilterChange(_objectSpread({
	        fieldId: 'TAGS.ID'
	      }, tag));
	    }
	  }, {
	    key: "handleTagAddClick",
	    value: function handleTagAddClick(dashboardId, preselectedIds, event) {
	      var _this8 = this;
	      var onTagsChange = function onTagsChange() {
	        var tags = babelHelpers.classPrivateFieldGet(_this8, _tagSelectorDialog).getSelectedItems().map(function (item) {
	          return item.getId();
	        });
	        babelHelpers.classPrivateFieldGet(_this8, _dashboardManager).setDashboardTags(dashboardId, tags).then(function () {
	          var _filterTagValues$TAGS;
	          _this8.getGrid().updateRow(dashboardId, null, null, function () {
	            var _this8$getGrid$getRow;
	            var anchor = (_this8$getGrid$getRow = _this8.getGrid().getRows().getById(dashboardId)) === null || _this8$getGrid$getRow === void 0 ? void 0 : _this8$getGrid$getRow.getCellById('TAGS');
	            if (anchor && babelHelpers.classPrivateFieldGet(_this8, _tagSelectorDialog)) {
	              babelHelpers.classPrivateFieldGet(_this8, _tagSelectorDialog).setTargetNode(anchor);
	            }
	          });
	          var filterTagValues = _this8.getFilter().getFilterFieldsValues();
	          var currentFilteredTags = (_filterTagValues$TAGS = filterTagValues['TAGS.ID']) !== null && _filterTagValues$TAGS !== void 0 ? _filterTagValues$TAGS : [];
	          if (currentFilteredTags.length > 0) {
	            var filtered = tags.filter(function (tagId) {
	              return currentFilteredTags.includes(String(tagId));
	            });
	            if (filtered.length === 0) {
	              babelHelpers.classPrivateFieldGet(_this8, _tagSelectorDialog).destroy();
	              babelHelpers.classPrivateFieldSet(_this8, _tagSelectorDialog, null);
	            }
	          }
	        });
	      };
	      var entityId = 'biconnector-superset-dashboard-tag';
	      var preselectedItems = [];
	      JSON.parse(preselectedIds).forEach(function (id) {
	        return preselectedItems.push([entityId, id]);
	      });
	      babelHelpers.classPrivateFieldSet(this, _tagSelectorDialog, new ui_entitySelector.Dialog({
	        id: 'biconnector-superset-tag-widget',
	        targetNode: event.getData().button,
	        enableSearch: true,
	        width: 350,
	        height: 400,
	        multiple: true,
	        dropdownMode: true,
	        compactView: true,
	        context: entityId,
	        clearUnavailableItems: true,
	        entities: [{
	          id: entityId,
	          options: {
	            dashboardId: dashboardId
	          }
	        }],
	        preselectedItems: preselectedItems,
	        searchOptions: {
	          allowCreateItem: false
	        },
	        footer: biconnector_entitySelector.TagFooter,
	        events: {
	          onSearch: function onSearch(event) {
	            var query = event.getData().query;
	            var footer = babelHelpers.classPrivateFieldGet(_this8, _tagSelectorDialog).getFooter();
	            var footerWrapper = babelHelpers.classPrivateFieldGet(_this8, _tagSelectorDialog).getFooterContainer();
	            if (main_core.Type.isStringFilled(query.trim()) && footer.canCreateTag()) {
	              main_core.Dom.show(footerWrapper.querySelector('#tags-widget-custom-footer-add-new'));
	              main_core.Dom.show(footerWrapper.querySelector('#tags-widget-custom-footer-conjunction'));
	              return;
	            }
	            main_core.Dom.hide(footerWrapper.querySelector('#tags-widget-custom-footer-add-new'));
	            main_core.Dom.hide(footerWrapper.querySelector('#tags-widget-custom-footer-conjunction'));
	          },
	          'Search:onItemCreateAsync': function SearchOnItemCreateAsync(searchEvent) {
	            return new Promise(function (resolve, reject) {
	              var _searchEvent$getData = searchEvent.getData(),
	                searchQuery = _searchEvent$getData.searchQuery;
	              var name = searchQuery.getQuery();
	              babelHelpers.classPrivateFieldGet(_this8, _dashboardManager).addTag(name).then(function (result) {
	                var newTag = result.data;
	                var item = babelHelpers.classPrivateFieldGet(_this8, _tagSelectorDialog).addItem({
	                  id: newTag.ID,
	                  entityId: entityId,
	                  title: name,
	                  tabs: 'all'
	                });
	                if (item) {
	                  item.select();
	                }
	                resolve();
	              })["catch"](function (result) {
	                var errors = result.errors;
	                errors.forEach(function (error) {
	                  var alert = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t\t\t<div class=\"dashboard-tag-already-exists-alert\">\n\t\t\t\t\t\t\t\t\t\t\t<div class='ui-alert ui-alert-xs ui-alert-danger'> \n\t\t\t\t\t\t\t\t\t\t\t\t<span class='ui-alert-message'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t\t\t</span> \n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t"])), error.message);
	                  main_core.Dom.prepend(alert, babelHelpers.classPrivateFieldGet(_this8, _tagSelectorDialog).getFooterContainer());
	                  setTimeout(function () {
	                    main_core.Dom.remove(alert);
	                  }, 3000);
	                  reject();
	                });
	              });
	            });
	          },
	          'Item:onSelect': main_core.Runtime.debounce(onTagsChange, 100, this),
	          'Item:onDeselect': main_core.Runtime.debounce(onTagsChange, 100, this)
	        }
	      }));
	      babelHelpers.classPrivateFieldGet(this, _tagSelectorDialog).show();
	    }
	  }, {
	    key: "handleOwnerClick",
	    value: function handleOwnerClick(ownerData) {
	      this.handleFilterChange(_objectSpread({
	        fieldId: 'OWNER_ID'
	      }, ownerData));
	    }
	  }, {
	    key: "handleCreatedByClick",
	    value: function handleCreatedByClick(ownerData) {
	      this.handleFilterChange(_objectSpread({
	        fieldId: 'CREATED_BY_ID'
	      }, ownerData));
	    }
	  }, {
	    key: "handleFilterChange",
	    value: function handleFilterChange(fieldData) {
	      var _filterFieldsValues$f, _filterFieldsValues;
	      var filterFieldsValues = this.getFilter().getFilterFieldsValues();
	      var currentFilteredField = (_filterFieldsValues$f = filterFieldsValues[fieldData.fieldId]) !== null && _filterFieldsValues$f !== void 0 ? _filterFieldsValues$f : [];
	      var currentFilteredFieldLabel = (_filterFieldsValues = filterFieldsValues["".concat(fieldData.fieldId, "_label")]) !== null && _filterFieldsValues !== void 0 ? _filterFieldsValues : [];
	      if (fieldData.IS_FILTERED) {
	        currentFilteredField = currentFilteredField.filter(function (value) {
	          return parseInt(value, 10) !== fieldData.ID;
	        });
	        currentFilteredFieldLabel = currentFilteredFieldLabel.filter(function (value) {
	          return value !== fieldData.TITLE;
	        });
	      } else if (!currentFilteredField.includes(fieldData.ID)) {
	        currentFilteredField.push(fieldData.ID);
	        currentFilteredFieldLabel.push(fieldData.TITLE);
	      }
	      var filterApi = this.getFilter().getApi();
	      var filterToExtend = {};
	      filterToExtend[fieldData.fieldId] = currentFilteredField;
	      filterToExtend["".concat(fieldData.fieldId, "_label")] = currentFilteredFieldLabel;
	      filterApi.extendFilter(filterToExtend);
	      filterApi.apply();
	    }
	  }, {
	    key: "addToTopMenu",
	    value: function addToTopMenu(dashboardId) {
	      var _this9 = this;
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ADD_TO_TOP_MENU_SUCCESS')
	      });
	      _classPrivateMethodGet(this, _switchTopMenuAction, _switchTopMenuAction2).call(this, dashboardId, true);
	      return babelHelpers.classPrivateFieldGet(this, _dashboardManager).addToTopMenu(dashboardId).then(function (response) {})["catch"](function (response) {
	        babelHelpers.classPrivateFieldGet(_this9, _grid).updateRow(dashboardId);
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ADD_TO_TOP_MENU_ERROR')
	        });
	      });
	    }
	  }, {
	    key: "deleteFromTopMenu",
	    value: function deleteFromTopMenu(dashboardId) {
	      var _this10 = this;
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_FROM_TOP_MENU_SUCCESS')
	      });
	      _classPrivateMethodGet(this, _switchTopMenuAction, _switchTopMenuAction2).call(this, dashboardId, false);
	      return babelHelpers.classPrivateFieldGet(this, _dashboardManager).deleteFromTopMenu(dashboardId).then(function (response) {})["catch"](function (response) {
	        babelHelpers.classPrivateFieldGet(_this10, _grid).updateRow(dashboardId);
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DELETE_FROM_TOP_MENU_ERROR')
	        });
	      });
	    }
	  }, {
	    key: "pin",
	    value: function pin(dashboardId) {
	      var _this11 = this;
	      return babelHelpers.classPrivateFieldGet(this, _dashboardManager).pin(dashboardId).then(function () {
	        babelHelpers.classPrivateFieldGet(_this11, _grid).reload();
	      })["catch"](function () {});
	    }
	  }, {
	    key: "unpin",
	    value: function unpin(dashboardId) {
	      var _this12 = this;
	      return babelHelpers.classPrivateFieldGet(this, _dashboardManager).unpin(dashboardId).then(function () {
	        babelHelpers.classPrivateFieldGet(_this12, _grid).reload();
	      })["catch"](function () {});
	    }
	  }]);
	  return SupersetDashboardGridManager;
	}();
	function _subscribeToEvents2() {
	  var _this13 = this;
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	    var _event$getCompatData = event.getCompatData(),
	      _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	      sliderEvent = _event$getCompatData2[0];
	    if (sliderEvent.getEventId() === 'BIConnector.Superset.DashboardDetail:onDashboardBatchStatusUpdate') {
	      var eventArgs = sliderEvent.getData();
	      if (eventArgs.dashboardList) {
	        _this13.onUpdatedDashboardBatchStatus(eventArgs.dashboardList);
	      }
	    } else if (sliderEvent.getEventId() === 'BIConnector.Superset.DashboardTagGrid:onTagChange' || sliderEvent.getEventId() === 'BIConnector.Superset.DashboardTagGrid:onTagDelete') {
	      var _filterTagValues$TAGS2, _filterTagValues$TAGS3;
	      if (babelHelpers.classPrivateFieldGet(_this13, _tagSelectorDialog)) {
	        babelHelpers.classPrivateFieldGet(_this13, _tagSelectorDialog).destroy();
	        babelHelpers.classPrivateFieldSet(_this13, _tagSelectorDialog, null);
	      }
	      var filterTagValues = _this13.getFilter().getFilterFieldsValues();
	      if (main_core.Type.isUndefined(filterTagValues['TAGS.ID']) || filterTagValues['TAGS.ID'].length === 0) {
	        _this13.getGrid().reload();
	        return;
	      }
	      var _sliderEvent$getData = sliderEvent.getData(),
	        tagId = _sliderEvent$getData.tagId,
	        title = _sliderEvent$getData.title;
	      var currentFilteredTags = (_filterTagValues$TAGS2 = filterTagValues['TAGS.ID']) !== null && _filterTagValues$TAGS2 !== void 0 ? _filterTagValues$TAGS2 : [];
	      var currentFilteredTagLabels = (_filterTagValues$TAGS3 = filterTagValues['TAGS.ID_label']) !== null && _filterTagValues$TAGS3 !== void 0 ? _filterTagValues$TAGS3 : [];
	      var index = currentFilteredTags.findIndex(function (id) {
	        return main_core.Text.toInteger(id) === main_core.Text.toInteger(tagId);
	      });
	      if (sliderEvent.getEventId() === 'BIConnector.Superset.DashboardTagGrid:onTagDelete') {
	        currentFilteredTags.splice(index, 1);
	        currentFilteredTagLabels.splice(index, 1);
	      } else {
	        currentFilteredTagLabels[index] = title;
	      }
	      var filterApi = _this13.getFilter().getApi();
	      filterApi.extendFilter({
	        'TAGS.ID': currentFilteredTags,
	        'TAGS.ID_label': currentFilteredTagLabels
	      });
	      filterApi.apply();
	    }
	  });
	  main_core_events.EventEmitter.subscribe('BIConnector.Superset.DashboardManager:onDashboardBatchStatusUpdate', function (event) {
	    var data = event.getData();
	    if (!data.dashboardList) {
	      return;
	    }
	    var dashboardList = data.dashboardList;
	    _this13.onUpdatedDashboardBatchStatus(dashboardList);
	  });
	  BX.PULL && BX.PULL.extendWatch('superset_dashboard', true);
	  main_core_events.EventEmitter.subscribe('onPullEvent-biconnector', function (event) {
	    var _event$data = babelHelpers.slicedToArray(event.data, 2),
	      eventName = _event$data[0],
	      eventData = _event$data[1];
	    if (eventName !== 'onSupersetStatusUpdated' || !eventData) {
	      return;
	    }
	    var status = eventData === null || eventData === void 0 ? void 0 : eventData.status;
	    if (status) {
	      _classPrivateMethodGet(_this13, _onSupersetStatusChange, _onSupersetStatusChange2).call(_this13, status);
	    }
	  });
	  main_core_events.EventEmitter.subscribe('BX.Rest.Configuration.Install:onFinish', function () {
	    babelHelpers.classPrivateFieldGet(_this13, _grid).reload();
	  });
	  main_core_events.EventEmitter.subscribe('Grid::updated', function () {
	    _classPrivateMethodGet(_this13, _initHints, _initHints2).call(_this13);
	    _classPrivateMethodGet(_this13, _colorPinnedRows, _colorPinnedRows2).call(_this13);
	  });
	  main_core_events.EventEmitter.subscribe('BIConnector.ExportMaster:onDashboardDataLoaded', function () {
	    babelHelpers.classPrivateFieldGet(_this13, _grid).tableUnfade();
	  });
	  main_core_events.EventEmitter.subscribe('BIConnector.DashboardManager:onEmbeddedDataLoaded', function () {
	    babelHelpers.classPrivateFieldGet(_this13, _grid).reload();
	  });
	  main_core_events.EventEmitter.subscribe('BX.BIConnector.Settings:onAfterSave', function () {
	    babelHelpers.classPrivateFieldGet(_this13, _grid).reload();
	  });
	  main_core_events.EventEmitter.subscribe('BIConnector.CreateForm:onDashboardCreated', function (event) {
	    _classPrivateMethodGet(_this13, _onNewDashboardCreated, _onNewDashboardCreated2).call(_this13, event);
	  });
	}
	function _initHints2() {
	  var manager = BX.UI.Hint.createInstance({
	    popupParameters: {
	      autoHide: true
	    }
	  });
	  manager.init(babelHelpers.classPrivateFieldGet(this, _grid).getContainer());
	}
	function _onSupersetStatusChange2(status) {
	  if (status === 'READY') {
	    this.getGrid().reload();
	  }
	  if (status !== 'LOAD' && status !== 'ERROR') {
	    return;
	  }
	  var statusMap = {
	    LOAD: biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_LOAD,
	    ERROR: biconnector_apacheSupersetDashboardManager.DashboardManager.DASHBOARD_STATUS_COMPUTED_NOT_LOAD
	  };
	  var grid = this.getGrid();
	  var rows = grid.getRows().getBodyChild();
	  var _iterator3 = _createForOfIteratorHelper(rows),
	    _step3;
	  try {
	    for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	      var row = _step3.value;
	      var dashboardId = row.getId();
	      var dashboardStatus = statusMap[status];
	      if (dashboardStatus) {
	        this.updateDashboardStatus(dashboardId, dashboardStatus);
	      }
	    }
	  } catch (err) {
	    _iterator3.e(err);
	  } finally {
	    _iterator3.f();
	  }
	}
	function _showTopMenuGuide2() {
	  var guide = new ui_tour.Guide({
	    steps: [{
	      target: babelHelpers.classPrivateFieldGet(this, _grid).getRows().getBodyFirstChild().node,
	      title: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_TOP_MENU_GUIDE_TITLE'),
	      text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_TOP_MENU_GUIDE_TEXT'),
	      events: {
	        onClose: function onClose() {
	          BX.userOptions.save('biconnector', 'top_menu_guide', 'is_over', true);
	        }
	      },
	      rounded: false,
	      position: 'bottom',
	      areaPadding: 0
	    }],
	    onEvents: true
	  });
	  guide.start();
	}
	function _showDraftGuide2(node) {
	  if (!babelHelpers.classPrivateFieldGet(this, _properties).isNeedShowDraftGuide) {
	    return;
	  }
	  var labelNode = node.querySelector('.dashboard-status-label.ui-label-default');
	  var cellNode = labelNode ? labelNode.closest('.main-grid-cell') : null;
	  var guide = new ui_tour.Guide({
	    steps: [{
	      target: cellNode !== null && cellNode !== void 0 ? cellNode : node,
	      title: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DRAFT_GUIDE_TITLE'),
	      text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_DRAFT_GUIDE_TEXT'),
	      events: {
	        onClose: function onClose() {
	          BX.userOptions.save('biconnector', 'draft_guide', 'is_over', true);
	        }
	      },
	      rounded: false,
	      position: 'bottom',
	      areaPadding: 0
	    }],
	    onEvents: true
	  });
	  guide.start();
	  babelHelpers.classPrivateFieldGet(this, _properties).isNeedShowDraftGuide = false;
	}
	function _colorPinnedRows2() {
	  babelHelpers.classPrivateFieldSet(this, _lastPinnedRowId, 0);
	  var rows = babelHelpers.classPrivateFieldGet(this, _grid).getRows().getBodyChild();
	  var _iterator4 = _createForOfIteratorHelper(rows),
	    _step4;
	  try {
	    for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	      var row = _step4.value;
	      if (row.node.querySelector('.dashboard-unpin-icon')) {
	        main_core.Dom.addClass(row.node, 'biconnector-dashboard-pinned');
	        babelHelpers.classPrivateFieldSet(this, _lastPinnedRowId, row.getId());
	      }
	    }
	  } catch (err) {
	    _iterator4.e(err);
	  } finally {
	    _iterator4.f();
	  }
	}
	function _notifyErrors2(errors) {
	  if (errors[0] && errors[0].message) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(errors[0].message)
	    });
	  }
	}
	function _onNewDashboardCreated2(event) {
	  var _this14 = this;
	  var grid = this.getGrid();
	  var newDashboard = event.data.dashboard;
	  var gridRealtime = grid.getRealtime();
	  gridRealtime.addRow({
	    id: newDashboard.id,
	    columns: newDashboard.columns,
	    actions: newDashboard.actions,
	    insertAfter: babelHelpers.classPrivateFieldGet(this, _lastPinnedRowId)
	  });
	  var editableData = grid.getParam('EDITABLE_DATA');
	  if (BX.type.isPlainObject(editableData)) {
	    editableData[newDashboard.id] = {
	      TITLE: newDashboard.title
	    };
	  }
	  var counterTotalTextContainer = grid.getCounterTotal().querySelector('.main-grid-panel-content-text');
	  counterTotalTextContainer.textContent++;
	  _classPrivateMethodGet(this, _initHints, _initHints2).call(this);
	  setTimeout(function () {
	    _classPrivateMethodGet(_this14, _showDraftGuide, _showDraftGuide2).call(_this14, babelHelpers.classPrivateFieldGet(_this14, _grid).getRows().getBodyFirstChild().node);
	  }, 1200);
	}
	function _buildDashboardTitleEditor2(id, title, onCancel, onSave) {
	  var input = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input class=\"main-grid-editor main-grid-editor-text\" type=\"text\">\n\t\t"])));
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
	  var applyButton = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a>\n\t\t\t\t<i\n\t\t\t\t\tclass=\"ui-icon-set --check\"\n\t\t\t\t\tstyle=\"--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);\"\n\t\t\t\t></i>\n\t\t\t</a>\n\t\t"])));
	  var cancelButton = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a>\n\t\t\t\t<i\n\t\t\t\t\tclass=\"ui-icon-set --cross-60\"\n\t\t\t\t\tstyle=\"--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);\"\n\t\t\t\t></i>\n\t\t\t</a>\n\t\t"])));
	  var buttons = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"dashboard-title-wrapper__buttons\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), applyButton, cancelButton);
	  main_core.Event.bind(cancelButton, 'click', function () {
	    onCancel();
	  });
	  main_core.Event.bind(applyButton, 'click', saveInputValue);
	  return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"dashboard-title-wrapper__item dashboard-title-edit\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"dashboard-title-wrapper__buttons-wrapper\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), input, buttons);
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
	  var newCellContent = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span data-hint=\"", "\" data-hint-no-icon data-hint-interactivity>", "</span>\n\t\t"])), date, readableDate);
	  main_core.Dom.replace(cellContent, newCellContent);
	  _classPrivateMethodGet(this, _initHints, _initHints2).call(this);
	}
	function _switchTopMenuAction2(dashboardId, isInTopMenu) {
	  var row = babelHelpers.classPrivateFieldGet(this, _grid).getRows().getById(dashboardId);
	  var rowActions = row === null || row === void 0 ? void 0 : row.getActions();
	  var _iterator5 = _createForOfIteratorHelper(rowActions.entries()),
	    _step5;
	  try {
	    for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
	      var _step5$value = babelHelpers.slicedToArray(_step5.value, 2),
	        index = _step5$value[0],
	        action = _step5$value[1];
	      if (isInTopMenu && action.ACTION_ID === 'addToTopMenu') {
	        rowActions[index].ACTION_ID = 'deleteFromTopMenu';
	        rowActions[index].onclick = "BX.BIConnector.SupersetDashboardGridManager.Instance.deleteFromTopMenu(".concat(dashboardId, ")");
	        rowActions[index].text = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ACTION_ITEM_DELETE_FROM_TOP_MENU');
	      } else if (!isInTopMenu && action.ACTION_ID === 'deleteFromTopMenu') {
	        rowActions[index].ACTION_ID = 'addToTopMenu';
	        rowActions[index].onclick = "BX.BIConnector.SupersetDashboardGridManager.Instance.addToTopMenu(".concat(dashboardId, ")");
	        rowActions[index].text = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ACTION_ITEM_ADD_TO_TOP_MENU');
	      }
	    }
	  } catch (err) {
	    _iterator5.e(err);
	  } finally {
	    _iterator5.f();
	  }
	  row.setActions(rowActions);
	  var titleCell = row === null || row === void 0 ? void 0 : row.getCellById('TITLE');
	  var dashboardTitle = '';
	  if (titleCell) {
	    var titleWrapper = titleCell.querySelector('.dashboard-title-wrapper__item');
	    dashboardTitle = titleWrapper.querySelector('a').innerText;
	  }
	  var menu = BX.Main.interfaceButtonsManager.getById('biconnector_superset_menu');
	  if (isInTopMenu && dashboardTitle) {
	    menu.addMenuItem({
	      id: "biconnector_superset_menu_dashboard_".concat(dashboardId),
	      text: dashboardTitle,
	      url: "/bi/dashboard/detail/".concat(dashboardId, "/?openFrom=menu"),
	      onClick: ''
	    });
	    var menuItem = menu.getItemById("biconnector_superset_menu_dashboard_".concat(dashboardId));
	    var firstMenuItem = menu.getVisibleItems();
	    main_core.Dom.insertBefore(menuItem, firstMenuItem[0]);
	  } else {
	    var _menuItem = menu.getItemById("biconnector_superset_menu_dashboard_".concat(dashboardId));
	    menu.deleteMenuItem(_menuItem);
	  }
	}
	main_core.Reflection.namespace('BX.BIConnector').SupersetDashboardGridManager = SupersetDashboardGridManager;

}((this.window = this.window || {}),BX,BX.Main,BX.Main,BX.BIConnector,BX.Event,BX.UI.Dialogs,BX.BIConnector,BX.UI.EntitySelector,BX.UI.Tour,BX,BX.BIConnector.EntitySelector));
//# sourceMappingURL=script.js.map
