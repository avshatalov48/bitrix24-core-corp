/* eslint-disable */
(function (exports,biconnector_grid_editableColumns,main_core,ui_notification,ui_dialogs_messagebox,main_sidepanel) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _grid = /*#__PURE__*/new WeakMap();
	var _notifyErrors = /*#__PURE__*/new WeakSet();
	var _delete = /*#__PURE__*/new WeakSet();
	var _sendChangeEventMessage = /*#__PURE__*/new WeakSet();
	var _sendDeleteEventMessage = /*#__PURE__*/new WeakSet();
	var SupersetDashboardTagGridManager = /*#__PURE__*/function () {
	  function SupersetDashboardTagGridManager(props) {
	    var _BX$Main$gridManager$,
	      _this = this;
	    babelHelpers.classCallCheck(this, SupersetDashboardTagGridManager);
	    _classPrivateMethodInitSpec(this, _sendDeleteEventMessage);
	    _classPrivateMethodInitSpec(this, _sendChangeEventMessage);
	    _classPrivateMethodInitSpec(this, _delete);
	    _classPrivateMethodInitSpec(this, _notifyErrors);
	    _classPrivateFieldInitSpec(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(props.gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	    biconnector_grid_editableColumns.EditableColumnManager.init(props.gridId, [{
	      name: 'TITLE',
	      saveEndpoint: 'biconnector.dashboardTag.rename',
	      onValueCheck: function onValueCheck(newValue) {
	        if (newValue.trim() === '') {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_TITLE_ERROR_EMPTY')
	          });
	          return false;
	        }
	        return true;
	      },
	      onSave: function onSave(rowId, newValue) {
	        var msg = main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_RENAME_TITLE_SUCCESS', {
	          '#NEW_TITLE#': main_core.Text.encode(newValue)
	        });
	        ui_notification.UI.Notification.Center.notify({
	          content: msg
	        });
	        _classPrivateMethodGet(_this, _sendChangeEventMessage, _sendChangeEventMessage2).call(_this, rowId, newValue);
	      }
	    }]);
	  }
	  babelHelpers.createClass(SupersetDashboardTagGridManager, [{
	    key: "getGrid",
	    value: function getGrid() {
	      return babelHelpers.classPrivateFieldGet(this, _grid);
	    }
	  }, {
	    key: "deleteTag",
	    value: function deleteTag(tagId) {
	      var _this2 = this;
	      var grid = this.getGrid();
	      var row = grid.getRows().getById(tagId);
	      var count = row.getEditData().DASHBOARD_COUNT;
	      if (!count) {
	        _classPrivateMethodGet(this, _delete, _delete2).call(this, tagId);
	        return;
	      }
	      var messageBox = ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_DELETE_POPUP'), function () {
	        _classPrivateMethodGet(_this2, _delete, _delete2).call(_this2, tagId);
	        messageBox.close();
	      }, main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_DELETE_POPUP_YES'));
	    }
	  }]);
	  return SupersetDashboardTagGridManager;
	}();
	function _notifyErrors2(errors) {
	  if (errors[0] && errors[0].message) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(errors[0].message)
	    });
	  }
	}
	function _delete2(tagId) {
	  var _this3 = this;
	  return main_core.ajax.runAction('biconnector.dashboardTag.delete', {
	    data: {
	      id: tagId
	    }
	  }).then(function () {
	    _this3.getGrid().removeRow(tagId, null, null, function () {
	      _classPrivateMethodGet(_this3, _sendDeleteEventMessage, _sendDeleteEventMessage2).call(_this3, tagId);
	    });
	    var msg = main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_DELETE_SUCCESS');
	    ui_notification.UI.Notification.Center.notify({
	      content: msg
	    });
	  })["catch"](function (response) {
	    if (response.errors) {
	      _classPrivateMethodGet(_this3, _notifyErrors, _notifyErrors2).call(_this3, response.errors);
	    }
	  });
	}
	function _sendChangeEventMessage2(tagId, title) {
	  if (main_sidepanel.SidePanel.Instance) {
	    main_sidepanel.SidePanel.Instance.postMessage(window, 'BIConnector.Superset.DashboardTagGrid:onTagChange', {
	      tagId: tagId,
	      title: title
	    });
	  }
	}
	function _sendDeleteEventMessage2(tagId) {
	  if (main_sidepanel.SidePanel.Instance) {
	    main_sidepanel.SidePanel.Instance.postMessage(window, 'BIConnector.Superset.DashboardTagGrid:onTagDelete', {
	      tagId: tagId
	    });
	  }
	}
	main_core.Reflection.namespace('BX.BIConnector').SupersetDashboardTagGridManager = SupersetDashboardTagGridManager;

}((this.window = this.window || {}),BX.BIConnector.Grid,BX,BX,BX.UI.Dialogs,BX.SidePanel));
//# sourceMappingURL=script.js.map
