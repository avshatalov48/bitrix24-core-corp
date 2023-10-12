this.BX = this.BX || {};
(function (exports,main_core,main_popup,ui_tour) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	function _classStaticPrivateMethodGet(receiver, classConstructor, method) { _classCheckPrivateStaticAccess(receiver, classConstructor); return method; }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	var _options = /*#__PURE__*/new WeakMap();
	var _spotLight = /*#__PURE__*/new WeakMap();
	var _guide = /*#__PURE__*/new WeakMap();
	var _getSpotlight = /*#__PURE__*/new WeakSet();
	var _getGuide = /*#__PURE__*/new WeakSet();
	var KeysGrid = /*#__PURE__*/function () {
	  function KeysGrid(options) {
	    babelHelpers.classCallCheck(this, KeysGrid);
	    _classPrivateMethodInitSpec(this, _getGuide);
	    _classPrivateMethodInitSpec(this, _getSpotlight);
	    _classPrivateFieldInitSpec(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _spotLight, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _guide, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _options, options);
	  }
	  babelHelpers.createClass(KeysGrid, [{
	    key: "showOnboarding",
	    value: function showOnboarding() {
	      var _this = this;
	      if (!(main_popup.PopupWindowManager && main_popup.PopupWindowManager.isAnyPopupShown())) {
	        _classPrivateMethodGet(this, _getSpotlight, _getSpotlight2).call(this).show();
	        _classPrivateMethodGet(this, _getGuide, _getGuide2).call(this).start();
	        _classPrivateMethodGet(this, _getSpotlight, _getSpotlight2).call(this).getTargetContainer().addEventListener('mouseover', function () {
	          _classPrivateMethodGet(_this, _getSpotlight, _getSpotlight2).call(_this).close();
	        });
	      }
	    }
	  }], [{
	    key: "deleteRow",
	    value: function deleteRow(id) {
	      var grid = BX.Main.gridManager.getInstanceById(KeysGrid.gridId);
	      grid.confirmDialog({
	        CONFIRM: true,
	        CONFIRM_MESSAGE: main_core.Loc.getMessage('CC_BBKL_ACTION_MENU_DELETE_CONF')
	      }, function () {
	        main_core.ajax.runComponentAction(KeysGrid.componentName, 'deleteRow', {
	          mode: 'class',
	          data: {
	            id: id
	          }
	        }).then(function () {
	          grid.removeRow(id);
	        });
	      });
	    }
	  }, {
	    key: "activateKey",
	    value: function activateKey(id, switcher) {
	      var _this2 = this;
	      main_core.ajax.runComponentAction(KeysGrid.componentName, 'activateKey', {
	        mode: 'class',
	        data: {
	          id: id
	        }
	      }).then(function (response) {
	        if (response.data === false) {
	          switcher.check(false, false);
	          _classStaticPrivateMethodGet(_this2, KeysGrid, _showNotifyKeySwitcherError).call(_this2, false);
	        }
	      })["catch"](function () {
	        switcher.check(false, false);
	        _classStaticPrivateMethodGet(_this2, KeysGrid, _showNotifyKeySwitcherError).call(_this2, false);
	      });
	    }
	  }, {
	    key: "deactivateKey",
	    value: function deactivateKey(id, switcher) {
	      var _this3 = this;
	      main_core.ajax.runComponentAction('bitrix:biconnector.key.list', 'deactivateKey', {
	        mode: 'class',
	        data: {
	          id: id
	        }
	      }).then(function (response) {
	        if (response.data === false) {
	          switcher.check(true, false);
	          _classStaticPrivateMethodGet(_this3, KeysGrid, _showNotifyKeySwitcherError).call(_this3);
	        }
	      })["catch"](function () {
	        switcher.check(true, false);
	        _classStaticPrivateMethodGet(_this3, KeysGrid, _showNotifyKeySwitcherError).call(_this3);
	      });
	    }
	  }, {
	    key: "copyKey",
	    value: function copyKey(elementWrapper) {
	      var access_key = elementWrapper.querySelector('[data-key-id]');
	      var textarea = document.createElement('textarea');
	      textarea.value = access_key.value;
	      textarea.setAttribute('readonly', '');
	      textarea.style.position = 'absolute';
	      textarea.style.left = '-9999px';
	      document.body.appendChild(textarea);
	      textarea.select();
	      try {
	        document.execCommand('copy');
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CC_BBKL_KEY_COPIED'),
	          autoHideDelay: 2000
	        });
	      } catch (error) {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CC_BBKL_KEY_COPY_ERROR'),
	          autoHideDelay: 2000
	        });
	      }
	      textarea.remove();
	      return false;
	    }
	  }]);
	  return KeysGrid;
	}();
	function _showNotifyKeySwitcherError() {
	  BX.UI.Notification.Center.notify({
	    content: main_core.Loc.getMessage('CC_BBKL_ACTIVATE_KEY_ERROR'),
	    autoHideDelay: 2000
	  });
	}
	function _getSpotlight2() {
	  if (babelHelpers.classPrivateFieldGet(this, _spotLight)) {
	    return babelHelpers.classPrivateFieldGet(this, _spotLight);
	  }
	  babelHelpers.classPrivateFieldSet(this, _spotLight, new BX.SpotLight({
	    targetElement: babelHelpers.classPrivateFieldGet(this, _options).bindElement,
	    targetVertex: 'middle-center',
	    id: KeysGrid.gridId,
	    lightMode: true
	  }));
	  return babelHelpers.classPrivateFieldGet(this, _spotLight);
	}
	function _getGuide2() {
	  var _this4 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _guide)) {
	    return babelHelpers.classPrivateFieldGet(this, _guide);
	  }
	  babelHelpers.classPrivateFieldSet(this, _guide, new ui_tour.Guide({
	    simpleMode: true,
	    onEvents: true,
	    overlay: false,
	    steps: [{
	      target: babelHelpers.classPrivateFieldGet(this, _options).bindElement,
	      title: main_core.Loc.getMessage('CC_BBKL_KEY_ONBOARDING_TITLE'),
	      text: main_core.Loc.getMessage('CC_BBKL_KEY_ONBOARDING_DESCRIPTION'),
	      buttons: null,
	      events: {
	        onClose: function onClose() {
	          _classPrivateMethodGet(_this4, _getSpotlight, _getSpotlight2).call(_this4).close();
	        },
	        onShow: function onShow() {
	          main_core.ajax.runComponentAction(KeysGrid.componentName, 'markShowOnboarding', {
	            mode: 'class'
	          });
	        }
	      },
	      article: babelHelpers.classPrivateFieldGet(this, _options).article
	    }],
	    autoHide: true
	  }));
	  babelHelpers.classPrivateFieldGet(this, _guide).getPopup().setWidth(360);
	  babelHelpers.classPrivateFieldGet(this, _guide).getPopup().setAngle({
	    offset: babelHelpers.classPrivateFieldGet(this, _options).bindElement.offsetWidth / 2
	  });
	  babelHelpers.classPrivateFieldGet(this, _guide).getPopup().setAutoHide(true);
	  return babelHelpers.classPrivateFieldGet(this, _guide);
	}
	babelHelpers.defineProperty(KeysGrid, "gridId", 'biconnector_key_list');
	babelHelpers.defineProperty(KeysGrid, "componentName", 'bitrix:biconnector.key.list');

	exports.KeysGrid = KeysGrid;

}((this.BX.BIConnector = this.BX.BIConnector || {}),BX,BX.Main,BX.UI.Tour));
//# sourceMappingURL=script.js.map
