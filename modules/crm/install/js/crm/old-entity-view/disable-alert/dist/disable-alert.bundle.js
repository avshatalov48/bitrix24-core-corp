/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_dialogs_messagebox,ui_notification,ui_buttons,crm_router) {
	'use strict';

	var _templateObject;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _alertContainer = /*#__PURE__*/new WeakMap();
	var _daysUntilDisable = /*#__PURE__*/new WeakMap();
	var _isAdmin = /*#__PURE__*/new WeakMap();
	var _lastTimeShownField = /*#__PURE__*/new WeakMap();
	var _lastTimeShownOptionName = /*#__PURE__*/new WeakMap();
	var _previewHref = /*#__PURE__*/new WeakMap();
	var _sendEnableNewLayoutRequest = /*#__PURE__*/new WeakSet();
	var _showConfirmationPopup = /*#__PURE__*/new WeakSet();
	var _getTitleText = /*#__PURE__*/new WeakSet();
	var _getText = /*#__PURE__*/new WeakSet();
	var DisableAlert = /*#__PURE__*/function () {
	  function DisableAlert(options) {
	    babelHelpers.classCallCheck(this, DisableAlert);
	    _classPrivateMethodInitSpec(this, _getText);
	    _classPrivateMethodInitSpec(this, _getTitleText);
	    _classPrivateMethodInitSpec(this, _showConfirmationPopup);
	    _classPrivateMethodInitSpec(this, _sendEnableNewLayoutRequest);
	    _classPrivateFieldInitSpec(this, _alertContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _daysUntilDisable, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isAdmin, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _lastTimeShownField, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _lastTimeShownOptionName, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _previewHref, {
	      writable: true,
	      value: void 0
	    });
	    if (!main_core.Type.isElementNode(options.alertContainer)) {
	      throw new Error('OldCardLayout.DisableAlert: \'alertContainer\' must be a DOM element.');
	    }
	    if (!main_core.Type.isInteger(options.daysUntilDisable)) {
	      throw new TypeError('OldCardLayout.DisableAlert: \'daysUntilDisable\' must be integer');
	    }
	    if (!main_core.Type.isBoolean(options.isAdmin)) {
	      throw new TypeError('OldCardLayout.DisableAlert: \'isAdmin\' must be boolean');
	    }
	    if (!main_core.Type.isString(options.lastTimeShownField)) {
	      throw new TypeError('OldCardLayout.DisableAlert: \'lastTimeShownField\' must be string');
	    }
	    if (!main_core.Type.isString(options.lastTimeShownOptionName)) {
	      throw new TypeError('OldCardLayout.DisableAlert: \'lastTimeShownOptionName\' must be string');
	    }
	    if (!main_core.Type.isString(options.previewHref)) {
	      throw new TypeError('OldCardLayout.DisableAlert: \'previewHref\' must be string');
	    }
	    babelHelpers.classPrivateFieldSet(this, _alertContainer, options.alertContainer);
	    babelHelpers.classPrivateFieldSet(this, _daysUntilDisable, options.daysUntilDisable);
	    babelHelpers.classPrivateFieldSet(this, _isAdmin, options.isAdmin);
	    babelHelpers.classPrivateFieldSet(this, _lastTimeShownField, options.lastTimeShownField);
	    babelHelpers.classPrivateFieldSet(this, _lastTimeShownOptionName, options.lastTimeShownOptionName);
	    babelHelpers.classPrivateFieldSet(this, _previewHref, options.previewHref);
	  }
	  babelHelpers.createClass(DisableAlert, [{
	    key: "render",
	    value: function render() {
	      var _this = this;
	      var previewButton = new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_SHOW_PREVIEW_TEXT'),
	        useAirDesign: true,
	        style: ui_buttons.Button.AirStyle.OUTLINE,
	        size: ui_buttons.Button.Size.SMALL,
	        onclick: function onclick() {
	          crm_router.Router.openSlider(babelHelpers.classPrivateFieldGet(_this, _previewHref), {
	            allowChangeHistory: false
	          });
	        }
	      });
	      var _ref = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-old-layout-disable-alert\">\n\t\t\t\t<div class=\"crm-old-layout-left-part\">\n\t\t\t\t\t<span class=\"crm-old-layout-icon\"></span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-old-layout-right-part\">\n\t\t\t\t\t<h4 class=\"crm-old-layout-title ui-typography-heading-h4\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</h4>\n\t\t\t\t\t<p class=\"crm-old-layout-text ui-typography-text-md\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</p>\n\t\t\t\t\t<div class=\"crm-old-layout-buttons\" ref=\"buttonContainer\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<button class=\"crm-old-layout-close-button\" ref=\"closeButton\">\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet(this, _getTitleText, _getTitleText2).call(this), _classPrivateMethodGet(this, _getText, _getText2).call(this), previewButton.getContainer()),
	        root = _ref.root,
	        buttonContainer = _ref.buttonContainer,
	        closeButton = _ref.closeButton;
	      if (babelHelpers.classPrivateFieldGet(this, _isAdmin)) {
	        var showConfirmationButton = new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_ENABLE_NEW_LAYOUT_TEXT'),
	          useAirDesign: true,
	          style: ui_buttons.Button.AirStyle.OUTLINE,
	          size: ui_buttons.Button.Size.SMALL,
	          onclick: function onclick() {
	            _classPrivateMethodGet(_this, _showConfirmationPopup, _showConfirmationPopup2).call(_this);
	          }
	        });
	        showConfirmationButton.renderTo(buttonContainer);
	      }
	      main_core.Event.bind(closeButton, 'click', function () {
	        BX.userOptions.save('crm', babelHelpers.classPrivateFieldGet(_this, _lastTimeShownField), babelHelpers.classPrivateFieldGet(_this, _lastTimeShownOptionName), Date.now());
	        babelHelpers.classPrivateFieldGet(_this, _alertContainer).remove();
	      });
	      babelHelpers.classPrivateFieldGet(this, _alertContainer).append(root);
	    }
	  }]);
	  return DisableAlert;
	}();
	function _sendEnableNewLayoutRequest2() {
	  BX.ajax.runAction('crm.oldentityview.sunset.enableNewCardLayout').then(function () {
	    window.location.reload();
	  })["catch"](function () {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_ENABLE_NEW_LAYOUT_ERROR_MESSAGE')
	    });
	  });
	}
	function _showConfirmationPopup2() {
	  var _this2 = this;
	  var confirmationPopup = ui_dialogs_messagebox.MessageBox.create({
	    message: main_core.Loc.getMessage('CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_ENABLE_NEW_LAYOUT_ASSERT'),
	    useAirDesign: true,
	    buttons: [new ui_buttons.Button({
	      text: main_core.Loc.getMessage('CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_ENABLE_NEW_LAYOUT_ASSERT_CONFIRM'),
	      useAirDesign: true,
	      style: ui_buttons.Button.AirStyle.FILLED,
	      onclick: function onclick(button) {
	        _classPrivateMethodGet(_this2, _sendEnableNewLayoutRequest, _sendEnableNewLayoutRequest2).call(_this2, button);
	      }
	    }), new ui_buttons.Button({
	      text: main_core.Loc.getMessage('CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_ENABLE_NEW_LAYOUT_ASSERT_CANCEL'),
	      useAirDesign: true,
	      style: ui_buttons.Button.AirStyle.OUTLINE,
	      onclick: function onclick(button) {
	        button.context.close();
	      }
	    })]
	  });
	  confirmationPopup.show();
	}
	function _getTitleText2() {
	  if (babelHelpers.classPrivateFieldGet(this, _daysUntilDisable) === 0) {
	    return main_core.Loc.getMessage('CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_TITLE_LESS_THAN_DAY');
	  }
	  return main_core.Loc.getMessagePlural('CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_TITLE', babelHelpers.classPrivateFieldGet(this, _daysUntilDisable), {
	    '#DAYS_UNTIL_DISABLE#': babelHelpers.classPrivateFieldGet(this, _daysUntilDisable)
	  });
	}
	function _getText2() {
	  var helpdeskLink = '<a class="crm-old-layout-helpdesk-link" href="javascript:top.BX.Helper.show(\'redirect=detail&code=26179574\');">';
	  var localPhraseCode = babelHelpers.classPrivateFieldGet(this, _isAdmin) ? 'CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_TEXT' : 'CRM_OLD_CARD_LAYOUT_DISABLE_ALERT_ENABLE_NEW_LAYOUT_CONTACT_ADMIN';
	  return main_core.Loc.getMessage(localPhraseCode, {
	    '[helpdeskLink]': helpdeskLink,
	    '[/helpdeskLink]': '</a>'
	  });
	}

	exports.DisableAlert = DisableAlert;

}((this.BX.Crm.OldEntityView = this.BX.Crm.OldEntityView || {}),BX,BX.UI.Dialogs,BX,BX.UI,BX.Crm));
//# sourceMappingURL=disable-alert.bundle.js.map
