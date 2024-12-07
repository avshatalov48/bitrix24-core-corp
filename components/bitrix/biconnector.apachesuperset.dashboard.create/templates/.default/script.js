/* eslint-disable */
(function (exports,main_core,biconnector_dashboardParametersSelector,biconnector_apacheSupersetAnalytics,ui_buttons) {
	'use strict';

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _props = /*#__PURE__*/new WeakMap();
	var _node = /*#__PURE__*/new WeakMap();
	var _paramsSelector = /*#__PURE__*/new WeakMap();
	var _render = /*#__PURE__*/new WeakSet();
	var _getMainContent = /*#__PURE__*/new WeakSet();
	var _getTopBlock = /*#__PURE__*/new WeakSet();
	/**
	 * @namespace BX.BIConnector
	 */
	var SupersetDashboardCreateManager = /*#__PURE__*/function () {
	  function SupersetDashboardCreateManager(props) {
	    babelHelpers.classCallCheck(this, SupersetDashboardCreateManager);
	    _classPrivateMethodInitSpec(this, _getTopBlock);
	    _classPrivateMethodInitSpec(this, _getMainContent);
	    _classPrivateMethodInitSpec(this, _render);
	    _classPrivateFieldInitSpec(this, _props, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _node, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _paramsSelector, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _props, props);
	    babelHelpers.classPrivateFieldSet(this, _node, document.querySelector("#".concat(babelHelpers.classPrivateFieldGet(this, _props).nodeId)));
	    _classPrivateMethodGet(this, _render, _render2).call(this);
	  }
	  babelHelpers.createClass(SupersetDashboardCreateManager, [{
	    key: "onClickSave",
	    // noinspection JSUnusedGlobalSymbols
	    value: function onClickSave() {
	      var selectorData = babelHelpers.classPrivateFieldGet(this, _paramsSelector).getValues();
	      var titleField = document.querySelector('#dashboard-title-field');
	      var saveData = {
	        scopes: babelHelpers.toConsumableArray(selectorData.scopes),
	        params: babelHelpers.toConsumableArray(selectorData.params),
	        title: titleField.value
	      };
	      var saveButton = ui_buttons.ButtonManager.createFromNode(document.querySelector('#dashboard-button-save'));
	      saveButton.setWaiting(true);
	      main_core.ajax.runComponentAction(babelHelpers.classPrivateFieldGet(this, _props).componentName, 'save', {
	        mode: 'class',
	        signedParameters: babelHelpers.classPrivateFieldGet(this, _props).signedParameters,
	        data: {
	          data: saveData
	        }
	      }).then(function (response) {
	        biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('new', 'report_new', {
	          type: 'custom',
	          c_element: 'new_button'
	        });
	        parent.BX.Event.EventEmitter.emit('BIConnector.CreateForm:onDashboardCreated', {
	          dashboard: response.data.dashboard
	        });
	        BX.SidePanel.Instance.getTopSlider().close();
	      })["catch"](function (response) {
	        BX.UI.Notification.Center.notify({
	          content: response.errors[0].message
	        });
	        saveButton.setWaiting(false);
	      });
	    }
	  }]);
	  return SupersetDashboardCreateManager;
	}();
	function _render2() {
	  main_core.Dom.append(_classPrivateMethodGet(this, _getTopBlock, _getTopBlock2).call(this), babelHelpers.classPrivateFieldGet(this, _node));
	  main_core.Dom.append(_classPrivateMethodGet(this, _getMainContent, _getMainContent2).call(this), babelHelpers.classPrivateFieldGet(this, _node));
	  babelHelpers.classPrivateFieldSet(this, _paramsSelector, new biconnector_dashboardParametersSelector.DashboardParametersSelector({
	    scopes: new Set(),
	    params: new Set(),
	    scopeParamsMap: babelHelpers.classPrivateFieldGet(this, _props).scopeParamsMap
	  }));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _paramsSelector).getLayout(), babelHelpers.classPrivateFieldGet(this, _node));
	}
	function _getMainContent2() {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"dashboard-params-title-container\">\n\t\t\t\t\t<div class=\"dashboard-params-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100 dashboard-title-wrapper\">\n\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\" id=\"dashboard-title-field\" value=\"", "\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('DASHBOARD_CREATE_NAME'), babelHelpers.classPrivateFieldGet(this, _props).defaultValues.title);
	}
	function _getTopBlock2() {
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"dashboard-create-top-block\">\n\t\t\t\t<div class=\"dashboard-create-top-block-image\"></div>\n\t\t\t\t<div class=\"dashboard-create-top-block-text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('DASHBOARD_CREATE_TOP_BLOCK'));
	}
	main_core.Reflection.namespace('BX.BIConnector').SupersetDashboardCreateManager = SupersetDashboardCreateManager;

}((this.window = this.window || {}),BX,BX.BIConnector,BX.BIConnector,BX.UI));
//# sourceMappingURL=script.js.map
