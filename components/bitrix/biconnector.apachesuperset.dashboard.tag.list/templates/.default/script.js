/* eslint-disable */
(function (exports,main_core,ui_notification,ui_dialogs_messagebox,main_sidepanel) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _grid = /*#__PURE__*/new WeakMap();
	var _notifyErrors = /*#__PURE__*/new WeakSet();
	var _buildTitleEditor = /*#__PURE__*/new WeakSet();
	var _getTitlePreview = /*#__PURE__*/new WeakSet();
	var _delete = /*#__PURE__*/new WeakSet();
	var _sendChangeEventMessage = /*#__PURE__*/new WeakSet();
	var _sendDeleteEventMessage = /*#__PURE__*/new WeakSet();
	var _cancelRename = /*#__PURE__*/new WeakSet();
	var _saveTitle = /*#__PURE__*/new WeakSet();
	var SupersetDashboardTagGridManager = /*#__PURE__*/function () {
	  function SupersetDashboardTagGridManager(props) {
	    var _BX$Main$gridManager$;
	    babelHelpers.classCallCheck(this, SupersetDashboardTagGridManager);
	    _classPrivateMethodInitSpec(this, _saveTitle);
	    _classPrivateMethodInitSpec(this, _cancelRename);
	    _classPrivateMethodInitSpec(this, _sendDeleteEventMessage);
	    _classPrivateMethodInitSpec(this, _sendChangeEventMessage);
	    _classPrivateMethodInitSpec(this, _delete);
	    _classPrivateMethodInitSpec(this, _getTitlePreview);
	    _classPrivateMethodInitSpec(this, _buildTitleEditor);
	    _classPrivateMethodInitSpec(this, _notifyErrors);
	    _classPrivateFieldInitSpec(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(props.gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	  }
	  babelHelpers.createClass(SupersetDashboardTagGridManager, [{
	    key: "getGrid",
	    value: function getGrid() {
	      return babelHelpers.classPrivateFieldGet(this, _grid);
	    }
	  }, {
	    key: "renameTag",
	    value: function renameTag(tagId) {
	      var _row$getCellById,
	        _this = this,
	        _row$getCellById2;
	      var grid = this.getGrid();
	      var row = grid.getRows().getById(tagId);
	      if (!row) {
	        return;
	      }
	      var rowNode = row.getNode();
	      main_core.Dom.removeClass(rowNode, 'tag-title-edited');
	      var wrapper = (_row$getCellById = row.getCellById('TITLE')) === null || _row$getCellById === void 0 ? void 0 : _row$getCellById.querySelector('.tag-title-wrapper');
	      if (!wrapper) {
	        return;
	      }
	      var editor = _classPrivateMethodGet(this, _buildTitleEditor, _buildTitleEditor2).call(this, tagId, row.getEditData().TITLE, function () {
	        _classPrivateMethodGet(_this, _cancelRename, _cancelRename2).call(_this, tagId);
	      }, function (innerTitle) {
	        var oldTitle = _classPrivateMethodGet(_this, _getTitlePreview, _getTitlePreview2).call(_this, tagId).querySelector('span').innerText;
	        _classPrivateMethodGet(_this, _getTitlePreview, _getTitlePreview2).call(_this, tagId).querySelector('span').innerText = innerTitle;
	        var rowEditData = row.getEditData();
	        rowEditData.TITLE = innerTitle;
	        var editableData = grid.getParam('EDITABLE_DATA');
	        if (main_core.Type.isPlainObject(editableData)) {
	          editableData[row.getId()] = rowEditData;
	        }
	        return new Promise(function (resolve, reject) {
	          _classPrivateMethodGet(_this, _saveTitle, _saveTitle2).call(_this, tagId, innerTitle).then(function () {
	            main_core.Dom.addClass(rowNode, 'tag-title-edited');
	            var msg = main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_RENAME_TITLE_SUCCESS', {
	              '#NEW_TITLE#': main_core.Text.encode(innerTitle)
	            });
	            ui_notification.UI.Notification.Center.notify({
	              content: msg
	            });
	            _classPrivateMethodGet(_this, _cancelRename, _cancelRename2).call(_this, tagId);
	            _classPrivateMethodGet(_this, _sendChangeEventMessage, _sendChangeEventMessage2).call(_this, tagId, innerTitle);
	            resolve();
	          })["catch"](function (response) {
	            if (response.errors) {
	              _classPrivateMethodGet(_this, _notifyErrors, _notifyErrors2).call(_this, response.errors);
	            }
	            _classPrivateMethodGet(_this, _getTitlePreview, _getTitlePreview2).call(_this, tagId).querySelector('span').innerText = oldTitle;
	            rowEditData.TITLE = oldTitle;
	            reject();
	          });
	        });
	      });
	      var preview = wrapper.querySelector('.tag-title-preview');
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
	        _classPrivateMethodGet(_this, _cancelRename, _cancelRename2).call(_this, tagId);
	      };
	      main_core.Event.bind(row.getActionsButton(), 'click', actionsClickHandler);
	      if (editBtn) {
	        main_core.Event.bind(editBtn, 'click', actionsClickHandler);
	      }
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
	function _buildTitleEditor2(id, title, onCancel, onSave) {
	  var input = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input class=\"main-grid-editor main-grid-editor-text\" type=\"text\">\n\t\t"])));
	  input.value = title;
	  var saveInputValue = function saveInputValue() {
	    var value = input.value;
	    main_core.Dom.removeClass(input, 'tag-title-input-danger');
	    if (value.trim() === '') {
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_TITLE_ERROR_EMPTY')
	      });
	      main_core.Dom.addClass(input, 'tag-title-input-danger');
	      return;
	    }
	    onSave(input.value).then(function () {
	      main_core.Dom.style(buttons, 'display', 'none');
	      main_core.Dom.attr(input, 'disabled', true);
	    })["catch"](function () {
	      main_core.Dom.addClass(input, 'tag-title-input-danger');
	    });
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
	  var applyButton = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a>\n\t\t\t\t<i\n\t\t\t\t\tclass=\"ui-icon-set --check\"\n\t\t\t\t\tstyle=\"--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);\"\n\t\t\t\t></i>\n\t\t\t</a>\n\t\t"])));
	  var cancelButton = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a>\n\t\t\t\t<i\n\t\t\t\t\tclass=\"ui-icon-set --cross-60\"\n\t\t\t\t\tstyle=\"--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);\"\n\t\t\t\t></i>\n\t\t\t</a>\n\t\t"])));
	  var buttons = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tag-title-wrapper__buttons\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), applyButton, cancelButton);
	  main_core.Event.bind(cancelButton, 'click', function () {
	    onCancel();
	  });
	  main_core.Event.bind(applyButton, 'click', saveInputValue);
	  return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tag-title-wrapper__item tag-title-edit\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"tag-title-wrapper__buttons-wrapper\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), input, buttons);
	}
	function _getTitlePreview2(tagId) {
	  var _row$getCellById3;
	  var grid = this.getGrid();
	  var row = grid.getRows().getById(tagId);
	  if (!row) {
	    return null;
	  }
	  var wrapper = (_row$getCellById3 = row.getCellById('TITLE')) === null || _row$getCellById3 === void 0 ? void 0 : _row$getCellById3.querySelector('.tag-title-wrapper');
	  if (!wrapper) {
	    return null;
	  }
	  var previewSection = wrapper.querySelector('.tag-title-preview');
	  if (previewSection) {
	    return previewSection;
	  }
	  return null;
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
	function _cancelRename2(tagId) {
	  var _row$getCellById4, _row$getCellById5;
	  var row = this.getGrid().getRows().getById(tagId);
	  if (!row) {
	    return;
	  }
	  var editSection = (_row$getCellById4 = row.getCellById('TITLE')) === null || _row$getCellById4 === void 0 ? void 0 : _row$getCellById4.querySelector('.tag-title-edit');
	  var previewSection = (_row$getCellById5 = row.getCellById('TITLE')) === null || _row$getCellById5 === void 0 ? void 0 : _row$getCellById5.querySelector('.tag-title-preview');
	  if (editSection) {
	    main_core.Dom.remove(editSection);
	  }
	  if (previewSection) {
	    main_core.Dom.style(previewSection, 'display', 'flex');
	  }
	}
	function _saveTitle2(tagId, title) {
	  return main_core.ajax.runAction('biconnector.dashboardTag.rename', {
	    data: {
	      id: tagId,
	      title: title
	    }
	  });
	}
	main_core.Reflection.namespace('BX.BIConnector').SupersetDashboardTagGridManager = SupersetDashboardTagGridManager;

}((this.window = this.window || {}),BX,BX,BX.UI.Dialogs,BX));
//# sourceMappingURL=script.js.map
