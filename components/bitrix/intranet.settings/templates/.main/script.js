/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_analytics,ui_draganddrop_draggable,ui_switcherNested,ui_iconSet_crm,ui_uploader_stackWidget,ui_ears,ui_iconSet_social,ui_alerts,ui_form,ui_forms,ui_iconSet_actions,ui_iconSet_main,ui_formElements_view,ui_switcher,ui_entitySelector,ui_buttons,ui_icon_set,ui_section,sidepanel,ui_dialogs_messagebox,ui_formElements_field,main_core_events,main_popup,main_loader,main_core) {
	'use strict';

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _eventList = /*#__PURE__*/new WeakMap();
	var _tool = /*#__PURE__*/new WeakMap();
	var _context = /*#__PURE__*/new WeakMap();
	var Analytic = /*#__PURE__*/function () {
	  function Analytic() {
	    var context = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	    babelHelpers.classCallCheck(this, Analytic);
	    _classPrivateFieldInitSpec(this, _eventList, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(this, _tool, {
	      writable: true,
	      value: 'settings'
	    });
	    _classPrivateFieldInitSpec(this, _context, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _context, context);
	  }
	  babelHelpers.createClass(Analytic, [{
	    key: "addEvent",
	    value: function addEvent(eventType, eventData) {
	      if (babelHelpers.classPrivateFieldGet(this, _context).isBitrix24) {
	        babelHelpers.classPrivateFieldGet(this, _eventList)[eventType] = eventData;
	      }
	    }
	  }, {
	    key: "send",
	    value: function send() {
	      if (!babelHelpers.classPrivateFieldGet(this, _context).isBitrix24) {
	        return;
	      }
	      if (Object.keys(babelHelpers.classPrivateFieldGet(this, _eventList)).length > 0) {
	        main_core.ajax.runComponentAction('bitrix:intranet.settings', 'analytic', {
	          mode: 'class',
	          data: {
	            data: babelHelpers.classPrivateFieldGet(this, _eventList)
	          }
	        }).then(function () {});
	      }
	      babelHelpers.classPrivateFieldSet(this, _eventList, []);
	    }
	  }, {
	    key: "addEventOpenSettings",
	    value: function addEventOpenSettings() {
	      var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	      var options = {
	        event: AnalyticSettingsEvent.OPEN,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: 'slider',
	        p1: ((_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	        c_section: (_babelHelpers$classPr2 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr3 === void 0 ? void 0 : _babelHelpers$classPr3.analyticContext) !== null && _babelHelpers$classPr2 !== void 0 ? _babelHelpers$classPr2 : '',
	        c_element: (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4.locationName
	      };
	      ui_analytics.sendData(options);
	      //this.addEvent(AnalyticSettingsEvent.OPEN, options);
	    }
	  }, {
	    key: "addEventOpenTariffSelector",
	    value: function addEventOpenTariffSelector(fieldName) {
	      var _babelHelpers$classPr5;
	      var options = {
	        event: 'open_tariff',
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: fieldName,
	        p1: ((_babelHelpers$classPr5 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr5 === void 0 ? void 0 : _babelHelpers$classPr5.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	      };
	      this.addEvent(fieldName + '_open_tariff', options);
	    }
	  }, {
	    key: "addEventOpenHint",
	    value: function addEventOpenHint(fieldName) {
	      var _babelHelpers$classPr6;
	      var options = {
	        event: 'open_hint',
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: fieldName,
	        p1: ((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr6 === void 0 ? void 0 : _babelHelpers$classPr6.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	      };
	      this.addEvent(fieldName + '_open_hint', options);
	    }
	  }, {
	    key: "addEventStartPagePage",
	    value: function addEventStartPagePage(page) {
	      var _babelHelpers$classPr7;
	      var options = {
	        event: AnalyticSettingsEvent.START_PAGE,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: page,
	        p1: ((_babelHelpers$classPr7 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr7 === void 0 ? void 0 : _babelHelpers$classPr7.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	      };
	      ui_analytics.sendData(options);
	    }
	  }, {
	    key: "addEventChangePage",
	    value: function addEventChangePage(page) {
	      var _babelHelpers$classPr8;
	      var options = {
	        event: AnalyticSettingsEvent.VIEW,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: page,
	        p1: ((_babelHelpers$classPr8 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr8 === void 0 ? void 0 : _babelHelpers$classPr8.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	      };
	      ui_analytics.sendData(options);
	    }
	  }, {
	    key: "addEventToggleTools",
	    value: function addEventToggleTools(toolName, state) {
	      var _babelHelpers$classPr9, _babelHelpers$classPr10;
	      var event = 'onoff_tools';
	      var options = {
	        event: event,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: 'tools',
	        type: toolName,
	        c_element: (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr9 === void 0 ? void 0 : _babelHelpers$classPr9.locationName,
	        p1: ((_babelHelpers$classPr10 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr10 === void 0 ? void 0 : _babelHelpers$classPr10.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	        p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF
	      };
	      this.addEvent('tools' + toolName + '_' + event, options);
	    }
	  }, {
	    key: "addEventToggle2fa",
	    value: function addEventToggle2fa(state) {
	      var _babelHelpers$classPr11;
	      var event = '2fa_onoff';
	      var options = {
	        event: event,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: 'security',
	        p1: ((_babelHelpers$classPr11 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr11 === void 0 ? void 0 : _babelHelpers$classPr11.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	        p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF
	      };
	      this.addEvent('security_' + event, options);
	    }
	  }, {
	    key: "addEventConfigPortal",
	    value: function addEventConfigPortal(event) {
	      var options = {
	        event: event,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: AnalyticSettingsCategory.PORTAL
	      };
	      this.addEvent('portal_' + event, options);
	    }
	  }, {
	    key: "addEventChangeTheme",
	    value: function addEventChangeTheme(themeId) {
	      var regex = /custom_\d+/;
	      var preparedThemeId = regex.test(themeId) ? 'themeName_custom' : 'themeName_' + themeId;
	      var options = {
	        event: AnalyticSettingsEvent.CHANGE_PORTAL_THEME,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: AnalyticSettingsCategory.PORTAL,
	        type: AnalyticSettingsType.COMMON,
	        c_section: AnalyticSettingsSection.SETTINGS,
	        p1: preparedThemeId
	      };
	      this.addEvent('portal_' + AnalyticSettingsEvent.CHANGE_PORTAL_THEME, options);
	    }
	  }, {
	    key: "addEventConfigEmployee",
	    value: function addEventConfigEmployee(event, state) {
	      var _babelHelpers$classPr12;
	      var options = {
	        event: event,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: 'employee',
	        p1: ((_babelHelpers$classPr12 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr12 === void 0 ? void 0 : _babelHelpers$classPr12.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	        p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF
	      };
	      this.addEvent('employee_' + event, options);
	    }
	  }, {
	    key: "addEventConfigConfiguration",
	    value: function addEventConfigConfiguration(event, state) {
	      var _babelHelpers$classPr13;
	      var options = {
	        event: event,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: 'configuration',
	        p1: ((_babelHelpers$classPr13 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr13 === void 0 ? void 0 : _babelHelpers$classPr13.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	        p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF
	      };
	      this.addEvent('configuration_' + event, options);
	    }
	  }, {
	    key: "addEventConfigRequisite",
	    value: function addEventConfigRequisite(event) {
	      var _babelHelpers$classPr14, _babelHelpers$classPr15;
	      var options = {
	        event: event,
	        tool: babelHelpers.classPrivateFieldGet(this, _tool),
	        category: 'requisite',
	        c_element: (_babelHelpers$classPr14 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr14 === void 0 ? void 0 : _babelHelpers$classPr14.locationName,
	        p1: ((_babelHelpers$classPr15 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr15 === void 0 ? void 0 : _babelHelpers$classPr15.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	      };
	      ui_analytics.sendData(options);
	    }
	  }]);
	  return Analytic;
	}();
	var AnalyticSettingsCategory = function AnalyticSettingsCategory() {
	  babelHelpers.classCallCheck(this, AnalyticSettingsCategory);
	};
	babelHelpers.defineProperty(AnalyticSettingsCategory, "TOOLS", 'tools');
	babelHelpers.defineProperty(AnalyticSettingsCategory, "SECURITY", 'security');
	babelHelpers.defineProperty(AnalyticSettingsCategory, "AI", 'ai');
	babelHelpers.defineProperty(AnalyticSettingsCategory, "PORTAL", 'portal');
	babelHelpers.defineProperty(AnalyticSettingsCategory, "EMPLOYEE", 'employee');
	babelHelpers.defineProperty(AnalyticSettingsCategory, "COMMUNICATION", 'communication');
	babelHelpers.defineProperty(AnalyticSettingsCategory, "REQUISITE", 'requisite');
	babelHelpers.defineProperty(AnalyticSettingsCategory, "SCHEDULE", 'schedule');
	babelHelpers.defineProperty(AnalyticSettingsCategory, "CONFIGURATION", 'configuration');
	var AnalyticSettingsEvent = function AnalyticSettingsEvent() {
	  babelHelpers.classCallCheck(this, AnalyticSettingsEvent);
	};
	babelHelpers.defineProperty(AnalyticSettingsEvent, "OPEN", 'open_setting');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "START_PAGE", 'start_page');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "VIEW", 'view');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "TFA", '2fa_onoff');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_PORTAL_NAME", 'change_portal_name');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_PORTAL_LOGO", 'change_portal_logo');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_PORTAL_SITE", 'change_portal_site');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_PORTAL_THEME", 'change_portal_theme');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_MARKET", 'change_market');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_PAY_TARIFF", 'change_pay_tariff');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CREATE_CARD", 'create_vizitka');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "EDIT_CARD", 'edit_vizitka');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "COPY_LINK_CARD", 'copylink_vizitka');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "OPEN_ADD_COMPANY", 'open_add_company');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_QUICK_REG", 'change_quick_reg');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_REG_ALL", 'change_reg_all');
	babelHelpers.defineProperty(AnalyticSettingsEvent, "CHANGE_EXTRANET_INVITE", 'change_extranet_invite');
	var AnalyticSettingsSection = function AnalyticSettingsSection() {
	  babelHelpers.classCallCheck(this, AnalyticSettingsSection);
	};
	babelHelpers.defineProperty(AnalyticSettingsSection, "SETTINGS", 'settings');
	var AnalyticSettingsType = function AnalyticSettingsType() {
	  babelHelpers.classCallCheck(this, AnalyticSettingsType);
	};
	babelHelpers.defineProperty(AnalyticSettingsType, "COMMON", 'common');
	var AnalyticSettingsUserRole = function AnalyticSettingsUserRole() {
	  babelHelpers.classCallCheck(this, AnalyticSettingsUserRole);
	};
	babelHelpers.defineProperty(AnalyticSettingsUserRole, "ADMIN", 'isAdmin_Y');
	babelHelpers.defineProperty(AnalyticSettingsUserRole, "NOT_ADMIN", 'isAdmin_N');
	var AnalyticSettingsTurnState = function AnalyticSettingsTurnState() {
	  babelHelpers.classCallCheck(this, AnalyticSettingsTurnState);
	};
	babelHelpers.defineProperty(AnalyticSettingsTurnState, "ON", 'turn_on');
	babelHelpers.defineProperty(AnalyticSettingsTurnState, "OFF", 'turn_off');

	var _templateObject;
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _checker = /*#__PURE__*/new WeakMap();
	var _content = /*#__PURE__*/new WeakMap();
	var SiteTitle24Field = /*#__PURE__*/function (_BaseSettingsElement) {
	  babelHelpers.inherits(SiteTitle24Field, _BaseSettingsElement);
	  function SiteTitle24Field(params) {
	    var _params$title;
	    var _this;
	    babelHelpers.classCallCheck(this, SiteTitle24Field);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SiteTitle24Field).call(this));
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _checker, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _content, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('BX.Intranet.Settings');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _checker, new ui_formElements_view.Checker({
	      id: 'siteLogo24',
	      inputName: 'logo24',
	      title: (_params$title = params.title) !== null && _params$title !== void 0 ? _params$title : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_LOGO24'),
	      size: 'extra-small',
	      // hintOn: '',
	      // hintOff: '',
	      isEnable: params.isEnable,
	      checked: params.checked !== '',
	      value: 'Y',
	      bannerCode: 'limit_admin_logo24',
	      hideSeparator: true
	    }));
	    return _this;
	  }
	  babelHelpers.createClass(SiteTitle24Field, [{
	    key: "getFieldView",
	    value: function getFieldView() {
	      return babelHelpers.classPrivateFieldGet(this, _checker);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      if (babelHelpers.classPrivateFieldGet(this, _content)) {
	        return babelHelpers.classPrivateFieldGet(this, _content);
	      }
	      babelHelpers.classPrivateFieldSet(this, _content, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-section__field-selector --align-center\">\n\t\t\t\t<div class=\"ui-section__hint\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _checker).render()));
	      return babelHelpers.classPrivateFieldGet(this, _content);
	    }
	  }]);
	  return SiteTitle24Field;
	}(ui_formElements_field.BaseSettingsElement);

	var _templateObject$1;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _content$1 = /*#__PURE__*/new WeakMap();
	var _contentLogo = /*#__PURE__*/new WeakMap();
	var _title = /*#__PURE__*/new WeakMap();
	var _logo = /*#__PURE__*/new WeakMap();
	var _inputMonitoringIntervalId = /*#__PURE__*/new WeakMap();
	var _inputMonitoringCountdown = /*#__PURE__*/new WeakMap();
	var _inputMonitoringPrevState = /*#__PURE__*/new WeakMap();
	var _initTitle = /*#__PURE__*/new WeakSet();
	var _initLogo = /*#__PURE__*/new WeakSet();
	var SiteTitleField = /*#__PURE__*/function (_BaseSettingsElement) {
	  babelHelpers.inherits(SiteTitleField, _BaseSettingsElement);
	  function SiteTitleField(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, SiteTitleField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SiteTitleField).call(this, params));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _initLogo);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _initTitle);
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _content$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _contentLogo, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _title, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _logo, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _inputMonitoringIntervalId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _inputMonitoringCountdown, {
	      writable: true,
	      value: 10
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _inputMonitoringPrevState, {
	      writable: true,
	      value: void 0
	    });
	    _this.setParentElement(params.parent);
	    _this.setEventNamespace('BX.Intranet.Settings');
	    var _options = params.siteTitleOptions;
	    _this.options = {
	      title: _options.title,
	      canUserEditTitle: _options.canUserEditTitle,
	      logo24: _options.logo24,
	      canUserEditLogo24: _options.canUserEditLogo24
	    };
	    var _labels = params.siteTitleLabels;
	    _this.labels = {
	      title: _labels.title,
	      logo24: _labels.logo24
	    };
	    _classPrivateMethodGet(babelHelpers.assertThisInitialized(_this), _initTitle, _initTitle2).call(babelHelpers.assertThisInitialized(_this), _options, _labels);
	    _classPrivateMethodGet(babelHelpers.assertThisInitialized(_this), _initLogo, _initLogo2).call(babelHelpers.assertThisInitialized(_this), _options, _labels);
	    return _this;
	  }
	  babelHelpers.createClass(SiteTitleField, [{
	    key: "getFieldView",
	    value: function getFieldView() {
	      return babelHelpers.classPrivateFieldGet(this, _title);
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {}
	  }, {
	    key: "startInputMonitoring",
	    value: function startInputMonitoring() {
	      if (babelHelpers.classPrivateFieldGet(this, _inputMonitoringIntervalId) > 0) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _inputMonitoringIntervalId, setInterval(this.monitorInput.bind(this), 500));
	    }
	  }, {
	    key: "stopInputMonitoring",
	    value: function stopInputMonitoring() {
	      if (babelHelpers.classPrivateFieldGet(this, _inputMonitoringIntervalId) > 0) {
	        clearInterval(babelHelpers.classPrivateFieldGet(this, _inputMonitoringIntervalId));
	        babelHelpers.classPrivateFieldSet(this, _inputMonitoringIntervalId, null);
	      }
	    }
	  }, {
	    key: "monitorInput",
	    value: function monitorInput() {
	      var _this$inputMonitoring;
	      var value = babelHelpers.classPrivateFieldGet(this, _title).getInputNode().value;
	      if (babelHelpers.classPrivateFieldGet(this, _inputMonitoringPrevState) !== value) {
	        babelHelpers.classPrivateFieldSet(this, _inputMonitoringCountdown, 10);
	        babelHelpers.classPrivateFieldSet(this, _inputMonitoringPrevState, value);
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', new main_core_events.BaseEvent({
	          data: {
	            title: value
	          }
	        }));
	      } else if (babelHelpers.classPrivateFieldSet(this, _inputMonitoringCountdown, (_this$inputMonitoring = babelHelpers.classPrivateFieldGet(this, _inputMonitoringCountdown), --_this$inputMonitoring)) <= 0) {
	        this.stopInputMonitoring();
	      }
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this2 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _content$1)) {
	        return babelHelpers.classPrivateFieldGet(this, _content$1);
	      }
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _title).getInputNode(), 'focus', this.startInputMonitoring.bind(this));
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _title).getInputNode(), 'keydown', this.startInputMonitoring.bind(this));
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _title).getInputNode(), 'click', this.startInputMonitoring.bind(this));
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _title).getInputNode(), 'blur', this.stopInputMonitoring.bind(this));
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _title).getInputNode(), 'blur', this.stopInputMonitoring.bind(this));
	      babelHelpers.classPrivateFieldGet(this, _logo).getFieldView().subscribe('change', function (event) {
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, _this2.getEventNamespace() + ':Portal:Change', new main_core_events.BaseEvent({
	          data: {
	            logo24: event.getData() === true ? '24' : ''
	          }
	        }));
	      });
	      babelHelpers.classPrivateFieldSet(this, _content$1, main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div id=\"", "\" class=\"ui-section__field-selector --no-border --no-margin --align-center\">\n\t\t\t<div class=\"ui-section__field-container\">\n\t\t\t\t<div class=\"ui-section__field-label_box\">\n\t\t\t\t\t<label class=\"ui-section__field-label\" for=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</label> \n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-section__field-inner\">\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-block\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _title).getId(), babelHelpers.classPrivateFieldGet(this, _title).getName(), babelHelpers.classPrivateFieldGet(this, _title).getLabel(), babelHelpers.classPrivateFieldGet(this, _title).getInputNode()));
	      return babelHelpers.classPrivateFieldGet(this, _content$1);
	    }
	  }, {
	    key: "getLogo24Field",
	    value: function getLogo24Field() {
	      return babelHelpers.classPrivateFieldGet(this, _logo);
	    }
	  }]);
	  return SiteTitleField;
	}(ui_formElements_field.BaseSettingsElement);
	function _initTitle2(options, labels) {
	  var _labels$title;
	  babelHelpers.classPrivateFieldSet(this, _title, new ui_formElements_view.TextInput({
	    value: options.title,
	    placeholder: options.title,
	    label: (_labels$title = labels.title) !== null && _labels$title !== void 0 ? _labels$title : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE_INPUT_LABEL'),
	    id: 'siteTitle',
	    inputName: 'title',
	    isEnable: true
	    // bannerCode: '123',
	    // helpDeskCode: '234',
	    // helpMessageProvider: () => {}
	  }));

	  babelHelpers.classPrivateFieldGet(this, _title).setEventNamespace(this.getEventNamespace());
	}
	function _initLogo2(options, labels) {
	  babelHelpers.classPrivateFieldSet(this, _logo, new SiteTitle24Field({
	    title: labels.logo24,
	    isEnable: options.canUserEditLogo24,
	    checked: options.logo24
	  }));
	}

	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _settings = /*#__PURE__*/new WeakMap();
	var _currentPage = /*#__PURE__*/new WeakMap();
	var _prevPage = /*#__PURE__*/new WeakMap();
	var Navigation = /*#__PURE__*/function () {
	  function Navigation(settings) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Navigation);
	    _classPrivateFieldInitSpec$3(this, _settings, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(this, _currentPage, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(this, _prevPage, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _settings, settings);
	    main_core_events.EventEmitter.subscribe('BX.Intranet.SettingsNavigation:onMove', function (event) {
	      var _this$getCurrentPage;
	      var _event$data = event.data,
	        page = _event$data.page,
	        fieldName = _event$data.fieldName;
	      if (((_this$getCurrentPage = _this.getCurrentPage()) === null || _this$getCurrentPage === void 0 ? void 0 : _this$getCurrentPage.getType()) === page) {
	        _this.moveTo(_this.getCurrentPage(), fieldName);
	        return;
	      }
	      var pageObj = _this.getPageByType(page);
	      if (!(pageObj !== null && pageObj !== void 0 && pageObj.hasData())) {
	        main_core_events.EventEmitter.subscribeOnce('BX.Intranet.Settings:onPageComplete', function (event) {
	          if (event.data.page.hasContent()) {
	            _this.moveTo(event.data.page, fieldName);
	          }
	        });
	      }
	      main_core_events.EventEmitter.subscribeOnce('BX.Intranet.Settings:onAfterShowPage', function (event) {
	        if (event.data.page.hasContent()) {
	          _this.moveTo(event.data.page, fieldName);
	        }
	      });
	      babelHelpers.classPrivateFieldGet(_this, _settings).show(page);
	    });
	  }
	  babelHelpers.createClass(Navigation, [{
	    key: "getPageByType",
	    value: function getPageByType(type) {
	      return this.getPages().find(function (page) {
	        return page.getType() === type;
	      });
	    }
	  }, {
	    key: "getCurrentPage",
	    value: function getCurrentPage() {
	      return babelHelpers.classPrivateFieldGet(this, _currentPage);
	    }
	  }, {
	    key: "getPrevPage",
	    value: function getPrevPage() {
	      return babelHelpers.classPrivateFieldGet(this, _prevPage);
	    }
	  }, {
	    key: "changePage",
	    value: function changePage(page) {
	      if (!(page instanceof ui_formElements_field.BaseSettingsPage)) {
	        console.log('Not found "' + type + '" page');
	        return;
	      }
	      if (page === babelHelpers.classPrivateFieldGet(this, _currentPage)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _prevPage, babelHelpers.classPrivateFieldGet(this, _currentPage));
	      babelHelpers.classPrivateFieldSet(this, _currentPage, page);
	    }
	  }, {
	    key: "getPages",
	    value: function getPages() {
	      return babelHelpers.classPrivateFieldGet(this, _settings).getChildrenElements();
	    }
	  }, {
	    key: "updateAddressBar",
	    value: function updateAddressBar() {
	      var _this$getCurrentPage2;
	      var url = new URL(window.location.href);
	      url.searchParams.set('page', (_this$getCurrentPage2 = this.getCurrentPage()) === null || _this$getCurrentPage2 === void 0 ? void 0 : _this$getCurrentPage2.getType());
	      url.searchParams["delete"]('IFRAME');
	      url.searchParams["delete"]('IFRAME_TYPE');
	      top.window.history.replaceState(null, '', url.toString());
	    }
	  }, {
	    key: "findByFieldName",
	    value: function findByFieldName(rootNode, fieldName) {
	      var _node$shift;
	      var node = ui_formElements_field.RecursiveFilteringVisitor.startFrom(rootNode, function (node) {
	        if (node instanceof ui_formElements_field.SettingsSection && node.getSectionView().getId() === fieldName) {
	          return true;
	        }
	        if (node instanceof ui_formElements_field.TabField && node.getFieldView().getId() === fieldName) {
	          return true;
	        }
	        return (node instanceof ui_formElements_field.SettingsField || node instanceof SiteTitleField || node instanceof SiteTitle24Field) && (node.getFieldView().getName() === fieldName || node.getFieldView().getId() === fieldName);
	      });
	      return (_node$shift = node.shift()) !== null && _node$shift !== void 0 ? _node$shift : null;
	    }
	  }, {
	    key: "scrollToNode",
	    value: function scrollToNode(node) {
	      var element = node.render();
	      var headerOffset = 45;
	      var elementPosition = element.getBoundingClientRect().top;
	      var offsetPosition = elementPosition + window.pageYOffset - headerOffset;
	      scrollTo({
	        top: offsetPosition,
	        behavior: "smooth"
	      });
	    }
	  }, {
	    key: "moveTo",
	    value: function moveTo(element, fieldName) {
	      var fieldNode = this.findByFieldName(element, fieldName);
	      if (main_core.Type.isNil(fieldNode)) {
	        return;
	      }
	      var isColored = false;
	      ui_formElements_field.AscendingOpeningVisitor.startFrom(fieldNode, function (element) {
	        if (element instanceof ui_formElements_field.SettingsRow) {
	          element.getRowView().show();
	        } else if (element instanceof ui_formElements_field.SettingsSection) {
	          element.getSectionView().toggle(true, false);
	        } else if (element instanceof ui_formElements_field.TabField) {
	          var tabs = element.getParentElement();
	          if (tabs instanceof ui_formElements_field.TabsField) {
	            tabs.activateTab(element, false);
	          }
	        }
	        if (!isColored) {
	          isColored = element.highlight();
	        }
	      });
	      this.scrollToNode(fieldNode);
	    }
	  }]);
	  return Navigation;
	}();

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _inputForSaveSortTools = /*#__PURE__*/new WeakMap();
	var _toolsWrapperRow = /*#__PURE__*/new WeakMap();
	var _draggable = /*#__PURE__*/new WeakMap();
	var _mainSection = /*#__PURE__*/new WeakMap();
	var _settingsSection = /*#__PURE__*/new WeakMap();
	var _renderToolsSelectors = /*#__PURE__*/new WeakSet();
	var _getSubToolHelpMessage = /*#__PURE__*/new WeakSet();
	var _getToolsSelectorsItems = /*#__PURE__*/new WeakSet();
	var _getMainSection = /*#__PURE__*/new WeakSet();
	var _getSettingsSection = /*#__PURE__*/new WeakSet();
	var _getInputForSaveSortTools = /*#__PURE__*/new WeakSet();
	var _getDraggable = /*#__PURE__*/new WeakSet();
	var _getToolsWrapperRow = /*#__PURE__*/new WeakSet();
	var _getWarningMessage = /*#__PURE__*/new WeakSet();
	var ToolsPage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(ToolsPage, _BaseSettingsPage);
	  function ToolsPage() {
	    var _this;
	    babelHelpers.classCallCheck(this, ToolsPage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ToolsPage).call(this));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getWarningMessage);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getToolsWrapperRow);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getDraggable);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getInputForSaveSortTools);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getSettingsSection);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getMainSection);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getToolsSelectorsItems);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getSubToolHelpMessage);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _renderToolsSelectors);
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _inputForSaveSortTools, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _toolsWrapperRow, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _draggable, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _mainSection, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _settingsSection, {
	      writable: true,
	      value: void 0
	    });
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_TOOLS');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_TOOLS');
	    return _this;
	  }
	  babelHelpers.createClass(ToolsPage, [{
	    key: "getType",
	    value: function getType() {
	      return 'tools';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var description = new ui_section.Row({
	        content: this.getDescription().getContainer()
	      });
	      new ui_formElements_field.SettingsRow({
	        row: description,
	        parent: _classPrivateMethodGet$1(this, _getSettingsSection, _getSettingsSection2).call(this)
	      });
	      if (this.hasValue('tools')) {
	        _classPrivateMethodGet$1(this, _renderToolsSelectors, _renderToolsSelectors2).call(this);
	      }
	      _classPrivateMethodGet$1(this, _getSettingsSection, _getSettingsSection2).call(this).renderTo(contentNode);
	    }
	  }, {
	    key: "getDescription",
	    value: function getDescription() {
	      var descriptionText = "\n\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_DESCRIPTION'), "\n\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=18213196')\">\n\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t</a>\n\t\t");
	      return new BX.UI.Alert({
	        text: descriptionText,
	        inline: true,
	        size: BX.UI.Alert.Size.SMALL,
	        color: BX.UI.Alert.Color.PRIMARY,
	        animated: true
	      });
	    }
	  }]);
	  return ToolsPage;
	}(ui_formElements_field.BaseSettingsPage);
	function _renderToolsSelectors2() {
	  var _this2 = this;
	  var tools = this.getValue('tools');
	  var startSort = [];
	  Object.keys(tools).forEach(function (item) {
	    var _tool$settingsTitle;
	    startSort.push(tools[item].menuId);
	    var tool = tools[item];
	    var subgroups = tool.subgroups;
	    var toolSelectorItems = [];
	    if (Object.keys(subgroups).length > 0) {
	      toolSelectorItems = _classPrivateMethodGet$1(_this2, _getToolsSelectorsItems, _getToolsSelectorsItems2).call(_this2, subgroups, tool);
	    }
	    var toolSelector = new ui_switcherNested.SwitcherNested({
	      id: tool.code,
	      title: tool.name,
	      link: _this2.getPermission().canEdit() ? tool['settings-path'] : null,
	      infoHelperCode: _this2.getPermission().canEdit() ? tool['infohelper-slider'] : null,
	      linkTitle: (_tool$settingsTitle = tool['settings-title']) !== null && _tool$settingsTitle !== void 0 ? _tool$settingsTitle : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_LINK_SETTINGS'),
	      isChecked: tool.enabled,
	      mainInputName: tool.code,
	      isOpen: false,
	      items: toolSelectorItems,
	      isDisabled: !_this2.getPermission().canEdit(),
	      isDefault: tool["default"],
	      helpMessage: !_this2.getPermission().canEdit() ? main_core.Loc.getMessage('INTRANET_SETTINGS_ELEMENT_PERMISSION_MSG') : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_DISABLED', {
	        '#TOOL#': tool.name
	      })
	    });
	    var toolSelectorSection = new ui_formElements_field.SettingsSection({
	      section: toolSelector
	    });
	    main_core.Dom.style(toolSelectorSection.getSectionView().render(), 'margin-bottom', '8px');
	    main_core.Dom.attr(toolSelectorSection.getSectionView().render(), 'data-menu-id', tool.menuId);
	    _classPrivateMethodGet$1(_this2, _getToolsWrapperRow, _getToolsWrapperRow2).call(_this2).append(toolSelectorSection.getSectionView().render());
	    new ui_formElements_field.SettingsRow({
	      row: _classPrivateMethodGet$1(_this2, _getToolsWrapperRow, _getToolsWrapperRow2).call(_this2),
	      parent: _classPrivateMethodGet$1(_this2, _getSettingsSection, _getSettingsSection2).call(_this2),
	      child: toolSelectorSection
	    });
	  });
	}
	function _getSubToolHelpMessage2(tool, parentToolName) {
	  if (!this.getPermission().canEdit()) {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_ELEMENT_PERMISSION_MSG');
	  }
	  if (tool.disabled) {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_DISABLED', {
	      '#TOOL#': tool.name
	    });
	  }
	  if (tool["default"]) {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_MAIN_TOOL', {
	      '#TOOL#': parentToolName !== null && parentToolName !== void 0 ? parentToolName : ''
	    });
	  }
	  return '';
	}
	function _getToolsSelectorsItems2(subgroups, tool) {
	  var _this3 = this;
	  var toolSelectorItems = [];
	  Object.keys(subgroups).forEach(function (item) {
	    var _subgroupConfig$setti, _ref, _subgroupConfig$defau;
	    var subgroupConfig = subgroups[item];
	    if (main_core.Type.isNull(subgroupConfig.name) || main_core.Type.isNull(subgroupConfig.code) || main_core.Type.isNull(subgroupConfig.enabled)) {
	      return;
	    }
	    var toolSelectorItem = new ui_switcherNested.SwitcherNestedItem({
	      title: subgroupConfig.name,
	      id: subgroupConfig.code,
	      inputName: subgroupConfig.code,
	      isChecked: subgroupConfig.enabled,
	      settingsPath: _this3.getPermission().canEdit() ? subgroupConfig['settings_path'] : null,
	      settingsTitle: (_subgroupConfig$setti = subgroupConfig['settings_title']) !== null && _subgroupConfig$setti !== void 0 ? _subgroupConfig$setti : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_LINK_SETTINGS'),
	      infoHelperCode: _this3.getPermission().canEdit() ? subgroupConfig['infohelper-slider'] : null,
	      isDisabled: !_this3.getPermission().canEdit(),
	      isDefault: (_ref = (_subgroupConfig$defau = subgroupConfig["default"]) !== null && _subgroupConfig$defau !== void 0 ? _subgroupConfig$defau : subgroupConfig.disabled) !== null && _ref !== void 0 ? _ref : false,
	      helpMessage: _classPrivateMethodGet$1(_this3, _getSubToolHelpMessage, _getSubToolHelpMessage2).call(_this3, subgroupConfig, tool.name)
	    });
	    if (subgroupConfig.disabled) {
	      main_core.Dom.style(toolSelectorItem.getSwitcher().getNode(), {
	        opacity: '0.4'
	      });
	    } else {
	      main_core_events.EventEmitter.subscribe(toolSelectorItem.getSwitcher(), 'toggled', function () {
	        var _this3$getAnalytic;
	        (_this3$getAnalytic = _this3.getAnalytic()) === null || _this3$getAnalytic === void 0 ? void 0 : _this3$getAnalytic.addEventToggleTools(subgroupConfig.code, toolSelectorItem.getSwitcher().isChecked());
	      });
	    }
	    if (subgroupConfig.code === 'tool_subgroup_team_work_instant_messenger') {
	      main_core.Event.bind(toolSelectorItem.getSwitcher().getNode(), 'click', function () {
	        if (!toolSelectorItem.getSwitcher().isChecked()) {
	          _classPrivateMethodGet$1(_this3, _getWarningMessage, _getWarningMessage2).call(_this3, subgroupConfig.code, toolSelectorItem.getSwitcher().getNode(), main_core.Loc.getMessage('INTRANET_SETTINGS_WARNING_TOOL_INSTANT_MESSENGER')).show();
	        }
	      });
	    }
	    toolSelectorItems.push(toolSelectorItem);
	  });
	  return toolSelectorItems;
	}
	function _getMainSection2() {
	  if (babelHelpers.classPrivateFieldGet(this, _mainSection)) {
	    return babelHelpers.classPrivateFieldGet(this, _mainSection);
	  }
	  babelHelpers.classPrivateFieldSet(this, _mainSection, new ui_section.Section(this.getValue('sectionTools')));
	  return babelHelpers.classPrivateFieldGet(this, _mainSection);
	}
	function _getSettingsSection2() {
	  if (babelHelpers.classPrivateFieldGet(this, _settingsSection)) {
	    return babelHelpers.classPrivateFieldGet(this, _settingsSection);
	  }
	  babelHelpers.classPrivateFieldSet(this, _settingsSection, new ui_formElements_field.SettingsSection({
	    section: _classPrivateMethodGet$1(this, _getMainSection, _getMainSection2).call(this),
	    parent: this
	  }));
	  return babelHelpers.classPrivateFieldGet(this, _settingsSection);
	}
	function _getToolsWrapperRow2() {
	  if (babelHelpers.classPrivateFieldGet(this, _toolsWrapperRow)) {
	    return babelHelpers.classPrivateFieldGet(this, _toolsWrapperRow);
	  }
	  babelHelpers.classPrivateFieldSet(this, _toolsWrapperRow, new ui_section.Row({}));
	  return babelHelpers.classPrivateFieldGet(this, _toolsWrapperRow);
	}
	function _getWarningMessage2(toolId, bindElement, message) {
	  return BX.PopupWindowManager.create(toolId, bindElement, {
	    content: message,
	    darkMode: true,
	    autoHide: true,
	    angle: true,
	    offsetLeft: 14,
	    bindOptions: {
	      position: 'bottom'
	    },
	    closeByEsc: true
	  });
	}

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _buildAdditionalSection = /*#__PURE__*/new WeakSet();
	var _buildProfileSection = /*#__PURE__*/new WeakSet();
	var _buildInviteSection = /*#__PURE__*/new WeakSet();
	var EmployeePage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(EmployeePage, _BaseSettingsPage);
	  function EmployeePage() {
	    var _this;
	    babelHelpers.classCallCheck(this, EmployeePage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EmployeePage).call(this));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _buildInviteSection);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _buildProfileSection);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _buildAdditionalSection);
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_EMPLOYEE');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_EMPLOYEE_BOX');
	    return _this;
	  }
	  babelHelpers.createClass(EmployeePage, [{
	    key: "onSuccessDataFetched",
	    value: function onSuccessDataFetched(response) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(EmployeePage.prototype), "onSuccessDataFetched", this).call(this, response);
	      if (this.hasValue('IS_BITRIX_24') && this.getValue('IS_BITRIX_24')) {
	        this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_EMPLOYEE');
	        this.render().querySelector('.intranet-settings__page-header_desc').innerText = this.descriptionPage;
	      }
	    }
	  }, {
	    key: "getType",
	    value: function getType() {
	      return 'employee';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var profileSection = _classPrivateMethodGet$2(this, _buildProfileSection, _buildProfileSection2).call(this);
	      profileSection === null || profileSection === void 0 ? void 0 : profileSection.renderTo(contentNode);
	      var inviteSection = _classPrivateMethodGet$2(this, _buildInviteSection, _buildInviteSection2).call(this);
	      inviteSection === null || inviteSection === void 0 ? void 0 : inviteSection.renderTo(contentNode);
	      var additionalSection = _classPrivateMethodGet$2(this, _buildAdditionalSection, _buildAdditionalSection2).call(this);
	      additionalSection === null || additionalSection === void 0 ? void 0 : additionalSection.renderTo(contentNode);
	    }
	  }]);
	  return EmployeePage;
	}(ui_formElements_field.BaseSettingsPage);
	function _buildAdditionalSection2() {
	  var _this2 = this;
	  if (!this.hasValue('SECTION_ADDITIONAL')) {
	    return;
	  }
	  var additionalSection = new ui_section.Section(this.getValue('SECTION_ADDITIONAL'));
	  var sectionSettings = new ui_formElements_field.SettingsSection({
	    section: additionalSection,
	    parent: this
	  });
	  if (this.hasValue('allow_company_pulse')) {
	    var companyPulseField = new ui_formElements_view.Checker(this.getValue('allow_company_pulse'));
	    main_core_events.EventEmitter.subscribe(companyPulseField.switcher, 'toggled', function () {
	      var _this2$getAnalytic;
	      (_this2$getAnalytic = _this2.getAnalytic()) === null || _this2$getAnalytic === void 0 ? void 0 : _this2$getAnalytic.addEventConfigEmployee(AnalyticSettingsEvent.CHANGE_QUICK_REG, companyPulseField.isChecked());
	    });
	    EmployeePage.addToSectionHelper(companyPulseField, sectionSettings);
	  }
	  return sectionSettings;
	}
	function _buildProfileSection2() {
	  if (!this.hasValue('SECTION_PROFILE')) {
	    return;
	  }
	  var profileSection = new ui_section.Section(this.getValue('SECTION_PROFILE'));
	  var sectionSettings = new ui_formElements_field.SettingsSection({
	    section: profileSection,
	    parent: this
	  });
	  if (this.hasValue('fieldFormatName')) {
	    var _this$getValue$label;
	    var hasSelectValue = false;
	    var currentValue = this.getValue('fieldFormatName').current;
	    var _iterator = _createForOfIteratorHelper(this.getValue('fieldFormatName').values),
	      _step;
	    try {
	      for (_iterator.s(); !(_step = _iterator.n()).done;) {
	        var value = _step.value;
	        if (value.selected === true) {
	          hasSelectValue = true;
	        }
	      }
	    } catch (err) {
	      _iterator.e(err);
	    } finally {
	      _iterator.f();
	    }
	    this.getValue('fieldFormatName').values.push({
	      value: 'other',
	      name: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_OPTION_OTHER'),
	      selected: !hasSelectValue
	    });
	    var nameFormatField = new ui_formElements_view.Selector({
	      label: (_this$getValue$label = this.getValue('fieldFormatName').label) !== null && _this$getValue$label !== void 0 ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_NAME_FORMAT'),
	      name: this.getValue('fieldFormatName').name + '_selector',
	      items: this.getValue('fieldFormatName').values,
	      hints: this.getValue('fieldFormatName').hints,
	      current: this.getValue('fieldFormatName').current
	    });
	    var settingsField = new ui_formElements_field.SettingsField({
	      fieldView: nameFormatField
	    });
	    new ui_formElements_field.SettingsRow({
	      child: settingsField,
	      parent: sectionSettings
	    });
	    var customFormatNameField = new ui_formElements_view.TextInput({
	      inputName: this.getValue('fieldFormatName').name,
	      label: '',
	      value: currentValue
	    });
	    settingsField = new ui_formElements_field.SettingsField({
	      fieldView: customFormatNameField
	    });
	    var customFormatNameRow = new ui_section.Row({
	      isHidden: true
	    });
	    new ui_formElements_field.SettingsRow({
	      row: customFormatNameRow,
	      parent: sectionSettings,
	      child: settingsField
	    });
	    if (!hasSelectValue) {
	      customFormatNameRow.show();
	    }
	    nameFormatField.getInputNode().addEventListener('change', function (event) {
	      if (event.target.value === 'other') {
	        customFormatNameRow.show();
	      } else {
	        customFormatNameField.getInputNode().value = nameFormatField.getInputNode().value;
	        customFormatNameRow.hide();
	      }
	    });
	  }
	  if (this.hasValue('fieldFormatPhoneNumber')) {
	    var formatNumberField = new ui_formElements_view.Selector(this.getValue('fieldFormatPhoneNumber'));
	    EmployeePage.addToSectionHelper(formatNumberField, sectionSettings);
	  }
	  if (this.hasValue('fieldFormatAddress')) {
	    var addressFormatField = new ui_formElements_view.Selector(this.getValue('fieldFormatAddress'));
	    var addressFormatRow = new ui_section.Row({
	      separator: this.hasValue('show_year_for_female') ? 'bottom' : null,
	      className: '--block'
	    });
	    EmployeePage.addToSectionHelper(addressFormatField, sectionSettings, addressFormatRow);
	  }
	  if (this.hasValue('show_year_for_female')) {
	    var showBirthYearField = new ui_formElements_view.InlineChecker(this.getValue('show_year_for_female'));
	    EmployeePage.addToSectionHelper(showBirthYearField, sectionSettings);
	  }
	  return sectionSettings;
	}
	function _buildInviteSection2() {
	  var _this3 = this;
	  if (!this.hasValue('SECTION_INVITE')) {
	    return;
	  }
	  var inviteSection = new ui_section.Section(this.getValue('SECTION_INVITE'));
	  var sectionSettings = new ui_formElements_field.SettingsSection({
	    section: inviteSection,
	    parent: this
	  });
	  if (this.hasValue('allow_register')) {
	    var fastReqField = new ui_formElements_view.Checker(this.getValue('allow_register'));
	    main_core_events.EventEmitter.subscribe(fastReqField.switcher, 'toggled', function () {
	      var _this3$getAnalytic;
	      (_this3$getAnalytic = _this3.getAnalytic()) === null || _this3$getAnalytic === void 0 ? void 0 : _this3$getAnalytic.addEventConfigEmployee(AnalyticSettingsEvent.CHANGE_QUICK_REG, fastReqField.isChecked());
	    });
	    EmployeePage.addToSectionHelper(fastReqField, sectionSettings);
	  }
	  if (this.hasValue('allow_invite_users')) {
	    var inviteToUserField = new ui_formElements_view.Checker(this.getValue('allow_invite_users'));
	    main_core_events.EventEmitter.subscribe(inviteToUserField.switcher, 'toggled', function () {
	      var _this3$getAnalytic2;
	      (_this3$getAnalytic2 = _this3.getAnalytic()) === null || _this3$getAnalytic2 === void 0 ? void 0 : _this3$getAnalytic2.addEventConfigEmployee(AnalyticSettingsEvent.CHANGE_REG_ALL, inviteToUserField.isChecked());
	    });
	    EmployeePage.addToSectionHelper(inviteToUserField, sectionSettings);
	  }
	  if (this.hasValue('show_fired_employees')) {
	    var showQuitField = new ui_formElements_view.Checker(this.getValue('show_fired_employees'));
	    EmployeePage.addToSectionHelper(showQuitField, sectionSettings);
	  }
	  if (this.hasValue('general_chat_message_join')) {
	    var newUserField = new ui_formElements_view.Checker(this.getValue('general_chat_message_join'));
	    EmployeePage.addToSectionHelper(newUserField, sectionSettings);
	  }
	  if (this.hasValue('allow_new_user_lf')) {
	    var newUserLfField = new ui_formElements_view.Checker(this.getValue('allow_new_user_lf'));
	    EmployeePage.addToSectionHelper(newUserLfField, sectionSettings);
	  }
	  if (this.hasValue('feature_extranet')) {
	    var extranetField = new ui_formElements_view.Checker(this.getValue('feature_extranet'));
	    main_core_events.EventEmitter.subscribe(extranetField.switcher, 'toggled', function () {
	      var _this3$getAnalytic3;
	      (_this3$getAnalytic3 = _this3.getAnalytic()) === null || _this3$getAnalytic3 === void 0 ? void 0 : _this3$getAnalytic3.addEventConfigEmployee(AnalyticSettingsEvent.CHANGE_EXTRANET_INVITE, extranetField.isChecked());
	    });
	    EmployeePage.addToSectionHelper(extranetField, sectionSettings);
	  }
	  return sectionSettings;
	}

	var _templateObject$3;
	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }
	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateFieldInitSpec$5(obj, privateMap, value) { _checkPrivateRedeclaration$6(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$6(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _buttonBarElement = /*#__PURE__*/new WeakMap();
	var _buttons = /*#__PURE__*/new WeakMap();
	var ButtonBar = /*#__PURE__*/function () {
	  function ButtonBar() {
	    var buttons = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
	    babelHelpers.classCallCheck(this, ButtonBar);
	    _classPrivateFieldInitSpec$5(this, _buttonBarElement, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$5(this, _buttons, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _buttons, buttons);
	  }
	  babelHelpers.createClass(ButtonBar, [{
	    key: "render",
	    value: function render() {
	      if (babelHelpers.classPrivateFieldGet(this, _buttonBarElement)) {
	        return babelHelpers.classPrivateFieldGet(this, _buttonBarElement);
	      }
	      babelHelpers.classPrivateFieldSet(this, _buttonBarElement, main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["<div class=\"intranet-settings__button_bar\"></div>"]))));
	      var _iterator = _createForOfIteratorHelper$1(babelHelpers.classPrivateFieldGet(this, _buttons)),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var button = _step.value;
	          main_core.Dom.append(button.getContainer(), babelHelpers.classPrivateFieldGet(this, _buttonBarElement));
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return babelHelpers.classPrivateFieldGet(this, _buttonBarElement);
	    }
	  }, {
	    key: "getButtons",
	    value: function getButtons() {
	      return babelHelpers.classPrivateFieldGet(this, _buttons);
	    }
	  }, {
	    key: "addButton",
	    value: function addButton(button) {
	      babelHelpers.classPrivateFieldGet(this, _buttons).push(button);
	      main_core.Dom.append(button.getContainer(), this.render());
	    }
	  }]);
	  return ButtonBar;
	}();

	function _classPrivateFieldInitSpec$6(obj, privateMap, value) { _checkPrivateRedeclaration$7(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$7(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _button = /*#__PURE__*/new WeakMap();
	var LandingButton = /*#__PURE__*/function () {
	  function LandingButton() {
	    babelHelpers.classCallCheck(this, LandingButton);
	    _classPrivateFieldInitSpec$6(this, _button, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _button, new ui_buttons.Button({
	      className: 'landing-button-trigger',
	      round: true,
	      noCaps: true,
	      size: BX.UI.Button.Size.MEDIUM,
	      color: BX.UI.Button.Color.LIGHT_BORDER
	    }));
	  }
	  babelHelpers.createClass(LandingButton, [{
	    key: "setState",
	    value: function setState(state) {
	      state.apply(babelHelpers.classPrivateFieldGet(this, _button));
	    }
	  }, {
	    key: "getButton",
	    value: function getButton() {
	      return babelHelpers.classPrivateFieldGet(this, _button);
	    }
	  }]);
	  return LandingButton;
	}();
	var LandingButtonState = /*#__PURE__*/function () {
	  function LandingButtonState() {
	    babelHelpers.classCallCheck(this, LandingButtonState);
	  }
	  babelHelpers.createClass(LandingButtonState, [{
	    key: "apply",
	    value: function apply(button) {}
	  }]);
	  return LandingButtonState;
	}();
	var _landing = /*#__PURE__*/new WeakMap();
	var EditState = /*#__PURE__*/function (_LandingButtonState) {
	  babelHelpers.inherits(EditState, _LandingButtonState);
	  function EditState(landing) {
	    var _this;
	    babelHelpers.classCallCheck(this, EditState);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EditState).call(this));
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _landing, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _landing, landing);
	    return _this;
	  }
	  babelHelpers.createClass(EditState, [{
	    key: "apply",
	    value: function apply(button) {
	      var _this2 = this;
	      button.setText(main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_EDIT'));
	      button.setDropdown(false);
	      button.bindEvent('click', function () {
	        _this2.openNewTab(babelHelpers.classPrivateFieldGet(_this2, _landing).edit_url);
	      });
	    }
	  }, {
	    key: "openNewTab",
	    value: function openNewTab(url) {
	      window.open(url, '_blank').focus();
	    }
	  }]);
	  return EditState;
	}(LandingButtonState);
	var _landing2 = /*#__PURE__*/new WeakMap();
	var _menuRenderer = /*#__PURE__*/new WeakMap();
	var ShowState = /*#__PURE__*/function (_LandingButtonState2) {
	  babelHelpers.inherits(ShowState, _LandingButtonState2);
	  function ShowState(landing, menuRenderer) {
	    var _this3;
	    babelHelpers.classCallCheck(this, ShowState);
	    _this3 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ShowState).call(this));
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this3), _landing2, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this3), _menuRenderer, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this3), _landing2, landing);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this3), _menuRenderer, menuRenderer);
	    return _this3;
	  }
	  babelHelpers.createClass(ShowState, [{
	    key: "apply",
	    value: function apply(button) {
	      button.setText(main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_SITE'));
	      button.setDropdown(true);
	      button.unbindEvent('click');
	      button.setColor(BX.UI.Button.Color.PRIMARY);
	      button.setMenu(babelHelpers.classPrivateFieldGet(this, _menuRenderer).call(this, babelHelpers.classPrivateFieldGet(this, _landing2)));
	    }
	  }]);
	  return ShowState;
	}(LandingButtonState);
	var _requestBuilder = /*#__PURE__*/new WeakMap();
	var _menuRenderer2 = /*#__PURE__*/new WeakMap();
	var CreateState = /*#__PURE__*/function (_LandingButtonState3) {
	  babelHelpers.inherits(CreateState, _LandingButtonState3);
	  function CreateState(request, menuRenderer) {
	    var _this4;
	    babelHelpers.classCallCheck(this, CreateState);
	    _this4 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CreateState).call(this));
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this4), _requestBuilder, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this4), _menuRenderer2, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this4), _requestBuilder, request);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this4), _menuRenderer2, menuRenderer);
	    return _this4;
	  }
	  babelHelpers.createClass(CreateState, [{
	    key: "apply",
	    value: function apply(button) {
	      var _this5 = this;
	      button.setText(main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_CREATE'));
	      button.setDropdown(false);
	      button.bindEvent('click', function (event) {
	        if (button.getState() === ui_buttons.ButtonState.WAITING) {
	          return;
	        }
	        button.setState(ui_buttons.ButtonState.WAITING);
	        babelHelpers.classPrivateFieldGet(_this5, _requestBuilder).call(_this5).then(function (response) {
	          var landing = response.data;
	          button.setState(null);
	          button.unbindEvent('click');
	          if (landing.is_public) {
	            new ShowState(landing, babelHelpers.classPrivateFieldGet(_this5, _menuRenderer2)).apply(button);
	          } else {
	            var state = new EditState(landing);
	            state.apply(button);
	            state.openNewTab(landing.edit_url);
	          }
	        }, function (response) {
	          button.setState(null);
	          ui_formElements_field.ErrorCollection.showSystemError(response.errors[0].message);
	        });
	      });
	    }
	  }]);
	  return CreateState;
	}(LandingButtonState);

	var _templateObject$4, _templateObject2;
	function _classPrivateFieldInitSpec$7(obj, privateMap, value) { _checkPrivateRedeclaration$8(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$8(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _landing$1 = /*#__PURE__*/new WeakMap();
	var _copyBtn = /*#__PURE__*/new WeakMap();
	var _landingCardElement = /*#__PURE__*/new WeakMap();
	var LandingCard = /*#__PURE__*/function () {
	  function LandingCard(landingOptions) {
	    babelHelpers.classCallCheck(this, LandingCard);
	    _classPrivateFieldInitSpec$7(this, _landing$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$7(this, _copyBtn, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$7(this, _landingCardElement, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _landing$1, landingOptions);
	  }
	  babelHelpers.createClass(LandingCard, [{
	    key: "qrRender",
	    value: function qrRender() {
	      var qrContainer = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["<div class=\"intranet-settings__qr_image-container\"></div>"])));
	      new QRCode(qrContainer, {
	        text: babelHelpers.classPrivateFieldGet(this, _landing$1).public_url,
	        width: 106,
	        height: 106
	      });
	      return qrContainer;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this = this;
	      if (babelHelpers.classPrivateFieldGet(this, _landingCardElement)) {
	        return babelHelpers.classPrivateFieldGet(this, _landingCardElement);
	      }
	      var onclickOpenEdit = function onclickOpenEdit() {
	        window.open(babelHelpers.classPrivateFieldGet(_this, _landing$1).edit_url, '_blank').focus();
	      };
	      babelHelpers.classPrivateFieldSet(this, _landingCardElement, main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"intranet-settings__req-info-container\">\n\t\t\t<div class=\"intranet-settings__req-info-inner\">\n\t\t\t\t<div class=\"intranet-settings__qr_container\">", "</div>\n\t\t\t\t<div class=\"intranet-settings__qr_description-block\">\n\t\t\t\t\t<div class=\"intranet-settings__qr_help-text\">\n\t\t\t\t\t\t<h4 class=\"intranet-settings__qr_title\">", "</h4>\n\t\t\t\t\t\t<p class=\"intranet-settings__qr_text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</p>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"intranet-settings__qr_button\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"intranet-settings__qr_editor_box\" onclick=\"", "\">\n\t\t\t\t<div class=\"intranet-settings__qr_editor_icon\">\n\t\t\t\t\t<div class=\"ui-icon-set --paint-1\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"intranet-settings__qr_editor_name\">", "</div>\n\t\t\t\t<div class=\"ui-icon-set --expand intranet-settings__qr_editor_btn\"></div>\n\t\t\t</div>\n\t\t</div>"])), this.qrRender(), main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_SITE'), main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_HELP_TEXT', {
	        '#SITE_URL#': babelHelpers.classPrivateFieldGet(this, _landing$1).public_url
	      }), this.getCopyButton().getContainer(), onclickOpenEdit, main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_EDIT_LANDING')));
	      return babelHelpers.classPrivateFieldGet(this, _landingCardElement);
	    }
	  }, {
	    key: "getCopyButton",
	    value: function getCopyButton() {
	      var _this2 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _copyBtn)) {
	        return babelHelpers.classPrivateFieldGet(this, _copyBtn);
	      }
	      babelHelpers.classPrivateFieldSet(this, _copyBtn, new ui_buttons.Button({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_COPY_LINK'),
	        round: true,
	        noCaps: true,
	        className: 'landing-copy-button',
	        size: BX.UI.Button.Size.EXTRA_SMALL,
	        color: BX.UI.Button.Color.SUCCESS,
	        events: {
	          click: function click() {
	            if (BX.clipboard.copy(babelHelpers.classPrivateFieldGet(_this2, _landing$1).public_url)) {
	              top.BX.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_LINK_WAS_COPIED'),
	                autoHide: true
	              });
	            }
	          }
	        }
	      }));
	      return babelHelpers.classPrivateFieldGet(this, _copyBtn);
	    }
	  }]);
	  return LandingCard;
	}();

	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$9(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$8(obj, privateMap, value) { _checkPrivateRedeclaration$9(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$9(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _options = /*#__PURE__*/new WeakMap();
	var _menuRenderer$1 = /*#__PURE__*/new WeakMap();
	var _defaultMenuRenderer = /*#__PURE__*/new WeakSet();
	var _getRequestCreateLanding = /*#__PURE__*/new WeakSet();
	var LandingButtonFactory = /*#__PURE__*/function () {
	  function LandingButtonFactory(options, _landingData) {
	    babelHelpers.classCallCheck(this, LandingButtonFactory);
	    _classPrivateMethodInitSpec$3(this, _getRequestCreateLanding);
	    _classPrivateMethodInitSpec$3(this, _defaultMenuRenderer);
	    _classPrivateFieldInitSpec$8(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(this, _menuRenderer$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _options, options);
	    this.landingData = _landingData;
	    babelHelpers.classPrivateFieldSet(this, _menuRenderer$1, _classPrivateMethodGet$3(this, _defaultMenuRenderer, _defaultMenuRenderer2));
	  }
	  babelHelpers.createClass(LandingButtonFactory, [{
	    key: "setMenuRenderer",
	    value: function setMenuRenderer(renderer) {
	      babelHelpers.classPrivateFieldSet(this, _menuRenderer$1, renderer);
	    }
	  }, {
	    key: "create",
	    value: function create() {
	      var _this = this;
	      var btn = new LandingButton();
	      var state;
	      if (babelHelpers.classPrivateFieldGet(this, _options).is_connected && !babelHelpers.classPrivateFieldGet(this, _options).is_public) {
	        state = new EditState(babelHelpers.classPrivateFieldGet(this, _options));
	      } else if (babelHelpers.classPrivateFieldGet(this, _options).is_connected && babelHelpers.classPrivateFieldGet(this, _options).is_public) {
	        state = new ShowState(babelHelpers.classPrivateFieldGet(this, _options), babelHelpers.classPrivateFieldGet(this, _menuRenderer$1).bind(this));
	      } else {
	        state = new CreateState(function () {
	          return _classPrivateMethodGet$3(_this, _getRequestCreateLanding, _getRequestCreateLanding2).call(_this);
	        }, babelHelpers.classPrivateFieldGet(this, _menuRenderer$1).bind(this));
	      }
	      btn.setState(state);
	      return btn.getButton();
	    }
	  }]);
	  return LandingButtonFactory;
	}();
	function _defaultMenuRenderer2(landingData) {
	  return {
	    angle: true,
	    maxWidth: 396,
	    closeByEsc: true,
	    className: 'intranet-settings__qr_popup',
	    items: [{
	      html: new LandingCard(landingData).render(),
	      className: 'intranet-settings__qr_popup_item'
	    }]
	  };
	}
	function _getRequestCreateLanding2() {
	  return main_core.ajax.runComponentAction('bitrix:intranet.settings', 'getLanding', {
	    mode: 'class',
	    data: {
	      companyId: this.landingData.company_id,
	      requisiteId: this.landingData.requisite_id,
	      bankRequisiteId: this.landingData.bank_requisite_id
	    }
	  });
	}

	var _templateObject$5, _templateObject2$1, _templateObject3;
	function _createForOfIteratorHelper$2(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$2(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$2(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$2(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$2(o, minLen); }
	function _arrayLikeToArray$2(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec$4(obj, privateSet) { _checkPrivateRedeclaration$a(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$9(obj, privateMap, value) { _checkPrivateRedeclaration$a(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$a(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _options$1 = /*#__PURE__*/new WeakMap();
	var _cardElement = /*#__PURE__*/new WeakMap();
	var _requisiteFieldsElement = /*#__PURE__*/new WeakMap();
	var _buttonBar = /*#__PURE__*/new WeakMap();
	var _buildCompanyField = /*#__PURE__*/new WeakSet();
	var _buildField = /*#__PURE__*/new WeakSet();
	var Card = /*#__PURE__*/function () {
	  function Card(options) {
	    babelHelpers.classCallCheck(this, Card);
	    _classPrivateMethodInitSpec$4(this, _buildField);
	    _classPrivateMethodInitSpec$4(this, _buildCompanyField);
	    _classPrivateFieldInitSpec$9(this, _options$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _cardElement, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _requisiteFieldsElement, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _buttonBar, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _options$1, options);
	  }
	  babelHelpers.createClass(Card, [{
	    key: "render",
	    value: function render() {
	      if (babelHelpers.classPrivateFieldGet(this, _cardElement)) {
	        return babelHelpers.classPrivateFieldGet(this, _cardElement);
	      }
	      babelHelpers.classPrivateFieldSet(this, _cardElement, main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"intranet-settings__req_background\">\n\t\t\t<div class=\"intranet-settings__req-card_wrapper\">\n\t\t\t\t<div class=\"intranet-settings__header\">\n\t\t\t\t\t<div class=\"intranet-settings__title\"> <span class=\"ui-section__title-icon ui-icon-set --city\"></span> <span>", "</span></div>\n\t\t\t\t\t<div class=\"intranet-settings__contact_bar\"> \n\t\t\t\t\t\t<span class=\"intranet-settings__contact_bar_item\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span> \n\t\t\t\t\t\t<span class=\"intranet-settings__contact_bar_item\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span> \n\t\t\t\t\t\t<span class=\"intranet-settings__contact_bar_item\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span> \n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _options$1).company.TITLE, _classPrivateMethodGet$4(this, _buildCompanyField, _buildCompanyField2).call(this, main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _options$1).phone) ? babelHelpers.classPrivateFieldGet(this, _options$1).phone : main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_PHONE')), _classPrivateMethodGet$4(this, _buildCompanyField, _buildCompanyField2).call(this, main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _options$1).email) ? babelHelpers.classPrivateFieldGet(this, _options$1).email : main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_EMAIL')), _classPrivateMethodGet$4(this, _buildCompanyField, _buildCompanyField2).call(this, main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _options$1).site) ? babelHelpers.classPrivateFieldGet(this, _options$1).site : main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_SITE')), this.requisiteFieldsRender(), this.getButtonsBar().render()));
	      return babelHelpers.classPrivateFieldGet(this, _cardElement);
	    }
	  }, {
	    key: "getRequisiteUrl",
	    value: function getRequisiteUrl() {
	      var requisiteId = babelHelpers.classPrivateFieldGet(this, _options$1).landingData.requisite_id;
	      if (requisiteId) {
	        return '/crm/company/requisite/' + requisiteId + '/';
	      } else {
	        return '/crm/company/requisite/0/?itemId=' + babelHelpers.classPrivateFieldGet(this, _options$1).company.ID;
	      }
	    }
	  }, {
	    key: "getCompanyUrl",
	    value: function getCompanyUrl() {
	      if (babelHelpers.classPrivateFieldGet(this, _options$1).company.ID === 0) {
	        return '/crm/company/details/0/?mycompany=y&TITLE=' + babelHelpers.classPrivateFieldGet(this, _options$1).company.TITLE;
	      } else {
	        return '/crm/company/details/' + babelHelpers.classPrivateFieldGet(this, _options$1).company.ID + '/';
	      }
	    }
	  }, {
	    key: "requisiteFieldsRender",
	    value: function requisiteFieldsRender() {
	      if (babelHelpers.classPrivateFieldGet(this, _requisiteFieldsElement)) {
	        return babelHelpers.classPrivateFieldGet(this, _requisiteFieldsElement);
	      }
	      var fields = babelHelpers.classPrivateFieldGet(this, _options$1).fields;
	      babelHelpers.classPrivateFieldSet(this, _requisiteFieldsElement, main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"intranet-settings__req-table_wrap\"></div>"]))));
	      var _iterator = _createForOfIteratorHelper$2(fields),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var field = _step.value;
	          var renderField = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"intranet-settings__req-table_row\">\n\t\t\t\t\t<div class=\"intranet-settings__table-cell\">", "</div>\n\t\t\t\t\t<div class=\"intranet-settings__table-cell\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), field.TITLE, !babelHelpers.classPrivateFieldGet(this, _options$1).company.ID ? _classPrivateMethodGet$4(this, _buildCompanyField, _buildCompanyField2).call(this, main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB')) : main_core.Type.isStringFilled(field.VALUE) ? _classPrivateMethodGet$4(this, _buildField, _buildField2).call(this, field.VALUE) : _classPrivateMethodGet$4(this, _buildField, _buildField2).call(this, main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB')));
	          main_core.Dom.append(renderField, babelHelpers.classPrivateFieldGet(this, _requisiteFieldsElement));
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return babelHelpers.classPrivateFieldGet(this, _requisiteFieldsElement);
	    }
	  }, {
	    key: "getButtonsBar",
	    value: function getButtonsBar() {
	      if (babelHelpers.classPrivateFieldGet(this, _buttonBar)) {
	        return babelHelpers.classPrivateFieldGet(this, _buttonBar);
	      }
	      babelHelpers.classPrivateFieldSet(this, _buttonBar, new ButtonBar());
	      return babelHelpers.classPrivateFieldGet(this, _buttonBar);
	    }
	  }, {
	    key: "setButtonBar",
	    value: function setButtonBar(buttonBar) {
	      babelHelpers.classPrivateFieldSet(this, _buttonBar, buttonBar);
	    }
	  }]);
	  return Card;
	}();
	function _buildCompanyField2(label) {
	  return main_core.Dom.create('a', {
	    text: label,
	    attrs: {
	      href: this.getCompanyUrl()
	    }
	  });
	}
	function _buildField2(label) {
	  return main_core.Dom.create('a', {
	    text: label,
	    attrs: {
	      href: this.getRequisiteUrl()
	    }
	  });
	}

	var _templateObject$6, _templateObject2$2;
	function _createForOfIteratorHelper$3(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$3(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$3(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$3(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$3(o, minLen); }
	function _arrayLikeToArray$3(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var RequisitePage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(RequisitePage, _BaseSettingsPage);
	  function RequisitePage() {
	    var _this;
	    babelHelpers.classCallCheck(this, RequisitePage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(RequisitePage).call(this));
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_REQUISITE');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_REQUISITE');
	    top.BX.addCustomEvent('onLocalStorageSet', function (params) {
	      var _params$key;
	      var eventName = (_params$key = params === null || params === void 0 ? void 0 : params.key) !== null && _params$key !== void 0 ? _params$key : null;
	      if (eventName === 'onCrmEntityUpdate' || eventName === 'onCrmEntityCreate' || eventName === 'BX.Crm.RequisiteSliderDetails:onSave') {
	        _this.reload();
	      }
	    });
	    return _this;
	  }
	  babelHelpers.createClass(RequisitePage, [{
	    key: "getType",
	    value: function getType() {
	      return 'requisite';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      if (!this.hasValue('sectionRequisite')) {
	        return;
	      }
	      var reqSection = new ui_section.Section(this.getValue('sectionRequisite'));
	      var sectionField = new ui_formElements_field.SettingsSection({
	        parent: this,
	        section: reqSection
	      });
	      var description = new BX.UI.Alert({
	        text: "\n\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_REQUISITE_DESCRIPTION'), "\n\t\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=18213326')\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t\t</a>\n\t\t\t"),
	        inline: true,
	        size: BX.UI.Alert.Size.SMALL,
	        color: BX.UI.Alert.Color.PRIMARY,
	        animated: true
	      });
	      var descriptionRow = new ui_section.Row({
	        content: description.getContainer()
	      });
	      reqSection.append(descriptionRow.render());
	      if (this.hasValue('COMPANY')) {
	        var companies = this.getValue('COMPANY');
	        var requisites = this.getValue('REQUISITES');
	        var phones = this.getValue('PHONES');
	        var sites = this.getValue('SITES');
	        var emails = this.getValue('EMAILS');
	        var landings = this.getValue('LANDINGS');
	        var landingsData = this.getValue('LANDINGS_DATA');
	        if (!main_core.Type.isArray(companies) || companies.length <= 0) {
	          var defaultCompanyRow = new ui_section.Row({
	            content: this.cardRender({
	              company: {
	                ID: 0,
	                TITLE: this.getValue('BITRIX_TITLE')
	              },
	              fields: this.getValue('EMPTY_REQUISITE'),
	              phone: [],
	              email: [],
	              site: []
	            })
	          });
	          reqSection.append(defaultCompanyRow.render());
	        }
	        var _iterator = _createForOfIteratorHelper$3(companies),
	          _step;
	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var company = _step.value;
	            var fields = !main_core.Type.isNil(requisites[company.ID]) ? requisites[company.ID] : this.getValue('EMPTY_REQUISITE');
	            var cardRow = new ui_section.Row({
	              content: this.cardRender({
	                company: company,
	                fields: fields,
	                phone: !main_core.Type.isNil(phones[company.ID]) ? phones[company.ID] : [],
	                email: !main_core.Type.isNil(emails[company.ID]) ? emails[company.ID] : [],
	                site: !main_core.Type.isNil(sites[company.ID]) ? sites[company.ID] : [],
	                landing: !main_core.Type.isNil(landings[company.ID]) ? landings[company.ID] : [],
	                landingData: !main_core.Type.isNil(landingsData[company.ID]) ? landingsData[company.ID] : []
	              })
	            });
	            reqSection.append(cardRow.render());
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	      }
	      new ui_formElements_field.SettingsRow({
	        row: new ui_section.Row({
	          content: this.addCompanyLinkRender()
	        }),
	        parent: sectionField
	      });
	      sectionField.renderTo(contentNode);
	    }
	  }, {
	    key: "addCompanyLinkRender",
	    value: function addCompanyLinkRender() {
	      var _this2 = this;
	      var link = main_core.Tag.render(_templateObject$6 || (_templateObject$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"ui-section__link\" \n\t\t\t\t\thref=\"/crm/company/details/0/?mycompany=y\" target=\"_blank\">\n\t\t\t\t", "\n\t\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_ADD_COMPANY'));
	      main_core.Event.bind(link, 'click', function (event) {
	        var _this2$getAnalytic;
	        (_this2$getAnalytic = _this2.getAnalytic()) === null || _this2$getAnalytic === void 0 ? void 0 : _this2$getAnalytic.addEventConfigRequisite(AnalyticSettingsEvent.OPEN_ADD_COMPANY);
	      });
	      return main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-section__link_box\">", "</div>"])), link);
	    }
	  }, {
	    key: "cardRender",
	    value: function cardRender(params) {
	      var _this3 = this;
	      var card = new Card(params);
	      var buttonBar = new ButtonBar();
	      if (params.company.ID > 0) {
	        var factory = new LandingButtonFactory(params.landing, params.landingData);
	        factory.setMenuRenderer(function (landingData) {
	          var landingCard = new LandingCard(landingData);
	          main_core.Event.bind(landingCard.getCopyButton().getContainer(), 'click', function (event) {
	            var _this3$getAnalytic;
	            (_this3$getAnalytic = _this3.getAnalytic()) === null || _this3$getAnalytic === void 0 ? void 0 : _this3$getAnalytic.addEventConfigRequisite(AnalyticSettingsEvent.COPY_LINK_CARD);
	          });
	          return {
	            angle: true,
	            maxWidth: 396,
	            closeByEsc: true,
	            className: 'intranet-settings__qr_popup',
	            items: [{
	              html: landingCard.render(),
	              className: 'intranet-settings__qr_popup_item'
	            }]
	          };
	        });
	        var landingBtn = factory.create();
	        if (main_core.Dom.hasClass(landingBtn.getContainer(), 'landing-button-trigger')) {
	          main_core.Event.bind(landingBtn.getContainer(), 'click', function (event) {
	            if (params.landing.is_connected && !params.landing.is_public) {
	              var _this3$getAnalytic2;
	              (_this3$getAnalytic2 = _this3.getAnalytic()) === null || _this3$getAnalytic2 === void 0 ? void 0 : _this3$getAnalytic2.addEventConfigRequisite(AnalyticSettingsEvent.EDIT_CARD);
	            } else if (!params.landing.is_connected && !params.landing.is_public) {
	              var _this3$getAnalytic3;
	              (_this3$getAnalytic3 = _this3.getAnalytic()) === null || _this3$getAnalytic3 === void 0 ? void 0 : _this3$getAnalytic3.addEventConfigRequisite(AnalyticSettingsEvent.CREATE_CARD);
	            }
	          });
	        }
	        buttonBar.addButton(landingBtn);
	      }
	      card.setButtonBar(buttonBar);
	      return card.render();
	    }
	  }]);
	  return RequisitePage;
	}(ui_formElements_field.BaseSettingsPage);

	var _templateObject$7, _templateObject2$3, _templateObject3$1, _templateObject4;
	function _classPrivateMethodInitSpec$5(obj, privateSet) { _checkPrivateRedeclaration$b(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$b(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _buildNewsFeedSection = /*#__PURE__*/new WeakSet();
	var _buildChatSection = /*#__PURE__*/new WeakSet();
	var _buildChannelSection = /*#__PURE__*/new WeakSet();
	var _buildDiskSection = /*#__PURE__*/new WeakSet();
	var CommunicationPage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(CommunicationPage, _BaseSettingsPage);
	  function CommunicationPage() {
	    var _this;
	    babelHelpers.classCallCheck(this, CommunicationPage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CommunicationPage).call(this));
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _buildDiskSection);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _buildChannelSection);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _buildChatSection);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _buildNewsFeedSection);
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_COMMUNICATION');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_COMMUNICATION');
	    return _this;
	  }
	  babelHelpers.createClass(CommunicationPage, [{
	    key: "getType",
	    value: function getType() {
	      return 'communication';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var profileSection = _classPrivateMethodGet$5(this, _buildNewsFeedSection, _buildNewsFeedSection2).call(this);
	      profileSection.renderTo(contentNode);
	      var chatSection = _classPrivateMethodGet$5(this, _buildChatSection, _buildChatSection2).call(this);
	      chatSection.renderTo(contentNode);
	      if (this.hasValue('availableGeneralChannel')) {
	        var channelSection = _classPrivateMethodGet$5(this, _buildChannelSection, _buildChannelSection2).call(this);
	        channelSection.renderTo(contentNode);
	      }
	      var diskSection = _classPrivateMethodGet$5(this, _buildDiskSection, _buildDiskSection2).call(this);
	      diskSection.renderTo(contentNode);
	    }
	  }]);
	  return CommunicationPage;
	}(ui_formElements_field.BaseSettingsPage);
	function _buildNewsFeedSection2() {
	  if (!this.hasValue('sectionFeed')) {
	    return;
	  }
	  var newsFeedSection = new ui_section.Section(this.getValue('sectionFeed'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: newsFeedSection,
	    parent: this
	  });
	  if (this.hasValue('allow_livefeed_toall')) {
	    var allowPostFeedField = new ui_formElements_view.Checker(this.getValue('allow_livefeed_toall'));
	    allowPostFeedField.hideSeparator = true;
	    var settingsField = new ui_formElements_field.SettingsField({
	      fieldView: allowPostFeedField
	    });
	    var settingsRow = new ui_formElements_field.SettingsRow({
	      parent: settingsSection,
	      child: settingsField
	    });
	    var userSelectorField = new ui_formElements_view.UserSelector({
	      inputName: 'livefeed_toall_rights[]',
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS'),
	      values: Object.values(this.getValue('arToAllRights')),
	      enableDepartments: true,
	      encodeValue: function encodeValue(value) {
	        if (!main_core.Type.isNil(value.id)) {
	          return value.id === 'all-users' ? 'AU' : value.type + value.id.toString().split(':')[0];
	        }
	        return null;
	      },
	      decodeValue: function decodeValue(value) {
	        if (value === 'UA') {
	          return {
	            type: 'AU',
	            id: ''
	          };
	        }
	        var arr = value.match(/^(U|DR|D)(\d+)/);
	        if (!main_core.Type.isArray(arr)) {
	          return {
	            type: null,
	            id: null
	          };
	        }
	        return {
	          type: arr[1],
	          id: arr[2]
	        };
	      }
	    });
	    settingsField = new ui_formElements_field.SettingsField({
	      fieldView: userSelectorField
	    });
	    var userSelectorRow = new ui_section.Row({
	      isHidden: !allowPostFeedField.isChecked(),
	      className: 'ui-section__subrow'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: userSelectorRow,
	      parent: settingsRow,
	      child: settingsField
	    });
	    main_core_events.EventEmitter.subscribe(allowPostFeedField.switcher, 'toggled', function () {
	      if (allowPostFeedField.isChecked()) {
	        userSelectorRow.show();
	      } else {
	        userSelectorRow.hide();
	      }
	    });
	  }
	  if (this.hasValue('default_livefeed_toall')) {
	    var allowPostToAllField = new ui_formElements_view.Checker(this.getValue('default_livefeed_toall'));
	    CommunicationPage.addToSectionHelper(allowPostToAllField, settingsSection);
	  }
	  if (this.hasValue('ratingTextLikeY')) {
	    var _this$getValue, _this$getValue$label, _this$getValue2;
	    var likeBtnNameField = new ui_formElements_view.TextInputInline({
	      inputName: (_this$getValue = this.getValue('ratingTextLikeY')) === null || _this$getValue === void 0 ? void 0 : _this$getValue.name,
	      label: (_this$getValue$label = this.getValue('ratingTextLikeY').label) !== null && _this$getValue$label !== void 0 ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_LIKE_INPUT'),
	      hintTitle: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_LIKE'),
	      value: (_this$getValue2 = this.getValue('ratingTextLikeY')) === null || _this$getValue2 === void 0 ? void 0 : _this$getValue2.current,
	      valueColor: this.hasValue('ratingTextLikeY'),
	      hintDesc: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_DESC_LIKE')
	    });
	    CommunicationPage.addToSectionHelper(likeBtnNameField, settingsSection);
	  }
	  return settingsSection;
	}
	function _buildChatSection2() {
	  if (!this.hasValue('sectionChats')) {
	    return;
	  }
	  var chatSection = new ui_section.Section(this.getValue('sectionChats'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: chatSection,
	    parent: this
	  });
	  if (this.hasValue('general_chat_can_post')) {
	    var _this$getValue3;
	    var canPostGeneralChatField = new ui_formElements_view.Checker(this.getValue('allow_post_general_chat'));
	    var settingsField = new ui_formElements_field.SettingsField({
	      fieldView: canPostGeneralChatField
	    });
	    var settingsRow = new ui_formElements_field.SettingsRow({
	      parent: settingsSection,
	      child: settingsField
	    });
	    var canPostGeneralChatListField = new ui_formElements_view.Selector(this.getValue('general_chat_can_post'));
	    settingsField = new ui_formElements_field.SettingsField({
	      fieldView: canPostGeneralChatListField
	    });
	    var canPostGeneralChatListRow = new ui_section.Row({
	      isHidden: !canPostGeneralChatField.isChecked(),
	      className: 'ui-section__subrow --no-border'
	    });
	    CommunicationPage.addToSectionHelper(canPostGeneralChatListField, settingsRow, canPostGeneralChatListRow);
	    var managerSelectorField = new ui_formElements_view.UserSelector({
	      inputName: 'imchat_toall_rights[]',
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS'),
	      enableAll: false,
	      values: Object.values((_this$getValue3 = this.getValue('generalChatManagersList')) !== null && _this$getValue3 !== void 0 ? _this$getValue3 : []),
	      encodeValue: function encodeValue(value) {
	        if (!main_core.Type.isNil(value.id)) {
	          return value.id === 'all-users' ? 'AU' : 'U' + value.id;
	        }
	        return null;
	      },
	      decodeValue: function decodeValue(value) {
	        if (value === 'UA') {
	          return {
	            type: 'AU',
	            id: ''
	          };
	        }
	        var arr = value.match(/^(U)(\d+)/);
	        if (!main_core.Type.isArray(arr)) {
	          return {
	            type: null,
	            id: null
	          };
	        }
	        return {
	          type: arr[1],
	          id: arr[2]
	        };
	      }
	    });
	    var managerSelectorRow = new ui_section.Row({
	      content: managerSelectorField.render(),
	      isHidden: this.getValue('general_chat_can_post').current !== 'MANAGER',
	      className: 'ui-section__subrow --no-border'
	    });
	    CommunicationPage.addToSectionHelper(managerSelectorField, settingsRow, managerSelectorRow);
	    var separatorRow = new ui_section.SeparatorRow({
	      isHidden: this.getValue('general_chat_can_post').current !== 'MANAGER'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: separatorRow,
	      parent: settingsRow
	    });
	    main_core_events.EventEmitter.subscribe(canPostGeneralChatField.switcher, 'toggled', function () {
	      if (canPostGeneralChatField.isChecked()) {
	        canPostGeneralChatListRow.show();
	        if (canPostGeneralChatListField.getInputNode().value === 'MANAGER') {
	          managerSelectorRow.show();
	        }
	        separatorRow.show();
	      } else {
	        canPostGeneralChatListRow.hide();
	        managerSelectorRow.hide();
	        separatorRow.hide();
	      }
	    });
	    canPostGeneralChatListField.getInputNode().addEventListener('change', function (event) {
	      if (event.target.value === 'MANAGER') {
	        managerSelectorRow.show();
	      } else {
	        managerSelectorRow.hide();
	      }
	    });
	  }
	  if (this.hasValue('general_chat_message_leave')) {
	    var leaveMessageField = new ui_formElements_view.Checker(this.getValue('general_chat_message_leave'));
	    CommunicationPage.addToSectionHelper(leaveMessageField, settingsSection);
	  }
	  if (this.hasValue('general_chat_message_admin_rights')) {
	    var adminMessageField = new ui_formElements_view.Checker(this.getValue('general_chat_message_admin_rights'));
	    CommunicationPage.addToSectionHelper(adminMessageField, settingsSection);
	  }
	  if (this.hasValue('url_preview_enable')) {
	    var allowUrlPreviewField = new ui_formElements_view.Checker(this.getValue('url_preview_enable'));
	    CommunicationPage.addToSectionHelper(allowUrlPreviewField, settingsSection);
	  }
	  if (this.hasValue('create_overdue_chats')) {
	    var overdueChatsField = new ui_formElements_view.Checker(this.getValue('create_overdue_chats'));
	    CommunicationPage.addToSectionHelper(overdueChatsField, settingsSection);
	  }
	  return settingsSection;
	}
	function _buildChannelSection2() {
	  if (!this.hasValue('sectionChannels')) {
	    return;
	  }
	  var chatSection = new ui_section.Section(this.getValue('sectionChannels'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: chatSection,
	    parent: this
	  });
	  if (this.hasValue('general_channel_can_post')) {
	    var _Loc$getMessage, _this$getValue4;
	    var canPostGeneralChannelField = new ui_formElements_view.Checker(this.getValue('allow_post_general_channel'));
	    var settingsField = new ui_formElements_field.SettingsField({
	      fieldView: canPostGeneralChannelField
	    });
	    var settingsRow = new ui_formElements_field.SettingsRow({
	      parent: settingsSection,
	      child: settingsField
	    });
	    var canPostGeneralChannelListField = new ui_formElements_view.Selector(this.getValue('general_channel_can_post'));
	    settingsField = new ui_formElements_field.SettingsField({
	      fieldView: canPostGeneralChannelListField
	    });
	    var canPostGeneralChannelListRow = new ui_section.Row({
	      isHidden: !canPostGeneralChannelField.isChecked(),
	      className: 'ui-section__subrow --no-border'
	    });
	    CommunicationPage.addToSectionHelper(canPostGeneralChannelListField, settingsRow, canPostGeneralChannelListRow);
	    var managerSelectorField = new ui_formElements_view.UserSelector({
	      inputName: 'imchannel_toall_rights[]',
	      label: (_Loc$getMessage = main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS_CHANNEL')) !== null && _Loc$getMessage !== void 0 ? _Loc$getMessage : '',
	      enableAll: false,
	      values: Object.values((_this$getValue4 = this.getValue('generalChannelManagersList')) !== null && _this$getValue4 !== void 0 ? _this$getValue4 : []),
	      encodeValue: function encodeValue(value) {
	        if (!main_core.Type.isNil(value.id)) {
	          return value.id === 'all-users' ? 'AU' : 'U' + value.id;
	        }
	        return null;
	      },
	      decodeValue: function decodeValue(value) {
	        if (value === 'UA') {
	          return {
	            type: 'AU',
	            id: ''
	          };
	        }
	        var arr = value.match(/^(U)(\d+)/);
	        if (!main_core.Type.isArray(arr)) {
	          return {
	            type: null,
	            id: null
	          };
	        }
	        return {
	          type: arr[1],
	          id: arr[2]
	        };
	      }
	    });
	    var managerSelectorRow = new ui_section.Row({
	      content: managerSelectorField.render(),
	      isHidden: this.getValue('general_channel_can_post').current !== 'MANAGER',
	      className: 'ui-section__subrow --no-border'
	    });
	    CommunicationPage.addToSectionHelper(managerSelectorField, settingsRow, managerSelectorRow);
	    var separatorRow = new ui_section.SeparatorRow({
	      isHidden: this.getValue('general_channel_can_post').current !== 'MANAGER'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: separatorRow,
	      parent: settingsRow
	    });
	    main_core_events.EventEmitter.subscribe(canPostGeneralChannelField.switcher, 'toggled', function () {
	      if (canPostGeneralChannelField.isChecked()) {
	        canPostGeneralChannelListRow.show();
	        if (canPostGeneralChannelListField.getInputNode().value === 'MANAGER') {
	          managerSelectorRow.show();
	        }
	        separatorRow.show();
	      } else {
	        canPostGeneralChannelListRow.hide();
	        managerSelectorRow.hide();
	        separatorRow.hide();
	      }
	    });
	    canPostGeneralChannelListField.getInputNode().addEventListener('change', function (event) {
	      if (event.target.value === 'MANAGER') {
	        managerSelectorRow.show();
	      } else {
	        managerSelectorRow.hide();
	      }
	    });
	  }
	  return settingsSection;
	}
	function _buildDiskSection2() {
	  var _this2 = this;
	  if (!this.hasValue('sectionDisk')) {
	    return;
	  }
	  var diskSection = new ui_section.Section(this.getValue('sectionDisk'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: diskSection,
	    parent: this
	  });
	  if (this.hasValue('DISK_VIEWER_SERVICE')) {
	    var fileViewerField = new ui_formElements_view.Selector(this.getValue('DISK_VIEWER_SERVICE'));
	    CommunicationPage.addToSectionHelper(fileViewerField, settingsSection);
	  }
	  if (this.hasValue('DISK_LIMIT_PER_FILE')) {
	    var _this$getValue$label2;
	    var messageNode = main_core.Tag.render(_templateObject$7 || (_templateObject$7 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE'));
	    var fileLimitField = new ui_formElements_view.Selector({
	      label: (_this$getValue$label2 = this.getValue('DISK_LIMIT_PER_FILE').label) !== null && _this$getValue$label2 !== void 0 ? _this$getValue$label2 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAX_FILE_LIMIT'),
	      hintTitle: this.getValue('DISK_LIMIT_PER_FILE').hintTitle,
	      name: this.getValue('DISK_LIMIT_PER_FILE').name,
	      items: this.getValue('DISK_LIMIT_PER_FILE').values,
	      hints: this.getValue('DISK_LIMIT_PER_FILE').hints,
	      current: this.getValue('DISK_LIMIT_PER_FILE').current,
	      isEnable: this.getValue('DISK_LIMIT_PER_FILE').isEnable,
	      bannerCode: 'limit_max_entries_in_document_history',
	      helpDesk: 'redirect=detail&code=18869612',
	      helpMessageProvider: this.helpMessageProviderFactory(messageNode)
	    });
	    var fileLimitRow = new ui_section.Row({
	      separator: 'bottom',
	      className: '--block'
	    });
	    if (!this.getValue('DISK_LIMIT_PER_FILE').isEnable) {
	      main_core.Event.bind(fileLimitField.getInputNode(), 'click', function () {
	        var _this2$getAnalytic;
	        (_this2$getAnalytic = _this2.getAnalytic()) === null || _this2$getAnalytic === void 0 ? void 0 : _this2$getAnalytic.addEventOpenHint(_this2.getValue('DISK_LIMIT_PER_FILE').name);
	      });
	      main_core.Event.bind(messageNode.querySelector('a'), 'click', function () {
	        var _this2$getAnalytic2;
	        return (_this2$getAnalytic2 = _this2.getAnalytic()) === null || _this2$getAnalytic2 === void 0 ? void 0 : _this2$getAnalytic2.addEventOpenTariffSelector(_this2.getValue('DISK_LIMIT_PER_FILE').name);
	      });
	    }
	    CommunicationPage.addToSectionHelper(fileLimitField, settingsSection, fileLimitRow);
	  }
	  new ui_formElements_field.SettingsRow({
	    row: new ui_section.SeparatorRow(),
	    parent: settingsSection
	  });
	  if (this.hasValue('disk_allow_edit_object_in_uf')) {
	    var allowEditDocField = new ui_formElements_view.Checker(this.getValue('disk_allow_edit_object_in_uf'));
	    var allowEditDocRow = new ui_section.Row({
	      separator: 'top',
	      className: '--block'
	    });
	    CommunicationPage.addToSectionHelper(allowEditDocField, settingsSection, allowEditDocRow);
	  }
	  if (this.hasValue('disk_allow_autoconnect_shared_objects')) {
	    var connectDiskField = new ui_formElements_view.Checker(this.getValue('disk_allow_autoconnect_shared_objects'));
	    CommunicationPage.addToSectionHelper(connectDiskField, settingsSection);
	  }
	  if (this.hasValue('disk_allow_use_external_link')) {
	    var _this$getValue$label3;
	    var _messageNode = main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE'));
	    var publicLinkField = new ui_formElements_view.Checker({
	      inputName: this.getValue('disk_allow_use_external_link').inputName,
	      title: (_this$getValue$label3 = this.getValue('disk_allow_use_external_link').label) !== null && _this$getValue$label3 !== void 0 ? _this$getValue$label3 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_PUBLIC_LINK'),
	      hintOn: this.getValue('disk_allow_use_external_link').hintOn,
	      checked: this.getValue('disk_allow_use_external_link').checked,
	      isEnable: this.getValue('disk_allow_use_external_link').isEnable,
	      bannerCode: 'limit_admin_share_link',
	      helpDesk: this.getValue('disk_allow_use_external_link').helpDesk,
	      helpMessageProvider: this.helpMessageProviderFactory(_messageNode)
	    });
	    if (!this.getValue('disk_allow_use_external_link').isEnable) {
	      main_core_events.EventEmitter.subscribe(publicLinkField.switcher, 'toggled', function () {
	        var _this2$getAnalytic3;
	        (_this2$getAnalytic3 = _this2.getAnalytic()) === null || _this2$getAnalytic3 === void 0 ? void 0 : _this2$getAnalytic3.addEventOpenHint('disk_allow_use_external_link');
	      });
	      main_core.Event.bind(_messageNode.querySelector('a'), 'click', function () {
	        var _this2$getAnalytic4;
	        return (_this2$getAnalytic4 = _this2.getAnalytic()) === null || _this2$getAnalytic4 === void 0 ? void 0 : _this2$getAnalytic4.addEventOpenTariffSelector('enable_pub_link');
	      });
	    }
	    CommunicationPage.addToSectionHelper(publicLinkField, settingsSection);
	  }
	  if (this.hasValue('disk_object_lock_enabled')) {
	    var _this$getValue$label4;
	    var _messageNode2 = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE'));
	    var enableBlockDocField = new ui_formElements_view.Checker({
	      inputName: this.getValue('disk_object_lock_enabled').inputName,
	      title: (_this$getValue$label4 = this.getValue('disk_object_lock_enabled').label) !== null && _this$getValue$label4 !== void 0 ? _this$getValue$label4 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_BLOCK_DOC'),
	      hintOn: this.getValue('disk_object_lock_enabled').hintOn,
	      checked: this.getValue('disk_object_lock_enabled').checked,
	      isEnable: this.getValue('disk_object_lock_enabled').isEnable,
	      bannerCode: 'limit_document_lock',
	      helpMessageProvider: this.helpMessageProviderFactory(_messageNode2),
	      helpDesk: this.getValue('disk_object_lock_enabled').helpDesk
	    });
	    if (!this.getValue('disk_object_lock_enabled').isEnable) {
	      main_core_events.EventEmitter.subscribe(enableBlockDocField.switcher, 'toggled', function () {
	        var _this2$getAnalytic5;
	        (_this2$getAnalytic5 = _this2.getAnalytic()) === null || _this2$getAnalytic5 === void 0 ? void 0 : _this2$getAnalytic5.addEventOpenHint('disk_object_lock_enabled');
	      });
	      main_core.Event.bind(_messageNode2.querySelector('a'), 'click', function () {
	        var _this2$getAnalytic6;
	        return (_this2$getAnalytic6 = _this2.getAnalytic()) === null || _this2$getAnalytic6 === void 0 ? void 0 : _this2$getAnalytic6.addEventOpenTariffSelector('disk_object_lock_enabled');
	      });
	    }
	    CommunicationPage.addToSectionHelper(enableBlockDocField, settingsSection);
	  }
	  if (this.hasValue('disk_allow_use_extended_fulltext')) {
	    var _this$getValue$label5;
	    var _messageNode3 = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_ENT', {
	      '#TARIFF#': 'ent250'
	    }));
	    var enableFindField = new ui_formElements_view.Checker({
	      inputName: this.getValue('disk_allow_use_extended_fulltext').inputName,
	      title: (_this$getValue$label5 = this.getValue('disk_allow_use_extended_fulltext').label) !== null && _this$getValue$label5 !== void 0 ? _this$getValue$label5 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_SEARCH_DOC'),
	      hintOn: this.getValue('disk_allow_use_extended_fulltext').hintOn,
	      checked: this.getValue('disk_allow_use_extended_fulltext').checked,
	      isEnable: this.getValue('disk_allow_use_extended_fulltext').isEnable,
	      bannerCode: 'limit_in_text_search',
	      helpDesk: this.getValue('disk_allow_use_extended_fulltext').helpDesk,
	      helpMessageProvider: this.helpMessageProviderFactory(_messageNode3)
	    });
	    if (!this.getValue('disk_allow_use_extended_fulltext').isEnable) {
	      main_core_events.EventEmitter.subscribe(enableFindField.switcher, 'toggled', function () {
	        var _this2$getAnalytic7;
	        (_this2$getAnalytic7 = _this2.getAnalytic()) === null || _this2$getAnalytic7 === void 0 ? void 0 : _this2$getAnalytic7.addEventOpenHint('disk_allow_use_extended_fulltext');
	      });
	      main_core.Event.bind(_messageNode3.querySelector('a'), 'click', function () {
	        var _this2$getAnalytic8;
	        return (_this2$getAnalytic8 = _this2.getAnalytic()) === null || _this2$getAnalytic8 === void 0 ? void 0 : _this2$getAnalytic8.addEventOpenTariffSelector('disk_allow_use_extended_fulltext');
	      });
	    }
	    CommunicationPage.addToSectionHelper(enableFindField, settingsSection);
	  }
	  return settingsSection;
	}

	var _templateObject$8, _templateObject2$4, _templateObject3$2;
	function _classPrivateFieldInitSpec$a(obj, privateMap, value) { _checkPrivateRedeclaration$c(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$c(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _content$2 = /*#__PURE__*/new WeakMap();
	var _title$1 = /*#__PURE__*/new WeakMap();
	var SiteDomainField = /*#__PURE__*/function (_SettingsField) {
	  babelHelpers.inherits(SiteDomainField, _SettingsField);
	  function SiteDomainField(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, SiteDomainField);
	    var options = params.siteDomainOptions;
	    params.fieldView = new ui_formElements_view.TextInput({
	      value: options.subDomainName,
	      placeholder: options.subDomainName,
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME3'),
	      id: 'subDomainName',
	      inputName: 'subDomainName',
	      isEnable: true
	    });
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SiteDomainField).call(this, params));
	    _classPrivateFieldInitSpec$a(babelHelpers.assertThisInitialized(_this), _content$2, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(babelHelpers.assertThisInitialized(_this), _title$1, {
	      writable: true,
	      value: void 0
	    });
	    _this.setParentElement(params.parent);
	    _this.getFieldView().setEventNamespace(_this.getEventNamespace());
	    _this.getFieldView().getInputNode().setAttribute('autocomplete', 'off');
	    var timeout = null;
	    main_core.Event.bind(_this.getFieldView().getInputNode(), 'input', function () {
	      clearTimeout(timeout);
	      timeout = setTimeout(function () {
	        _this.validateInput();
	      }, 1000);
	    });
	    _this.options = {
	      hostname: options.hostname,
	      subDomainName: options.subDomainName,
	      mainDomainName: options.mainDomainName,
	      isRenameable: options.isRenameable,
	      occupiedDomains: options.occupiedDomains
	    };
	    _this.options.mainDomainName = ['.', _this.options.mainDomainName].join('').replace('..', '.');
	    return _this;
	  }
	  babelHelpers.createClass(SiteDomainField, [{
	    key: "validateInput",
	    value: function validateInput() {
	      var newDomain = this.getFieldView().getInputNode().value;
	      newDomain = newDomain.trim();
	      if (newDomain.length < 3 || newDomain.length > 60) {
	        this.getFieldView().setErrors([main_core.Loc.getMessage('INTRANET_SETTINGS_DOMAIN_RENAMING_LENGTH_ERROR')]);
	      } else if (!/^([a-zA-Z0-9]([a-zA-Z0-9\\-]{0,58})[a-zA-Z0-9])$/.test(newDomain)) {
	        this.getFieldView().setErrors([main_core.Loc.getMessage('INTRANET_SETTINGS_DOMAIN_RENAMING_FORMAT_ERROR')]);
	      } else if (this.options.occupiedDomains.includes(newDomain)) {
	        this.getFieldView().setErrors([main_core.Loc.getMessage('INTRANET_SETTINGS_DOMAIN_RENAMING_DOMAIN_EXISTS_ERROR')]);
	      } else {
	        this.getFieldView().cleanError();
	      }
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {}
	  }, {
	    key: "render",
	    value: function render() {
	      var _this2 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _content$2)) {
	        return babelHelpers.classPrivateFieldGet(this, _content$2);
	      }
	      if (this.options.isRenameable !== true) {
	        var copyButton = main_core.Tag.render(_templateObject$8 || (_templateObject$8 = babelHelpers.taggedTemplateLiteral(["<div class=\"settings-tools-description-link\">", "</div>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_COPY'));
	        BX.clipboard.bindCopyClick(copyButton, {
	          text: function text() {
	            return _this2.options.hostname;
	          }
	        });
	        babelHelpers.classPrivateFieldSet(this, _content$2, main_core.Tag.render(_templateObject2$4 || (_templateObject2$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div>\n\t\t\t\t\t<div class=\"ui-section__field-label_box\">\n\t\t\t\t\t\t<div class=\"ui-section__field-label\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"intranet-settings__domain_box\">\n\t\t\t\t\t\t<div class=\"intranet-settings__domain_name\">", "</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME4'), main_core.Text.encode(this.options.hostname), copyButton));
	      } else {
	        babelHelpers.classPrivateFieldSet(this, _content$2, main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["<div id=\"", "\" class=\"ui-section__field-selector --no-border\">\n\t\t\t\t<div class=\"ui-section__field-container\">\n\t\t\t\t\t<div class=\"ui-section__field-label_box\">\n\t\t\t\t\t\t<label class=\"ui-section__field-label\" for=\"", "\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</label> \n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-section__field-inner\">\n\t\t\t\t\t\t<div class=\"intarnet-settings__domain_inline-field\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-block\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"intarnet-settings__domain_name\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>"])), this.getFieldView().getId(), this.getFieldView().getName(), this.getFieldView().getLabel(), this.getFieldView().getInputNode(), main_core.Text.encode(this.options.mainDomainName), this.getFieldView().renderErrors()));
	      }
	      return babelHelpers.classPrivateFieldGet(this, _content$2);
	    }
	  }]);
	  return SiteDomainField;
	}(ui_formElements_field.SettingsField);

	function setPortalSettings(container, portalSettings) {
	  var logoNode = container.querySelector('[data-role="logo"]');
	  var titleNode = container.querySelector('[data-role="title"]');
	  var logo24Node = container.querySelector('[data-role="logo24"]');
	  if (!logoNode.hasAttribute('data-prev-display')) {
	    logoNode.dataset.prevDisplay = logoNode.style.display;
	    titleNode.dataset.prevDisplay = titleNode.style.display;
	    logo24Node.dataset.prevDisplay = logo24Node.style.display;
	  }
	  if (main_core.Type.isUndefined(portalSettings.title) !== true) {
	    titleNode.innerHTML = main_core.Text.encode(main_core.Type.isStringFilled(portalSettings.title) ? portalSettings.title : 'Bitrix');
	  }
	  if (main_core.Type.isUndefined(portalSettings.logo24) !== true) {
	    if (main_core.Type.isStringFilled(portalSettings.logo24)) {
	      delete logo24Node.dataset.visibility;
	      if (logoNode.style.display === 'none') {
	        logo24Node.style.removeProperty('display');
	      }
	    } else {
	      logo24Node.dataset.visibility = 'hidden';
	      logo24Node.style.display = 'none';
	    }
	  }
	  if (main_core.Type.isUndefined(portalSettings.logo) !== true) {
	    if (main_core.Type.isPlainObject(portalSettings.logo)) {
	      logoNode.style.backgroundImage = 'url("' + encodeURI(portalSettings.logo.src) + '")';
	      logoNode.style.removeProperty('display');
	      titleNode.style.display = 'none';
	      logo24Node.style.display = 'none';
	    } else {
	      logoNode.style.display = 'none';
	      titleNode.style.removeProperty('display');
	      if (logo24Node.dataset.visibility !== 'hidden') {
	        logo24Node.style.removeProperty('display');
	      } else {
	        logo24Node.style.display = 'none';
	      }
	    }
	  }
	}
	function setPortalThemeSettings(container, themeSettings) {
	  var theme = main_core.Type.isPlainObject(themeSettings) ? themeSettings : {};
	  var lightning = String(theme.id).indexOf('dark:') === 0 ? 'dark' : 'light';
	  main_core.Dom.removeClass(container, '--light --dark');
	  main_core.Dom.addClass(container, '--' + lightning);
	  if (main_core.Type.isStringFilled(theme.previewImage)) {
	    container.style.backgroundImage = 'url("' + theme.previewImage + '")';
	    container.style.backgroundSize = 'cover';
	  } else {
	    container.style.removeProperty('backgroundImage');
	    container.style.removeProperty('backgroundSize');
	    container.style.background = 'none';
	  }
	  if (main_core.Type.isStringFilled(theme.previewColor)) {
	    container.style.backgroundColor = theme.previewColor;
	  }
	}

	var _templateObject$9, _templateObject2$5, _templateObject3$3;
	function _classPrivateMethodInitSpec$6(obj, privateSet) { _checkPrivateRedeclaration$d(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$b(obj, privateMap, value) { _checkPrivateRedeclaration$d(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$d(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$6(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _themePicker = /*#__PURE__*/new WeakMap();
	var _initThemePicker = /*#__PURE__*/new WeakSet();
	var ThemePickerElement = /*#__PURE__*/function (_BaseField) {
	  babelHelpers.inherits(ThemePickerElement, _BaseField);
	  function ThemePickerElement(_themePickerSettings) {
	    var _this;
	    babelHelpers.classCallCheck(this, ThemePickerElement);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ThemePickerElement).call(this, {
	      inputName: 'themeId',
	      isEnable: _themePickerSettings.allowSetDefaultTheme,
	      bannerCode: 'limit_office_background_to_all'
	    }));
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _initThemePicker);
	    _classPrivateFieldInitSpec$b(babelHelpers.assertThisInitialized(_this), _themePicker, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateMethodGet$6(babelHelpers.assertThisInitialized(_this), _initThemePicker, _initThemePicker2).call(babelHelpers.assertThisInitialized(_this), _themePickerSettings);
	    _this.applyTheme();
	    return _this;
	  }
	  babelHelpers.createClass(ThemePickerElement, [{
	    key: "applyTheme",
	    value: function applyTheme(event) {
	      var themeNode = event ? babelHelpers.classPrivateFieldGet(this, _themePicker).getItemNode(event) : null;
	      var themeSettings = themeNode ? babelHelpers.classPrivateFieldGet(this, _themePicker).getTheme(themeNode.dataset.themeId) : babelHelpers.classPrivateFieldGet(this, _themePicker).getAppliedTheme();
	      this.applyPortalThemePreview(themeSettings);
	      if (event) {
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:ThemePicker:Change', themeSettings);
	        this.showSaveButton();
	      }
	    }
	  }, {
	    key: "applyPortalThemePreview",
	    value: function applyPortalThemePreview(theme) {
	      var container = this.render().querySelector('[data-role="preview"]');
	      setPortalThemeSettings(container, theme);
	      this.getInputNode().value = main_core.Type.isPlainObject(theme) ? theme['id'] : '';
	    }
	  }, {
	    key: "showSaveButton",
	    value: function showSaveButton() {
	      this.getInputNode().disabled = false;
	      this.getInputNode().form.dispatchEvent(new window.Event('change'));
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return this.getInputNode().value;
	    }
	  }, {
	    key: "getInputNode",
	    value: function getInputNode() {
	      return this.render().querySelector('input[name="themeId"]');
	    }
	  }, {
	    key: "applyPortalSettings",
	    value: function applyPortalSettings() {}
	  }, {
	    key: "renderContentField",
	    value: function renderContentField() {
	      var _this2 = this;
	      document.querySelector('.ui-side-panel-content').style.overflow = 'hidden';
	      var container = main_core.Tag.render(_templateObject$9 || (_templateObject$9 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"intranet-theme-settings ui-section__row\">\n\t\t\t<div class=\"ui-section__row theme-dialog-preview\">\n\t\t\t\t<section data-role=\"preview\" style=\"background-color: #0a51ae;\" class=\"intranet-settings__main-widget_section --preview\">\n\t\t\t\t\t<div class=\"intranet-settings__main-widget__bang\"></div>\n\t\t\t\t\t<aside class=\"intranet-settings__main-widget__aside\">\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item --active\"></div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t</aside>\n\t\t\t\t\t<main class=\"intranet-settings__main-widget_main\">\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_header --with-logo\">\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_header_left\">\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_logo\" data-role=\"logo\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_name\" data-role=\"title\">Bitrix</div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_logo24\" data-role=\"logo24\">24</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_header_right\">\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_box\">\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_inline --space-between\">\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --sm\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --square\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_inner\">\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</main>\n\t\t\t\t\t<aside class=\"intranet-settings__main-widget__aside --right-side\">\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item --active\"></div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t</aside>\n\t\t\t\t</section>\n\t\t\t</div>\n\t\t\t<div class=\"ui-section__row theme-dialog-content\" data-role=\"theme-container\"></div>\n\t\t\t<input type=\"hidden\" name=\"themeId\" value=\"\" disabled>\n\t\t</div>\n\t\t"])));
	      var uploadBtn = main_core.Tag.render(_templateObject2$5 || (_templateObject2$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"intranet-settings__theme-btn_box\">\n\t\t\t\t<div class=\"intranet-settings__theme-btn\" onclick=\"", "\">", "</div>\n\t\t\t</div>\n\t\t"])), this.handleNewThemeButtonClick.bind(this), main_core.Loc.getMessage('INTRANET_SETTINGS_THEME_UPLOAD_BTN'));
	      var themeContainer = container.querySelector('div[data-role="theme-container"]');
	      Array.from(babelHelpers.classPrivateFieldGet(this, _themePicker).getThemes()).forEach(function (theme) {
	        var itemNode = babelHelpers.classPrivateFieldGet(_this2, _themePicker).createItem(theme);
	        if (babelHelpers.classPrivateFieldGet(_this2, _themePicker).canSetDefaultTheme() !== true) {
	          main_core.Event.unbindAll(itemNode, 'click');
	          if (theme['default'] !== true) {
	            main_core.Dom.addClass(itemNode, '--restricted');
	            itemNode.appendChild(main_core.Tag.render(_templateObject3$3 || (_templateObject3$3 = babelHelpers.taggedTemplateLiteral(["<div class=\"intranet-settings__theme_lock_box\">", "</div>"])), _this2.renderLockElement()));
	            main_core.Event.bind(itemNode, 'click', _this2.showBanner.bind(_this2));
	          }
	        }
	        if (theme['default'] === true) {
	          itemNode.setAttribute('data-role', 'ui-ears-active');
	        }
	        themeContainer.appendChild(itemNode);
	      });
	      new ui_ears.Ears({
	        container: themeContainer,
	        noScrollbar: false
	      }).init();
	      container.appendChild(uploadBtn);
	      return container;
	    }
	  }, {
	    key: "handleNewThemeButtonClick",
	    value: function handleNewThemeButtonClick(event) {
	      if (babelHelpers.classPrivateFieldGet(this, _themePicker).canSetDefaultTheme() !== true) {
	        return this.showBanner();
	      }
	      babelHelpers.classPrivateFieldGet(this, _themePicker).getNewThemeDialog().show();
	    }
	  }, {
	    key: "handleLockButtonClick",
	    value: function handleLockButtonClick() {
	      if (BX.getClass("BX.UI.InfoHelper")) {
	        BX.UI.InfoHelper.show("limit_office_background_to_all");
	      }
	    }
	  }]);
	  return ThemePickerElement;
	}(ui_formElements_view.BaseField);
	function _initThemePicker2(themePickerSettings) {
	  var _this4 = this;
	  babelHelpers.classPrivateFieldSet(this, _themePicker, new BX.Intranet.Bitrix24.ThemePicker(themePickerSettings));
	  babelHelpers.classPrivateFieldGet(this, _themePicker).setThemes(themePickerSettings.themes);
	  babelHelpers.classPrivateFieldGet(this, _themePicker).setBaseThemes(themePickerSettings.baseThemes);
	  babelHelpers.classPrivateFieldGet(this, _themePicker).applyThemeAssets = function () {};
	  babelHelpers.classPrivateFieldGet(this, _themePicker).getContentContainer = function () {
	    return _this4.render().querySelector('div[data-role="theme-container"]');
	  };
	  var closure = babelHelpers.classPrivateFieldGet(this, _themePicker).handleRemoveBtnClick.bind(babelHelpers.classPrivateFieldGet(this, _themePicker));
	  babelHelpers.classPrivateFieldGet(this, _themePicker).handleRemoveBtnClick = function (event) {
	    var item = babelHelpers.classPrivateFieldGet(_this4, _themePicker).getItemNode(event);
	    if (!item) {
	      return;
	    }
	    closure(event);
	    _this4.applyPortalThemePreview(babelHelpers.classPrivateFieldGet(_this4, _themePicker).getTheme(babelHelpers.classPrivateFieldGet(_this4, _themePicker).getThemeId()));
	    _this4.showSaveButton();
	    //TODO Shift all <td>
	  };

	  var handleItemClick = babelHelpers.classPrivateFieldGet(this, _themePicker).handleItemClick.bind(babelHelpers.classPrivateFieldGet(this, _themePicker));
	  babelHelpers.classPrivateFieldGet(this, _themePicker).handleItemClick = function (event) {
	    handleItemClick(event);
	    _this4.applyTheme(event);
	  };
	  var addItem = babelHelpers.classPrivateFieldGet(this, _themePicker).addItem.bind(babelHelpers.classPrivateFieldGet(this, _themePicker));
	  babelHelpers.classPrivateFieldGet(this, _themePicker).addItem = function (theme) {
	    addItem(theme);
	    _this4.applyPortalThemePreview(theme);
	    _this4.showSaveButton();
	  };
	}
	var _fieldView = /*#__PURE__*/new WeakMap();
	var SiteThemePickerField = /*#__PURE__*/function (_SettingsField) {
	  babelHelpers.inherits(SiteThemePickerField, _SettingsField);
	  function SiteThemePickerField(params) {
	    var _this3;
	    babelHelpers.classCallCheck(this, SiteThemePickerField);
	    params.fieldView = new ThemePickerElement(params.themePickerSettings);
	    _this3 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SiteThemePickerField).call(this, params));
	    _classPrivateFieldInitSpec$b(babelHelpers.assertThisInitialized(_this3), _fieldView, {
	      writable: true,
	      value: void 0
	    });
	    if (params.portalSettings) {
	      _this3.setEventNamespace('BX.Intranet.Settings');
	      setPortalSettings(_this3.getFieldView().render(), params.portalSettings);
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, _this3.getEventNamespace() + ':Portal:Change', function (baseEvent) {
	        setPortalSettings(_this3.getFieldView().render(), baseEvent.getData());
	      });
	    }
	    return _this3;
	  }
	  return SiteThemePickerField;
	}(ui_formElements_field.SettingsField);

	var _templateObject$a, _templateObject2$6, _templateObject3$4;
	function _classPrivateMethodInitSpec$7(obj, privateSet) { _checkPrivateRedeclaration$e(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$c(obj, privateMap, value) { _checkPrivateRedeclaration$e(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$e(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$7(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var HiddenInput = /*#__PURE__*/function (_TextInput) {
	  babelHelpers.inherits(HiddenInput, _TextInput);
	  function HiddenInput(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, HiddenInput);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(HiddenInput).call(this, {
	      inputName: 'logo',
	      isEnable: params.isEnable,
	      defaultValue: 'default',
	      bannerCode: 'limit_admin_logo',
	      helpDeskCode: 123,
	      helpMessageProvider: function helpMessageProvider() {}
	    }));
	    _this.getInputNode().type = 'hidden';
	    _this.getInputNode().disabled = true;
	    return _this;
	  }
	  babelHelpers.createClass(HiddenInput, [{
	    key: "renderContentField",
	    value: function renderContentField() {
	      return this.getInputNode();
	    }
	  }]);
	  return HiddenInput;
	}(ui_formElements_view.TextInput);
	var _content$3 = /*#__PURE__*/new WeakMap();
	var _uploader = /*#__PURE__*/new WeakMap();
	var _siteLogo = /*#__PURE__*/new WeakMap();
	var _hiddenContainer = /*#__PURE__*/new WeakMap();
	var _hiddenRemoveInput = /*#__PURE__*/new WeakMap();
	var _loader = /*#__PURE__*/new WeakMap();
	var _getFileContainer = /*#__PURE__*/new WeakSet();
	var _renderAfterLoad = /*#__PURE__*/new WeakSet();
	var _showLoader = /*#__PURE__*/new WeakSet();
	var _removeLoader = /*#__PURE__*/new WeakSet();
	var SiteLogoField = /*#__PURE__*/function (_SettingsField) {
	  babelHelpers.inherits(SiteLogoField, _SettingsField);
	  function SiteLogoField(params) {
	    var _this2;
	    babelHelpers.classCallCheck(this, SiteLogoField);
	    params.fieldView = new HiddenInput({
	      isEnable: params.canUserEditLogo
	    });
	    _this2 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SiteLogoField).call(this, params));
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this2), _removeLoader);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this2), _showLoader);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this2), _renderAfterLoad);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this2), _getFileContainer);
	    _classPrivateFieldInitSpec$c(babelHelpers.assertThisInitialized(_this2), _content$3, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(babelHelpers.assertThisInitialized(_this2), _uploader, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(babelHelpers.assertThisInitialized(_this2), _siteLogo, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(babelHelpers.assertThisInitialized(_this2), _hiddenContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(babelHelpers.assertThisInitialized(_this2), _hiddenRemoveInput, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(babelHelpers.assertThisInitialized(_this2), _loader, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this2), _siteLogo, params.siteLogoOptions);
	    _this2.siteLogoLabel = params.siteLogoLabel;
	    _this2.setEventNamespace('BX.Intranet.Settings');
	    return _this2;
	  }
	  babelHelpers.createClass(SiteLogoField, [{
	    key: "initUploader",
	    value: function initUploader(_ref) {
	      var _this3 = this;
	      var TileWidget = _ref.TileWidget,
	        StackWidget = _ref.StackWidget,
	        StackWidgetSize = _ref.StackWidgetSize;
	      var defaultOptions = {
	        maxFileCount: 1,
	        acceptOnlyImages: true,
	        multiple: false,
	        acceptedFileTypes: ['image/png'],
	        events: {
	          'onError': function onError(event) {
	            console.error('File Uploader onError', event.getData().error);
	          },
	          'File:onError': this.onFileError.bind(this),
	          'File:onAdd': this.onLogoAdd.bind(this),
	          'File:onRemove': this.onLogoRemove.bind(this),
	          'onBeforeFilesAdd': this.getFieldView().isEnable() ? function () {} : function (event) {
	            _this3.getFieldView().showBanner();
	            event.preventDefault();
	          }
	        },
	        allowReplaceSingle: true,
	        hiddenFieldName: 'logo_file',
	        hiddenFieldsContainer: _classPrivateMethodGet$7(this, _getFileContainer, _getFileContainer2).call(this),
	        assignAsFile: true,
	        // imageMaxWidth: 444,
	        // imageMaxHeight: 110,

	        // imageMaxFileSize?: number,
	        // imageMinFileSize?: number,

	        imageResizeWidth: 444,
	        imageResizeHeight: 110,
	        imageResizeMode: 'contain',
	        imageResizeMimeType: 'image/png',
	        imagePreviewWidth: 444,
	        imagePreviewHeight: 110,
	        imagePreviewResizeMode: 'contain',
	        // serverOptions: ServerOptions,
	        // filters?: Array<{ type: FilterType, filter: Filter | Function | string, options: { [key: string]: any } }>,
	        files: babelHelpers.classPrivateFieldGet(this, _siteLogo) ? [[1, {
	          serverFileId: babelHelpers.classPrivateFieldGet(this, _siteLogo).id,
	          serverId: babelHelpers.classPrivateFieldGet(this, _siteLogo).id,
	          type: 'image/png',
	          width: babelHelpers.classPrivateFieldGet(this, _siteLogo).width,
	          height: babelHelpers.classPrivateFieldGet(this, _siteLogo).height,
	          treatImageAsFile: true,
	          downloadUrl: babelHelpers.classPrivateFieldGet(this, _siteLogo).src,
	          serverPreviewUrl: babelHelpers.classPrivateFieldGet(this, _siteLogo).src,
	          serverPreviewWidth: babelHelpers.classPrivateFieldGet(this, _siteLogo).width,
	          serverPreviewHeight: babelHelpers.classPrivateFieldGet(this, _siteLogo).height,
	          src: babelHelpers.classPrivateFieldGet(this, _siteLogo).src,
	          preload: true
	        }]] : null
	      };
	      babelHelpers.classPrivateFieldSet(this, _uploader, new StackWidget(defaultOptions, {
	        size: StackWidgetSize.LARGE
	      }));
	      return this;
	    }
	  }, {
	    key: "onFileError",
	    value: function onFileError(event) {
	      console.error('File Error', event.getData().error);
	      main_core_events.EventEmitter.subscribeOnce(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':onAfterShowPage', this.removeFailedLogo.bind(this));
	      var tabField = this.getParentElement();
	      if (tabField) {
	        main_core_events.EventEmitter.subscribeOnce(tabField.getFieldView(), 'onActive', this.removeFailedLogo.bind(this));
	      }
	    }
	  }, {
	    key: "removeFailedLogo",
	    value: function removeFailedLogo() {
	      var logo = babelHelpers.classPrivateFieldGet(this, _uploader).getUploader().getFiles()[0];
	      if (logo && logo.isLoadFailed()) {
	        babelHelpers.classPrivateFieldGet(this, _uploader).getUploader().removeFiles();
	      }
	    }
	  }, {
	    key: "onLogoAdd",
	    value: function onLogoAdd(event) {
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', new main_core_events.BaseEvent({
	        data: {
	          logo: {
	            src: event.getData().file.getClientPreviewUrl()
	          }
	        }
	      }));
	      this.getFieldView().getInputNode().disabled = false;
	      this.getFieldView().getInputNode().value = 'add';
	      this.getFieldView().getInputNode().form.dispatchEvent(new window.Event('change'));
	    }
	  }, {
	    key: "onLogoRemove",
	    value: function onLogoRemove(event) {
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', new main_core_events.BaseEvent({
	        data: {
	          logo: null
	        }
	      }));
	      this.getFieldView().getInputNode().disabled = false;
	      this.getFieldView().getInputNode().value = 'remove';
	      this.getFieldView().getInputNode().form.dispatchEvent(new window.Event('change'));
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return 'logo';
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {}
	  }, {
	    key: "render",
	    value: function render() {
	      var _this4 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _content$3)) {
	        return babelHelpers.classPrivateFieldGet(this, _content$3);
	      }
	      babelHelpers.classPrivateFieldSet(this, _content$3, main_core.Tag.render(_templateObject$a || (_templateObject$a = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	      _classPrivateMethodGet$7(this, _showLoader, _showLoader2).call(this);
	      main_core.Runtime.loadExtension('ui.uploader.stack-widget').then(function (exports) {
	        _this4.initUploader(exports);
	        _classPrivateMethodGet$7(_this4, _renderAfterLoad, _renderAfterLoad2).call(_this4);
	        _classPrivateMethodGet$7(_this4, _removeLoader, _removeLoader2).call(_this4);
	      });
	      return babelHelpers.classPrivateFieldGet(this, _content$3);
	    }
	  }]);
	  return SiteLogoField;
	}(ui_formElements_field.SettingsField);
	function _getFileContainer2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _hiddenContainer)) {
	    babelHelpers.classPrivateFieldSet(this, _hiddenContainer, document.createElement('div'));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _hiddenContainer);
	}
	function _renderAfterLoad2() {
	  var _this$siteLogoLabel;
	  var uploaderContent = main_core.Tag.render(_templateObject2$6 || (_templateObject2$6 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	  var content = main_core.Tag.render(_templateObject3$4 || (_templateObject3$4 = babelHelpers.taggedTemplateLiteral(["<div>\n\t\t\t\t<div class=\"ui-section__field-label\">", "</div>\n\t\t\t\t", "\n\t\t\t\t<div class=\"ui-section__field-label\">", "</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>"])), (_this$siteLogoLabel = this.siteLogoLabel) !== null && _this$siteLogoLabel !== void 0 ? _this$siteLogoLabel : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO_TITLE1'), uploaderContent, main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO_TITLE2'), this.getFieldView().getInputNode(), _classPrivateMethodGet$7(this, _getFileContainer, _getFileContainer2).call(this), this.getFieldView().renderErrors());
	  babelHelpers.classPrivateFieldGet(this, _uploader).renderTo(uploaderContent);
	  main_core.Dom.replace(babelHelpers.classPrivateFieldGet(this, _content$3), content);
	}
	function _showLoader2() {
	  babelHelpers.classPrivateFieldSet(this, _loader, new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldGet(this, _content$3),
	    color: 'rgba(82, 92, 105, 0.9)',
	    mode: 'inline'
	  }));
	  babelHelpers.classPrivateFieldGet(this, _loader).show().then(function () {
	    console.log('The loader is shown');
	  });
	}
	function _removeLoader2() {
	  if (babelHelpers.classPrivateFieldGet(this, _loader)) {
	    babelHelpers.classPrivateFieldGet(this, _loader).destroy();
	    babelHelpers.classPrivateFieldSet(this, _loader, null);
	  }
	}

	var _templateObject$b;
	function _classPrivateFieldInitSpec$d(obj, privateMap, value) { _checkPrivateRedeclaration$f(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$f(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _container = /*#__PURE__*/new WeakMap();
	var SiteTitlePreviewWidget = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SiteTitlePreviewWidget, _EventEmitter);
	  function SiteTitlePreviewWidget(portalSettings, portalThemeSettings) {
	    var _this;
	    babelHelpers.classCallCheck(this, SiteTitlePreviewWidget);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SiteTitlePreviewWidget).call(this));
	    _classPrivateFieldInitSpec$d(babelHelpers.assertThisInitialized(_this), _container, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('BX.Intranet.Settings');
	    setPortalSettings(_this.render(), portalSettings);
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, _this.getEventNamespace() + ':Portal:Change', _this.onChange.bind(babelHelpers.assertThisInitialized(_this)));
	    if (portalThemeSettings) {
	      setPortalThemeSettings(_this.render(), portalThemeSettings === null || portalThemeSettings === void 0 ? void 0 : portalThemeSettings.theme);
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, _this.getEventNamespace() + ':ThemePicker:Change', _this.onSetTheme.bind(babelHelpers.assertThisInitialized(_this)));
	    }
	    return _this;
	  }
	  babelHelpers.createClass(SiteTitlePreviewWidget, [{
	    key: "onChange",
	    value: function onChange(event) {
	      setPortalSettings(this.render(), event.getData());
	    }
	  }, {
	    key: "onSetTheme",
	    value: function onSetTheme(baseEvent) {
	      setPortalThemeSettings(this.render(), baseEvent.getData());
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	        babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject$b || (_templateObject$b = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<section class=\"intranet-settings__main-widget_section\">\n\t\t\t\t<div class=\"intranet-settings__main-widget__bang\"></div>\n\t\t\t\t\t<div class=\"intranet-settings__main-widget_bg\"></div>\n\t\t\t\t\t<div class=\"intranet-settings__main-widget_pos-box\">\n\t\t\t\t\t\t<aside class=\"intranet-settings__main-widget__aside\">\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item --active\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget__aside_item\"></div>\n\t\t\t\t\t\t</aside>\n\t\t\t\t\t\t<main class=\"intranet-settings__main-widget_main\">\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_header\"> \n\t\t\t\t\t\t<!-- statement class. depends of content --with-logo -->\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_logo\" data-role=\"logo\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_name\" data-role=\"title\">Bitrix</div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_logo24\" data-role=\"logo24\">24</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_box\">\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item\"></div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_inline\">\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --sm\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_inner\">\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-widget_lane_item --bg-30\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</main>\n\t\t\t\t\t</div>\t\t\t\t\n\t\t\t</section>"]))));
	      }
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }]);
	  return SiteTitlePreviewWidget;
	}(main_core_events.EventEmitter);

	var _templateObject2$7, _templateObject3$5;
	function _classPrivateMethodInitSpec$8(obj, privateSet) { _checkPrivateRedeclaration$g(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$g(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$8(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _headerWidgetRenderAlternative = /*#__PURE__*/new WeakSet();
	var _getOwnDomainTabBody = /*#__PURE__*/new WeakSet();
	var PortalPage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(PortalPage, _BaseSettingsPage);
	  function PortalPage() {
	    var _this;
	    babelHelpers.classCallCheck(this, PortalPage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PortalPage).call(this));
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _getOwnDomainTabBody);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _headerWidgetRenderAlternative);
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_PORTAL');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_PORTAL');
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, _this.getEventNamespace() + ':Portal:Change', function (baseEvent) {
	      if (!main_core.Type.isNil(baseEvent.data.title)) {
	        var _this$getAnalytic;
	        (_this$getAnalytic = _this.getAnalytic()) === null || _this$getAnalytic === void 0 ? void 0 : _this$getAnalytic.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_NAME);
	      } else if (!main_core.Type.isNil(baseEvent.data.logo)) {
	        var _this$getAnalytic2;
	        (_this$getAnalytic2 = _this.getAnalytic()) === null || _this$getAnalytic2 === void 0 ? void 0 : _this$getAnalytic2.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_LOGO);
	      }
	    });
	    //BX.Intranet.Settings:ThemePicker:Change
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, _this.getEventNamespace() + ':ThemePicker:Change', function (baseEvent) {
	      var _this$getAnalytic3, _baseEvent$data;
	      (_this$getAnalytic3 = _this.getAnalytic()) === null || _this$getAnalytic3 === void 0 ? void 0 : _this$getAnalytic3.addEventChangeTheme((_baseEvent$data = baseEvent.data) === null || _baseEvent$data === void 0 ? void 0 : _baseEvent$data.id);
	    });
	    return _this;
	  }
	  babelHelpers.createClass(PortalPage, [{
	    key: "getType",
	    value: function getType() {
	      return 'portal';
	    }
	  }, {
	    key: "headerWidgetRender",
	    value: function headerWidgetRender() {
	      return '';
	      // It is used to return #headerWidgetRenderAlternative;
	    } //TODO delete after autumn 2023
	  }, {
	    key: "getSections",
	    value: function getSections() {
	      return [this.buildSiteTitleSection(this.getValue('portalSettings'), this.getValue('portalThemeSettings'), this.getValue('portalSettingsLabels')), this.getValue('portalDomainSettings') ? this.buildDomainSection(this.getValue('portalDomainSettings')) : null, this.buildThemeSection(this.getValue('portalThemeSettings'), this.getValue('portalSettings'))].filter(function (section) {
	        return section instanceof ui_formElements_field.SettingsSection;
	      });
	    }
	  }, {
	    key: "buildSiteTitleSection",
	    value: function buildSiteTitleSection(portalSettings, portalThemeSettings, portalSettingsLabels) {
	      if (!this.hasValue('sectionCompanyTitle')) {
	        return;
	      }
	      var sectionView = new ui_section.Section(this.getValue('sectionCompanyTitle'));
	      var sectionField = new ui_formElements_field.SettingsSection({
	        parent: this,
	        section: sectionView
	      });
	      // 1. This is a description on blue box
	      sectionView.append(new ui_section.Row({
	        content: new ui_alerts.Alert({
	          text: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE_DESCRIPTION'),
	          inline: true,
	          size: ui_alerts.AlertSize.SMALL,
	          color: ui_alerts.AlertColor.PRIMARY
	        }).getContainer()
	      }).render());

	      //region 2. Tabs
	      var siteLogoRow = new ui_formElements_field.SettingsRow({
	        row: new ui_section.Row({
	          className: 'intranet-settings__grid_box'
	        }),
	        parent: sectionField
	      });
	      var previewWidget = new SiteTitlePreviewWidget(portalSettings, portalThemeSettings);
	      var tabsRow = new ui_formElements_field.SettingsRow({
	        row: new ui_section.Row({
	          className: 'intranet-settings__site-logo_subrow --no-padding --bottom-separator --block'
	        }),
	        parent: siteLogoRow
	      });
	      var tabsField = new ui_formElements_field.TabsField({
	        parent: tabsRow
	      });
	      // 2.1 Tab Site name
	      var siteTitleTab = new ui_formElements_field.TabField({
	        parent: tabsField,
	        tabsOptions: this.getValue('tabCompanyTitle')
	      });
	      var siteTitleRow = new ui_section.Row({});
	      var siteTitleField = new SiteTitleField({
	        parent: siteTitleRow,
	        siteTitleOptions: portalSettings,
	        siteTitleLabels: portalSettingsLabels,
	        helpMessages: {
	          site: this.helpMessageProviderFactory()
	        }
	      });
	      new ui_formElements_field.SettingsRow({
	        row: siteTitleRow,
	        parent: siteTitleTab,
	        child: siteTitleField
	      });
	      new ui_formElements_field.SettingsRow({
	        parent: siteTitleTab,
	        child: siteTitleField.getLogo24Field()
	      });
	      var siteLogoTab = new ui_formElements_field.TabField({
	        parent: tabsField,
	        tabsOptions: this.getValue('tabCompanyLogo')
	      });
	      var siteLogoField = new SiteLogoField({
	        siteLogoLabel: this.getValue('portalSettingsLabels').logo,
	        siteLogoOptions: this.getValue('portalSettings').logo,
	        canUserEditLogo: this.getValue('portalSettings').canUserEditLogo
	      });
	      new ui_formElements_field.SettingsRow({
	        parent: siteLogoTab,
	        child: siteLogoField
	      });
	      tabsField.activateTab(siteTitleTab);
	      // 2.2 Widget

	      new ui_formElements_field.SettingsRow({
	        row: new ui_section.Row({
	          content: previewWidget.render(),
	          className: 'intranet-settings__site-logo_subrow --no-padding'
	        }),
	        parent: siteLogoRow
	      });

	      // 2.3 site_name

	      new ui_formElements_field.SettingsRow({
	        row: new ui_section.SeparatorRow(),
	        parent: sectionField
	      });
	      new ui_formElements_field.SettingsRow({
	        parent: sectionField,
	        child: new ui_formElements_field.SettingsField({
	          fieldView: new ui_formElements_view.TextInput({
	            inputName: 'name',
	            label: this.getValue('portalSettingsLabels').name,
	            value: this.getValue('portalSettings').name,
	            placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COMPANY_TITLE'),
	            inputDefaultWidth: true
	          })
	        })
	      });

	      //endregion
	      return sectionField;
	    }
	  }, {
	    key: "buildDomainSection",
	    value: function buildDomainSection(domainSettings) {
	      var _this2 = this;
	      if (!this.hasValue('sectionSiteDomain')) {
	        return;
	      }
	      var sectionView = new ui_section.Section(this.getValue('sectionSiteDomain'));
	      var sectionField = new ui_formElements_field.SettingsSection({
	        parent: this,
	        section: sectionView
	      });
	      // 1. This is a description on blue box
	      sectionView.append(new ui_section.Row({
	        content: new ui_alerts.Alert({
	          text: "\n\t\t\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_DOMAIN_DESCRIPTION'), "\n\t\t\t\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=18213298')\">\n\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t\t\t\t</a>\n\t\t\t\t\t"),
	          inline: true,
	          size: ui_alerts.AlertSize.SMALL,
	          color: ui_alerts.AlertColor.PRIMARY
	        }).getContainer()
	      }).render());
	      var tabsRow = new ui_formElements_field.SettingsRow({
	        parent: sectionField
	      });

	      //region 2. Tabs
	      var tabsField = new ui_formElements_field.TabsField({
	        parent: tabsRow
	      });
	      // 2.1 Tab Site name
	      var firstTab = new ui_formElements_field.TabField({
	        parent: tabsField,
	        tabsOptions: this.getValue('tabDomainPrefix')
	      });
	      var siteDomainField = new SiteDomainField({
	        siteDomainOptions: domainSettings,
	        helpMessages: {
	          site: this.helpMessageProviderFactory()
	        }
	      });
	      main_core.Event.bind(siteDomainField.getFieldView().getInputNode(), 'keydown', function () {
	        var _this2$getAnalytic;
	        (_this2$getAnalytic = _this2.getAnalytic()) === null || _this2$getAnalytic === void 0 ? void 0 : _this2$getAnalytic.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_SITE);
	      });
	      var firstTabRow = new ui_section.Row({
	        content: siteDomainField.render()
	      });
	      new ui_formElements_field.SettingsRow({
	        row: firstTabRow,
	        parent: firstTab,
	        child: siteDomainField
	      });
	      var secondTab = new ui_formElements_field.TabField({
	        parent: tabsField,
	        tabsOptions: this.getValue('tabDomain')
	      });
	      var descriptionRow = new ui_section.Row({
	        content: _classPrivateMethodGet$8(this, _getOwnDomainTabBody, _getOwnDomainTabBody2).call(this, domainSettings)
	      });
	      new ui_formElements_field.SettingsRow({
	        row: descriptionRow,
	        parent: secondTab
	      });
	      tabsField.activateTab(firstTab);
	      //endregion

	      return sectionField;
	    }
	  }, {
	    key: "buildThemeSection",
	    value: function buildThemeSection(themePickerSettings, portalSettings) {
	      if (!this.hasValue('sectionSiteTheme')) {
	        return;
	      }
	      var sectionView = new ui_section.Section(this.getValue('sectionSiteTheme'));
	      var sectionField = new ui_formElements_field.SettingsSection({
	        section: sectionView,
	        parent: this
	      });

	      // 1. This is a description on blue box
	      new ui_formElements_field.SettingsRow({
	        row: new ui_section.Row({
	          content: new ui_alerts.Alert({
	            text: "\n\t\t\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_PORTAL_THEME_DESCRIPTION'), "\n\t\t\t\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=18325288')\">\n\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t\t\t\t</a>\n\t\t\t\t\t"),
	            inline: true,
	            size: ui_alerts.AlertSize.SMALL,
	            color: ui_alerts.AlertColor.PRIMARY
	          }).getContainer()
	        }),
	        parent: sectionField
	      });

	      // 2. This is a theme picker
	      new SiteThemePickerField({
	        parent: sectionField,
	        portalSettings: portalSettings,
	        themePickerSettings: themePickerSettings
	      });
	      return sectionField;
	    }
	  }]);
	  return PortalPage;
	}(ui_formElements_field.BaseSettingsPage);
	function _getOwnDomainTabBody2(domainSettings) {
	  var copyButton = main_core.Tag.render(_templateObject2$7 || (_templateObject2$7 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-icon-set --copy-plates intranet-settings__domain__list_btn\"></div>"])));
	  var exampleDns = domainSettings.exampleDns.join('<br>');
	  BX.clipboard.bindCopyClick(copyButton, {
	    text: function text() {
	      return exampleDns.replaceAll('<br>', "\n");
	    }
	  });
	  var res = main_core.Tag.render(_templateObject3$5 || (_templateObject3$5 = babelHelpers.taggedTemplateLiteral(["<div class=\"intranet-settings__domain__list_box\">\n\t\t\t\t\t\t<ul class=\"intranet-settings__domain__list\">\n\t\t\t\t\t\t\t<li class=\"intranet-settings__domain__list_item\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__domain_box\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"intranet-settings__domain__list_item\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"intranet-settings__domain__list_item\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t\t<a target=\"_blank\" href=\"/settings/support.php\" class=\"settings-tools-description-link\">", "</a>\n\t\t\t\t\t</div>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP1'), exampleDns, copyButton, main_core.Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP2'), main_core.Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP3'), main_core.Loc.getMessage('INTRANET_SETTINGS_WRITE_TO_SUPPORT'));
	  if (domainSettings.isCustomizable !== true) {
	    main_core.Event.bind(res.querySelector('a.settings-tools-description-link'), 'click', function (event) {
	      BX.UI.InfoHelper.show('limit_office_own_domain');
	      event.preventDefault();
	      return false;
	    });
	  }
	  return res;
	}

	var _templateObject$d, _templateObject2$8, _templateObject3$6;
	function _classPrivateFieldInitSpec$e(obj, privateMap, value) { _checkPrivateRedeclaration$h(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$h(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var PortalDeleteFormTypes = function PortalDeleteFormTypes() {
	  babelHelpers.classCallCheck(this, PortalDeleteFormTypes);
	};
	babelHelpers.defineProperty(PortalDeleteFormTypes, "WARNING", '--warning');
	babelHelpers.defineProperty(PortalDeleteFormTypes, "DANGER", '--danger');
	var _container$1 = /*#__PURE__*/new WeakMap();
	var _verificationOptions = /*#__PURE__*/new WeakMap();
	var PortalDeleteForm = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(PortalDeleteForm, _EventEmitter);
	  function PortalDeleteForm(verificationOptions) {
	    var _this;
	    babelHelpers.classCallCheck(this, PortalDeleteForm);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PortalDeleteForm).call(this));
	    _classPrivateFieldInitSpec$e(babelHelpers.assertThisInitialized(_this), _container$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$e(babelHelpers.assertThisInitialized(_this), _verificationOptions, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _verificationOptions, verificationOptions);
	    _this.setEventNamespace('BX.Intranet.Settings:PortalDeleteForm');
	    return _this;
	  }
	  babelHelpers.createClass(PortalDeleteForm, [{
	    key: "getDescription",
	    value: function getDescription() {
	      var moreDetails = "\n\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=19566456')\">\n\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t</a>\n\t\t");
	      return main_core.Tag.render(_templateObject$d || (_templateObject$d = babelHelpers.taggedTemplateLiteral(["\n\t\t\t", "\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_MSGVER_1', {
	        '#MORE_DETAILS#': moreDetails
	      }));
	    }
	  }, {
	    key: "getBodyClass",
	    value: function getBodyClass() {
	      return PortalDeleteFormTypes.WARNING;
	    }
	  }, {
	    key: "getConfirmButtonText",
	    value: function getConfirmButtonText() {
	      return main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL');
	    }
	  }, {
	    key: "getInputContainer",
	    value: function getInputContainer() {}
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      if (!babelHelpers.classPrivateFieldGet(this, _container$1)) {
	        babelHelpers.classPrivateFieldSet(this, _container$1, main_core.Tag.render(_templateObject2$8 || (_templateObject2$8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"intranet-settings__portal-delete-form_wrapper ", "\">\n\t\t\t\t\t<div class=\"intranet-settings__portal-delete-form_body\">\n\t\t\t\t\t\t<div class=\"intranet-settings__portal-delete-icon-wrapper\">\n\t\t\t\t\t\t\t<div class=\"ui-icon-set --warning\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"intranet-settings__portal-delete-form_description-wrapper\">\n\t\t\t\t\t\t\t<span class=\"intranet-settings__portal-delete-form_description\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), this.getBodyClass(), this.getDescription(), this.getInputContainer(), this.getButtonContainer()));
	      }
	      return babelHelpers.classPrivateFieldGet(this, _container$1);
	    }
	  }, {
	    key: "onConfirmEventHandler",
	    value: function onConfirmEventHandler() {
	      var _this2 = this;
	      this.getConfirmButton().setWaiting(true);
	      top.BX.Runtime.loadExtension('bitrix24.portal-delete').then(function (exports) {
	        var PortalDelete = exports.PortalDelete;
	        var portalDelete = new PortalDelete(babelHelpers.classPrivateFieldGet(_this2, _verificationOptions));
	        portalDelete.showCheckwordPopup();
	        _this2.getConfirmButton().setWaiting(false);
	      });
	    }
	  }, {
	    key: "getButtonContainer",
	    value: function getButtonContainer() {
	      return main_core.Tag.render(_templateObject3$6 || (_templateObject3$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"intranet-settings__portal-delete-form_buttons-wrapper\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), this.getConfirmButton().getContainer());
	    }
	  }, {
	    key: "getConfirmButton",
	    value: function getConfirmButton() {
	      var _this3 = this;
	      if (!this.confirmButton) {
	        var _this$getConfirmButto;
	        this.confirmButton = new BX.UI.Button({
	          text: (_this$getConfirmButto = this.getConfirmButtonText()) !== null && _this$getConfirmButto !== void 0 ? _this$getConfirmButto : '',
	          noCaps: true,
	          round: true,
	          className: '--confirm',
	          events: {
	            click: function click() {
	              _this3.onConfirmEventHandler();
	            }
	          },
	          props: {
	            'data-bx-role': 'delete-portal-confirm'
	          }
	        });
	      }
	      return this.confirmButton;
	    }
	  }, {
	    key: "sendChangeFormEvent",
	    value: function sendChangeFormEvent(type) {
	      this.emit('updateForm', new main_core_events.BaseEvent({
	        data: {
	          type: type !== null && type !== void 0 ? type : null
	        }
	      }));
	    }
	  }]);
	  return PortalDeleteForm;
	}(main_core_events.EventEmitter);

	var _templateObject$e;
	function _classPrivateFieldInitSpec$f(obj, privateMap, value) { _checkPrivateRedeclaration$i(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$i(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _isFreeLicense = /*#__PURE__*/new WeakMap();
	var PortalDeleteFormEmployee = /*#__PURE__*/function (_PortalDeleteForm) {
	  babelHelpers.inherits(PortalDeleteFormEmployee, _PortalDeleteForm);
	  function PortalDeleteFormEmployee(isFreeLicense) {
	    var _this;
	    babelHelpers.classCallCheck(this, PortalDeleteFormEmployee);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PortalDeleteFormEmployee).call(this));
	    _classPrivateFieldInitSpec$f(babelHelpers.assertThisInitialized(_this), _isFreeLicense, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _isFreeLicense, isFreeLicense);
	    return _this;
	  }
	  babelHelpers.createClass(PortalDeleteFormEmployee, [{
	    key: "getDescription",
	    value: function getDescription() {
	      var moreDetails = "\n\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=19566456')\">\n\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t</a>\n\t\t");
	      return main_core.Tag.render(_templateObject$e || (_templateObject$e = babelHelpers.taggedTemplateLiteral(["\n\t\t\t", "\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_EMPLOYEE', {
	        '#MORE_DETAILS#': moreDetails
	      }));
	    }
	  }, {
	    key: "getBodyClass",
	    value: function getBodyClass() {
	      return PortalDeleteFormTypes.WARNING;
	    }
	  }, {
	    key: "getConfirmButtonText",
	    value: function getConfirmButtonText() {
	      return main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL_FIRE_EMPLOYEE');
	    }
	  }, {
	    key: "onConfirmEventHandler",
	    value: function onConfirmEventHandler() {
	      var _this2 = this;
	      this.getConfirmButton().setWaiting(true);
	      BX.SidePanel.Instance.open('/company/?apply_filter=Y&FIRED=N', {
	        events: {
	          onCloseComplete: function onCloseComplete() {
	            main_core.ajax.runAction('bitrix24.portal.getActiveUserCount').then(function (response) {
	              _this2.getConfirmButton().setWaiting(false);
	              if (response.data <= 1) {
	                _this2.sendChangeFormEvent(babelHelpers.classPrivateFieldGet(_this2, _isFreeLicense) ? 'default' : 'mail');
	              }
	            })["catch"](function (reject) {
	              _this2.getConfirmButton().setWaiting(false);
	              reject.errors.forEach(function (error) {
	                console.log(error.message);
	              });
	            });
	          }
	        }
	      });
	    }
	  }]);
	  return PortalDeleteFormEmployee;
	}(PortalDeleteForm);

	var _templateObject$f;
	function _classPrivateMethodInitSpec$9(obj, privateSet) { _checkPrivateRedeclaration$j(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$g(obj, privateMap, value) { _checkPrivateRedeclaration$j(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$j(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$9(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _mailForRequest = /*#__PURE__*/new WeakMap();
	var _portalUrl = /*#__PURE__*/new WeakMap();
	var _mailLink = /*#__PURE__*/new WeakMap();
	var _getMailLink = /*#__PURE__*/new WeakSet();
	var PortalDeleteFormMail = /*#__PURE__*/function (_PortalDeleteForm) {
	  babelHelpers.inherits(PortalDeleteFormMail, _PortalDeleteForm);
	  function PortalDeleteFormMail(mailForRequest, portalUrl) {
	    var _this;
	    babelHelpers.classCallCheck(this, PortalDeleteFormMail);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PortalDeleteFormMail).call(this));
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _getMailLink);
	    _classPrivateFieldInitSpec$g(babelHelpers.assertThisInitialized(_this), _mailForRequest, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$g(babelHelpers.assertThisInitialized(_this), _portalUrl, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$g(babelHelpers.assertThisInitialized(_this), _mailLink, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _mailForRequest, mailForRequest);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _portalUrl, portalUrl);
	    return _this;
	  }
	  babelHelpers.createClass(PortalDeleteFormMail, [{
	    key: "getBodyClass",
	    value: function getBodyClass() {
	      return PortalDeleteFormTypes.WARNING;
	    }
	  }, {
	    key: "getConfirmButtonText",
	    value: function getConfirmButtonText() {
	      return main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL_MAIL', {
	        '#MAIL#': babelHelpers.classPrivateFieldGet(this, _mailForRequest)
	      });
	    }
	  }, {
	    key: "onConfirmEventHandler",
	    value: function onConfirmEventHandler() {
	      top.window.location.href = _classPrivateMethodGet$9(this, _getMailLink, _getMailLink2).call(this);
	    }
	  }, {
	    key: "getDescription",
	    value: function getDescription() {
	      var moreDetails = "\n\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=19566456')\">\n\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t</a>\n\t\t");
	      return main_core.Tag.render(_templateObject$f || (_templateObject$f = babelHelpers.taggedTemplateLiteral(["\n\t\t\t", "\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_MAIL', {
	        '#MAIL#': babelHelpers.classPrivateFieldGet(this, _mailForRequest),
	        '#MAIL_LINK#': _classPrivateMethodGet$9(this, _getMailLink, _getMailLink2).call(this),
	        '#MORE_DETAILS#': moreDetails
	      }));
	    }
	  }]);
	  return PortalDeleteFormMail;
	}(PortalDeleteForm);
	function _getMailLink2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _mailLink)) {
	    var mailBody = main_core.Loc.getMessage('INTRANET_SETTINGS_PORTAL_DELETE_MAIL_BODY', {
	      '#PORTAL_URL#': babelHelpers.classPrivateFieldGet(this, _portalUrl)
	    });
	    var mailSubject = main_core.Loc.getMessage('INTRANET_SETTINGS_PORTAL_DELETE_MAIL_SUBJECT', {
	      '#PORTAL_URL#': babelHelpers.classPrivateFieldGet(this, _portalUrl)
	    });
	    babelHelpers.classPrivateFieldSet(this, _mailLink, "mailto:".concat(babelHelpers.classPrivateFieldGet(this, _mailForRequest), "?body=").concat(mailBody, "&subject=").concat(mailSubject));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _mailLink);
	}

	var _templateObject$g;
	var PortalDeleteFormNotAdmin = /*#__PURE__*/function (_PortalDeleteForm) {
	  babelHelpers.inherits(PortalDeleteFormNotAdmin, _PortalDeleteForm);
	  function PortalDeleteFormNotAdmin() {
	    babelHelpers.classCallCheck(this, PortalDeleteFormNotAdmin);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PortalDeleteFormNotAdmin).apply(this, arguments));
	  }
	  babelHelpers.createClass(PortalDeleteFormNotAdmin, [{
	    key: "getButtonContainer",
	    value: function getButtonContainer() {}
	  }, {
	    key: "getBodyClass",
	    value: function getBodyClass() {
	      return PortalDeleteFormTypes.WARNING;
	    }
	  }, {
	    key: "getDescription",
	    value: function getDescription() {
	      return main_core.Tag.render(_templateObject$g || (_templateObject$g = babelHelpers.taggedTemplateLiteral(["\n\t\t\t", "\n\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=19566456')\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_NOT_ADMIN'), main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'));
	    }
	  }]);
	  return PortalDeleteFormNotAdmin;
	}(PortalDeleteForm);

	function _classPrivateMethodInitSpec$a(obj, privateSet) { _checkPrivateRedeclaration$k(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$h(obj, privateMap, value) { _checkPrivateRedeclaration$k(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$k(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$a(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _options$2 = /*#__PURE__*/new WeakMap();
	var _settingsRow = /*#__PURE__*/new WeakMap();
	var _form = /*#__PURE__*/new WeakMap();
	var _defaultBodyClass = /*#__PURE__*/new WeakMap();
	var _bodyClass = /*#__PURE__*/new WeakMap();
	var _renderFormRow = /*#__PURE__*/new WeakSet();
	var _bindFormEvents = /*#__PURE__*/new WeakSet();
	var _updateSectionBodyClass = /*#__PURE__*/new WeakSet();
	var PortalDeleteSection = /*#__PURE__*/function (_SettingsSection) {
	  babelHelpers.inherits(PortalDeleteSection, _SettingsSection);
	  function PortalDeleteSection(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, PortalDeleteSection);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PortalDeleteSection).call(this, params));
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _updateSectionBodyClass);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _bindFormEvents);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _renderFormRow);
	    _classPrivateFieldInitSpec$h(babelHelpers.assertThisInitialized(_this), _options$2, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$h(babelHelpers.assertThisInitialized(_this), _settingsRow, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$h(babelHelpers.assertThisInitialized(_this), _form, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$h(babelHelpers.assertThisInitialized(_this), _defaultBodyClass, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$h(babelHelpers.assertThisInitialized(_this), _bodyClass, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _defaultBodyClass, _this.getSectionView().className.bodyActive);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _options$2, params.options);
	    var _type;
	    if (!babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _options$2).isAdmin) {
	      _type = 'not_admin';
	    } else if (!babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _options$2).isFreeLicense) {
	      _type = 'mail';
	    } else if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _options$2).isEmployeesLeft) {
	      _type = 'employee';
	    } else if (!babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _options$2).verificationOptions) {
	      _type = 'mail';
	    } else {
	      _type = 'default';
	    }
	    _classPrivateMethodGet$a(babelHelpers.assertThisInitialized(_this), _renderFormRow, _renderFormRow2).call(babelHelpers.assertThisInitialized(_this), _type);
	    return _this;
	  }
	  return PortalDeleteSection;
	}(ui_formElements_field.SettingsSection);
	function _renderFormRow2(type) {
	  if (babelHelpers.classPrivateFieldGet(this, _settingsRow)) {
	    this.removeChild(babelHelpers.classPrivateFieldGet(this, _settingsRow));
	    main_core.Dom.remove(babelHelpers.classPrivateFieldGet(this, _settingsRow).render());
	  }
	  switch (type) {
	    case 'mail':
	      babelHelpers.classPrivateFieldSet(this, _form, new PortalDeleteFormMail(babelHelpers.classPrivateFieldGet(this, _options$2).mailForRequest, babelHelpers.classPrivateFieldGet(this, _options$2).portalUrl));
	      break;
	    case 'employee':
	      babelHelpers.classPrivateFieldSet(this, _form, new PortalDeleteFormEmployee(babelHelpers.classPrivateFieldGet(this, _options$2).isFreeLicense));
	      break;
	    case 'not_admin':
	      babelHelpers.classPrivateFieldSet(this, _form, new PortalDeleteFormNotAdmin());
	      break;
	    default:
	      babelHelpers.classPrivateFieldSet(this, _form, new PortalDeleteForm(babelHelpers.classPrivateFieldGet(this, _options$2).verificationOptions));
	      break;
	  }
	  _classPrivateMethodGet$a(this, _updateSectionBodyClass, _updateSectionBodyClass2).call(this);
	  _classPrivateMethodGet$a(this, _bindFormEvents, _bindFormEvents2).call(this);
	  var formRow = new ui_section.Row({
	    content: babelHelpers.classPrivateFieldGet(this, _form).getContainer()
	  });
	  babelHelpers.classPrivateFieldSet(this, _settingsRow, new ui_formElements_field.SettingsRow({
	    row: formRow
	  }));
	  this.addChild(babelHelpers.classPrivateFieldGet(this, _settingsRow));
	  this.render();
	}
	function _bindFormEvents2() {
	  var _this2 = this;
	  babelHelpers.classPrivateFieldGet(this, _form).subscribe('closeForm', function () {
	    _this2.getSectionView().toggle(false);
	  });
	  babelHelpers.classPrivateFieldGet(this, _form).subscribe('updateForm', function (event) {
	    if (event.data.type) {
	      _classPrivateMethodGet$a(_this2, _renderFormRow, _renderFormRow2).call(_this2, event.data.type);
	    }
	  });
	}
	function _updateSectionBodyClass2() {
	  main_core.Dom.removeClass(this.getSectionView().getContent(), babelHelpers.classPrivateFieldGet(this, _bodyClass));
	  babelHelpers.classPrivateFieldSet(this, _bodyClass, babelHelpers.classPrivateFieldGet(this, _form).getBodyClass());
	  this.getSectionView().className.bodyActive = babelHelpers.classPrivateFieldGet(this, _defaultBodyClass) + ' ' + babelHelpers.classPrivateFieldGet(this, _bodyClass);
	  if (this.getSectionView().isOpen) {
	    main_core.Dom.addClass(this.getSectionView().getContent(), babelHelpers.classPrivateFieldGet(this, _bodyClass));
	  }
	}

	var _templateObject$h, _templateObject2$9;
	function _classPrivateMethodInitSpec$b(obj, privateSet) { _checkPrivateRedeclaration$l(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$i(obj, privateMap, value) { _checkPrivateRedeclaration$l(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$l(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$b(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _header = /*#__PURE__*/new WeakMap();
	var _buildDateTimeSection = /*#__PURE__*/new WeakSet();
	var _buildMailsSection = /*#__PURE__*/new WeakSet();
	var _buildCRMMapsSection = /*#__PURE__*/new WeakSet();
	var _buildCardsProductPropertiesSection = /*#__PURE__*/new WeakSet();
	var _buildAdditionalSettingsSection = /*#__PURE__*/new WeakSet();
	var _geoDataSwitch = /*#__PURE__*/new WeakSet();
	var ConfigurationPage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(ConfigurationPage, _BaseSettingsPage);
	  function ConfigurationPage() {
	    var _this;
	    babelHelpers.classCallCheck(this, ConfigurationPage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ConfigurationPage).call(this));
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _geoDataSwitch);
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _buildAdditionalSettingsSection);
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _buildCardsProductPropertiesSection);
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _buildCRMMapsSection);
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _buildMailsSection);
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _buildDateTimeSection);
	    _classPrivateFieldInitSpec$i(babelHelpers.assertThisInitialized(_this), _header, {
	      writable: true,
	      value: void 0
	    });
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_CONFIGURATION');
	    return _this;
	  }
	  babelHelpers.createClass(ConfigurationPage, [{
	    key: "getType",
	    value: function getType() {
	      return 'configuration';
	    }
	  }, {
	    key: "headerWidgetRender",
	    value: function headerWidgetRender() {
	      var _this$getValue;
	      var timeFormat = '';
	      if (((_this$getValue = this.getValue('isFormat24Hour')) === null || _this$getValue === void 0 ? void 0 : _this$getValue.current) === 'Y') {
	        timeFormat = this.getValue('format24HourTime');
	      } else {
	        timeFormat = this.getValue('format12HourTime');
	      }
	      babelHelpers.classPrivateFieldSet(this, _header, main_core.Tag.render(_templateObject$h || (_templateObject$h = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"intranet-settings__date-widget_box\">\n\t\t\t<span class=\"ui-icon-set --earth-language\"></span>\n\t\t\t<div class=\"intranet-settings__date-widget_content\">\n\t\t\t\t<div class=\"intranet-settings__date-widget_inner\">\n\t\t\t\t\t<span data-role=\"time\" class=\"intranet-settings__date-widget_title\">", "</span>\n\t\t\t\t\t<span class=\"intranet-settings__date-widget_subtitle\">", "</span>\n\t\t\t\t</div>\n\t\t\t\t<div data-role=\"date\" class=\"intranet-settings__date-widget_subtitle\">", "</div>\n\t\t\t</div>\n\t\t</div>"])), timeFormat, this.getValue('offsetUTC'), this.getValue('currentDate')));
	      return babelHelpers.classPrivateFieldGet(this, _header);
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var dateTimeSection = _classPrivateMethodGet$b(this, _buildDateTimeSection, _buildDateTimeSection2).call(this);
	      dateTimeSection === null || dateTimeSection === void 0 ? void 0 : dateTimeSection.renderTo(contentNode);
	      var mailsSection = _classPrivateMethodGet$b(this, _buildMailsSection, _buildMailsSection2).call(this);
	      mailsSection === null || mailsSection === void 0 ? void 0 : mailsSection.renderTo(contentNode);
	      if (this.hasValue('mapsProviderCRM') && this.getValue('mapsProviderCRM')) {
	        var mapsSection = _classPrivateMethodGet$b(this, _buildCRMMapsSection, _buildCRMMapsSection2).call(this);
	        mapsSection === null || mapsSection === void 0 ? void 0 : mapsSection.renderTo(contentNode);
	      }
	      var cardsProductPropertiesSection = _classPrivateMethodGet$b(this, _buildCardsProductPropertiesSection, _buildCardsProductPropertiesSection2).call(this);
	      cardsProductPropertiesSection === null || cardsProductPropertiesSection === void 0 ? void 0 : cardsProductPropertiesSection.renderTo(contentNode);
	      var additionalSettingsSection = _classPrivateMethodGet$b(this, _buildAdditionalSettingsSection, _buildAdditionalSettingsSection2).call(this);
	      additionalSettingsSection === null || additionalSettingsSection === void 0 ? void 0 : additionalSettingsSection.renderTo(contentNode);
	      if (this.hasValue('deletePortalOptions') && this.hasValue('sectionDeletePortal')) {
	        var deletePortalSection = new ui_section.Section(this.getValue('sectionDeletePortal'));
	        var settingsSection = new PortalDeleteSection({
	          section: deletePortalSection,
	          parent: this,
	          options: this.getValue('deletePortalOptions')
	        });
	        settingsSection.renderTo(contentNode);
	      }
	    }
	  }]);
	  return ConfigurationPage;
	}(ui_formElements_field.BaseSettingsPage);
	function _buildDateTimeSection2() {
	  var _this2 = this;
	  if (!this.hasValue('sectionDateFormat')) {
	    return;
	  }
	  var dateTimeSection = new ui_section.Section(this.getValue('sectionDateFormat'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: dateTimeSection,
	    parent: this
	  });
	  if (this.hasValue('culture')) {
	    var regionField = new ui_formElements_view.Selector(this.getValue('culture'));
	    ConfigurationPage.addToSectionHelper(regionField, settingsSection, new ui_section.Row({
	      className: '--intranet-settings__mb-20'
	    }));
	    main_core.Event.bind(regionField.getInputNode(), 'change', function (event) {
	      babelHelpers.classPrivateFieldGet(_this2, _header).querySelector('[data-role="date"]').innerHTML = _this2.getValue('longDates')[event.target.value];
	    });
	  }
	  if (this.hasValue('isFormat24Hour')) {
	    var format24Time = new ui_formElements_view.InlineChecker(this.getValue('isFormat24Hour'));
	    ConfigurationPage.addToSectionHelper(format24Time, settingsSection);
	    main_core_events.EventEmitter.subscribe(format24Time, 'change', function (event) {
	      babelHelpers.classPrivateFieldGet(_this2, _header).querySelector('[data-role="time"]').innerHTML = format24Time.isChecked() ? _this2.getValue('format24HourTime') : _this2.getValue('format12HourTime');
	    });
	  }
	  return settingsSection;
	}
	function _buildMailsSection2() {
	  if (!this.hasValue('sectionLetters')) {
	    return;
	  }
	  var mailsSection = new ui_section.Section(this.getValue('sectionLetters'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: mailsSection,
	    parent: this
	  });
	  if (this.hasValue('trackOutMailsRead')) {
	    var trackOutLettersRead = new ui_formElements_view.Checker(this.getValue('trackOutMailsRead'));
	    var showQuitRow = new ui_section.Row({});
	    ConfigurationPage.addToSectionHelper(trackOutLettersRead, settingsSection, showQuitRow);
	  }
	  if (this.hasValue('trackOutMailsClick')) {
	    var trackOutMailsClick = new ui_formElements_view.Checker(this.getValue('trackOutMailsClick'));
	    var _showQuitRow = new ui_section.Row({});
	    ConfigurationPage.addToSectionHelper(trackOutMailsClick, settingsSection, _showQuitRow);
	  }
	  if (this.hasValue('defaultEmailFrom')) {
	    var defaultEmailFrom = new ui_formElements_view.TextInput(this.getValue('defaultEmailFrom'));
	    var _showQuitRow2 = new ui_section.Row({});
	    ConfigurationPage.addToSectionHelper(defaultEmailFrom, settingsSection, _showQuitRow2);
	  }
	  return settingsSection;
	}
	function _buildCRMMapsSection2() {
	  var _this$getValue$label;
	  if (!this.hasValue('sectionMapsInCrm')) {
	    return;
	  }
	  var mapsSection = new ui_section.Section(this.getValue('sectionMapsInCrm'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: mapsSection,
	    parent: this
	  });
	  var cardsProvider = new ui_formElements_view.Selector({
	    label: (_this$getValue$label = this.getValue('mapsProviderCRM').label) !== null && _this$getValue$label !== void 0 ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
	    name: this.getValue('mapsProviderCRM').name,
	    items: this.getValue('mapsProviderCRM').values,
	    current: this.getValue('mapsProviderCRM').current
	  });
	  var cardsProviderRow = new ui_section.Row({
	    separator: 'bottom',
	    className: '--block'
	  });
	  ConfigurationPage.addToSectionHelper(cardsProvider, settingsSection, cardsProviderRow);
	  var separatorRow = new ui_section.SeparatorRow({
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  new ui_formElements_field.SettingsRow({
	    row: separatorRow,
	    parent: settingsSection
	  });
	  var description = new BX.UI.Alert({
	    text: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_DESCRIPTION', {
	      '#GOOGLE_API_URL#': this.getValue('googleApiUrl')
	    }),
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	  var descriptionRow = new ui_section.Row({
	    separator: 'top',
	    content: description.getContainer(),
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: settingsSection
	  });
	  var googleKeyFrontend = new ui_formElements_view.TextInputInline({
	    inputName: 'API_KEY_FRONTEND',
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_PUBLIC'),
	    hintTitle: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_PUBLIC_HINT'),
	    value: this.getValue('API_KEY_FRONTEND').value,
	    placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER')
	  });
	  var googleKeyFrontendRow = new ui_section.Row({
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  ConfigurationPage.addToSectionHelper(googleKeyFrontend, settingsSection, googleKeyFrontendRow);
	  var mapApiKeyBackend = new ui_formElements_view.TextInputInline({
	    inputName: 'API_KEY_BACKEND',
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_SERVER'),
	    hintTitle: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_SERVER_HINT'),
	    value: this.getValue('API_KEY_BACKEND').value,
	    placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER')
	  });
	  var googleKeyBackendRow = new ui_section.Row({
	    content: mapApiKeyBackend.render(),
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM',
	    separator: 'bottom',
	    className: '--block'
	  });
	  ConfigurationPage.addToSectionHelper(mapApiKeyBackend, settingsSection, googleKeyBackendRow);
	  var separatorRow1 = new ui_section.SeparatorRow({});
	  new ui_formElements_field.SettingsRow({
	    row: separatorRow1,
	    parent: settingsSection
	  });
	  var showPhotoPlacesMaps = new ui_formElements_view.Checker({
	    inputName: 'SHOW_PHOTOS_ON_MAP',
	    title: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_PHOTO_PLACES_MAPS'),
	    hintOn: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
	    hintOff: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
	    checked: this.getValue('SHOW_PHOTOS_ON_MAP').value === '1'
	  });
	  var showPhotoPlacesMapsRow = new ui_section.Row({
	    separator: 'top',
	    className: '--block',
	    content: showPhotoPlacesMaps.render(),
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  ConfigurationPage.addToSectionHelper(showPhotoPlacesMaps, settingsSection, showPhotoPlacesMapsRow);
	  var useGeocodingService = new ui_formElements_view.Checker({
	    inputName: 'USE_GEOCODING_SERVICE',
	    title: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_GEOCODING_SERVICE'),
	    hintOn: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
	    hintOff: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
	    checked: this.getValue('USE_GEOCODING_SERVICE').value === '1'
	  });
	  var useGeocodingServiceRow = new ui_section.Row({
	    content: useGeocodingService.render(),
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  ConfigurationPage.addToSectionHelper(useGeocodingService, settingsSection, useGeocodingServiceRow);
	  cardsProvider.getInputNode().addEventListener('change', function (event) {
	    if (event.target.value === 'OSM') {
	      separatorRow.hide();
	      descriptionRow.hide();
	      googleKeyFrontendRow.hide();
	      googleKeyBackendRow.hide();
	      useGeocodingServiceRow.hide();
	      showPhotoPlacesMapsRow.hide();
	    } else {
	      separatorRow.show();
	      descriptionRow.show();
	      googleKeyFrontendRow.show();
	      googleKeyBackendRow.show();
	      useGeocodingServiceRow.show();
	      showPhotoPlacesMapsRow.show();
	    }
	  });
	  return settingsSection;
	}
	function _buildCardsProductPropertiesSection2() {
	  if (!this.hasValue('sectionMapsInProduct')) {
	    return;
	  }
	  var productPropertiesSection = new ui_section.Section(this.getValue('sectionMapsInProduct'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: productPropertiesSection,
	    parent: this
	  });
	  if (this.hasValue('cardsProviderProductProperties')) {
	    var _this$getValue$label2;
	    var cardsProviderProductProperties = new ui_formElements_view.Selector({
	      label: (_this$getValue$label2 = this.getValue('cardsProviderProductProperties').label) !== null && _this$getValue$label2 !== void 0 ? _this$getValue$label2 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
	      name: this.getValue('cardsProviderProductProperties').name,
	      items: this.getValue('cardsProviderProductProperties').values,
	      current: this.getValue('cardsProviderProductProperties').current
	    });
	    var cardsProviderProductPropertiesRow = new ui_section.Row({
	      separator: 'bottom'
	    });
	    ConfigurationPage.addToSectionHelper(cardsProviderProductProperties, settingsSection, cardsProviderProductPropertiesRow);
	    new ui_formElements_field.SettingsRow({
	      row: new ui_section.SeparatorRow(),
	      parent: settingsSection
	    });
	    var descriptionYandex = new BX.UI.Alert({
	      text: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_YANDEX_DESCRIPTION', {
	        '#YANDEX_API_URL#': this.getValue('yandexApiUrl')
	      }),
	      inline: true,
	      size: BX.UI.Alert.Size.SMALL,
	      color: BX.UI.Alert.Color.PRIMARY,
	      animated: true
	    });
	    var descriptionYandexRow = new ui_section.Row({
	      content: descriptionYandex.getContainer(),
	      isHidden: this.getValue('cardsProviderProductProperties').current !== 'yandex'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: descriptionYandexRow,
	      parent: settingsSection
	    });
	    var yandexKeyProductProperties = new ui_formElements_view.TextInput({
	      inputName: 'yandexKeyProductProperties',
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAP_PRODUCT_PROPERTIES_YANDEX_KEY'),
	      value: this.getValue('yandexKeyProductProperties'),
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER')
	    });
	    var yandexKeyProductPropertiesRow = new ui_section.Row({
	      separator: 'bottom',
	      className: '--block',
	      content: yandexKeyProductProperties.render(),
	      isHidden: this.getValue('cardsProviderProductProperties').current !== 'yandex'
	    });
	    ConfigurationPage.addToSectionHelper(yandexKeyProductProperties, settingsSection, yandexKeyProductPropertiesRow);
	    var descriptionGoogle = new BX.UI.Alert({
	      text: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_DESCRIPTION', {
	        '#GOOGLE_API_URL#': this.getValue('googleApiUrl')
	      }),
	      inline: true,
	      size: BX.UI.Alert.Size.SMALL,
	      color: BX.UI.Alert.Color.PRIMARY,
	      animated: true
	    });
	    var descriptionGoogleRow = new ui_section.Row({
	      content: descriptionGoogle.getContainer(),
	      isHidden: this.getValue('cardsProviderProductProperties').current !== 'google'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: descriptionGoogleRow,
	      parent: settingsSection
	    });
	    var googleKeyProductProperties = new ui_formElements_view.TextInput({
	      inputName: 'googleKeyProductProperties',
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAP_PRODUCT_PROPERTIES_GOOGLE_KEY'),
	      value: this.getValue('googleKeyProductProperties'),
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER')
	    });
	    var googleKeyProductPropertiesRow = new ui_section.Row({
	      content: googleKeyProductProperties.render(),
	      isHidden: this.getValue('cardsProviderProductProperties').current !== 'google'
	    });
	    ConfigurationPage.addToSectionHelper(googleKeyProductProperties, settingsSection, googleKeyProductPropertiesRow);
	    cardsProviderProductProperties.getInputNode().addEventListener('change', function (event) {
	      if (event.target.value === 'yandex') {
	        descriptionYandexRow.show();
	        yandexKeyProductPropertiesRow.show();
	        descriptionGoogleRow.hide();
	        googleKeyProductPropertiesRow.hide();
	      } else {
	        descriptionYandexRow.hide();
	        yandexKeyProductPropertiesRow.hide();
	        descriptionGoogleRow.show();
	        googleKeyProductPropertiesRow.show();
	      }
	    });
	  }
	  return settingsSection;
	}
	function _buildAdditionalSettingsSection2() {
	  var _this3 = this;
	  if (!this.hasValue('sectionOther')) {
	    return;
	  }
	  var additionalSettingsSection = new ui_section.Section(this.getValue('sectionOther'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: additionalSettingsSection,
	    parent: this
	  });
	  if (this.hasValue('allowUserInstallApplication')) {
	    var allInstallMarketApplication = new ui_formElements_view.Checker(this.getValue('allowUserInstallApplication'));
	    var allInstallMarketApplicationRow = new ui_section.Row({});
	    main_core_events.EventEmitter.subscribe(allInstallMarketApplication.switcher, 'toggled', function () {
	      var _this3$getAnalytic;
	      (_this3$getAnalytic = _this3.getAnalytic()) === null || _this3$getAnalytic === void 0 ? void 0 : _this3$getAnalytic.addEventConfigConfiguration(AnalyticSettingsEvent.CHANGE_MARKET, allInstallMarketApplication.isChecked());
	    });
	    ConfigurationPage.addToSectionHelper(allInstallMarketApplication, settingsSection, allInstallMarketApplicationRow);
	  }
	  if (this.hasValue('allCanBuyTariff')) {
	    var _this$getValue$title;
	    var messageNode = main_core.Tag.render(_templateObject2$9 || (_templateObject2$9 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE'));
	    var allCanBuyTariff = new ui_formElements_view.Checker({
	      inputName: this.getValue('allCanBuyTariff').inputName,
	      title: (_this$getValue$title = this.getValue('allCanBuyTariff').title) !== null && _this$getValue$title !== void 0 ? _this$getValue$title : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALL_CAN_BUY_TARIFF'),
	      hintOn: this.getValue('allCanBuyTariff').hintOn,
	      checked: this.getValue('allCanBuyTariff').checked,
	      isEnable: this.getValue('allCanBuyTariff').isEnable,
	      bannerCode: 'limit_why_pay_tariff_everyone',
	      helpMessageProvider: this.helpMessageProviderFactory(messageNode)
	    });
	    var allCanBuyTariffRow = new ui_section.Row({});
	    main_core_events.EventEmitter.subscribe(allCanBuyTariff.switcher, 'toggled', function () {
	      var _this3$getAnalytic2;
	      (_this3$getAnalytic2 = _this3.getAnalytic()) === null || _this3$getAnalytic2 === void 0 ? void 0 : _this3$getAnalytic2.addEventConfigConfiguration(AnalyticSettingsEvent.CHANGE_PAY_TARIFF, allCanBuyTariff.isChecked());
	    });
	    ConfigurationPage.addToSectionHelper(allCanBuyTariff, settingsSection, allCanBuyTariffRow);
	  }
	  if (this.hasValue('allowMeasureStressLevel')) {
	    var allowMeasureStressLevel = new ui_formElements_view.Checker(this.getValue('allowMeasureStressLevel'));
	    var allowMeasureStressLevelRow = new ui_section.Row({});
	    ConfigurationPage.addToSectionHelper(allowMeasureStressLevel, settingsSection, allowMeasureStressLevelRow);
	  }
	  if (this.hasValue('collectGeoData')) {
	    var collectGeoData = new ui_formElements_view.Checker(this.getValue('collectGeoData'));
	    main_core_events.EventEmitter.subscribe(collectGeoData.switcher, 'toggled', function () {
	      _classPrivateMethodGet$b(_this3, _geoDataSwitch, _geoDataSwitch2).call(_this3, collectGeoData);
	    });
	    ConfigurationPage.addToSectionHelper(collectGeoData, settingsSection);
	  }

	  // This is hidden
	  // if (this.hasValue('showSettingsAllUsers'))
	  // {
	  // 	let showSettingsAllUsers = new Checker({
	  // 		inputName: 'showSettingsAllUsers',
	  // 		title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_SETTINGS_ALL_USER'),
	  // 		hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_SETTINGS_ALL_USER_CLICK_ON'),
	  // 		checked: this.getValue('showSettingsAllUsers') === 'Y'
	  // 	});
	  // 	let showSettingsAllUsersRow = new Row({
	  // 		content: showSettingsAllUsers.render(),
	  // 		isHidden: true
	  // 	});
	  // 	ConfigurationPage.addToSectionHelper(showSettingsAllUsers, settingsSection, showSettingsAllUsersRow);
	  // }

	  return settingsSection;
	}
	function _geoDataSwitch2(element) {
	  if (element.isChecked()) {
	    BX.UI.Dialogs.MessageBox.show({
	      'modal': true,
	      'minWidth': 640,
	      'title': main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COLLECT_GEO_DATA'),
	      'message': main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COLLECT_GEO_DATA_CONFIRM'),
	      'buttons': BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	      'okCaption': main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COLLECT_GEO_DATA_OK'),
	      'onCancel': function onCancel() {
	        element.switcher.check(false);
	        return true;
	      },
	      'onOk': function onOk() {
	        return true;
	      }
	    });
	  }
	}

	var _templateObject$i, _templateObject2$a, _templateObject3$7;
	function _classPrivateMethodInitSpec$c(obj, privateSet) { _checkPrivateRedeclaration$m(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$m(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$c(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _buildScheduleTab = /*#__PURE__*/new WeakSet();
	var _buildScheduleSection = /*#__PURE__*/new WeakSet();
	var _buildHolidaysSection = /*#__PURE__*/new WeakSet();
	var _forDepartmentsRender = /*#__PURE__*/new WeakSet();
	var SchedulePage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(SchedulePage, _BaseSettingsPage);
	  function SchedulePage() {
	    var _this;
	    babelHelpers.classCallCheck(this, SchedulePage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SchedulePage).call(this));
	    _classPrivateMethodInitSpec$c(babelHelpers.assertThisInitialized(_this), _forDepartmentsRender);
	    _classPrivateMethodInitSpec$c(babelHelpers.assertThisInitialized(_this), _buildHolidaysSection);
	    _classPrivateMethodInitSpec$c(babelHelpers.assertThisInitialized(_this), _buildScheduleSection);
	    _classPrivateMethodInitSpec$c(babelHelpers.assertThisInitialized(_this), _buildScheduleTab);
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_SCHEDULE');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_SCHEDULE');
	    return _this;
	  }
	  babelHelpers.createClass(SchedulePage, [{
	    key: "getType",
	    value: function getType() {
	      return 'schedule';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var scheduleSection = _classPrivateMethodGet$c(this, _buildScheduleSection, _buildScheduleSection2).call(this);
	      scheduleSection.renderTo(contentNode);
	      var holidaysSection = _classPrivateMethodGet$c(this, _buildHolidaysSection, _buildHolidaysSection2).call(this);
	      holidaysSection.renderTo(contentNode);
	    }
	  }]);
	  return SchedulePage;
	}(ui_formElements_field.BaseSettingsPage);
	function _buildScheduleTab2(parent) {
	  var workTimeRow = new ui_section.Row({
	    className: 'intranet-settings__work-time_container --no-padding'
	  });
	  var settingsRow = new ui_formElements_field.SettingsRow({
	    row: workTimeRow,
	    parent: parent
	  });
	  if (this.hasValue('WORK_TIME_START')) {
	    var workTimeStartField = new ui_formElements_view.Selector(this.getValue('WORK_TIME_START'));
	    new ui_formElements_field.SettingsRow({
	      child: new ui_formElements_field.SettingsField({
	        fieldView: workTimeStartField
	      }),
	      parent: settingsRow,
	      row: new ui_section.Row({
	        className: 'intranet-settings__work-time_row'
	      })
	    });
	  }
	  new ui_formElements_field.SettingsRow({
	    row: new ui_section.Row({
	      className: 'ui-section__field-inline-separator'
	    }),
	    parent: settingsRow
	  });
	  if (this.hasValue('WORK_TIME_END')) {
	    var workTimeEndField = new ui_formElements_view.Selector(this.getValue('WORK_TIME_END'));
	    new ui_formElements_field.SettingsRow({
	      child: new ui_formElements_field.SettingsField({
	        fieldView: workTimeEndField
	      }),
	      parent: settingsRow,
	      row: new ui_section.Row({
	        className: 'intranet-settings__work-time_row'
	      })
	    });
	  }
	  var containerTab = main_core.Tag.render(_templateObject$i || (_templateObject$i = babelHelpers.taggedTemplateLiteral(["<div><div>"])));
	  main_core.Dom.append(workTimeRow.render(), containerTab);
	  if (this.hasValue('WEEK_DAYS')) {
	    var _this$getValue$label;
	    var itemPickerField = new ui_formElements_view.ItemPicker({
	      inputName: this.getValue('WEEK_DAYS').inputName,
	      isMulti: true,
	      label: (_this$getValue$label = this.getValue('WEEK_DAYS').label) !== null && _this$getValue$label !== void 0 ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEKEND'),
	      items: this.getValue('WEEK_DAYS').values,
	      current: this.getValue('WEEK_DAYS').multiValue
	    });
	    var settingsField = new ui_formElements_field.SettingsField({
	      fieldView: itemPickerField
	    });
	    var itemPickerRow = new ui_section.Row({
	      content: itemPickerField.render()
	    });
	    new ui_formElements_field.SettingsRow({
	      row: itemPickerRow,
	      child: settingsField,
	      parent: parent
	    });
	    main_core.Dom.append(itemPickerRow.render(), containerTab);
	  }
	  if (this.hasValue('WEEK_START')) {
	    var weekStartField = new ui_formElements_view.ItemPicker(this.getValue('WEEK_START'));
	    var _settingsField = new ui_formElements_field.SettingsField({
	      fieldView: weekStartField
	    });
	    main_core.Dom.addClass(weekStartField.render(), '--row-frame_gray');
	    var weekStartRow = new ui_section.Row({
	      content: weekStartField.render()
	    });
	    new ui_formElements_field.SettingsRow({
	      row: weekStartRow,
	      child: _settingsField,
	      parent: parent
	    });
	    main_core.Dom.append(weekStartRow.render(), containerTab);
	  }
	  return containerTab;
	}
	function _buildScheduleSection2() {
	  if (!this.hasValue('sectionSchedule')) {
	    return;
	  }
	  var scheduleSection = new ui_section.Section(this.getValue('sectionSchedule'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    parent: this,
	    section: scheduleSection
	  });
	  var tabsRow = new ui_formElements_field.SettingsRow({
	    parent: settingsSection
	  });
	  var tabsField = new ui_formElements_field.TabsField({
	    parent: tabsRow
	  });
	  var forCompanyTab = new ui_formElements_field.TabField({
	    parent: tabsField,
	    tabsOptions: this.getValue('tabForCompany')
	  });
	  _classPrivateMethodGet$c(this, _buildScheduleTab, _buildScheduleTab2).call(this, forCompanyTab);
	  if (this.getValue('TIMEMAN').enabled) {
	    var forDepartmentTab = new ui_formElements_field.TabField({
	      parent: tabsField,
	      tabsOptions: this.getValue('tabForDepartment')
	    });
	    var forDepartmentRow = new ui_section.Row({
	      content: _classPrivateMethodGet$c(this, _forDepartmentsRender, _forDepartmentsRender2).call(this)
	    });
	    new ui_formElements_field.SettingsRow({
	      row: forDepartmentRow,
	      parent: forDepartmentTab
	    });
	  }
	  tabsField.activateTab(forCompanyTab);
	  //endregion

	  return settingsSection;
	}
	function _buildHolidaysSection2() {
	  var _this$getValue$value$, _this$getValue, _this$getValue$value, _this$getValue$value$2;
	  if (!this.hasValue('sectionHoliday')) {
	    return;
	  }
	  var holidaysSection = new ui_section.Section(this.getValue('sectionHoliday'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    parent: this,
	    section: holidaysSection
	  });
	  var countDays = (_this$getValue$value$ = (_this$getValue = this.getValue('year_holidays')) === null || _this$getValue === void 0 ? void 0 : (_this$getValue$value = _this$getValue.value) === null || _this$getValue$value === void 0 ? void 0 : (_this$getValue$value$2 = _this$getValue$value.match(/\d{1,2}.\d{1,2}/gm)) === null || _this$getValue$value$2 === void 0 ? void 0 : _this$getValue$value$2.length) !== null && _this$getValue$value$ !== void 0 ? _this$getValue$value$ : 0;
	  var countDaysNode = main_core.Tag.render(_templateObject2$a || (_templateObject2$a = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-section__field-label\">", "</div>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_INFO', {
	    '#COUNT_DAYS#': countDays
	  }));
	  var holidaysRow = new ui_section.Row({
	    content: countDaysNode
	  });
	  holidaysSection.append(holidaysRow.render());
	  if (this.hasValue('year_holidays')) {
	    var holidaysField = new ui_formElements_view.TextInput(this.getValue('year_holidays'));
	    SchedulePage.addToSectionHelper(holidaysField, settingsSection);
	    main_core.Event.bind(holidaysField.getInputNode(), 'keyup', function () {
	      var _holidaysField$getInp, _holidaysField$getInp2, _holidaysField$getInp3;
	      var count = (_holidaysField$getInp = holidaysField === null || holidaysField === void 0 ? void 0 : (_holidaysField$getInp2 = holidaysField.getInputNode().value) === null || _holidaysField$getInp2 === void 0 ? void 0 : (_holidaysField$getInp3 = _holidaysField$getInp2.match(/\d{1,2}.\d{1,2}/gm)) === null || _holidaysField$getInp3 === void 0 ? void 0 : _holidaysField$getInp3.length) !== null && _holidaysField$getInp !== void 0 ? _holidaysField$getInp : 0;
	      countDaysNode.innerHTML = main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_INFO', {
	        '#COUNT_DAYS#': count
	      });
	    });
	  }
	  return settingsSection;
	}
	function _forDepartmentsRender2() {
	  return main_core.Tag.render(_templateObject3$7 || (_templateObject3$7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"intranet-settings__tab-info_container\">\n\t\t\t\t<div class=\"intranet-settings__tab-info_text\">", "</div>\n\t\t\t\t<a href=\"/timeman/schedules/\" class=\"ui-section__link\" target=\"_blank\">", "</a>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_FOR_DEPARTMENTS'), main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_FOR_DEPARTMENTS_CONFIG'));
	}

	var _templateObject$j;
	function _classPrivateMethodInitSpec$d(obj, privateSet) { _checkPrivateRedeclaration$n(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$n(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$d(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _buildGdprSection = /*#__PURE__*/new WeakSet();
	var GdprPage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(GdprPage, _BaseSettingsPage);
	  function GdprPage() {
	    var _this;
	    babelHelpers.classCallCheck(this, GdprPage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(GdprPage).call(this));
	    _classPrivateMethodInitSpec$d(babelHelpers.assertThisInitialized(_this), _buildGdprSection);
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_GDPR');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_GDPR');
	    return _this;
	  }
	  babelHelpers.createClass(GdprPage, [{
	    key: "getType",
	    value: function getType() {
	      return 'gdpr';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var gdprSection = _classPrivateMethodGet$d(this, _buildGdprSection, _buildGdprSection2).call(this);
	      gdprSection === null || gdprSection === void 0 ? void 0 : gdprSection.renderTo(contentNode);
	    }
	  }, {
	    key: "addApplicationsRender",
	    value: function addApplicationsRender() {
	      if (this.hasValue('marketDirectory')) {
	        var marketDirectory = this.getValue('marketDirectory');
	        return main_core.Tag.render(_templateObject$j || (_templateObject$j = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-text-right\">\n\t\t\t\t\t<a class=\"ui-section__link\" href=\"", "detail/integrations24.gdprstaff/\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t\t<a class=\"ui-section__link\" style=\"margin-left: 12px;\" href=\"", "detail/integrations24.gdpr/\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t"])), marketDirectory, main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_APPLICATION_EMPLOYEE'), marketDirectory, main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_APPLICATION_CRM'));
	      }
	      return null;
	    }
	  }]);
	  return GdprPage;
	}(ui_formElements_field.BaseSettingsPage);
	function _buildGdprSection2() {
	  if (!this.hasValue('sectionGdpr')) {
	    return;
	  }
	  var gdprSection = new ui_section.Section(this.getValue('sectionGdpr'));
	  var sectionSettings = new ui_formElements_field.SettingsSection({
	    section: gdprSection,
	    parent: this
	  });
	  var description = new BX.UI.Alert({
	    text: "\n\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_GDPR_DESCRIPTION'), "\n\t\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=7608199')\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t\t</a>\n\t\t\t\t</br>\n\t\t\t\t<a class=\"ui-section__link\" href=\"").concat(this.getValue('dpaLink'), "\" target=\"_blank\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_AGREEMENT'), "\n\t\t\t\t</a>\n\t\t\t"),
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	  var descriptionRow = new ui_section.Row({
	    content: description.getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: sectionSettings
	  });
	  if (this.hasValue('companyTitle')) {
	    var _this$getValue$label;
	    var titleField = new ui_formElements_view.TextInput({
	      inputName: 'companyTitle',
	      label: (_this$getValue$label = this.getValue('companyTitle').label) !== null && _this$getValue$label !== void 0 ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COMPANY_TITLE'),
	      value: this.getValue('companyTitle').value,
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_COMPANY_TITLE')
	    });
	    var settingsField = new ui_formElements_field.SettingsField({
	      fieldView: titleField
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: sectionSettings,
	      child: settingsField
	    });
	  }
	  if (this.hasValue('contactName')) {
	    var _this$getValue$label2;
	    var contactNameField = new ui_formElements_view.TextInput({
	      inputName: 'contactName',
	      label: (_this$getValue$label2 = this.getValue('contactName').label) !== null && _this$getValue$label2 !== void 0 ? _this$getValue$label2 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CONTACT_NAME'),
	      value: this.getValue('contactName').value,
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_CONTACT_NAME')
	    });
	    var _settingsField = new ui_formElements_field.SettingsField({
	      fieldView: contactNameField
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: sectionSettings,
	      child: _settingsField
	    });
	  }
	  if (this.hasValue('notificationEmail')) {
	    var _this$getValue$label3;
	    var emailField = new ui_formElements_view.TextInput({
	      inputName: 'notificationEmail',
	      label: (_this$getValue$label3 = this.getValue('notificationEmail').label) !== null && _this$getValue$label3 !== void 0 ? _this$getValue$label3 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_NOTIFICATION_EMAIL'),
	      value: this.getValue('notificationEmail').value,
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_NOTIFICATION_EMAIL')
	    });
	    var _settingsField2 = new ui_formElements_field.SettingsField({
	      fieldView: emailField
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: sectionSettings,
	      child: _settingsField2
	    });
	  }
	  if (this.hasValue('date')) {
	    var _this$getValue$label4;
	    var dateField = new ui_formElements_view.TextInput({
	      inputName: 'date',
	      label: (_this$getValue$label4 = this.getValue('date').label) !== null && _this$getValue$label4 !== void 0 ? _this$getValue$label4 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_DATE'),
	      value: this.getValue('date').value
	    });
	    main_core.Dom.adjust(dateField.render(), {
	      events: {
	        click: function click(event) {
	          BX.calendar({
	            node: event.target,
	            field: 'date',
	            form: '',
	            bTime: false,
	            bHideTime: true
	          });
	        }
	      }
	    });
	    var _settingsField3 = new ui_formElements_field.SettingsField({
	      fieldView: dateField
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: sectionSettings,
	      child: _settingsField3
	    });
	  }
	  new ui_formElements_field.SettingsRow({
	    row: new ui_section.Row({
	      content: this.addApplicationsRender()
	    }),
	    parent: sectionSettings
	  });
	  return sectionSettings;
	}

	var _templateObject$k, _templateObject2$b, _templateObject3$8, _templateObject4$1, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9;
	function _createForOfIteratorHelper$4(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$4(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$4(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$4(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$4(o, minLen); }
	function _arrayLikeToArray$4(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec$e(obj, privateSet) { _checkPrivateRedeclaration$o(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$j(obj, privateMap, value) { _checkPrivateRedeclaration$o(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$o(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$e(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _otpChecker = /*#__PURE__*/new WeakMap();
	var _otpSelector = /*#__PURE__*/new WeakMap();
	var _otpPopup = /*#__PURE__*/new WeakMap();
	var _buildOTPSection = /*#__PURE__*/new WeakSet();
	var _getOTPChecker = /*#__PURE__*/new WeakSet();
	var _getOTPPopup = /*#__PURE__*/new WeakSet();
	var _getOTPPeriodSelector = /*#__PURE__*/new WeakSet();
	var _getOTPDescription = /*#__PURE__*/new WeakSet();
	var _getOTPDescriptionText = /*#__PURE__*/new WeakSet();
	var _buildAccessIPSection = /*#__PURE__*/new WeakSet();
	var _getEmptyUserSelectorRow = /*#__PURE__*/new WeakSet();
	var _getEmptyAccessIpRow = /*#__PURE__*/new WeakSet();
	var _getUserSelectorRow = /*#__PURE__*/new WeakSet();
	var _getAccessIpRow = /*#__PURE__*/new WeakSet();
	var _getIpAccessDescription = /*#__PURE__*/new WeakSet();
	var _buildPasswordRecoverySection = /*#__PURE__*/new WeakSet();
	var _buildDevicesHistorySection = /*#__PURE__*/new WeakSet();
	var _buildEventLogSection = /*#__PURE__*/new WeakSet();
	var _buildBlackListSection = /*#__PURE__*/new WeakSet();
	var _buildMobileAppSection = /*#__PURE__*/new WeakSet();
	var SecurityPage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(SecurityPage, _BaseSettingsPage);
	  function SecurityPage() {
	    var _this;
	    babelHelpers.classCallCheck(this, SecurityPage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SecurityPage).call(this));
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _buildMobileAppSection);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _buildBlackListSection);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _buildEventLogSection);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _buildDevicesHistorySection);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _buildPasswordRecoverySection);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getIpAccessDescription);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getAccessIpRow);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getUserSelectorRow);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getEmptyAccessIpRow);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getEmptyUserSelectorRow);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _buildAccessIPSection);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getOTPDescriptionText);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getOTPDescription);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getOTPPeriodSelector);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getOTPPopup);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _getOTPChecker);
	    _classPrivateMethodInitSpec$e(babelHelpers.assertThisInitialized(_this), _buildOTPSection);
	    _classPrivateFieldInitSpec$j(babelHelpers.assertThisInitialized(_this), _otpChecker, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$j(babelHelpers.assertThisInitialized(_this), _otpSelector, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$j(babelHelpers.assertThisInitialized(_this), _otpPopup, {
	      writable: true,
	      value: void 0
	    });
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_SECURITY');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_SECURITY');
	    return _this;
	  }
	  babelHelpers.createClass(SecurityPage, [{
	    key: "getType",
	    value: function getType() {
	      return 'security';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var _classPrivateMethodGe2, _classPrivateMethodGe3, _classPrivateMethodGe4;
	      var isBitrix24 = this.hasValue('IS_BITRIX_24') && this.getValue('IS_BITRIX_24');
	      if (this.hasValue('SECURITY_OTP_ENABLED') && this.getValue('SECURITY_OTP_ENABLED')) {
	        var _classPrivateMethodGe;
	        (_classPrivateMethodGe = _classPrivateMethodGet$e(this, _buildOTPSection, _buildOTPSection2).call(this)) === null || _classPrivateMethodGe === void 0 ? void 0 : _classPrivateMethodGe.renderTo(contentNode);
	      }

	      // if (isBitrix24)
	      // {
	      // 	this.#buildPasswordRecoverySection().renderTo(contentNode);
	      // }
	      (_classPrivateMethodGe2 = _classPrivateMethodGet$e(this, _buildDevicesHistorySection, _buildDevicesHistorySection2).call(this)) === null || _classPrivateMethodGe2 === void 0 ? void 0 : _classPrivateMethodGe2.renderTo(contentNode);
	      (_classPrivateMethodGe3 = _classPrivateMethodGet$e(this, _buildEventLogSection, _buildEventLogSection2).call(this)) === null || _classPrivateMethodGe3 === void 0 ? void 0 : _classPrivateMethodGe3.renderTo(contentNode);
	      (_classPrivateMethodGe4 = _classPrivateMethodGet$e(this, _buildMobileAppSection, _buildMobileAppSection2).call(this)) === null || _classPrivateMethodGe4 === void 0 ? void 0 : _classPrivateMethodGe4.renderTo(contentNode);
	      if (isBitrix24) {
	        var _classPrivateMethodGe5, _classPrivateMethodGe6;
	        (_classPrivateMethodGe5 = _classPrivateMethodGet$e(this, _buildAccessIPSection, _buildAccessIPSection2).call(this)) === null || _classPrivateMethodGe5 === void 0 ? void 0 : _classPrivateMethodGe5.renderTo(contentNode);
	        (_classPrivateMethodGe6 = _classPrivateMethodGet$e(this, _buildBlackListSection, _buildBlackListSection2).call(this)) === null || _classPrivateMethodGe6 === void 0 ? void 0 : _classPrivateMethodGe6.renderTo(contentNode);
	      }
	    }
	  }]);
	  return SecurityPage;
	}(ui_formElements_field.BaseSettingsPage);
	function _buildOTPSection2() {
	  var _this2 = this;
	  if (!this.hasValue('sectionOtp')) {
	    return;
	  }
	  var otpSection = new ui_section.Section(this.getValue('sectionOtp'));
	  var section = new ui_formElements_field.SettingsSection({
	    section: otpSection,
	    parent: this
	  });
	  var descriptionRow = new ui_section.Row({
	    content: _classPrivateMethodGet$e(this, _getOTPDescription, _getOTPDescription2).call(this).getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: section
	  });
	  if (this.hasValue('SECURITY_OTP') && this.hasValue('SEND_OTP_PUSH')) {
	    var securityOtpCheckerRow = new ui_section.Row({
	      content: _classPrivateMethodGet$e(this, _getOTPChecker, _getOTPChecker2).call(this).render(),
	      separator: _classPrivateMethodGet$e(this, _getOTPChecker, _getOTPChecker2).call(this).isChecked() ? '' : 'bottom',
	      className: _classPrivateMethodGet$e(this, _getOTPChecker, _getOTPChecker2).call(this).isChecked() ? '' : '--block'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: securityOtpCheckerRow,
	      parent: section
	    });
	    var securityOtpPeriodSelectorRow = new ui_section.Row({
	      content: _classPrivateMethodGet$e(this, _getOTPPeriodSelector, _getOTPPeriodSelector2).call(this).render(),
	      isHidden: !_classPrivateMethodGet$e(this, _getOTPChecker, _getOTPChecker2).call(this).isChecked()
	    });
	    new ui_formElements_field.SettingsRow({
	      row: securityOtpPeriodSelectorRow,
	      parent: section
	    });
	    var switcherWrapper = main_core.Tag.render(_templateObject$k || (_templateObject$k = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"settings-switcher-wrapper\">\n\t\t\t\t\t<div class=\"settings-security-message-switcher\"/>\n\t\t\t\t</div>\n\t\t\t"])));
	    new ui_formElements_view.SingleChecker({
	      switcher: new ui_switcher.Switcher({
	        node: switcherWrapper.querySelector('.settings-security-message-switcher'),
	        inputName: 'SEND_OTP_PUSH',
	        checked: this.getValue('SEND_OTP_PUSH'),
	        size: ui_switcher.SwitcherSize.small
	      })
	    });
	    var securityOtpMessageChatCheckerRow = new ui_section.Row({
	      content: switcherWrapper,
	      isHidden: !_classPrivateMethodGet$e(this, _getOTPChecker, _getOTPChecker2).call(this).isChecked()
	    });
	    switcherWrapper.append(main_core.Tag.render(_templateObject2$b || (_templateObject2$b = babelHelpers.taggedTemplateLiteral(["<span class=\"settings-switcher-title\">", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_OTP_SWITCHING_MESSAGE_CHAT')));
	    new ui_formElements_field.SettingsRow({
	      row: securityOtpMessageChatCheckerRow,
	      parent: section
	    });
	    main_core_events.EventEmitter.subscribe(_classPrivateMethodGet$e(this, _getOTPChecker, _getOTPChecker2).call(this).switcher, 'toggled', function () {
	      if (_this2.getValue('SECURITY_IS_USER_OTP_ACTIVE') !== true && _classPrivateMethodGet$e(_this2, _getOTPChecker, _getOTPChecker2).call(_this2).isChecked()) {
	        _classPrivateMethodGet$e(_this2, _getOTPPopup, _getOTPPopup2).call(_this2).show();
	        _classPrivateMethodGet$e(_this2, _getOTPChecker, _getOTPChecker2).call(_this2).cancel();
	        _classPrivateMethodGet$e(_this2, _getOTPChecker, _getOTPChecker2).call(_this2).switcher.check(false);
	        return;
	      }
	      if (_this2.hasValue('SECURITY_OTP_ENABLED') && _this2.getValue('SECURITY_OTP_ENABLED')) {
	        var _this2$getAnalytic;
	        (_this2$getAnalytic = _this2.getAnalytic()) === null || _this2$getAnalytic === void 0 ? void 0 : _this2$getAnalytic.addEventToggle2fa(_classPrivateMethodGet$e(_this2, _getOTPChecker, _getOTPChecker2).call(_this2).isChecked());
	      }
	      if (_classPrivateMethodGet$e(_this2, _getOTPChecker, _getOTPChecker2).call(_this2).isChecked()) {
	        main_core.Dom.removeClass(securityOtpCheckerRow.render(), '--bottom-separator --block');
	        securityOtpPeriodSelectorRow.show();
	        securityOtpMessageChatCheckerRow.show();
	      } else {
	        main_core.Dom.addClass(securityOtpCheckerRow.render(), '--bottom-separator --block');
	        securityOtpPeriodSelectorRow.hide();
	        securityOtpMessageChatCheckerRow.hide();
	      }
	    });
	  }
	  return section;
	}
	function _getOTPChecker2() {
	  if (babelHelpers.classPrivateFieldGet(this, _otpChecker) instanceof ui_formElements_view.Checker) {
	    return babelHelpers.classPrivateFieldGet(this, _otpChecker);
	  }
	  if (this.hasValue('fieldSecurityOtp')) {
	    babelHelpers.classPrivateFieldSet(this, _otpChecker, new ui_formElements_view.Checker({
	      inputName: this.getValue('fieldSecurityOtp').inputName,
	      checked: this.getValue('fieldSecurityOtp').checked,
	      title: this.getValue('fieldSecurityOtp').title,
	      isEnable: this.getValue('fieldSecurityOtp').isEnable,
	      hideSeparator: true,
	      alignCenter: true,
	      noMarginBottom: true
	    }));
	  }
	  babelHelpers.classPrivateFieldGet(this, _otpChecker).renderLockElement = function () {
	    return null;
	  };
	  return babelHelpers.classPrivateFieldGet(this, _otpChecker);
	}
	function _getOTPPopup2() {
	  var _this3 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _otpPopup) instanceof main_popup.Popup) {
	    return babelHelpers.classPrivateFieldGet(this, _otpPopup);
	  }
	  var popupDescription = main_core.Tag.render(_templateObject3$8 || (_templateObject3$8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"intranet-settings__security_popup_info\">\n\t\t\t\t", "\n\t\t\t</div>\t\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_POPUP_OTP_ENABLE'));
	  var popupButton = new BX.UI.Button({
	    text: main_core.Loc.getMessage('INTRANET_SETTINGS_POPUP_OTP_ENABLE_BUTTON'),
	    color: BX.UI.Button.Color.PRIMARY,
	    events: {
	      click: function click() {
	        _classPrivateMethodGet$e(_this3, _getOTPPopup, _getOTPPopup2).call(_this3).close();
	        BX.SidePanel.Instance.open(_this3.getValue('SECURITY_OTP_PATH'));
	      }
	    }
	  });
	  var popupContent = main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"intranet-settings__security_popup_container\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"ui-btn-container ui-btn-container-center\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\t\t\t\n\t\t\t</div>\n\t\t"])), popupDescription, popupButton.getContainer());
	  babelHelpers.classPrivateFieldSet(this, _otpPopup, new main_popup.Popup({
	    bindElement: babelHelpers.classPrivateFieldGet(this, _otpChecker).getInputNode(),
	    content: popupContent,
	    autoHide: true,
	    width: 337,
	    angle: {
	      offset: 200 - 15
	    },
	    offsetLeft: babelHelpers.classPrivateFieldGet(this, _otpChecker).getInputNode().offsetWidth - 200 + 15,
	    closeByEsc: true,
	    borderRadius: 18
	  }));
	  return babelHelpers.classPrivateFieldGet(this, _otpPopup);
	}
	function _getOTPPeriodSelector2() {
	  if (babelHelpers.classPrivateFieldGet(this, _otpSelector) instanceof ui_formElements_view.Selector) {
	    return babelHelpers.classPrivateFieldGet(this, _otpSelector);
	  }
	  babelHelpers.classPrivateFieldSet(this, _otpSelector, new ui_formElements_view.Selector({
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_OTP_SWITCHING_PERIOD'),
	    name: 'SECURITY_OTP_DAYS',
	    items: this.getValue('SECURITY_OTP_DAYS').ITEMS,
	    current: this.getValue('SECURITY_OTP_DAYS').CURRENT
	  }));
	  return babelHelpers.classPrivateFieldGet(this, _otpSelector);
	}
	function _getOTPDescription2() {
	  return new BX.UI.Alert({
	    text: _classPrivateMethodGet$e(this, _getOTPDescriptionText, _getOTPDescriptionText2).call(this),
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	}
	function _getOTPDescriptionText2() {
	  return "\n\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_FIRST'), "\n\t\t</br></br>\n\t\t<span class=\"settings-section-description-focus-text --security-info\">\n\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_SECOND'), "\n\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=17728602')\">\n\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t</a>\n\t\t</span>");
	}
	function _buildAccessIPSection2() {
	  var _this4 = this;
	  if (!this.hasValue('sectionAccessIp')) {
	    return;
	  }
	  var accessIpSection = new ui_section.Section(this.getValue('sectionAccessIp'));
	  var section = new ui_formElements_field.SettingsSection({
	    section: accessIpSection,
	    parent: this
	  });
	  var descriptionRow = new ui_section.Row({
	    content: _classPrivateMethodGet$e(this, _getIpAccessDescription, _getIpAccessDescription2).call(this).getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: section
	  });
	  var fieldsCount = 0;
	  if (this.hasValue('IP_ACCESS_RIGHTS')) {
	    var _iterator = _createForOfIteratorHelper$4(this.getValue('IP_ACCESS_RIGHTS')),
	      _step;
	    try {
	      for (_iterator.s(); !(_step = _iterator.n()).done;) {
	        var ipUsersList = _step.value;
	        fieldsCount++;
	        new ui_formElements_field.SettingsRow({
	          parent: section,
	          child: _classPrivateMethodGet$e(this, _getUserSelectorRow, _getUserSelectorRow2).call(this, ipUsersList)
	        });
	        new ui_formElements_field.SettingsRow({
	          parent: section,
	          child: _classPrivateMethodGet$e(this, _getAccessIpRow, _getAccessIpRow2).call(this, ipUsersList)
	        });
	      }
	    } catch (err) {
	      _iterator.e(err);
	    } finally {
	      _iterator.f();
	    }
	  }
	  if (fieldsCount === 0) {
	    fieldsCount++;
	    new ui_formElements_field.SettingsRow({
	      parent: section,
	      child: _classPrivateMethodGet$e(this, _getEmptyUserSelectorRow, _getEmptyUserSelectorRow2).call(this, fieldsCount)
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: section,
	      child: _classPrivateMethodGet$e(this, _getEmptyAccessIpRow, _getEmptyAccessIpRow2).call(this, fieldsCount)
	    });
	  }
	  var onclickAddField = function onclickAddField() {
	    if (_this4.getValue('IP_ACCESS_RIGHTS_ENABLED')) {
	      fieldsCount++;
	      var emptyUserSelectorRow = new ui_section.Row({
	        content: _classPrivateMethodGet$e(_this4, _getEmptyUserSelectorRow, _getEmptyUserSelectorRow2).call(_this4, fieldsCount).render()
	      });
	      main_core.Dom.insertBefore(emptyUserSelectorRow.render(), additionalUsersAccessIpButton.parentElement);
	      var emptyAccessIpRow = new ui_section.Row({
	        content: _classPrivateMethodGet$e(_this4, _getEmptyAccessIpRow, _getEmptyAccessIpRow2).call(_this4, fieldsCount).render()
	      });
	      main_core.Dom.insertBefore(emptyAccessIpRow.render(), additionalUsersAccessIpButton.parentElement);
	    } else {
	      BX.UI.InfoHelper.show('limit_admin_ip');
	    }
	  };
	  var additionalUsersAccessIpButton = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-text-right\">\n\t\t\t\t<a class=\"ui-section__link\" href=\"javascript:void(0)\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t</div>\n\t\t"])), onclickAddField, main_core.Loc.getMessage('INTRANET_SETTINGS_ADDITIONAL_USER_ACCESS_IP'));
	  new ui_formElements_field.SettingsRow({
	    row: new ui_section.Row({
	      content: additionalUsersAccessIpButton
	    }),
	    parent: section
	  });
	  return section;
	}
	function _getEmptyUserSelectorRow2(fieldNumber) {
	  var userSelector = new ui_formElements_view.UserSelector({
	    inputName: "SECURITY_IP_ACCESS_".concat(fieldNumber, "_USERS[]"),
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_ACCESS_IP'),
	    enableDepartments: true,
	    encodeValue: function encodeValue(value) {
	      if (!main_core.Type.isNil(value.id)) {
	        return value.id === 'all-users' ? 'AU' : value.type + value.id.toString().split(':')[0];
	      }
	      return null;
	    },
	    isEnable: this.getValue('IP_ACCESS_RIGHTS_ENABLED'),
	    helpMessageProvider: this.helpMessageProviderFactory(main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_PRO'))
	  });
	  return new ui_formElements_field.SettingsField({
	    fieldView: userSelector
	  });
	}
	function _getEmptyAccessIpRow2(fieldNumber) {
	  var _this$getValue;
	  var inputName = "SECURITY_IP_ACCESS_".concat(fieldNumber, "_IP");
	  var accessIp = new ui_formElements_view.TextInput({
	    inputName: inputName,
	    label: (_this$getValue = this.getValue('IP_ACCESS_RIGHTS_ENABLED_LABEL')) !== null && _this$getValue !== void 0 ? _this$getValue : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_ACCEPTED_IP'),
	    isEnable: this.getValue('IP_ACCESS_RIGHTS_ENABLED'),
	    helpMessageProvider: this.helpMessageProviderFactory(main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_PRO'))
	  });
	  return new ui_formElements_field.SettingsField({
	    fieldView: accessIp
	  });
	}
	function _getUserSelectorRow2(ipUsersList) {
	  var _this$getValue2;
	  var userSelector = new ui_formElements_view.UserSelector({
	    inputName: "SECURITY_IP_ACCESS_".concat(ipUsersList.fieldNumber, "_USERS[]"),
	    label: (_this$getValue2 = this.getValue('IP_ACCESS_RIGHTS_ENABLED_LABEL')) !== null && _this$getValue2 !== void 0 ? _this$getValue2 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_ACCESS_IP'),
	    values: Object.values(ipUsersList.users),
	    enableDepartments: true,
	    encodeValue: function encodeValue(value) {
	      if (!main_core.Type.isNil(value.id)) {
	        return value.id === 'all-users' ? 'AU' : value.type + value.id.toString().split(':')[0];
	      }
	      return null;
	    },
	    decodeValue: function decodeValue(value) {
	      if (value === 'AU') {
	        return {
	          type: value,
	          id: ''
	        };
	      }
	      var arr = value.match(/^(U|DR|D)(\d+)/);
	      if (!main_core.Type.isArray(arr)) {
	        return {
	          type: null,
	          id: null
	        };
	      }
	      return {
	        type: arr[1],
	        id: arr[2]
	      };
	    }
	  });
	  return new ui_formElements_field.SettingsField({
	    fieldView: userSelector
	  });
	}
	function _getAccessIpRow2(ipUsersList) {
	  var inputName = "SECURITY_IP_ACCESS_".concat(ipUsersList.fieldNumber, "_IP");
	  var accessIp = new ui_formElements_view.TextInput({
	    inputName: inputName,
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_ACCEPTED_IP'),
	    value: ipUsersList.ip
	  });
	  return new ui_formElements_field.SettingsField({
	    fieldView: accessIp
	  });
	}
	function _getIpAccessDescription2() {
	  return new BX.UI.Alert({
	    text: "\n\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_IP_ACCESS', {
	      '#ARTICLE_CODE#': 'redirect=detail&code=17300230'
	    }), "\n\t\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=17300230')\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t\t</a>\n\t\t\t"),
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	}
	function _buildDevicesHistorySection2() {
	  var _this5 = this;
	  if (!this.hasValue('sectionHistory')) {
	    return;
	  }
	  var devicesHistorySection = new ui_section.Section(this.getValue('sectionHistory'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: devicesHistorySection,
	    parent: this
	  });
	  var devicesHistoryDescription = new BX.UI.Alert({
	    text: "\n\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_DEVICE_HISTORY'), "\n\t\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=16623484')\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t\t</a>\n\t\t\t"),
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	  var descriptionRow = new ui_section.Row({
	    content: devicesHistoryDescription.getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: settingsSection
	  });
	  if (this.hasValue('DEVICE_HISTORY_SETTINGS')) {
	    var messageNode = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_ENT', {
	      '#TARIFF#': 'ent250'
	    }));
	    var cleanupDaysField = new ui_formElements_view.Selector({
	      label: this.getValue('DEVICE_HISTORY_SETTINGS').label,
	      name: this.getValue('DEVICE_HISTORY_SETTINGS').name,
	      items: this.getValue('DEVICE_HISTORY_SETTINGS').values,
	      current: this.getValue('DEVICE_HISTORY_SETTINGS').current,
	      isEnable: this.getValue('DEVICE_HISTORY_SETTINGS').isEnable,
	      bannerCode: 'limit_office_login_history',
	      helpMessageProvider: this.helpMessageProviderFactory(messageNode)
	    });
	    if (!this.getValue('DEVICE_HISTORY_SETTINGS').isEnable) {
	      main_core.Event.bind(cleanupDaysField.getInputNode(), 'click', function () {
	        var _this5$getAnalytic;
	        (_this5$getAnalytic = _this5.getAnalytic()) === null || _this5$getAnalytic === void 0 ? void 0 : _this5$getAnalytic.addEventOpenHint(_this5.getValue('DEVICE_HISTORY_SETTINGS').name);
	      });
	      main_core.Event.bind(messageNode.querySelector('a'), 'click', function () {
	        var _this5$getAnalytic2;
	        return (_this5$getAnalytic2 = _this5.getAnalytic()) === null || _this5$getAnalytic2 === void 0 ? void 0 : _this5$getAnalytic2.addEventOpenTariffSelector(_this5.getValue('DEVICE_HISTORY_SETTINGS').name);
	      });
	    }
	    SecurityPage.addToSectionHelper(cleanupDaysField, settingsSection);
	  }
	  var goToUserListButton = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-text-right\">\n\t\t\t\t<a class=\"ui-section__link\" href=\"/company/\" target=\"_blank\">\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_GO_TO_USER_LIST_LINK'));
	  new ui_formElements_field.SettingsRow({
	    row: new ui_section.Row({
	      content: goToUserListButton
	    }),
	    parent: settingsSection
	  });
	  return settingsSection;
	}
	function _buildEventLogSection2() {
	  if (!this.hasValue('sectionEventLog')) {
	    return;
	  }
	  var eventLogSection = new ui_section.Section(this.getValue('sectionEventLog'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: eventLogSection,
	    parent: this
	  });
	  var eventLogDescription = new BX.UI.Alert({
	    text: "\n\t\t\t\t".concat(main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_EVENT_LOG'), "\n\t\t\t\t<a class=\"ui-section__link\" onclick=\"top.BX.Helper.show('redirect=detail&code=17296266')\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'), "\n\t\t\t\t</a>\n\t\t\t"),
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	  var descriptionRow = new ui_section.Row({
	    content: eventLogDescription.getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: settingsSection
	  });
	  var goToUserListButton = this.hasValue('EVENT_LOG') ? main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-text-right\">\n\t\t\t\t\t<a class=\"ui-section__link\" href=\"", "\" target=\"_blank\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t"])), this.getValue('EVENT_LOG'), main_core.Loc.getMessage('INTRANET_SETTINGS_GO_TO_EVENT_LOG_LINK')) : main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-text-right\">\n\t\t\t\t\t<a class=\"ui-section__link\" href=\"javascript:void(0)\" onclick=\"BX.UI.InfoHelper.show('limit_office_login_log')\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_GO_TO_EVENT_LOG_LINK'));
	  new ui_formElements_field.SettingsRow({
	    row: new ui_section.Row({
	      content: goToUserListButton
	    }),
	    parent: settingsSection
	  });
	  return settingsSection;
	}
	function _buildBlackListSection2() {
	  if (!this.hasValue('sectionBlackList')) {
	    return;
	  }
	  var params = this.getValue('sectionBlackList');
	  params['singleLink'] = {
	    href: '/settings/configs/mail_blacklist.php'
	  };
	  return new ui_section.Section(params);
	}
	function _buildMobileAppSection2() {
	  if (!this.hasValue('sectionMobileApp')) {
	    return;
	  }
	  var mobileAppSection = new ui_section.Section(this.getValue('sectionMobileApp'));
	  var settingsSection = new ui_formElements_field.SettingsSection({
	    section: mobileAppSection,
	    parent: this
	  });
	  if (this.hasValue('switcherDisableCopy')) {
	    var disableCopyField = new ui_formElements_view.Checker(this.getValue('switcherDisableCopy'));
	    SecurityPage.addToSectionHelper(disableCopyField, settingsSection);
	  }
	  if (this.hasValue('switcherDisableScreenshot')) {
	    var disableCopyScreenshotField = new ui_formElements_view.Checker(this.getValue('switcherDisableScreenshot'));
	    SecurityPage.addToSectionHelper(disableCopyScreenshotField, settingsSection);
	  }
	  return settingsSection;
	}

	var _templateObject$l, _templateObject2$c, _templateObject3$9, _templateObject4$2, _templateObject5$1, _templateObject6$1, _templateObject7$1, _templateObject8$1, _templateObject9$1, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16, _templateObject17, _templateObject18, _templateObject19, _templateObject20, _templateObject21, _templateObject22;
	function _classPrivateMethodInitSpec$f(obj, privateSet) { _checkPrivateRedeclaration$p(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$k(obj, privateMap, value) { _checkPrivateRedeclaration$p(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$p(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$f(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _urlCreate = /*#__PURE__*/new WeakMap();
	var _urlEdit = /*#__PURE__*/new WeakMap();
	var _urlPublic = /*#__PURE__*/new WeakMap();
	var _urlPartners = /*#__PURE__*/new WeakMap();
	var _urlImport = /*#__PURE__*/new WeakMap();
	var _urlExport = /*#__PURE__*/new WeakMap();
	var _previewImg = /*#__PURE__*/new WeakMap();
	var _title$2 = /*#__PURE__*/new WeakMap();
	var _feedbackParams = /*#__PURE__*/new WeakMap();
	var _buttonEdit = /*#__PURE__*/new WeakMap();
	var _buttonPartners = /*#__PURE__*/new WeakMap();
	var _buttonMarket = /*#__PURE__*/new WeakMap();
	var _buttonWithdraw = /*#__PURE__*/new WeakMap();
	var _buttonPublish = /*#__PURE__*/new WeakMap();
	var _mainTemplate = /*#__PURE__*/new WeakMap();
	var _secondaryTemplate = /*#__PURE__*/new WeakMap();
	var _buttonMainSettings = /*#__PURE__*/new WeakMap();
	var _buttonSecondarySettings = /*#__PURE__*/new WeakMap();
	var _importPopup = /*#__PURE__*/new WeakMap();
	var _exportPopup = /*#__PURE__*/new WeakMap();
	var _popupShare = /*#__PURE__*/new WeakMap();
	var _popupWithdraw = /*#__PURE__*/new WeakMap();
	var _isSiteExists = /*#__PURE__*/new WeakMap();
	var _isPageExists = /*#__PURE__*/new WeakMap();
	var _isPublished = /*#__PURE__*/new WeakMap();
	var _isEnable = /*#__PURE__*/new WeakMap();
	var _getMainTemplate = /*#__PURE__*/new WeakSet();
	var _getSecondaryTemplate = /*#__PURE__*/new WeakSet();
	var _getButtonMainSettings = /*#__PURE__*/new WeakSet();
	var _getButtonSecondarySettings = /*#__PURE__*/new WeakSet();
	var _showImportPopup = /*#__PURE__*/new WeakSet();
	var _showExportPopup = /*#__PURE__*/new WeakSet();
	var _showImportSlider = /*#__PURE__*/new WeakSet();
	var _showExportSlider = /*#__PURE__*/new WeakSet();
	var _showSharePopup = /*#__PURE__*/new WeakSet();
	var _showWithdrawPopup = /*#__PURE__*/new WeakSet();
	var _getButtonEdit = /*#__PURE__*/new WeakSet();
	var _getButtonPublish = /*#__PURE__*/new WeakSet();
	var _getButtonWithdraw = /*#__PURE__*/new WeakSet();
	var _getButtonPartners = /*#__PURE__*/new WeakSet();
	var _getButtonCreate = /*#__PURE__*/new WeakSet();
	var _bindButtonEvents = /*#__PURE__*/new WeakSet();
	var _bindSliderCloseEvent = /*#__PURE__*/new WeakSet();
	var MainpagePage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(MainpagePage, _BaseSettingsPage);
	  function MainpagePage() {
	    var _this;
	    babelHelpers.classCallCheck(this, MainpagePage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MainpagePage).call(this));
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _bindSliderCloseEvent);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _bindButtonEvents);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getButtonCreate);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getButtonPartners);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getButtonWithdraw);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getButtonPublish);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getButtonEdit);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _showWithdrawPopup);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _showSharePopup);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _showExportSlider);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _showImportSlider);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _showExportPopup);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _showImportPopup);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getButtonSecondarySettings);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getButtonMainSettings);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getSecondaryTemplate);
	    _classPrivateMethodInitSpec$f(babelHelpers.assertThisInitialized(_this), _getMainTemplate);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "titlePage", '');
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "descriptionPage", '');
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _urlCreate, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _urlEdit, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _urlPublic, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _urlPartners, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _urlImport, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _urlExport, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _previewImg, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _title$2, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _feedbackParams, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _buttonEdit, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _buttonPartners, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _buttonMarket, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _buttonWithdraw, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _buttonPublish, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _mainTemplate, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _secondaryTemplate, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _buttonMainSettings, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _buttonSecondarySettings, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _importPopup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _exportPopup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _popupShare, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _popupWithdraw, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _isSiteExists, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _isPageExists, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _isPublished, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$k(babelHelpers.assertThisInitialized(_this), _isEnable, {
	      writable: true,
	      value: true
	    });
	    _this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_MAINPAGE');
	    _this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_DESCRIPTION_PAGE_MAINPAGE');
	    return _this;
	  }
	  babelHelpers.createClass(MainpagePage, [{
	    key: "getType",
	    value: function getType() {
	      return 'mainpage';
	    }
	  }, {
	    key: "appendSections",
	    value: function appendSections(contentNode) {
	      var _options$isSiteExists, _options$isPageExists, _options$isPublished, _options$title, _options$isEnable;
	      var options = this.getValue('main-page');
	      babelHelpers.classPrivateFieldSet(this, _urlCreate, options.urlCreate || null);
	      babelHelpers.classPrivateFieldSet(this, _urlEdit, options.urlEdit || null);
	      babelHelpers.classPrivateFieldSet(this, _urlPublic, options.urlPublic || null);
	      babelHelpers.classPrivateFieldSet(this, _urlPartners, options.urlPartners || null);
	      babelHelpers.classPrivateFieldSet(this, _urlImport, options.urlImport || null);
	      babelHelpers.classPrivateFieldSet(this, _urlExport, options.urlExport || null);
	      babelHelpers.classPrivateFieldSet(this, _previewImg, options.previewImg || null);
	      babelHelpers.classPrivateFieldSet(this, _feedbackParams, options.feedbackParams || null);
	      babelHelpers.classPrivateFieldSet(this, _isSiteExists, (_options$isSiteExists = options.isSiteExists) !== null && _options$isSiteExists !== void 0 ? _options$isSiteExists : false);
	      babelHelpers.classPrivateFieldSet(this, _isPageExists, (_options$isPageExists = options.isPageExists) !== null && _options$isPageExists !== void 0 ? _options$isPageExists : false);
	      babelHelpers.classPrivateFieldSet(this, _isPublished, (_options$isPublished = options.isPublished) !== null && _options$isPublished !== void 0 ? _options$isPublished : false);
	      babelHelpers.classPrivateFieldSet(this, _title$2, (_options$title = options.title) !== null && _options$title !== void 0 ? _options$title : null);
	      babelHelpers.classPrivateFieldSet(this, _isEnable, (_options$isEnable = options.isEnable) !== null && _options$isEnable !== void 0 ? _options$isEnable : false);
	      var section = new ui_formElements_field.SettingsSection({
	        parent: this,
	        section: {
	          canCollapse: false,
	          isOpen: true
	        }
	      });
	      var secondarySection = new ui_formElements_field.SettingsSection({
	        parent: this,
	        section: {
	          canCollapse: false,
	          isOpen: true
	        }
	      });
	      var content = main_core.Tag.render(_templateObject$l || (_templateObject$l = babelHelpers.taggedTemplateLiteral(["<div>\t\t\n\t\t\t", "\t\t\n\t\t</div>"])), _classPrivateMethodGet$f(this, _getMainTemplate, _getMainTemplate2).call(this));
	      section.getSectionView().append(new ui_section.Row({
	        content: content
	      }).render());
	      if (babelHelpers.classPrivateFieldGet(this, _isPageExists)) {
	        var secondaryContent = main_core.Tag.render(_templateObject2$c || (_templateObject2$c = babelHelpers.taggedTemplateLiteral(["<div>\n\t\t\t\t", "\t\t\t\n\t\t\t</div>"])), _classPrivateMethodGet$f(this, _getSecondaryTemplate, _getSecondaryTemplate2).call(this));
	        secondarySection.getSectionView().append(new ui_section.Row({
	          content: secondaryContent
	        }).render());
	      }
	      _classPrivateMethodGet$f(this, _bindButtonEvents, _bindButtonEvents2).call(this);
	      _classPrivateMethodGet$f(this, _bindSliderCloseEvent, _bindSliderCloseEvent2).call(this);
	      secondarySection.renderTo(contentNode);
	      section.renderTo(contentNode);
	    }
	  }, {
	    key: "getInfoTemplate",
	    value: function getInfoTemplate() {
	      var _this2 = this;
	      this.infoTemplate = main_core.Tag.render(_templateObject3$9 || (_templateObject3$9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"intranet-settings__main-page-info\">\n\t\t\t\t<div class=\"intranet-settings__main-page-info-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"intranet-settings__main-page-info-subtitle\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-icon-set --help intranet-settings__main-page-info-help\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_TITLE'), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUBTITLE'));
	      main_core.Event.bind(this.infoTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseenter', function (event) {
	        var width = _this2.infoTemplate.querySelector('.intranet-settings__main-page-info-help').offsetWidth;
	        _this2.warningHintPopup = new main_popup.Popup({
	          angle: true,
	          autoHide: true,
	          content: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_HINT_WARNING'),
	          cacheable: false,
	          animation: 'fading-slide',
	          bindElement: event.target,
	          offsetTop: 0,
	          offsetLeft: parseInt(width / 2),
	          bindOptions: {
	            position: 'top'
	          },
	          darkMode: true
	        });
	        _this2.warningHintPopup.show();
	      });
	      main_core.Event.bind(this.infoTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseleave', function () {
	        if (_this2.warningHintPopup) {
	          setTimeout(function () {
	            _this2.warningHintPopup.destroy();
	            _this2.warningHintPopup = null;
	          }, 300);
	        }
	      });
	      return this.infoTemplate;
	    }
	  }, {
	    key: "getInfoSuccessTemplate",
	    value: function getInfoSuccessTemplate() {
	      var _this3 = this;
	      this.infoSuccessTemplate = main_core.Tag.render(_templateObject4$2 || (_templateObject4$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"intranet-settings__main-page-info --success\">\n\t\t\t\t<div class=\"intranet-settings__main-page-info-title\">\n\t\t\t\t\t", "\t\t\t\t\n\t\t\t\t</div>\n\t\t\t\t<div class=\"intranet-settings__main-page-info-subtitle\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-icon-set --help intranet-settings__main-page-info-help\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUCCESS_TITLE'), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUCCESS_SUBTITLE'));
	      main_core.Event.bind(this.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseenter', function (event) {
	        var width = _this3.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help').offsetWidth;
	        _this3.successHintPopup = new main_popup.Popup({
	          angle: true,
	          autoHide: true,
	          content: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_HINT_SUCCESS'),
	          cacheable: false,
	          animation: 'fading-slide',
	          bindElement: event.target,
	          offsetTop: 0,
	          offsetLeft: parseInt(width / 2),
	          bindOptions: {
	            position: 'top'
	          },
	          darkMode: true
	        });
	        _this3.successHintPopup.show();
	      });
	      main_core.Event.bind(this.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseleave', function () {
	        if (_this3.successHintPopup) {
	          setTimeout(function () {
	            _this3.successHintPopup.destroy();
	            _this3.successHintPopup = null;
	          }, 300);
	        }
	      });
	      return this.infoSuccessTemplate;
	    }
	  }, {
	    key: "renderLockElement",
	    value: function renderLockElement() {
	      return main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["<span class=\"intranet-settings-mp-icon ui-icon-set --lock\"></span>"])));
	    }
	  }]);
	  return MainpagePage;
	}(ui_formElements_field.BaseSettingsPage);
	function _getMainTemplate2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _mainTemplate)) {
	    babelHelpers.classPrivateFieldSet(this, _mainTemplate, main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"intranet-settings__main-page-template\">\n\t\t\t\t\t<div class=\"intranet-settings__main-page-icon-box\">\n\t\t\t\t\t\t<div class=\"intranet-settings__main-page-icon\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"intranet-settings__main-page-content\">\n\t\t\t\t\t\t<ul class=\"intranet-settings__main-page-list\">\n\t\t\t\t\t\t\t<li class=\"intranet-settings__main-page-list-item\">\n\t\t\t\t\t\t\t\t<div class=\"ui-icon-set --check intranet-settings__main-page-list-icon\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-page-list-name\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"intranet-settings__main-page-list-item\">\n\t\t\t\t\t\t\t\t<div class=\"ui-icon-set --check intranet-settings__main-page-list-icon\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-page-list-name\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"intranet-settings__main-page-list-item\">\n\t\t\t\t\t\t\t\t<div class=\"ui-icon-set --check intranet-settings__main-page-list-icon\"></div>\n\t\t\t\t\t\t\t\t<div class=\"intranet-settings__main-page-list-name\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-page-button-box\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-page-button-box-right\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_1'), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_2'), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_3'), _classPrivateMethodGet$f(this, _getButtonCreate, _getButtonCreate2).call(this), _classPrivateMethodGet$f(this, _getButtonPartners, _getButtonPartners2).call(this), _classPrivateMethodGet$f(this, _getButtonMainSettings, _getButtonMainSettings2).call(this)));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _mainTemplate);
	}
	function _getSecondaryTemplate2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _secondaryTemplate)) {
	    var _babelHelpers$classPr;
	    var previewImg = babelHelpers.classPrivateFieldGet(this, _previewImg) ? main_core.Tag.render(_templateObject7$1 || (_templateObject7$1 = babelHelpers.taggedTemplateLiteral(["<img \n\t\t\t\t\t\tsrc=\"", "\"\n\t\t\t\t\t\tclass=\"intranet-settings__main-page-preview\" \n\t\t\t\t\t/>"])), babelHelpers.classPrivateFieldGet(this, _previewImg)) : '';
	    babelHelpers.classPrivateFieldSet(this, _secondaryTemplate, main_core.Tag.render(_templateObject8$1 || (_templateObject8$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"intranet-settings__main-page-template --secondary-template\">\n\t\t\t\t\t<div class=\"intranet-settings__main-page-preview-box\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"intranet-settings__main-page-content\">\n\t\t\t\t\t\t<div class=\"intranet-settings__main-page-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"intranet-settings__main-page-info-template\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\t\t\t\t\t\n\t\t\t\t\t\t<div class=\"intranet-settings__main-page-button-box\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t<div class=\"intranet-settings__main-page-button-box-right\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\t\t\t\n\t\t\t"])), previewImg, (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _title$2)) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : '', babelHelpers.classPrivateFieldGet(this, _isPublished) ? this.getInfoSuccessTemplate() : this.getInfoTemplate(), _classPrivateMethodGet$f(this, _getButtonEdit, _getButtonEdit2).call(this), babelHelpers.classPrivateFieldGet(this, _isPublished) && babelHelpers.classPrivateFieldGet(this, _isEnable) ? _classPrivateMethodGet$f(this, _getButtonWithdraw, _getButtonWithdraw2).call(this) : _classPrivateMethodGet$f(this, _getButtonPublish, _getButtonPublish2).call(this), _classPrivateMethodGet$f(this, _getButtonSecondarySettings, _getButtonSecondarySettings2).call(this)));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _secondaryTemplate);
	}
	function _getButtonMainSettings2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _buttonMainSettings)) {
	    babelHelpers.classPrivateFieldSet(this, _buttonMainSettings, main_core.Tag.render(_templateObject9$1 || (_templateObject9$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"intranet-settings-btn-settings\">\n\t\t\t\t\t<div class=\"ui-icon-set --more\"></div>\n\t\t\t\t</button>\n\t\t\t"]))));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _buttonMainSettings);
	}
	function _getButtonSecondarySettings2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _buttonSecondarySettings)) {
	    babelHelpers.classPrivateFieldSet(this, _buttonSecondarySettings, main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"intranet-settings-btn-settings\">\n\t\t\t\t<div class=\"ui-icon-set --more\"></div>\n\t\t\t</button>"]))));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _buttonSecondarySettings);
	}
	function _showImportPopup2() {
	  var _babelHelpers$classPr2;
	  if (!babelHelpers.classPrivateFieldGet(this, _importPopup)) {
	    var htmlContent = babelHelpers.classPrivateFieldGet(this, _isEnable) ? main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP')) : main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["<span class=\"intranet-settings-mp-popup-item\">", " ", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP'), this.renderLockElement());
	    babelHelpers.classPrivateFieldSet(this, _importPopup, new main_popup.Menu({
	      angle: true,
	      animation: 'fading-slide',
	      bindElement: babelHelpers.classPrivateFieldGet(this, _buttonMainSettings),
	      className: babelHelpers.classPrivateFieldGet(this, _isEnable) ? '' : '--disabled',
	      items: [{
	        id: 'importPopup',
	        html: htmlContent,
	        onclick: _classPrivateMethodGet$f(this, _showImportSlider, _showImportSlider2).bind(this)
	      }],
	      offsetLeft: 20,
	      events: {
	        onPopupClose: function onPopupClose() {},
	        onPopupShow: function onPopupShow() {}
	      }
	    }));
	  }
	  (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _importPopup)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.show();
	}
	function _showExportPopup2() {
	  var _babelHelpers$classPr3;
	  if (!babelHelpers.classPrivateFieldGet(this, _exportPopup)) {
	    var htmlContent = babelHelpers.classPrivateFieldGet(this, _isEnable) ? main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_EXPORT_POPUP')) : main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["<span class=\"intranet-settings-mp-popup-item --disabled\">", " ", "</span>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_EXPORT_POPUP'), this.renderLockElement());
	    babelHelpers.classPrivateFieldSet(this, _exportPopup, new main_popup.Menu({
	      angle: true,
	      animation: 'fading-slide',
	      bindElement: babelHelpers.classPrivateFieldGet(this, _buttonSecondarySettings),
	      className: babelHelpers.classPrivateFieldGet(this, _isEnable) ? '' : '--disabled',
	      items: [{
	        id: 'exportPopup',
	        html: htmlContent,
	        onclick: _classPrivateMethodGet$f(this, _showExportSlider, _showExportSlider2).bind(this)
	      }],
	      offsetLeft: 20,
	      events: {
	        onPopupClose: function onPopupClose() {},
	        onPopupShow: function onPopupShow() {}
	      }
	    }));
	  }
	  (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _exportPopup)) === null || _babelHelpers$classPr3 === void 0 ? void 0 : _babelHelpers$classPr3.show();
	}
	function _showImportSlider2() {
	  var _this4 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _isEnable)) {
	    BX.UI.InfoHelper.show("limit_office_vibe");
	    return;
	  }
	  if (typeof BX.SidePanel === 'undefined') {
	    return;
	  }
	  if (typeof BX.SidePanel !== 'undefined' && babelHelpers.classPrivateFieldGet(this, _urlImport)) {
	    var onOK = function onOK() {
	      BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldGet(_this4, _urlImport), {
	        width: 491,
	        allowChangeHistory: false,
	        cacheable: false,
	        data: {
	          rightBoundary: 0
	        }
	      });
	    };
	    if (!babelHelpers.classPrivateFieldGet(this, _isPageExists)) {
	      onOK();
	      return;
	    }
	    BX.Runtime.loadExtension('ui.dialogs.messagebox').then(function () {
	      var messageBox = new BX.UI.Dialogs.MessageBox({
	        message: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_MESSAGE'),
	        title: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_TITLE'),
	        buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_OK_BUTTON'),
	        cancelCaption: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_CANCEL_BUTTON'),
	        onOk: function onOk() {
	          onOK();
	          return true;
	        },
	        onCancel: function onCancel() {
	          return true;
	        }
	      });
	      messageBox.show();
	      if (messageBox.popupWindow && messageBox.popupWindow.popupContainer) {
	        messageBox.popupWindow.popupContainer.classList.add('intranet-settings__main-page-popup');
	      }
	    });
	  }
	}
	function _showExportSlider2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _isEnable)) {
	    BX.UI.InfoHelper.show("limit_office_vibe");
	    return;
	  }
	  if (typeof BX.SidePanel === 'undefined') {
	    return;
	  }
	  if (typeof BX.SidePanel !== 'undefined' && babelHelpers.classPrivateFieldGet(this, _urlExport)) {
	    BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldGet(this, _urlExport), {
	      width: 491,
	      allowChangeHistory: false,
	      cacheable: false,
	      data: {
	        rightBoundary: 0
	      }
	    });
	  }
	}
	function _showSharePopup2() {
	  var _this5 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _popupShare)) {
	    var _babelHelpers$classPr4;
	    babelHelpers.classPrivateFieldSet(this, _popupShare, new main_popup.Popup({
	      titleBar: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_TITLE_MSGVER_1'),
	      content: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_CONTENT'),
	      width: 350,
	      closeIcon: true,
	      closeByEsc: true,
	      animation: 'fading-slide',
	      buttons: [new ui_buttons.Button({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_BTN_CONFIRM'),
	        color: ui_buttons.Button.Color.PRIMARY,
	        onclick: function onclick() {
	          var newTemplate = _this5.getInfoSuccessTemplate();
	          var wrapper = babelHelpers.classPrivateFieldGet(_this5, _secondaryTemplate).querySelector('.intranet-settings__main-page-info-template');
	          var innerWrapper = wrapper.querySelector('.intranet-settings__main-page-info:not(.--success)');
	          main_core.Dom.replace(innerWrapper, newTemplate);
	          main_core.ajax.runAction('intranet.mainpage.publish').then(function () {
	            _this5.emit('publish');
	            if (babelHelpers.classPrivateFieldGet(_this5, _urlPublic)) {
	              babelHelpers.classPrivateFieldSet(_this5, _isPublished, true);
	            }
	          });
	          babelHelpers.classPrivateFieldGet(_this5, _popupShare).close();
	          BX.UI.Analytics.sendData({
	            tool: 'landing',
	            category: 'vibe',
	            event: 'publish_page',
	            c_sub_section: 'from_settings',
	            status: 'success'
	          });
	        }
	      }), new ui_buttons.Button({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_POPUP_BTN_CANCEL'),
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        onclick: function onclick() {
	          babelHelpers.classPrivateFieldGet(_this5, _popupShare).close();
	        }
	      })],
	      events: {
	        onClose: function onClose() {}
	      }
	    }));
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldGet(this, _popupShare)) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4.show();
	  } else {
	    var _babelHelpers$classPr5;
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldGet(this, _popupShare)) === null || _babelHelpers$classPr5 === void 0 ? void 0 : _babelHelpers$classPr5.show();
	  }
	}
	function _showWithdrawPopup2() {
	  var _this6 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _popupWithdraw)) {
	    var _babelHelpers$classPr6;
	    babelHelpers.classPrivateFieldSet(this, _popupWithdraw, new main_popup.Popup({
	      titleBar: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_TITLE'),
	      content: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_CONTENT'),
	      width: 350,
	      closeIcon: true,
	      closeByEsc: true,
	      animation: 'fading-slide',
	      buttons: [new ui_buttons.Button({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_BTN_CONFIRM'),
	        color: ui_buttons.Button.Color.DANGER_DARK,
	        onclick: function onclick() {
	          var newTemplate = _this6.getInfoTemplate();
	          var wrapper = babelHelpers.classPrivateFieldGet(_this6, _secondaryTemplate).querySelector('.intranet-settings__main-page-info-template');
	          var innerWrapper = wrapper.querySelector('.intranet-settings__main-page-info');
	          main_core.Dom.replace(innerWrapper, newTemplate);
	          main_core.ajax.runAction('intranet.mainpage.withdraw').then(function () {
	            _this6.emit('withdraw');
	            babelHelpers.classPrivateFieldSet(_this6, _isPublished, false);
	          });
	          babelHelpers.classPrivateFieldGet(_this6, _popupWithdraw).close();
	          BX.UI.Analytics.sendData({
	            tool: 'landing',
	            category: 'vibe',
	            event: 'unpublish_page',
	            c_sub_section: 'from_settings'
	          });
	        }
	      }), new ui_buttons.Button({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_POPUP_BTN_CANCEL'),
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        onclick: function onclick() {
	          babelHelpers.classPrivateFieldGet(_this6, _popupWithdraw).close();
	        }
	      })],
	      events: {
	        onClose: function onClose() {}
	      }
	    }));
	    (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldGet(this, _popupWithdraw)) === null || _babelHelpers$classPr6 === void 0 ? void 0 : _babelHelpers$classPr6.show();
	  } else {
	    var _babelHelpers$classPr7;
	    (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldGet(this, _popupWithdraw)) === null || _babelHelpers$classPr7 === void 0 ? void 0 : _babelHelpers$classPr7.show();
	  }
	}
	function _getButtonEdit2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _urlEdit)) {
	    return null;
	  }
	  if (!babelHelpers.classPrivateFieldGet(this, _buttonEdit)) {
	    babelHelpers.classPrivateFieldSet(this, _buttonEdit, main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --light-blue\">\n\t\t\t\t\t", "\n\t\t\t\t</button>"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_EDIT')));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _buttonEdit);
	}
	function _getButtonPublish2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _buttonPublish)) {
	    var renderNode = main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-round ui-btn-no-caps\n\t\t\t\t\t\t", "\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _isPageExists) ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PUBLIC'));
	    var renderNodeLock = main_core.Tag.render(_templateObject17 || (_templateObject17 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --disabled\n\t\t\t\t\t\t", "\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _isPageExists) ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PUBLIC'), this.renderLockElement());
	    babelHelpers.classPrivateFieldSet(this, _buttonPublish, babelHelpers.classPrivateFieldGet(this, _isEnable) ? renderNode : renderNodeLock);
	  }
	  return babelHelpers.classPrivateFieldGet(this, _buttonPublish);
	}
	function _getButtonWithdraw2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _buttonWithdraw)) {
	    var renderNode = main_core.Tag.render(_templateObject18 || (_templateObject18 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-round ui-btn-no-caps\n\t\t\t\t\t\t", "\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _isPageExists) ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_UNPUBLIC'));
	    var renderNodeLock = main_core.Tag.render(_templateObject19 || (_templateObject19 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --disabled\n\t\t\t\t\t\t", "\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _isPageExists) ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_UNPUBLIC'), this.renderLockElement());
	    babelHelpers.classPrivateFieldSet(this, _buttonWithdraw, babelHelpers.classPrivateFieldGet(this, _isEnable) ? renderNode : renderNodeLock);
	  }
	  return babelHelpers.classPrivateFieldGet(this, _buttonWithdraw);
	}
	function _getButtonPartners2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _urlPartners)) {
	    return null;
	  }
	  if (!babelHelpers.classPrivateFieldGet(this, _buttonPartners)) {
	    babelHelpers.classPrivateFieldSet(this, _buttonPartners, main_core.Tag.render(_templateObject20 || (_templateObject20 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --light-gray\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PARTNERS')));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _buttonPartners);
	}
	function _getButtonCreate2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _urlCreate)) {
	    return null;
	  }
	  if (!babelHelpers.classPrivateFieldGet(this, _buttonMarket)) {
	    var renderNode = main_core.Tag.render(_templateObject21 || (_templateObject21 = babelHelpers.taggedTemplateLiteral(["\t\t\t\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-round ui-btn-no-caps \n\t\t\t\t\t\t", "\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"])), !babelHelpers.classPrivateFieldGet(this, _isPageExists) ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_MARKET'));
	    var renderNodeLock = main_core.Tag.render(_templateObject22 || (_templateObject22 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --disabled\n\t\t\t\t\t\t", "\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"])), !babelHelpers.classPrivateFieldGet(this, _isPageExists) ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_MARKET'), this.renderLockElement());
	    babelHelpers.classPrivateFieldSet(this, _buttonMarket, babelHelpers.classPrivateFieldGet(this, _isEnable) ? renderNode : renderNodeLock);
	  }
	  return babelHelpers.classPrivateFieldGet(this, _buttonMarket);
	}
	function _bindButtonEvents2() {
	  var _this7 = this;
	  if (typeof BX.SidePanel === 'undefined') {
	    return;
	  }
	  main_core.Event.bind(_classPrivateMethodGet$f(this, _getButtonMainSettings, _getButtonMainSettings2).call(this), 'click', _classPrivateMethodGet$f(this, _showImportPopup, _showImportPopup2).bind(this));
	  main_core.Event.bind(_classPrivateMethodGet$f(this, _getButtonSecondarySettings, _getButtonSecondarySettings2).call(this), 'click', _classPrivateMethodGet$f(this, _showExportPopup, _showExportPopup2).bind(this));
	  if (_classPrivateMethodGet$f(this, _getButtonCreate, _getButtonCreate2).call(this)) {
	    main_core.Event.bind(_classPrivateMethodGet$f(this, _getButtonCreate, _getButtonCreate2).call(this), 'click', function () {
	      if (babelHelpers.classPrivateFieldGet(_this7, _isEnable)) {
	        BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldGet(_this7, _urlCreate));
	      } else {
	        BX.UI.InfoHelper.show("limit_office_vibe");
	      }
	      BX.UI.Analytics.sendData({
	        tool: 'landing',
	        category: 'vibe',
	        event: 'open_market',
	        status: babelHelpers.classPrivateFieldGet(_this7, _isEnable) ? 'success' : 'error_limit'
	      });
	    });
	  }
	  if (_classPrivateMethodGet$f(this, _getButtonEdit, _getButtonEdit2).call(this)) {
	    main_core.Event.bind(_classPrivateMethodGet$f(this, _getButtonEdit, _getButtonEdit2).call(this), 'click', function () {
	      BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldGet(_this7, _urlEdit), {
	        customLeftBoundary: 66,
	        events: {
	          onCloseComplete: function onCloseComplete() {
	            if (babelHelpers.classPrivateFieldGet(_this7, _urlPublic)) {
	              window.top.location = babelHelpers.classPrivateFieldGet(_this7, _urlPublic);
	            }
	          }
	        }
	      });
	      BX.UI.Analytics.sendData({
	        tool: 'landing',
	        category: 'vibe',
	        event: 'open_editor'
	      });
	    });
	  }
	  if (_classPrivateMethodGet$f(this, _getButtonPartners, _getButtonPartners2).call(this)) {
	    main_core.Event.bind(_classPrivateMethodGet$f(this, _getButtonPartners, _getButtonPartners2).call(this), 'click', function () {
	      if (!babelHelpers.classPrivateFieldGet(_this7, _feedbackParams)) {
	        return;
	      }
	      main_core.Runtime.loadExtension('ui.feedback.form').then(function () {
	        babelHelpers.classPrivateFieldGet(_this7, _feedbackParams).title = main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PARTNERS');
	        BX.UI.Feedback.Form.open(babelHelpers.classPrivateFieldGet(_this7, _feedbackParams));
	      });
	    });
	  }
	  this.subscribe('publish', function () {
	    main_core.Dom.replace(_classPrivateMethodGet$f(_this7, _getButtonPublish, _getButtonPublish2).call(_this7), _classPrivateMethodGet$f(_this7, _getButtonWithdraw, _getButtonWithdraw2).call(_this7));
	  });
	  this.subscribe('withdraw', function () {
	    main_core.Dom.replace(_classPrivateMethodGet$f(_this7, _getButtonWithdraw, _getButtonWithdraw2).call(_this7), _classPrivateMethodGet$f(_this7, _getButtonPublish, _getButtonPublish2).call(_this7));
	  });
	  main_core.Event.bind(_classPrivateMethodGet$f(this, _getButtonPublish, _getButtonPublish2).call(this), 'click', function () {
	    if (!babelHelpers.classPrivateFieldGet(_this7, _isEnable)) {
	      BX.UI.InfoHelper.show("limit_office_vibe");
	      BX.UI.Analytics.sendData({
	        tool: 'landing',
	        category: 'vibe',
	        event: 'publish_page',
	        c_sub_section: 'from_settings',
	        status: 'error_limit'
	      });
	      return;
	    }
	    _classPrivateMethodGet$f(_this7, _showSharePopup, _showSharePopup2).call(_this7);
	  });
	  main_core.Event.bind(_classPrivateMethodGet$f(this, _getButtonWithdraw, _getButtonWithdraw2).call(this), 'click', _classPrivateMethodGet$f(this, _showWithdrawPopup, _showWithdrawPopup2).bind(this));
	}
	function _bindSliderCloseEvent2() {
	  var _this8 = this;
	  var isPublishedBefore = babelHelpers.classPrivateFieldGet(this, _isPublished);
	  main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onClose', function () {
	    if (babelHelpers.classPrivateFieldGet(_this8, _isPublished) !== isPublishedBefore) {
	      var location = babelHelpers.classPrivateFieldGet(_this8, _isPublished) ? babelHelpers.classPrivateFieldGet(_this8, _urlPublic) : '/';
	      window.top.location = location;
	    }
	  });
	}

	function _classPrivateFieldInitSpec$l(obj, privateMap, value) { _checkPrivateRedeclaration$q(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$q(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _type = /*#__PURE__*/new WeakMap();
	var _extensions = /*#__PURE__*/new WeakMap();
	var ExternalTemporaryPage = /*#__PURE__*/function (_BaseSettingsPage) {
	  babelHelpers.inherits(ExternalTemporaryPage, _BaseSettingsPage);
	  function ExternalTemporaryPage(type, extensions) {
	    var _this;
	    babelHelpers.classCallCheck(this, ExternalTemporaryPage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ExternalTemporaryPage).call(this));
	    _classPrivateFieldInitSpec$l(babelHelpers.assertThisInitialized(_this), _type, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$l(babelHelpers.assertThisInitialized(_this), _extensions, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _type, type);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _extensions, extensions);
	    return _this;
	  }
	  babelHelpers.createClass(ExternalTemporaryPage, [{
	    key: "getType",
	    value: function getType() {
	      return babelHelpers.classPrivateFieldGet(this, _type);
	    }
	  }, {
	    key: "onSuccessDataFetched",
	    value: function onSuccessDataFetched(response) {
	      var _this2 = this;
	      main_core.Runtime.loadExtension(babelHelpers.classPrivateFieldGet(this, _extensions)).then(function (exports) {
	        var externalPage;
	        var externalPageHasBeenFound = Object.values(exports).some(function (externalPageClassOrInstance) {
	          if (main_core.Type.isObjectLike(externalPageClassOrInstance)) {
	            var pageExemplar = null;
	            if (externalPageClassOrInstance.prototype instanceof ui_formElements_field.BaseSettingsPage) {
	              pageExemplar = new externalPageClassOrInstance();
	            } else if (externalPageClassOrInstance instanceof ui_formElements_field.BaseSettingsPage) {
	              pageExemplar = externalPageClassOrInstance;
	            }
	            if (pageExemplar instanceof ui_formElements_field.BaseSettingsPage) {
	              externalPage = pageExemplar;
	              return true;
	            }
	          }
	          return false;
	        });
	        if (externalPageHasBeenFound === false) {
	          var event = new main_core_events.BaseEvent();
	          externalPageHasBeenFound = main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onExternalPageLoaded:' + _this2.getType(), event).some(function (pageExemplar) {
	            if (pageExemplar instanceof ui_formElements_field.BaseSettingsPage) {
	              externalPage = pageExemplar;
	              return true;
	            }
	            return false;
	          });
	        }
	        if (externalPage instanceof ui_formElements_field.BaseSettingsPage) {
	          _this2.getParentElement().registerPage(externalPage);
	          externalPage.setData(response.data);
	          _this2.getParentElement().removeChild(_this2);
	          if (main_core.Dom.isShown(_this2.getPage())) {
	            externalPage.getParentElement().show(externalPage.getType());
	          }
	        } else {
	          _this2.onFailDataFetched('The external page was not found.');
	        }
	      }, this.onFailDataFetched.bind(this));
	    }
	  }]);
	  return ExternalTemporaryPage;
	}(ui_formElements_field.BaseSettingsPage);

	function _classPrivateFieldInitSpec$m(obj, privateMap, value) { _checkPrivateRedeclaration$r(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$r(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _pages = /*#__PURE__*/new WeakMap();
	var PageManager = /*#__PURE__*/function () {
	  function PageManager(pages) {
	    babelHelpers.classCallCheck(this, PageManager);
	    _classPrivateFieldInitSpec$m(this, _pages, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _pages, pages);
	  }
	  babelHelpers.createClass(PageManager, [{
	    key: "fetchPage",
	    value: function fetchPage(page) {
	      var _this = this;
	      return new Promise(function (resolve, reject) {
	        var pageIsFound = babelHelpers.classPrivateFieldGet(_this, _pages).some(function (savedPage) {
	          if (page.getType() === savedPage.getType()) {
	            main_core.ajax.runComponentAction('bitrix:intranet.settings', 'get', {
	              mode: 'class',
	              data: {
	                type: page.getType()
	              }
	            }).then(resolve, reject);
	            return true;
	          }
	          return false;
	        });
	        if (pageIsFound !== true) {
	          return reject({
	            error: 'The page was not found in pageManager'
	          });
	        }
	      });
	    }
	  }, {
	    key: "collectData",
	    value: function collectData() {
	      var _this2 = this;
	      var data = {};
	      babelHelpers.classPrivateFieldGet(this, _pages).forEach(function (page) {
	        if (page.hasData()) {
	          data[page.getType()] = _this2.constructor.getFormData(page.getFormNode());
	        }
	      });
	      return data;
	    }
	  }], [{
	    key: "getFormData",
	    value: function getFormData(formNode) {
	      return BX.ajax.prepareForm(formNode).data;
	    }
	  }]);
	  return PageManager;
	}();

	var DataSource = /*#__PURE__*/function () {
	  function DataSource() {
	    babelHelpers.classCallCheck(this, DataSource);
	  }
	  babelHelpers.createClass(DataSource, [{
	    key: "fetch",
	    value: function fetch(query) {
	      return new Promise();
	    }
	  }]);
	  return DataSource;
	}();

	function _classPrivateFieldInitSpec$n(obj, privateMap, value) { _checkPrivateRedeclaration$s(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$s(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _query = /*#__PURE__*/new WeakMap();
	var _minSymbol = /*#__PURE__*/new WeakMap();
	var _dataSource = /*#__PURE__*/new WeakMap();
	var _result = /*#__PURE__*/new WeakMap();
	var _state = /*#__PURE__*/new WeakMap();
	var Searcher = /*#__PURE__*/function () {
	  // ready | wait;

	  function Searcher(dataSource) {
	    var minSymbol = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 3;
	    babelHelpers.classCallCheck(this, Searcher);
	    _classPrivateFieldInitSpec$n(this, _query, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$n(this, _minSymbol, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$n(this, _dataSource, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$n(this, _result, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$n(this, _state, {
	      writable: true,
	      value: void 0
	    });
	    if (!(dataSource instanceof DataSource)) {
	      throw new Error('Unexpected type, expect: DataSource');
	    }
	    babelHelpers.classPrivateFieldSet(this, _dataSource, dataSource);
	    babelHelpers.classPrivateFieldSet(this, _minSymbol, minSymbol);
	    babelHelpers.classPrivateFieldSet(this, _result, []);
	    babelHelpers.classPrivateFieldSet(this, _state, 'ready');
	  }
	  babelHelpers.createClass(Searcher, [{
	    key: "find",
	    value: function find(query) {
	      if (query.length < babelHelpers.classPrivateFieldGet(this, _minSymbol)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _query, query);
	      this.changeState(Searcher.STATE_WAIT);
	      babelHelpers.classPrivateFieldGet(this, _dataSource).fetch(babelHelpers.classPrivateFieldGet(this, _query)).then(this.resolve.bind(this), this.reject.bind(this));
	    }
	  }, {
	    key: "changeState",
	    value: function changeState(state) {
	      if (babelHelpers.classPrivateFieldGet(this, _state) === state) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _state, state);
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:searchChangeState', {
	        state: babelHelpers.classPrivateFieldGet(this, _state)
	      });
	    }
	  }, {
	    key: "getMinSymbol",
	    value: function getMinSymbol() {
	      return babelHelpers.classPrivateFieldGet(this, _minSymbol);
	    }
	  }, {
	    key: "resolve",
	    value: function resolve(response) {
	      babelHelpers.classPrivateFieldSet(this, _result, response.data);
	      this.changeState(babelHelpers.classPrivateFieldGet(this, _result).length > 0 ? Searcher.STATE_READY : Searcher.STATE_NOT_FOUND);
	    }
	  }, {
	    key: "reject",
	    value: function reject(response) {
	      babelHelpers.classPrivateFieldSet(this, _result, []);
	      this.changeState(Searcher.STATE_READY);
	    }
	  }, {
	    key: "getResult",
	    value: function getResult() {
	      return babelHelpers.classPrivateFieldGet(this, _result);
	    }
	  }, {
	    key: "getOthers",
	    value: function getOthers() {
	      return [{
	        link: '/stream/',
	        title: main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_TEAMWORK')
	      }, {
	        link: '/tasks/config/permissions/',
	        title: main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_TASKS')
	      }, {
	        link: '/crm/configs/',
	        title: main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_CRM')
	      }, {
	        link: '/shop/documents/?inventoryManagementSource=inventory',
	        title: main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_WAREHOUSE')
	      }, {
	        link: '/sites/',
	        title: main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_SITES')
	      }, {
	        link: '/company/vis_structure.php',
	        title: main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_COMPANY')
	      }];
	    }
	  }]);
	  return Searcher;
	}();
	babelHelpers.defineProperty(Searcher, "STATE_READY", 'ready');
	babelHelpers.defineProperty(Searcher, "STATE_WAIT", 'wait');
	babelHelpers.defineProperty(Searcher, "STATE_NOT_FOUND", 'not_found');

	var _templateObject$m, _templateObject2$d, _templateObject3$a, _templateObject4$3, _templateObject5$2, _templateObject6$2, _templateObject7$2, _templateObject8$2, _templateObject9$2, _templateObject10$1, _templateObject11$1, _templateObject12$1, _templateObject13$1, _templateObject14$1, _templateObject15$1;
	function _classPrivateFieldInitSpec$o(obj, privateMap, value) { _checkPrivateRedeclaration$t(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$t(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _searcher = /*#__PURE__*/new WeakMap();
	var _inputNode = /*#__PURE__*/new WeakMap();
	var _iconContainer = /*#__PURE__*/new WeakMap();
	var _popup = /*#__PURE__*/new WeakMap();
	var _timeoutId = /*#__PURE__*/new WeakMap();
	var _timeout = /*#__PURE__*/new WeakMap();
	var _nav = /*#__PURE__*/new WeakMap();
	var Renderer = /*#__PURE__*/function () {
	  function Renderer(options) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Renderer);
	    _classPrivateFieldInitSpec$o(this, _searcher, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$o(this, _inputNode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$o(this, _iconContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$o(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$o(this, _timeoutId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$o(this, _timeout, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$o(this, _nav, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _searcher, options.searcher);
	    babelHelpers.classPrivateFieldSet(this, _inputNode, options.inputNode);
	    babelHelpers.classPrivateFieldSet(this, _iconContainer, options.iconContainer);
	    babelHelpers.classPrivateFieldSet(this, _timeout, options.timeout);
	    babelHelpers.classPrivateFieldSet(this, _nav, new SearchNavigation());
	    babelHelpers.classPrivateFieldSet(this, _popup, new main_popup.Popup('settings-search-popup', babelHelpers.classPrivateFieldGet(this, _inputNode), {
	      closeByEsc: true,
	      angle: false,
	      overlay: false,
	      width: 332,
	      //470,//this.#inputNode.offsetWidth,
	      offsetTop: 4,
	      background: '#fff',
	      contentBackground: '#fff',
	      contentPadding: 0,
	      autoHide: true,
	      borderRadius: 6,
	      autoHideHandler: function autoHideHandler(event) {
	        return event.target !== babelHelpers.classPrivateFieldGet(_this, _inputNode);
	      }
	    }));
	    babelHelpers.classPrivateFieldGet(this, _popup).setContent(this.renderContent());
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _inputNode), 'focus', function () {
	      if (!babelHelpers.classPrivateFieldGet(_this, _popup).isShown()) {
	        babelHelpers.classPrivateFieldGet(_this, _popup).show();
	      }
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Settings:searchChangeState', function (event) {
	      var state = event.data.state;
	      babelHelpers.classPrivateFieldGet(_this, _nav).clean();
	      babelHelpers.classPrivateFieldGet(_this, _popup).setContent(_this.renderContent(state));
	    });
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _iconContainer).querySelector('#intranet-settings-icon-delete'), 'click', function () {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this, _iconContainer), 'main-ui-show');
	      babelHelpers.classPrivateFieldGet(_this, _inputNode).value = '';
	    });
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _inputNode), 'keyup', function (event) {
	      if (event.keyCode === 37 || event.keyCode === 39) {
	        return;
	      }
	      if (event.keyCode === 13)
	        //enter
	        {
	          babelHelpers.classPrivateFieldGet(_this, _nav).current().dispatchEvent(new MouseEvent('click'));
	          babelHelpers.classPrivateFieldGet(_this, _nav).unHighlightAll();
	          return;
	        }
	      if (event.keyCode === 38)
	        //up
	        {
	          babelHelpers.classPrivateFieldGet(_this, _nav).prev().highlight();
	          if (!main_core.Type.isNil(babelHelpers.classPrivateFieldGet(_this, _nav).current())) {
	            _this.updateScroll(babelHelpers.classPrivateFieldGet(_this, _nav).current());
	          }
	          return;
	        }
	      if (event.keyCode === 40)
	        //down
	        {
	          babelHelpers.classPrivateFieldGet(_this, _nav).next().highlight();
	          if (!main_core.Type.isNil(babelHelpers.classPrivateFieldGet(_this, _nav).current())) {
	            _this.updateScroll(babelHelpers.classPrivateFieldGet(_this, _nav).current());
	          }
	          return;
	        }
	      if (_this.getQuery().length > 0) {
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(_this, _iconContainer), 'main-ui-show');
	      } else {
	        main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this, _iconContainer), 'main-ui-show');
	      }
	      if (!babelHelpers.classPrivateFieldGet(_this, _popup).isShown() && _this.getQuery().length > 0) {
	        babelHelpers.classPrivateFieldGet(_this, _popup).show();
	      }
	      _this.find();
	    });
	  }
	  babelHelpers.createClass(Renderer, [{
	    key: "updateScroll",
	    value: function updateScroll(element) {
	      var rect = element.getBoundingClientRect();
	      var container = babelHelpers.classPrivateFieldGet(this, _popup).getContentContainer().firstElementChild;
	      var relTop = rect.top - container.getBoundingClientRect().top;
	      var relBot = rect.bottom - container.getBoundingClientRect().bottom;
	      var padding = 10;
	      if (relTop < 0 && relBot <= 0)
	        //invisible top
	        {
	          container.scrollTo(0, relTop + container.scrollTop - padding);
	        } else if (relTop >= 0 && relBot > 0)
	        //invisible bottom
	        {
	          container.scrollTo(0, relBot + container.scrollTop + padding);
	        }
	    }
	  }, {
	    key: "renderWait",
	    value: function renderWait() {
	      var loaderContainer = main_core.Tag.render(_templateObject$m || (_templateObject$m = babelHelpers.taggedTemplateLiteral(["<span class=\"title-search-waiter-img\"></span>"])));
	      var loader = new main_loader.Loader({
	        target: loaderContainer,
	        size: 20,
	        mode: 'inline'
	      });
	      loader.show();
	      return main_core.Tag.render(_templateObject2$d || (_templateObject2$d = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"title-search-waiter\">\n\t\t\t\t", "\n\t\t\t\t<span class=\"title-search-waiter-text\">", "</span>\n\t\t\t</div>\n\t\t"])), loaderContainer, main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_SEARCHING'));
	    }
	  }, {
	    key: "find",
	    value: function find() {
	      var _this2 = this;
	      clearTimeout(babelHelpers.classPrivateFieldGet(this, _timeoutId));
	      babelHelpers.classPrivateFieldSet(this, _timeoutId, setTimeout(function () {
	        babelHelpers.classPrivateFieldGet(_this2, _searcher).find(_this2.getQuery());
	      }, babelHelpers.classPrivateFieldGet(this, _timeout)));
	    }
	  }, {
	    key: "getQuery",
	    value: function getQuery() {
	      return BX.util.trim(babelHelpers.classPrivateFieldGet(this, _inputNode).value);
	    }
	  }, {
	    key: "createLinkOption",
	    value: function createLinkOption(option) {
	      var _this3 = this;
	      var link = main_core.Dom.create('a', {
	        props: {
	          className: 'search-title-top-item-link'
	        },
	        events: {
	          mouseenter: function mouseenter(event) {
	            babelHelpers.classPrivateFieldGet(_this3, _nav).unHighlightAll();
	            babelHelpers.classPrivateFieldGet(_this3, _nav).cursorTo(event.target);
	            SearchNavigation.highlight(event.target);
	          },
	          mouseleave: function mouseleave(event) {
	            SearchNavigation.unHighlight(event.target);
	          }
	        },
	        attrs: {
	          title: option.title,
	          href: main_core.Type.isStringFilled(option.url) ? option.url : '#',
	          target: '_blank'
	        },
	        children: [main_core.Tag.render(_templateObject3$a || (_templateObject3$a = babelHelpers.taggedTemplateLiteral(["<span class=\"search-title-top-item-text\"><span>", "</span></span>"])), option.title)]
	      });
	      babelHelpers.classPrivateFieldGet(this, _nav).add(link);
	      return link;
	    }
	  }, {
	    key: "createBtnOption",
	    value: function createBtnOption(page, option) {
	      var _this4 = this;
	      var link = main_core.Dom.create('a', {
	        props: {
	          className: 'search-title-top-item-link'
	        },
	        events: {
	          click: function click(event) {
	            main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.SettingsNavigation:onMove', {
	              page: page,
	              fieldName: option.code
	            });
	            babelHelpers.classPrivateFieldGet(_this4, _inputNode).blur();
	            babelHelpers.classPrivateFieldGet(_this4, _popup).close();
	            event.preventDefault();
	          },
	          mouseenter: function mouseenter(event) {
	            babelHelpers.classPrivateFieldGet(_this4, _nav).unHighlightAll();
	            babelHelpers.classPrivateFieldGet(_this4, _nav).cursorTo(event.target);
	            SearchNavigation.highlight(event.target);
	          },
	          mouseleave: function mouseleave(event) {
	            SearchNavigation.unHighlight(event.target);
	          }
	        },
	        attrs: {
	          title: option.title,
	          href: "#"
	        },
	        children: [main_core.Tag.render(_templateObject4$3 || (_templateObject4$3 = babelHelpers.taggedTemplateLiteral(["<span class=\"search-title-top-item-text\"><span>", "</span></span>"])), option.title)]
	      });
	      babelHelpers.classPrivateFieldGet(this, _nav).add(link);
	      return main_core.Tag.render(_templateObject5$2 || (_templateObject5$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"search-title-top-item search-title-top-item-js\">", "</div>"])), link);
	    }
	  }, {
	    key: "renderOption",
	    value: function renderOption(page, option) {
	      var link;
	      if (page === Renderer.EXTERNAL_LINK) {
	        link = this.createLinkOption(option);
	      } else {
	        link = this.createBtnOption(page, option);
	      }
	      return main_core.Tag.render(_templateObject6$2 || (_templateObject6$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"search-title-top-item search-title-top-item-js\">", "</div>"])), link);
	    }
	  }, {
	    key: "renderGroup",
	    value: function renderGroup(group) {
	      var _this5 = this;
	      var optionsContainer = main_core.Tag.render(_templateObject7$2 || (_templateObject7$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"search-title-top-list search-title-top-list-js\"></div>"])));
	      group.options.forEach(function (option) {
	        main_core.Dom.append(_this5.renderOption(group.page, option), optionsContainer);
	      });
	      return main_core.Tag.render(_templateObject8$2 || (_templateObject8$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"search-title-top-block search-title-top-block-sonetgroups\">\n\t\t\t\t<div class=\"search-title-top-subtitle\">\n\t\t\t\t\t<div class=\"search-title-top-subtitle-text\">", "</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"search-title-top-list-wrap\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), group.title, optionsContainer);
	    }
	  }, {
	    key: "renderContent",
	    value: function renderContent() {
	      var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'ready';
	      var optionsContainer = main_core.Tag.render(_templateObject9$2 || (_templateObject9$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"search-title-top-result\"></div>"])));
	      switch (state) {
	        case 'ready':
	          main_core.Dom.append(this.renderSearchResult(babelHelpers.classPrivateFieldGet(this, _searcher).getResult()), optionsContainer);
	          break;
	        case 'wait':
	          main_core.Dom.append(this.renderWait(), optionsContainer);
	          break;
	        case 'not_found':
	          main_core.Dom.append(this.renderNotFound(), optionsContainer);
	          break;
	      }
	      main_core.Dom.append(this.renderOthers(babelHelpers.classPrivateFieldGet(this, _searcher).getOthers()), optionsContainer);
	      return optionsContainer;
	    }
	  }, {
	    key: "renderNotFound",
	    value: function renderNotFound() {
	      return main_core.Tag.render(_templateObject10$1 || (_templateObject10$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"title-search-waiter\">\n\t\t\t\t<span class=\"title-search-waiter-text\">", "</span>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_SEARCH_NOT_FOUND'));
	    }
	  }, {
	    key: "renderSearchResult",
	    value: function renderSearchResult(result) {
	      var _this6 = this;
	      var container = main_core.Tag.render(_templateObject11$1 || (_templateObject11$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"search-title-content-result\"></div>"])));
	      result.forEach(function (item) {
	        main_core.Dom.append(_this6.renderGroup(item), container);
	      });
	      return container;
	    }
	  }, {
	    key: "renderOthers",
	    value: function renderOthers(links) {
	      var _this7 = this;
	      var wraper = main_core.Tag.render(_templateObject12$1 || (_templateObject12$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"search-title-top-list search-title-top-list-js\"></div>"])));
	      var other = main_core.Tag.render(_templateObject13$1 || (_templateObject13$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"search-title-top-block search-title-top-block-tools\">\n\t\t\t<div class=\"search-title-top-subtitle\">\n\t\t\t\t<div class=\"search-title-top-subtitle-text\">", "</div>\n\t\t\t</div>\n\t\t\t<div class=\"search-title-top-list-height-wrap\">\n\t\t\t\t\t<div class=\"search-title-top-list-wrap\">", "</div>\n\t\t\t\t</div>\n\t\t</div>\n\t\t"])), main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_SEARCH_IN'), wraper);
	      links.forEach(function (link) {
	        main_core.Dom.append(_this7.renderOtherLink(link), wraper);
	      });
	      return other;
	    }
	  }, {
	    key: "renderOtherLink",
	    value: function renderOtherLink(link) {
	      var _this8 = this;
	      var linkTag = main_core.Dom.create('a', {
	        props: {
	          className: 'search-title-top-item-link'
	        },
	        events: {
	          mouseenter: function mouseenter(event) {
	            babelHelpers.classPrivateFieldGet(_this8, _nav).unHighlightAll();
	            babelHelpers.classPrivateFieldGet(_this8, _nav).cursorTo(event.target);
	            SearchNavigation.highlight(event.target);
	          },
	          mouseleave: function mouseleave(event) {
	            SearchNavigation.unHighlight(event.target);
	          }
	        },
	        attrs: {
	          title: link.title,
	          href: link.link,
	          target: 'blank_'
	        },
	        children: [main_core.Tag.render(_templateObject14$1 || (_templateObject14$1 = babelHelpers.taggedTemplateLiteral(["<span class=\"search-title-top-item-text\"><span>", "</span></span>"])), link.title)]
	      });
	      babelHelpers.classPrivateFieldGet(this, _nav).add(linkTag);
	      return main_core.Tag.render(_templateObject15$1 || (_templateObject15$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"search-title-top-item search-title-top-item-js\">\n\t\t\t", "\n\t\t</div>"])), linkTag);
	    }
	  }]);
	  return Renderer;
	}();
	babelHelpers.defineProperty(Renderer, "EXTERNAL_LINK", 'EXTERNAL_LINKS');
	var _index = /*#__PURE__*/new WeakMap();
	var _elementList = /*#__PURE__*/new WeakMap();
	var SearchNavigation = /*#__PURE__*/function () {
	  function SearchNavigation() {
	    var nodeList = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
	    babelHelpers.classCallCheck(this, SearchNavigation);
	    _classPrivateFieldInitSpec$o(this, _index, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$o(this, _elementList, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _elementList, nodeList);
	  }
	  babelHelpers.createClass(SearchNavigation, [{
	    key: "add",
	    value: function add(element) {
	      babelHelpers.classPrivateFieldGet(this, _elementList).push(element);
	    }
	  }, {
	    key: "clean",
	    value: function clean() {
	      babelHelpers.classPrivateFieldSet(this, _index, null);
	      babelHelpers.classPrivateFieldSet(this, _elementList, []);
	    }
	  }, {
	    key: "next",
	    value: function next() {
	      if (main_core.Type.isNil(babelHelpers.classPrivateFieldGet(this, _index))) {
	        babelHelpers.classPrivateFieldSet(this, _index, 0);
	        return this;
	      }
	      if (babelHelpers.classPrivateFieldGet(this, _elementList).length - 1 > babelHelpers.classPrivateFieldGet(this, _index)) {
	        var _this$index, _this$index2;
	        babelHelpers.classPrivateFieldSet(this, _index, (_this$index = babelHelpers.classPrivateFieldGet(this, _index), _this$index2 = _this$index++, _this$index)), _this$index2;
	      }
	      return this;
	    }
	  }, {
	    key: "current",
	    value: function current() {
	      if (main_core.Type.isNil(babelHelpers.classPrivateFieldGet(this, _index))) {
	        return null;
	      }
	      return babelHelpers.classPrivateFieldGet(this, _elementList)[babelHelpers.classPrivateFieldGet(this, _index)];
	    }
	  }, {
	    key: "prev",
	    value: function prev() {
	      if (main_core.Type.isNil(babelHelpers.classPrivateFieldGet(this, _index))) {
	        babelHelpers.classPrivateFieldSet(this, _index, babelHelpers.classPrivateFieldGet(this, _elementList).length - 1);
	        return this;
	      }
	      if (babelHelpers.classPrivateFieldGet(this, _index) > 0) {
	        babelHelpers.classPrivateFieldSet(this, _index, babelHelpers.classPrivateFieldGet(this, _index) - 1);
	      }
	      return this;
	    }
	  }, {
	    key: "highlight",
	    value: function highlight() {
	      this.unHighlightAll();
	      if (!main_core.Dom.hasClass(this.current(), 'active')) {
	        main_core.Dom.addClass(this.current(), 'active');
	      }
	      return this;
	    }
	  }, {
	    key: "unHighlight",
	    value: function unHighlight() {
	      if (main_core.Dom.hasClass(this.current(), 'active')) {
	        main_core.Dom.removeClass(this.current(), 'active');
	      }
	      return this;
	    }
	  }, {
	    key: "cursorTo",
	    value: function cursorTo(element) {
	      var _this9 = this;
	      babelHelpers.classPrivateFieldGet(this, _elementList).forEach(function (item, index) {
	        if (item === element) {
	          babelHelpers.classPrivateFieldSet(_this9, _index, index);
	          return;
	        }
	      });
	    }
	  }, {
	    key: "unHighlightAll",
	    value: function unHighlightAll() {
	      babelHelpers.classPrivateFieldGet(this, _elementList).forEach(function (item) {
	        SearchNavigation.unHighlight(item);
	      });
	    }
	  }], [{
	    key: "highlight",
	    value: function highlight(element) {
	      element.classList.add('active');
	    }
	  }, {
	    key: "unHighlight",
	    value: function unHighlight(element) {
	      element.classList.remove('active');
	    }
	  }]);
	  return SearchNavigation;
	}();

	var ServerDataSource = /*#__PURE__*/function (_DataSource) {
	  babelHelpers.inherits(ServerDataSource, _DataSource);
	  function ServerDataSource() {
	    babelHelpers.classCallCheck(this, ServerDataSource);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ServerDataSource).call(this));
	  }
	  babelHelpers.createClass(ServerDataSource, [{
	    key: "fetch",
	    value: function fetch(query) {
	      return main_core.ajax.runComponentAction('bitrix:intranet.settings', 'search', {
	        mode: 'class',
	        data: {
	          query: query
	        }
	      });
	    }
	  }]);
	  return ServerDataSource;
	}(DataSource);

	function _classPrivateFieldInitSpec$p(obj, privateMap, value) { _checkPrivateRedeclaration$u(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$u(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _permission = /*#__PURE__*/new WeakMap();
	var Permission = /*#__PURE__*/function () {
	  function Permission() {
	    var permission = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	    babelHelpers.classCallCheck(this, Permission);
	    _classPrivateFieldInitSpec$p(this, _permission, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _permission, permission);
	  }
	  babelHelpers.createClass(Permission, [{
	    key: "canRead",
	    value: function canRead() {
	      return !!(babelHelpers.classPrivateFieldGet(this, _permission) & Permission.READ);
	    }
	  }, {
	    key: "canEdit",
	    value: function canEdit() {
	      return !!(babelHelpers.classPrivateFieldGet(this, _permission) & Permission.EDIT);
	    }
	  }, {
	    key: "getPermission",
	    value: function getPermission() {
	      return babelHelpers.classPrivateFieldGet(this, _permission);
	    }
	  }]);
	  return Permission;
	}();
	babelHelpers.defineProperty(Permission, "READ", 1 << 0);
	babelHelpers.defineProperty(Permission, "EDIT", 1 << 2);

	function _createForOfIteratorHelper$5(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$5(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$5(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$5(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$5(o, minLen); }
	function _arrayLikeToArray$5(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec$g(obj, privateSet) { _checkPrivateRedeclaration$v(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$q(obj, privateMap, value) { _checkPrivateRedeclaration$v(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$v(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$g(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _basePage = /*#__PURE__*/new WeakMap();
	var _menuNode = /*#__PURE__*/new WeakMap();
	var _settingsNode = /*#__PURE__*/new WeakMap();
	var _contentNode = /*#__PURE__*/new WeakMap();
	var _pageManager = /*#__PURE__*/new WeakMap();
	var _cancelMessageBox = /*#__PURE__*/new WeakMap();
	var _analytic = /*#__PURE__*/new WeakMap();
	var _navigator = /*#__PURE__*/new WeakMap();
	var _permission$1 = /*#__PURE__*/new WeakMap();
	var _pagesPermission = /*#__PURE__*/new WeakMap();
	var _extraSettings = /*#__PURE__*/new WeakMap();
	var _getPageManager = /*#__PURE__*/new WeakSet();
	var _onEventFetchPage = /*#__PURE__*/new WeakSet();
	var _onSliderCloseHandler = /*#__PURE__*/new WeakSet();
	var _reload = /*#__PURE__*/new WeakSet();
	var _onEventChangeData = /*#__PURE__*/new WeakSet();
	var _onClickSaveBtn = /*#__PURE__*/new WeakSet();
	var _successSaveHandler = /*#__PURE__*/new WeakSet();
	var _failSaveHandler = /*#__PURE__*/new WeakSet();
	var _prepareErrorCollection = /*#__PURE__*/new WeakSet();
	var _onClickCancelBtn = /*#__PURE__*/new WeakSet();
	var _hideWaitIcon = /*#__PURE__*/new WeakSet();
	var _selectPageForError = /*#__PURE__*/new WeakSet();
	var Settings = /*#__PURE__*/function (_BaseSettingsElement) {
	  babelHelpers.inherits(Settings, _BaseSettingsElement);
	  function Settings(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, Settings);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Settings).call(this, params));
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _selectPageForError);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _hideWaitIcon);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _onClickCancelBtn);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _prepareErrorCollection);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _failSaveHandler);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _successSaveHandler);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _onClickSaveBtn);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _onEventChangeData);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _reload);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _onSliderCloseHandler);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _onEventFetchPage);
	    _classPrivateMethodInitSpec$g(babelHelpers.assertThisInitialized(_this), _getPageManager);
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _basePage, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "isChanged", false);
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _menuNode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _settingsNode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _contentNode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _pageManager, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _cancelMessageBox, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _analytic, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _navigator, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _permission$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _pagesPermission, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$q(babelHelpers.assertThisInitialized(_this), _extraSettings, {
	      writable: true,
	      value: {
	        reloadAfterClose: false
	      }
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _analytic, new Analytic({
	      isAdmin: true,
	      locationName: 'settings',
	      isBitrix24: params.isBitrix24 === true,
	      analyticContext: main_core.Type.isStringFilled(params.analyticContext) ? params.analyticContext : null
	    }));
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _analytic).addEventOpenSettings();
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _analytic).addEventStartPagePage(params.startPage);
	    _this.setEventNamespace('BX.Intranet.Settings');
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'button-click', function (event) {
	      var _event$data = babelHelpers.slicedToArray(event.data, 1),
	        clickedBtn = _event$data[0];
	      if (clickedBtn.TYPE === 'save') {
	        _classPrivateMethodGet$g(babelHelpers.assertThisInitialized(_this), _onClickSaveBtn, _onClickSaveBtn2).call(babelHelpers.assertThisInitialized(_this), event);
	      }
	    });
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onClose', _classPrivateMethodGet$g(babelHelpers.assertThisInitialized(_this), _onSliderCloseHandler, _onSliderCloseHandler2).bind(babelHelpers.assertThisInitialized(_this)));
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _menuNode, main_core.Type.isDomNode(params.menuNode) ? params.menuNode : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _settingsNode, main_core.Type.isDomNode(params.settingsNode) ? params.settingsNode : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _contentNode, main_core.Type.isDomNode(params.contentNode) ? params.contentNode : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _basePage, main_core.Type.isString(params.basePage) ? params.basePage : '');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _permission$1, params.permission instanceof Permission ? params.permission : new Permission());
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _pagesPermission, params.pagesPermission);
	    if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _settingsNode)) {
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _settingsNode).querySelector('.ui-button-panel input[name="cancel"]').addEventListener('click', _classPrivateMethodGet$g(babelHelpers.assertThisInitialized(_this), _onClickCancelBtn, _onClickCancelBtn2));
	    }
	    params.pages.concat(Object.values(params.externalPages).map(function (_ref) {
	      var type = _ref.type,
	        extensions = _ref.extensions;
	      return new ExternalTemporaryPage(type, extensions);
	    })).forEach(function (page) {
	      return _this.registerPage(page).expandPage(params.subPages[page.getType()]);
	    });
	    var toolsMenuItem = BX.UI.DropdownMenuItem.getItemByNode(babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _menuNode).querySelector('[data-type="tools"]'));
	    if (toolsMenuItem.subItems && toolsMenuItem.subItems.length > 0) {
	      toolsMenuItem.hideSubmenu();
	      toolsMenuItem.setDefaultToggleButtonName();
	    }
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _navigator, new Navigation(babelHelpers.assertThisInitialized(_this)));
	    if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _menuNode)) {
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _menuNode).querySelectorAll('li.ui-sidepanel-menu-item a.ui-sidepanel-menu-link').forEach(function (item) {
	        var helpPopup = new ui_section.HelpMessage(item.dataset.type + '_help-msg', item, main_core.Loc.getMessage('INTRANET_SETTINGS_PERMISSION_MSG'));
	        helpPopup.getPopup().setWidth(275);
	        var page = _this.getNavigator().getPageByType(item.dataset.type);
	        item.addEventListener('click', function (event) {
	          var _page$getPermission;
	          if (page !== null && page !== void 0 && (_page$getPermission = page.getPermission()) !== null && _page$getPermission !== void 0 && _page$getPermission.canRead()) {
	            _this.show(item.dataset.type);
	          } else {
	            helpPopup.show();
	          }
	        });
	      });
	    }
	    return _this;
	  }
	  babelHelpers.createClass(Settings, [{
	    key: "registerPage",
	    value: function registerPage(page) {
	      var _babelHelpers$classPr;
	      page.setParentElement(this);
	      page.setPermission(new Permission((_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _pagesPermission)[page.getType()]) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : null));
	      page.subscribe('change', _classPrivateMethodGet$g(this, _onEventChangeData, _onEventChangeData2).bind(this)).subscribe('fetch', _classPrivateMethodGet$g(this, _onEventFetchPage, _onEventFetchPage2).bind(this));
	      page.setAnalytic(babelHelpers.classPrivateFieldGet(this, _analytic));
	      return page;
	    }
	  }, {
	    key: "getCurrentPage",
	    value: function getCurrentPage() {
	      return this.getNavigator().getCurrentPage();
	    }
	  }, {
	    key: "getNavigator",
	    value: function getNavigator() {
	      return babelHelpers.classPrivateFieldGet(this, _navigator);
	    }
	  }, {
	    key: "show",
	    value: function show(type, option) {
	      var _this$getNavigator$ge,
	        _this2 = this;
	      if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _contentNode))) {
	        console.log('Not found settings container');
	        return;
	      }
	      if (!babelHelpers.classPrivateFieldGet(this, _permission$1).canRead()) {
	        return;
	      }
	      var nextPage = this.getNavigator().getPageByType(type);
	      if (this.getCurrentPage() === nextPage) {
	        return;
	      }
	      this.getNavigator().changePage(nextPage);
	      main_core.Dom.hide((_this$getNavigator$ge = this.getNavigator().getPrevPage()) === null || _this$getNavigator$ge === void 0 ? void 0 : _this$getNavigator$ge.getPage());
	      if (main_core.Type.isNil(this.getNavigator().getCurrentPage().getPage().parentNode)) {
	        main_core.Dom.append(this.getNavigator().getCurrentPage().getPage(), babelHelpers.classPrivateFieldGet(this, _contentNode));
	      } else {
	        main_core.Dom.show(this.getNavigator().getCurrentPage().getPage());
	      }
	      this.activateMenuItem(type);
	      babelHelpers.classPrivateFieldGet(this, _analytic).addEventChangePage(type);
	      this.getNavigator().updateAddressBar();
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onAfterShowPage', {
	        source: this,
	        page: nextPage
	      });
	      if (main_core.Type.isString(option) && option !== '') {
	        main_core_events.EventEmitter.subscribeOnce(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onPageComplete', function () {
	          console.log(option);
	          _this2.getNavigator().moveTo(nextPage, option);
	        });
	      }
	    }
	  }, {
	    key: "activateMenuItem",
	    value: function activateMenuItem(type) {
	      var menuItem = BX.UI.DropdownMenuItem.getItemByNode(babelHelpers.classPrivateFieldGet(this, _menuNode).querySelector("a.ui-sidepanel-menu-link[data-type=\"".concat(type, "\"]")));
	      menuItem && menuItem.setActiveHandler();
	    }
	  }]);
	  return Settings;
	}(ui_formElements_field.BaseSettingsElement);
	function _getPageManager2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _pageManager)) {
	    babelHelpers.classPrivateFieldSet(this, _pageManager, new PageManager(this.getChildrenElements()));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _pageManager);
	}
	function _onEventFetchPage2(event) {
	  return _classPrivateMethodGet$g(this, _getPageManager, _getPageManager2).call(this).fetchPage(event.getTarget());
	}
	function _onSliderCloseHandler2(event) {
	  var _panelEvent$slider$ge,
	    _this3 = this;
	  var _event$getCompatData = event.getCompatData(),
	    _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	    panelEvent = _event$getCompatData2[0];
	  if (babelHelpers.classPrivateFieldGet(this, _cancelMessageBox) instanceof ui_dialogs_messagebox.MessageBox) {
	    panelEvent.denyAction();
	    return false;
	  }
	  if (this.isChanged && ((_panelEvent$slider$ge = panelEvent.slider.getData()) === null || _panelEvent$slider$ge === void 0 ? void 0 : _panelEvent$slider$ge.get('ignoreChanges')) !== true) {
	    panelEvent.denyAction();
	    babelHelpers.classPrivateFieldSet(this, _cancelMessageBox, ui_dialogs_messagebox.MessageBox.create({
	      message: main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DESC'),
	      modal: true,
	      buttons: [new BX.UI.Button({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_OK'),
	        color: BX.UI.Button.Color.SUCCESS,
	        events: {
	          click: function click() {
	            main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onCancel', {});
	            panelEvent.slider.getData().set('ignoreChanges', true);
	            _this3.isChanged = false;
	            BX.UI.ButtonPanel.hide();
	            babelHelpers.classPrivateFieldGet(_this3, _cancelMessageBox).close();
	            babelHelpers.classPrivateFieldSet(_this3, _cancelMessageBox, null);
	            panelEvent.slider.close();
	            panelEvent.slider.destroy();
	            if (babelHelpers.classPrivateFieldGet(_this3, _basePage).includes('/configs/')) {
	              _classPrivateMethodGet$g(_this3, _reload, _reload2).call(_this3, '/index.php');
	            }
	          }
	        }
	      }), new BX.UI.CancelButton({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_CANCEL'),
	        events: {
	          click: function click() {
	            babelHelpers.classPrivateFieldGet(_this3, _cancelMessageBox).close();
	            babelHelpers.classPrivateFieldSet(_this3, _cancelMessageBox, null);
	          }
	        }
	      })]
	    }));
	    return babelHelpers.classPrivateFieldGet(this, _cancelMessageBox).show();
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _basePage).includes('/configs/') || babelHelpers.classPrivateFieldGet(this, _extraSettings).reloadAfterClose === true) {
	    _classPrivateMethodGet$g(this, _reload, _reload2).call(this, '/index.php');
	  }
	}
	function _reload2() {
	  var url = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	  var loader = document.querySelector('#ui-sidepanel-wrapper-loader');
	  if (loader) {
	    loader.style.display = '';
	  }
	  if (main_core.Type.isString(url)) {
	    top.window.location.href = url;
	  } else {
	    top.window.location.href = babelHelpers.classPrivateFieldGet(this, _basePage);
	  }
	}
	function _onEventChangeData2(event) {
	  if (!babelHelpers.classPrivateFieldGet(this, _permission$1).canEdit()) {
	    return;
	  }
	  this.isChanged = true;
	  BX.UI.ButtonPanel.show();
	}
	function _onClickSaveBtn2(event) {
	  var data = _classPrivateMethodGet$g(this, _getPageManager, _getPageManager2).call(this).collectData();
	  main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onBeforeSave', {
	    data: data
	  });
	  babelHelpers.classPrivateFieldGet(this, _analytic).send();
	  main_core.ajax.runComponentAction('bitrix:intranet.settings', 'set', {
	    mode: 'class',
	    data: main_core.Http.Data.convertObjectToFormData(data)
	  }).then(_classPrivateMethodGet$g(this, _successSaveHandler, _successSaveHandler2).bind(this), _classPrivateMethodGet$g(this, _failSaveHandler, _failSaveHandler2).bind(this));
	}
	function _successSaveHandler2(response) {
	  babelHelpers.classPrivateFieldGet(this, _extraSettings).reloadAfterClose = true;
	  this.isChanged = false;
	  _classPrivateMethodGet$g(this, _hideWaitIcon, _hideWaitIcon2).call(this);
	  BX.UI.ButtonPanel.hide();
	  main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onSuccessSave', babelHelpers.classPrivateFieldGet(this, _extraSettings));
	}
	function _failSaveHandler2(response) {
	  var errorCollection = _classPrivateMethodGet$g(this, _prepareErrorCollection, _prepareErrorCollection2).call(this, response.errors);
	  _classPrivateMethodGet$g(this, _hideWaitIcon, _hideWaitIcon2).call(this);
	  main_core_events.EventEmitter.emit('BX.UI.FormElement.Field:onFailedSave', {
	    errors: errorCollection
	  });
	  var pageType = _classPrivateMethodGet$g(this, _selectPageForError, _selectPageForError2).call(this, errorCollection);
	  this.show(pageType);
	}
	function _prepareErrorCollection2(rawErrors) {
	  var errorCollection = {};
	  var _iterator = _createForOfIteratorHelper$5(rawErrors),
	    _step;
	  try {
	    for (_iterator.s(); !(_step = _iterator.n()).done;) {
	      var _error$customData, _error$customData2;
	      var error = _step.value;
	      var type = (_error$customData = error.customData) === null || _error$customData === void 0 ? void 0 : _error$customData.page;
	      var field = (_error$customData2 = error.customData) === null || _error$customData2 === void 0 ? void 0 : _error$customData2.field;
	      if (main_core.Type.isNil(type) || main_core.Type.isNil(field)) {
	        ui_formElements_field.ErrorCollection.showSystemError(main_core.Loc.getMessage('INTRANET_SETTINGS_ERROR_FETCH_DATA'));
	        break;
	      }
	      if (main_core.Type.isNil(errorCollection[type])) {
	        errorCollection[type] = {};
	      }
	      if (main_core.Type.isNil(errorCollection[type][field])) {
	        errorCollection[type][field] = [];
	      }
	      errorCollection[type][field].push(error.message);
	    }
	  } catch (err) {
	    _iterator.e(err);
	  } finally {
	    _iterator.f();
	  }
	  return errorCollection;
	}
	function _onClickCancelBtn2(event) {
	  top.BX.SidePanel.Instance.close();
	}
	function _hideWaitIcon2() {
	  var saveBtnNode = document.querySelector('#intranet-settings-page #ui-button-panel-save');
	  main_core.Dom.removeClass(saveBtnNode, 'ui-btn-wait');
	}
	function _selectPageForError2(errors) {
	  for (var pageType in errors) {
	    return pageType;
	  }
	}

	exports.Settings = Settings;
	exports.ToolsPage = ToolsPage;
	exports.EmployeePage = EmployeePage;
	exports.PortalPage = PortalPage;
	exports.MainpagePage = MainpagePage;
	exports.CommunicationPage = CommunicationPage;
	exports.RequisitePage = RequisitePage;
	exports.ConfigurationPage = ConfigurationPage;
	exports.SchedulePage = SchedulePage;
	exports.GdprPage = GdprPage;
	exports.SecurityPage = SecurityPage;
	exports.Renderer = Renderer;
	exports.Searcher = Searcher;
	exports.ServerDataSource = ServerDataSource;
	exports.Permission = Permission;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.UI.Analytics,BX.UI.DragAndDrop,BX.UI,BX,BX.UI.Uploader,BX.UI,BX,BX.UI,BX,BX,BX,BX,BX.UI.FormElements,BX.UI,BX.UI.EntitySelector,BX.UI,BX,BX.UI,BX,BX.UI.Dialogs,BX.UI.FormElements,BX.Event,BX.Main,BX,BX));
//# sourceMappingURL=script.js.map
