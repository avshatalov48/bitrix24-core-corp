/* eslint-disable */
(function (exports,main_core,ui_buttons) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.BIConnector');
	var _enableButtonSection = /*#__PURE__*/new WeakMap();
	var _enableButton = /*#__PURE__*/new WeakMap();
	var _renderEnableSection = /*#__PURE__*/new WeakSet();
	var _createEnableButton = /*#__PURE__*/new WeakSet();
	var _enableSuperset = /*#__PURE__*/new WeakSet();
	var SupersetEnabler = function SupersetEnabler(props) {
	  babelHelpers.classCallCheck(this, SupersetEnabler);
	  _classPrivateMethodInitSpec(this, _enableSuperset);
	  _classPrivateMethodInitSpec(this, _createEnableButton);
	  _classPrivateMethodInitSpec(this, _renderEnableSection);
	  _classPrivateFieldInitSpec(this, _enableButtonSection, {
	    writable: true,
	    value: void 0
	  });
	  _classPrivateFieldInitSpec(this, _enableButton, {
	    writable: true,
	    value: void 0
	  });
	  babelHelpers.classPrivateFieldSet(this, _enableButtonSection, document.getElementById(props.enableButtonSectionId));
	  if (!babelHelpers.classPrivateFieldGet(this, _enableButtonSection)) {
	    throw new Error('Enable button section not found');
	  }
	  _classPrivateMethodGet(this, _renderEnableSection, _renderEnableSection2).call(this, props.canEnable, props.enableDate);
	};
	function _renderEnableSection2(canEnable, enableDate) {
	  babelHelpers.classPrivateFieldSet(this, _enableButton, _classPrivateMethodGet(this, _createEnableButton, _createEnableButton2).call(this, !canEnable));
	  var buttonWrapper = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	  babelHelpers.classPrivateFieldGet(this, _enableButton).renderTo(buttonWrapper);
	  var descriptionBlock = null;
	  if (!canEnable) {
	    var description = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_CONTROLLER_CREATE_SUPERSET_BUTTON_ENABLE_DATE', {
	      '#ENABLE_TIME#': enableDate
	    });
	    descriptionBlock = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"biconnector-create-superset-button-block-desc\">", "</div>"])), description);
	  }
	  var content = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-create-superset-button-block\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), buttonWrapper, descriptionBlock);
	  babelHelpers.classPrivateFieldGet(this, _enableButtonSection).append(content);
	}
	function _createEnableButton2(disabled) {
	  var _this = this;
	  return new ui_buttons.Button({
	    text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_CONTROLLER_CREATE_SUPERSET_BUTTON_MSGVER_1'),
	    round: true,
	    color: ui_buttons.Button.Color.PRIMARY,
	    onclick: function onclick() {
	      _classPrivateMethodGet(_this, _enableSuperset, _enableSuperset2).call(_this);
	    },
	    disabled: disabled,
	    className: disabled ? 'biconnector-create-superset-disabled-button biconnector-create-superset-button' : 'biconnector-create-superset-button'
	  });
	}
	function _enableSuperset2() {
	  var _this2 = this;
	  babelHelpers.classPrivateFieldGet(this, _enableButton).setWaiting(true);
	  main_core.ajax.runAction('biconnector.superset.enable').then(function () {
	    window.location.reload();
	  })["catch"](function (response) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(response.errors[0].message)
	    });
	    babelHelpers.classPrivateFieldGet(_this2, _enableButton).setWaiting(false);
	  });
	}
	namespace.SupersetEnabler = SupersetEnabler;

}((this.window = this.window || {}),BX,BX.UI));
//# sourceMappingURL=script.js.map
