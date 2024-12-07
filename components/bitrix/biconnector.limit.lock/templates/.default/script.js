/* eslint-disable */
(function (exports,main_core,main_core_events,main_popup,ui_buttons,ui_bannerDispatcher) {
	'use strict';

	var _templateObject;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _title = /*#__PURE__*/new WeakMap();
	var _content = /*#__PURE__*/new WeakMap();
	var _licenseButtonText = /*#__PURE__*/new WeakMap();
	var _laterButtonText = /*#__PURE__*/new WeakMap();
	var _licenseUrl = /*#__PURE__*/new WeakMap();
	var _popupClassName = /*#__PURE__*/new WeakMap();
	var _fullLock = /*#__PURE__*/new WeakMap();
	var _init = /*#__PURE__*/new WeakSet();
	var _show = /*#__PURE__*/new WeakSet();
	var LimitLockPopup = function LimitLockPopup(_params) {
	  babelHelpers.classCallCheck(this, LimitLockPopup);
	  _classPrivateMethodInitSpec(this, _show);
	  _classPrivateMethodInitSpec(this, _init);
	  _classPrivateFieldInitSpec(this, _title, {
	    writable: true,
	    value: ''
	  });
	  _classPrivateFieldInitSpec(this, _content, {
	    writable: true,
	    value: ''
	  });
	  _classPrivateFieldInitSpec(this, _licenseButtonText, {
	    writable: true,
	    value: ''
	  });
	  _classPrivateFieldInitSpec(this, _laterButtonText, {
	    writable: true,
	    value: ''
	  });
	  _classPrivateFieldInitSpec(this, _licenseUrl, {
	    writable: true,
	    value: ''
	  });
	  _classPrivateFieldInitSpec(this, _popupClassName, {
	    writable: true,
	    value: 'biconnector-limit-lock'
	  });
	  _classPrivateFieldInitSpec(this, _fullLock, {
	    writable: true,
	    value: false
	  });
	  _classPrivateMethodGet(this, _init, _init2).call(this, _params);
	  _classPrivateMethodGet(this, _show, _show2).call(this);
	};
	function _init2(params) {
	  babelHelpers.classPrivateFieldSet(this, _title, params.title || '');
	  babelHelpers.classPrivateFieldSet(this, _content, params.content || '');
	  babelHelpers.classPrivateFieldSet(this, _licenseButtonText, params.licenseButtonText || '');
	  babelHelpers.classPrivateFieldSet(this, _laterButtonText, params.laterButtonText || '');
	  babelHelpers.classPrivateFieldSet(this, _licenseUrl, params.licenseUrl);
	  babelHelpers.classPrivateFieldSet(this, _fullLock, params.fullLock === 'Y');
	}
	function _show2() {
	  var _this = this;
	  ui_bannerDispatcher.BannerDispatcher.high.toQueue(function (onDone) {
	    var popupButtons = [];
	    if (babelHelpers.classPrivateFieldGet(_this, _licenseButtonText)) {
	      popupButtons.push(new ui_buttons.Button({
	        text: babelHelpers.classPrivateFieldGet(_this, _licenseButtonText),
	        color: ui_buttons.Button.Color.SUCCESS,
	        onclick: function onclick() {
	          top.location.href = babelHelpers.classPrivateFieldGet(_this, _licenseUrl);
	        }
	      }));
	    }
	    popupButtons.push(new ui_buttons.Button({
	      text: babelHelpers.classPrivateFieldGet(_this, _laterButtonText),
	      color: ui_buttons.Button.Color.LINK,
	      onclick: function onclick() {
	        popup.close();
	      }
	    }));
	    var popupContent = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-limit-popup-wrap\">\n\t\t\t\t<div class=\"biconnector-limit-popup\">\n\t\t\t\t\t<div class=\"biconnector-limit-pic\">\n\t\t\t\t\t\t<div class=\"biconnector-limit-pic-round\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"biconnector-limit-text\">", "</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(_this, _content));
	    var popup = new main_popup.Popup({
	      titleBar: babelHelpers.classPrivateFieldGet(_this, _title),
	      content: popupContent,
	      overlay: true,
	      className: babelHelpers.classPrivateFieldGet(_this, _popupClassName),
	      closeIcon: true,
	      lightShadow: true,
	      offsetLeft: 100,
	      buttons: popupButtons
	    });
	    if (babelHelpers.classPrivateFieldGet(_this, _fullLock)) {
	      popup.subscribe('onClose', function () {
	        if (BX.SidePanel.Instance.isOpen()) {
	          BX.SidePanel.Instance.close();
	        }
	        main_core_events.EventEmitter.emit('BiConnector:LimitPopup.Lock.onClose');
	        onDone();
	      });
	    } else {
	      popup.subscribe('onClose', function () {
	        main_core_events.EventEmitter.emit('BiConnector:LimitPopup.Warning.onClose');
	        onDone();
	      });
	    }
	    popup.show();
	  });
	}
	main_core.Reflection.namespace('BX.BIConnector').LimitLockPopup = LimitLockPopup;

}((this.window = this.window || {}),BX,BX.Event,BX.Main,BX.UI,BX.UI));
//# sourceMappingURL=script.js.map
