/* eslint-disable */
this.BX = this.BX || {};
this.BX.BIConnector = this.BX.BIConnector || {};
this.BX.BIConnector.ApacheSuperset = this.BX.BIConnector.ApacheSuperset || {};
(function (exports,biconnector_entityEditor_field_settingsDateFilter,main_core,biconnector_apacheSupersetAnalytics,main_core_events) {
	'use strict';

	var SidePanel = BX.SidePanel;
	var SettingController = /*#__PURE__*/function (_BX$UI$EntityEditorCo) {
	  babelHelpers.inherits(SettingController, _BX$UI$EntityEditorCo);
	  function SettingController(id, settings) {
	    var _settings$config$dash, _settings$config;
	    var _this;
	    babelHelpers.classCallCheck(this, SettingController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SettingController).call(this));
	    _this.initialize(id, settings);
	    _this.analytic = (_settings$config$dash = (_settings$config = settings.config) === null || _settings$config === void 0 ? void 0 : _settings$config.dashboardAnalyticInfo) !== null && _settings$config$dash !== void 0 ? _settings$config$dash : {};
	    main_core_events.EventEmitter.subscribeOnce('BX.UI.EntityEditor:onInit', function (event) {
	      var _event$getData = event.getData(),
	        _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	        editor = _event$getData2[0];
	      editor === null || editor === void 0 ? void 0 : editor._toolPanel.disableSaveButton();
	    });
	    main_core_events.EventEmitter.subscribeOnce('BX.UI.EntityEditor:onControlChange', function (event) {
	      var _event$getData3 = event.getData(),
	        _event$getData4 = babelHelpers.slicedToArray(_event$getData3, 1),
	        editor = _event$getData4[0];
	      editor === null || editor === void 0 ? void 0 : editor._toolPanel.enableSaveButton();
	    });
	    main_core_events.EventEmitter.subscribeOnce('BX.UI.EntityEditor:onCancel', function (event) {
	      var _event$getData5 = event.getData(),
	        _event$getData6 = babelHelpers.slicedToArray(_event$getData5, 2),
	        eventArguments = _event$getData6[1];
	      eventArguments.enableCloseConfirmation = false;
	    });
	    main_core_events.EventEmitter.subscribeOnce('BX.UI.EntityEditor:onSave', function (event) {
	      var _event$getData7 = event.getData(),
	        _event$getData8 = babelHelpers.slicedToArray(_event$getData7, 2),
	        eventArguments = _event$getData8[1];
	      eventArguments.enableCloseConfirmation = false;
	    });
	    return _this;
	  }
	  babelHelpers.createClass(SettingController, [{
	    key: "onAfterSave",
	    value: function onAfterSave() {
	      var _this$_editor;
	      var analyticOptions;
	      if (main_core.Type.isStringFilled(this.analytic.type)) {
	        analyticOptions = {
	          type: this.analytic.type.toLowerCase(),
	          p1: biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.analytic.appId),
	          p2: this.analytic.id,
	          c_element: 'grid_menu',
	          status: 'success'
	        };
	      } else {
	        analyticOptions = {
	          c_element: 'grid_settings',
	          status: 'success'
	        };
	      }
	      biconnector_apacheSupersetAnalytics.ApacheSupersetAnalytics.sendAnalytics('edit', 'report_settings', analyticOptions);
	      this === null || this === void 0 ? void 0 : (_this$_editor = this._editor) === null || _this$_editor === void 0 ? void 0 : _this$_editor._modeSwitch.reset();
	      window.location.reload();
	    }
	  }, {
	    key: "innerCancel",
	    value: function innerCancel() {
	      SidePanel.Instance.close();
	    }
	  }]);
	  return SettingController;
	}(BX.UI.EntityEditorController);

	var Factory = /*#__PURE__*/function () {
	  function Factory(eventName) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Factory);
	    main_core_events.EventEmitter.subscribe(eventName + ':onInitialize', function (event) {
	      var _event$getCompatData = event.getCompatData(),
	        _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	        eventArgs = _event$getCompatData2[1];
	      eventArgs.methods['dashboardSettings'] = _this.factory.bind(_this);
	    });
	  }
	  babelHelpers.createClass(Factory, [{
	    key: "factory",
	    value: function factory(type, controlId, settings) {
	      if (type === 'settingComponentController') {
	        return new SettingController(controlId, settings);
	      }
	      return null;
	    }
	  }]);
	  return Factory;
	}();

	var Setting = /*#__PURE__*/function () {
	  function Setting() {
	    babelHelpers.classCallCheck(this, Setting);
	  }
	  babelHelpers.createClass(Setting, null, [{
	    key: "registerFieldFactory",
	    value: function registerFieldFactory(entityEditorControlFactory) {
	      new biconnector_entityEditor_field_settingsDateFilter.SettingsDateFilterFieldFactory(entityEditorControlFactory);
	    }
	  }, {
	    key: "registerControllerFactory",
	    value: function registerControllerFactory(entityEditorControllerFactory) {
	      new Factory(entityEditorControllerFactory);
	    }
	  }]);
	  return Setting;
	}();

	exports.Setting = Setting;

}((this.BX.BIConnector.ApacheSuperset.Dashboard = this.BX.BIConnector.ApacheSuperset.Dashboard || {}),BX.BIConnector.EntityEditor.Field,BX,BX.BIConnector,BX.Event));
//# sourceMappingURL=script.js.map
