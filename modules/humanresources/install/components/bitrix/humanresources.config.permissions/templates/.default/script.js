/* eslint-disable */
(function (exports,main_core,ui_accessrights_v2,main_loader,ui_buttons,ui_dialogs_messagebox) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Humanresources');
	var _addWrapperSliderContent = /*#__PURE__*/new WeakSet();
	var _addWrapperLeftMenu = /*#__PURE__*/new WeakSet();
	var _confirmBeforeRedraw = /*#__PURE__*/new WeakSet();
	var _handleSaveButtonClick = /*#__PURE__*/new WeakSet();
	var _runGetDataAjaxRequest = /*#__PURE__*/new WeakSet();
	var ConfigPermsComponent = /*#__PURE__*/function () {
	  function ConfigPermsComponent(config) {
	    babelHelpers.classCallCheck(this, ConfigPermsComponent);
	    _classPrivateMethodInitSpec(this, _runGetDataAjaxRequest);
	    _classPrivateMethodInitSpec(this, _handleSaveButtonClick);
	    _classPrivateMethodInitSpec(this, _confirmBeforeRedraw);
	    _classPrivateMethodInitSpec(this, _addWrapperLeftMenu);
	    _classPrivateMethodInitSpec(this, _addWrapperSliderContent);
	    this.accessRightsOption = config.accessRightsOption;
	    this.accessRights = config.accessRights;
	    this.menuId = config.menuId;
	  }
	  babelHelpers.createClass(ConfigPermsComponent, [{
	    key: "init",
	    value: function init() {
	      this.accessRights.draw();
	      this.isRendered = true;
	      _classPrivateMethodGet(this, _addWrapperSliderContent, _addWrapperSliderContent2).call(this);
	      _classPrivateMethodGet(this, _addWrapperLeftMenu, _addWrapperLeftMenu2).call(this);
	    }
	  }, {
	    key: "openPermission",
	    value: function openPermission(parameters) {
	      if (parameters.menuId === this.menuId) {
	        return;
	      }
	      if (this.isRendered && this.accessRights.hasUnsavedChanges()) {
	        event.stopImmediatePropagation();
	        _classPrivateMethodGet(this, _confirmBeforeRedraw, _confirmBeforeRedraw2).call(this, parameters.menuId);
	      } else {
	        this.redrawAccessRight(parameters);
	      }
	    }
	  }, {
	    key: "redrawAccessRight",
	    value: function redrawAccessRight(parameters) {
	      var _this = this;
	      if (this.isRendered) {
	        this.accessRights.destroy();
	      }
	      this.menuId = parameters.menuId;
	      this.isRendered = false;
	      var loader = new main_loader.Loader({
	        target: document.getElementById('bx-hr-permission-config-role-main-container')
	      });
	      loader.show();
	      _classPrivateMethodGet(this, _runGetDataAjaxRequest, _runGetDataAjaxRequest2).call(this, parameters).then(function (data) {
	        if (data.userGroups && data.accessRights) {
	          _this.accessRightsOption.userGroups = data.userGroups;
	          _this.accessRightsOption.accessRights = data.accessRights;
	          _this.accessRightsOption.additionalSaveParams = {
	            category: parameters.category
	          };
	          _this.accessRightsOption.loadParams = {
	            category: parameters.category
	          };
	          _this.accessRights = new ui_accessrights_v2.App(_this.accessRightsOption);
	          scrollTo({
	            top: 0
	          });
	        }
	        _this.accessRights.draw();
	        _this.isRendered = true;
	      })["catch"](function () {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('HUMANRESOURCES_CONFIG_PERMISSIONS_ERROR'),
	          position: 'top-right',
	          autoHideDelay: 3000
	        });
	      })["finally"](function () {
	        loader.destroy();
	      });
	    }
	  }]);
	  return ConfigPermsComponent;
	}();
	function _addWrapperSliderContent2() {
	  var sliderContent = document.getElementById('ui-page-slider-content');
	  if (sliderContent) {
	    var wrapperSliderContent = main_core.Dom.create('div', {
	      props: {
	        className: 'hr-permission-config-role-main-content'
	      }
	    });
	    wrapperSliderContent.className = 'hr-permission-config-role-main-content';
	    main_core.Dom.insertBefore(wrapperSliderContent, sliderContent);
	    main_core.Dom.append(sliderContent, wrapperSliderContent);
	  }
	}
	function _addWrapperLeftMenu2() {
	  var leftPanel = document.getElementById('left-panel');
	  if (leftPanel) {
	    var wrapperLeftMenu = main_core.Dom.create('div', {
	      props: {
	        className: 'hr-permission-config-role-main-left-menu'
	      }
	    });
	    main_core.Dom.insertBefore(wrapperLeftMenu, leftPanel);
	    main_core.Dom.append(leftPanel, wrapperLeftMenu);
	  }
	}
	function _confirmBeforeRedraw2(menuId) {
	  var _this2 = this;
	  var messageBox = ui_dialogs_messagebox.MessageBox.create({
	    message: main_core.Loc.getMessage('HUMANRESOURCES_CONFIG_PERMISSIONS_SAVE_MESSAGEBOX_TEXT'),
	    modal: true,
	    buttons: [new ui_buttons.SaveButton({
	      size: ui_buttons.ButtonSize.SMALL,
	      color: ui_buttons.ButtonColor.PRIMARY,
	      onclick: function onclick(button) {
	        _classPrivateMethodGet(_this2, _handleSaveButtonClick, _handleSaveButtonClick2).call(_this2, button, menuId, messageBox);
	      }
	    }), new ui_buttons.CancelButton({
	      size: ui_buttons.ButtonSize.SMALL,
	      onclick: function onclick() {
	        messageBox.close();
	      }
	    })]
	  });
	  messageBox.show();
	}
	function _handleSaveButtonClick2(button, menuId, messageBox) {
	  button.setWaiting(true);
	  this.accessRights.sendActionRequest().then(function () {
	    document.querySelector("[data-menu-id=\"".concat(menuId, "\"]")).click();
	  })["catch"](function () {})["finally"](function () {
	    messageBox.close();
	  });
	}
	function _runGetDataAjaxRequest2(parameters) {
	  return new Promise(function (resolve, reject) {
	    main_core.ajax.runComponentAction('bitrix:humanresources.config.permissions', 'getData', {
	      mode: 'class',
	      data: {
	        parameters: parameters
	      }
	    }).then(function (response) {
	      resolve(response.data);
	    })["catch"](reject);
	  });
	}
	namespace.ConfigPermsComponent = ConfigPermsComponent;

}((this.window = this.window || {}),BX,BX.UI.AccessRights.V2,BX,BX.UI,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map
