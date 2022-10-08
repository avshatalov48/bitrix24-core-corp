this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_popup,ui_buttons,main_core,ui_dialogs_messagebox) {
	'use strict';

	var _templateObject, _templateObject2;

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var DEFAULT_LANGUAGE_ID = 'en';

	var _getSelectedServer = /*#__PURE__*/new WeakSet();

	var _getLanguageId = /*#__PURE__*/new WeakSet();

	var _getServiceName = /*#__PURE__*/new WeakSet();

	var _showOnlyErrorRowInPopup = /*#__PURE__*/new WeakSet();

	var _buildUsefulErrorText = /*#__PURE__*/new WeakSet();

	var ClientRegistration = /*#__PURE__*/function () {
	  function ClientRegistration(options) {
	    babelHelpers.classCallCheck(this, ClientRegistration);

	    _classPrivateMethodInitSpec(this, _buildUsefulErrorText);

	    _classPrivateMethodInitSpec(this, _showOnlyErrorRowInPopup);

	    _classPrivateMethodInitSpec(this, _getServiceName);

	    _classPrivateMethodInitSpec(this, _getLanguageId);

	    _classPrivateMethodInitSpec(this, _getSelectedServer);

	    babelHelpers.defineProperty(this, "popupContainerId", 'content-register-modal');
	    this.options = options;
	    this.bindEvents();
	  }

	  babelHelpers.createClass(ClientRegistration, [{
	    key: "bindEvents",
	    value: function bindEvents() {}
	  }, {
	    key: "start",
	    value: function start() {
	      var allowedServerPromise = main_core.ajax.runAction('disk.api.integration.b24documents.listAllowedServers').then(function (response) {
	        return response.data.servers;
	      });
	      var warning = main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_WARNING', {
	        '#NAME#': _classPrivateMethodGet(this, _getServiceName, _getServiceName2).call(this)
	      });
	      var popupContent = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form\" id=\"", "\" style=\"padding-top: 20px\">\n\t\t\t\t<div class=\"ui-form-row\" style=\"display: none\">\n\t\t\t\t\t<div class=\"ui-alert ui-alert-danger\">\n\t\t\t\t\t\t<span class=\"ui-alert-message\"></span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t<select class=\"ui-ctl-element\"></select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.popupContainerId, main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_SELECT_SERVER_LABEL'), warning);
	      var popup = new main_popup.Popup({
	        overlay: true,
	        height: 280,
	        width: 350,
	        content: popupContent,
	        closeIcon: true,
	        events: {
	          onAfterClose: function onAfterClose() {
	            return popup.destroy();
	          }
	        },
	        buttons: [new ui_buttons.SaveButton({
	          text: main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_BUTTON'),
	          onclick: this.handleClickRegister.bind(this)
	        })]
	      });
	      popup.show();
	      allowedServerPromise.then(function (servers) {
	        var select = popupContent.querySelector('select');
	        servers.forEach(function (server) {
	          var regionSuffix = server.region ? " (".concat(server.region, ")") : '';
	          select.add(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<option value=\"", "\">", "", "</option>"])), server.proxy, server.proxy, regionSuffix));
	        });
	      });
	    }
	  }, {
	    key: "handleClickRegister",
	    value: function handleClickRegister(button, event) {
	      var _this = this;

	      button.setDisabled();
	      var loader = new BX.Loader({
	        target: button.getContainer(),
	        size: 45
	      });
	      loader.show();
	      main_core.ajax.runAction('disk.api.integration.b24documents.registerCloudClient', {
	        data: {
	          serviceUrl: _classPrivateMethodGet(this, _getSelectedServer, _getSelectedServer2).call(this),
	          languageId: _classPrivateMethodGet(this, _getLanguageId, _getLanguageId2).call(this)
	        }
	      }).then(function () {
	        document.location.reload();
	      })["catch"](function (response) {
	        console.warn('Registration error', response);
	        loader.hide();

	        _classPrivateMethodGet(_this, _showOnlyErrorRowInPopup, _showOnlyErrorRowInPopup2).call(_this, _classPrivateMethodGet(_this, _buildUsefulErrorText, _buildUsefulErrorText2).call(_this, response.errors || []));
	      });
	    }
	  }]);
	  return ClientRegistration;
	}();

	function _getSelectedServer2() {
	  var selectNode = document.querySelector("#".concat(this.popupContainerId, " select"));

	  if (!selectNode) {
	    return '';
	  }

	  return selectNode.value;
	}

	function _getLanguageId2() {
	  return main_core.Loc.hasMessage('LANGUAGE_ID') ? main_core.Loc.getMessage('LANGUAGE_ID') : DEFAULT_LANGUAGE_ID;
	}

	function _getServiceName2() {
	  return main_core.Extension.getSettings('disk.b24-documents-client-registration').get('serviceName');
	}

	function _showOnlyErrorRowInPopup2(message) {
	  var rows = document.querySelectorAll("#".concat(this.popupContainerId, " .ui-form-row"));
	  rows.forEach(function (row) {
	    row.style.display = 'none';
	  });
	  rows[0].style.display = '';
	  rows[0].querySelector('.ui-alert-message').textContent = message;
	}

	function _buildUsefulErrorText2(errors) {
	  var _iterator = _createForOfIteratorHelper(errors),
	      _step;

	  try {
	    for (_iterator.s(); !(_step = _iterator.n()).done;) {
	      var error = _step.value;

	      if (error.code === 'tariff_restriction') {
	        return main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_ERROR_AFTER_REG', {
	          '#NAME#': _classPrivateMethodGet(this, _getServiceName, _getServiceName2).call(this)
	        });
	      }

	      if (error.code === 'should_show_in_ui') {
	        return error.message;
	      }

	      if (error.code === 'domain_verification') {
	        return main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_ERROR_DOMAIN_VERIFICATION', {
	          '#NAME#': _classPrivateMethodGet(this, _getServiceName, _getServiceName2).call(this),
	          '#DOMAIN#': error.customData.domain
	        });
	      }
	    }
	  } catch (err) {
	    _iterator.e(err);
	  } finally {
	    _iterator.f();
	  }

	  return main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_ERROR_COMMON');
	}

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var DEFAULT_LANGUAGE_ID$1 = 'en';

	var _getLanguageId$1 = /*#__PURE__*/new WeakSet();

	var _getServiceName$1 = /*#__PURE__*/new WeakSet();

	var _buildUsefulErrorText$1 = /*#__PURE__*/new WeakSet();

	var ClientUnRegistration = /*#__PURE__*/function () {
	  function ClientUnRegistration(options) {
	    babelHelpers.classCallCheck(this, ClientUnRegistration);

	    _classPrivateMethodInitSpec$1(this, _buildUsefulErrorText$1);

	    _classPrivateMethodInitSpec$1(this, _getServiceName$1);

	    _classPrivateMethodInitSpec$1(this, _getLanguageId$1);

	    babelHelpers.defineProperty(this, "popupContainerId", 'content-register-modal');
	    this.options = options;
	    this.bindEvents();
	  }

	  babelHelpers.createClass(ClientUnRegistration, [{
	    key: "bindEvents",
	    value: function bindEvents() {}
	  }, {
	    key: "start",
	    value: function start() {
	      var _this = this;

	      this.messageBox = ui_dialogs_messagebox.MessageBox.create({
	        title: main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_UNREGISTRATION_TITLE', {
	          '#NAME#': _classPrivateMethodGet$1(this, _getServiceName$1, _getServiceName2$1).call(this)
	        }),
	        message: main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_UNREGISTRATION_MSG', {
	          '#NAME#': _classPrivateMethodGet$1(this, _getServiceName$1, _getServiceName2$1).call(this)
	        }),
	        buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_UNREGISTRATION_UNREGISTER_BTN'),
	        onOk: function onOk() {
	          console.log(_this);

	          _this.handleClickUnregister();
	        }
	      });
	      this.messageBox.show();
	    }
	  }, {
	    key: "handleClickUnregister",
	    value: function handleClickUnregister() {
	      var _this2 = this;

	      main_core.ajax.runAction('disk.api.integration.b24documents.unregisterCloudClient', {
	        data: {
	          languageId: _classPrivateMethodGet$1(this, _getLanguageId$1, _getLanguageId2$1).call(this)
	        }
	      }).then(function () {
	        document.location.reload();
	      })["catch"](function (response) {
	        console.warn('Unregistration error', response);

	        _this2.messageBox.setMessage(_classPrivateMethodGet$1(_this2, _buildUsefulErrorText$1, _buildUsefulErrorText2$1).call(_this2, response.errors || []));
	      });
	    }
	  }]);
	  return ClientUnRegistration;
	}();

	function _getLanguageId2$1() {
	  return main_core.Loc.hasMessage('LANGUAGE_ID') ? main_core.Loc.getMessage('LANGUAGE_ID') : DEFAULT_LANGUAGE_ID$1;
	}

	function _getServiceName2$1() {
	  return main_core.Extension.getSettings('disk.b24-documents-client-registration').get('serviceName');
	}

	function _buildUsefulErrorText2$1(errors) {
	  var _iterator = _createForOfIteratorHelper$1(errors),
	      _step;

	  try {
	    for (_iterator.s(); !(_step = _iterator.n()).done;) {
	      var error = _step.value;

	      if (error.code === 'should_show_in_ui') {
	        return error.message;
	      }
	    }
	  } catch (err) {
	    _iterator.e(err);
	  } finally {
	    _iterator.f();
	  }

	  return main_core.Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_UNREGISTRATION_ERROR_COMMON');
	}

	exports.ClientRegistration = ClientRegistration;
	exports.ClientUnRegistration = ClientUnRegistration;

}((this.BX.Disk.B24Documents = this.BX.Disk.B24Documents || {}),BX.Main,BX.UI,BX,BX.UI.Dialogs));
//# sourceMappingURL=disk.b24-documents-client-registration.bundle.js.map
