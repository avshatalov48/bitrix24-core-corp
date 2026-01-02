/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_analytics,ui_draganddrop_draggable,ui_switcherNested,ui_iconSet_crm,ui_uploader_stackWidget,ui_ears,intranet_themePicker_dialog,ui_iconSet_social,ui_alerts,ui_form,ui_forms,ui_iconSet_actions,ui_iconSet_main,ui_formElements_view,ui_switcher,ui_cnt,ui_buttons,ui_icon_set,ui_section,sidepanel,ui_dialogs_messagebox,ui_formElements_field,main_core_events,main_popup,main_loader,main_core) {
	'use strict';

	var _eventList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("eventList");
	var _tool = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tool");
	var _context = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	class Analytic {
	  constructor(context = null) {
	    Object.defineProperty(this, _eventList, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _tool, {
	      writable: true,
	      value: 'settings'
	    });
	    Object.defineProperty(this, _context, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _context)[_context] = context;
	  }
	  addEvent(eventType, eventData) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].isBitrix24) {
	      babelHelpers.classPrivateFieldLooseBase(this, _eventList)[_eventList][eventType] = eventData;
	    }
	  }
	  send() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _context)[_context].isBitrix24) {
	      return;
	    }
	    if (Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _eventList)[_eventList]).length > 0) {
	      main_core.ajax.runComponentAction('bitrix:intranet.settings', 'analytic', {
	        mode: 'class',
	        data: {
	          data: babelHelpers.classPrivateFieldLooseBase(this, _eventList)[_eventList]
	        }
	      }).then(() => {});
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _eventList)[_eventList] = [];
	  }
	  addEventOpenSettings() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	    const options = {
	      event: AnalyticSettingsEvent.OPEN,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: 'slider',
	      p1: ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	      c_section: (_babelHelpers$classPr2 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr3.analyticContext) != null ? _babelHelpers$classPr2 : '',
	      c_element: (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr4.locationName
	    };
	    ui_analytics.sendData(options);
	    //this.addEvent(AnalyticSettingsEvent.OPEN, options);
	  }

	  addEventOpenTariffSelector(fieldName) {
	    var _babelHelpers$classPr5;
	    const options = {
	      event: 'open_tariff',
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: fieldName,
	      p1: ((_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr5.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	    };
	    this.addEvent(fieldName + '_open_tariff', options);
	  }
	  addEventOpenHint(fieldName) {
	    var _babelHelpers$classPr6;
	    const options = {
	      event: 'open_hint',
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: fieldName,
	      p1: ((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr6.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	    };
	    this.addEvent(fieldName + '_open_hint', options);
	  }
	  addEventStartPagePage(page) {
	    var _babelHelpers$classPr7;
	    const options = {
	      event: AnalyticSettingsEvent.START_PAGE,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: page,
	      p1: ((_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr7.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	    };
	    ui_analytics.sendData(options);
	  }
	  addEventChangePage(page) {
	    var _babelHelpers$classPr8;
	    const options = {
	      event: AnalyticSettingsEvent.VIEW,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: page,
	      p1: ((_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr8.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	    };
	    ui_analytics.sendData(options);
	  }
	  addEventToggleTools(toolName, state) {
	    var _babelHelpers$classPr9, _babelHelpers$classPr10;
	    const event = 'onoff_tools';
	    const options = {
	      event: event,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: 'tools',
	      type: toolName,
	      c_element: (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr9.locationName,
	      p1: ((_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr10.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	      p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF
	    };
	    this.addEvent('tools' + toolName + '_' + event, options);
	  }
	  addEventToggle2fa(state) {
	    var _babelHelpers$classPr11;
	    const event = '2fa_onoff';
	    const options = {
	      event: event,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: 'security',
	      p1: ((_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr11.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	      p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF
	    };
	    this.addEvent('security_' + event, options);
	  }
	  addEventConfigPortal(event) {
	    const options = {
	      event: event,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: AnalyticSettingsCategory.PORTAL
	    };
	    this.addEvent('portal_' + event, options);
	  }
	  addEventChangeTheme(themeId) {
	    const regex = /custom_\d+/;
	    const preparedThemeId = regex.test(themeId) ? 'themeName_custom' : 'themeName_' + themeId;
	    const options = {
	      event: AnalyticSettingsEvent.CHANGE_PORTAL_THEME,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: AnalyticSettingsCategory.PORTAL,
	      type: AnalyticSettingsType.COMMON,
	      c_section: AnalyticSettingsSection.SETTINGS,
	      p1: preparedThemeId
	    };
	    this.addEvent('portal_' + AnalyticSettingsEvent.CHANGE_PORTAL_THEME, options);
	  }
	  addEventConfigEmployee(event, state) {
	    var _babelHelpers$classPr12;
	    const options = {
	      event: event,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: 'employee',
	      p1: ((_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr12.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	      p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF
	    };
	    this.addEvent('employee_' + event, options);
	  }
	  addEventConfigConfiguration(event, state) {
	    var _babelHelpers$classPr13;
	    const options = {
	      event: event,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: 'configuration',
	      p1: ((_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr13.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
	      p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF
	    };
	    this.addEvent('configuration_' + event, options);
	  }
	  addEventConfigRequisite(event) {
	    var _babelHelpers$classPr14, _babelHelpers$classPr15;
	    const options = {
	      event: event,
	      tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	      category: 'requisite',
	      c_element: (_babelHelpers$classPr14 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr14.locationName,
	      p1: ((_babelHelpers$classPr15 = babelHelpers.classPrivateFieldLooseBase(this, _context)[_context]) == null ? void 0 : _babelHelpers$classPr15.isAdmin) !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN
	    };
	    ui_analytics.sendData(options);
	  }
	}
	class AnalyticSettingsCategory {}
	AnalyticSettingsCategory.TOOLS = 'tools';
	AnalyticSettingsCategory.SECURITY = 'security';
	AnalyticSettingsCategory.AI = 'ai';
	AnalyticSettingsCategory.PORTAL = 'portal';
	AnalyticSettingsCategory.EMPLOYEE = 'employee';
	AnalyticSettingsCategory.COMMUNICATION = 'communication';
	AnalyticSettingsCategory.REQUISITE = 'requisite';
	AnalyticSettingsCategory.SCHEDULE = 'schedule';
	AnalyticSettingsCategory.CONFIGURATION = 'configuration';
	class AnalyticSettingsEvent {}
	AnalyticSettingsEvent.OPEN = 'open_setting';
	AnalyticSettingsEvent.START_PAGE = 'start_page';
	AnalyticSettingsEvent.VIEW = 'view';
	AnalyticSettingsEvent.TFA = '2fa_onoff';
	AnalyticSettingsEvent.CHANGE_PORTAL_NAME = 'change_portal_name';
	AnalyticSettingsEvent.CHANGE_PORTAL_LOGO = 'change_portal_logo';
	AnalyticSettingsEvent.CHANGE_PORTAL_SITE = 'change_portal_site';
	AnalyticSettingsEvent.CHANGE_PORTAL_THEME = 'change_portal_theme';
	AnalyticSettingsEvent.CHANGE_MARKET = 'change_market';
	AnalyticSettingsEvent.CHANGE_PAY_TARIFF = 'change_pay_tariff';
	AnalyticSettingsEvent.CREATE_CARD = 'create_vizitka';
	AnalyticSettingsEvent.EDIT_CARD = 'edit_vizitka';
	AnalyticSettingsEvent.COPY_LINK_CARD = 'copylink_vizitka';
	AnalyticSettingsEvent.OPEN_ADD_COMPANY = 'open_add_company';
	AnalyticSettingsEvent.CHANGE_QUICK_REG = 'change_quick_reg';
	AnalyticSettingsEvent.CHANGE_REG_ALL = 'change_reg_all';
	AnalyticSettingsEvent.CHANGE_COLLABERS_INVITATION = 'change_collabers_invitation';
	AnalyticSettingsEvent.CHANGE_EXTRANET_INVITE = 'change_extranet_invite';
	class AnalyticSettingsSection {}
	AnalyticSettingsSection.SETTINGS = 'settings';
	class AnalyticSettingsType {}
	AnalyticSettingsType.COMMON = 'common';
	class AnalyticSettingsUserRole {}
	AnalyticSettingsUserRole.ADMIN = 'isAdmin_Y';
	AnalyticSettingsUserRole.NOT_ADMIN = 'isAdmin_N';
	class AnalyticSettingsTurnState {}
	AnalyticSettingsTurnState.ON = 'turn_on';
	AnalyticSettingsTurnState.OFF = 'turn_off';

	let _ = t => t,
	  _t;
	var _checker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checker");
	var _content = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	class SiteTitle24Field extends ui_formElements_field.BaseSettingsElement {
	  constructor(params) {
	    var _params$title;
	    super();
	    Object.defineProperty(this, _checker, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Intranet.Settings');
	    babelHelpers.classPrivateFieldLooseBase(this, _checker)[_checker] = new ui_formElements_view.Checker({
	      id: 'siteLogo24',
	      inputName: 'logo24',
	      title: (_params$title = params.title) != null ? _params$title : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_LOGO24'),
	      size: 'extra-small',
	      // hintOn: '',
	      // hintOff: '',
	      isEnable: params.isEnable,
	      checked: params.checked !== '',
	      value: 'Y',
	      bannerCode: 'limit_admin_logo24',
	      hideSeparator: true
	    });
	  }
	  getFieldView() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _checker)[_checker];
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _content)[_content]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _content)[_content];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _content)[_content] = main_core.Tag.render(_t || (_t = _`
			<div class="ui-section__field-selector --align-center">
				<div class="ui-section__hint">
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _checker)[_checker].render());
	    return babelHelpers.classPrivateFieldLooseBase(this, _content)[_content];
	  }
	}

	let _$1 = t => t,
	  _t$1;
	var _content$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _contentLogo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("contentLogo24");
	var _title = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("title");
	var _logo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("logo24");
	var _inputMonitoringIntervalId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputMonitoringIntervalId");
	var _inputMonitoringCountdown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputMonitoringCountdown");
	var _inputMonitoringPrevState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputMonitoringPrevState");
	var _initTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initTitle");
	var _initLogo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initLogo24");
	class SiteTitleField extends ui_formElements_field.BaseSettingsElement {
	  constructor(params) {
	    super(params);
	    Object.defineProperty(this, _initLogo, {
	      value: _initLogo2
	    });
	    Object.defineProperty(this, _initTitle, {
	      value: _initTitle2
	    });
	    Object.defineProperty(this, _content$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _contentLogo, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _title, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _logo, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputMonitoringIntervalId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputMonitoringCountdown, {
	      writable: true,
	      value: 10
	    });
	    Object.defineProperty(this, _inputMonitoringPrevState, {
	      writable: true,
	      value: void 0
	    });
	    this.setParentElement(params.parent);
	    this.setEventNamespace('BX.Intranet.Settings');
	    const _options = params.siteTitleOptions;
	    this.options = {
	      title: _options.title,
	      canUserEditTitle: _options.canUserEditTitle,
	      logo24: _options.logo24,
	      canUserEditLogo24: _options.canUserEditLogo24
	    };
	    const _labels = params.siteTitleLabels;
	    this.labels = {
	      title: _labels.title,
	      logo24: _labels.logo24
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _initTitle)[_initTitle](_options, _labels);
	    babelHelpers.classPrivateFieldLooseBase(this, _initLogo)[_initLogo](_options, _labels);
	  }
	  getFieldView() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _title)[_title];
	  }
	  cancel() {}
	  startInputMonitoring() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringIntervalId)[_inputMonitoringIntervalId] > 0) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringIntervalId)[_inputMonitoringIntervalId] = setInterval(this.monitorInput.bind(this), 500);
	  }
	  stopInputMonitoring() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringIntervalId)[_inputMonitoringIntervalId] > 0) {
	      clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringIntervalId)[_inputMonitoringIntervalId]);
	      babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringIntervalId)[_inputMonitoringIntervalId] = null;
	    }
	  }
	  monitorInput() {
	    const value = babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getInputNode().value;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringPrevState)[_inputMonitoringPrevState] !== value) {
	      babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringCountdown)[_inputMonitoringCountdown] = 10;
	      babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringPrevState)[_inputMonitoringPrevState] = value;
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', new main_core_events.BaseEvent({
	        data: {
	          title: value
	        }
	      }));
	    } else if (--babelHelpers.classPrivateFieldLooseBase(this, _inputMonitoringCountdown)[_inputMonitoringCountdown] <= 0) {
	      this.stopInputMonitoring();
	    }
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1];
	    }
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getInputNode(), 'focus', this.startInputMonitoring.bind(this));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getInputNode(), 'keydown', this.startInputMonitoring.bind(this));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getInputNode(), 'click', this.startInputMonitoring.bind(this));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getInputNode(), 'blur', this.stopInputMonitoring.bind(this));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getInputNode(), 'blur', this.stopInputMonitoring.bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _logo)[_logo].getFieldView().subscribe('change', event => {
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', new main_core_events.BaseEvent({
	        data: {
	          logo24: event.getData() === true ? '24' : ''
	        }
	      }));
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1] = main_core.Tag.render(_t$1 || (_t$1 = _$1`
		<div id="${0}" class="ui-section__field-selector --no-border --no-margin --align-center">
			<div class="ui-section__field-container">
				<div class="ui-section__field-label_box">
					<label class="ui-section__field-label" for="${0}">
						${0}
					</label> 
				</div>
				<div class="ui-section__field-inner">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-block">
						${0}
					</div>
				</div>
			</div>
		</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getId(), babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getName(), babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getLabel(), babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].getInputNode());
	    return babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1];
	  }
	  getLogo24Field() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _logo)[_logo];
	  }
	}
	function _initTitle2(options, labels) {
	  var _labels$title;
	  babelHelpers.classPrivateFieldLooseBase(this, _title)[_title] = new ui_formElements_view.TextInput({
	    value: options.title,
	    placeholder: options.title,
	    label: (_labels$title = labels.title) != null ? _labels$title : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE_INPUT_LABEL'),
	    id: 'siteTitle',
	    inputName: 'title',
	    isEnable: true
	    // bannerCode: '123',
	    // helpDeskCode: '234',
	    // helpMessageProvider: () => {}
	  });

	  babelHelpers.classPrivateFieldLooseBase(this, _title)[_title].setEventNamespace(this.getEventNamespace());
	}
	function _initLogo2(options, labels) {
	  babelHelpers.classPrivateFieldLooseBase(this, _logo)[_logo] = new SiteTitle24Field({
	    title: labels.logo24,
	    isEnable: options.canUserEditLogo24,
	    checked: options.logo24
	  });
	}

	var _settings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settings");
	var _currentPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentPage");
	var _prevPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prevPage");
	class Navigation {
	  constructor(settings) {
	    Object.defineProperty(this, _settings, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentPage, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _prevPage, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings] = settings;
	    main_core_events.EventEmitter.subscribe('BX.Intranet.SettingsNavigation:onMove', event => {
	      var _this$getCurrentPage;
	      const {
	        page,
	        fieldName
	      } = event.data;
	      if (((_this$getCurrentPage = this.getCurrentPage()) == null ? void 0 : _this$getCurrentPage.getType()) === page) {
	        this.moveTo(this.getCurrentPage(), fieldName);
	        return;
	      }
	      const pageObj = this.getPageByType(page);
	      if (!(pageObj != null && pageObj.hasData())) {
	        main_core_events.EventEmitter.subscribeOnce('BX.Intranet.Settings:onPageComplete', event => {
	          if (event.data.page.hasContent()) {
	            this.moveTo(event.data.page, fieldName);
	          }
	        });
	      }
	      main_core_events.EventEmitter.subscribeOnce('BX.Intranet.Settings:onAfterShowPage', event => {
	        if (event.data.page.hasContent()) {
	          this.moveTo(event.data.page, fieldName);
	        }
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].show(page);
	    });
	  }
	  getPageByType(type) {
	    return this.getPages().find(page => {
	      return page.getType() === type;
	    });
	  }
	  getCurrentPage() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _currentPage)[_currentPage];
	  }
	  getPrevPage() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _prevPage)[_prevPage];
	  }
	  changePage(page) {
	    if (!(page instanceof ui_formElements_field.BaseSettingsPage)) {
	      console.log('Not found "' + type + '" page');
	      return;
	    }
	    if (page === babelHelpers.classPrivateFieldLooseBase(this, _currentPage)[_currentPage]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _prevPage)[_prevPage] = babelHelpers.classPrivateFieldLooseBase(this, _currentPage)[_currentPage];
	    babelHelpers.classPrivateFieldLooseBase(this, _currentPage)[_currentPage] = page;
	  }
	  getPages() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].getChildrenElements();
	  }
	  updateAddressBar() {
	    var _this$getCurrentPage2;
	    let url = new URL(window.location.href);
	    url.searchParams.set('page', (_this$getCurrentPage2 = this.getCurrentPage()) == null ? void 0 : _this$getCurrentPage2.getType());
	    url.searchParams.delete('IFRAME');
	    url.searchParams.delete('IFRAME_TYPE');
	    top.window.history.replaceState(null, '', url.toString());
	  }
	  findByFieldName(rootNode, fieldName) {
	    var _node$shift;
	    const node = ui_formElements_field.RecursiveFilteringVisitor.startFrom(rootNode, node => {
	      if (node instanceof ui_formElements_field.SettingsSection && node.getSectionView().getId() === fieldName) {
	        return true;
	      }
	      if (node instanceof ui_formElements_field.TabField && node.getFieldView().getId() === fieldName) {
	        return true;
	      }
	      return (node instanceof ui_formElements_field.SettingsField || node instanceof SiteTitleField || node instanceof SiteTitle24Field) && (node.getFieldView().getName() === fieldName || node.getFieldView().getId() === fieldName);
	    });
	    return (_node$shift = node.shift()) != null ? _node$shift : null;
	  }
	  scrollToNode(node) {
	    const element = node.render();
	    const headerOffset = 45;
	    const elementPosition = element.getBoundingClientRect().top;
	    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
	    scrollTo({
	      top: offsetPosition,
	      behavior: "smooth"
	    });
	  }
	  moveTo(element, fieldName) {
	    const fieldNode = this.findByFieldName(element, fieldName);
	    if (main_core.Type.isNil(fieldNode)) {
	      return;
	    }
	    let isColored = false;
	    ui_formElements_field.AscendingOpeningVisitor.startFrom(fieldNode, element => {
	      if (element instanceof ui_formElements_field.SettingsRow) {
	        element.getRowView().show();
	      } else if (element instanceof ui_formElements_field.SettingsSection) {
	        element.getSectionView().toggle(true, false);
	      } else if (element instanceof ui_formElements_field.TabField) {
	        const tabs = element.getParentElement();
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
	}

	let _$2 = t => t,
	  _t$2;
	var _inputForSaveSortTools = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputForSaveSortTools");
	var _toolsWrapperRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toolsWrapperRow");
	var _draggable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggable");
	var _mainSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mainSection");
	var _settingsSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settingsSection");
	var _renderToolsSelectors = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderToolsSelectors");
	var _getSubToolHelpMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSubToolHelpMessage");
	var _getToolsSelectorsItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getToolsSelectorsItems");
	var _getMainSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMainSection");
	var _getSettingsSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSettingsSection");
	var _getInputForSaveSortTools = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInputForSaveSortTools");
	var _getDraggable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDraggable");
	var _getToolsWrapperRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getToolsWrapperRow");
	var _getWarningMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWarningMessage");
	class ToolsPage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _getWarningMessage, {
	      value: _getWarningMessage2
	    });
	    Object.defineProperty(this, _getToolsWrapperRow, {
	      value: _getToolsWrapperRow2
	    });
	    Object.defineProperty(this, _getDraggable, {
	      value: _getDraggable2
	    });
	    Object.defineProperty(this, _getInputForSaveSortTools, {
	      value: _getInputForSaveSortTools2
	    });
	    Object.defineProperty(this, _getSettingsSection, {
	      value: _getSettingsSection2
	    });
	    Object.defineProperty(this, _getMainSection, {
	      value: _getMainSection2
	    });
	    Object.defineProperty(this, _getToolsSelectorsItems, {
	      value: _getToolsSelectorsItems2
	    });
	    Object.defineProperty(this, _getSubToolHelpMessage, {
	      value: _getSubToolHelpMessage2
	    });
	    Object.defineProperty(this, _renderToolsSelectors, {
	      value: _renderToolsSelectors2
	    });
	    Object.defineProperty(this, _inputForSaveSortTools, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _toolsWrapperRow, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _draggable, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _mainSection, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _settingsSection, {
	      writable: true,
	      value: void 0
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_TOOLS');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_TOOLS');
	  }
	  getType() {
	    return 'tools';
	  }
	  appendSections(contentNode) {
	    const description = new ui_section.Row({
	      content: this.getDescription().getContainer()
	    });
	    new ui_formElements_field.SettingsRow({
	      row: description,
	      parent: babelHelpers.classPrivateFieldLooseBase(this, _getSettingsSection)[_getSettingsSection]()
	    });
	    if (this.hasValue('tools')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _renderToolsSelectors)[_renderToolsSelectors]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _getSettingsSection)[_getSettingsSection]().renderTo(contentNode);
	  }
	  getDescription() {
	    const descriptionText = `
			${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_DESCRIPTION')}
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=18213196')">
				${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		`;
	    return new BX.UI.Alert({
	      text: descriptionText,
	      inline: true,
	      size: BX.UI.Alert.Size.SMALL,
	      color: BX.UI.Alert.Color.PRIMARY,
	      animated: true
	    });
	  }
	}
	function _renderToolsSelectors2() {
	  const tools = this.getValue('tools');
	  const startSort = [];
	  Object.keys(tools).forEach(item => {
	    var _tool$settingsTitle;
	    startSort.push(tools[item].menuId);
	    const tool = tools[item];
	    const subgroups = tool.subgroups;
	    let toolSelectorItems = [];
	    if (Object.keys(subgroups).length > 0) {
	      toolSelectorItems = babelHelpers.classPrivateFieldLooseBase(this, _getToolsSelectorsItems)[_getToolsSelectorsItems](subgroups, tool);
	    }
	    const toolSelector = new ui_switcherNested.SwitcherNested({
	      id: tool.code,
	      title: tool.name,
	      link: this.getPermission().canEdit() ? tool['settings-path'] : null,
	      infoHelperCode: this.getPermission().canEdit() ? tool['infohelper-slider'] : null,
	      linkTitle: (_tool$settingsTitle = tool['settings-title']) != null ? _tool$settingsTitle : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_LINK_SETTINGS'),
	      isChecked: tool.enabled,
	      mainInputName: tool.code,
	      isOpen: false,
	      items: toolSelectorItems,
	      isDisabled: !this.getPermission().canEdit(),
	      isDefault: tool.default,
	      helpMessage: !this.getPermission().canEdit() ? main_core.Loc.getMessage('INTRANET_SETTINGS_ELEMENT_PERMISSION_MSG') : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_DISABLED', {
	        '#TOOL#': tool.name
	      })
	    });
	    const toolSelectorSection = new ui_formElements_field.SettingsSection({
	      section: toolSelector
	    });
	    main_core.Dom.style(toolSelectorSection.getSectionView().render(), 'margin-bottom', '8px');
	    main_core.Dom.attr(toolSelectorSection.getSectionView().render(), 'data-menu-id', tool.menuId);
	    babelHelpers.classPrivateFieldLooseBase(this, _getToolsWrapperRow)[_getToolsWrapperRow]().append(toolSelectorSection.getSectionView().render());
	    new ui_formElements_field.SettingsRow({
	      row: babelHelpers.classPrivateFieldLooseBase(this, _getToolsWrapperRow)[_getToolsWrapperRow](),
	      parent: babelHelpers.classPrivateFieldLooseBase(this, _getSettingsSection)[_getSettingsSection](),
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
	  if (tool.default) {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_MAIN_TOOL', {
	      '#TOOL#': parentToolName != null ? parentToolName : ''
	    });
	  }
	  return '';
	}
	function _getToolsSelectorsItems2(subgroups, tool) {
	  const toolSelectorItems = [];
	  Object.keys(subgroups).forEach(item => {
	    var _subgroupConfig$setti, _ref, _subgroupConfig$defau;
	    const subgroupConfig = subgroups[item];
	    if (main_core.Type.isNull(subgroupConfig.name) || main_core.Type.isNull(subgroupConfig.code) || main_core.Type.isNull(subgroupConfig.enabled)) {
	      return;
	    }
	    const toolSelectorItem = new ui_switcherNested.SwitcherNestedItem({
	      title: subgroupConfig.name,
	      id: subgroupConfig.code,
	      inputName: subgroupConfig.code,
	      isChecked: subgroupConfig.enabled,
	      settingsPath: this.getPermission().canEdit() ? subgroupConfig['settings_path'] : null,
	      settingsTitle: (_subgroupConfig$setti = subgroupConfig['settings_title']) != null ? _subgroupConfig$setti : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_LINK_SETTINGS'),
	      infoHelperCode: this.getPermission().canEdit() ? subgroupConfig['infohelper-slider'] : null,
	      isDisabled: !this.getPermission().canEdit(),
	      isDefault: (_ref = (_subgroupConfig$defau = subgroupConfig.default) != null ? _subgroupConfig$defau : subgroupConfig.disabled) != null ? _ref : false,
	      helpMessage: babelHelpers.classPrivateFieldLooseBase(this, _getSubToolHelpMessage)[_getSubToolHelpMessage](subgroupConfig, tool.name)
	    });
	    if (subgroupConfig.disabled) {
	      main_core.Dom.style(toolSelectorItem.getSwitcher().getNode(), {
	        opacity: '0.4'
	      });
	    } else {
	      main_core_events.EventEmitter.subscribe(toolSelectorItem.getSwitcher(), 'toggled', () => {
	        var _this$getAnalytic;
	        (_this$getAnalytic = this.getAnalytic()) == null ? void 0 : _this$getAnalytic.addEventToggleTools(subgroupConfig.code, toolSelectorItem.getSwitcher().isChecked());
	      });
	    }
	    if (subgroupConfig.code === 'tool_subgroup_team_work_instant_messenger') {
	      main_core.Event.bind(toolSelectorItem.getSwitcher().getNode(), 'click', () => {
	        if (!toolSelectorItem.getSwitcher().isChecked()) {
	          babelHelpers.classPrivateFieldLooseBase(this, _getWarningMessage)[_getWarningMessage](subgroupConfig.code, toolSelectorItem.getSwitcher().getNode(), main_core.Loc.getMessage('INTRANET_SETTINGS_WARNING_TOOL_INSTANT_MESSENGER')).show();
	        }
	      });
	    }
	    toolSelectorItems.push(toolSelectorItem);
	  });
	  return toolSelectorItems;
	}
	function _getMainSection2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _mainSection)[_mainSection]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _mainSection)[_mainSection];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _mainSection)[_mainSection] = new ui_section.Section(this.getValue('sectionTools'));
	  return babelHelpers.classPrivateFieldLooseBase(this, _mainSection)[_mainSection];
	}
	function _getSettingsSection2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _settingsSection)[_settingsSection]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _settingsSection)[_settingsSection];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _settingsSection)[_settingsSection] = new ui_formElements_field.SettingsSection({
	    section: babelHelpers.classPrivateFieldLooseBase(this, _getMainSection)[_getMainSection](),
	    parent: this
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _settingsSection)[_settingsSection];
	}
	function _getInputForSaveSortTools2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _inputForSaveSortTools)[_inputForSaveSortTools]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _inputForSaveSortTools)[_inputForSaveSortTools];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _inputForSaveSortTools)[_inputForSaveSortTools] = main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<input type="hidden" name="tools-sort">
		`));
	  return babelHelpers.classPrivateFieldLooseBase(this, _inputForSaveSortTools)[_inputForSaveSortTools];
	}
	function _getDraggable2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _draggable)[_draggable]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _draggable)[_draggable];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _draggable)[_draggable] = new ui_draganddrop_draggable.Draggable({
	    container: [babelHelpers.classPrivateFieldLooseBase(this, _getToolsWrapperRow)[_getToolsWrapperRow]().render()],
	    draggable: '.--tool-selector',
	    dragElement: '.ui-section__dragdrop-icon-wrapper',
	    type: ui_draganddrop_draggable.Draggable.CLONE
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _draggable)[_draggable];
	}
	function _getToolsWrapperRow2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _toolsWrapperRow)[_toolsWrapperRow]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _toolsWrapperRow)[_toolsWrapperRow];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _toolsWrapperRow)[_toolsWrapperRow] = new ui_section.Row({});
	  return babelHelpers.classPrivateFieldLooseBase(this, _toolsWrapperRow)[_toolsWrapperRow];
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

	var _buildAdditionalSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildAdditionalSection");
	var _buildProfileSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildProfileSection");
	var _buildInviteSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildInviteSection");
	class EmployeePage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _buildInviteSection, {
	      value: _buildInviteSection2
	    });
	    Object.defineProperty(this, _buildProfileSection, {
	      value: _buildProfileSection2
	    });
	    Object.defineProperty(this, _buildAdditionalSection, {
	      value: _buildAdditionalSection2
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_EMPLOYEE');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_EMPLOYEE_BOX');
	  }
	  onSuccessDataFetched(response) {
	    super.onSuccessDataFetched(response);
	    if (this.hasValue('IS_BITRIX_24') && this.getValue('IS_BITRIX_24')) {
	      this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_EMPLOYEE');
	      this.render().querySelector('.intranet-settings__page-header_desc').innerText = this.descriptionPage;
	    }
	  }
	  getType() {
	    return 'employee';
	  }
	  appendSections(contentNode) {
	    let profileSection = babelHelpers.classPrivateFieldLooseBase(this, _buildProfileSection)[_buildProfileSection]();
	    profileSection == null ? void 0 : profileSection.renderTo(contentNode);
	    let inviteSection = babelHelpers.classPrivateFieldLooseBase(this, _buildInviteSection)[_buildInviteSection]();
	    inviteSection == null ? void 0 : inviteSection.renderTo(contentNode);
	    let additionalSection = babelHelpers.classPrivateFieldLooseBase(this, _buildAdditionalSection)[_buildAdditionalSection]();
	    additionalSection == null ? void 0 : additionalSection.renderTo(contentNode);
	  }
	}
	function _buildAdditionalSection2() {
	  if (!this.hasValue('SECTION_ADDITIONAL')) {
	    return;
	  }
	  let additionalSection = new ui_section.Section(this.getValue('SECTION_ADDITIONAL'));
	  let sectionSettings = new ui_formElements_field.SettingsSection({
	    section: additionalSection,
	    parent: this
	  });
	  if (this.hasValue('allow_company_pulse')) {
	    let companyPulseField = new ui_formElements_view.Checker(this.getValue('allow_company_pulse'));
	    main_core_events.EventEmitter.subscribe(companyPulseField.switcher, 'toggled', () => {
	      var _this$getAnalytic;
	      (_this$getAnalytic = this.getAnalytic()) == null ? void 0 : _this$getAnalytic.addEventConfigEmployee(AnalyticSettingsEvent.CHANGE_QUICK_REG, companyPulseField.isChecked());
	    });
	    EmployeePage.addToSectionHelper(companyPulseField, sectionSettings);
	  }
	  return sectionSettings;
	}
	function _buildProfileSection2() {
	  if (!this.hasValue('SECTION_PROFILE')) {
	    return;
	  }
	  let profileSection = new ui_section.Section(this.getValue('SECTION_PROFILE'));
	  let sectionSettings = new ui_formElements_field.SettingsSection({
	    section: profileSection,
	    parent: this
	  });
	  if (this.hasValue('fieldFormatName')) {
	    var _this$getValue$label;
	    let hasSelectValue = false;
	    let currentValue = this.getValue('fieldFormatName').current;
	    for (let value of this.getValue('fieldFormatName').values) {
	      if (value.selected === true) {
	        hasSelectValue = true;
	      }
	    }
	    this.getValue('fieldFormatName').values.push({
	      value: 'other',
	      name: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_OPTION_OTHER'),
	      selected: !hasSelectValue
	    });
	    let nameFormatField = new ui_formElements_view.Selector({
	      label: (_this$getValue$label = this.getValue('fieldFormatName').label) != null ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_NAME_FORMAT'),
	      name: this.getValue('fieldFormatName').name + '_selector',
	      items: this.getValue('fieldFormatName').values,
	      hints: this.getValue('fieldFormatName').hints,
	      current: this.getValue('fieldFormatName').current
	    });
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: nameFormatField
	    });
	    new ui_formElements_field.SettingsRow({
	      child: settingsField,
	      parent: sectionSettings
	    });
	    let customFormatNameField = new ui_formElements_view.TextInput({
	      inputName: this.getValue('fieldFormatName').name,
	      label: '',
	      value: currentValue
	    });
	    settingsField = new ui_formElements_field.SettingsField({
	      fieldView: customFormatNameField
	    });
	    let customFormatNameRow = new ui_section.Row({
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
	    nameFormatField.getInputNode().addEventListener('change', event => {
	      if (event.target.value === 'other') {
	        customFormatNameRow.show();
	      } else {
	        customFormatNameField.getInputNode().value = nameFormatField.getInputNode().value;
	        customFormatNameRow.hide();
	      }
	    });
	  }
	  if (this.hasValue('fieldFormatPhoneNumber')) {
	    let formatNumberField = new ui_formElements_view.Selector(this.getValue('fieldFormatPhoneNumber'));
	    EmployeePage.addToSectionHelper(formatNumberField, sectionSettings);
	  }
	  if (this.hasValue('fieldFormatAddress')) {
	    let addressFormatField = new ui_formElements_view.Selector(this.getValue('fieldFormatAddress'));
	    let addressFormatRow = new ui_section.Row({
	      separator: this.hasValue('show_year_for_female') ? 'bottom' : null,
	      className: '--block'
	    });
	    EmployeePage.addToSectionHelper(addressFormatField, sectionSettings, addressFormatRow);
	  }
	  if (this.hasValue('show_year_for_female')) {
	    let showBirthYearField = new ui_formElements_view.InlineChecker(this.getValue('show_year_for_female'));
	    EmployeePage.addToSectionHelper(showBirthYearField, sectionSettings);
	  }
	  return sectionSettings;
	}
	function _buildInviteSection2() {
	  if (!this.hasValue('SECTION_INVITE')) {
	    return;
	  }
	  let inviteSection = new ui_section.Section(this.getValue('SECTION_INVITE'));
	  let sectionSettings = new ui_formElements_field.SettingsSection({
	    section: inviteSection,
	    parent: this
	  });
	  if (this.hasValue('allow_register')) {
	    let fastReqField = new ui_formElements_view.Checker(this.getValue('allow_register'));
	    main_core_events.EventEmitter.subscribe(fastReqField.switcher, 'toggled', () => {
	      var _this$getAnalytic2;
	      (_this$getAnalytic2 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic2.addEventConfigEmployee(AnalyticSettingsEvent.CHANGE_QUICK_REG, fastReqField.isChecked());
	    });
	    EmployeePage.addToSectionHelper(fastReqField, sectionSettings);
	  }
	  if (this.hasValue('allow_invite_collabers')) {
	    let inviteCollabersField = new ui_formElements_view.Checker(this.getValue('allow_invite_collabers'));
	    main_core_events.EventEmitter.subscribe(inviteCollabersField.switcher, 'toggled', () => {
	      var _this$getAnalytic3;
	      (_this$getAnalytic3 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic3.addEventConfigEmployee(AnalyticSettingsEvent.CHANGE_COLLABERS_INVITATION, inviteCollabersField.isChecked());
	    });
	    EmployeePage.addToSectionHelper(inviteCollabersField, sectionSettings);
	  }
	  if (this.hasValue('show_fired_employees')) {
	    let showQuitField = new ui_formElements_view.Checker(this.getValue('show_fired_employees'));
	    EmployeePage.addToSectionHelper(showQuitField, sectionSettings);
	  }
	  if (this.hasValue('general_chat_message_join')) {
	    let newUserField = new ui_formElements_view.Checker(this.getValue('general_chat_message_join'));
	    EmployeePage.addToSectionHelper(newUserField, sectionSettings);
	  }
	  if (this.hasValue('allow_new_user_lf')) {
	    let newUserLfField = new ui_formElements_view.Checker(this.getValue('allow_new_user_lf'));
	    EmployeePage.addToSectionHelper(newUserLfField, sectionSettings);
	  }
	  if (this.hasValue('feature_extranet')) {
	    let extranetField = new ui_formElements_view.Checker(this.getValue('feature_extranet'));
	    main_core_events.EventEmitter.subscribe(extranetField.switcher, 'toggled', () => {
	      var _this$getAnalytic4;
	      (_this$getAnalytic4 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic4.addEventConfigEmployee(AnalyticSettingsEvent.CHANGE_EXTRANET_INVITE, extranetField.isChecked());
	    });
	    EmployeePage.addToSectionHelper(extranetField, sectionSettings);
	  }
	  return sectionSettings;
	}

	let _$3 = t => t,
	  _t$3;
	var _buttonBarElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonBarElement");
	var _buttons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttons");
	class ButtonBar {
	  constructor(buttons = []) {
	    Object.defineProperty(this, _buttonBarElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _buttons, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons] = buttons;
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _buttonBarElement)[_buttonBarElement]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _buttonBarElement)[_buttonBarElement];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonBarElement)[_buttonBarElement] = main_core.Tag.render(_t$3 || (_t$3 = _$3`<div class="intranet-settings__button_bar"></div>`));
	    for (const button of babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons]) {
	      main_core.Dom.append(button.getContainer(), babelHelpers.classPrivateFieldLooseBase(this, _buttonBarElement)[_buttonBarElement]);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _buttonBarElement)[_buttonBarElement];
	  }
	  getButtons() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons];
	  }
	  addButton(button) {
	    babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons].push(button);
	    main_core.Dom.append(button.getContainer(), this.render());
	  }
	}

	var _button = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("button");
	class LandingButton {
	  constructor() {
	    Object.defineProperty(this, _button, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _button)[_button] = new ui_buttons.Button({
	      className: 'landing-button-trigger',
	      round: true,
	      noCaps: true,
	      size: BX.UI.Button.Size.MEDIUM,
	      color: BX.UI.Button.Color.LIGHT_BORDER
	    });
	  }
	  setState(state) {
	    state.apply(babelHelpers.classPrivateFieldLooseBase(this, _button)[_button]);
	  }
	  getButton() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _button)[_button];
	  }
	}
	class LandingButtonState {
	  apply(button) {}
	}
	var _landing = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("landing");
	class EditState extends LandingButtonState {
	  constructor(landing) {
	    super();
	    Object.defineProperty(this, _landing, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _landing)[_landing] = landing;
	  }
	  apply(button) {
	    button.setText(main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_EDIT'));
	    button.setDropdown(false);
	    button.bindEvent('click', () => {
	      this.openNewTab(babelHelpers.classPrivateFieldLooseBase(this, _landing)[_landing].edit_url);
	    });
	  }
	  openNewTab(url) {
	    window.open(url, '_blank').focus();
	  }
	}
	var _landing2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("landing");
	var _menuRenderer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuRenderer");
	class ShowState extends LandingButtonState {
	  constructor(landing, menuRenderer) {
	    super();
	    Object.defineProperty(this, _landing2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuRenderer, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _landing2)[_landing2] = landing;
	    babelHelpers.classPrivateFieldLooseBase(this, _menuRenderer)[_menuRenderer] = menuRenderer;
	  }
	  apply(button) {
	    button.setText(main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_SITE'));
	    button.setDropdown(true);
	    button.unbindEvent('click');
	    button.setColor(BX.UI.Button.Color.PRIMARY);
	    button.setMenu(babelHelpers.classPrivateFieldLooseBase(this, _menuRenderer)[_menuRenderer](babelHelpers.classPrivateFieldLooseBase(this, _landing2)[_landing2]));
	  }
	}
	var _requestBuilder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestBuilder");
	var _menuRenderer2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuRenderer");
	class CreateState extends LandingButtonState {
	  constructor(request, menuRenderer) {
	    super();
	    Object.defineProperty(this, _requestBuilder, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuRenderer2, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _requestBuilder)[_requestBuilder] = request;
	    babelHelpers.classPrivateFieldLooseBase(this, _menuRenderer2)[_menuRenderer2] = menuRenderer;
	  }
	  apply(button) {
	    button.setText(main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_CREATE'));
	    button.setDropdown(false);
	    button.bindEvent('click', event => {
	      if (button.getState() === ui_buttons.ButtonState.WAITING) {
	        return;
	      }
	      button.setState(ui_buttons.ButtonState.WAITING);
	      babelHelpers.classPrivateFieldLooseBase(this, _requestBuilder)[_requestBuilder]().then(response => {
	        const landing = response.data;
	        button.setState(null);
	        button.unbindEvent('click');
	        if (landing.is_public) {
	          new ShowState(landing, babelHelpers.classPrivateFieldLooseBase(this, _menuRenderer2)[_menuRenderer2]).apply(button);
	        } else {
	          const state = new EditState(landing);
	          state.apply(button);
	          state.openNewTab(landing.edit_url);
	        }
	      }, response => {
	        button.setState(null);
	        ui_formElements_field.ErrorCollection.showSystemError(response.errors[0].message);
	      });
	    });
	  }
	}

	let _$4 = t => t,
	  _t$4,
	  _t2;
	var _landing$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("landing");
	var _copyBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copyBtn");
	var _landingCardElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("landingCardElement");
	class LandingCard {
	  constructor(landingOptions) {
	    Object.defineProperty(this, _landing$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copyBtn, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _landingCardElement, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _landing$1)[_landing$1] = landingOptions;
	  }
	  qrRender() {
	    let qrContainer = main_core.Tag.render(_t$4 || (_t$4 = _$4`<div class="intranet-settings__qr_image-container"></div>`));
	    new QRCode(qrContainer, {
	      text: babelHelpers.classPrivateFieldLooseBase(this, _landing$1)[_landing$1].public_url,
	      width: 106,
	      height: 106
	    });
	    return qrContainer;
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _landingCardElement)[_landingCardElement]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _landingCardElement)[_landingCardElement];
	    }
	    const onclickOpenEdit = () => {
	      window.open(babelHelpers.classPrivateFieldLooseBase(this, _landing$1)[_landing$1].edit_url, '_blank').focus();
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _landingCardElement)[_landingCardElement] = main_core.Tag.render(_t2 || (_t2 = _$4`
		<div class="intranet-settings__req-info-container">
			<div class="intranet-settings__req-info-inner">
				<div class="intranet-settings__qr_container">${0}</div>
				<div class="intranet-settings__qr_description-block">
					<div class="intranet-settings__qr_help-text">
						<h4 class="intranet-settings__qr_title">${0}</h4>
						<p class="intranet-settings__qr_text">
							${0}
						</p>
					</div>
					<div class="intranet-settings__qr_button">
						${0}
					</div>
				</div>
			</div>
			<div class="intranet-settings__qr_editor_box" onclick="${0}">
				<div class="intranet-settings__qr_editor_icon">
					<div class="ui-icon-set --paint-1"></div>
				</div>
				<div class="intranet-settings__qr_editor_name">${0}</div>
				<div class="ui-icon-set --expand intranet-settings__qr_editor_btn"></div>
			</div>
		</div>`), this.qrRender(), main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_SITE'), main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_HELP_TEXT', {
	      '#SITE_URL#': babelHelpers.classPrivateFieldLooseBase(this, _landing$1)[_landing$1].public_url
	    }), this.getCopyButton().getContainer(), onclickOpenEdit, main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_EDIT_LANDING'));
	    return babelHelpers.classPrivateFieldLooseBase(this, _landingCardElement)[_landingCardElement];
	  }
	  getCopyButton() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _copyBtn)[_copyBtn]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _copyBtn)[_copyBtn];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _copyBtn)[_copyBtn] = new ui_buttons.Button({
	      text: main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_COPY_LINK'),
	      round: true,
	      noCaps: true,
	      className: 'landing-copy-button',
	      size: BX.UI.Button.Size.EXTRA_SMALL,
	      color: BX.UI.Button.Color.SUCCESS,
	      events: {
	        click: () => {
	          if (BX.clipboard.copy(babelHelpers.classPrivateFieldLooseBase(this, _landing$1)[_landing$1].public_url)) {
	            top.BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_LINK_WAS_COPIED'),
	              autoHide: true
	            });
	          }
	        }
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _copyBtn)[_copyBtn];
	  }
	}

	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _menuRenderer$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuRenderer");
	var _defaultMenuRenderer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultMenuRenderer");
	var _getRequestCreateLanding = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRequestCreateLanding");
	class LandingButtonFactory {
	  constructor(options, _landingData) {
	    Object.defineProperty(this, _getRequestCreateLanding, {
	      value: _getRequestCreateLanding2
	    });
	    Object.defineProperty(this, _defaultMenuRenderer, {
	      value: _defaultMenuRenderer2
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuRenderer$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    this.landingData = _landingData;
	    babelHelpers.classPrivateFieldLooseBase(this, _menuRenderer$1)[_menuRenderer$1] = babelHelpers.classPrivateFieldLooseBase(this, _defaultMenuRenderer)[_defaultMenuRenderer];
	  }
	  setMenuRenderer(renderer) {
	    babelHelpers.classPrivateFieldLooseBase(this, _menuRenderer$1)[_menuRenderer$1] = renderer;
	  }
	  create() {
	    const btn = new LandingButton();
	    let state;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].is_connected && !babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].is_public) {
	      state = new EditState(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options]);
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].is_connected && babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].is_public) {
	      state = new ShowState(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options], babelHelpers.classPrivateFieldLooseBase(this, _menuRenderer$1)[_menuRenderer$1].bind(this));
	    } else {
	      state = new CreateState(() => {
	        return babelHelpers.classPrivateFieldLooseBase(this, _getRequestCreateLanding)[_getRequestCreateLanding]();
	      }, babelHelpers.classPrivateFieldLooseBase(this, _menuRenderer$1)[_menuRenderer$1].bind(this));
	    }
	    btn.setState(state);
	    return btn.getButton();
	  }
	}
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

	let _$5 = t => t,
	  _t$5,
	  _t2$1,
	  _t3;
	var _options$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _cardElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cardElement");
	var _requisiteFieldsElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisiteFieldsElement");
	var _buttonBar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonBar");
	var _buildCompanyField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildCompanyField");
	var _buildField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildField");
	class Card {
	  constructor(options) {
	    Object.defineProperty(this, _buildField, {
	      value: _buildField2
	    });
	    Object.defineProperty(this, _buildCompanyField, {
	      value: _buildCompanyField2
	    });
	    Object.defineProperty(this, _options$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cardElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _requisiteFieldsElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _buttonBar, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1] = options;
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _cardElement)[_cardElement]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _cardElement)[_cardElement];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _cardElement)[_cardElement] = main_core.Tag.render(_t$5 || (_t$5 = _$5`
		<div class="intranet-settings__req_background">
			<div class="intranet-settings__req-card_wrapper">
				<div class="intranet-settings__header">
					<div class="intranet-settings__title"> <span class="ui-section__title-icon ui-icon-set --city"></span> <span>${0}</span></div>
					<div class="intranet-settings__contact_bar"> 
						<span class="intranet-settings__contact_bar_item">
							${0}
						</span> 
						<span class="intranet-settings__contact_bar_item">
							${0}
						</span> 
						<span class="intranet-settings__contact_bar_item">
							${0}
						</span> 
					</div>
				</div>
				${0}
				${0}
			</div>
		</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].company.TITLE, babelHelpers.classPrivateFieldLooseBase(this, _buildCompanyField)[_buildCompanyField](main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].phone) ? babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].phone : main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_PHONE')), babelHelpers.classPrivateFieldLooseBase(this, _buildCompanyField)[_buildCompanyField](main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].email) ? babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].email : main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_EMAIL')), babelHelpers.classPrivateFieldLooseBase(this, _buildCompanyField)[_buildCompanyField](main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].site) ? babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].site : main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_SITE')), this.requisiteFieldsRender(), this.getButtonsBar().render());
	    return babelHelpers.classPrivateFieldLooseBase(this, _cardElement)[_cardElement];
	  }
	  getRequisiteUrl() {
	    const requisiteId = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].landingData.requisite_id;
	    if (requisiteId) {
	      return '/crm/company/requisite/' + requisiteId + '/';
	    } else {
	      return '/crm/company/requisite/0/?itemId=' + babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].company.ID;
	    }
	  }
	  getCompanyUrl() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].company.ID === 0) {
	      return '/crm/company/details/0/?mycompany=y&TITLE=' + babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].company.TITLE;
	    } else {
	      return '/crm/company/details/' + babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].company.ID + '/';
	    }
	  }
	  requisiteFieldsRender() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _requisiteFieldsElement)[_requisiteFieldsElement]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _requisiteFieldsElement)[_requisiteFieldsElement];
	    }
	    const fields = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].fields;
	    babelHelpers.classPrivateFieldLooseBase(this, _requisiteFieldsElement)[_requisiteFieldsElement] = main_core.Tag.render(_t2$1 || (_t2$1 = _$5`<div class="intranet-settings__req-table_wrap"></div>`));
	    for (let field of fields) {
	      const renderField = main_core.Tag.render(_t3 || (_t3 = _$5`
				<div class="intranet-settings__req-table_row">
					<div class="intranet-settings__table-cell">${0}</div>
					<div class="intranet-settings__table-cell">
						${0}
					</div>
				</div>
			`), field.TITLE, !babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].company.ID ? babelHelpers.classPrivateFieldLooseBase(this, _buildCompanyField)[_buildCompanyField](main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB')) : main_core.Type.isStringFilled(field.VALUE) ? babelHelpers.classPrivateFieldLooseBase(this, _buildField)[_buildField](field.VALUE) : babelHelpers.classPrivateFieldLooseBase(this, _buildField)[_buildField](main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB')));
	      main_core.Dom.append(renderField, babelHelpers.classPrivateFieldLooseBase(this, _requisiteFieldsElement)[_requisiteFieldsElement]);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _requisiteFieldsElement)[_requisiteFieldsElement];
	  }
	  getButtonsBar() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _buttonBar)[_buttonBar]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _buttonBar)[_buttonBar];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonBar)[_buttonBar] = new ButtonBar();
	    return babelHelpers.classPrivateFieldLooseBase(this, _buttonBar)[_buttonBar];
	  }
	  setButtonBar(buttonBar) {
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonBar)[_buttonBar] = buttonBar;
	  }
	}
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

	let _$6 = t => t,
	  _t$6,
	  _t2$2;
	class RequisitePage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_REQUISITE');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_REQUISITE');
	    top.BX.addCustomEvent('onLocalStorageSet', params => {
	      var _params$key;
	      let eventName = (_params$key = params == null ? void 0 : params.key) != null ? _params$key : null;
	      if (eventName === 'onCrmEntityUpdate' || eventName === 'onCrmEntityCreate' || eventName === 'BX.Crm.RequisiteSliderDetails:onSave') {
	        this.reload();
	      }
	    });
	  }
	  getType() {
	    return 'requisite';
	  }
	  appendSections(contentNode) {
	    if (!this.hasValue('sectionRequisite')) {
	      return;
	    }
	    let reqSection = new ui_section.Section(this.getValue('sectionRequisite'));
	    const sectionField = new ui_formElements_field.SettingsSection({
	      parent: this,
	      section: reqSection
	    });
	    const description = new BX.UI.Alert({
	      text: `
				${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_REQUISITE_DESCRIPTION')}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=18213326')">
					${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
			`,
	      inline: true,
	      size: BX.UI.Alert.Size.SMALL,
	      color: BX.UI.Alert.Color.PRIMARY,
	      animated: true
	    });
	    const descriptionRow = new ui_section.Row({
	      content: description.getContainer()
	    });
	    reqSection.append(descriptionRow.render());
	    if (this.hasValue('COMPANY')) {
	      let companies = this.getValue('COMPANY');
	      const requisites = this.getValue('REQUISITES');
	      const phones = this.getValue('PHONES');
	      const sites = this.getValue('SITES');
	      const emails = this.getValue('EMAILS');
	      const landings = this.getValue('LANDINGS');
	      const landingsData = this.getValue('LANDINGS_DATA');
	      if (!main_core.Type.isArray(companies) || companies.length <= 0) {
	        const defaultCompanyRow = new ui_section.Row({
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
	      for (let company of companies) {
	        const fields = !main_core.Type.isNil(requisites[company.ID]) ? requisites[company.ID] : this.getValue('EMPTY_REQUISITE');
	        const cardRow = new ui_section.Row({
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
	    }
	    new ui_formElements_field.SettingsRow({
	      row: new ui_section.Row({
	        content: this.addCompanyLinkRender()
	      }),
	      parent: sectionField
	    });
	    sectionField.renderTo(contentNode);
	  }
	  addCompanyLinkRender() {
	    const link = main_core.Tag.render(_t$6 || (_t$6 = _$6`
				<a class="ui-section__link" 
					href="/crm/company/details/0/?mycompany=y" target="_blank">
				${0}
				</a>
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_ADD_COMPANY'));
	    main_core.Event.bind(link, 'click', event => {
	      var _this$getAnalytic;
	      (_this$getAnalytic = this.getAnalytic()) == null ? void 0 : _this$getAnalytic.addEventConfigRequisite(AnalyticSettingsEvent.OPEN_ADD_COMPANY);
	    });
	    return main_core.Tag.render(_t2$2 || (_t2$2 = _$6`<div class="ui-section__link_box">${0}</div>`), link);
	  }
	  cardRender(params) {
	    const card = new Card(params);
	    const buttonBar = new ButtonBar();
	    if (params.company.ID > 0) {
	      const factory = new LandingButtonFactory(params.landing, params.landingData);
	      factory.setMenuRenderer(landingData => {
	        const landingCard = new LandingCard(landingData);
	        main_core.Event.bind(landingCard.getCopyButton().getContainer(), 'click', event => {
	          var _this$getAnalytic2;
	          (_this$getAnalytic2 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic2.addEventConfigRequisite(AnalyticSettingsEvent.COPY_LINK_CARD);
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
	      const landingBtn = factory.create();
	      if (main_core.Dom.hasClass(landingBtn.getContainer(), 'landing-button-trigger')) {
	        main_core.Event.bind(landingBtn.getContainer(), 'click', event => {
	          if (params.landing.is_connected && !params.landing.is_public) {
	            var _this$getAnalytic3;
	            (_this$getAnalytic3 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic3.addEventConfigRequisite(AnalyticSettingsEvent.EDIT_CARD);
	          } else if (!params.landing.is_connected && !params.landing.is_public) {
	            var _this$getAnalytic4;
	            (_this$getAnalytic4 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic4.addEventConfigRequisite(AnalyticSettingsEvent.CREATE_CARD);
	          }
	        });
	      }
	      buttonBar.addButton(landingBtn);
	    }
	    card.setButtonBar(buttonBar);
	    return card.render();
	  }
	}

	let _$7 = t => t,
	  _t$7,
	  _t2$3,
	  _t3$1,
	  _t4;
	var _buildNewsFeedSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildNewsFeedSection");
	var _buildChatSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildChatSection");
	var _buildChannelSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildChannelSection");
	var _buildDiskSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildDiskSection");
	class CommunicationPage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _buildDiskSection, {
	      value: _buildDiskSection2
	    });
	    Object.defineProperty(this, _buildChannelSection, {
	      value: _buildChannelSection2
	    });
	    Object.defineProperty(this, _buildChatSection, {
	      value: _buildChatSection2
	    });
	    Object.defineProperty(this, _buildNewsFeedSection, {
	      value: _buildNewsFeedSection2
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_COMMUNICATION');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_COMMUNICATION');
	  }
	  getType() {
	    return 'communication';
	  }
	  appendSections(contentNode) {
	    let profileSection = babelHelpers.classPrivateFieldLooseBase(this, _buildNewsFeedSection)[_buildNewsFeedSection]();
	    profileSection.renderTo(contentNode);
	    let chatSection = babelHelpers.classPrivateFieldLooseBase(this, _buildChatSection)[_buildChatSection]();
	    chatSection.renderTo(contentNode);
	    if (this.hasValue('availableGeneralChannel')) {
	      let channelSection = babelHelpers.classPrivateFieldLooseBase(this, _buildChannelSection)[_buildChannelSection]();
	      channelSection.renderTo(contentNode);
	    }
	    let diskSection = babelHelpers.classPrivateFieldLooseBase(this, _buildDiskSection)[_buildDiskSection]();
	    diskSection.renderTo(contentNode);
	  }
	}
	function _buildNewsFeedSection2() {
	  if (!this.hasValue('sectionFeed')) {
	    return;
	  }
	  let newsFeedSection = new ui_section.Section(this.getValue('sectionFeed'));
	  let settingsSection = new ui_formElements_field.SettingsSection({
	    section: newsFeedSection,
	    parent: this
	  });
	  if (this.hasValue('allow_livefeed_toall')) {
	    const allowPostFeedChecker = new ui_formElements_view.Checker(this.getValue('allow_livefeed_toall'));
	    const allowPostFeedSelector = ui_formElements_view.FieldFactory.createUserSelector({
	      inputName: 'livefeed_toall_rights[]',
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS'),
	      values: Object.values(this.getValue('arToAllRights')),
	      enableDepartments: true
	    });
	    CommunicationPage.addToSectionCheckerHelper(allowPostFeedChecker, [allowPostFeedSelector], settingsSection);
	  }
	  if (this.hasValue('default_livefeed_toall')) {
	    let allowPostToAllField = new ui_formElements_view.Checker(this.getValue('default_livefeed_toall'));
	    CommunicationPage.addToSectionHelper(allowPostToAllField, settingsSection);
	  }
	  if (this.hasValue('ratingTextLikeY')) {
	    var _this$getValue, _this$getValue$label, _this$getValue2;
	    const likeBtnNameField = new ui_formElements_view.TextInputInline({
	      inputName: (_this$getValue = this.getValue('ratingTextLikeY')) == null ? void 0 : _this$getValue.name,
	      label: (_this$getValue$label = this.getValue('ratingTextLikeY').label) != null ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_LIKE_INPUT'),
	      hintTitle: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_LIKE'),
	      value: (_this$getValue2 = this.getValue('ratingTextLikeY')) == null ? void 0 : _this$getValue2.current,
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
	  let chatSection = new ui_section.Section(this.getValue('sectionChats'));
	  let settingsSection = new ui_formElements_field.SettingsSection({
	    section: chatSection,
	    parent: this
	  });
	  if (this.hasValue('general_chat_can_post')) {
	    var _this$getValue3;
	    let canPostGeneralChatField = new ui_formElements_view.Checker(this.getValue('allow_post_general_chat'));
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: canPostGeneralChatField
	    });
	    let settingsRow = new ui_formElements_field.SettingsRow({
	      parent: settingsSection,
	      child: settingsField
	    });
	    let canPostGeneralChatListField = new ui_formElements_view.Selector(this.getValue('general_chat_can_post'));
	    settingsField = new ui_formElements_field.SettingsField({
	      fieldView: canPostGeneralChatListField
	    });
	    let canPostGeneralChatListRow = new ui_section.Row({
	      isHidden: !canPostGeneralChatField.isChecked(),
	      className: 'ui-section__subrow --no-border'
	    });
	    CommunicationPage.addToSectionHelper(canPostGeneralChatListField, settingsRow, canPostGeneralChatListRow);
	    let managerSelectorField = ui_formElements_view.FieldFactory.createUserSelector({
	      inputName: 'imchat_toall_rights[]',
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS'),
	      enableAll: false,
	      values: Object.values((_this$getValue3 = this.getValue('generalChatManagersList')) != null ? _this$getValue3 : [])
	    });
	    let managerSelectorRow = new ui_section.Row({
	      content: managerSelectorField.render(),
	      isHidden: this.getValue('general_chat_can_post').current !== 'MANAGER',
	      className: 'ui-section__subrow --no-border'
	    });
	    CommunicationPage.addToSectionHelper(managerSelectorField, settingsRow, managerSelectorRow);
	    const separatorRow = new ui_section.SeparatorRow({
	      isHidden: this.getValue('general_chat_can_post').current !== 'MANAGER'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: separatorRow,
	      parent: settingsRow
	    });
	    main_core_events.EventEmitter.subscribe(canPostGeneralChatField.switcher, 'toggled', () => {
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
	    canPostGeneralChatListField.getInputNode().addEventListener('change', event => {
	      if (event.target.value === 'MANAGER') {
	        managerSelectorRow.show();
	      } else {
	        managerSelectorRow.hide();
	      }
	    });
	  }
	  if (this.hasValue('general_chat_message_leave')) {
	    let leaveMessageField = new ui_formElements_view.Checker(this.getValue('general_chat_message_leave'));
	    CommunicationPage.addToSectionHelper(leaveMessageField, settingsSection);
	  }
	  if (this.hasValue('general_chat_message_admin_rights')) {
	    let adminMessageField = new ui_formElements_view.Checker(this.getValue('general_chat_message_admin_rights'));
	    CommunicationPage.addToSectionHelper(adminMessageField, settingsSection);
	  }
	  if (this.hasValue('url_preview_enable')) {
	    let allowUrlPreviewField = new ui_formElements_view.Checker(this.getValue('url_preview_enable'));
	    CommunicationPage.addToSectionHelper(allowUrlPreviewField, settingsSection);
	  }
	  if (this.hasValue('create_overdue_chats')) {
	    let overdueChatsField = new ui_formElements_view.Checker(this.getValue('create_overdue_chats'));
	    CommunicationPage.addToSectionHelper(overdueChatsField, settingsSection);
	  }
	  return settingsSection;
	}
	function _buildChannelSection2() {
	  if (!this.hasValue('sectionChannels')) {
	    return;
	  }
	  let chatSection = new ui_section.Section(this.getValue('sectionChannels'));
	  let settingsSection = new ui_formElements_field.SettingsSection({
	    section: chatSection,
	    parent: this
	  });
	  if (this.hasValue('general_channel_can_post')) {
	    var _Loc$getMessage, _this$getValue4;
	    let canPostGeneralChannelField = new ui_formElements_view.Checker(this.getValue('allow_post_general_channel'));
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: canPostGeneralChannelField
	    });
	    let settingsRow = new ui_formElements_field.SettingsRow({
	      parent: settingsSection,
	      child: settingsField
	    });
	    let canPostGeneralChannelListField = new ui_formElements_view.Selector(this.getValue('general_channel_can_post'));
	    settingsField = new ui_formElements_field.SettingsField({
	      fieldView: canPostGeneralChannelListField
	    });
	    let canPostGeneralChannelListRow = new ui_section.Row({
	      isHidden: !canPostGeneralChannelField.isChecked(),
	      className: 'ui-section__subrow --no-border'
	    });
	    CommunicationPage.addToSectionHelper(canPostGeneralChannelListField, settingsRow, canPostGeneralChannelListRow);
	    let managerSelectorField = ui_formElements_view.FieldFactory.createUserSelector({
	      inputName: 'imchannel_toall_rights[]',
	      label: (_Loc$getMessage = main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_PUBLIC_MESS_CHANNEL')) != null ? _Loc$getMessage : '',
	      enableAll: false,
	      values: Object.values((_this$getValue4 = this.getValue('generalChannelManagersList')) != null ? _this$getValue4 : [])
	    });
	    let managerSelectorRow = new ui_section.Row({
	      content: managerSelectorField.render(),
	      isHidden: this.getValue('general_channel_can_post').current !== 'MANAGER',
	      className: 'ui-section__subrow --no-border'
	    });
	    CommunicationPage.addToSectionHelper(managerSelectorField, settingsRow, managerSelectorRow);
	    const separatorRow = new ui_section.SeparatorRow({
	      isHidden: this.getValue('general_channel_can_post').current !== 'MANAGER'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: separatorRow,
	      parent: settingsRow
	    });
	    main_core_events.EventEmitter.subscribe(canPostGeneralChannelField.switcher, 'toggled', () => {
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
	    canPostGeneralChannelListField.getInputNode().addEventListener('change', event => {
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
	  if (!this.hasValue('sectionDisk')) {
	    return;
	  }
	  let diskSection = new ui_section.Section(this.getValue('sectionDisk'));
	  let settingsSection = new ui_formElements_field.SettingsSection({
	    section: diskSection,
	    parent: this
	  });
	  if (this.hasValue('DISK_VIEWER_SERVICE')) {
	    let fileViewerField = new ui_formElements_view.Selector(this.getValue('DISK_VIEWER_SERVICE'));
	    CommunicationPage.addToSectionHelper(fileViewerField, settingsSection);
	  }
	  if (this.hasValue('DISK_UNIFIED_LINK_DEFAULT_ACCESS_LEVEL')) {
	    let unifiedLinkDefaultAccessLevelSelector = new ui_formElements_view.Selector(this.getValue('DISK_UNIFIED_LINK_DEFAULT_ACCESS_LEVEL'));
	    CommunicationPage.addToSectionHelper(unifiedLinkDefaultAccessLevelSelector, settingsSection);
	  }
	  if (this.hasValue('DISK_LIMIT_PER_FILE')) {
	    var _this$getValue$label2;
	    const messageNode = main_core.Tag.render(_t$7 || (_t$7 = _$7`<span>${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE'));
	    let fileLimitField = new ui_formElements_view.Selector({
	      label: (_this$getValue$label2 = this.getValue('DISK_LIMIT_PER_FILE').label) != null ? _this$getValue$label2 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAX_FILE_LIMIT'),
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
	    let fileLimitRow = new ui_section.Row({
	      separator: 'bottom',
	      className: '--block'
	    });
	    if (!this.getValue('DISK_LIMIT_PER_FILE').isEnable) {
	      main_core.Event.bind(fileLimitField.getInputNode(), 'click', () => {
	        var _this$getAnalytic;
	        (_this$getAnalytic = this.getAnalytic()) == null ? void 0 : _this$getAnalytic.addEventOpenHint(this.getValue('DISK_LIMIT_PER_FILE').name);
	      });
	      main_core.Event.bind(messageNode.querySelector('a'), 'click', () => {
	        var _this$getAnalytic2;
	        return (_this$getAnalytic2 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic2.addEventOpenTariffSelector(this.getValue('DISK_LIMIT_PER_FILE').name);
	      });
	    }
	    CommunicationPage.addToSectionHelper(fileLimitField, settingsSection, fileLimitRow);
	  }
	  new ui_formElements_field.SettingsRow({
	    row: new ui_section.SeparatorRow(),
	    parent: settingsSection
	  });
	  if (this.hasValue('disk_allow_edit_object_in_uf')) {
	    let allowEditDocField = new ui_formElements_view.Checker(this.getValue('disk_allow_edit_object_in_uf'));
	    let allowEditDocRow = new ui_section.Row({
	      separator: 'top',
	      className: '--block'
	    });
	    CommunicationPage.addToSectionHelper(allowEditDocField, settingsSection, allowEditDocRow);
	  }
	  if (this.hasValue('disk_allow_autoconnect_shared_objects')) {
	    let connectDiskField = new ui_formElements_view.Checker(this.getValue('disk_allow_autoconnect_shared_objects'));
	    CommunicationPage.addToSectionHelper(connectDiskField, settingsSection);
	  }
	  if (this.hasValue('disk_allow_use_external_link')) {
	    var _this$getValue$label3;
	    const messageNode = main_core.Tag.render(_t2$3 || (_t2$3 = _$7`<span>${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE'));
	    let publicLinkField = new ui_formElements_view.Checker({
	      inputName: this.getValue('disk_allow_use_external_link').inputName,
	      title: (_this$getValue$label3 = this.getValue('disk_allow_use_external_link').label) != null ? _this$getValue$label3 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_PUBLIC_LINK'),
	      hintOn: this.getValue('disk_allow_use_external_link').hintOn,
	      checked: this.getValue('disk_allow_use_external_link').checked,
	      isEnable: this.getValue('disk_allow_use_external_link').isEnable,
	      bannerCode: 'limit_admin_share_link',
	      helpDesk: this.getValue('disk_allow_use_external_link').helpDesk,
	      helpMessageProvider: this.helpMessageProviderFactory(messageNode)
	    });
	    if (!this.getValue('disk_allow_use_external_link').isEnable) {
	      main_core_events.EventEmitter.subscribe(publicLinkField.switcher, 'toggled', () => {
	        var _this$getAnalytic3;
	        (_this$getAnalytic3 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic3.addEventOpenHint('disk_allow_use_external_link');
	      });
	      main_core.Event.bind(messageNode.querySelector('a'), 'click', () => {
	        var _this$getAnalytic4;
	        return (_this$getAnalytic4 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic4.addEventOpenTariffSelector('enable_pub_link');
	      });
	    }
	    CommunicationPage.addToSectionHelper(publicLinkField, settingsSection);
	  }
	  if (this.hasValue('disk_object_lock_enabled')) {
	    var _this$getValue$label4;
	    const messageNode = main_core.Tag.render(_t3$1 || (_t3$1 = _$7`<span>${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE'));
	    let enableBlockDocField = new ui_formElements_view.Checker({
	      inputName: this.getValue('disk_object_lock_enabled').inputName,
	      title: (_this$getValue$label4 = this.getValue('disk_object_lock_enabled').label) != null ? _this$getValue$label4 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_BLOCK_DOC'),
	      hintOn: this.getValue('disk_object_lock_enabled').hintOn,
	      checked: this.getValue('disk_object_lock_enabled').checked,
	      isEnable: this.getValue('disk_object_lock_enabled').isEnable,
	      bannerCode: 'limit_document_lock',
	      helpMessageProvider: this.helpMessageProviderFactory(messageNode),
	      helpDesk: this.getValue('disk_object_lock_enabled').helpDesk
	    });
	    if (!this.getValue('disk_object_lock_enabled').isEnable) {
	      main_core_events.EventEmitter.subscribe(enableBlockDocField.switcher, 'toggled', () => {
	        var _this$getAnalytic5;
	        (_this$getAnalytic5 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic5.addEventOpenHint('disk_object_lock_enabled');
	      });
	      main_core.Event.bind(messageNode.querySelector('a'), 'click', () => {
	        var _this$getAnalytic6;
	        return (_this$getAnalytic6 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic6.addEventOpenTariffSelector('disk_object_lock_enabled');
	      });
	    }
	    CommunicationPage.addToSectionHelper(enableBlockDocField, settingsSection);
	  }
	  if (this.hasValue('disk_allow_use_extended_fulltext')) {
	    var _this$getValue$label5;
	    const messageNode = main_core.Tag.render(_t4 || (_t4 = _$7`<span>${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_ENT', {
	      '#TARIFF#': 'ent250'
	    }));
	    let enableFindField = new ui_formElements_view.Checker({
	      inputName: this.getValue('disk_allow_use_extended_fulltext').inputName,
	      title: (_this$getValue$label5 = this.getValue('disk_allow_use_extended_fulltext').label) != null ? _this$getValue$label5 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_SEARCH_DOC'),
	      hintOn: this.getValue('disk_allow_use_extended_fulltext').hintOn,
	      checked: this.getValue('disk_allow_use_extended_fulltext').checked,
	      isEnable: this.getValue('disk_allow_use_extended_fulltext').isEnable,
	      bannerCode: 'limit_in_text_search',
	      helpDesk: this.getValue('disk_allow_use_extended_fulltext').helpDesk,
	      helpMessageProvider: this.helpMessageProviderFactory(messageNode)
	    });
	    if (!this.getValue('disk_allow_use_extended_fulltext').isEnable) {
	      main_core_events.EventEmitter.subscribe(enableFindField.switcher, 'toggled', () => {
	        var _this$getAnalytic7;
	        (_this$getAnalytic7 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic7.addEventOpenHint('disk_allow_use_extended_fulltext');
	      });
	      main_core.Event.bind(messageNode.querySelector('a'), 'click', () => {
	        var _this$getAnalytic8;
	        return (_this$getAnalytic8 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic8.addEventOpenTariffSelector('disk_allow_use_extended_fulltext');
	      });
	    }
	    CommunicationPage.addToSectionHelper(enableFindField, settingsSection);
	  }
	  return settingsSection;
	}

	let _$8 = t => t,
	  _t$8,
	  _t2$4,
	  _t3$2;
	var _content$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _title$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("title");
	class SiteDomainField extends ui_formElements_field.SettingsField {
	  constructor(params) {
	    const options = params.siteDomainOptions;
	    params.fieldView = new ui_formElements_view.TextInput({
	      value: options.subDomainName,
	      placeholder: options.subDomainName,
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME3'),
	      id: 'subDomainName',
	      inputName: 'subDomainName',
	      isEnable: true
	    });
	    super(params);
	    Object.defineProperty(this, _content$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _title$1, {
	      writable: true,
	      value: void 0
	    });
	    this.setParentElement(params.parent);
	    this.getFieldView().setEventNamespace(this.getEventNamespace());
	    this.getFieldView().getInputNode().setAttribute('autocomplete', 'off');
	    let timeout = null;
	    main_core.Event.bind(this.getFieldView().getInputNode(), 'input', () => {
	      clearTimeout(timeout);
	      timeout = setTimeout(() => {
	        this.validateInput();
	      }, 1000);
	    });
	    this.options = {
	      hostname: options.hostname,
	      subDomainName: options.subDomainName,
	      mainDomainName: options.mainDomainName,
	      isRenameable: options.isRenameable,
	      occupiedDomains: options.occupiedDomains
	    };
	    this.options.mainDomainName = ['.', this.options.mainDomainName].join('').replace('..', '.');
	  }
	  validateInput() {
	    let newDomain = this.getFieldView().getInputNode().value;
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
	  cancel() {}
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2];
	    }
	    if (this.options.isRenameable !== true) {
	      const copyButton = main_core.Tag.render(_t$8 || (_t$8 = _$8`<div class="settings-tools-description-link">${0}</div>`), main_core.Loc.getMessage('INTRANET_SETTINGS_COPY'));
	      BX.clipboard.bindCopyClick(copyButton, {
	        text: () => {
	          return this.options.hostname;
	        }
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2] = main_core.Tag.render(_t2$4 || (_t2$4 = _$8`
				<div>
					<div class="ui-section__field-label_box">
						<div class="ui-section__field-label">${0}</div>
					</div>
					<div class="intranet-settings__domain_box">
						<div class="intranet-settings__domain_name">${0}</div>
						${0}
					</div>
				</div>`), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME4'), main_core.Text.encode(this.options.hostname), copyButton);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2] = main_core.Tag.render(_t3$2 || (_t3$2 = _$8`<div id="${0}" class="ui-section__field-selector --no-border">
				<div class="ui-section__field-container">
					<div class="ui-section__field-label_box">
						<label class="ui-section__field-label" for="${0}">
							${0}
						</label> 
					</div>
					<div class="ui-section__field-inner">
						<div class="intarnet-settings__domain_inline-field">
							<div class="ui-ctl ui-ctl-textbox ui-ctl-block">
								${0}
							</div>
							<div class="intarnet-settings__domain_name">${0}</div>
						</div>
						${0}
					</div>
				</div>
			</div>`), this.getFieldView().getId(), this.getFieldView().getName(), this.getFieldView().getLabel(), this.getFieldView().getInputNode(), main_core.Text.encode(this.options.mainDomainName), this.getFieldView().renderErrors());
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2];
	  }
	}

	function setPortalSettings(container, portalSettings) {
	  const logoNode = container.querySelector('[data-role="logo"]');
	  const titleNode = container.querySelector('[data-role="title"]');
	  const logo24Node = container.querySelector('[data-role="logo24"]');
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
	  const theme = main_core.Type.isPlainObject(themeSettings) ? themeSettings : {};
	  const lightning = String(theme.id).indexOf('dark:') === 0 ? 'dark' : 'light';
	  main_core.Dom.removeClass(container, '--light --dark');
	  main_core.Dom.addClass(container, '--' + lightning);
	  if (main_core.Type.isStringFilled(theme.previewImage)) {
	    container.style.backgroundImage = 'url("' + encodeURI(theme.previewImage) + '")';
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

	let _$9 = t => t,
	  _t$9,
	  _t2$5,
	  _t3$3;
	var _themePicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("themePicker");
	var _themePickerDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("themePickerDialog");
	var _initThemePicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initThemePicker");
	class ThemePickerElement extends ui_formElements_view.BaseField {
	  constructor(_themePickerSettings) {
	    super({
	      inputName: 'themeId',
	      isEnable: _themePickerSettings.allowSetDefaultTheme,
	      bannerCode: 'limit_office_background_to_all'
	    });
	    Object.defineProperty(this, _initThemePicker, {
	      value: _initThemePicker2
	    });
	    Object.defineProperty(this, _themePicker, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _themePickerDialog, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _initThemePicker)[_initThemePicker](_themePickerSettings);
	    this.applyTheme();
	  }
	  applyTheme(event) {
	    const themeNode = event ? babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].getItemNode(event) : null;
	    let themeSettings = themeNode ? babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].getTheme(themeNode.dataset.themeId) : babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].getAppliedTheme();
	    this.applyPortalThemePreview(themeSettings);
	    if (event) {
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:ThemePicker:Change', themeSettings);
	      this.showSaveButton();
	    }
	  }
	  applyPortalThemePreview(theme) {
	    const container = this.render().querySelector('[data-role="preview"]');
	    setPortalThemeSettings(container, theme);
	    this.getInputNode().value = main_core.Type.isPlainObject(theme) ? theme['id'] : '';
	  }
	  showSaveButton() {
	    this.getInputNode().disabled = false;
	    this.getInputNode().form.dispatchEvent(new window.Event('change'));
	  }
	  getValue() {
	    return this.getInputNode().value;
	  }
	  getInputNode() {
	    return this.render().querySelector('input[name="themeId"]');
	  }
	  applyPortalSettings() {}
	  renderContentField() {
	    document.querySelector('.ui-side-panel-content').style.overflow = 'hidden';
	    const container = main_core.Tag.render(_t$9 || (_t$9 = _$9`
		<div class="intranet-theme-settings ui-section__row">
			<div class="ui-section__row theme-dialog-preview">
				
				<section class="intranet-settings__preview --preview" data-role="preview">
					<div class="preview__header">
						<div class="preview__header-box">
							<div class="preview__header-left-box">
								<div class="preview__menu-switcher">
									<span class="preview__menu-switcher__icon"></span>
								</div>
								<div class="preview__block-item"></div>
								<div class="preview__block-item"></div>
								<div class="preview__block-item"></div>
							</div>
							<div class="preview__header-right-box">
								<div class="intranet-settings__logo-box">
									<div class="intranet-settings__main-widget_logo" data-role="logo"></div>
									<div class="intranet-settings__main-widget_name" data-role="title">Bitrix</div>
									<div class="intranet-settings__logo24" data-role="logo24">
										24
									</div>
								</div>	
								<div class="preview__circle_container">	
									<div class="preview__circle_item"></div>
								</div>				
							</div>
						</div>
					</div>
					<div class="preview__main">
						<div class="preview__main-left">
							<div class="preview__circle_container">
								<div class="preview__circle_item-outline">
									<div class="preview__circle_item --active"></div>
								</div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item"></div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item"></div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item"></div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item"></div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item"></div>
							</div>	
						</div>
						<div class="preview__main-center">
							<div class="preview__main-row">
								<div class="preview__main-row-left">
									<div class="preview__block-item --w145"></div>
									<div class="preview__block-item --opacity80 --w47"></div>
									<div class="preview__block-item --w90"></div>
								</div>
								<div class="preview__main-row-right">
									<div class="preview__block-item --w50"></div>
								</div>
							</div>
							<div class="preview__main-row">
								<div class="preview__main-row-left">
									<div class="preview__block-item --w80"></div>
									<div class="preview__block-item --w50"></div>
								</div>
								<div class="preview__main-row-right">
									<div class="preview__block-item --w90"></div>
								</div>
							</div>
							<div class="preview__main-column">
								<div class="preview__main-header"></div>
								<div class="preview__main-table"></div>
							</div>
						</div>
						<div class="preview__main-right">
							<div class="preview__circle_container">	
								<div class="preview__circle_item --light"></div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item --light"></div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item --light"></div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item --light"></div>
							</div>	
						</div>
					</div>
				</section>
				
			</div>
			<div class="ui-section__row theme-dialog-content" data-role="theme-container"></div>
			<input type="hidden" name="themeId" value="" disabled>
		</div>
		`));
	    const uploadBtn = main_core.Tag.render(_t2$5 || (_t2$5 = _$9`
			<div class="intranet-settings__theme-btn_box">
				<div class="intranet-settings__theme-btn" onclick="${0}">${0}</div>
			</div>
		`), this.handleNewThemeButtonClick.bind(this), main_core.Loc.getMessage('INTRANET_SETTINGS_THEME_UPLOAD_BTN'));
	    const themeContainer = container.querySelector('div[data-role="theme-container"]');
	    Array.from(babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].getThemes()).forEach(theme => {
	      const itemNode = babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].createItem(theme);
	      if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].canSetDefaultTheme() !== true) {
	        main_core.Event.unbindAll(itemNode, 'click');
	        if (theme['default'] !== true) {
	          main_core.Dom.addClass(itemNode, '--restricted');
	          itemNode.appendChild(main_core.Tag.render(_t3$3 || (_t3$3 = _$9`<div class="intranet-settings__theme_lock_box">${0}</div>`), this.renderLockElement()));
	          main_core.Event.bind(itemNode, 'click', this.showBanner.bind(this));
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
	  handleNewThemeButtonClick(event) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].canSetDefaultTheme() !== true) {
	      return this.showBanner();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].getNewThemeDialog().show();
	  }
	  handleLockButtonClick() {
	    if (BX.getClass("BX.UI.InfoHelper")) {
	      BX.UI.InfoHelper.show("limit_office_background_to_all");
	    }
	  }
	}
	function _initThemePicker2(themePickerSettings) {
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker] = new top.BX.Intranet.Bitrix24.ThemePicker(themePickerSettings);
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].setThemes(themePickerSettings.themes);
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].setBaseThemes(themePickerSettings.baseThemes);
	  babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog] = new intranet_themePicker_dialog.ThemePickerDialog(babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker]);
	  babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].applyThemeAssets = () => {};
	  babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].getContentContainer = () => {
	    return this.render().querySelector('div[data-role="theme-container"]');
	  };
	  const closure = babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].handleRemoveBtnClick.bind(babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog]);
	  babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].handleRemoveBtnClick = event => {
	    const item = babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].getItemNode(event);
	    if (!item) {
	      return;
	    }
	    closure(event);
	    this.applyPortalThemePreview(babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].getTheme(babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].getThemeId()));
	    this.showSaveButton();
	    //TODO Shift all <td>
	  };

	  const handleItemClick = babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].handleItemClick.bind(babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog]);
	  babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].handleItemClick = event => {
	    handleItemClick(event);
	    this.applyTheme(event);
	  };
	  const addItem = babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].addItem.bind(babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog]);
	  babelHelpers.classPrivateFieldLooseBase(this, _themePickerDialog)[_themePickerDialog].addItem = theme => {
	    addItem(theme);
	    this.applyPortalThemePreview(theme);
	    this.showSaveButton();
	  };
	}
	var _fieldView = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fieldView");
	class SiteThemePickerField extends ui_formElements_field.SettingsField {
	  constructor(params) {
	    params.fieldView = new ThemePickerElement(params.themePickerSettings);
	    super(params);
	    Object.defineProperty(this, _fieldView, {
	      writable: true,
	      value: void 0
	    });
	    if (params.portalSettings) {
	      this.setEventNamespace('BX.Intranet.Settings');
	      setPortalSettings(this.getFieldView().render(), params.portalSettings);
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', baseEvent => {
	        setPortalSettings(this.getFieldView().render(), baseEvent.getData());
	      });
	    }
	  }
	}

	let _$a = t => t,
	  _t$a,
	  _t2$6,
	  _t3$4;
	class HiddenInput extends ui_formElements_view.TextInput {
	  constructor(params) {
	    super({
	      inputName: 'logo',
	      isEnable: params.isEnable,
	      defaultValue: 'default',
	      bannerCode: 'limit_admin_logo',
	      helpDeskCode: 123,
	      helpMessageProvider: () => {}
	    });
	    this.getInputNode().type = 'hidden';
	    this.getInputNode().disabled = true;
	  }
	  renderContentField() {
	    return this.getInputNode();
	  }
	}
	var _content$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _uploader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uploader");
	var _siteLogo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("siteLogo");
	var _hiddenContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hiddenContainer");
	var _hiddenRemoveInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hiddenRemoveInput");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _getFileContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFileContainer");
	var _renderAfterLoad = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderAfterLoad");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _removeLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeLoader");
	class SiteLogoField extends ui_formElements_field.SettingsField {
	  constructor(params) {
	    params.fieldView = new HiddenInput({
	      isEnable: params.canUserEditLogo
	    });
	    super(params);
	    Object.defineProperty(this, _removeLoader, {
	      value: _removeLoader2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _renderAfterLoad, {
	      value: _renderAfterLoad2
	    });
	    Object.defineProperty(this, _getFileContainer, {
	      value: _getFileContainer2
	    });
	    Object.defineProperty(this, _content$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _uploader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _siteLogo, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _hiddenContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _hiddenRemoveInput, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo] = params.siteLogoOptions;
	    this.siteLogoLabel = params.siteLogoLabel;
	    this.setEventNamespace('BX.Intranet.Settings');
	  }
	  initUploader({
	    TileWidget,
	    StackWidget,
	    StackWidgetSize
	  }) {
	    const defaultOptions = {
	      maxFileCount: 1,
	      acceptOnlyImages: true,
	      multiple: false,
	      acceptedFileTypes: ['image/png'],
	      events: {
	        'onError': function (event) {
	          console.error('File Uploader onError', event.getData().error);
	        },
	        'File:onError': this.onFileError.bind(this),
	        'File:onAdd': this.onLogoAdd.bind(this),
	        'File:onRemove': this.onLogoRemove.bind(this),
	        'onBeforeFilesAdd': this.getFieldView().isEnable() ? () => {} : event => {
	          this.getFieldView().showBanner();
	          event.preventDefault();
	        }
	      },
	      allowReplaceSingle: true,
	      hiddenFieldName: 'logo_file',
	      hiddenFieldsContainer: babelHelpers.classPrivateFieldLooseBase(this, _getFileContainer)[_getFileContainer](),
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
	      files: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo] ? [[1, {
	        serverFileId: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].id,
	        serverId: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].id,
	        type: 'image/png',
	        width: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].width,
	        height: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].height,
	        treatImageAsFile: true,
	        downloadUrl: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].src,
	        serverPreviewUrl: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].src,
	        serverPreviewWidth: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].width,
	        serverPreviewHeight: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].height,
	        src: babelHelpers.classPrivateFieldLooseBase(this, _siteLogo)[_siteLogo].src,
	        preload: true
	      }]] : null
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader] = new StackWidget(defaultOptions, {
	      size: StackWidgetSize.LARGE
	    });
	    return this;
	  }
	  onFileError(event) {
	    console.error('File Error', event.getData().error);
	    main_core_events.EventEmitter.subscribeOnce(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':onAfterShowPage', this.removeFailedLogo.bind(this));
	    const tabField = this.getParentElement();
	    if (tabField) {
	      main_core_events.EventEmitter.subscribeOnce(tabField.getFieldView(), 'onActive', this.removeFailedLogo.bind(this));
	    }
	  }
	  removeFailedLogo() {
	    const logo = babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader].getUploader().getFiles()[0];
	    if (logo && logo.isLoadFailed()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader].getUploader().removeFiles();
	    }
	  }
	  onLogoAdd(event) {
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
	  onLogoRemove(event) {
	    main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', new main_core_events.BaseEvent({
	      data: {
	        logo: null
	      }
	    }));
	    this.getFieldView().getInputNode().disabled = false;
	    this.getFieldView().getInputNode().value = 'remove';
	    this.getFieldView().getInputNode().form.dispatchEvent(new window.Event('change'));
	  }
	  getName() {
	    return 'logo';
	  }
	  cancel() {}
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3] = main_core.Tag.render(_t$a || (_t$a = _$a`<div></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	    main_core.Runtime.loadExtension('ui.uploader.stack-widget').then(exports => {
	      this.initUploader(exports);
	      babelHelpers.classPrivateFieldLooseBase(this, _renderAfterLoad)[_renderAfterLoad]();
	      babelHelpers.classPrivateFieldLooseBase(this, _removeLoader)[_removeLoader]();
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3];
	  }
	}
	function _getFileContainer2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _hiddenContainer)[_hiddenContainer]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _hiddenContainer)[_hiddenContainer] = document.createElement('div');
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _hiddenContainer)[_hiddenContainer];
	}
	function _renderAfterLoad2() {
	  var _this$siteLogoLabel;
	  const uploaderContent = main_core.Tag.render(_t2$6 || (_t2$6 = _$a`<div></div>`));
	  const content = main_core.Tag.render(_t3$4 || (_t3$4 = _$a`<div>
				<div class="ui-section__field-label">${0}</div>
				${0}
				<div class="ui-section__field-label">${0}</div>
				${0}
				${0}
				${0}
			</div>`), (_this$siteLogoLabel = this.siteLogoLabel) != null ? _this$siteLogoLabel : main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO_TITLE1'), uploaderContent, main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO_TITLE2'), this.getFieldView().getInputNode(), babelHelpers.classPrivateFieldLooseBase(this, _getFileContainer)[_getFileContainer](), this.getFieldView().renderErrors());
	  babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader].renderTo(uploaderContent);
	  main_core.Dom.replace(babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3], content);
	}
	function _showLoader2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3],
	    color: 'rgba(82, 92, 105, 0.9)',
	    mode: 'inline'
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show().then(() => {
	    console.log('The loader is shown');
	  });
	}
	function _removeLoader2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = null;
	  }
	}

	let _$b = t => t,
	  _t$b;
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	class SiteTitlePreviewWidget extends main_core_events.EventEmitter {
	  constructor(portalSettings, portalThemeSettings) {
	    super();
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Intranet.Settings');
	    setPortalSettings(this.render(), portalSettings);
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', this.onChange.bind(this));
	    if (portalThemeSettings) {
	      setPortalThemeSettings(this.render(), portalThemeSettings == null ? void 0 : portalThemeSettings.theme);
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':ThemePicker:Change', this.onSetTheme.bind(this));
	    }
	  }
	  onChange(event) {
	    setPortalSettings(this.render(), event.getData());
	  }
	  onSetTheme(baseEvent) {
	    setPortalThemeSettings(this.render(), baseEvent.getData());
	  }
	  render() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t$b || (_t$b = _$b`
			<section class="intranet-settings__preview --preview" data-role="preview">
				<div class="preview__header">
					<div class="preview__header-box">
						<div class="preview__header-left-box">
							<div class="preview__menu-switcher">
								<span class="preview__menu-switcher__icon"></span>
							</div>
							<div class="preview__block-item"></div>
							<div class="preview__block-item"></div>
							<div class="preview__block-item"></div>
						</div>
						<div class="preview__header-right-box">
							<div class="intranet-settings__logo-box">
								<div class="intranet-settings__main-widget_logo" data-role="logo"></div>
								<div class="intranet-settings__main-widget_name" data-role="title">Bitrix</div>
								<div class="intranet-settings__logo24" data-role="logo24">
									24
								</div>
							</div>	
							<div class="preview__circle_container">	
								<div class="preview__circle_item"></div>
							</div>				
						</div>
					</div>
				</div>
				<div class="preview__main">
					<div class="preview__main-left">
						<div class="preview__circle_container">
							<div class="preview__circle_item-outline">
								<div class="preview__circle_item --active"></div>
							</div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item"></div>
						</div>	
					</div>
					<div class="preview__main-center">
						<div class="preview__main-row">
							<div class="preview__main-row-left">
								<div class="preview__block-item --w145"></div>
								<div class="preview__block-item --opacity80 --w47"></div>
								<div class="preview__block-item --w90"></div>
							</div>
							<div class="preview__main-row-right">
								<div class="preview__block-item --w50"></div>
							</div>
						</div>
						<div class="preview__main-row">
							<div class="preview__main-row-left">
								<div class="preview__block-item --w80"></div>
								<div class="preview__block-item --w50"></div>
							</div>
							<div class="preview__main-row-right">
								<div class="preview__block-item --w90"></div>
							</div>
						</div>
						<div class="preview__main-column">
							<div class="preview__main-header"></div>
							<div class="preview__main-table"></div>
						</div>
					</div>
					<div class="preview__main-right">
						<div class="preview__circle_container">	
							<div class="preview__circle_item --light"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item --light"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item --light"></div>
						</div>	
						<div class="preview__circle_container">	
							<div class="preview__circle_item --light"></div>
						</div>	
					</div>
				</div>
			</section>
			`));
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	}

	let _$c = t => t,
	  _t$c,
	  _t2$7,
	  _t3$5;
	var _headerWidgetRenderAlternative = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("headerWidgetRenderAlternative");
	var _getOwnDomainTabBody = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOwnDomainTabBody");
	class PortalPage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _getOwnDomainTabBody, {
	      value: _getOwnDomainTabBody2
	    });
	    Object.defineProperty(this, _headerWidgetRenderAlternative, {
	      value: _headerWidgetRenderAlternative2
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_PORTAL');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_PORTAL');
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':Portal:Change', baseEvent => {
	      if (!main_core.Type.isNil(baseEvent.data.title)) {
	        var _this$getAnalytic;
	        (_this$getAnalytic = this.getAnalytic()) == null ? void 0 : _this$getAnalytic.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_NAME);
	      } else if (!main_core.Type.isNil(baseEvent.data.logo)) {
	        var _this$getAnalytic2;
	        (_this$getAnalytic2 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic2.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_LOGO);
	      }
	    });
	    //BX.Intranet.Settings:ThemePicker:Change
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, this.getEventNamespace() + ':ThemePicker:Change', baseEvent => {
	      var _this$getAnalytic3, _baseEvent$data;
	      (_this$getAnalytic3 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic3.addEventChangeTheme((_baseEvent$data = baseEvent.data) == null ? void 0 : _baseEvent$data.id);
	    });
	  }
	  getType() {
	    return 'portal';
	  }
	  headerWidgetRender() {
	    return '';
	    // It is used to return #headerWidgetRenderAlternative;
	  }

	  //TODO delete after autumn 2023

	  getSections() {
	    return [this.buildSiteTitleSection(this.getValue('portalSettings'), this.getValue('portalThemeSettings'), this.getValue('portalSettingsLabels')), this.getValue('portalDomainSettings') ? this.buildDomainSection(this.getValue('portalDomainSettings')) : null, this.buildThemeSection(this.getValue('portalThemeSettings'), this.getValue('portalSettings'))].filter(section => section instanceof ui_formElements_field.SettingsSection);
	  }
	  buildSiteTitleSection(portalSettings, portalThemeSettings, portalSettingsLabels) {
	    if (!this.hasValue('sectionCompanyTitle')) {
	      return;
	    }
	    const sectionView = new ui_section.Section(this.getValue('sectionCompanyTitle'));
	    const sectionField = new ui_formElements_field.SettingsSection({
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

	    // 2.0 Widget
	    const previewWidget = new SiteTitlePreviewWidget(portalSettings, portalThemeSettings);
	    new ui_formElements_field.SettingsRow({
	      row: new ui_section.Row({
	        content: previewWidget.render(),
	        className: 'intranet-settings__site-logo_subrow'
	      }),
	      parent: sectionField
	    });

	    //region 2.1 Tabs
	    const siteLogoRow = new ui_formElements_field.SettingsRow({
	      row: new ui_section.Row({
	        className: 'intranet-settings__grid_box'
	      }),
	      parent: sectionField
	    });
	    const tabsRow = new ui_formElements_field.SettingsRow({
	      row: new ui_section.Row({
	        className: 'intranet-settings__site-logo_subrow --bottom-separator --block'
	      }),
	      parent: sectionField
	    });
	    const tabsField = new ui_formElements_field.TabsField({
	      parent: tabsRow
	    });

	    // 2.2 Tab Site name
	    const siteTitleTab = new ui_formElements_field.TabField({
	      parent: tabsField,
	      tabsOptions: this.getValue('tabCompanyTitle')
	    });
	    const siteTitleRow = new ui_section.Row({});
	    const siteTitleField = new SiteTitleField({
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
	    const siteLogoTab = new ui_formElements_field.TabField({
	      parent: tabsField,
	      tabsOptions: this.getValue('tabCompanyLogo')
	    });
	    const siteLogoField = new SiteLogoField({
	      siteLogoLabel: this.getValue('portalSettingsLabels').logo,
	      siteLogoOptions: this.getValue('portalSettings').logo,
	      canUserEditLogo: this.getValue('portalSettings').canUserEditLogo
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: siteLogoTab,
	      child: siteLogoField
	    });
	    tabsField.activateTab(siteTitleTab);

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
	  buildDomainSection(domainSettings) {
	    if (!this.hasValue('sectionSiteDomain')) {
	      return;
	    }
	    const sectionView = new ui_section.Section(this.getValue('sectionSiteDomain'));
	    const sectionField = new ui_formElements_field.SettingsSection({
	      parent: this,
	      section: sectionView
	    });
	    // 1. This is a description on blue box
	    sectionView.append(new ui_section.Row({
	      content: new ui_alerts.Alert({
	        text: `
						${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_DOMAIN_DESCRIPTION')}
						<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=18213298')">
							${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
						</a>
					`,
	        inline: true,
	        size: ui_alerts.AlertSize.SMALL,
	        color: ui_alerts.AlertColor.PRIMARY
	      }).getContainer()
	    }).render());
	    const tabsRow = new ui_formElements_field.SettingsRow({
	      parent: sectionField
	    });

	    //region 2. Tabs
	    const tabsField = new ui_formElements_field.TabsField({
	      parent: tabsRow
	    });
	    // 2.1 Tab Site name
	    const firstTab = new ui_formElements_field.TabField({
	      parent: tabsField,
	      tabsOptions: this.getValue('tabDomainPrefix')
	    });
	    const siteDomainField = new SiteDomainField({
	      siteDomainOptions: domainSettings,
	      helpMessages: {
	        site: this.helpMessageProviderFactory()
	      }
	    });
	    main_core.Event.bind(siteDomainField.getFieldView().getInputNode(), 'keydown', () => {
	      var _this$getAnalytic4;
	      (_this$getAnalytic4 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic4.addEventConfigPortal(AnalyticSettingsEvent.CHANGE_PORTAL_SITE);
	    });
	    const firstTabRow = new ui_section.Row({
	      content: siteDomainField.render()
	    });
	    new ui_formElements_field.SettingsRow({
	      row: firstTabRow,
	      parent: firstTab,
	      child: siteDomainField
	    });
	    const secondTab = new ui_formElements_field.TabField({
	      parent: tabsField,
	      tabsOptions: this.getValue('tabDomain')
	    });
	    const descriptionRow = new ui_section.Row({
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getOwnDomainTabBody)[_getOwnDomainTabBody](domainSettings)
	    });
	    new ui_formElements_field.SettingsRow({
	      row: descriptionRow,
	      parent: secondTab
	    });
	    tabsField.activateTab(firstTab);
	    //endregion

	    return sectionField;
	  }
	  buildThemeSection(themePickerSettings, portalSettings) {
	    if (!this.hasValue('sectionSiteTheme')) {
	      return;
	    }
	    const sectionView = new ui_section.Section(this.getValue('sectionSiteTheme'));
	    const sectionField = new ui_formElements_field.SettingsSection({
	      section: sectionView,
	      parent: this
	    });

	    // 1. This is a description on blue box
	    new ui_formElements_field.SettingsRow({
	      row: new ui_section.Row({
	        content: new ui_alerts.Alert({
	          text: `
						${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_PORTAL_THEME_DESCRIPTION')}
						<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=18325288')">
							${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
						</a>
					`,
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
	      portalSettings,
	      themePickerSettings
	    });
	    return sectionField;
	  }
	}
	function _headerWidgetRenderAlternative2() {
	  if (!this.hasValue('portalSettings')) {
	    return '';
	  }
	  const portalSettings = this.getValue('portalSettings');
	  const portalThemeSettings = this.getValue('portalThemeSettings');
	  const portalDomainSettings = this.getValue('portalDomainSettings');
	  const container = main_core.Tag.render(_t$c || (_t$c = _$c`
		<div class="intranet-settings__header-widget_box">
			<div class="intranet-settings__header-widget_main">
				<div class="intranet-settings__header-widget_icon" data-role="logo"></div>
				<div class="intranet-settings__header-widget_name" data-role="title">Bitrix</div>
				<div class="intranet-settings__header-widget_logo24" data-role="logo24">24</div>
			</div>
			<div class="intranet-settings__header-widget__link_box">
				<div class="intranet-settings__header-widget__link_value">${0}</div>
				<div data-role="copy" class="ui-icon-set --link-3 intranet-settings__header-widget__link_btn"></div>
			</div>
		</div>`), main_core.Text.encode(portalDomainSettings.hostname));
	  setPortalSettings(container, portalSettings);
	  setPortalThemeSettings(container, portalThemeSettings == null ? void 0 : portalThemeSettings.theme);
	  const copyButton = container.querySelector('[data-role="copy"]');
	  BX.clipboard.bindCopyClick(copyButton, {
	    text: () => {
	      return portalDomainSettings.hostname;
	    }
	  });
	  return container;
	}
	function _getOwnDomainTabBody2(domainSettings) {
	  const copyButton = main_core.Tag.render(_t2$7 || (_t2$7 = _$c`<div class="ui-icon-set --copy-plates intranet-settings__domain__list_btn"></div>`));
	  const exampleDns = domainSettings.exampleDns.join('<br>');
	  BX.clipboard.bindCopyClick(copyButton, {
	    text: () => {
	      return exampleDns.replaceAll('<br>', "\n");
	    }
	  });
	  const res = main_core.Tag.render(_t3$5 || (_t3$5 = _$c`<div class="intranet-settings__domain__list_box">
						<ul class="intranet-settings__domain__list">
							<li class="intranet-settings__domain__list_item">
								${0}
								<div class="intranet-settings__domain_box">
									${0}
									${0}
								</div>
							</li>
							<li class="intranet-settings__domain__list_item">
								${0}
							</li>
							<li class="intranet-settings__domain__list_item">
								${0}
							</li>
						</ul>
						<a target="_blank" href="/settings/support.php" class="settings-tools-description-link">${0}</a>
					</div>`), main_core.Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP1'), exampleDns, copyButton, main_core.Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP2'), main_core.Loc.getMessage('INTRANET_SETTINGS_OWN_DOMAIN_HELP3'), main_core.Loc.getMessage('INTRANET_SETTINGS_WRITE_TO_SUPPORT'));
	  if (domainSettings.isCustomizable !== true) {
	    main_core.Event.bind(res.querySelector('a.settings-tools-description-link'), 'click', event => {
	      BX.UI.InfoHelper.show('limit_office_own_domain');
	      event.preventDefault();
	      return false;
	    });
	  }
	  return res;
	}

	let _$d = t => t,
	  _t$d,
	  _t2$8,
	  _t3$6;
	class PortalDeleteFormType {}
	PortalDeleteFormType.NOT_ADMIN = 'not_admin';
	PortalDeleteFormType.BOUND = 'bound';
	PortalDeleteFormType.MAIL = 'mail';
	PortalDeleteFormType.EMPLOYEE = 'employee';
	PortalDeleteFormType.NETWORK = 'network';
	PortalDeleteFormType.DEFAULT = 'default';
	var _container$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _verificationOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("verificationOptions");
	class PortalDeleteForm extends main_core_events.EventEmitter {
	  constructor(verificationOptions) {
	    super();
	    Object.defineProperty(this, _container$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _verificationOptions, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _verificationOptions)[_verificationOptions] = verificationOptions;
	    this.setEventNamespace('BX.Intranet.Settings:PortalDeleteForm');
	  }
	  getDescription() {
	    return main_core.Tag.render(_t$d || (_t$d = _$d`
			${0}
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_MSGVER_1', {
	      '#MORE_DETAILS#': this.getMoreDetails()
	    }));
	  }
	  getMoreDetails() {
	    return `
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=19566456')">
				${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		`;
	  }
	  getBodyClass() {
	    return '--warning';
	  }
	  getConfirmButtonText() {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL');
	  }
	  getInputContainer() {}
	  getContainer() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1] = main_core.Tag.render(_t2$8 || (_t2$8 = _$d`
				<div class="intranet-settings__portal-delete-form_wrapper ${0}">
					<div class="intranet-settings__portal-delete-form_body">
						<div class="intranet-settings__portal-delete-icon-wrapper">
							<div class="ui-icon-set --warning"></div>
						</div>
						<div class="intranet-settings__portal-delete-form_description-wrapper">
							<span class="intranet-settings__portal-delete-form_description">
								${0}
							</span>
							${0}
						</div>
					</div>
					${0}
				</div>
			`), this.getBodyClass(), this.getDescription(), this.getInputContainer(), this.getButtonContainer());
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1];
	  }
	  onConfirmEventHandler() {
	    this.getConfirmButton().setWaiting(true);
	    top.BX.Runtime.loadExtension('bitrix24.portal-delete').then(exports => {
	      const {
	        PortalDelete
	      } = exports;
	      const portalDelete = new PortalDelete(babelHelpers.classPrivateFieldLooseBase(this, _verificationOptions)[_verificationOptions]);
	      portalDelete.showCheckwordPopup();
	      this.getConfirmButton().setWaiting(false);
	    });
	  }
	  getButtonContainer() {
	    return main_core.Tag.render(_t3$6 || (_t3$6 = _$d`
			<span class="intranet-settings__portal-delete-form_buttons-wrapper">
				${0}
			</span>
		`), this.getConfirmButton().getContainer());
	  }
	  getConfirmButton() {
	    if (!this.confirmButton) {
	      var _this$getConfirmButto;
	      this.confirmButton = new ui_buttons.Button({
	        text: (_this$getConfirmButto = this.getConfirmButtonText()) != null ? _this$getConfirmButto : '',
	        noCaps: true,
	        round: true,
	        className: '--confirm',
	        events: {
	          click: () => {
	            this.onConfirmEventHandler();
	          }
	        },
	        props: {
	          'data-bx-role': 'delete-portal-confirm'
	        }
	      });
	    }
	    return this.confirmButton;
	  }
	  sendChangeFormEvent(type) {
	    this.emit('updateForm', new main_core_events.BaseEvent({
	      data: {
	        type: type != null ? type : null
	      }
	    }));
	  }
	}

	let _$e = t => t,
	  _t$e;
	var _isFreeLicense = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFreeLicense");
	class PortalDeleteFormEmployee extends PortalDeleteForm {
	  constructor(isFreeLicense) {
	    super();
	    Object.defineProperty(this, _isFreeLicense, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isFreeLicense)[_isFreeLicense] = isFreeLicense;
	  }
	  getDescription() {
	    return main_core.Tag.render(_t$e || (_t$e = _$e`
			${0}
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_EMPLOYEE', {
	      '#MORE_DETAILS#': this.getMoreDetails()
	    }));
	  }
	  getConfirmButtonText() {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL_FIRE_EMPLOYEE');
	  }
	  onConfirmEventHandler() {
	    this.getConfirmButton().setWaiting(true);
	    BX.SidePanel.Instance.open('/company/?apply_filter=Y&FIRED=N', {
	      events: {
	        onCloseComplete: () => {
	          main_core.ajax.runAction('bitrix24.portal.getActiveUserCount').then(response => {
	            this.getConfirmButton().setWaiting(false);
	            if (response.data <= 1) {
	              this.sendChangeFormEvent(babelHelpers.classPrivateFieldLooseBase(this, _isFreeLicense)[_isFreeLicense] ? PortalDeleteFormType.DEFAULT : PortalDeleteFormType.MAIL);
	            }
	          }).catch(reject => {
	            this.getConfirmButton().setWaiting(false);
	            reject.errors.forEach(error => {
	              console.log(error.message);
	            });
	          });
	        }
	      }
	    });
	  }
	}

	let _$f = t => t,
	  _t$f;
	var _mailForRequest = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mailForRequest");
	var _portalUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("portalUrl");
	var _mailLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mailLink");
	var _getMailLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMailLink");
	class PortalDeleteFormMail extends PortalDeleteForm {
	  constructor(mailForRequest, portalUrl) {
	    super();
	    Object.defineProperty(this, _getMailLink, {
	      value: _getMailLink2
	    });
	    Object.defineProperty(this, _mailForRequest, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _portalUrl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _mailLink, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _mailForRequest)[_mailForRequest] = mailForRequest;
	    babelHelpers.classPrivateFieldLooseBase(this, _portalUrl)[_portalUrl] = portalUrl;
	  }
	  getConfirmButtonText() {
	    return main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL_MAIL', {
	      '#MAIL#': babelHelpers.classPrivateFieldLooseBase(this, _mailForRequest)[_mailForRequest]
	    });
	  }
	  onConfirmEventHandler() {
	    top.window.location.href = babelHelpers.classPrivateFieldLooseBase(this, _getMailLink)[_getMailLink]();
	  }
	  getDescription() {
	    return main_core.Tag.render(_t$f || (_t$f = _$f`
			${0}
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_MAIL', {
	      '#MAIL#': babelHelpers.classPrivateFieldLooseBase(this, _mailForRequest)[_mailForRequest],
	      '#MAIL_LINK#': babelHelpers.classPrivateFieldLooseBase(this, _getMailLink)[_getMailLink](),
	      '#MORE_DETAILS#': this.getMoreDetails()
	    }));
	  }
	}
	function _getMailLink2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _mailLink)[_mailLink]) {
	    const mailBody = main_core.Loc.getMessage('INTRANET_SETTINGS_PORTAL_DELETE_MAIL_BODY', {
	      '#PORTAL_URL#': babelHelpers.classPrivateFieldLooseBase(this, _portalUrl)[_portalUrl]
	    });
	    const mailSubject = main_core.Loc.getMessage('INTRANET_SETTINGS_PORTAL_DELETE_MAIL_SUBJECT', {
	      '#PORTAL_URL#': babelHelpers.classPrivateFieldLooseBase(this, _portalUrl)[_portalUrl]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _mailLink)[_mailLink] = `mailto:${babelHelpers.classPrivateFieldLooseBase(this, _mailForRequest)[_mailForRequest]}?body=${mailBody}&subject=${mailSubject}`;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _mailLink)[_mailLink];
	}

	let _$g = t => t,
	  _t$g;
	class PortalDeleteFormNotAdmin extends PortalDeleteForm {
	  getButtonContainer() {}
	  getDescription() {
	    return main_core.Tag.render(_t$g || (_t$g = _$g`
			${0}
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=19566456')">
				${0}
			</a>
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_NOT_ADMIN'), main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE'));
	  }
	}

	let _$h = t => t,
	  _t$h;
	class PortalDeleteFormBound extends PortalDeleteForm {
	  getButtonContainer() {}
	  getDescription() {
	    return main_core.Tag.render(_t$h || (_t$h = _$h`
			${0}
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_BOUND', {
	      '#MORE_DETAILS#': this.getMoreDetails()
	    }));
	  }
	}

	let _$i = t => t,
	  _t$i;
	var _networkUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("networkUrl");
	class PortalDeleteFormNetwork extends PortalDeleteForm {
	  constructor(networkUrl) {
	    super();
	    Object.defineProperty(this, _networkUrl, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _networkUrl)[_networkUrl] = networkUrl;
	  }
	  getButtonContainer() {
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _networkUrl)[_networkUrl])) {
	      return super.getButtonContainer();
	    }
	    return null;
	  }
	  getConfirmButton() {
	    if (!this.confirmButton) {
	      this.confirmButton = new ui_buttons.Button({
	        tag: ui_buttons.Button.Tag.LINK,
	        link: babelHelpers.classPrivateFieldLooseBase(this, _networkUrl)[_networkUrl],
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL_NETWORK_LINK'),
	        noCaps: true,
	        round: true,
	        className: '--confirm',
	        props: {
	          'data-bx-role': 'delete-portal-confirm',
	          'target': '_top'
	        }
	      });
	    }
	    return this.confirmButton;
	  }
	  getDescription() {
	    return main_core.Tag.render(_t$i || (_t$i = _$i`
			${0}
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_NETWORK', {
	      '#MORE_DETAILS#': this.getMoreDetails()
	    }));
	  }
	}

	var _options$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _settingsRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settingsRow");
	var _form = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("form");
	var _defaultBodyClass = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultBodyClass");
	var _bodyClass = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bodyClass");
	var _renderFormRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFormRow");
	var _bindFormEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindFormEvents");
	var _updateSectionBodyClass = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateSectionBodyClass");
	class PortalDeleteSection extends ui_formElements_field.SettingsSection {
	  constructor(params) {
	    super(params);
	    Object.defineProperty(this, _updateSectionBodyClass, {
	      value: _updateSectionBodyClass2
	    });
	    Object.defineProperty(this, _bindFormEvents, {
	      value: _bindFormEvents2
	    });
	    Object.defineProperty(this, _renderFormRow, {
	      value: _renderFormRow2
	    });
	    Object.defineProperty(this, _options$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _settingsRow, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _form, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _defaultBodyClass, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _bodyClass, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _defaultBodyClass)[_defaultBodyClass] = this.getSectionView().className.bodyActive;
	    babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2] = params.options;
	    let _type;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].isAdmin) {
	      _type = PortalDeleteFormType.NOT_ADMIN;
	    } else if (!babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].isFreeLicense) {
	      _type = PortalDeleteFormType.MAIL;
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].isBound) {
	      _type = PortalDeleteFormType.BOUND;
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].isEmployeesLeft) {
	      _type = PortalDeleteFormType.EMPLOYEE;
	    } else if (!babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].verificationOptions) {
	      _type = PortalDeleteFormType.NETWORK;
	    } else {
	      _type = PortalDeleteFormType.DEFAULT;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _renderFormRow)[_renderFormRow](_type);
	  }
	}
	function _renderFormRow2(type) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _settingsRow)[_settingsRow]) {
	    this.removeChild(babelHelpers.classPrivateFieldLooseBase(this, _settingsRow)[_settingsRow]);
	    main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _settingsRow)[_settingsRow].render());
	  }
	  switch (type) {
	    case PortalDeleteFormType.MAIL:
	      babelHelpers.classPrivateFieldLooseBase(this, _form)[_form] = new PortalDeleteFormMail(babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].mailForRequest, babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].portalUrl);
	      break;
	    case PortalDeleteFormType.EMPLOYEE:
	      babelHelpers.classPrivateFieldLooseBase(this, _form)[_form] = new PortalDeleteFormEmployee(babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].isFreeLicense);
	      break;
	    case PortalDeleteFormType.NOT_ADMIN:
	      babelHelpers.classPrivateFieldLooseBase(this, _form)[_form] = new PortalDeleteFormNotAdmin();
	      break;
	    case PortalDeleteFormType.BOUND:
	      babelHelpers.classPrivateFieldLooseBase(this, _form)[_form] = new PortalDeleteFormBound();
	      break;
	    case PortalDeleteFormType.NETWORK:
	      babelHelpers.classPrivateFieldLooseBase(this, _form)[_form] = new PortalDeleteFormNetwork(babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].networkUrl);
	      break;
	    default:
	      babelHelpers.classPrivateFieldLooseBase(this, _form)[_form] = new PortalDeleteForm(babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].verificationOptions);
	      break;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _updateSectionBodyClass)[_updateSectionBodyClass]();
	  babelHelpers.classPrivateFieldLooseBase(this, _bindFormEvents)[_bindFormEvents]();
	  const formRow = new ui_section.Row({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getContainer()
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _settingsRow)[_settingsRow] = new ui_formElements_field.SettingsRow({
	    row: formRow
	  });
	  this.addChild(babelHelpers.classPrivateFieldLooseBase(this, _settingsRow)[_settingsRow]);
	  this.render();
	}
	function _bindFormEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].subscribe('closeForm', () => {
	    this.getSectionView().toggle(false);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].subscribe('updateForm', event => {
	    if (event.data.type) {
	      babelHelpers.classPrivateFieldLooseBase(this, _renderFormRow)[_renderFormRow](event.data.type);
	    }
	  });
	}
	function _updateSectionBodyClass2() {
	  main_core.Dom.removeClass(this.getSectionView().getContent(), babelHelpers.classPrivateFieldLooseBase(this, _bodyClass)[_bodyClass]);
	  babelHelpers.classPrivateFieldLooseBase(this, _bodyClass)[_bodyClass] = babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getBodyClass();
	  this.getSectionView().className.bodyActive = babelHelpers.classPrivateFieldLooseBase(this, _defaultBodyClass)[_defaultBodyClass] + ' ' + babelHelpers.classPrivateFieldLooseBase(this, _bodyClass)[_bodyClass];
	  if (this.getSectionView().isOpen) {
	    main_core.Dom.addClass(this.getSectionView().getContent(), babelHelpers.classPrivateFieldLooseBase(this, _bodyClass)[_bodyClass]);
	  }
	}

	let _$j = t => t,
	  _t$j,
	  _t2$9;
	var _header = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("header");
	var _buildDateTimeSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildDateTimeSection");
	var _buildMailsSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildMailsSection");
	var _buildCRMMapsSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildCRMMapsSection");
	var _buildCardsProductPropertiesSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildCardsProductPropertiesSection");
	var _buildAdditionalSettingsSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildAdditionalSettingsSection");
	var _geoDataSwitch = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("geoDataSwitch");
	class ConfigurationPage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _geoDataSwitch, {
	      value: _geoDataSwitch2
	    });
	    Object.defineProperty(this, _buildAdditionalSettingsSection, {
	      value: _buildAdditionalSettingsSection2
	    });
	    Object.defineProperty(this, _buildCardsProductPropertiesSection, {
	      value: _buildCardsProductPropertiesSection2
	    });
	    Object.defineProperty(this, _buildCRMMapsSection, {
	      value: _buildCRMMapsSection2
	    });
	    Object.defineProperty(this, _buildMailsSection, {
	      value: _buildMailsSection2
	    });
	    Object.defineProperty(this, _buildDateTimeSection, {
	      value: _buildDateTimeSection2
	    });
	    Object.defineProperty(this, _header, {
	      writable: true,
	      value: void 0
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_CONFIGURATION');
	  }
	  getType() {
	    return 'configuration';
	  }
	  headerWidgetRender() {
	    var _this$getValue;
	    let timeFormat = '';
	    if (((_this$getValue = this.getValue('isFormat24Hour')) == null ? void 0 : _this$getValue.current) === 'Y') {
	      timeFormat = this.getValue('format24HourTime');
	    } else {
	      timeFormat = this.getValue('format12HourTime');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _header)[_header] = main_core.Tag.render(_t$j || (_t$j = _$j`
		<div class="intranet-settings__date-widget_box">
			<span class="ui-icon-set --earth-language"></span>
			<div class="intranet-settings__date-widget_content">
				<div class="intranet-settings__date-widget_inner">
					<span data-role="time" class="intranet-settings__date-widget_title">${0}</span>
					<span class="intranet-settings__date-widget_subtitle">${0}</span>
				</div>
				<div data-role="date" class="intranet-settings__date-widget_subtitle">${0}</div>
			</div>
		</div>`), timeFormat, this.getValue('offsetUTC'), this.getValue('currentDate'));
	    return babelHelpers.classPrivateFieldLooseBase(this, _header)[_header];
	  }
	  appendSections(contentNode) {
	    let dateTimeSection = babelHelpers.classPrivateFieldLooseBase(this, _buildDateTimeSection)[_buildDateTimeSection]();
	    dateTimeSection == null ? void 0 : dateTimeSection.renderTo(contentNode);
	    let mailsSection = babelHelpers.classPrivateFieldLooseBase(this, _buildMailsSection)[_buildMailsSection]();
	    mailsSection == null ? void 0 : mailsSection.renderTo(contentNode);
	    if (this.hasValue('mapsProviderCRM') && this.getValue('mapsProviderCRM')) {
	      let mapsSection = babelHelpers.classPrivateFieldLooseBase(this, _buildCRMMapsSection)[_buildCRMMapsSection]();
	      mapsSection == null ? void 0 : mapsSection.renderTo(contentNode);
	    }
	    let cardsProductPropertiesSection = babelHelpers.classPrivateFieldLooseBase(this, _buildCardsProductPropertiesSection)[_buildCardsProductPropertiesSection]();
	    cardsProductPropertiesSection == null ? void 0 : cardsProductPropertiesSection.renderTo(contentNode);
	    let additionalSettingsSection = babelHelpers.classPrivateFieldLooseBase(this, _buildAdditionalSettingsSection)[_buildAdditionalSettingsSection]();
	    additionalSettingsSection == null ? void 0 : additionalSettingsSection.renderTo(contentNode);
	    if (this.hasValue('deletePortalOptions') && this.hasValue('sectionDeletePortal')) {
	      const deletePortalSection = new ui_section.Section(this.getValue('sectionDeletePortal'));
	      const settingsSection = new PortalDeleteSection({
	        section: deletePortalSection,
	        parent: this,
	        options: this.getValue('deletePortalOptions')
	      });
	      settingsSection.renderTo(contentNode);
	    }
	  }
	}
	function _buildDateTimeSection2() {
	  if (!this.hasValue('sectionDateFormat')) {
	    return;
	  }
	  let dateTimeSection = new ui_section.Section(this.getValue('sectionDateFormat'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    section: dateTimeSection,
	    parent: this
	  });
	  if (this.hasValue('culture')) {
	    let regionField = new ui_formElements_view.Selector(this.getValue('culture'));
	    ConfigurationPage.addToSectionHelper(regionField, settingsSection, new ui_section.Row({
	      className: '--intranet-settings__mb-20'
	    }));
	    main_core.Event.bind(regionField.getInputNode(), 'change', event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _header)[_header].querySelector('[data-role="date"]').innerHTML = this.getValue('longDates')[event.target.value];
	    });
	  }
	  if (this.hasValue('isFormat24Hour')) {
	    let format24Time = new ui_formElements_view.InlineChecker(this.getValue('isFormat24Hour'));
	    ConfigurationPage.addToSectionHelper(format24Time, settingsSection);
	    main_core_events.EventEmitter.subscribe(format24Time, 'change', event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _header)[_header].querySelector('[data-role="time"]').innerHTML = format24Time.isChecked() ? this.getValue('format24HourTime') : this.getValue('format12HourTime');
	    });
	  }
	  return settingsSection;
	}
	function _buildMailsSection2() {
	  if (!this.hasValue('sectionLetters')) {
	    return;
	  }
	  let mailsSection = new ui_section.Section(this.getValue('sectionLetters'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    section: mailsSection,
	    parent: this
	  });
	  if (this.hasValue('trackOutMailsRead')) {
	    let trackOutLettersRead = new ui_formElements_view.Checker(this.getValue('trackOutMailsRead'));
	    let showQuitRow = new ui_section.Row({});
	    ConfigurationPage.addToSectionHelper(trackOutLettersRead, settingsSection, showQuitRow);
	  }
	  if (this.hasValue('trackOutMailsClick')) {
	    let trackOutMailsClick = new ui_formElements_view.Checker(this.getValue('trackOutMailsClick'));
	    let showQuitRow = new ui_section.Row({});
	    ConfigurationPage.addToSectionHelper(trackOutMailsClick, settingsSection, showQuitRow);
	  }
	  if (this.hasValue('defaultEmailFrom')) {
	    let defaultEmailFrom = new ui_formElements_view.TextInput(this.getValue('defaultEmailFrom'));
	    let showQuitRow = new ui_section.Row({});
	    ConfigurationPage.addToSectionHelper(defaultEmailFrom, settingsSection, showQuitRow);
	  }
	  return settingsSection;
	}
	function _buildCRMMapsSection2() {
	  var _this$getValue$label;
	  if (!this.hasValue('sectionMapsInCrm')) {
	    return;
	  }
	  let mapsSection = new ui_section.Section(this.getValue('sectionMapsInCrm'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    section: mapsSection,
	    parent: this
	  });
	  let cardsProvider = new ui_formElements_view.Selector({
	    label: (_this$getValue$label = this.getValue('mapsProviderCRM').label) != null ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
	    name: this.getValue('mapsProviderCRM').name,
	    items: this.getValue('mapsProviderCRM').values,
	    current: this.getValue('mapsProviderCRM').current
	  });
	  let cardsProviderRow = new ui_section.Row({
	    separator: 'bottom',
	    className: '--block'
	  });
	  ConfigurationPage.addToSectionHelper(cardsProvider, settingsSection, cardsProviderRow);
	  const separatorRow = new ui_section.SeparatorRow({
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  new ui_formElements_field.SettingsRow({
	    row: separatorRow,
	    parent: settingsSection
	  });
	  const description = new BX.UI.Alert({
	    text: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_DESCRIPTION', {
	      '#GOOGLE_API_URL#': this.getValue('googleApiUrl')
	    }),
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	  const descriptionRow = new ui_section.Row({
	    separator: 'top',
	    content: description.getContainer(),
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: settingsSection
	  });
	  const googleKeyFrontend = new ui_formElements_view.TextInputInline({
	    inputName: 'API_KEY_FRONTEND',
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_PUBLIC'),
	    hintTitle: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_PUBLIC_HINT'),
	    value: this.getValue('API_KEY_FRONTEND').value,
	    placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER')
	  });
	  const googleKeyFrontendRow = new ui_section.Row({
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  ConfigurationPage.addToSectionHelper(googleKeyFrontend, settingsSection, googleKeyFrontendRow);
	  const mapApiKeyBackend = new ui_formElements_view.TextInputInline({
	    inputName: 'API_KEY_BACKEND',
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_SERVER'),
	    hintTitle: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_SERVER_HINT'),
	    value: this.getValue('API_KEY_BACKEND').value,
	    placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER')
	  });
	  const googleKeyBackendRow = new ui_section.Row({
	    content: mapApiKeyBackend.render(),
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM',
	    separator: 'bottom',
	    className: '--block'
	  });
	  ConfigurationPage.addToSectionHelper(mapApiKeyBackend, settingsSection, googleKeyBackendRow);
	  const separatorRow1 = new ui_section.SeparatorRow({});
	  new ui_formElements_field.SettingsRow({
	    row: separatorRow1,
	    parent: settingsSection
	  });
	  let showPhotoPlacesMaps = new ui_formElements_view.Checker({
	    inputName: 'SHOW_PHOTOS_ON_MAP',
	    title: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_PHOTO_PLACES_MAPS'),
	    hintOn: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
	    hintOff: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
	    checked: this.getValue('SHOW_PHOTOS_ON_MAP').value === '1'
	  });
	  let showPhotoPlacesMapsRow = new ui_section.Row({
	    separator: 'top',
	    className: '--block',
	    content: showPhotoPlacesMaps.render(),
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  ConfigurationPage.addToSectionHelper(showPhotoPlacesMaps, settingsSection, showPhotoPlacesMapsRow);
	  let useGeocodingService = new ui_formElements_view.Checker({
	    inputName: 'USE_GEOCODING_SERVICE',
	    title: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_GEOCODING_SERVICE'),
	    hintOn: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
	    hintOff: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
	    checked: this.getValue('USE_GEOCODING_SERVICE').value === '1'
	  });
	  let useGeocodingServiceRow = new ui_section.Row({
	    content: useGeocodingService.render(),
	    isHidden: this.getValue('mapsProviderCRM').current === 'OSM'
	  });
	  ConfigurationPage.addToSectionHelper(useGeocodingService, settingsSection, useGeocodingServiceRow);
	  cardsProvider.getInputNode().addEventListener('change', event => {
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
	  let productPropertiesSection = new ui_section.Section(this.getValue('sectionMapsInProduct'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    section: productPropertiesSection,
	    parent: this
	  });
	  if (this.hasValue('cardsProviderProductProperties')) {
	    var _this$getValue$label2;
	    let cardsProviderProductProperties = new ui_formElements_view.Selector({
	      label: (_this$getValue$label2 = this.getValue('cardsProviderProductProperties').label) != null ? _this$getValue$label2 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
	      name: this.getValue('cardsProviderProductProperties').name,
	      items: this.getValue('cardsProviderProductProperties').values,
	      current: this.getValue('cardsProviderProductProperties').current
	    });
	    let cardsProviderProductPropertiesRow = new ui_section.Row({
	      separator: 'bottom'
	    });
	    ConfigurationPage.addToSectionHelper(cardsProviderProductProperties, settingsSection, cardsProviderProductPropertiesRow);
	    new ui_formElements_field.SettingsRow({
	      row: new ui_section.SeparatorRow(),
	      parent: settingsSection
	    });
	    const descriptionYandex = new BX.UI.Alert({
	      text: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_YANDEX_DESCRIPTION', {
	        '#YANDEX_API_URL#': this.getValue('yandexApiUrl')
	      }),
	      inline: true,
	      size: BX.UI.Alert.Size.SMALL,
	      color: BX.UI.Alert.Color.PRIMARY,
	      animated: true
	    });
	    const descriptionYandexRow = new ui_section.Row({
	      content: descriptionYandex.getContainer(),
	      isHidden: this.getValue('cardsProviderProductProperties').current !== 'yandex'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: descriptionYandexRow,
	      parent: settingsSection
	    });
	    const yandexKeyProductProperties = new ui_formElements_view.TextInput({
	      inputName: 'yandexKeyProductProperties',
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAP_PRODUCT_PROPERTIES_YANDEX_KEY'),
	      value: this.getValue('yandexKeyProductProperties'),
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER')
	    });
	    const yandexKeyProductPropertiesRow = new ui_section.Row({
	      separator: 'bottom',
	      className: '--block',
	      content: yandexKeyProductProperties.render(),
	      isHidden: this.getValue('cardsProviderProductProperties').current !== 'yandex'
	    });
	    ConfigurationPage.addToSectionHelper(yandexKeyProductProperties, settingsSection, yandexKeyProductPropertiesRow);
	    const descriptionGoogle = new BX.UI.Alert({
	      text: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_DESCRIPTION', {
	        '#GOOGLE_API_URL#': this.getValue('googleApiUrl')
	      }),
	      inline: true,
	      size: BX.UI.Alert.Size.SMALL,
	      color: BX.UI.Alert.Color.PRIMARY,
	      animated: true
	    });
	    const descriptionGoogleRow = new ui_section.Row({
	      content: descriptionGoogle.getContainer(),
	      isHidden: this.getValue('cardsProviderProductProperties').current !== 'google'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: descriptionGoogleRow,
	      parent: settingsSection
	    });
	    const googleKeyProductProperties = new ui_formElements_view.TextInput({
	      inputName: 'googleKeyProductProperties',
	      label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAP_PRODUCT_PROPERTIES_GOOGLE_KEY'),
	      value: this.getValue('googleKeyProductProperties'),
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER')
	    });
	    const googleKeyProductPropertiesRow = new ui_section.Row({
	      content: googleKeyProductProperties.render(),
	      isHidden: this.getValue('cardsProviderProductProperties').current !== 'google'
	    });
	    ConfigurationPage.addToSectionHelper(googleKeyProductProperties, settingsSection, googleKeyProductPropertiesRow);
	    cardsProviderProductProperties.getInputNode().addEventListener('change', event => {
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
	  if (!this.hasValue('sectionOther')) {
	    return;
	  }
	  let additionalSettingsSection = new ui_section.Section(this.getValue('sectionOther'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    section: additionalSettingsSection,
	    parent: this
	  });
	  if (this.hasValue('allowUserInstallApplication')) {
	    let allInstallMarketApplication = new ui_formElements_view.Checker(this.getValue('allowUserInstallApplication'));
	    let allInstallMarketApplicationRow = new ui_section.Row({});
	    main_core_events.EventEmitter.subscribe(allInstallMarketApplication.switcher, 'toggled', () => {
	      var _this$getAnalytic;
	      (_this$getAnalytic = this.getAnalytic()) == null ? void 0 : _this$getAnalytic.addEventConfigConfiguration(AnalyticSettingsEvent.CHANGE_MARKET, allInstallMarketApplication.isChecked());
	    });
	    ConfigurationPage.addToSectionHelper(allInstallMarketApplication, settingsSection, allInstallMarketApplicationRow);
	  }
	  if (this.hasValue('allCanBuyTariff')) {
	    var _this$getValue$title;
	    const messageNode = main_core.Tag.render(_t2$9 || (_t2$9 = _$j`<span>${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE'));
	    let allCanBuyTariff = new ui_formElements_view.Checker({
	      inputName: this.getValue('allCanBuyTariff').inputName,
	      title: (_this$getValue$title = this.getValue('allCanBuyTariff').title) != null ? _this$getValue$title : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALL_CAN_BUY_TARIFF'),
	      hintOn: this.getValue('allCanBuyTariff').hintOn,
	      checked: this.getValue('allCanBuyTariff').checked,
	      isEnable: this.getValue('allCanBuyTariff').isEnable,
	      bannerCode: 'limit_why_pay_tariff_everyone',
	      helpMessageProvider: this.helpMessageProviderFactory(messageNode)
	    });
	    let allCanBuyTariffRow = new ui_section.Row({});
	    main_core_events.EventEmitter.subscribe(allCanBuyTariff.switcher, 'toggled', () => {
	      var _this$getAnalytic2;
	      (_this$getAnalytic2 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic2.addEventConfigConfiguration(AnalyticSettingsEvent.CHANGE_PAY_TARIFF, allCanBuyTariff.isChecked());
	    });
	    ConfigurationPage.addToSectionHelper(allCanBuyTariff, settingsSection, allCanBuyTariffRow);
	  }
	  if (this.hasValue('allowMeasureStressLevel')) {
	    let allowMeasureStressLevel = new ui_formElements_view.Checker(this.getValue('allowMeasureStressLevel'));
	    let allowMeasureStressLevelRow = new ui_section.Row({});
	    ConfigurationPage.addToSectionHelper(allowMeasureStressLevel, settingsSection, allowMeasureStressLevelRow);
	  }
	  if (this.hasValue('collectGeoData')) {
	    let collectGeoData = new ui_formElements_view.Checker(this.getValue('collectGeoData'));
	    main_core_events.EventEmitter.subscribe(collectGeoData.switcher, 'toggled', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _geoDataSwitch)[_geoDataSwitch](collectGeoData);
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
	      'onCancel': function () {
	        element.switcher.check(false);
	        return true;
	      },
	      'onOk': function () {
	        return true;
	      }
	    });
	  }
	}

	let _$k = t => t,
	  _t$k,
	  _t2$a,
	  _t3$7;
	var _buildScheduleTab = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildScheduleTab");
	var _buildScheduleSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildScheduleSection");
	var _buildHolidaysSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildHolidaysSection");
	var _forDepartmentsRender = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("forDepartmentsRender");
	class SchedulePage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _forDepartmentsRender, {
	      value: _forDepartmentsRender2
	    });
	    Object.defineProperty(this, _buildHolidaysSection, {
	      value: _buildHolidaysSection2
	    });
	    Object.defineProperty(this, _buildScheduleSection, {
	      value: _buildScheduleSection2
	    });
	    Object.defineProperty(this, _buildScheduleTab, {
	      value: _buildScheduleTab2
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_SCHEDULE');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_SCHEDULE');
	  }
	  getType() {
	    return 'schedule';
	  }
	  appendSections(contentNode) {
	    const scheduleSection = babelHelpers.classPrivateFieldLooseBase(this, _buildScheduleSection)[_buildScheduleSection]();
	    scheduleSection.renderTo(contentNode);
	    const holidaysSection = babelHelpers.classPrivateFieldLooseBase(this, _buildHolidaysSection)[_buildHolidaysSection]();
	    holidaysSection.renderTo(contentNode);
	  }
	}
	function _buildScheduleTab2(parent) {
	  let workTimeRow = new ui_section.Row({
	    className: 'intranet-settings__work-time_container --no-padding'
	  });
	  let settingsRow = new ui_formElements_field.SettingsRow({
	    row: workTimeRow,
	    parent: parent
	  });
	  if (this.hasValue('WORK_TIME_START')) {
	    const workTimeStartField = new ui_formElements_view.Selector(this.getValue('WORK_TIME_START'));
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
	    const workTimeEndField = new ui_formElements_view.Selector(this.getValue('WORK_TIME_END'));
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
	  let containerTab = main_core.Tag.render(_t$k || (_t$k = _$k`<div><div>`));
	  main_core.Dom.append(workTimeRow.render(), containerTab);
	  if (this.hasValue('WEEK_DAYS')) {
	    var _this$getValue$label;
	    const itemPickerField = new ui_formElements_view.ItemPicker({
	      inputName: this.getValue('WEEK_DAYS').inputName,
	      isMulti: true,
	      label: (_this$getValue$label = this.getValue('WEEK_DAYS').label) != null ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEKEND'),
	      items: this.getValue('WEEK_DAYS').values,
	      current: this.getValue('WEEK_DAYS').multiValue
	    });
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: itemPickerField
	    });
	    const itemPickerRow = new ui_section.Row({
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
	    const weekStartField = new ui_formElements_view.ItemPicker(this.getValue('WEEK_START'));
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: weekStartField
	    });
	    main_core.Dom.addClass(weekStartField.render(), '--row-frame_gray');
	    const weekStartRow = new ui_section.Row({
	      content: weekStartField.render()
	    });
	    new ui_formElements_field.SettingsRow({
	      row: weekStartRow,
	      child: settingsField,
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
	  let scheduleSection = new ui_section.Section(this.getValue('sectionSchedule'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    parent: this,
	    section: scheduleSection
	  });
	  const tabsRow = new ui_formElements_field.SettingsRow({
	    parent: settingsSection
	  });
	  const tabsField = new ui_formElements_field.TabsField({
	    parent: tabsRow
	  });
	  const forCompanyTab = new ui_formElements_field.TabField({
	    parent: tabsField,
	    tabsOptions: this.getValue('tabForCompany')
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _buildScheduleTab)[_buildScheduleTab](forCompanyTab);
	  if (this.getValue('TIMEMAN').enabled) {
	    const forDepartmentTab = new ui_formElements_field.TabField({
	      parent: tabsField,
	      tabsOptions: this.getValue('tabForDepartment')
	    });
	    const forDepartmentRow = new ui_section.Row({
	      content: babelHelpers.classPrivateFieldLooseBase(this, _forDepartmentsRender)[_forDepartmentsRender]()
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
	  let holidaysSection = new ui_section.Section(this.getValue('sectionHoliday'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    parent: this,
	    section: holidaysSection
	  });
	  const countDays = (_this$getValue$value$ = (_this$getValue = this.getValue('year_holidays')) == null ? void 0 : (_this$getValue$value = _this$getValue.value) == null ? void 0 : (_this$getValue$value$2 = _this$getValue$value.match(/\d{1,2}.\d{1,2}/gm)) == null ? void 0 : _this$getValue$value$2.length) != null ? _this$getValue$value$ : 0;
	  let countDaysNode = main_core.Tag.render(_t2$a || (_t2$a = _$k`<div class="ui-section__field-label">${0}</div>`), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_INFO', {
	    '#COUNT_DAYS#': countDays
	  }));
	  const holidaysRow = new ui_section.Row({
	    content: countDaysNode
	  });
	  holidaysSection.append(holidaysRow.render());
	  if (this.hasValue('year_holidays')) {
	    const holidaysField = new ui_formElements_view.TextInput(this.getValue('year_holidays'));
	    SchedulePage.addToSectionHelper(holidaysField, settingsSection);
	    main_core.Event.bind(holidaysField.getInputNode(), 'keyup', () => {
	      var _holidaysField$getInp, _holidaysField$getInp2, _holidaysField$getInp3;
	      const count = (_holidaysField$getInp = holidaysField == null ? void 0 : (_holidaysField$getInp2 = holidaysField.getInputNode().value) == null ? void 0 : (_holidaysField$getInp3 = _holidaysField$getInp2.match(/\d{1,2}.\d{1,2}/gm)) == null ? void 0 : _holidaysField$getInp3.length) != null ? _holidaysField$getInp : 0;
	      countDaysNode.innerHTML = main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_INFO', {
	        '#COUNT_DAYS#': count
	      });
	    });
	  }
	  return settingsSection;
	}
	function _forDepartmentsRender2() {
	  return main_core.Tag.render(_t3$7 || (_t3$7 = _$k`
			<div class="intranet-settings__tab-info_container">
				<div class="intranet-settings__tab-info_text">${0}</div>
				<a href="/timeman/schedules/" class="ui-section__link" target="_blank">${0}</a>
			</div>
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_FOR_DEPARTMENTS'), main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_FOR_DEPARTMENTS_CONFIG'));
	}

	let _$l = t => t,
	  _t$l;
	var _buildGdprSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildGdprSection");
	class GdprPage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _buildGdprSection, {
	      value: _buildGdprSection2
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_GDPR');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_GDPR');
	  }
	  getType() {
	    return 'gdpr';
	  }
	  appendSections(contentNode) {
	    let gdprSection = babelHelpers.classPrivateFieldLooseBase(this, _buildGdprSection)[_buildGdprSection]();
	    gdprSection == null ? void 0 : gdprSection.renderTo(contentNode);
	  }
	  addApplicationsRender() {
	    if (this.hasValue('marketDirectory')) {
	      const marketDirectory = this.getValue('marketDirectory');
	      return main_core.Tag.render(_t$l || (_t$l = _$l`
				<div class="ui-text-right">
					<a class="ui-section__link" href="${0}detail/integrations24.gdprstaff/">
						${0}
					</a>
					<a class="ui-section__link" style="margin-left: 12px;" href="${0}detail/integrations24.gdpr/">
						${0}
					</a>
				</div>
			`), marketDirectory, main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_APPLICATION_EMPLOYEE'), marketDirectory, main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_APPLICATION_CRM'));
	    }
	    return null;
	  }
	}
	function _buildGdprSection2() {
	  if (!this.hasValue('sectionGdpr')) {
	    return;
	  }
	  let gdprSection = new ui_section.Section(this.getValue('sectionGdpr'));
	  let sectionSettings = new ui_formElements_field.SettingsSection({
	    section: gdprSection,
	    parent: this
	  });
	  const description = new BX.UI.Alert({
	    text: `
				${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_GDPR_DESCRIPTION')}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=7608199')">
					${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
				</br>
				<a class="ui-section__link" href="${this.getValue('dpaLink')}" target="_blank">
					${main_core.Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_AGREEMENT')}
				</a>
			`,
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	  const descriptionRow = new ui_section.Row({
	    content: description.getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: sectionSettings
	  });
	  if (this.hasValue('companyTitle')) {
	    var _this$getValue$label;
	    const titleField = new ui_formElements_view.TextInput({
	      inputName: 'companyTitle',
	      label: (_this$getValue$label = this.getValue('companyTitle').label) != null ? _this$getValue$label : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COMPANY_TITLE'),
	      value: this.getValue('companyTitle').value,
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_COMPANY_TITLE')
	    });
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: titleField
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: sectionSettings,
	      child: settingsField
	    });
	  }
	  if (this.hasValue('contactName')) {
	    var _this$getValue$label2;
	    const contactNameField = new ui_formElements_view.TextInput({
	      inputName: 'contactName',
	      label: (_this$getValue$label2 = this.getValue('contactName').label) != null ? _this$getValue$label2 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CONTACT_NAME'),
	      value: this.getValue('contactName').value,
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_CONTACT_NAME')
	    });
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: contactNameField
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: sectionSettings,
	      child: settingsField
	    });
	  }
	  if (this.hasValue('notificationEmail')) {
	    var _this$getValue$label3;
	    const emailField = new ui_formElements_view.TextInput({
	      inputName: 'notificationEmail',
	      label: (_this$getValue$label3 = this.getValue('notificationEmail').label) != null ? _this$getValue$label3 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_NOTIFICATION_EMAIL'),
	      value: this.getValue('notificationEmail').value,
	      placeholder: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_NOTIFICATION_EMAIL')
	    });
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: emailField
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: sectionSettings,
	      child: settingsField
	    });
	  }
	  if (this.hasValue('date')) {
	    var _this$getValue$label4;
	    const dateField = new ui_formElements_view.TextInput({
	      inputName: 'date',
	      label: (_this$getValue$label4 = this.getValue('date').label) != null ? _this$getValue$label4 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_DATE'),
	      value: this.getValue('date').value
	    });
	    main_core.Dom.adjust(dateField.render(), {
	      events: {
	        click: event => {
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
	    let settingsField = new ui_formElements_field.SettingsField({
	      fieldView: dateField
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: sectionSettings,
	      child: settingsField
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

	let _$m = t => t,
	  _t$m,
	  _t2$b,
	  _t3$8,
	  _t4$1,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9;
	var _otpChecker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("otpChecker");
	var _otpSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("otpSelector");
	var _otpPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("otpPopup");
	var _buildOTPSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildOTPSection");
	var _getOTPChecker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOTPChecker");
	var _getOTPPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOTPPopup");
	var _getOTPPeriodSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOTPPeriodSelector");
	var _getOTPDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOTPDescription");
	var _getOTPDescriptionText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOTPDescriptionText");
	var _buildAccessIPSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildAccessIPSection");
	var _getEmptyUserSelectorRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEmptyUserSelectorRow");
	var _getEmptyAccessIpRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEmptyAccessIpRow");
	var _getUserSelectorRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUserSelectorRow");
	var _getAccessIpRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAccessIpRow");
	var _getIpAccessDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIpAccessDescription");
	var _buildPasswordRecoverySection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildPasswordRecoverySection");
	var _buildDevicesHistorySection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildDevicesHistorySection");
	var _buildEventLogSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildEventLogSection");
	var _buildBlackListSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildBlackListSection");
	var _buildDataLeakProtectionSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildDataLeakProtectionSection");
	class SecurityPage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _buildDataLeakProtectionSection, {
	      value: _buildDataLeakProtectionSection2
	    });
	    Object.defineProperty(this, _buildBlackListSection, {
	      value: _buildBlackListSection2
	    });
	    Object.defineProperty(this, _buildEventLogSection, {
	      value: _buildEventLogSection2
	    });
	    Object.defineProperty(this, _buildDevicesHistorySection, {
	      value: _buildDevicesHistorySection2
	    });
	    Object.defineProperty(this, _buildPasswordRecoverySection, {
	      value: _buildPasswordRecoverySection2
	    });
	    Object.defineProperty(this, _getIpAccessDescription, {
	      value: _getIpAccessDescription2
	    });
	    Object.defineProperty(this, _getAccessIpRow, {
	      value: _getAccessIpRow2
	    });
	    Object.defineProperty(this, _getUserSelectorRow, {
	      value: _getUserSelectorRow2
	    });
	    Object.defineProperty(this, _getEmptyAccessIpRow, {
	      value: _getEmptyAccessIpRow2
	    });
	    Object.defineProperty(this, _getEmptyUserSelectorRow, {
	      value: _getEmptyUserSelectorRow2
	    });
	    Object.defineProperty(this, _buildAccessIPSection, {
	      value: _buildAccessIPSection2
	    });
	    Object.defineProperty(this, _getOTPDescriptionText, {
	      value: _getOTPDescriptionText2
	    });
	    Object.defineProperty(this, _getOTPDescription, {
	      value: _getOTPDescription2
	    });
	    Object.defineProperty(this, _getOTPPeriodSelector, {
	      value: _getOTPPeriodSelector2
	    });
	    Object.defineProperty(this, _getOTPPopup, {
	      value: _getOTPPopup2
	    });
	    Object.defineProperty(this, _getOTPChecker, {
	      value: _getOTPChecker2
	    });
	    Object.defineProperty(this, _buildOTPSection, {
	      value: _buildOTPSection2
	    });
	    Object.defineProperty(this, _otpChecker, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _otpSelector, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _otpPopup, {
	      writable: true,
	      value: void 0
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_SECURITY');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_SECURITY');
	  }
	  getType() {
	    return 'security';
	  }
	  appendSections(contentNode) {
	    var _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	    const isBitrix24 = this.hasValue('IS_BITRIX_24') && this.getValue('IS_BITRIX_24');
	    if (this.hasValue('SECURITY_OTP_ENABLED') && this.getValue('SECURITY_OTP_ENABLED')) {
	      var _babelHelpers$classPr;
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _buildOTPSection)[_buildOTPSection]()) == null ? void 0 : _babelHelpers$classPr.renderTo(contentNode);
	    }
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _buildDataLeakProtectionSection)[_buildDataLeakProtectionSection]()) == null ? void 0 : _babelHelpers$classPr2.renderTo(contentNode);

	    // if (isBitrix24)
	    // {
	    // 	this.#buildPasswordRecoverySection().renderTo(contentNode);
	    // }
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _buildDevicesHistorySection)[_buildDevicesHistorySection]()) == null ? void 0 : _babelHelpers$classPr3.renderTo(contentNode);
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _buildEventLogSection)[_buildEventLogSection]()) == null ? void 0 : _babelHelpers$classPr4.renderTo(contentNode);
	    if (isBitrix24) {
	      var _babelHelpers$classPr5, _babelHelpers$classPr6;
	      (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _buildAccessIPSection)[_buildAccessIPSection]()) == null ? void 0 : _babelHelpers$classPr5.renderTo(contentNode);
	      (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _buildBlackListSection)[_buildBlackListSection]()) == null ? void 0 : _babelHelpers$classPr6.renderTo(contentNode);
	    }
	  }
	}
	function _buildOTPSection2() {
	  if (!this.hasValue('sectionOtp')) {
	    return;
	  }
	  const otpSection = new ui_section.Section(this.getValue('sectionOtp'));
	  const section = new ui_formElements_field.SettingsSection({
	    section: otpSection,
	    parent: this
	  });
	  const descriptionRow = new ui_section.Row({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getOTPDescription)[_getOTPDescription]().getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: section
	  });
	  if (this.hasValue('SECURITY_OTP') && this.hasValue('SEND_OTP_PUSH')) {
	    const securityOtpCheckerRow = new ui_section.Row({
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().render(),
	      separator: babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().isChecked() ? '' : 'bottom',
	      className: babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().isChecked() ? '' : '--block'
	    });
	    new ui_formElements_field.SettingsRow({
	      row: securityOtpCheckerRow,
	      parent: section
	    });
	    const securityOtpPeriodSelectorRow = new ui_section.Row({
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getOTPPeriodSelector)[_getOTPPeriodSelector]().render(),
	      isHidden: !babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().isChecked()
	    });
	    new ui_formElements_field.SettingsRow({
	      row: securityOtpPeriodSelectorRow,
	      parent: section
	    });
	    const switcherWrapper = main_core.Tag.render(_t$m || (_t$m = _$m`
				<div class="settings-switcher-wrapper">
					<div class="settings-security-message-switcher"/>
				</div>
			`));
	    new ui_formElements_view.SingleChecker({
	      switcher: new ui_switcher.Switcher({
	        node: switcherWrapper.querySelector('.settings-security-message-switcher'),
	        inputName: 'SEND_OTP_PUSH',
	        checked: this.getValue('SEND_OTP_PUSH'),
	        size: ui_switcher.SwitcherSize.small
	      })
	    });
	    const securityOtpMessageChatCheckerRow = new ui_section.Row({
	      content: switcherWrapper,
	      isHidden: !babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().isChecked()
	    });
	    switcherWrapper.append(main_core.Tag.render(_t2$b || (_t2$b = _$m`<span class="settings-switcher-title">${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_OTP_SWITCHING_MESSAGE_CHAT')));
	    new ui_formElements_field.SettingsRow({
	      row: securityOtpMessageChatCheckerRow,
	      parent: section
	    });
	    main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().switcher, 'toggled', () => {
	      if (this.getValue('SECURITY_IS_USER_OTP_ACTIVE') !== true && babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().isChecked()) {
	        babelHelpers.classPrivateFieldLooseBase(this, _getOTPPopup)[_getOTPPopup]().show();
	        babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().cancel();
	        babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().switcher.check(false);
	        return;
	      }
	      if (this.hasValue('SECURITY_OTP_ENABLED') && this.getValue('SECURITY_OTP_ENABLED')) {
	        var _this$getAnalytic;
	        (_this$getAnalytic = this.getAnalytic()) == null ? void 0 : _this$getAnalytic.addEventToggle2fa(babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().isChecked());
	      }
	      if (babelHelpers.classPrivateFieldLooseBase(this, _getOTPChecker)[_getOTPChecker]().isChecked()) {
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
	  if (babelHelpers.classPrivateFieldLooseBase(this, _otpChecker)[_otpChecker] instanceof ui_formElements_view.Checker) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _otpChecker)[_otpChecker];
	  }
	  if (this.hasValue('fieldSecurityOtp')) {
	    babelHelpers.classPrivateFieldLooseBase(this, _otpChecker)[_otpChecker] = new ui_formElements_view.Checker({
	      inputName: this.getValue('fieldSecurityOtp').inputName,
	      checked: this.getValue('fieldSecurityOtp').checked,
	      title: this.getValue('fieldSecurityOtp').title,
	      isEnable: this.getValue('fieldSecurityOtp').isEnable,
	      hideSeparator: true,
	      alignCenter: true,
	      noMarginBottom: true
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _otpChecker)[_otpChecker].renderLockElement = () => {
	    return null;
	  };
	  return babelHelpers.classPrivateFieldLooseBase(this, _otpChecker)[_otpChecker];
	}
	function _getOTPPopup2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _otpPopup)[_otpPopup] instanceof main_popup.Popup) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _otpPopup)[_otpPopup];
	  }
	  const popupDescription = main_core.Tag.render(_t3$8 || (_t3$8 = _$m`
			<div class="intranet-settings__security_popup_info">
				${0}
			</div>	
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_POPUP_OTP_ENABLE'));
	  const popupButton = new BX.UI.Button({
	    text: main_core.Loc.getMessage('INTRANET_SETTINGS_POPUP_OTP_ENABLE_BUTTON'),
	    color: BX.UI.Button.Color.PRIMARY,
	    events: {
	      click: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getOTPPopup)[_getOTPPopup]().close();
	        BX.SidePanel.Instance.open(this.getValue('SECURITY_OTP_PATH'));
	      }
	    }
	  });
	  const popupContent = main_core.Tag.render(_t4$1 || (_t4$1 = _$m`
			<div class="intranet-settings__security_popup_container">
				${0}
				<div class="ui-btn-container ui-btn-container-center">
					${0}
				</div>			
			</div>
		`), popupDescription, popupButton.getContainer());
	  babelHelpers.classPrivateFieldLooseBase(this, _otpPopup)[_otpPopup] = new main_popup.Popup({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _otpChecker)[_otpChecker].getInputNode(),
	    content: popupContent,
	    autoHide: true,
	    width: 337,
	    angle: {
	      offset: 200 - 15
	    },
	    offsetLeft: babelHelpers.classPrivateFieldLooseBase(this, _otpChecker)[_otpChecker].getInputNode().offsetWidth - 200 + 15,
	    closeByEsc: true,
	    borderRadius: 18
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _otpPopup)[_otpPopup];
	}
	function _getOTPPeriodSelector2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _otpSelector)[_otpSelector] instanceof ui_formElements_view.Selector) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _otpSelector)[_otpSelector];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _otpSelector)[_otpSelector] = new ui_formElements_view.Selector({
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_OTP_SWITCHING_PERIOD'),
	    name: 'SECURITY_OTP_DAYS',
	    items: this.getValue('SECURITY_OTP_DAYS').ITEMS,
	    current: this.getValue('SECURITY_OTP_DAYS').CURRENT
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _otpSelector)[_otpSelector];
	}
	function _getOTPDescription2() {
	  return new BX.UI.Alert({
	    text: babelHelpers.classPrivateFieldLooseBase(this, _getOTPDescriptionText)[_getOTPDescriptionText](),
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	}
	function _getOTPDescriptionText2() {
	  return `
		${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_FIRST')}
		</br></br>
		<span class="settings-section-description-focus-text --security-info">
			${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_SECOND')}
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=17728602')">
				${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		</span>`;
	}
	function _buildAccessIPSection2() {
	  if (!this.hasValue('sectionAccessIp')) {
	    return;
	  }
	  const accessIpSection = new ui_section.Section(this.getValue('sectionAccessIp'));
	  const section = new ui_formElements_field.SettingsSection({
	    section: accessIpSection,
	    parent: this
	  });
	  const descriptionRow = new ui_section.Row({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getIpAccessDescription)[_getIpAccessDescription]().getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: section
	  });
	  let fieldsCount = 0;
	  if (this.hasValue('IP_ACCESS_RIGHTS')) {
	    for (const ipUsersList of this.getValue('IP_ACCESS_RIGHTS')) {
	      fieldsCount++;
	      new ui_formElements_field.SettingsRow({
	        parent: section,
	        child: babelHelpers.classPrivateFieldLooseBase(this, _getUserSelectorRow)[_getUserSelectorRow](ipUsersList)
	      });
	      new ui_formElements_field.SettingsRow({
	        parent: section,
	        child: babelHelpers.classPrivateFieldLooseBase(this, _getAccessIpRow)[_getAccessIpRow](ipUsersList)
	      });
	    }
	  }
	  if (fieldsCount === 0) {
	    fieldsCount++;
	    new ui_formElements_field.SettingsRow({
	      parent: section,
	      child: babelHelpers.classPrivateFieldLooseBase(this, _getEmptyUserSelectorRow)[_getEmptyUserSelectorRow](fieldsCount)
	    });
	    new ui_formElements_field.SettingsRow({
	      parent: section,
	      child: babelHelpers.classPrivateFieldLooseBase(this, _getEmptyAccessIpRow)[_getEmptyAccessIpRow](fieldsCount)
	    });
	  }
	  const onclickAddField = () => {
	    if (this.getValue('IP_ACCESS_RIGHTS_ENABLED')) {
	      fieldsCount++;
	      const emptyUserSelectorRow = new ui_section.Row({
	        content: babelHelpers.classPrivateFieldLooseBase(this, _getEmptyUserSelectorRow)[_getEmptyUserSelectorRow](fieldsCount).render()
	      });
	      main_core.Dom.insertBefore(emptyUserSelectorRow.render(), additionalUsersAccessIpButton.parentElement);
	      const emptyAccessIpRow = new ui_section.Row({
	        content: babelHelpers.classPrivateFieldLooseBase(this, _getEmptyAccessIpRow)[_getEmptyAccessIpRow](fieldsCount).render()
	      });
	      main_core.Dom.insertBefore(emptyAccessIpRow.render(), additionalUsersAccessIpButton.parentElement);
	    } else {
	      BX.UI.InfoHelper.show('limit_admin_ip');
	    }
	  };
	  const additionalUsersAccessIpButton = main_core.Tag.render(_t5 || (_t5 = _$m`
			<div class="ui-text-right">
				<a class="ui-section__link" href="javascript:void(0)" onclick="${0}">
					${0}
				</a>
			</div>
		`), onclickAddField, main_core.Loc.getMessage('INTRANET_SETTINGS_ADDITIONAL_USER_ACCESS_IP'));
	  new ui_formElements_field.SettingsRow({
	    row: new ui_section.Row({
	      content: additionalUsersAccessIpButton
	    }),
	    parent: section
	  });
	  return section;
	}
	function _getEmptyUserSelectorRow2(fieldNumber) {
	  const userSelector = new ui_formElements_view.UserSelector({
	    inputName: `SECURITY_IP_ACCESS_${fieldNumber}_USERS[]`,
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_ACCESS_IP'),
	    enableDepartments: true,
	    encodeValue: value => {
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
	  const inputName = `SECURITY_IP_ACCESS_${fieldNumber}_IP`;
	  const accessIp = new ui_formElements_view.TextInput({
	    inputName,
	    label: (_this$getValue = this.getValue('IP_ACCESS_RIGHTS_ENABLED_LABEL')) != null ? _this$getValue : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_ACCEPTED_IP'),
	    isEnable: this.getValue('IP_ACCESS_RIGHTS_ENABLED'),
	    helpMessageProvider: this.helpMessageProviderFactory(main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_PRO'))
	  });
	  return new ui_formElements_field.SettingsField({
	    fieldView: accessIp
	  });
	}
	function _getUserSelectorRow2(ipUsersList) {
	  var _this$getValue2;
	  const userSelector = ui_formElements_view.FieldFactory.createUserSelector({
	    inputName: `SECURITY_IP_ACCESS_${ipUsersList.fieldNumber}_USERS[]`,
	    label: (_this$getValue2 = this.getValue('IP_ACCESS_RIGHTS_ENABLED_LABEL')) != null ? _this$getValue2 : main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_USER_ACCESS_IP'),
	    values: Object.values(ipUsersList.users),
	    enableDepartments: true
	  });
	  return new ui_formElements_field.SettingsField({
	    fieldView: userSelector
	  });
	}
	function _getAccessIpRow2(ipUsersList) {
	  const inputName = `SECURITY_IP_ACCESS_${ipUsersList.fieldNumber}_IP`;
	  const accessIp = new ui_formElements_view.TextInput({
	    inputName,
	    label: main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_ACCEPTED_IP'),
	    value: ipUsersList.ip
	  });
	  return new ui_formElements_field.SettingsField({
	    fieldView: accessIp
	  });
	}
	function _getIpAccessDescription2() {
	  return new BX.UI.Alert({
	    text: `
				${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_IP_ACCESS', {
      '#ARTICLE_CODE#': 'redirect=detail&code=17300230'
    })}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=17300230')">
					${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
			`,
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	}
	function _buildPasswordRecoverySection2() {
	  return new ui_section.Section({
	    title: main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_PASSWORD_RECOVERY'),
	    titleIconClasses: 'ui-icon-set',
	    isOpen: false,
	    canCollapse: false
	  });
	}
	function _buildDevicesHistorySection2() {
	  if (!this.hasValue('sectionHistory')) {
	    return;
	  }
	  const devicesHistorySection = new ui_section.Section(this.getValue('sectionHistory'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    section: devicesHistorySection,
	    parent: this
	  });
	  const devicesHistoryDescription = new BX.UI.Alert({
	    text: `
				${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_DEVICE_HISTORY')}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=16623484')">
					${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
			`,
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	  const descriptionRow = new ui_section.Row({
	    content: devicesHistoryDescription.getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: settingsSection
	  });
	  if (this.hasValue('DEVICE_HISTORY_SETTINGS')) {
	    const messageNode = main_core.Tag.render(_t6 || (_t6 = _$m`<span>${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_ENT', {
	      '#TARIFF#': 'ent250'
	    }));
	    const cleanupDaysField = new ui_formElements_view.Selector({
	      label: this.getValue('DEVICE_HISTORY_SETTINGS').label,
	      name: this.getValue('DEVICE_HISTORY_SETTINGS').name,
	      items: this.getValue('DEVICE_HISTORY_SETTINGS').values,
	      current: this.getValue('DEVICE_HISTORY_SETTINGS').current,
	      isEnable: this.getValue('DEVICE_HISTORY_SETTINGS').isEnable,
	      bannerCode: 'limit_office_login_history',
	      helpMessageProvider: this.helpMessageProviderFactory(messageNode)
	    });
	    if (!this.getValue('DEVICE_HISTORY_SETTINGS').isEnable) {
	      main_core.Event.bind(cleanupDaysField.getInputNode(), 'click', () => {
	        var _this$getAnalytic2;
	        (_this$getAnalytic2 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic2.addEventOpenHint(this.getValue('DEVICE_HISTORY_SETTINGS').name);
	      });
	      main_core.Event.bind(messageNode.querySelector('a'), 'click', () => {
	        var _this$getAnalytic3;
	        return (_this$getAnalytic3 = this.getAnalytic()) == null ? void 0 : _this$getAnalytic3.addEventOpenTariffSelector(this.getValue('DEVICE_HISTORY_SETTINGS').name);
	      });
	    }
	    SecurityPage.addToSectionHelper(cleanupDaysField, settingsSection);
	  }
	  const goToUserListButton = main_core.Tag.render(_t7 || (_t7 = _$m`
			<div class="ui-text-right">
				<a class="ui-section__link" href="/company/" target="_blank">
					${0}
				</a>
			</div>
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_GO_TO_USER_LIST_LINK'));
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
	  const eventLogSection = new ui_section.Section(this.getValue('sectionEventLog'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    section: eventLogSection,
	    parent: this
	  });
	  const eventLogDescription = new BX.UI.Alert({
	    text: `
				${main_core.Loc.getMessage('INTRANET_SETTINGS_SECTION_SECURITY_DESCRIPTION_EVENT_LOG')}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=17296266')">
					${main_core.Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
			`,
	    inline: true,
	    size: BX.UI.Alert.Size.SMALL,
	    color: BX.UI.Alert.Color.PRIMARY,
	    animated: true
	  });
	  const descriptionRow = new ui_section.Row({
	    content: eventLogDescription.getContainer()
	  });
	  new ui_formElements_field.SettingsRow({
	    row: descriptionRow,
	    parent: settingsSection
	  });
	  const goToUserListButton = this.hasValue('EVENT_LOG') ? main_core.Tag.render(_t8 || (_t8 = _$m`
				<div class="ui-text-right">
					<a class="ui-section__link" href="${0}" target="_blank">
						${0}
					</a>
				</div>
			`), this.getValue('EVENT_LOG'), main_core.Loc.getMessage('INTRANET_SETTINGS_GO_TO_EVENT_LOG_LINK')) : main_core.Tag.render(_t9 || (_t9 = _$m`
				<div class="ui-text-right">
					<a class="ui-section__link" href="javascript:void(0)" onclick="BX.UI.InfoHelper.show('limit_office_login_log')">
						${0}
					</a>
				</div>
			`), main_core.Loc.getMessage('INTRANET_SETTINGS_GO_TO_EVENT_LOG_LINK'));
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
	  let params = this.getValue('sectionBlackList');
	  params['singleLink'] = {
	    href: '/settings/configs/mail_blacklist.php'
	  };
	  return new ui_section.Section(params);
	}
	function _buildDataLeakProtectionSection2() {
	  if (!this.hasValue('sectionDataLeakProtection')) {
	    return;
	  }
	  const mobileAppSection = new ui_section.Section(this.getValue('sectionDataLeakProtection'));
	  const settingsSection = new ui_formElements_field.SettingsSection({
	    section: mobileAppSection,
	    parent: this
	  });
	  if (this.hasValue('switcherDisableScreenshot')) {
	    const disableCopyScreenshotChecker = new ui_formElements_view.Checker(this.getValue('switcherDisableScreenshot'));
	    const disableCopyScreenshotSelector = ui_formElements_view.FieldFactory.createUserSelector({
	      ...this.getValue('selectorDisableScreenshot'),
	      enableDepartments: true
	    });
	    SecurityPage.addToSectionCheckerHelper(disableCopyScreenshotChecker, [disableCopyScreenshotSelector], settingsSection);
	  }
	  if (this.hasValue('switcherDisableCopy')) {
	    const disableCopyCopyChecker = new ui_formElements_view.Checker(this.getValue('switcherDisableCopy'));
	    const disableCopyCopySelector = ui_formElements_view.FieldFactory.createUserSelector({
	      ...this.getValue('selectorDisableCopy'),
	      enableDepartments: true
	    });
	    SecurityPage.addToSectionCheckerHelper(disableCopyCopyChecker, [disableCopyCopySelector], settingsSection);
	  }
	  if (this.hasValue('isAutoDeleteMessagesEnabled')) {
	    const allowAutoDeleteField = new ui_formElements_view.Checker(this.getValue('isAutoDeleteMessagesEnabled'));
	    SecurityPage.addToSectionHelper(allowAutoDeleteField, settingsSection);
	  }
	  if (this.hasValue('isWaterMarksEnabled')) {
	    const allowAutoDeleteField = new ui_formElements_view.Checker(this.getValue('isWaterMarksEnabled'));
	    SecurityPage.addToSectionHelper(allowAutoDeleteField, settingsSection);
	  }
	  return settingsSection;
	}

	let _$n = t => t,
	  _t$n,
	  _t2$c,
	  _t3$9,
	  _t4$2,
	  _t5$1,
	  _t6$1,
	  _t7$1,
	  _t8$1,
	  _t9$1,
	  _t10,
	  _t11,
	  _t12,
	  _t13,
	  _t14,
	  _t15,
	  _t16,
	  _t17,
	  _t18,
	  _t19,
	  _t20,
	  _t21,
	  _t22;
	var _urlCreate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urlCreate");
	var _urlEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urlEdit");
	var _urlPublic = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urlPublic");
	var _urlPartners = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urlPartners");
	var _urlImport = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urlImport");
	var _urlExport = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urlExport");
	var _previewImg = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("previewImg");
	var _title$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("title");
	var _feedbackParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("feedbackParams");
	var _buttonEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonEdit");
	var _buttonPartners = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonPartners");
	var _buttonMarket = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonMarket");
	var _buttonWithdraw = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonWithdraw");
	var _buttonPublish = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonPublish");
	var _mainTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mainTemplate");
	var _secondaryTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("secondaryTemplate");
	var _buttonMainSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonMainSettings");
	var _buttonSecondarySettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonSecondarySettings");
	var _importPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("importPopup");
	var _exportPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("exportPopup");
	var _popupShare = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popupShare");
	var _popupWithdraw = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popupWithdraw");
	var _isPageExists = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPageExists");
	var _isPublished = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPublished");
	var _canEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("canEdit");
	var _getMainTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMainTemplate");
	var _getSecondaryTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSecondaryTemplate");
	var _getButtonMainSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonMainSettings");
	var _getButtonSecondarySettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonSecondarySettings");
	var _showImportPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showImportPopup");
	var _showExportPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showExportPopup");
	var _showImportSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showImportSlider");
	var _showExportSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showExportSlider");
	var _showSharePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showSharePopup");
	var _showWithdrawPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showWithdrawPopup");
	var _getButtonEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonEdit");
	var _getButtonPublish = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonPublish");
	var _getButtonWithdraw = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonWithdraw");
	var _getButtonPartners = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonPartners");
	var _getButtonCreate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonCreate");
	var _bindButtonEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindButtonEvents");
	var _bindSliderCloseEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindSliderCloseEvent");
	class MainpagePage extends ui_formElements_field.BaseSettingsPage {
	  constructor() {
	    super();
	    Object.defineProperty(this, _bindSliderCloseEvent, {
	      value: _bindSliderCloseEvent2
	    });
	    Object.defineProperty(this, _bindButtonEvents, {
	      value: _bindButtonEvents2
	    });
	    Object.defineProperty(this, _getButtonCreate, {
	      value: _getButtonCreate2
	    });
	    Object.defineProperty(this, _getButtonPartners, {
	      value: _getButtonPartners2
	    });
	    Object.defineProperty(this, _getButtonWithdraw, {
	      value: _getButtonWithdraw2
	    });
	    Object.defineProperty(this, _getButtonPublish, {
	      value: _getButtonPublish2
	    });
	    Object.defineProperty(this, _getButtonEdit, {
	      value: _getButtonEdit2
	    });
	    Object.defineProperty(this, _showWithdrawPopup, {
	      value: _showWithdrawPopup2
	    });
	    Object.defineProperty(this, _showSharePopup, {
	      value: _showSharePopup2
	    });
	    Object.defineProperty(this, _showExportSlider, {
	      value: _showExportSlider2
	    });
	    Object.defineProperty(this, _showImportSlider, {
	      value: _showImportSlider2
	    });
	    Object.defineProperty(this, _showExportPopup, {
	      value: _showExportPopup2
	    });
	    Object.defineProperty(this, _showImportPopup, {
	      value: _showImportPopup2
	    });
	    Object.defineProperty(this, _getButtonSecondarySettings, {
	      value: _getButtonSecondarySettings2
	    });
	    Object.defineProperty(this, _getButtonMainSettings, {
	      value: _getButtonMainSettings2
	    });
	    Object.defineProperty(this, _getSecondaryTemplate, {
	      value: _getSecondaryTemplate2
	    });
	    Object.defineProperty(this, _getMainTemplate, {
	      value: _getMainTemplate2
	    });
	    this.titlePage = '';
	    this.descriptionPage = '';
	    Object.defineProperty(this, _urlCreate, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _urlEdit, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _urlPublic, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _urlPartners, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _urlImport, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _urlExport, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _previewImg, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _title$2, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _feedbackParams, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _buttonEdit, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _buttonPartners, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _buttonMarket, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _buttonWithdraw, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _buttonPublish, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _mainTemplate, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _secondaryTemplate, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _buttonMainSettings, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _buttonSecondarySettings, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _importPopup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _exportPopup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _popupShare, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _popupWithdraw, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isPageExists, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isPublished, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _canEdit, {
	      writable: true,
	      value: true
	    });
	    this.titlePage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_MAINPAGE');
	    this.descriptionPage = main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_DESCRIPTION_PAGE_MAINPAGE');
	  }
	  getType() {
	    return 'mainpage';
	  }
	  appendSections(contentNode) {
	    var _options$isPageExists, _options$isPublished, _options$title, _options$canEdit;
	    const options = this.getValue('main-page');
	    babelHelpers.classPrivateFieldLooseBase(this, _urlCreate)[_urlCreate] = options.urlCreate || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _urlEdit)[_urlEdit] = options.urlEdit || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _urlPublic)[_urlPublic] = options.urlPublic || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _urlPartners)[_urlPartners] = options.urlPartners || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _urlImport)[_urlImport] = options.urlImport || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _urlExport)[_urlExport] = options.urlExport || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _previewImg)[_previewImg] = options.previewImg || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _feedbackParams)[_feedbackParams] = options.feedbackParams || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _isPageExists)[_isPageExists] = (_options$isPageExists = options.isPageExists) != null ? _options$isPageExists : false;
	    babelHelpers.classPrivateFieldLooseBase(this, _isPublished)[_isPublished] = (_options$isPublished = options.isPublished) != null ? _options$isPublished : false;
	    babelHelpers.classPrivateFieldLooseBase(this, _title$2)[_title$2] = (_options$title = options.title) != null ? _options$title : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] = (_options$canEdit = options.canEdit) != null ? _options$canEdit : false;
	    const section = new ui_formElements_field.SettingsSection({
	      parent: this,
	      section: {
	        canCollapse: false,
	        isOpen: true
	      }
	    });
	    const secondarySection = new ui_formElements_field.SettingsSection({
	      parent: this,
	      section: {
	        canCollapse: false,
	        isOpen: true
	      }
	    });
	    const content = main_core.Tag.render(_t$n || (_t$n = _$n`<div>		
			${0}		
		</div>`), babelHelpers.classPrivateFieldLooseBase(this, _getMainTemplate)[_getMainTemplate]());
	    section.getSectionView().append(new ui_section.Row({
	      content: content
	    }).render());
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isPageExists)[_isPageExists]) {
	      const secondaryContent = main_core.Tag.render(_t2$c || (_t2$c = _$n`<div>
				${0}			
			</div>`), babelHelpers.classPrivateFieldLooseBase(this, _getSecondaryTemplate)[_getSecondaryTemplate]());
	      secondarySection.getSectionView().append(new ui_section.Row({
	        content: secondaryContent
	      }).render());
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _bindButtonEvents)[_bindButtonEvents]();
	    babelHelpers.classPrivateFieldLooseBase(this, _bindSliderCloseEvent)[_bindSliderCloseEvent]();
	    secondarySection.renderTo(contentNode);
	    section.renderTo(contentNode);
	  }
	  getInfoTemplate() {
	    this.infoTemplate = main_core.Tag.render(_t3$9 || (_t3$9 = _$n`
			<div class="intranet-settings__main-page-info">
				<div class="intranet-settings__main-page-info-title">
					${0}
				</div>
				<div class="intranet-settings__main-page-info-subtitle">
					${0}
					<div class="ui-icon-set --help intranet-settings__main-page-info-help"></div>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_TITLE'), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUBTITLE'));
	    main_core.Event.bind(this.infoTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseenter', event => {
	      const width = this.infoTemplate.querySelector('.intranet-settings__main-page-info-help').offsetWidth;
	      this.warningHintPopup = new main_popup.Popup({
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
	      this.warningHintPopup.show();
	    });
	    main_core.Event.bind(this.infoTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseleave', () => {
	      if (this.warningHintPopup) {
	        setTimeout(() => {
	          this.warningHintPopup.destroy();
	          this.warningHintPopup = null;
	        }, 300);
	      }
	    });
	    return this.infoTemplate;
	  }
	  getInfoSuccessTemplate() {
	    this.infoSuccessTemplate = main_core.Tag.render(_t4$2 || (_t4$2 = _$n`
			<div class="intranet-settings__main-page-info --success">
				<div class="intranet-settings__main-page-info-title">
					${0}				
				</div>
				<div class="intranet-settings__main-page-info-subtitle">
					${0}
					<div class="ui-icon-set --help intranet-settings__main-page-info-help"></div>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUCCESS_TITLE'), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_INFO_SUCCESS_SUBTITLE'));
	    main_core.Event.bind(this.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseenter', event => {
	      const width = this.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help').offsetWidth;
	      this.successHintPopup = new main_popup.Popup({
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
	      this.successHintPopup.show();
	    });
	    main_core.Event.bind(this.infoSuccessTemplate.querySelector('.intranet-settings__main-page-info-help'), 'mouseleave', () => {
	      if (this.successHintPopup) {
	        setTimeout(() => {
	          this.successHintPopup.destroy();
	          this.successHintPopup = null;
	        }, 300);
	      }
	    });
	    return this.infoSuccessTemplate;
	  }
	  renderLockElement() {
	    return main_core.Tag.render(_t5$1 || (_t5$1 = _$n`<span class="intranet-settings-mp-icon ui-icon-set --lock"></span>`));
	  }
	}
	function _getMainTemplate2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _mainTemplate)[_mainTemplate]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _mainTemplate)[_mainTemplate] = main_core.Tag.render(_t6$1 || (_t6$1 = _$n`
				<div class="intranet-settings__main-page-template">
					<div class="intranet-settings__main-page-icon-box">
						<div class="intranet-settings__main-page-icon"></div>
					</div>
					<div class="intranet-settings__main-page-content">
						<ul class="intranet-settings__main-page-list">
							<li class="intranet-settings__main-page-list-item">
								<div class="ui-icon-set --check intranet-settings__main-page-list-icon"></div>
								<div class="intranet-settings__main-page-list-name">
									${0}
								</div>																																
							</li>
							<li class="intranet-settings__main-page-list-item">
								<div class="ui-icon-set --check intranet-settings__main-page-list-icon"></div>
								<div class="intranet-settings__main-page-list-name">
									${0}
								</div>								
							</li>
							<li class="intranet-settings__main-page-list-item">
								<div class="ui-icon-set --check intranet-settings__main-page-list-icon"></div>
								<div class="intranet-settings__main-page-list-name">
									${0}
								</div>
							</li>
						</ul>
						<div class="intranet-settings__main-page-button-box">
							${0}
							<div class="intranet-settings__main-page-button-box-right">
								${0}
								${0}
							</div>
						</div>
					</div>
				</div>
			`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_1'), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_2'), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_LIST_ITEM_3'), babelHelpers.classPrivateFieldLooseBase(this, _getButtonCreate)[_getButtonCreate](), babelHelpers.classPrivateFieldLooseBase(this, _getButtonPartners)[_getButtonPartners](), babelHelpers.classPrivateFieldLooseBase(this, _getButtonMainSettings)[_getButtonMainSettings]());
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _mainTemplate)[_mainTemplate];
	}
	function _getSecondaryTemplate2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _secondaryTemplate)[_secondaryTemplate]) {
	    var _babelHelpers$classPr;
	    const previewImg = babelHelpers.classPrivateFieldLooseBase(this, _previewImg)[_previewImg] ? main_core.Tag.render(_t7$1 || (_t7$1 = _$n`<img 
						src="${0}"
						class="intranet-settings__main-page-preview" 
					/>`), babelHelpers.classPrivateFieldLooseBase(this, _previewImg)[_previewImg]) : '';
	    babelHelpers.classPrivateFieldLooseBase(this, _secondaryTemplate)[_secondaryTemplate] = main_core.Tag.render(_t8$1 || (_t8$1 = _$n`
				<div class="intranet-settings__main-page-template --secondary-template">
					<div class="intranet-settings__main-page-preview-box">
						${0}
					</div>
					<div class="intranet-settings__main-page-content">
						<div class="intranet-settings__main-page-title">
							${0}
						</div>
						<div class="intranet-settings__main-page-info-template">
							${0}
						</div>					
						<div class="intranet-settings__main-page-button-box">
							${0}
							<div class="intranet-settings__main-page-button-box-right">
								${0}
								${0}
							</div>
						</div>
					</div>
				</div>			
			`), previewImg, (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _title$2)[_title$2]) != null ? _babelHelpers$classPr : '', babelHelpers.classPrivateFieldLooseBase(this, _isPublished)[_isPublished] ? this.getInfoSuccessTemplate() : this.getInfoTemplate(), babelHelpers.classPrivateFieldLooseBase(this, _getButtonEdit)[_getButtonEdit](), babelHelpers.classPrivateFieldLooseBase(this, _isPublished)[_isPublished] ? babelHelpers.classPrivateFieldLooseBase(this, _getButtonWithdraw)[_getButtonWithdraw]() : babelHelpers.classPrivateFieldLooseBase(this, _getButtonPublish)[_getButtonPublish](), babelHelpers.classPrivateFieldLooseBase(this, _getButtonSecondarySettings)[_getButtonSecondarySettings]());
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _secondaryTemplate)[_secondaryTemplate];
	}
	function _getButtonMainSettings2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _buttonMainSettings)[_buttonMainSettings]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonMainSettings)[_buttonMainSettings] = main_core.Tag.render(_t9$1 || (_t9$1 = _$n`
				<button class="intranet-settings-btn-settings">
					<div class="ui-icon-set --more"></div>
				</button>
			`));
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _buttonMainSettings)[_buttonMainSettings];
	}
	function _getButtonSecondarySettings2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _buttonSecondarySettings)[_buttonSecondarySettings]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonSecondarySettings)[_buttonSecondarySettings] = main_core.Tag.render(_t10 || (_t10 = _$n`
			<button class="intranet-settings-btn-settings">
				<div class="ui-icon-set --more"></div>
			</button>`));
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _buttonSecondarySettings)[_buttonSecondarySettings];
	}
	function _showImportPopup2() {
	  var _babelHelpers$classPr2;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _importPopup)[_importPopup]) {
	    const htmlContent = babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? main_core.Tag.render(_t11 || (_t11 = _$n`<span>${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP')) : main_core.Tag.render(_t12 || (_t12 = _$n`<span class="intranet-settings-mp-popup-item">${0} ${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP'), this.renderLockElement());
	    babelHelpers.classPrivateFieldLooseBase(this, _importPopup)[_importPopup] = new main_popup.Menu({
	      angle: true,
	      animation: 'fading-slide',
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _buttonMainSettings)[_buttonMainSettings],
	      className: babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? '' : '--disabled',
	      items: [{
	        id: 'importPopup',
	        html: htmlContent,
	        onclick: babelHelpers.classPrivateFieldLooseBase(this, _showImportSlider)[_showImportSlider].bind(this)
	      }],
	      offsetLeft: 20,
	      events: {
	        onPopupClose: () => {},
	        onPopupShow: () => {}
	      }
	    });
	  }
	  (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _importPopup)[_importPopup]) == null ? void 0 : _babelHelpers$classPr2.show();
	}
	function _showExportPopup2() {
	  var _babelHelpers$classPr3;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _exportPopup)[_exportPopup]) {
	    const htmlContent = babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? main_core.Tag.render(_t13 || (_t13 = _$n`<span>${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_EXPORT_POPUP')) : main_core.Tag.render(_t14 || (_t14 = _$n`<span class="intranet-settings-mp-popup-item --disabled">${0} ${0}</span>`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_EXPORT_POPUP'), this.renderLockElement());
	    babelHelpers.classPrivateFieldLooseBase(this, _exportPopup)[_exportPopup] = new main_popup.Menu({
	      angle: true,
	      animation: 'fading-slide',
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _buttonSecondarySettings)[_buttonSecondarySettings],
	      className: babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? '' : '--disabled',
	      items: [{
	        id: 'exportPopup',
	        html: htmlContent,
	        onclick: babelHelpers.classPrivateFieldLooseBase(this, _showExportSlider)[_showExportSlider].bind(this)
	      }],
	      offsetLeft: 20,
	      events: {
	        onPopupClose: () => {},
	        onPopupShow: () => {}
	      }
	    });
	  }
	  (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _exportPopup)[_exportPopup]) == null ? void 0 : _babelHelpers$classPr3.show();
	}
	function _showImportSlider2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit]) {
	    BX.UI.InfoHelper.show("limit_office_vibe");
	    return;
	  }
	  if (typeof BX.SidePanel === 'undefined') {
	    return;
	  }
	  if (typeof BX.SidePanel !== 'undefined' && babelHelpers.classPrivateFieldLooseBase(this, _urlImport)[_urlImport]) {
	    const onOK = () => {
	      BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _urlImport)[_urlImport], {
	        width: 491,
	        allowChangeHistory: false,
	        cacheable: false,
	        data: {
	          rightBoundary: 0
	        }
	      });
	    };
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isPageExists)[_isPageExists]) {
	      onOK();
	      return;
	    }
	    BX.Runtime.loadExtension('ui.dialogs.messagebox').then(() => {
	      const messageBox = new BX.UI.Dialogs.MessageBox({
	        message: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_MESSAGE'),
	        title: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_TITLE'),
	        buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	        okCaption: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_OK_BUTTON'),
	        cancelCaption: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_IMPORT_POPUP_MESSAGEBOX_CANCEL_BUTTON'),
	        onOk: () => {
	          onOK();
	          return true;
	        },
	        onCancel: () => {
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
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit]) {
	    BX.UI.InfoHelper.show("limit_office_vibe");
	    return;
	  }
	  if (typeof BX.SidePanel === 'undefined') {
	    return;
	  }
	  if (typeof BX.SidePanel !== 'undefined' && babelHelpers.classPrivateFieldLooseBase(this, _urlExport)[_urlExport]) {
	    BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _urlExport)[_urlExport], {
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
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _popupShare)[_popupShare]) {
	    var _babelHelpers$classPr4;
	    babelHelpers.classPrivateFieldLooseBase(this, _popupShare)[_popupShare] = new main_popup.Popup({
	      titleBar: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_TITLE_MSGVER_1'),
	      content: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_CONTENT'),
	      width: 350,
	      closeIcon: true,
	      closeByEsc: true,
	      animation: 'fading-slide',
	      buttons: [new ui_buttons.Button({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_SHARE_POPUP_BTN_CONFIRM'),
	        color: ui_buttons.Button.Color.PRIMARY,
	        onclick: () => {
	          const newTemplate = this.getInfoSuccessTemplate();
	          const wrapper = babelHelpers.classPrivateFieldLooseBase(this, _secondaryTemplate)[_secondaryTemplate].querySelector('.intranet-settings__main-page-info-template');
	          const innerWrapper = wrapper.querySelector('.intranet-settings__main-page-info:not(.--success)');
	          main_core.Dom.replace(innerWrapper, newTemplate);
	          main_core.ajax.runAction('intranet.mainpage.publish').then(() => {
	            this.emit('publish');
	            if (babelHelpers.classPrivateFieldLooseBase(this, _urlPublic)[_urlPublic]) {
	              babelHelpers.classPrivateFieldLooseBase(this, _isPublished)[_isPublished] = true;
	            }
	          });
	          babelHelpers.classPrivateFieldLooseBase(this, _popupShare)[_popupShare].close();
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
	        onclick: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _popupShare)[_popupShare].close();
	        }
	      })],
	      events: {
	        onClose: () => {}
	      }
	    });
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _popupShare)[_popupShare]) == null ? void 0 : _babelHelpers$classPr4.show();
	  } else {
	    var _babelHelpers$classPr5;
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _popupShare)[_popupShare]) == null ? void 0 : _babelHelpers$classPr5.show();
	  }
	}
	function _showWithdrawPopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _popupWithdraw)[_popupWithdraw]) {
	    var _babelHelpers$classPr6;
	    const title = babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_TITLE') : main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_TITLE_FREE');
	    const content = babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_CONTENT') : main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_CONTENT_FREE');
	    const okText = babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_BTN_CONFIRM') : main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_WITHDRAW_POPUP_BTN_CONFIRM_FREE');
	    babelHelpers.classPrivateFieldLooseBase(this, _popupWithdraw)[_popupWithdraw] = new main_popup.Popup({
	      titleBar: title,
	      content: content,
	      width: 350,
	      closeIcon: true,
	      closeByEsc: true,
	      animation: 'fading-slide',
	      buttons: [new ui_buttons.Button({
	        text: okText,
	        color: ui_buttons.Button.Color.DANGER_DARK,
	        onclick: () => {
	          const newTemplate = this.getInfoTemplate();
	          const wrapper = babelHelpers.classPrivateFieldLooseBase(this, _secondaryTemplate)[_secondaryTemplate].querySelector('.intranet-settings__main-page-info-template');
	          const innerWrapper = wrapper.querySelector('.intranet-settings__main-page-info');
	          main_core.Dom.replace(innerWrapper, newTemplate);
	          main_core.ajax.runAction('intranet.mainpage.withdraw').then(() => {
	            this.emit('withdraw');
	            babelHelpers.classPrivateFieldLooseBase(this, _isPublished)[_isPublished] = false;
	          });
	          babelHelpers.classPrivateFieldLooseBase(this, _popupWithdraw)[_popupWithdraw].close();
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
	        onclick: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _popupWithdraw)[_popupWithdraw].close();
	        }
	      })],
	      events: {
	        onClose: () => {}
	      }
	    });
	    (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _popupWithdraw)[_popupWithdraw]) == null ? void 0 : _babelHelpers$classPr6.show();
	  } else {
	    var _babelHelpers$classPr7;
	    (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _popupWithdraw)[_popupWithdraw]) == null ? void 0 : _babelHelpers$classPr7.show();
	  }
	}
	function _getButtonEdit2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _urlEdit)[_urlEdit]) {
	    return null;
	  }
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _buttonEdit)[_buttonEdit]) {
	    const buttonEdit = main_core.Tag.render(_t15 || (_t15 = _$n`			
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --light-blue">
					${0}
				</button>
			`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_EDIT'));
	    const buttonEditLock = main_core.Tag.render(_t16 || (_t16 = _$n`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --light-blue --disabled">
					${0}
					${0}
				</button>
			`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_EDIT'), this.renderLockElement());
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonEdit)[_buttonEdit] = babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? buttonEdit : buttonEditLock;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _buttonEdit)[_buttonEdit];
	}
	function _getButtonPublish2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _buttonPublish)[_buttonPublish]) {
	    const renderNode = main_core.Tag.render(_t17 || (_t17 = _$n`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps
						${0}">
					${0}
				</button>
			`), babelHelpers.classPrivateFieldLooseBase(this, _isPageExists)[_isPageExists] ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PUBLIC'));
	    const renderNodeLock = main_core.Tag.render(_t18 || (_t18 = _$n`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --disabled
						${0}">
					${0}
					${0}
				</button>
			`), babelHelpers.classPrivateFieldLooseBase(this, _isPageExists)[_isPageExists] ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PUBLIC'), this.renderLockElement());
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonPublish)[_buttonPublish] = babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? renderNode : renderNodeLock;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _buttonPublish)[_buttonPublish];
	}
	function _getButtonWithdraw2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _buttonWithdraw)[_buttonWithdraw]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonWithdraw)[_buttonWithdraw] = main_core.Tag.render(_t19 || (_t19 = _$n`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps
						${0}">
					${0}
				</button>
			`), babelHelpers.classPrivateFieldLooseBase(this, _isPageExists)[_isPageExists] ? 'ui-btn-primary' : '--light-blue', main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_UNPUBLIC'));
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _buttonWithdraw)[_buttonWithdraw];
	}
	function _getButtonPartners2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _urlPartners)[_urlPartners]) {
	    return null;
	  }
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _buttonPartners)[_buttonPartners]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonPartners)[_buttonPartners] = main_core.Tag.render(_t20 || (_t20 = _$n`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps --light-gray">
					${0}
				</button>
			`), main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PARTNERS'));
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _buttonPartners)[_buttonPartners];
	}
	function _getButtonCreate2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _urlCreate)[_urlCreate]) {
	    return null;
	  }
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _buttonMarket)[_buttonMarket]) {
	    const buttonColor = !babelHelpers.classPrivateFieldLooseBase(this, _isPageExists)[_isPageExists] ? 'ui-btn-primary' : '--light-blue';
	    const renderNode = main_core.Tag.render(_t21 || (_t21 = _$n`			
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps ${0}">
					${0}
				</button>
			`), buttonColor, main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_MARKET'));
	    const renderNodeLock = main_core.Tag.render(_t22 || (_t22 = _$n`
				<button class="ui-btn ui-btn-md ui-btn-round ui-btn-no-caps ${0} --disabled">
					${0}
					${0}
				</button>
			`), buttonColor, main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_MARKET'), this.renderLockElement());
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonMarket)[_buttonMarket] = babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? renderNode : renderNodeLock;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _buttonMarket)[_buttonMarket];
	}
	function _bindButtonEvents2() {
	  if (typeof BX.SidePanel === 'undefined') {
	    return;
	  }
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getButtonMainSettings)[_getButtonMainSettings](), 'click', babelHelpers.classPrivateFieldLooseBase(this, _showImportPopup)[_showImportPopup].bind(this));
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getButtonSecondarySettings)[_getButtonSecondarySettings](), 'click', babelHelpers.classPrivateFieldLooseBase(this, _showExportPopup)[_showExportPopup].bind(this));
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getButtonCreate)[_getButtonCreate]()) {
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getButtonCreate)[_getButtonCreate](), 'click', () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit]) {
	        BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _urlCreate)[_urlCreate]);
	      } else {
	        BX.UI.InfoHelper.show("limit_office_vibe");
	      }
	      BX.UI.Analytics.sendData({
	        tool: 'landing',
	        category: 'vibe',
	        event: 'open_market',
	        status: babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? 'success' : 'error_limit'
	      });
	    });
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getButtonEdit)[_getButtonEdit]()) {
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getButtonEdit)[_getButtonEdit](), 'click', () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit]) {
	        BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _urlEdit)[_urlEdit], {
	          customLeftBoundary: 66,
	          events: {
	            onCloseComplete: () => {
	              if (babelHelpers.classPrivateFieldLooseBase(this, _urlPublic)[_urlPublic]) {
	                window.top.location = babelHelpers.classPrivateFieldLooseBase(this, _urlPublic)[_urlPublic];
	              }
	            }
	          }
	        });
	      } else {
	        BX.UI.InfoHelper.show("limit_office_vibe");
	      }
	      BX.UI.Analytics.sendData({
	        tool: 'landing',
	        category: 'vibe',
	        event: 'open_editor',
	        status: babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit] ? 'success' : 'error_limit'
	      });
	    });
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getButtonPartners)[_getButtonPartners]()) {
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getButtonPartners)[_getButtonPartners](), 'click', () => {
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _feedbackParams)[_feedbackParams]) {
	        return;
	      }

	      // todo: need analitycs?

	      main_core.Runtime.loadExtension('ui.feedback.form').then(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _feedbackParams)[_feedbackParams].title = main_core.Loc.getMessage('INTRANET_SETTINGS_MAINPAGE_BUTTON_PARTNERS');
	        BX.UI.Feedback.Form.open(babelHelpers.classPrivateFieldLooseBase(this, _feedbackParams)[_feedbackParams]);
	      });
	    });
	  }
	  this.subscribe('publish', () => {
	    main_core.Dom.replace(babelHelpers.classPrivateFieldLooseBase(this, _getButtonPublish)[_getButtonPublish](), babelHelpers.classPrivateFieldLooseBase(this, _getButtonWithdraw)[_getButtonWithdraw]());
	  });
	  this.subscribe('withdraw', () => {
	    main_core.Dom.replace(babelHelpers.classPrivateFieldLooseBase(this, _getButtonWithdraw)[_getButtonWithdraw](), babelHelpers.classPrivateFieldLooseBase(this, _getButtonPublish)[_getButtonPublish]());
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getButtonPublish)[_getButtonPublish](), 'click', () => {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _canEdit)[_canEdit]) {
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
	    babelHelpers.classPrivateFieldLooseBase(this, _showSharePopup)[_showSharePopup]();
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getButtonWithdraw)[_getButtonWithdraw](), 'click', babelHelpers.classPrivateFieldLooseBase(this, _showWithdrawPopup)[_showWithdrawPopup].bind(this));
	}
	function _bindSliderCloseEvent2() {
	  const isPublishedBefore = babelHelpers.classPrivateFieldLooseBase(this, _isPublished)[_isPublished];
	  main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onClose', () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isPublished)[_isPublished] !== isPublishedBefore) {
	      const location = babelHelpers.classPrivateFieldLooseBase(this, _isPublished)[_isPublished] ? babelHelpers.classPrivateFieldLooseBase(this, _urlPublic)[_urlPublic] : '/';
	      window.top.location = location;
	    }
	  });
	}

	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _extensions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extensions");
	class ExternalTemporaryPage extends ui_formElements_field.BaseSettingsPage {
	  constructor(type, extensions) {
	    super();
	    Object.defineProperty(this, _type, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _extensions, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = type;
	    babelHelpers.classPrivateFieldLooseBase(this, _extensions)[_extensions] = extensions;
	  }
	  getType() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _type)[_type];
	  }
	  onSuccessDataFetched(response) {
	    main_core.Runtime.loadExtension(babelHelpers.classPrivateFieldLooseBase(this, _extensions)[_extensions]).then(exports => {
	      let externalPage;
	      let externalPageHasBeenFound = Object.values(exports).some(externalPageClassOrInstance => {
	        if (main_core.Type.isObjectLike(externalPageClassOrInstance)) {
	          let pageExemplar = null;
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
	        const event = new main_core_events.BaseEvent();
	        externalPageHasBeenFound = main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onExternalPageLoaded:' + this.getType(), event).some(pageExemplar => {
	          if (pageExemplar instanceof ui_formElements_field.BaseSettingsPage) {
	            externalPage = pageExemplar;
	            return true;
	          }
	          return false;
	        });
	      }
	      if (externalPage instanceof ui_formElements_field.BaseSettingsPage) {
	        this.getParentElement().registerPage(externalPage);
	        externalPage.setData(response.data);
	        this.getParentElement().removeChild(this);
	        if (main_core.Dom.isShown(this.getPage())) {
	          externalPage.getParentElement().show(externalPage.getType());
	        }
	      } else {
	        this.onFailDataFetched('The external page was not found.');
	      }
	    }, this.onFailDataFetched.bind(this));
	  }
	}

	var _pages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pages");
	class PageManager {
	  constructor(pages) {
	    Object.defineProperty(this, _pages, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages] = pages;
	  }
	  fetchPage(page) {
	    return new Promise((resolve, reject) => {
	      const pageIsFound = babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].some(savedPage => {
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
	  collectData() {
	    const data = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].forEach(page => {
	      if (page.hasData()) {
	        data[page.getType()] = this.constructor.getFormData(page.getFormNode());
	      }
	    });
	    return data;
	  }
	  static getFormData(formNode) {
	    return BX.ajax.prepareForm(formNode).data;
	  }
	}

	class DataSource {
	  fetch(query) {
	    return new Promise();
	  }
	}

	var _query = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("query");
	var _minSymbol = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("minSymbol");
	var _dataSource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dataSource");
	var _result = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("result");
	var _state = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("state");
	class Searcher {
	  // ready | wait;

	  constructor(dataSource, minSymbol = 3) {
	    Object.defineProperty(this, _query, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _minSymbol, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dataSource, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _result, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _state, {
	      writable: true,
	      value: void 0
	    });
	    if (!(dataSource instanceof DataSource)) {
	      throw new Error('Unexpected type, expect: DataSource');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _dataSource)[_dataSource] = dataSource;
	    babelHelpers.classPrivateFieldLooseBase(this, _minSymbol)[_minSymbol] = minSymbol;
	    babelHelpers.classPrivateFieldLooseBase(this, _result)[_result] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _state)[_state] = 'ready';
	  }
	  find(query) {
	    if (query.length < babelHelpers.classPrivateFieldLooseBase(this, _minSymbol)[_minSymbol]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _query)[_query] = query;
	    this.changeState(Searcher.STATE_WAIT);
	    babelHelpers.classPrivateFieldLooseBase(this, _dataSource)[_dataSource].fetch(babelHelpers.classPrivateFieldLooseBase(this, _query)[_query]).then(this.resolve.bind(this), this.reject.bind(this));
	  }
	  changeState(state) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _state)[_state] === state) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _state)[_state] = state;
	    main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:searchChangeState', {
	      state: babelHelpers.classPrivateFieldLooseBase(this, _state)[_state]
	    });
	  }
	  getMinSymbol() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _minSymbol)[_minSymbol];
	  }
	  resolve(response) {
	    babelHelpers.classPrivateFieldLooseBase(this, _result)[_result] = response.data;
	    this.changeState(babelHelpers.classPrivateFieldLooseBase(this, _result)[_result].length > 0 ? Searcher.STATE_READY : Searcher.STATE_NOT_FOUND);
	  }
	  reject(response) {
	    babelHelpers.classPrivateFieldLooseBase(this, _result)[_result] = [];
	    this.changeState(Searcher.STATE_READY);
	  }
	  getResult() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _result)[_result];
	  }
	  getOthers() {
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
	}
	Searcher.STATE_READY = 'ready';
	Searcher.STATE_WAIT = 'wait';
	Searcher.STATE_NOT_FOUND = 'not_found';

	let _$o = t => t,
	  _t$o,
	  _t2$d,
	  _t3$a,
	  _t4$3,
	  _t5$2,
	  _t6$2,
	  _t7$2,
	  _t8$2,
	  _t9$2,
	  _t10$1,
	  _t11$1,
	  _t12$1,
	  _t13$1,
	  _t14$1,
	  _t15$1;
	var _searcher = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("searcher");
	var _inputNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputNode");
	var _iconContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("iconContainer");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _timeoutId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timeoutId");
	var _timeout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timeout");
	var _nav = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("nav");
	class Renderer {
	  constructor(options) {
	    Object.defineProperty(this, _searcher, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputNode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _iconContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _timeoutId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _timeout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _nav, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _searcher)[_searcher] = options.searcher;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode] = options.inputNode;
	    babelHelpers.classPrivateFieldLooseBase(this, _iconContainer)[_iconContainer] = options.iconContainer;
	    babelHelpers.classPrivateFieldLooseBase(this, _timeout)[_timeout] = options.timeout;
	    babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav] = new SearchNavigation();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup('settings-search-popup', babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode], {
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
	      autoHideHandler: event => {
	        return event.target !== babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode];
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setContent(this.renderContent());
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode], 'focus', () => {
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].isShown()) {
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	      }
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Settings:searchChangeState', event => {
	      const {
	        state
	      } = event.data;
	      babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].clean();
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setContent(this.renderContent(state));
	    });
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _iconContainer)[_iconContainer].querySelector('#intranet-settings-icon-delete'), 'click', () => {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _iconContainer)[_iconContainer], 'main-ui-show');
	      babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode].value = '';
	    });
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode], 'keyup', event => {
	      if (event.keyCode === 37 || event.keyCode === 39) {
	        return;
	      }
	      if (event.keyCode === 13)
	        //enter
	        {
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].current().dispatchEvent(new MouseEvent('click'));
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].unHighlightAll();
	          return;
	        }
	      if (event.keyCode === 38)
	        //up
	        {
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].prev().highlight();
	          if (!main_core.Type.isNil(babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].current())) {
	            this.updateScroll(babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].current());
	          }
	          return;
	        }
	      if (event.keyCode === 40)
	        //down
	        {
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].next().highlight();
	          if (!main_core.Type.isNil(babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].current())) {
	            this.updateScroll(babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].current());
	          }
	          return;
	        }
	      if (this.getQuery().length > 0) {
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _iconContainer)[_iconContainer], 'main-ui-show');
	      } else {
	        main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _iconContainer)[_iconContainer], 'main-ui-show');
	      }
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].isShown() && this.getQuery().length > 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	      }
	      this.find();
	    });
	  }
	  updateScroll(element) {
	    const rect = element.getBoundingClientRect();
	    const container = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getContentContainer().firstElementChild;
	    const relTop = rect.top - container.getBoundingClientRect().top;
	    const relBot = rect.bottom - container.getBoundingClientRect().bottom;
	    const padding = 10;
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
	  renderWait() {
	    const loaderContainer = main_core.Tag.render(_t$o || (_t$o = _$o`<span class="title-search-waiter-img"></span>`));
	    const loader = new main_loader.Loader({
	      target: loaderContainer,
	      size: 20,
	      mode: 'inline'
	    });
	    loader.show();
	    return main_core.Tag.render(_t2$d || (_t2$d = _$o`
			<div class="title-search-waiter">
				${0}
				<span class="title-search-waiter-text">${0}</span>
			</div>
		`), loaderContainer, main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_SEARCHING'));
	  }
	  find() {
	    clearTimeout(babelHelpers.classPrivateFieldLooseBase(this, _timeoutId)[_timeoutId]);
	    babelHelpers.classPrivateFieldLooseBase(this, _timeoutId)[_timeoutId] = setTimeout(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _searcher)[_searcher].find(this.getQuery());
	    }, babelHelpers.classPrivateFieldLooseBase(this, _timeout)[_timeout]);
	  }
	  getQuery() {
	    return BX.util.trim(babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode].value);
	  }
	  createLinkOption(option) {
	    const link = main_core.Dom.create('a', {
	      props: {
	        className: 'search-title-top-item-link'
	      },
	      events: {
	        mouseenter: event => {
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].unHighlightAll();
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].cursorTo(event.target);
	          SearchNavigation.highlight(event.target);
	        },
	        mouseleave: event => {
	          SearchNavigation.unHighlight(event.target);
	        }
	      },
	      attrs: {
	        title: option.title,
	        href: main_core.Type.isStringFilled(option.url) ? option.url : '#',
	        target: '_blank'
	      },
	      children: [main_core.Tag.render(_t3$a || (_t3$a = _$o`<span class="search-title-top-item-text"><span>${0}</span></span>`), option.title)]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].add(link);
	    return link;
	  }
	  createBtnOption(page, option) {
	    const link = main_core.Dom.create('a', {
	      props: {
	        className: 'search-title-top-item-link'
	      },
	      events: {
	        click: event => {
	          main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.SettingsNavigation:onMove', {
	            page: page,
	            fieldName: option.code
	          });
	          babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode].blur();
	          babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].close();
	          event.preventDefault();
	        },
	        mouseenter: event => {
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].unHighlightAll();
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].cursorTo(event.target);
	          SearchNavigation.highlight(event.target);
	        },
	        mouseleave: event => {
	          SearchNavigation.unHighlight(event.target);
	        }
	      },
	      attrs: {
	        title: option.title,
	        href: "#"
	      },
	      children: [main_core.Tag.render(_t4$3 || (_t4$3 = _$o`<span class="search-title-top-item-text"><span>${0}</span></span>`), option.title)]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].add(link);
	    return main_core.Tag.render(_t5$2 || (_t5$2 = _$o`<div class="search-title-top-item search-title-top-item-js">${0}</div>`), link);
	  }
	  renderOption(page, option) {
	    let link;
	    if (page === Renderer.EXTERNAL_LINK) {
	      link = this.createLinkOption(option);
	    } else {
	      link = this.createBtnOption(page, option);
	    }
	    return main_core.Tag.render(_t6$2 || (_t6$2 = _$o`<div class="search-title-top-item search-title-top-item-js">${0}</div>`), link);
	  }
	  renderGroup(group) {
	    const optionsContainer = main_core.Tag.render(_t7$2 || (_t7$2 = _$o`<div class="search-title-top-list search-title-top-list-js"></div>`));
	    group.options.forEach(option => {
	      main_core.Dom.append(this.renderOption(group.page, option), optionsContainer);
	    });
	    return main_core.Tag.render(_t8$2 || (_t8$2 = _$o`
			<div class="search-title-top-block search-title-top-block-sonetgroups">
				<div class="search-title-top-subtitle">
					<div class="search-title-top-subtitle-text">${0}</div>
				</div>
				<div class="search-title-top-list-wrap">
					${0}
				</div>
			</div>
		`), group.title, optionsContainer);
	  }
	  renderContent(state = 'ready') {
	    const optionsContainer = main_core.Tag.render(_t9$2 || (_t9$2 = _$o`<div class="search-title-top-result"></div>`));
	    switch (state) {
	      case 'ready':
	        main_core.Dom.append(this.renderSearchResult(babelHelpers.classPrivateFieldLooseBase(this, _searcher)[_searcher].getResult()), optionsContainer);
	        break;
	      case 'wait':
	        main_core.Dom.append(this.renderWait(), optionsContainer);
	        break;
	      case 'not_found':
	        main_core.Dom.append(this.renderNotFound(), optionsContainer);
	        break;
	    }
	    main_core.Dom.append(this.renderOthers(babelHelpers.classPrivateFieldLooseBase(this, _searcher)[_searcher].getOthers()), optionsContainer);
	    return optionsContainer;
	  }
	  renderNotFound() {
	    return main_core.Tag.render(_t10$1 || (_t10$1 = _$o`
			<div class="title-search-waiter">
				<span class="title-search-waiter-text">${0}</span>
			</div>
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_SEARCH_NOT_FOUND'));
	  }
	  renderSearchResult(result) {
	    const container = main_core.Tag.render(_t11$1 || (_t11$1 = _$o`<div class="search-title-content-result"></div>`));
	    result.forEach(item => {
	      main_core.Dom.append(this.renderGroup(item), container);
	    });
	    return container;
	  }
	  renderOthers(links) {
	    const wraper = main_core.Tag.render(_t12$1 || (_t12$1 = _$o`<div class="search-title-top-list search-title-top-list-js"></div>`));
	    const other = main_core.Tag.render(_t13$1 || (_t13$1 = _$o`
		<div class="search-title-top-block search-title-top-block-tools">
			<div class="search-title-top-subtitle">
				<div class="search-title-top-subtitle-text">${0}</div>
			</div>
			<div class="search-title-top-list-height-wrap">
					<div class="search-title-top-list-wrap">${0}</div>
				</div>
		</div>
		`), main_core.Loc.getMessage('INTRANET_SETTINGS_TITLE_SEARCH_IN'), wraper);
	    links.forEach(link => {
	      main_core.Dom.append(this.renderOtherLink(link), wraper);
	    });
	    return other;
	  }
	  renderOtherLink(link) {
	    const linkTag = main_core.Dom.create('a', {
	      props: {
	        className: 'search-title-top-item-link'
	      },
	      events: {
	        mouseenter: event => {
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].unHighlightAll();
	          babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].cursorTo(event.target);
	          SearchNavigation.highlight(event.target);
	        },
	        mouseleave: event => {
	          SearchNavigation.unHighlight(event.target);
	        }
	      },
	      attrs: {
	        title: link.title,
	        href: link.link,
	        target: 'blank_'
	      },
	      children: [main_core.Tag.render(_t14$1 || (_t14$1 = _$o`<span class="search-title-top-item-text"><span>${0}</span></span>`), link.title)]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _nav)[_nav].add(linkTag);
	    return main_core.Tag.render(_t15$1 || (_t15$1 = _$o`
		<div class="search-title-top-item search-title-top-item-js">
			${0}
		</div>`), linkTag);
	  }
	}
	Renderer.EXTERNAL_LINK = 'EXTERNAL_LINKS';
	var _index = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("index");
	var _elementList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("elementList");
	class SearchNavigation {
	  constructor(nodeList = []) {
	    Object.defineProperty(this, _index, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _elementList, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _elementList)[_elementList] = nodeList;
	  }
	  add(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _elementList)[_elementList].push(element);
	  }
	  clean() {
	    babelHelpers.classPrivateFieldLooseBase(this, _index)[_index] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _elementList)[_elementList] = [];
	  }
	  next() {
	    if (main_core.Type.isNil(babelHelpers.classPrivateFieldLooseBase(this, _index)[_index])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _index)[_index] = 0;
	      return this;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _elementList)[_elementList].length - 1 > babelHelpers.classPrivateFieldLooseBase(this, _index)[_index]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _index)[_index]++;
	    }
	    return this;
	  }
	  current() {
	    if (main_core.Type.isNil(babelHelpers.classPrivateFieldLooseBase(this, _index)[_index])) {
	      return null;
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _elementList)[_elementList][babelHelpers.classPrivateFieldLooseBase(this, _index)[_index]];
	  }
	  prev() {
	    if (main_core.Type.isNil(babelHelpers.classPrivateFieldLooseBase(this, _index)[_index])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _index)[_index] = babelHelpers.classPrivateFieldLooseBase(this, _elementList)[_elementList].length - 1;
	      return this;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _index)[_index] > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _index)[_index] -= 1;
	    }
	    return this;
	  }
	  highlight() {
	    this.unHighlightAll();
	    if (!main_core.Dom.hasClass(this.current(), 'active')) {
	      main_core.Dom.addClass(this.current(), 'active');
	    }
	    return this;
	  }
	  unHighlight() {
	    if (main_core.Dom.hasClass(this.current(), 'active')) {
	      main_core.Dom.removeClass(this.current(), 'active');
	    }
	    return this;
	  }
	  static highlight(element) {
	    element.classList.add('active');
	  }
	  static unHighlight(element) {
	    element.classList.remove('active');
	  }
	  cursorTo(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _elementList)[_elementList].forEach((item, index) => {
	      if (item === element) {
	        babelHelpers.classPrivateFieldLooseBase(this, _index)[_index] = index;
	        return;
	      }
	    });
	  }
	  unHighlightAll() {
	    babelHelpers.classPrivateFieldLooseBase(this, _elementList)[_elementList].forEach(item => {
	      SearchNavigation.unHighlight(item);
	    });
	  }
	}

	class ServerDataSource extends DataSource {
	  constructor() {
	    super();
	  }
	  fetch(query) {
	    return main_core.ajax.runComponentAction('bitrix:intranet.settings', 'search', {
	      mode: 'class',
	      data: {
	        query: query
	      }
	    });
	  }
	}

	var _permission = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("permission");
	class Permission {
	  constructor(permission = 0) {
	    Object.defineProperty(this, _permission, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _permission)[_permission] = permission;
	  }
	  canRead() {
	    return !!(babelHelpers.classPrivateFieldLooseBase(this, _permission)[_permission] & Permission.READ);
	  }
	  canEdit() {
	    return !!(babelHelpers.classPrivateFieldLooseBase(this, _permission)[_permission] & Permission.EDIT);
	  }
	  getPermission() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _permission)[_permission];
	  }
	}
	Permission.READ = 1 << 0;
	Permission.EDIT = 1 << 2;

	var _basePage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("basePage");
	var _menuNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuNode");
	var _settingsNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settingsNode");
	var _contentNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("contentNode");
	var _pageManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageManager");
	var _cancelMessageBox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelMessageBox");
	var _analytic = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytic");
	var _navigator = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("navigator");
	var _permission$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("permission");
	var _pagesPermission = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pagesPermission");
	var _extraSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extraSettings");
	var _getPageManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPageManager");
	var _onEventFetchPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEventFetchPage");
	var _onSliderCloseHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSliderCloseHandler");
	var _reload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("reload");
	var _onEventChangeData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEventChangeData");
	var _onClickSaveBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickSaveBtn");
	var _successSaveHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("successSaveHandler");
	var _failSaveHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("failSaveHandler");
	var _prepareErrorCollection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareErrorCollection");
	var _onClickCancelBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickCancelBtn");
	var _hideWaitIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideWaitIcon");
	var _selectPageForError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectPageForError");
	class Settings extends ui_formElements_field.BaseSettingsElement {
	  constructor(params) {
	    super(params);
	    Object.defineProperty(this, _selectPageForError, {
	      value: _selectPageForError2
	    });
	    Object.defineProperty(this, _hideWaitIcon, {
	      value: _hideWaitIcon2
	    });
	    Object.defineProperty(this, _onClickCancelBtn, {
	      value: _onClickCancelBtn2
	    });
	    Object.defineProperty(this, _prepareErrorCollection, {
	      value: _prepareErrorCollection2
	    });
	    Object.defineProperty(this, _failSaveHandler, {
	      value: _failSaveHandler2
	    });
	    Object.defineProperty(this, _successSaveHandler, {
	      value: _successSaveHandler2
	    });
	    Object.defineProperty(this, _onClickSaveBtn, {
	      value: _onClickSaveBtn2
	    });
	    Object.defineProperty(this, _onEventChangeData, {
	      value: _onEventChangeData2
	    });
	    Object.defineProperty(this, _reload, {
	      value: _reload2
	    });
	    Object.defineProperty(this, _onSliderCloseHandler, {
	      value: _onSliderCloseHandler2
	    });
	    Object.defineProperty(this, _onEventFetchPage, {
	      value: _onEventFetchPage2
	    });
	    Object.defineProperty(this, _getPageManager, {
	      value: _getPageManager2
	    });
	    Object.defineProperty(this, _basePage, {
	      writable: true,
	      value: void 0
	    });
	    this.isChanged = false;
	    Object.defineProperty(this, _menuNode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _settingsNode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _contentNode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pageManager, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cancelMessageBox, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytic, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _navigator, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _permission$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pagesPermission, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _extraSettings, {
	      writable: true,
	      value: {
	        reloadAfterClose: false
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _analytic)[_analytic] = new Analytic({
	      isAdmin: true,
	      locationName: 'settings',
	      isBitrix24: params.isBitrix24 === true,
	      analyticContext: main_core.Type.isStringFilled(params.analyticContext) ? params.analyticContext : null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _analytic)[_analytic].addEventOpenSettings();
	    babelHelpers.classPrivateFieldLooseBase(this, _analytic)[_analytic].addEventStartPagePage(params.startPage);
	    this.setEventNamespace('BX.Intranet.Settings');
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'button-click', event => {
	      const [clickedBtn] = event.data;
	      if (clickedBtn.TYPE === 'save') {
	        babelHelpers.classPrivateFieldLooseBase(this, _onClickSaveBtn)[_onClickSaveBtn](event);
	      }
	    });
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onClose', babelHelpers.classPrivateFieldLooseBase(this, _onSliderCloseHandler)[_onSliderCloseHandler].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _menuNode)[_menuNode] = main_core.Type.isDomNode(params.menuNode) ? params.menuNode : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _settingsNode)[_settingsNode] = main_core.Type.isDomNode(params.settingsNode) ? params.settingsNode : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _contentNode)[_contentNode] = main_core.Type.isDomNode(params.contentNode) ? params.contentNode : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _basePage)[_basePage] = main_core.Type.isString(params.basePage) ? params.basePage : '';
	    babelHelpers.classPrivateFieldLooseBase(this, _permission$1)[_permission$1] = params.permission instanceof Permission ? params.permission : new Permission();
	    babelHelpers.classPrivateFieldLooseBase(this, _pagesPermission)[_pagesPermission] = params.pagesPermission;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _settingsNode)[_settingsNode]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _settingsNode)[_settingsNode].querySelector('.ui-button-panel input[name="cancel"]').addEventListener('click', babelHelpers.classPrivateFieldLooseBase(this, _onClickCancelBtn)[_onClickCancelBtn]);
	    }
	    params.pages.concat(Object.values(params.externalPages).map(({
	      type,
	      extensions
	    }) => new ExternalTemporaryPage(type, extensions))).forEach(page => this.registerPage(page).expandPage(params.subPages[page.getType()]));
	    const toolsMenuItem = BX.UI.DropdownMenuItem.getItemByNode(babelHelpers.classPrivateFieldLooseBase(this, _menuNode)[_menuNode].querySelector('[data-type="tools"]'));
	    if (toolsMenuItem.subItems && toolsMenuItem.subItems.length > 0) {
	      toolsMenuItem.hideSubmenu();
	      toolsMenuItem.setDefaultToggleButtonName();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _navigator)[_navigator] = new Navigation(this);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _menuNode)[_menuNode]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _menuNode)[_menuNode].querySelectorAll('li.ui-sidepanel-menu-item a.ui-sidepanel-menu-link').forEach(item => {
	        const helpPopup = new ui_section.HelpMessage(item.dataset.type + '_help-msg', item, main_core.Loc.getMessage('INTRANET_SETTINGS_PERMISSION_MSG'));
	        helpPopup.getPopup().setWidth(275);
	        const page = this.getNavigator().getPageByType(item.dataset.type);
	        item.addEventListener('click', event => {
	          var _page$getPermission;
	          if (page != null && (_page$getPermission = page.getPermission()) != null && _page$getPermission.canRead()) {
	            this.show(item.dataset.type);
	          } else {
	            helpPopup.show();
	          }
	        });
	      });
	    }
	  }
	  registerPage(page) {
	    var _babelHelpers$classPr;
	    page.setParentElement(this);
	    page.setPermission(new Permission((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _pagesPermission)[_pagesPermission][page.getType()]) != null ? _babelHelpers$classPr : null));
	    page.subscribe('change', babelHelpers.classPrivateFieldLooseBase(this, _onEventChangeData)[_onEventChangeData].bind(this)).subscribe('fetch', babelHelpers.classPrivateFieldLooseBase(this, _onEventFetchPage)[_onEventFetchPage].bind(this));
	    page.setAnalytic(babelHelpers.classPrivateFieldLooseBase(this, _analytic)[_analytic]);
	    return page;
	  }
	  getCurrentPage() {
	    return this.getNavigator().getCurrentPage();
	  }
	  getNavigator() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _navigator)[_navigator];
	  }
	  show(type, option) {
	    var _this$getNavigator$ge;
	    if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _contentNode)[_contentNode])) {
	      console.log('Not found settings container');
	      return;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _permission$1)[_permission$1].canRead()) {
	      return;
	    }
	    const nextPage = this.getNavigator().getPageByType(type);
	    if (this.getCurrentPage() === nextPage) {
	      return;
	    }
	    this.getNavigator().changePage(nextPage);
	    main_core.Dom.hide((_this$getNavigator$ge = this.getNavigator().getPrevPage()) == null ? void 0 : _this$getNavigator$ge.getPage());
	    if (main_core.Type.isNil(this.getNavigator().getCurrentPage().getPage().parentNode)) {
	      main_core.Dom.append(this.getNavigator().getCurrentPage().getPage(), babelHelpers.classPrivateFieldLooseBase(this, _contentNode)[_contentNode]);
	    } else {
	      main_core.Dom.show(this.getNavigator().getCurrentPage().getPage());
	    }
	    this.activateMenuItem(type);
	    babelHelpers.classPrivateFieldLooseBase(this, _analytic)[_analytic].addEventChangePage(type);
	    this.getNavigator().updateAddressBar();
	    main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onAfterShowPage', {
	      source: this,
	      page: nextPage
	    });
	    if (main_core.Type.isString(option) && option !== '') {
	      main_core_events.EventEmitter.subscribeOnce(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onPageComplete', () => {
	        console.log(option);
	        this.getNavigator().moveTo(nextPage, option);
	      });
	    }
	  }
	  activateMenuItem(type) {
	    const menuItem = BX.UI.DropdownMenuItem.getItemByNode(babelHelpers.classPrivateFieldLooseBase(this, _menuNode)[_menuNode].querySelector(`a.ui-sidepanel-menu-link[data-type="${type}"]`));
	    menuItem && menuItem.setActiveHandler();
	  }
	}
	function _getPageManager2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _pageManager)[_pageManager]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _pageManager)[_pageManager] = new PageManager(this.getChildrenElements());
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _pageManager)[_pageManager];
	}
	function _onEventFetchPage2(event) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getPageManager)[_getPageManager]().fetchPage(event.getTarget());
	}
	function _onSliderCloseHandler2(event) {
	  var _panelEvent$slider$ge;
	  const [panelEvent] = event.getCompatData();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _cancelMessageBox)[_cancelMessageBox] instanceof ui_dialogs_messagebox.MessageBox) {
	    panelEvent.denyAction();
	    return false;
	  }
	  if (this.isChanged && ((_panelEvent$slider$ge = panelEvent.slider.getData()) == null ? void 0 : _panelEvent$slider$ge.get('ignoreChanges')) !== true) {
	    panelEvent.denyAction();
	    babelHelpers.classPrivateFieldLooseBase(this, _cancelMessageBox)[_cancelMessageBox] = ui_dialogs_messagebox.MessageBox.create({
	      message: main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DESC'),
	      modal: true,
	      buttons: [new BX.UI.Button({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_OK'),
	        color: BX.UI.Button.Color.SUCCESS,
	        events: {
	          click: () => {
	            main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onCancel', {});
	            panelEvent.slider.getData().set('ignoreChanges', true);
	            this.isChanged = false;
	            BX.UI.ButtonPanel.hide();
	            babelHelpers.classPrivateFieldLooseBase(this, _cancelMessageBox)[_cancelMessageBox].close();
	            babelHelpers.classPrivateFieldLooseBase(this, _cancelMessageBox)[_cancelMessageBox] = null;
	            panelEvent.slider.close(false, () => {
	              panelEvent.slider.destroy();
	            });
	            if (babelHelpers.classPrivateFieldLooseBase(this, _basePage)[_basePage].includes('/configs/')) {
	              babelHelpers.classPrivateFieldLooseBase(this, _reload)[_reload]('/index.php');
	            }
	          }
	        }
	      }), new BX.UI.CancelButton({
	        text: main_core.Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_CANCEL'),
	        events: {
	          click: () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _cancelMessageBox)[_cancelMessageBox].close();
	            babelHelpers.classPrivateFieldLooseBase(this, _cancelMessageBox)[_cancelMessageBox] = null;
	          }
	        }
	      })]
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _cancelMessageBox)[_cancelMessageBox].show();
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _basePage)[_basePage].includes('/configs/') || babelHelpers.classPrivateFieldLooseBase(this, _extraSettings)[_extraSettings].reloadAfterClose === true) {
	    babelHelpers.classPrivateFieldLooseBase(this, _reload)[_reload]('/index.php');
	  }
	}
	function _reload2(url = null) {
	  const loader = document.querySelector('#ui-sidepanel-wrapper-loader');
	  if (loader) {
	    loader.style.display = '';
	  }
	  if (main_core.Type.isString(url)) {
	    top.window.location.href = url;
	  } else {
	    top.window.location.href = babelHelpers.classPrivateFieldLooseBase(this, _basePage)[_basePage];
	  }
	}
	function _onEventChangeData2(event) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _permission$1)[_permission$1].canEdit()) {
	    return;
	  }
	  this.isChanged = true;
	  BX.UI.ButtonPanel.show();
	}
	function _onClickSaveBtn2(event) {
	  let data = babelHelpers.classPrivateFieldLooseBase(this, _getPageManager)[_getPageManager]().collectData();
	  main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onBeforeSave', {
	    data: data
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _analytic)[_analytic].send();
	  main_core.ajax.runComponentAction('bitrix:intranet.settings', 'set', {
	    mode: 'class',
	    data: main_core.Http.Data.convertObjectToFormData(data)
	  }).then(babelHelpers.classPrivateFieldLooseBase(this, _successSaveHandler)[_successSaveHandler].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _failSaveHandler)[_failSaveHandler].bind(this));
	}
	function _successSaveHandler2(response) {
	  babelHelpers.classPrivateFieldLooseBase(this, _extraSettings)[_extraSettings].reloadAfterClose = true;
	  this.isChanged = false;
	  babelHelpers.classPrivateFieldLooseBase(this, _hideWaitIcon)[_hideWaitIcon]();
	  BX.UI.ButtonPanel.hide();
	  main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onSuccessSave', babelHelpers.classPrivateFieldLooseBase(this, _extraSettings)[_extraSettings]);
	}
	function _failSaveHandler2(response) {
	  let errorCollection = babelHelpers.classPrivateFieldLooseBase(this, _prepareErrorCollection)[_prepareErrorCollection](response.errors);
	  babelHelpers.classPrivateFieldLooseBase(this, _hideWaitIcon)[_hideWaitIcon]();
	  main_core_events.EventEmitter.emit('BX.UI.FormElement.Field:onFailedSave', {
	    errors: errorCollection
	  });
	  let pageType = babelHelpers.classPrivateFieldLooseBase(this, _selectPageForError)[_selectPageForError](errorCollection);
	  this.show(pageType);
	}
	function _prepareErrorCollection2(rawErrors) {
	  let errorCollection = {};
	  for (let error of rawErrors) {
	    var _error$customData, _error$customData2;
	    let type = (_error$customData = error.customData) == null ? void 0 : _error$customData.page;
	    let field = (_error$customData2 = error.customData) == null ? void 0 : _error$customData2.field;
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
	  return errorCollection;
	}
	function _onClickCancelBtn2(event) {
	  top.BX.SidePanel.Instance.close();
	}
	function _hideWaitIcon2() {
	  let saveBtnNode = document.querySelector('#intranet-settings-page #ui-button-panel-save');
	  main_core.Dom.removeClass(saveBtnNode, 'ui-btn-wait');
	}
	function _selectPageForError2(errors) {
	  for (let pageType in errors) {
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

}((this.BX.Intranet = this.BX.Intranet || {}),BX.UI.Analytics,BX.UI.DragAndDrop,BX.UI,BX,BX.UI.Uploader,BX.UI,BX.Intranet.Bitrix24.ThemePicker,BX,BX.UI,BX,BX,BX,BX,BX.UI.FormElements,BX.UI,BX.UI,BX.UI,BX,BX.UI,BX,BX.UI.Dialogs,BX.UI.FormElements,BX.Event,BX.Main,BX,BX));
//# sourceMappingURL=script.js.map
