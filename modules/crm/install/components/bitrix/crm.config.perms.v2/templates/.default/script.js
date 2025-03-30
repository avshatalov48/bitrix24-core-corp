/* eslint-disable */
(function (exports,main_core,ui_dialogs_messagebox,ui_buttons,ui_accessrights_v2) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm');
	var _addWrapperSliderContent = /*#__PURE__*/new WeakSet();
	var _addWrapperLeftMenu = /*#__PURE__*/new WeakSet();
	var _renderHelpButton = /*#__PURE__*/new WeakSet();
	var _runGetDataAjaxRequest = /*#__PURE__*/new WeakSet();
	var _confirmBeforeRedraw = /*#__PURE__*/new WeakSet();
	var _showNotification = /*#__PURE__*/new WeakSet();
	var ConfigPermsComponent = /*#__PURE__*/function () {
	  function ConfigPermsComponent(config) {
	    babelHelpers.classCallCheck(this, ConfigPermsComponent);
	    _classPrivateMethodInitSpec(this, _showNotification);
	    _classPrivateMethodInitSpec(this, _confirmBeforeRedraw);
	    _classPrivateMethodInitSpec(this, _runGetDataAjaxRequest);
	    _classPrivateMethodInitSpec(this, _renderHelpButton);
	    _classPrivateMethodInitSpec(this, _addWrapperLeftMenu);
	    _classPrivateMethodInitSpec(this, _addWrapperSliderContent);
	    this.AccessRightsOption = config.AccessRightsOption;
	    this.AccessRights = config.AccessRights;
	    this.hasLeftMenu = config.hasLeftMenu;
	    this.menuId = config.menuId;
	  }
	  babelHelpers.createClass(ConfigPermsComponent, [{
	    key: "init",
	    value: function init() {
	      this.AccessRights.draw();
	      _classPrivateMethodGet(this, _renderHelpButton, _renderHelpButton2).call(this);
	      if (this.hasLeftMenu) {
	        _classPrivateMethodGet(this, _addWrapperSliderContent, _addWrapperSliderContent2).call(this);
	        _classPrivateMethodGet(this, _addWrapperLeftMenu, _addWrapperLeftMenu2).call(this);
	      }
	    }
	  }, {
	    key: "openPermission",
	    value: function openPermission(controllerData) {
	      if (this.menuId === controllerData.menuId) {
	        return;
	      }
	      if (!this.AccessRights.hasUnsavedChanges()) {
	        this.redrawAccessRight(controllerData);
	      } else {
	        event.stopImmediatePropagation();
	        _classPrivateMethodGet(this, _confirmBeforeRedraw, _confirmBeforeRedraw2).call(this, controllerData);
	      }
	    }
	  }, {
	    key: "redrawAccessRight",
	    value: function redrawAccessRight(controllerData) {
	      var _this = this;
	      var loader = new BX.Loader({
	        target: document.getElementById('bx-crm-perms-config-permissions')
	      });
	      this.AccessRights.destroy();
	      loader.show();
	      _classPrivateMethodGet(this, _runGetDataAjaxRequest, _runGetDataAjaxRequest2).call(this, controllerData).then(function (_ref) {
	        var accessRightsData = _ref.accessRightsData,
	          maxVisibleUserGroups = _ref.maxVisibleUserGroups,
	          additionalSaveParams = _ref.additionalSaveParams;
	        _this.AccessRightsOption.userGroups = accessRightsData.userGroups;
	        _this.AccessRightsOption.accessRights = accessRightsData.accessRights;
	        _this.AccessRightsOption.maxVisibleUserGroups = maxVisibleUserGroups;
	        _this.AccessRightsOption.additionalSaveParams = additionalSaveParams;
	        _this.AccessRights = new ui_accessrights_v2.App(_this.AccessRightsOption);
	        _this.AccessRights.draw();
	        scrollTo({
	          top: 0
	        });
	        _this.menuId = controllerData.menuId;
	      })["catch"](function (response) {
	        var _response$errors, _response$errors$;
	        console.warn('ui.accessrights.v2: error during redraw', response);
	        _classPrivateMethodGet(_this, _showNotification, _showNotification2).call(_this, (response === null || response === void 0 ? void 0 : (_response$errors = response.errors) === null || _response$errors === void 0 ? void 0 : (_response$errors$ = _response$errors[0]) === null || _response$errors$ === void 0 ? void 0 : _response$errors$.message) || 'Something went wrong');
	      })["finally"](function () {
	        loader.hide();
	      });
	    }
	  }]);
	  return ConfigPermsComponent;
	}();
	function _addWrapperSliderContent2() {
	  var sliderContent = document.getElementById('ui-page-slider-content');
	  if (sliderContent) {
	    var wrapperSliderContent = document.createElement('div');
	    wrapperSliderContent.className = 'crm-config-perms-v2-slider-content';
	    sliderContent.parentNode.insertBefore(wrapperSliderContent, sliderContent);
	    wrapperSliderContent.appendChild(sliderContent);
	  }
	}
	function _addWrapperLeftMenu2() {
	  var leftPanel = document.getElementById('left-panel');
	  if (leftPanel) {
	    var wrapperLeftMenu = document.createElement('div');
	    wrapperLeftMenu.className = 'crm-config-perms-v2-sidebar';
	    leftPanel.parentNode.insertBefore(wrapperLeftMenu, leftPanel);
	    wrapperLeftMenu.appendChild(leftPanel);
	  }
	}
	function _renderHelpButton2() {
	  var Helper = main_core.Reflection.getClass('top.BX.Helper');
	  var helpButton = new ui_buttons.Button({
	    size: ui_buttons.ButtonSize.MEDIUM,
	    color: ui_buttons.ButtonColor.LIGHT_BORDER,
	    text: main_core.Loc.getMessage('CRM_CONFIG_PERMS_HELP'),
	    noCaps: true,
	    onclick: function onclick() {
	      var articleCode = '23240636'; // todo replace with the real article code

	      Helper === null || Helper === void 0 ? void 0 : Helper.show("redirect=detail&code=".concat(articleCode));
	    }
	  });
	  var parentElement = document.querySelector('.crm-config-perms-v2-header');
	  helpButton.renderTo(parentElement);
	}
	function _runGetDataAjaxRequest2(controllerData) {
	  return new Promise(function (resolve, reject) {
	    main_core.ajax.runComponentAction('bitrix:crm.config.perms.v2', 'getData', {
	      mode: 'class',
	      data: {
	        controllerData: controllerData
	      }
	    }).then(function (response) {
	      resolve(response.data);
	    })["catch"](reject);
	  });
	}
	function _confirmBeforeRedraw2(controllerData) {
	  var _this2 = this;
	  var box = ui_dialogs_messagebox.MessageBox.create({
	    message: main_core.Loc.getMessage('CRM_CONFIG_PERMS_SAVE_POPUP_TITLE'),
	    modal: true,
	    buttons: [new ui_buttons.SaveButton({
	      size: ui_buttons.ButtonSize.SMALL,
	      color: ui_buttons.ButtonColor.PRIMARY,
	      onclick: function onclick(button) {
	        button.setWaiting(true);
	        _this2.AccessRights.sendActionRequest().then(function () {
	          document.querySelector("[data-menu-id=\"".concat(controllerData.menuId, "\"]")).click();
	        })["catch"]()["finally"](function () {
	          box.close();
	        });
	      }
	    }), new ui_buttons.CancelButton({
	      text: main_core.Loc.getMessage('CRM_CONFIG_PERMS_SAVE_POPUP_CANCEL'),
	      size: ui_buttons.ButtonSize.SMALL,
	      onclick: function onclick() {
	        box.close();
	      }
	    })]
	  });
	  box.show();
	}
	function _showNotification2(title) {
	  BX.UI.Notification.Center.notify({
	    content: title,
	    position: 'top-right',
	    autoHideDelay: 3000
	  });
	}
	namespace.ConfigPermsComponent = ConfigPermsComponent;

}((this.window = this.window || {}),BX,BX.UI.Dialogs,BX.UI,BX.UI.AccessRights.V2));
//# sourceMappingURL=script.js.map
