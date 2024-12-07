/* eslint-disable */
(function (exports,main_core,main_popup,crm_integration_ui_bannerDispatcher) {
	'use strict';

	var _templateObject;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespaceCrmWhatsNew = main_core.Reflection.namespace('BX.Crm.WhatsNew');
	var _popup = /*#__PURE__*/new WeakMap();
	var _data = /*#__PURE__*/new WeakMap();
	var _options = /*#__PURE__*/new WeakMap();
	var _bannerDispatcher = /*#__PURE__*/new WeakMap();
	var _userOptionCategory = /*#__PURE__*/new WeakMap();
	var _userOptionName = /*#__PURE__*/new WeakMap();
	var _getPopupContent = /*#__PURE__*/new WeakSet();
	var RichPopup = /*#__PURE__*/function () {
	  function RichPopup(_ref) {
	    var data = _ref.data,
	      options = _ref.options,
	      userOptionCategory = _ref.userOptionCategory,
	      userOptionName = _ref.userOptionName;
	    babelHelpers.classCallCheck(this, RichPopup);
	    _classPrivateMethodInitSpec(this, _getPopupContent);
	    _classPrivateFieldInitSpec(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _bannerDispatcher, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _userOptionCategory, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _userOptionName, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _popup, null);
	    babelHelpers.classPrivateFieldSet(this, _data, data);
	    babelHelpers.classPrivateFieldSet(this, _options, main_core.Type.isObject(options) ? options : {});
	    babelHelpers.classPrivateFieldSet(this, _userOptionCategory, main_core.Type.isString(userOptionCategory) ? userOptionCategory : 'crm');
	    babelHelpers.classPrivateFieldSet(this, _userOptionName, main_core.Type.isString(userOptionName) ? userOptionName : '');
	    if (main_core.Type.isNumber(options.entityTypeId) && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _userOptionName))) {
	      babelHelpers.classPrivateFieldSet(this, _userOptionName, "".concat(babelHelpers.classPrivateFieldGet(this, _userOptionName)).concat(options.entityTypeId));
	    }
	    babelHelpers.classPrivateFieldSet(this, _bannerDispatcher, new crm_integration_ui_bannerDispatcher.BannerDispatcher());
	  }
	  babelHelpers.createClass(RichPopup, [{
	    key: "show",
	    value: function show() {
	      var _this = this;
	      var isAnyPopupShown = main_popup.PopupManager && main_popup.PopupManager.isAnyPopupShown();
	      var isAnySliderShown = BX.SidePanel.Instance.getOpenSlidersCount() > 0;
	      if (isAnyPopupShown || isAnySliderShown) {
	        return;
	      }
	      if (!babelHelpers.classPrivateFieldGet(this, _popup)) {
	        var htmlStyles = getComputedStyle(document.documentElement);
	        var popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
	        var popupPaddingNumberValue = parseFloat(popupPadding) || 12;
	        var popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';
	        babelHelpers.classPrivateFieldSet(this, _popup, new main_popup.Popup({
	          className: 'crm-rich-popup-wrapper',
	          closeIcon: true,
	          closeByEsc: true,
	          cacheable: false,
	          padding: popupPaddingNumberValue,
	          overlay: {
	            opacity: 40,
	            backgroundColor: popupOverlayColor
	          },
	          content: _classPrivateMethodGet(this, _getPopupContent, _getPopupContent2).call(this),
	          width: 640,
	          height: 400,
	          events: {
	            onPopupClose: function onPopupClose() {
	              _this.save();
	            }
	          }
	        }));
	        babelHelpers.classPrivateFieldGet(this, _bannerDispatcher).toQueue(function (onDone) {
	          babelHelpers.classPrivateFieldGet(_this, _popup).subscribe('onClose', onDone);
	          babelHelpers.classPrivateFieldGet(_this, _popup).show();
	        });
	      }
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      BX.userOptions.save(babelHelpers.classPrivateFieldGet(this, _userOptionCategory), babelHelpers.classPrivateFieldGet(this, _userOptionName), 'count', babelHelpers.classPrivateFieldGet(this, _options).checkpoint);
	    }
	  }]);
	  return RichPopup;
	}();
	function _getPopupContent2() {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-rich-popup-slide\">\n\t\t\t\t<img src=\"", "\" alt=\"\">\n\t\t\t\t<div class=\"crm-rich-popup-slide-inner-title\"> ", " </div>\n\t\t\t\t<div class=\"crm-rich-popup-slide-inner-subtitle\"> ", " </div>\n\t\t\t\t<div class=\"crm-rich-popup-slide-inner-description\">", "</div>\n\t\t\t\t<div class=\"crm-rich-popup-slide-inner-info\">", "</div>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _data).innerImage, babelHelpers.classPrivateFieldGet(this, _data).innerTitle, babelHelpers.classPrivateFieldGet(this, _data).innerSubTitle, babelHelpers.classPrivateFieldGet(this, _data).innerDescription, babelHelpers.classPrivateFieldGet(this, _data).innerInfo);
	}
	namespaceCrmWhatsNew.RichPopup = RichPopup;

}((this.window = this.window || {}),BX,BX.Main,BX.Crm.Integration.UI));
//# sourceMappingURL=script.js.map
