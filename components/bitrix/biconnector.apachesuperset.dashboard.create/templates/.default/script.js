/* eslint-disable */
(function (exports,main_core,main_core_events,biconnector_dashboardParametersSelector,biconnector_apacheSupersetAnalytics,ui_buttons) {
	'use strict';

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _props = /*#__PURE__*/new WeakMap();
	var _node = /*#__PURE__*/new WeakMap();
	var _paramsSelector = /*#__PURE__*/new WeakMap();
	var _saveButton = /*#__PURE__*/new WeakMap();
	var _render = /*#__PURE__*/new WeakSet();
	var _getMainContent = /*#__PURE__*/new WeakSet();
	var _getTopBlock = /*#__PURE__*/new WeakSet();
	var _onParamSelectorInit = /*#__PURE__*/new WeakSet();
	/**
	 * @namespace BX.BIConnector
	 */
	var SupersetDashboardCreateManager = /*#__PURE__*/function () {
	  function SupersetDashboardCreateManager(props) {
	    babelHelpers.classCallCheck(this, SupersetDashboardCreateManager);
	    _classPrivateMethodInitSpec(this, _onParamSelectorInit);
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
	    _classPrivateFieldInitSpec(this, _saveButton, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _props, props);
	    babelHelpers.classPrivateFieldSet(this, _node, document.querySelector("#".concat(babelHelpers.classPrivateFieldGet(this, _props).nodeId)));
	    _classPrivateMethodGet(this, _render, _render2).call(this);
	    babelHelpers.classPrivateFieldSet(this, _saveButton, ui_buttons.ButtonManager.createFromNode(document.querySelector('#dashboard-button-save')));
	    if (babelHelpers.classPrivateFieldGet(this, _props).canManageGroups) {
	      babelHelpers.classPrivateFieldGet(this, _saveButton).setDisabled(true);
	    }
	    main_core_events.EventEmitter.subscribe('BIConnector.DashboardParamsSelector:initCompleted', _classPrivateMethodGet(this, _onParamSelectorInit, _onParamSelectorInit2).bind(this));
	  }
	  babelHelpers.createClass(SupersetDashboardCreateManager, [{
	    key: "onClickSave",
	    // noinspection JSUnusedGlobalSymbols
	    value: function onClickSave() {
	      var _this = this;
	      var titleField = document.querySelector('#dashboard-title-field');
	      var saveData = {
	        title: titleField.value
	      };
	      if (babelHelpers.classPrivateFieldGet(this, _props).canManageGroups) {
	        var selectorData = babelHelpers.classPrivateFieldGet(this, _paramsSelector).getValues();
	        saveData.groups = babelHelpers.toConsumableArray(selectorData.groups);
	        saveData.scopes = babelHelpers.toConsumableArray(selectorData.scopes);
	        saveData.params = babelHelpers.toConsumableArray(selectorData.params);
	      }
	      babelHelpers.classPrivateFieldGet(this, _saveButton).setWaiting(true);
	      main_core.ajax.runComponentAction(babelHelpers.classPrivateFieldGet(this, _props).componentName, 'save', {
	        mode: 'class',
	        signedParameters: babelHelpers.classPrivateFieldGet(this, _props).signedParameters,
	        data: {
	          data: saveData
	        }
	      }).then(function (response) {
	        var _window$open;
	        biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('new', 'report_new', {
	          type: 'custom',
	          c_element: 'new_button'
	        });
	        (_window$open = window.open(response.data.dashboard.detailUrl, '_blank')) === null || _window$open === void 0 ? void 0 : _window$open.focus();
	        parent.BX.Event.EventEmitter.emit('BIConnector.CreateForm:onDashboardCreated', {
	          dashboard: response.data.dashboard
	        });
	        BX.SidePanel.Instance.getTopSlider().close();
	      })["catch"](function (response) {
	        BX.UI.Notification.Center.notify({
	          content: response.errors[0].message
	        });
	        babelHelpers.classPrivateFieldGet(_this, _saveButton).setWaiting(false);
	      });
	    }
	  }]);
	  return SupersetDashboardCreateManager;
	}();
	function _render2() {
	  main_core.Dom.append(_classPrivateMethodGet(this, _getTopBlock, _getTopBlock2).call(this), babelHelpers.classPrivateFieldGet(this, _node));
	  main_core.Dom.append(_classPrivateMethodGet(this, _getMainContent, _getMainContent2).call(this), babelHelpers.classPrivateFieldGet(this, _node));
	  if (babelHelpers.classPrivateFieldGet(this, _props).canManageGroups) {
	    babelHelpers.classPrivateFieldSet(this, _paramsSelector, new biconnector_dashboardParametersSelector.DashboardParametersSelector({
	      groups: new Set(),
	      scopes: new Set(),
	      params: new Set(),
	      scopeParamsMap: babelHelpers.classPrivateFieldGet(this, _props).scopeParamsMap
	    }));
	    main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _paramsSelector).getLayout(), babelHelpers.classPrivateFieldGet(this, _node));
	  }
	}
	function _getMainContent2() {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"dashboard-params-title-container\">\n\t\t\t\t\t<div class=\"dashboard-params-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100 dashboard-title-wrapper\">\n\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\" id=\"dashboard-title-field\" value=\"", "\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('DASHBOARD_CREATE_NAME'), babelHelpers.classPrivateFieldGet(this, _props).defaultValues.title);
	}
	function _getTopBlock2() {
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"dashboard-create-top-block\">\n\t\t\t\t<div class=\"dashboard-create-top-block-image\"></div>\n\t\t\t\t<div class=\"dashboard-create-top-block-text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('DASHBOARD_CREATE_TOP_BLOCK'));
	}
	function _onParamSelectorInit2() {
	  babelHelpers.classPrivateFieldGet(this, _paramsSelector).selectGroups(babelHelpers.classPrivateFieldGet(this, _props).groupIds);
	  babelHelpers.classPrivateFieldGet(this, _saveButton).setDisabled(false);
	}
	main_core.Reflection.namespace('BX.BIConnector').SupersetDashboardCreateManager = SupersetDashboardCreateManager;

}((this.window = this.window || {}),BX,BX.Event,BX.BIConnector,BX.BIConnector,BX.UI));
//# sourceMappingURL=script.js.map
