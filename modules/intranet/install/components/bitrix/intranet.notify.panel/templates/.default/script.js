this.BX = this.BX || {};
(function (exports,main_popup,ui_buttons,ui_designTokens,main_core,main_date) {
	'use strict';

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cache = /*#__PURE__*/new WeakMap();
	var _getPopup = /*#__PURE__*/new WeakSet();
	var _getContent = /*#__PURE__*/new WeakSet();
	var _getDate = /*#__PURE__*/new WeakSet();
	var _getDescriptionWithPartner = /*#__PURE__*/new WeakSet();
	var _getButtons = /*#__PURE__*/new WeakSet();
	var _getPartnerButton = /*#__PURE__*/new WeakSet();
	var _getRenewalButton = /*#__PURE__*/new WeakSet();
	var _getMoreInformationButton = /*#__PURE__*/new WeakSet();
	var LicenseNotificationPopup = /*#__PURE__*/function () {
	  function LicenseNotificationPopup(options) {
	    babelHelpers.classCallCheck(this, LicenseNotificationPopup);
	    _classPrivateMethodInitSpec(this, _getMoreInformationButton);
	    _classPrivateMethodInitSpec(this, _getRenewalButton);
	    _classPrivateMethodInitSpec(this, _getPartnerButton);
	    _classPrivateMethodInitSpec(this, _getButtons);
	    _classPrivateMethodInitSpec(this, _getDescriptionWithPartner);
	    _classPrivateMethodInitSpec(this, _getDate);
	    _classPrivateMethodInitSpec(this, _getContent);
	    _classPrivateMethodInitSpec(this, _getPopup);
	    _classPrivateFieldInitSpec(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setOptions(options);
	  }
	  babelHelpers.createClass(LicenseNotificationPopup, [{
	    key: "setOptions",
	    value: function setOptions(options) {
	      babelHelpers.classPrivateFieldGet(this, _cache).set('options', options);
	    }
	  }, {
	    key: "getOptions",
	    value: function getOptions() {
	      return babelHelpers.classPrivateFieldGet(this, _cache).get('options', null);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      _classPrivateMethodGet(this, _getPopup, _getPopup2).call(this).show();
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      _classPrivateMethodGet(this, _getPopup, _getPopup2).call(this).close();
	    } //endregion
	  }]);
	  return LicenseNotificationPopup;
	}();
	function _getPopup2() {
	  var _this = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('popup', function () {
	    return new main_popup.Popup({
	      className: 'bitrix24-notify-popup',
	      width: 800,
	      padding: 0,
	      content: _classPrivateMethodGet(_this, _getContent, _getContent2).call(_this),
	      contentBackground: "transparent",
	      overlay: true,
	      closeIcon: true,
	      titleBar: false,
	      buttons: _classPrivateMethodGet(_this, _getButtons, _getButtons2).call(_this),
	      events: {
	        onShow: function onShow() {
	          main_core.ajax.runComponentAction(NotifyManager.componentName, 'setLicenseNotifyConfig', {
	            mode: 'class',
	            data: {
	              type: _this.getOptions().type
	            }
	          });
	        }
	      }
	    });
	  });
	}
	function _getContent2() {
	  var _this2 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('popup-content', function () {
	    return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"bitrix24-notify-popup-inner\">\n\t\t\t\t\t<div class=\"bitrix24-notify-popup-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"bitrix24-notify-popup-block\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _this2.getOptions().isExpired ? main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_TITLE_EXPIRED', {
	      '#DAY_MONTH#': _classPrivateMethodGet(_this2, _getDate, _getDate2).call(_this2)
	    }) : main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_TITLE', {
	      '#DAY_MONTH#': _classPrivateMethodGet(_this2, _getDate, _getDate2).call(_this2)
	    }), _this2.getOptions().isExpired ? main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_EXPIRED_DESCRIPTION_1', {
	      '#DAY_MONTH#': _classPrivateMethodGet(_this2, _getDate, _getDate2).call(_this2)
	    }) : main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_DESCRIPTION_1'), _this2.getOptions().isPortalWithPartner ? _classPrivateMethodGet(_this2, _getDescriptionWithPartner, _getDescriptionWithPartner2).call(_this2) : null);
	  });
	}
	function _getDate2() {
	  var _this3 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('date', function () {
	    var format = main_date.DateTimeFormat.getFormat('DAY_MONTH_FORMAT');
	    if (_this3.getOptions().isExpired) {
	      return main_date.DateTimeFormat.format(format, Number(_this3.getOptions().blockDate));
	    }
	    return main_date.DateTimeFormat.format(format, Number(_this3.getOptions().expireDate));
	  });
	}
	function _getDescriptionWithPartner2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('description-partner', function () {
	    return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"bitrix24-notify-popup-block\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_DESCRIPTION_2'));
	  });
	}
	function _getButtons2() {
	  return [_classPrivateMethodGet(this, _getRenewalButton, _getRenewalButton2).call(this), this.getOptions().isAdmin ? _classPrivateMethodGet(this, _getPartnerButton, _getPartnerButton2).call(this) : null, _classPrivateMethodGet(this, _getMoreInformationButton, _getMoreInformationButton2).call(this)];
	}
	function _getPartnerButton2() {
	  var _this4 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('partner-button', function () {
	    if (!_this4.getOptions().isPortalWithPartner) {
	      return null;
	    }
	    return new ui_buttons.Button({
	      color: ui_buttons.Button.Color.LIGHT_BORDER,
	      text: main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_BUTTON_PARTNER'),
	      round: true,
	      onclick: function onclick() {
	        window.open(_this4.getOptions().urlBuyWithPartner, '_self');
	      }
	    });
	  });
	}
	function _getRenewalButton2() {
	  var _this5 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('renewal-button', function () {
	    return new ui_buttons.Button({
	      color: ui_buttons.Button.Color.SUCCESS,
	      text: main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_BUTTON_RENEW_LICENSE'),
	      round: true,
	      onclick: function onclick() {
	        window.open(_this5.getOptions().urlDefaultBuy, '_blank');
	      }
	    });
	  });
	}
	function _getMoreInformationButton2() {
	  var _this6 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('more-button', function () {
	    return new ui_buttons.Button({
	      color: ui_buttons.Button.Color.LIGHT_BORDER,
	      text: main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_BUTTON_MORE'),
	      round: true,
	      onclick: function onclick() {
	        window.open(_this6.getOptions().urlArticle, '_blank');
	      }
	    });
	  });
	}

	var _templateObject$1;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cache$1 = /*#__PURE__*/new WeakMap();
	var _getParams = /*#__PURE__*/new WeakSet();
	var _getPanel = /*#__PURE__*/new WeakSet();
	var _getColorClass = /*#__PURE__*/new WeakSet();
	var _getMessage = /*#__PURE__*/new WeakSet();
	var _getBlockDate = /*#__PURE__*/new WeakSet();
	var NotifyPanel = /*#__PURE__*/function () {
	  function NotifyPanel(options) {
	    babelHelpers.classCallCheck(this, NotifyPanel);
	    _classPrivateMethodInitSpec$1(this, _getBlockDate);
	    _classPrivateMethodInitSpec$1(this, _getMessage);
	    _classPrivateMethodInitSpec$1(this, _getColorClass);
	    _classPrivateMethodInitSpec$1(this, _getPanel);
	    _classPrivateMethodInitSpec$1(this, _getParams);
	    _classPrivateFieldInitSpec$1(this, _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setOptions(options);
	  }
	  babelHelpers.createClass(NotifyPanel, [{
	    key: "setOptions",
	    value: function setOptions(options) {
	      babelHelpers.classPrivateFieldGet(this, _cache$1).set('options', options);
	    }
	  }, {
	    key: "getOptions",
	    value: function getOptions() {
	      return babelHelpers.classPrivateFieldGet(this, _cache$1).get('options', null);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var mainTable = document.querySelector('.bx-layout-table');
	      if (mainTable) {
	        main_core.Dom.insertBefore(_classPrivateMethodGet$1(this, _getPanel, _getPanel2).call(this), mainTable);
	      }
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (main_core.Dom.hasClass(_classPrivateMethodGet$1(this, _getPanel, _getPanel2).call(this), _classStaticPrivateFieldSpecGet(NotifyPanel, NotifyPanel, _classActivity))) {
	        main_core.Dom.removeClass(_classPrivateMethodGet$1(this, _getPanel, _getPanel2).call(this), _classStaticPrivateFieldSpecGet(NotifyPanel, NotifyPanel, _classActivity));
	      }
	    }
	  }]);
	  return NotifyPanel;
	}();
	function _getParams2() {
	  return this.getOptions().params;
	}
	function _getPanel2() {
	  var _this = this;
	  var onclick = function onclick() {
	    _this.close();
	  };
	  return babelHelpers.classPrivateFieldGet(this, _cache$1).remember('panel-template', function () {
	    return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"bx24-tariff-notify bx24-tariff-notify-show bx24-tariff-notify-panel\">\n\t\t\t\t\t<div class=\"bx24-tariff-notify-wrap ", "\">\n\t\t\t\t\t\t<span class=\"bx24-tariff-notify-text\">\n\t\t\t\t\t\t ", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span onclick=\"", "\" class=\"bx24-tariff-notify-text-reload\">\n\t\t\t\t\t\t\t<span class=\"bx24-tariff-notify-text-reload-title\">\n\t\t\t\t\t\t\t\tx\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), _classPrivateMethodGet$1(_this, _getColorClass, _getColorClass2).call(_this), _classPrivateMethodGet$1(_this, _getMessage, _getMessage2).call(_this), onclick);
	  });
	}
	function _getColorClass2() {
	  return this.getOptions().color && this.getOptions().color === 'blue' ? 'bx24-tariff-notify-blue' : 'bx24-tariff-notify-red';
	}
	function _getMessage2() {
	  if (this.getOptions().type === 'license-expired') {
	    return main_core.Loc.getMessage('INTRANET_NOTIFY_PANEL_FOOTER_LICENSE_NOTIFICATION_TEXT', {
	      '#BLOCK_DATE#': _classPrivateMethodGet$1(this, _getBlockDate, _getBlockDate2).call(this),
	      '#LINK_BUY#': _classPrivateMethodGet$1(this, _getParams, _getParams2).call(this).urlBuy,
	      '#ARTICLE_LINK#': _classPrivateMethodGet$1(this, _getParams, _getParams2).call(this).urlArticle
	    });
	  }
	}
	function _getBlockDate2() {
	  var format = main_date.DateTimeFormat.getFormat('DAY_MONTH_FORMAT');
	  return main_date.DateTimeFormat.format(format, Number(this.getOptions().params.blockDate));
	}
	var _classActivity = {
	  writable: true,
	  value: 'bx24-tariff-notify-show'
	};

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _cache$2 = /*#__PURE__*/new WeakMap();
	var NotifyManager = /*#__PURE__*/function () {
	  function NotifyManager(options) {
	    babelHelpers.classCallCheck(this, NotifyManager);
	    _classPrivateFieldInitSpec$2(this, _cache$2, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setOptions(options);
	  }
	  babelHelpers.createClass(NotifyManager, [{
	    key: "setOptions",
	    value: function setOptions(options) {
	      babelHelpers.classPrivateFieldGet(this, _cache$2).set('options', options);
	    }
	  }, {
	    key: "getOptions",
	    value: function getOptions() {
	      return babelHelpers.classPrivateFieldGet(this, _cache$2).get('options', null);
	    }
	  }, {
	    key: "getLicenseNotificationPopup",
	    value: function getLicenseNotificationPopup(options) {
	      var _this = this;
	      return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('License-notification-popup', function () {
	        return new LicenseNotificationPopup(_objectSpread({
	          isAdmin: _this.getOptions().isAdmin
	        }, options));
	      });
	    }
	  }, {
	    key: "getNotifyPanel",
	    value: function getNotifyPanel(options) {
	      return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('notify-panel', function () {
	        return new NotifyPanel(options);
	      });
	    }
	  }]);
	  return NotifyManager;
	}();
	babelHelpers.defineProperty(NotifyManager, "componentName", 'bitrix:intranet.notify.panel');

	exports.NotifyManager = NotifyManager;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.Main,BX.UI,BX,BX,BX.Main));
//# sourceMappingURL=script.js.map
