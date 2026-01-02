/* eslint-disable */
(function (exports,main_core,ui_bannerDispatcher,ui_buttons,ui_system_dialog) {
	'use strict';

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespaceCrmWhatsNew = main_core.Reflection.namespace('BX.Crm.WhatsNew');
	var _dialog = /*#__PURE__*/new WeakMap();
	var _isNeedToUpdateVat = /*#__PURE__*/new WeakMap();
	var _renderDialogContent = /*#__PURE__*/new WeakSet();
	var _renderHelpDescLink = /*#__PURE__*/new WeakSet();
	var _renderUpdateVat22Button = /*#__PURE__*/new WeakSet();
	var _renderUpdateLaterButton = /*#__PURE__*/new WeakSet();
	var Vat22Popup = /*#__PURE__*/function () {
	  function Vat22Popup() {
	    babelHelpers.classCallCheck(this, Vat22Popup);
	    _classPrivateMethodInitSpec(this, _renderUpdateLaterButton);
	    _classPrivateMethodInitSpec(this, _renderUpdateVat22Button);
	    _classPrivateMethodInitSpec(this, _renderHelpDescLink);
	    _classPrivateMethodInitSpec(this, _renderDialogContent);
	    _classPrivateFieldInitSpec(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isNeedToUpdateVat, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _dialog, null);
	    babelHelpers.classPrivateFieldSet(this, _isNeedToUpdateVat, false);
	  }
	  babelHelpers.createClass(Vat22Popup, [{
	    key: "show",
	    value: function show() {
	      var _this = this;
	      if (!babelHelpers.classPrivateFieldGet(this, _dialog)) {
	        babelHelpers.classPrivateFieldSet(this, _dialog, new ui_system_dialog.Dialog({
	          title: main_core.Loc.getMessage('CRM_WHATS_NEW_VAT_22_TITLE'),
	          content: _classPrivateMethodGet(this, _renderDialogContent, _renderDialogContent2).call(this),
	          leftButtons: [_classPrivateMethodGet(this, _renderUpdateVat22Button, _renderUpdateVat22Button2).call(this), _classPrivateMethodGet(this, _renderUpdateLaterButton, _renderUpdateLaterButton2).call(this)],
	          closeByClickOutside: false,
	          width: 555,
	          hasOverlay: true
	        }));
	        ui_bannerDispatcher.BannerDispatcher.high.toQueue(function (onDone) {
	          babelHelpers.classPrivateFieldGet(_this, _dialog).subscribe('onAfterHide', function () {
	            onDone();
	            if (babelHelpers.classPrivateFieldGet(_this, _isNeedToUpdateVat)) {
	              _this.updateVat22();
	            } else {
	              _this.updateLater();
	            }
	          });
	          babelHelpers.classPrivateFieldGet(_this, _dialog).show();
	        });
	      }
	    }
	  }, {
	    key: "updateLater",
	    value: function updateLater() {
	      main_core.ajax.runAction('crm.settings.tourvat22.updateLater')["catch"](function (errors) {
	        console.error(errors);
	      });
	    }
	  }, {
	    key: "updateVat22",
	    value: function updateVat22() {
	      main_core.ajax.runAction('crm.settings.tourvat22.updateVat22')["catch"](function (errors) {
	        console.error(errors);
	      });
	    }
	  }]);
	  return Vat22Popup;
	}();
	function _renderDialogContent2() {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-whats-new-vat-22_content-description\">\n\t\t\t\t<p>", "</p>\n\t\t\t\t<p>", "</p>\n\t\t\t\t<p>", "</p>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_WHATS_NEW_VAT_22_CONTENT'), main_core.Loc.getMessage('CRM_WHATS_NEW_VAT_22_MARKETPLACE'), _classPrivateMethodGet(this, _renderHelpDescLink, _renderHelpDescLink2).call(this));
	}
	function _renderHelpDescLink2() {
	  var Helper = main_core.Reflection.getClass('top.BX.Helper');
	  var link = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<a>", "</a>"])), main_core.Loc.getMessage('CRM_WHATS_NEW_VAT_22_HELP_DESC'));
	  main_core.bind(link, 'click', function () {
	    Helper === null || Helper === void 0 ? void 0 : Helper.show('redirect=detail&code=27490382');
	  });
	  return link;
	}
	function _renderUpdateVat22Button2() {
	  var _this2 = this;
	  return new ui_buttons.Button({
	    style: ui_buttons.AirButtonStyle.FILLED,
	    text: main_core.Loc.getMessage('CRM_WHATS_NEW_VAT_22_UPDATE_AUTO'),
	    round: true,
	    useAirDesign: true,
	    onclick: function onclick() {
	      babelHelpers.classPrivateFieldSet(_this2, _isNeedToUpdateVat, true);
	      babelHelpers.classPrivateFieldGet(_this2, _dialog).hide();
	    }
	  });
	}
	function _renderUpdateLaterButton2() {
	  var _this3 = this;
	  return new ui_buttons.Button({
	    style: ui_buttons.AirButtonStyle.OUTLINE,
	    text: main_core.Loc.getMessage('CRM_WHATS_NEW_VAT_22_UPDATE_LATER'),
	    round: true,
	    useAirDesign: true,
	    onclick: function onclick() {
	      babelHelpers.classPrivateFieldGet(_this3, _dialog).hide();
	    }
	  });
	}
	namespaceCrmWhatsNew.Vat22Popup = Vat22Popup;

}((this.window = this.window || {}),BX,BX.UI,BX.UI,BX.UI.System));
//# sourceMappingURL=script.js.map
