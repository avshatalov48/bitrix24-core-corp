(function (exports,main_core,main_popup,ui_dialogs_messagebox,ui_switcher,bitrix24_phoneverify) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var PHONE_VERIFY_FORM_ENTITY = 'crm_webform';
	var _groupAction = /*#__PURE__*/new WeakMap();
	var _renderGridRows = /*#__PURE__*/new WeakSet();
	var _renderQrButtons = /*#__PURE__*/new WeakSet();
	var _renderEntities = /*#__PURE__*/new WeakSet();
	var _renderActiveSwitchers = /*#__PURE__*/new WeakSet();
	var _getGrid = /*#__PURE__*/new WeakSet();
	var _getGridContainer = /*#__PURE__*/new WeakSet();
	var _verifyPhone = /*#__PURE__*/new WeakSet();
	var WebFormList = /*#__PURE__*/function () {
	  function WebFormList() {
	    babelHelpers.classCallCheck(this, WebFormList);
	    _classPrivateMethodInitSpec(this, _verifyPhone);
	    _classPrivateMethodInitSpec(this, _getGridContainer);
	    _classPrivateMethodInitSpec(this, _getGrid);
	    _classPrivateMethodInitSpec(this, _renderActiveSwitchers);
	    _classPrivateMethodInitSpec(this, _renderEntities);
	    _classPrivateMethodInitSpec(this, _renderQrButtons);
	    _classPrivateMethodInitSpec(this, _renderGridRows);
	    _classPrivateFieldInitSpec(this, _groupAction, {
	      writable: true,
	      value: null
	    });
	  }
	  babelHelpers.createClass(WebFormList, [{
	    key: "init",
	    value: function init(params) {
	      var _this = this;
	      this.reloadGridTimeoutId = 0;
	      this.gridId = params.gridId;
	      this.gridNode = document.getElementById(this.gridId);
	      var hideDescBtnNode = BX('CRM_LIST_DESC_BTN_HIDE');
	      if (hideDescBtnNode) {
	        BX.bind(hideDescBtnNode, 'click', function () {
	          BX.addClass(BX('CRM_LIST_DESC_CONT'), 'crm-webform-list-info-hide');
	          BX.userOptions.delay = 0;
	          BX.userOptions.save('crm', 'webform_list_view', 'hide-desc', 'Y');
	        });
	      }
	      var notifyBtnNode = BX('CRM_LIST_WEBFORM_NOTIFY_BTN_HIDE');
	      if (notifyBtnNode) {
	        BX.bind(notifyBtnNode, 'click', function () {
	          BX.addClass(BX('CRM_LIST_DESC_CONT'), 'crm-webform-list-info-hide');
	          BX.userOptions.delay = 0;
	          BX.userOptions.save('crm', 'notify_webform', 'ru_fz_152', 'Y');
	        });
	      }
	      _classPrivateMethodGet(this, _renderGridRows, _renderGridRows2).call(this);
	      BX.addCustomEvent('Grid::updated', function () {
	        _classPrivateMethodGet(_this, _renderGridRows, _renderGridRows2).call(_this);
	      });
	      return this;
	    }
	  }, {
	    key: "setGroupAction",
	    value: function setGroupAction(code) {
	      babelHelpers.classPrivateFieldSet(this, _groupAction, code);
	    }
	  }, {
	    key: "runGroupAction",
	    value: function runGroupAction() {
	      switch (babelHelpers.classPrivateFieldGet(this, _groupAction)) {
	        case 'activate':
	          this.activateList(true);
	          return;
	        case 'deactivate':
	          this.activateList(false);
	          return;
	        case 'delete':
	          this.removeList();
	          return;
	      }
	      if (babelHelpers.classPrivateFieldGet(this, _groupAction)) {
	        throw new Error("Wrong group action \"".concat(babelHelpers.classPrivateFieldGet(this, _groupAction), "\""));
	      }
	    }
	  }, {
	    key: "showConfirm",
	    value: function showConfirm() {
	      var code = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'delete';
	      code = code.toUpperCase();
	      return new Promise(function (resolve, reject) {
	        ui_dialogs_messagebox.MessageBox.show({
	          message: main_core.Loc.getMessage('CRM_WEBFORM_LIST_' + code + '_CONFIRM'),
	          modal: true,
	          title: main_core.Loc.getMessage('CRM_WEBFORM_LIST_' + code + '_CONFIRM_TITLE'),
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	          onOk: function onOk(messageBox) {
	            messageBox.close();
	            resolve();
	          },
	          onCancel: function onCancel(messageBox) {
	            messageBox.close();
	            reject();
	          }
	        });
	      });
	    }
	  }, {
	    key: "reloadGrid",
	    value: function reloadGrid() {
	      var grid = _classPrivateMethodGet(this, _getGrid, _getGrid2).call(this);
	      if (grid) {
	        return grid.reload();
	      }
	    }
	  }, {
	    key: "showGridLoader",
	    value: function showGridLoader() {
	      var grid = _classPrivateMethodGet(this, _getGrid, _getGrid2).call(this);
	      if (grid) {
	        grid.getLoader().show();
	      }
	    }
	  }, {
	    key: "hideGridLoader",
	    value: function hideGridLoader() {
	      var grid = _classPrivateMethodGet(this, _getGrid, _getGrid2).call(this);
	      if (grid) {
	        grid.getLoader().hide();
	      }
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(message) {
	      BX.UI.Notification.Center.notify({
	        content: message
	      });
	    }
	  }, {
	    key: "remove",
	    value: function remove(id) {
	      var _this2 = this;
	      this.showConfirm('delete').then(function () {
	        _this2.showGridLoader();
	        main_core.ajax.runAction('crm.form.delete', {
	          json: {
	            id: id
	          }
	        }).then(function (response) {
	          if (response.data) {
	            _this2.reloadGrid();
	          } else {
	            _this2.hideGridLoader();
	            _this2.showNotification(main_core.Loc.getMessage('CRM_WEBFORM_LIST_DELETE_ERROR'));
	          }
	        })["catch"](function () {
	          _this2.hideGridLoader();
	          _this2.showNotification(main_core.Loc.getMessage('CRM_WEBFORM_LIST_DELETE_ERROR'));
	        });
	      });
	    }
	  }, {
	    key: "removeList",
	    value: function removeList() {
	      var _this3 = this;
	      this.showConfirm('delete').then(function () {
	        var grid = _classPrivateMethodGet(_this3, _getGrid, _getGrid2).call(_this3);
	        if (grid) {
	          _classPrivateMethodGet(_this3, _getGrid, _getGrid2).call(_this3).removeSelected();
	        }
	      });
	    }
	  }, {
	    key: "resetCounters",
	    value: function resetCounters(id) {
	      var _this4 = this;
	      this.showGridLoader();
	      return main_core.ajax.runAction('crm.form.resetCounters', {
	        json: {
	          id: id
	        }
	      }).then(function () {
	        return _this4.reloadGrid();
	      })["catch"](function () {
	        _this4.checkOnWriteAccessError(result);
	        _this4.hideGridLoader();
	      });
	    }
	  }, {
	    key: "copy",
	    value: function copy(id) {
	      var _this5 = this;
	      this.showGridLoader();
	      return main_core.ajax.runAction('crm.form.copy', {
	        json: {
	          id: id
	        }
	      }).then(function () {
	        return _this5.reloadGrid();
	      })["catch"](function () {
	        _this5.checkOnWriteAccessError(result);
	        _this5.hideGridLoader();
	      });
	    }
	  }, {
	    key: "showSiteCode",
	    value: function showSiteCode(id) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var needVerify = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
	      if (needVerify) {
	        _classPrivateMethodGet(this, _verifyPhone, _verifyPhone2).call(this, PHONE_VERIFY_FORM_ENTITY, id, function () {
	          BX.Crm.Form.Embed.openSlider(id, options);
	        });
	      } else {
	        BX.Crm.Form.Embed.openSlider(id, options);
	      }
	    }
	  }, {
	    key: "activateList",
	    value: function activateList() {
	      var _this6 = this;
	      var mode = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      this.showGridLoader();
	      var grid = _classPrivateMethodGet(this, _getGrid, _getGrid2).call(this);
	      if (!grid) {
	        return;
	      }
	      var list = grid.getRows().getSelectedIds();
	      main_core.ajax.runAction('crm.form.activateList', {
	        json: {
	          list: list,
	          mode: mode
	        }
	      }).then(function () {
	        return _this6.reloadGrid();
	      })["catch"](function () {
	        _this6.checkOnWriteAccessError(result);
	        _this6.hideGridLoader();
	      });
	    }
	  }, {
	    key: "activate",
	    value: function activate(id, mode) {
	      var _this7 = this;
	      var reloadGrid = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
	      var nodeText = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;
	      var switcher = BX.UI.Switcher.getById('crm-form-list-item-' + id);
	      if (switcher) {
	        switcher.setLoading(true);
	        switcher.check(mode, false);
	      }
	      return main_core.ajax.runAction('crm.form.activate', {
	        json: {
	          id: parseInt(id),
	          mode: mode
	        }
	      }).then(function () {
	        if (switcher) {
	          nodeText.textContent = switcher.isChecked() ? BX.date.format(BX.date.convertBitrixFormat(BX.message("FORMAT_DATE"))) : main_core.Loc.getMessage('CRM_WEBFORM_LIST_NOT_ACTIVE');
	          switcher.setLoading(false);
	        }
	        if (reloadGrid) {
	          _this7.reloadGrid();
	        }
	      })["catch"](function (result) {
	        _this7.checkOnWriteAccessError(result);
	        if (switcher) {
	          switcher.setLoading(false);
	          switcher.check(!switcher.isChecked(), false);
	        }
	      });
	    }
	  }, {
	    key: "checkOnWriteAccessError",
	    value: function checkOnWriteAccessError(result) {
	      var _this8 = this;
	      var errors = result.errors;
	      errors.forEach(function (error) {
	        if (parseInt(error.code) === 2) {
	          _this8.showNotification(main_core.Loc.getMessage('CRM_WEBFORM_LIST_ITEM_WRITE_ACCESS_DENIED'));
	        }
	        if (error.code === 'ERROR_CODE_PHONE_NOT_VERIFIED') {
	          _this8.showNotification(main_core.Loc.getMessage('CRM_WEBFORM_LIST_ITEM_PHONE_NOT_VERIFIED'));
	        }
	      });
	    }
	  }]);
	  return WebFormList;
	}();
	function _renderGridRows2() {
	  _classPrivateMethodGet(this, _renderEntities, _renderEntities2).call(this);
	  _classPrivateMethodGet(this, _renderQrButtons, _renderQrButtons2).call(this);
	  _classPrivateMethodGet(this, _renderActiveSwitchers, _renderActiveSwitchers2).call(this);
	}
	function _renderQrButtons2() {
	  var _this9 = this;
	  var container = _classPrivateMethodGet(this, _getGridContainer, _getGridContainer2).call(this);
	  if (!container) {
	    return;
	  }
	  var switcherAttr = 'data-crm-form-qr';
	  var switchers = container.querySelectorAll('[' + switcherAttr + ']');
	  switchers = Array.prototype.slice.call(switchers);
	  switchers.forEach(function (node) {
	    if (node.querySelector('.crm-webform-qr-btn')) {
	      return;
	    }
	    var data = JSON.parse(node.getAttribute(switcherAttr));
	    if (data.needVerify) {
	      var onClickVerify = function onClickVerify() {
	        _classPrivateMethodGet(_this9, _verifyPhone, _verifyPhone2).call(_this9, PHONE_VERIFY_FORM_ENTITY, data.id, function () {
	          new BX.Crm.Form.Qr({
	            link: data.path
	          }).show();
	        });
	      };
	      node.appendChild(main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<button\n\t\t\t\t\t\ttype=\"button\"\n\t\t\t\t\t\tclass=\"crm-webform-qr-btn ui-btn ui-btn-xs ui-btn-light-border ui-btn-round ui-btn-no-caps ui-btn-icon-share\"\n\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t"])), onClickVerify, main_core.Loc.getMessage('CRM_WEBFORM_QR_OPEN')));
	    } else {
	      new BX.Crm.Form.Qr({
	        link: data.path
	      }).renderTo(node);
	    }
	  });
	}
	function _renderEntities2() {
	  var container = _classPrivateMethodGet(this, _getGridContainer, _getGridContainer2).call(this);
	  if (!container) {
	    return;
	  }
	  var attr = 'data-crm-form-entities';
	  var buttons = container.querySelectorAll('[' + attr + ']');
	  buttons = Array.prototype.slice.call(buttons);
	  buttons.forEach(function (node) {
	    var data = JSON.parse(node.getAttribute(attr));
	    var handler = function handler(event) {
	      event.stopPropagation();
	      event.preventDefault();
	      var id = 'crm-form-grid-entities-' + data.id;
	      var popup = main_popup.PopupManager.getPopupById(id);
	      if (popup) {
	        var hide = popup.getId() === id;
	        popup.destroy();
	        popup = null;
	        if (hide) {
	          return;
	        }
	      }
	      var contentNode = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-webform-list-entities\"></div>"])));
	      data.counters.forEach(function (counter) {
	        var counterHandler = function counterHandler(event) {
	          event.stopPropagation();
	          event.preventDefault();
	          BX.SidePanel.Instance.open(counter.LINK);
	          return false;
	        };
	        var caption = main_core.Text.encode(counter.ENTITY_CAPTION);
	        var value = main_core.Text.encode(counter.VALUE);
	        var counterNode = !counter.LINK ? main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<span \n\t\t\t\t\t\t\t\tclass=\"crm-webform-active-popup-item-date\" \n\t\t\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\t\t>", "</span>\n\t\t\t\t\t\t"])), caption, caption) : main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\thref=\"", "\"\n\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t\tclass=\"crm-webform-active-popup-item-date\"\n\t\t\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\t\t>", "</a>\n\t\t\t\t\t\t"])), main_core.Text.encode(counter.LINK), counterHandler, caption, caption);
	        contentNode.appendChild(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"crm-webform-list-active-popup-row\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t<span class=\"crm-webform-list-entity-counter\">", "</span>\n\t\t\t\t\t\t</div>\t\t\t\t\t\t\n\t\t\t\t\t"])), counterNode, value));
	      });
	      var popupWidth = 160;
	      popup = main_popup.PopupManager.create({
	        id: id,
	        className: 'crm-webform-list-entities-popup',
	        closeByEsc: true,
	        autoHide: true,
	        bindElement: event.target,
	        content: contentNode,
	        angle: {
	          offset: popupWidth / 2 - 16
	        },
	        offsetLeft: -(popupWidth / 2) + event.target.offsetWidth / 2 + 40,
	        animation: 'fading-slide',
	        width: popupWidth,
	        padding: 0
	      });
	      popup.show();
	    };
	    node.addEventListener('click', handler);
	  });
	}
	function _renderActiveSwitchers2() {
	  var _this10 = this;
	  var container = _classPrivateMethodGet(this, _getGridContainer, _getGridContainer2).call(this);
	  if (!container) {
	    return;
	  }
	  var switcherAttr = 'data-crm-form-switcher';
	  var switchers = container.querySelectorAll('[' + switcherAttr + ']');
	  switchers = Array.prototype.slice.call(switchers);
	  switchers.forEach(function (node) {
	    node.innerHTML = '';
	    var data = JSON.parse(node.getAttribute(switcherAttr));
	    var nodeText = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div\n\t\t\t\t\tclass=\"", "\"\n\t\t\t\t>", "</div>\n\t\t\t"])), data.active ? '' : 'crm-webform-list-text-gray', main_core.Text.encode(data.dateActiveShort));
	    var switcher = new BX.UI.Switcher({
	      id: 'crm-form-list-item-' + data.id,
	      checked: data.active,
	      color: 'green',
	      handlers: {
	        toggled: function toggled() {
	          _this10.activate(data.id, switcher.isChecked(), false, nodeText);
	          switcher.isChecked() ? main_core.Dom.removeClass(nodeText, 'crm-webform-list-text-gray') : main_core.Dom.addClass(nodeText, 'crm-webform-list-text-gray');
	        }
	      }
	    });
	    switcher.renderTo(node);
	    var handler = function handler(event) {
	      var id = 'crm-form-grid-active-' + data.id;
	      var popup = main_popup.PopupManager.getPopupById(id);
	      if (popup) {
	        var hide = popup.getId() === id;
	        popup.destroy();
	        popup = null;
	        if (hide) {
	          return;
	        }
	      }
	      var popupWidth = 250;
	      popup = main_popup.PopupManager.create({
	        id: id,
	        className: 'crm-webform-list-active-popup',
	        closeByEsc: true,
	        autoHide: true,
	        angle: {
	          offset: popupWidth / 2 - 16
	        },
	        offsetLeft: -(popupWidth / 2) + event.target.offsetWidth / 2 + 40,
	        animation: 'fading-slide',
	        bindElement: event.target,
	        width: popupWidth,
	        padding: 0,
	        content: main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"crm-webform-list-active-popup-row\">\n\t\t\t\t\t\t\t<div class=\"crm-webform-list-active-popup-item\">\n\t\t\t\t\t\t\t\t<div class=\"crm-webform-active-popup-item-caption\">", "</div>\n\t\t\t\t\t\t\t\t<div class=\"crm-webform-active-popup-item-date\"\n\t\t\t\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\t\t\t>", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<a \n\t\t\t\t\t\t\t\thref=\"", "\"\n\t\t\t\t\t\t\t\tonclick=\"BX.SidePanel.Instance.open('", "')\"\n\t\t\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\t\t\tclass=\"ui-icon ui-icon-common-user crm-webform-active-popup-item-avatar ", "\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<i style=\"background-image: url(", ");\"></i>\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"])), main_core.Text.encode(data.activatedBy.text), main_core.Text.encode(data.dateActiveFull), main_core.Text.encode(data.dateActiveFull), main_core.Text.encode(data.activatedBy.path), main_core.Text.encode(data.activatedBy.path), main_core.Text.encode(data.activatedBy.name), main_core.Text.encode(data.activatedBy.iconClass), encodeURI(main_core.Text.encode(data.activatedBy.iconPath)))
	      });
	      popup.show();
	    };
	    node.appendChild(main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-webform-list-active-desc\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div>\n\t\t\t\t\t\t<a \n\t\t\t\t\t\t\tclass=\"crm-webform-list-active-more\"\n\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t>", "</a>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), nodeText, handler, main_core.Loc.getMessage('CRM_WEBFORM_LIST_BTN_DETAILS')));
	  });
	}
	function _getGrid2() {
	  return BX.Main.gridManager.getInstanceById(this.gridId);
	}
	function _getGridContainer2() {
	  var grid = _classPrivateMethodGet(this, _getGrid, _getGrid2).call(this);
	  if (grid) {
	    return grid.getContainer();
	  }
	}
	function _verifyPhone2(entityType, entityId, runOnVerified) {
	  var _this11 = this;
	  var sliderTitle = main_core.Loc.getMessage('CRM_WEBFORM_PHONE_VERIFY_CUSTOM_SLIDER_TITLE'),
	    title = main_core.Loc.getMessage('CRM_WEBFORM_PHONE_VERIFY_CUSTOM_TITLE'),
	    description = main_core.Loc.getMessage('CRM_WEBFORM_PHONE_VERIFY_CUSTOM_DESCRIPTION_V1');
	  if (typeof bitrix24_phoneverify.PhoneVerify !== 'undefined') {
	    bitrix24_phoneverify.PhoneVerify.getInstance().setEntityType(entityType).setEntityId(entityId).startVerify({
	      sliderTitle: sliderTitle,
	      title: title,
	      description: description
	    }).then(function (verified) {
	      if (verified) {
	        runOnVerified();
	        _this11.reloadGrid();
	      }
	    });
	  } else {
	    runOnVerified();
	  }
	}
	BX.Crm.WebFormList = new WebFormList();

}((this.window = this.window || {}),BX,BX.Main,BX.UI.Dialogs,BX,BX.Bitrix24));
//# sourceMappingURL=script.js.map
