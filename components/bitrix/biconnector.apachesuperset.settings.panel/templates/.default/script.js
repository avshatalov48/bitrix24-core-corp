/* eslint-disable */
this.BX = this.BX || {};
this.BX.BIConnector = this.BX.BIConnector || {};
(function (exports,biconnector_apacheSupersetAnalytics,ui_iconSet_main,ui_designTokens,ui_entitySelector,ui_countdown,ui_notification,main_core,main_core_events,ui_forms,ui_buttons,biconnector_dashboardParametersSelector) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var SidePanel = BX.SidePanel;
	var _sendOnSaveEvent = /*#__PURE__*/new WeakSet();
	var SettingController = /*#__PURE__*/function (_BX$UI$EntityEditorCo) {
	  babelHelpers.inherits(SettingController, _BX$UI$EntityEditorCo);
	  function SettingController(id, settings) {
	    var _settings$config$dash, _settings$config;
	    var _this;
	    babelHelpers.classCallCheck(this, SettingController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SettingController).call(this));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _sendOnSaveEvent);
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
	      _classPrivateMethodGet(this, _sendOnSaveEvent, _sendOnSaveEvent2).call(this);
	      this.innerCancel();
	    }
	  }, {
	    key: "innerCancel",
	    value: function innerCancel() {
	      SidePanel.Instance.close();
	    }
	  }]);
	  return SettingController;
	}(BX.UI.EntityEditorController);
	function _sendOnSaveEvent2() {
	  var previousSlider = BX.SidePanel.Instance.getPreviousSlider(BX.SidePanel.Instance.getSliderByWindow(window));
	  var parent = previousSlider ? previousSlider.getWindow() : top;
	  if (!parent.BX.Event) {
	    return;
	  }
	  parent.BX.Event.EventEmitter.emit('BX.BIConnector.Settings:onAfterSave');
	}

	var _templateObject;
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _subscribeOnEvents = /*#__PURE__*/new WeakSet();
	var _fillSectionIcons = /*#__PURE__*/new WeakSet();
	var _setSectionIcon = /*#__PURE__*/new WeakSet();
	var IconController = /*#__PURE__*/function (_BX$UI$EntityEditorCo) {
	  babelHelpers.inherits(IconController, _BX$UI$EntityEditorCo);
	  function IconController(id, settings) {
	    var _this;
	    babelHelpers.classCallCheck(this, IconController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(IconController).call(this));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _setSectionIcon);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _fillSectionIcons);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _subscribeOnEvents);
	    _this.initialize(id, settings);
	    _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _subscribeOnEvents, _subscribeOnEvents2).call(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  return IconController;
	}(BX.UI.EntityEditorController);
	function _subscribeOnEvents2() {
	  var _this2 = this;
	  main_core_events.EventEmitter.subscribeOnce('BX.UI.EntityEditor:onInit', function (event) {
	    var _editor$_controls;
	    var _event$getData = event.getData(),
	      _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	      editor = _event$getData2[0];
	    var control = editor === null || editor === void 0 ? void 0 : (_editor$_controls = editor._controls) === null || _editor$_controls === void 0 ? void 0 : _editor$_controls[0];
	    if (control !== null && control !== void 0 && control._sections && control._sections.length > 0) {
	      _classPrivateMethodGet$1(_this2, _fillSectionIcons, _fillSectionIcons2).call(_this2, control._sections);
	    }
	  });
	}
	function _fillSectionIcons2(sectionList) {
	  var _iterator = _createForOfIteratorHelper(sectionList),
	    _step;
	  try {
	    for (_iterator.s(); !(_step = _iterator.n()).done;) {
	      var section = _step.value;
	      if (section.getTitle() !== '') {
	        _classPrivateMethodGet$1(this, _setSectionIcon, _setSectionIcon2).call(this, section);
	      }
	    }
	  } catch (err) {
	    _iterator.e(err);
	  } finally {
	    _iterator.f();
	  }
	}
	function _setSectionIcon2(section) {
	  var container = section._headerContainer;
	  if (container === null) {
	    return;
	  }
	  var data = section.getData();
	  var headerTitle = container.querySelector('.ui-entity-editor-header-title');
	  if (headerTitle && data.iconClass) {
	    var icon = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"\n\t\t\t\t\t\tsuperset-settings-section-icon\n\t\t\t\t\t\tui-icon-set \n\t\t\t\t\t\t", "\n\t\t\t\t\t\"></span>\n\t\t\t"])), data.iconClass);
	    main_core.Dom.insertBefore(icon, headerTitle);
	  }
	}

	var ControllerFactory = /*#__PURE__*/function () {
	  function ControllerFactory(eventName) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, ControllerFactory);
	    main_core_events.EventEmitter.subscribe("".concat(eventName, ":onInitialize"), function (event) {
	      var _event$getCompatData = event.getCompatData(),
	        _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	        eventArgs = _event$getCompatData2[1];
	      eventArgs.methods.dashboardSettings = _this.factory.bind(_this);
	    });
	  }
	  babelHelpers.createClass(ControllerFactory, [{
	    key: "factory",
	    value: function factory(type, controlId, settings) {
	      switch (type) {
	        case 'settingComponentController':
	          return new SettingController(controlId, settings);
	        case 'iconController':
	          return new IconController(controlId, settings);
	        default:
	          return null;
	      }
	    }
	  }]);
	  return ControllerFactory;
	}();

	var _templateObject$1, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6;
	var DateFilterField = /*#__PURE__*/function (_BX$UI$EntityEditorLi) {
	  babelHelpers.inherits(DateFilterField, _BX$UI$EntityEditorLi);
	  function DateFilterField(id, settings) {
	    var _this;
	    babelHelpers.classCallCheck(this, DateFilterField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DateFilterField).call(this));
	    _this.dateSelectorBlock = null;
	    _this.toInput = null;
	    _this.startInput = null;
	    _this.includeLastDateCheckbox = null;
	    return _this;
	  }
	  babelHelpers.createClass(DateFilterField, [{
	    key: "createTitleNode",
	    value: function createTitleNode() {
	      return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(DateFilterField.prototype), "layout", this).call(this, options);
	      this.layoutRangeField(this.getValue() === DateFilterField.RANGE_VALUE);
	      this.layoutHint();
	    }
	  }, {
	    key: "onItemSelect",
	    value: function onItemSelect(e, item) {
	      this.layoutRangeField(item.value === DateFilterField.RANGE_VALUE);
	      babelHelpers.get(babelHelpers.getPrototypeOf(DateFilterField.prototype), "onItemSelect", this).call(this, e, item);
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(DateFilterField.prototype), "refreshLayout", this).call(this);
	      this.layoutRangeField(this.getModel().getField('FILTER_PERIOD') === DateFilterField.RANGE_VALUE);
	    }
	  }, {
	    key: "layoutRangeField",
	    value: function layoutRangeField(isRangeSelected) {
	      var _this2 = this;
	      if (this.dateSelectorBlock !== null) {
	        main_core.Dom.remove(this.dateSelectorBlock);
	        this.dateSelectorBlock = null;
	        this.startInput = null;
	        this.endInput = null;
	        this.includeLastDateCheckbox = null;
	      }
	      if (isRangeSelected) {
	        var dateStartValue = main_core.Text.encode(this.getModel().getField(this.getDateStartFieldName()));
	        this.startInput = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<input class=\"ui-ctl-element\" type=\"text\" value=\"", "\" name=\"", "\">"])), dateStartValue, this.getDateStartFieldName());
	        main_core.Event.bind(this.startInput, 'click', function () {
	          DateFilterField.showCalendar(_this2.startInput);
	        });
	        main_core.Event.bind(this.startInput, 'change', function () {
	          _this2.onChange();
	        });
	        main_core.Event.bind(this.startInput, 'input', function () {
	          _this2.onChange();
	        });
	        var dateEndValue = main_core.Text.encode(this.getModel().getField(this.getDateEndFieldName()));
	        this.endInput = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<input class=\"ui-ctl-element\" type=\"text\" value=\"", "\" name=\"", "\">"])), dateEndValue, this.getDateEndFieldName());
	        main_core.Event.bind(this.endInput, 'click', function () {
	          DateFilterField.showCalendar(_this2.endInput);
	        });
	        main_core.Event.bind(this.endInput, 'change', function () {
	          _this2.onChange();
	        });
	        main_core.Event.bind(this.endInput, 'input', function () {
	          _this2.onChange();
	        });
	        this.includeLastDateCheckbox = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<input class=\"ui-ctl-element\" type=\"checkbox\" name=\"", "\">"])), this.getIncludeLastDateName());
	        var includeLastDateValue = this.getModel().getField(this.getIncludeLastDateName());
	        if (includeLastDateValue) {
	          this.includeLastDateCheckbox.checked = true;
	        }
	        main_core.Event.bind(this.includeLastDateCheckbox, 'change', function () {
	          _this2.onChange();
	        });
	        this.dateSelectorBlock = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-ctl-dropdown-range-group\">\n\t\t\t\t\t\t<div class=\"ui-ctl-container\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-top\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-title\">", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-before-icon ui-ctl-datetime\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-before ui-ctl-icon-calendar\"></div>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-ctl-container\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-dropdown-range-line\">\n\t\t\t\t\t\t\t\t<span class=\"ui-ctl-dropdown-range-line-item\"></span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"ui-ctl-container\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-top\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-title\">", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-before-icon ui-ctl-datetime\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-before ui-ctl-icon-calendar\"></div>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-ctl-bottom\">\n\t\t\t\t\t\t\t\t<label class=\"ui-ctl ui-ctl-checkbox\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_FROM_TITLE'), this.startInput, main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_TO_TITLE'), this.endInput, this.includeLastDateCheckbox, main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_INCLUDE_LAST_DATE'));
	        main_core.Dom.append(this.dateSelectorBlock, this._innerWrapper);
	      } else {
	        main_core.Dom.addClass(this._selectContainer, 'ui-ctl-w100');
	        main_core.Dom.removeClass(this._selectContainer, 'ui-ctl-date-range');
	      }
	    }
	  }, {
	    key: "layoutHint",
	    value: function layoutHint() {
	      var hintContainer = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-settings-panel-range__hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.getHintText());
	      main_core.Dom.insertBefore(hintContainer, this._container);
	    }
	  }, {
	    key: "getHintText",
	    value: function getHintText() {
	      var hintLink = "\n\t\t\t<a \n\t\t\t\tclass=\"biconnector-superset-settings-panel-range__hint-link\"\n\t\t\t\tonclick=\"top.BX.Helper.show('redirect=detail&code=20337242&anchor=Defaultreportingperiod')\"\n\t\t\t>\n\t\t\t\t".concat(main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_FIELD_HINT_LINK'), "\n\t\t\t</a>\n\t\t");
	      return main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_FIELD_HINT').replace('#HINT_LINK#', hintLink);
	    }
	  }, {
	    key: "getDateStartFieldName",
	    value: function getDateStartFieldName() {
	      var _this$_schemeElement$;
	      return (_this$_schemeElement$ = this._schemeElement.getData().dateStartFieldName) !== null && _this$_schemeElement$ !== void 0 ? _this$_schemeElement$ : 'DATE_FILTER_START';
	    }
	  }, {
	    key: "getDateEndFieldName",
	    value: function getDateEndFieldName() {
	      var _this$_schemeElement$2;
	      return (_this$_schemeElement$2 = this._schemeElement.getData().dateEndFieldName) !== null && _this$_schemeElement$2 !== void 0 ? _this$_schemeElement$2 : 'DATE_FILTER_END';
	    }
	  }, {
	    key: "getIncludeLastDateName",
	    value: function getIncludeLastDateName() {
	      var _this$_schemeElement$3;
	      return (_this$_schemeElement$3 = this._schemeElement.getData().includeLastDateName) !== null && _this$_schemeElement$3 !== void 0 ? _this$_schemeElement$3 : 'INCLUDE_LAST_FILTER_DATE';
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(DateFilterField.prototype), "save", this).call(this);
	      this._model.setField(this.getDateStartFieldName(), null);
	      this._model.setField(this.getDateEndFieldName(), null);
	      this._model.setField(this.getIncludeLastDateName(), null);
	      if (main_core.Type.isDomNode(this.endInput)) {
	        this._model.setField(this.getDateEndFieldName(), this.endInput.value);
	      }
	      if (main_core.Type.isDomNode(this.startInput)) {
	        this._model.setField(this.getDateStartFieldName(), this.startInput.value);
	      }
	      if (main_core.Type.isDomNode(this.includeLastDateCheckbox)) {
	        this.includeLastDateCheckbox.value = this.includeLastDateCheckbox.checked ? 'Y' : 'N';
	        this._model.setField(this.getIncludeLastDateName(), this.includeLastDateCheckbox.checked ? 'Y' : 'N');
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }, {
	    key: "layout",
	    value: function layout() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(DateFilterField), "layout", this).call(this);
	    }
	  }, {
	    key: "showCalendar",
	    value: function showCalendar(input) {
	      BX.calendar.get().Close();
	      BX.calendar({
	        node: input,
	        field: input,
	        bTime: false,
	        bSetFocus: false
	      });
	    }
	  }]);
	  return DateFilterField;
	}(BX.UI.EntityEditorList);
	babelHelpers.defineProperty(DateFilterField, "RANGE_VALUE", 'range');

	var DashboardDateFilterField = /*#__PURE__*/function (_DateFilterField) {
	  babelHelpers.inherits(DashboardDateFilterField, _DateFilterField);
	  function DashboardDateFilterField() {
	    babelHelpers.classCallCheck(this, DashboardDateFilterField);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DashboardDateFilterField).apply(this, arguments));
	  }
	  babelHelpers.createClass(DashboardDateFilterField, [{
	    key: "getHintText",
	    value: function getHintText() {
	      return main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_RANGE_FIELD_HINT');
	    }
	  }]);
	  return DashboardDateFilterField;
	}(DateFilterField);

	var _templateObject$2, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5$1, _templateObject6$1, _templateObject7, _templateObject8;
	var KeyInfoField = /*#__PURE__*/function (_BX$UI$EntityEditorCu) {
	  babelHelpers.inherits(KeyInfoField, _BX$UI$EntityEditorCu);
	  function KeyInfoField() {
	    babelHelpers.classCallCheck(this, KeyInfoField);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(KeyInfoField).apply(this, arguments));
	  }
	  babelHelpers.createClass(KeyInfoField, [{
	    key: "createTitleNode",
	    value: function createTitleNode() {
	      return main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      this.ensureWrapperCreated({
	        classNames: ['ui-entity-editor-field-text']
	      });
	      this.adjustWrapper();
	      var message = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_KEY_FIELD_HINT_LINK', {
	        '#HINT_LINK#': '<link></link>'
	      });
	      var hint = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-settings-panel-range__hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), message);
	      var link = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"biconnector-superset-settings-panel-range__hint-link\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_HINT_LINK'));
	      main_core.Event.bind(link, 'click', function () {
	        top.BX.Helper.show('redirect=detail&code=20337242&anchor=Encryptionkey');
	      });
	      main_core.Dom.replace(hint.querySelector('link'), link);
	      main_core.Dom.insertBefore(hint, this._container);
	      this._innerWrapper = main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["<div class='ui-entity-editor-content-block ui-ctl-custom biconnector-superset-settings-panel-key-info-container'></div>"])));
	      main_core.Dom.append(this._innerWrapper, this._wrapper);
	      var value = main_core.Text.encode(this.getValue());
	      this.keyInput = main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"password\" class=\"ui-ctl-element\" readonly value=\"", "\">\n\t\t"])), value);
	      this.eyeButton = main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-btn-link ui-btn\">\n\t\t\t\t<span class=\"ui-icon-set --crossed-eye\"></span>\n\t\t\t</button>\n\t\t"])));
	      main_core.Event.bind(this.eyeButton, 'click', this.toggleKey.bind(this));
	      var copyButton = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-btn-link ui-btn\">\n\t\t\t\t<span class=\"ui-icon-set --copy-plates\"></span>\n\t\t\t</button>\n\t\t"])));
	      main_core.Event.bind(copyButton, 'click', this.copyText.bind(this));
	      var content = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-ctl ui-ctl__combined-input ui-ctl-w100\">\n\t\t\t\t<div class=\"ui-ctl-icon__set ui-ctl-after\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.eyeButton, copyButton, this.keyInput);
	      main_core.Dom.append(content, this._innerWrapper);
	      this.refreshButton = new ui_buttons.Button({
	        text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_KEY_FIELD_REFRESH_BUTTON_MSGVER_1'),
	        color: ui_buttons.ButtonColor.LIGHT_BORDER,
	        size: ui_buttons.ButtonSize.MEDIUM,
	        onclick: this.refreshKey.bind(this)
	      });
	      this.refreshButton.renderTo(this._innerWrapper);
	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "toggleKey",
	    value: function toggleKey(event) {
	      if (!main_core.Type.isDomNode(this.keyInput)) {
	        return;
	      }
	      var eye = this.eyeButton.querySelector('span');
	      if (this.keyInput.type === 'password') {
	        this.keyInput.type = 'text';
	        main_core.Dom.removeClass(eye, '--crossed-eye');
	        main_core.Dom.addClass(eye, '--opened-eye');
	      } else {
	        this.keyInput.type = 'password';
	        main_core.Dom.removeClass(eye, '--opened-eye');
	        main_core.Dom.addClass(eye, '--crossed-eye');
	      }
	    }
	  }, {
	    key: "copyText",
	    value: function copyText(event) {
	      if (!main_core.Type.isDomNode(this.keyInput)) {
	        return;
	      }
	      BX.clipboard.copy(this.getValue());
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_KEY_COPIED'),
	        autoHideDelay: 2000
	      });
	    }
	  }, {
	    key: "refreshKey",
	    value: function refreshKey() {
	      var _this = this;
	      this.refreshButton.setClocking();
	      main_core.ajax.runComponentAction('bitrix:biconnector.apachesuperset.setting', 'changeBiToken', {
	        mode: 'class'
	      }).then(function (response) {
	        var generatedKey = response.data;
	        if (main_core.Type.isStringFilled(generatedKey)) {
	          _this.keyInput.value = main_core.Text.encode(generatedKey);
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_KEY_UPDATE_SUCCESS'),
	            autoHideDelay: 2000
	          });
	        } else {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_KEY_UPDATE_FAILED'),
	            autoHideDelay: 2000
	          });
	        }
	        _this.refreshButton.setClocking(false);
	      });
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return KeyInfoField;
	}(BX.UI.EntityEditorCustom);

	var _templateObject$3, _templateObject2$2, _templateObject3$2, _templateObject4$2, _templateObject5$2, _templateObject6$2, _templateObject7$1;
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _values = /*#__PURE__*/new WeakMap();
	var _currentValues = /*#__PURE__*/new WeakMap();
	var UserNotificationField = /*#__PURE__*/function (_BX$UI$EntityEditorCu) {
	  babelHelpers.inherits(UserNotificationField, _BX$UI$EntityEditorCu);
	  function UserNotificationField() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, UserNotificationField);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(UserNotificationField)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "refreshKeyLock", false);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _values, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _currentValues, {
	      writable: true,
	      value: void 0
	    });
	    return _this;
	  }
	  babelHelpers.createClass(UserNotificationField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      var _this2 = this;
	      babelHelpers.get(babelHelpers.getPrototypeOf(UserNotificationField.prototype), "initialize", this).call(this, id, settings);
	      babelHelpers.classPrivateFieldSet(this, _values, new Set());
	      babelHelpers.classPrivateFieldSet(this, _currentValues, new Set());
	      this._model.getField(this.getName(), []).forEach(function (id) {
	        id = main_core.Text.toNumber(id);
	        babelHelpers.classPrivateFieldGet(_this2, _values).add(id);
	        babelHelpers.classPrivateFieldGet(_this2, _currentValues).add(id);
	      });
	    }
	  }, {
	    key: "createTitleNode",
	    value: function createTitleNode() {
	      return main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      var _this3 = this;
	      this.ensureWrapperCreated({
	        classNames: ['ui-entity-editor-field-text']
	      });
	      this.adjustWrapper();
	      var message = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_NEW_DASHBOARD_NOTIFICATION_HINT_LINK', {
	        '#HINT_LINK#': '<link></link>'
	      });
	      var hint = main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-settings-panel-range__hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), message);
	      var link = main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"biconnector-superset-settings-panel-range__hint-link\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_HINT_LINK'));
	      main_core.Event.bind(link, 'click', function () {
	        top.BX.Helper.show('redirect=detail&code=20337242&anchor=UpdateNotification');
	      });
	      main_core.Dom.replace(hint.querySelector('link'), link);
	      main_core.Dom.insertBefore(hint, this._container);
	      this._innerWrapper = main_core.Tag.render(_templateObject4$2 || (_templateObject4$2 = babelHelpers.taggedTemplateLiteral(["<div class='ui-entity-editor-content-block ui-ctl-custom biconnector-superset-settings-panel-key-info-container'></div>"])));
	      main_core.Dom.append(this._innerWrapper, this._wrapper);
	      var content = main_core.Tag.render(_templateObject5$2 || (_templateObject5$2 = babelHelpers.taggedTemplateLiteral(["\t\n\t\t\t<div class=\"ui-ctl-w100\"></div>\n\t\t"])));
	      main_core.Dom.append(content, this._innerWrapper);
	      var preselectedItems = [];
	      babelHelpers.classPrivateFieldGet(this, _values).forEach(function (id) {
	        preselectedItems.push(['user', id]);
	      });
	      var tagSelector = new ui_entitySelector.TagSelector({
	        dialogOptions: {
	          context: 'biconnector--new-dashboard-notify',
	          entities: [{
	            id: 'user',
	            options: {
	              selectMode: 'usersOnly'
	            }
	          }],
	          preselectedItems: preselectedItems
	        },
	        events: {
	          onBeforeTagAdd: function onBeforeTagAdd(event) {
	            var _event$getData = event.getData(),
	              tag = _event$getData.tag;
	            babelHelpers.classPrivateFieldGet(_this3, _values).add(tag.getId());
	            _this3.onChange();
	          },
	          onBeforeTagRemove: function onBeforeTagRemove(event) {
	            var _event$getData2 = event.getData(),
	              tag = _event$getData2.tag;
	            babelHelpers.classPrivateFieldGet(_this3, _values)["delete"](tag.getId());
	            _this3.onChange();
	          }
	        }
	      });
	      tagSelector.renderTo(content);
	      main_core.Dom.addClass(tagSelector.getOuterContainer(), 'ui-ctl-element');
	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "onChange",
	    value: function onChange() {
	      var _this4 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _currentValues).size !== babelHelpers.classPrivateFieldGet(this, _values).size) {
	        this.markAsChanged();
	        return;
	      }
	      this._isChanged = false;
	      babelHelpers.classPrivateFieldGet(this, _values).forEach(function (id) {
	        if (!babelHelpers.classPrivateFieldGet(_this4, _currentValues).has(id)) {
	          _this4.markAsChanged();
	        }
	      });
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      var _this5 = this;
	      var values = [];
	      if (main_core.Type.isDomNode(this._innerWrapper)) {
	        var oldSaveBlock = this._innerWrapper.querySelector('.save-block');
	        if (main_core.Type.isDomNode(oldSaveBlock)) {
	          main_core.Dom.remove(oldSaveBlock);
	        }
	        var saveBlock = main_core.Tag.render(_templateObject6$2 || (_templateObject6$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"save-block\"></div>"])));
	        babelHelpers.classPrivateFieldGet(this, _values).forEach(function (id) {
	          values.push(id);
	          main_core.Dom.append(main_core.Tag.render(_templateObject7$1 || (_templateObject7$1 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"", "[]\" value=\"", "\">"])), _this5.getName(), id), saveBlock);
	        });
	        main_core.Dom.append(saveBlock, this._innerWrapper);
	      }
	      this._model.setField(this.getName(), values);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return UserNotificationField;
	}(BX.UI.EntityEditorCustom);

	var _templateObject$4, _templateObject2$3, _templateObject3$3, _templateObject4$3, _templateObject5$3, _templateObject6$3;
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _ownerId = /*#__PURE__*/new WeakMap();
	var _initialOwnerId = /*#__PURE__*/new WeakMap();
	var DashboardOwnerField = /*#__PURE__*/function (_BX$UI$EntityEditorCu) {
	  babelHelpers.inherits(DashboardOwnerField, _BX$UI$EntityEditorCu);
	  function DashboardOwnerField() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, DashboardOwnerField);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(DashboardOwnerField)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _ownerId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _initialOwnerId, {
	      writable: true,
	      value: void 0
	    });
	    return _this;
	  }
	  babelHelpers.createClass(DashboardOwnerField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(DashboardOwnerField.prototype), "initialize", this).call(this, id, settings);
	      babelHelpers.classPrivateFieldSet(this, _ownerId, this._model.getIntegerField(this.getName(), null));
	      babelHelpers.classPrivateFieldSet(this, _initialOwnerId, this._model.getIntegerField(this.getName(), null));
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      var _this2 = this;
	      this.ensureWrapperCreated({
	        classNames: ['ui-entity-editor-field-text']
	      });
	      this.adjustWrapper();
	      var message = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_OWNER_HINT_LINK', {
	        '#HINT_LINK#': '<link></link>'
	      });
	      var hint = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-settings-panel-range__hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), message);
	      var link = main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"biconnector-superset-settings-panel-range__hint-link\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_HINT_LINK'));
	      main_core.Event.bind(link, 'click', function () {
	        top.BX.Helper.show('redirect=detail&code=20337242&anchor=DashboardOwner');
	      });
	      main_core.Dom.replace(hint.querySelector('link'), link);
	      main_core.Dom.insertBefore(hint, this._container);
	      this._innerWrapper = main_core.Tag.render(_templateObject3$3 || (_templateObject3$3 = babelHelpers.taggedTemplateLiteral(["<div class='ui-entity-editor-content-block ui-ctl-custom'></div>"])));
	      main_core.Dom.append(this._innerWrapper, this._wrapper);
	      var content = main_core.Tag.render(_templateObject4$3 || (_templateObject4$3 = babelHelpers.taggedTemplateLiteral(["\t\n\t\t\t<div class=\"ui-ctl-w100\"></div>\n\t\t"])));
	      main_core.Dom.append(content, this._innerWrapper);
	      var tagSelector = new ui_entitySelector.TagSelector({
	        multiple: false,
	        dialogOptions: {
	          context: 'biconnector--dashboard-owner',
	          dropdownMode: true,
	          entities: [{
	            id: 'user',
	            options: {
	              selectMode: 'usersOnly',
	              inviteEmployeeLink: false
	            }
	          }],
	          preselectedItems: [['user', this._model.getField(this.getName(), null)]]
	        },
	        events: {
	          onBeforeTagAdd: function onBeforeTagAdd(event) {
	            var _event$getData = event.getData(),
	              tag = _event$getData.tag;
	            babelHelpers.classPrivateFieldSet(_this2, _ownerId, tag.getId());
	            _this2.onChange();
	          },
	          onBeforeTagRemove: function onBeforeTagRemove(event) {
	            babelHelpers.classPrivateFieldSet(_this2, _ownerId, null);
	            _this2.onChange();
	          }
	        }
	      });
	      tagSelector.renderTo(content);
	      main_core.Dom.addClass(tagSelector.getOuterContainer(), 'ui-ctl-element');
	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "onChange",
	    value: function onChange() {
	      if (babelHelpers.classPrivateFieldGet(this, _initialOwnerId) !== babelHelpers.classPrivateFieldGet(this, _ownerId)) {
	        this.markAsChanged();
	        return;
	      }
	      this._isChanged = false;
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      if (main_core.Type.isDomNode(this._innerWrapper)) {
	        var oldSaveBlock = this._innerWrapper.querySelector('.save-block');
	        if (main_core.Type.isDomNode(oldSaveBlock)) {
	          main_core.Dom.remove(oldSaveBlock);
	        }
	        var saveBlock = main_core.Tag.render(_templateObject5$3 || (_templateObject5$3 = babelHelpers.taggedTemplateLiteral(["<div class=\"save-block\"></div>"])));
	        main_core.Dom.append(main_core.Tag.render(_templateObject6$3 || (_templateObject6$3 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"", "\" value=\"", "\">"])), this.getName(), babelHelpers.classPrivateFieldGet(this, _ownerId)), saveBlock);
	        main_core.Dom.append(saveBlock, this._innerWrapper);
	      }
	      this._model.setField(this.getName(), babelHelpers.classPrivateFieldGet(this, _ownerId));
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return DashboardOwnerField;
	}(BX.UI.EntityEditorCustom);

	var _templateObject$5, _templateObject2$4, _templateObject3$4, _templateObject4$4, _templateObject5$4;
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _deleteButton = /*#__PURE__*/new WeakMap();
	var _deletePopup = /*#__PURE__*/new WeakMap();
	var DeleteSupersetField = /*#__PURE__*/function (_BX$UI$EntityEditorLi) {
	  babelHelpers.inherits(DeleteSupersetField, _BX$UI$EntityEditorLi);
	  function DeleteSupersetField() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, DeleteSupersetField);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(DeleteSupersetField)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _deleteButton, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _deletePopup, {
	      writable: true,
	      value: void 0
	    });
	    return _this;
	  }
	  babelHelpers.createClass(DeleteSupersetField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(DeleteSupersetField.prototype), "initialize", this).call(this, id, settings);
	    }
	  }, {
	    key: "createTitleNode",
	    value: function createTitleNode() {
	      return main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      this.ensureWrapperCreated({
	        classNames: ['ui-entity-editor-field-text']
	      });
	      this.adjustWrapper();
	      var message = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DELETE_SUPERSET_FIELD_HINT', {
	        '#HINT_LINK#': '<link></link>'
	      });
	      var hint = main_core.Tag.render(_templateObject2$4 || (_templateObject2$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-settings-panel-range__hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), message);
	      var link = main_core.Tag.render(_templateObject3$4 || (_templateObject3$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"biconnector-superset-settings-panel-range__hint-link\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_HINT_LINK'));
	      main_core.Event.bind(link, 'click', function () {
	        top.BX.Helper.show('redirect=detail&code=20337242&anchor=Disable');
	      });
	      main_core.Dom.replace(hint.querySelector('link'), link);
	      main_core.Dom.insertBefore(hint, this._container);
	      this._innerWrapper = main_core.Tag.render(_templateObject4$4 || (_templateObject4$4 = babelHelpers.taggedTemplateLiteral(["<div class='ui-entity-editor-content-block ui-ctl-custom'></div>"])));
	      main_core.Dom.append(this._innerWrapper, this._wrapper);
	      var deleteButtonBlock = main_core.Tag.render(_templateObject5$4 || (_templateObject5$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-delete-superset-button-block\"></div>\n\t\t"])));
	      babelHelpers.classPrivateFieldSet(this, _deleteButton, new ui_buttons.Button({
	        text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DELETE_SUPERSET_FIELD_DELETE_BUTTON'),
	        color: ui_buttons.ButtonColor.LIGHT_BORDER,
	        size: ui_buttons.ButtonSize.SMALL,
	        onclick: this.deleteSuperset.bind(this)
	      }));
	      babelHelpers.classPrivateFieldGet(this, _deleteButton).renderTo(deleteButtonBlock);

	      // Put the clear button into the section header
	      main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorSection:onLayout', function (event) {
	        if (event.data[1].id === 'DELETE_SUPERSET_SECTION') {
	          event.data[1].customNodes.push(deleteButtonBlock);
	        }
	      });
	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "deleteSuperset",
	    value: function deleteSuperset() {
	      var _this2 = this;
	      babelHelpers.classPrivateFieldSet(this, _deletePopup, new BX.BIConnector.ApacheSupersetCleanPopup({
	        onSuccess: function onSuccess() {
	          window.top.location.reload();
	        },
	        onAccept: function onAccept() {
	          babelHelpers.classPrivateFieldGet(_this2, _deleteButton).setClocking();
	        },
	        onError: function onError() {
	          babelHelpers.classPrivateFieldGet(_this2, _deleteButton).setClocking(false);
	        }
	      }));
	      babelHelpers.classPrivateFieldGet(this, _deletePopup).show();
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return DeleteSupersetField;
	}(BX.UI.EntityEditorList);

	var _templateObject$6, _templateObject2$5, _templateObject3$5, _templateObject4$5, _templateObject5$5;
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$5(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _clearCacheButton = /*#__PURE__*/new WeakMap();
	var _canClearCache = /*#__PURE__*/new WeakMap();
	var _clearTimeout = /*#__PURE__*/new WeakMap();
	var _initCacheTimer = /*#__PURE__*/new WeakSet();
	var _updateHintTimer = /*#__PURE__*/new WeakSet();
	var _clearCache = /*#__PURE__*/new WeakSet();
	var _initClearCacheButton = /*#__PURE__*/new WeakSet();
	var ClearCacheField = /*#__PURE__*/function (_BX$UI$EntityEditorCu) {
	  babelHelpers.inherits(ClearCacheField, _BX$UI$EntityEditorCu);
	  function ClearCacheField() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, ClearCacheField);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(ClearCacheField)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _initClearCacheButton);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _clearCache);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _updateHintTimer);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _initCacheTimer);
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _clearCacheButton, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _canClearCache, {
	      writable: true,
	      value: true
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _clearTimeout, {
	      writable: true,
	      value: 0
	    });
	    return _this;
	  }
	  babelHelpers.createClass(ClearCacheField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(ClearCacheField.prototype), "initialize", this).call(this, id, settings);
	      var fieldSettings = settings.model.getData();
	      babelHelpers.classPrivateFieldSet(this, _canClearCache, fieldSettings.canClearCache);
	      babelHelpers.classPrivateFieldSet(this, _clearTimeout, parseInt(fieldSettings.clearCacheTimeout, 10));
	      if (!babelHelpers.classPrivateFieldGet(this, _canClearCache)) {
	        _classPrivateMethodGet$2(this, _initCacheTimer, _initCacheTimer2).call(this);
	      }
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      this.ensureWrapperCreated({
	        classNames: ['ui-entity-editor-field-text']
	      });
	      this.adjustWrapper();
	      var message = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_HINT_LINK', {
	        '#HINT_LINK#': '<link></link>'
	      });
	      var hint = main_core.Tag.render(_templateObject$6 || (_templateObject$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-settings-panel-range__hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), message);
	      var link = main_core.Tag.render(_templateObject2$5 || (_templateObject2$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"biconnector-superset-settings-panel-range__hint-link\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_HINT_LINK'));
	      main_core.Event.bind(link, 'click', function () {
	        top.BX.Helper.show('redirect=detail&code=21000502');
	      });
	      main_core.Dom.replace(hint.querySelector('link'), link);
	      main_core.Dom.insertBefore(hint, this._container);
	      this._innerWrapper = main_core.Tag.render(_templateObject3$5 || (_templateObject3$5 = babelHelpers.taggedTemplateLiteral(["<div class='ui-entity-editor-content-block ui-ctl-custom'></div>"])));
	      main_core.Dom.append(this._innerWrapper, this._wrapper);
	      _classPrivateMethodGet$2(this, _initClearCacheButton, _initClearCacheButton2).call(this);
	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ClearCacheField;
	}(BX.UI.EntityEditorCustom);
	function _initCacheTimer2() {
	  var _this2 = this;
	  var timerContainer = main_core.Tag.render(_templateObject4$5 || (_templateObject4$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-cache-container\"></div>\n\t\t"])));
	  var timerProps = {
	    seconds: babelHelpers.classPrivateFieldGet(this, _clearTimeout),
	    node: timerContainer,
	    onTimerEnd: function onTimerEnd() {
	      babelHelpers.classPrivateFieldSet(_this2, _canClearCache, true);
	      babelHelpers.classPrivateFieldGet(_this2, _clearCacheButton).setDisabled(false);
	    },
	    onTimerUpdate: function onTimerUpdate(data) {
	      _classPrivateMethodGet$2(_this2, _updateHintTimer, _updateHintTimer2).call(_this2, data);
	    }
	  };
	  new ui_countdown.Countdown(timerProps);
	}
	function _updateHintTimer2(data) {
	  babelHelpers.classPrivateFieldSet(this, _clearTimeout, data.seconds);
	}
	function _clearCache2() {
	  var _this3 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _canClearCache)) {
	    return new Promise(function (resolve) {
	      resolve();
	    });
	  }
	  babelHelpers.classPrivateFieldGet(this, _clearCacheButton).setDisabled();
	  babelHelpers.classPrivateFieldSet(this, _canClearCache, false);
	  return main_core.ajax.runAction('biconnector.superset.clearCache').then(function (response) {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_SUCCESS'),
	      autoHideDelay: 2000
	    });
	    babelHelpers.classPrivateFieldSet(_this3, _clearTimeout, response.data.timeoutToNextClearCache);
	    _classPrivateMethodGet$2(_this3, _initCacheTimer, _initCacheTimer2).call(_this3);
	  })["catch"](function () {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_ERROR'),
	      autoHideDelay: 2000
	    });
	    babelHelpers.classPrivateFieldGet(_this3, _clearCacheButton).setDisabled(false);
	    babelHelpers.classPrivateFieldSet(_this3, _canClearCache, true);
	  });
	}
	function _initClearCacheButton2() {
	  var _this4 = this;
	  var buttonContainer = main_core.Tag.render(_templateObject5$5 || (_templateObject5$5 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	  babelHelpers.classPrivateFieldSet(this, _clearCacheButton, new ui_buttons.Button({
	    text: main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_BUTTON'),
	    color: ui_buttons.ButtonColor.LIGHT_BORDER,
	    size: ui_buttons.ButtonSize.SMALL,
	    onclick: _classPrivateMethodGet$2(this, _clearCache, _clearCache2).bind(this),
	    state: babelHelpers.classPrivateFieldGet(this, _canClearCache) ? null : ui_buttons.ButtonState.DISABLED
	  }));
	  babelHelpers.classPrivateFieldGet(this, _clearCacheButton).renderTo(buttonContainer);

	  // Put the clear button into the section header
	  main_core_events.EventEmitter.subscribe('BX.UI.EntityEditorSection:onLayout', function (event) {
	    if (event.data[1].id === 'CLEAR_CACHE_SECTION') {
	      event.data[1].customNodes.push(buttonContainer);
	    }
	  });
	  var node = babelHelpers.classPrivateFieldGet(this, _clearCacheButton).button;
	  var hint = BX.UI.Hint.createInstance({
	    popupParameters: {
	      offsetLeft: -60,
	      angle: {
	        offset: 160
	      }
	    }
	  });
	  main_core.Event.bind(node, 'mouseenter', function () {
	    babelHelpers.classPrivateFieldGet(_this4, _clearCacheButton).button.setAttribute('data-hint-no-icon', '');
	    if (babelHelpers.classPrivateFieldGet(_this4, _clearTimeout)) {
	      var minutesLeft = Math.ceil(parseInt(babelHelpers.classPrivateFieldGet(_this4, _clearTimeout), 10) / 60);
	      hint.show(node, main_core.Loc.getMessagePlural('BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_BUTTON_HINT_TIME_LEFT', minutesLeft, {
	        '#COUNT#': minutesLeft
	      }));
	    }
	  });
	  main_core.Event.bind(node, 'mouseleave', function () {
	    hint.hide(node);
	  });
	}

	var _templateObject$7, _templateObject2$6, _templateObject3$6, _templateObject4$6, _templateObject5$6, _templateObject6$4;
	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }
	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$6(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$6(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _scopes = /*#__PURE__*/new WeakMap();
	var _params = /*#__PURE__*/new WeakMap();
	var _scopeParamsMap = /*#__PURE__*/new WeakMap();
	var DashboardParamsField = /*#__PURE__*/function (_BX$UI$EntityEditorCu) {
	  babelHelpers.inherits(DashboardParamsField, _BX$UI$EntityEditorCu);
	  function DashboardParamsField() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, DashboardParamsField);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(DashboardParamsField)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _scopes, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _params, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _scopeParamsMap, {
	      writable: true,
	      value: void 0
	    });
	    return _this;
	  }
	  babelHelpers.createClass(DashboardParamsField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      var _this2 = this;
	      babelHelpers.get(babelHelpers.getPrototypeOf(DashboardParamsField.prototype), "initialize", this).call(this, id, settings);
	      babelHelpers.classPrivateFieldSet(this, _scopes, new Set());
	      var scopes = this._model.getField('SCOPE', []);
	      scopes.forEach(function (scopeCode) {
	        babelHelpers.classPrivateFieldGet(_this2, _scopes).add(scopeCode);
	      });
	      babelHelpers.classPrivateFieldSet(this, _params, new Set());
	      var params = this._model.getField('PARAMS', []);
	      params.forEach(function (param) {
	        babelHelpers.classPrivateFieldGet(_this2, _params).add(param);
	      });
	      babelHelpers.classPrivateFieldSet(this, _scopeParamsMap, this._model.getField('SCOPE_PARAMS_MAP', {}));
	      main_core_events.EventEmitter.subscribe('BIConnector.DashboardParamsSelector:onChange', this.onChange.bind(this));
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      this.ensureWrapperCreated({
	        classNames: ['ui-entity-editor-field-text']
	      });
	      this.adjustWrapper();
	      this._innerWrapper = main_core.Tag.render(_templateObject$7 || (_templateObject$7 = babelHelpers.taggedTemplateLiteral(["<div class='ui-entity-editor-content-block ui-ctl-custom'></div>"])));
	      var message = main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_PARAMS_HINT_LINK', {
	        '#HINT_LINK#': '<link></link>'
	      });
	      var hint = main_core.Tag.render(_templateObject2$6 || (_templateObject2$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-settings-panel-range__hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), message);
	      var link = main_core.Tag.render(_templateObject3$6 || (_templateObject3$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"biconnector-superset-settings-panel-range__hint-link\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_HINT_LINK'));
	      main_core.Event.bind(link, 'click', function () {
	        top.BX.Helper.show('redirect=detail&code=20337242&anchor=DashboardOwner');
	      });
	      main_core.Dom.replace(hint.querySelector('link'), link);
	      main_core.Dom.insertBefore(hint, this._container);
	      main_core.Dom.append(this._innerWrapper, this._wrapper);
	      var selectorParams = {
	        scopes: babelHelpers.classPrivateFieldGet(this, _scopes),
	        params: babelHelpers.classPrivateFieldGet(this, _params),
	        scopeParamsMap: babelHelpers.classPrivateFieldGet(this, _scopeParamsMap)
	      };
	      var selector = new biconnector_dashboardParametersSelector.DashboardParametersSelector(selectorParams);
	      main_core.Dom.append(selector.getLayout(), this._innerWrapper);
	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      if (main_core.Type.isDomNode(this._innerWrapper)) {
	        var oldSaveBlock = this._innerWrapper.querySelector('.save-block');
	        if (main_core.Type.isDomNode(oldSaveBlock)) {
	          main_core.Dom.remove(oldSaveBlock);
	        }
	        var saveBlock = main_core.Tag.render(_templateObject4$6 || (_templateObject4$6 = babelHelpers.taggedTemplateLiteral(["<div class=\"save-block\"></div>"])));
	        var _iterator = _createForOfIteratorHelper$1(babelHelpers.classPrivateFieldGet(this, _scopes)),
	          _step;
	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var scope = _step.value;
	            main_core.Dom.append(main_core.Tag.render(_templateObject5$6 || (_templateObject5$6 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"", "[SCOPE][]\" value=\"", "\">"])), this.getName(), scope), saveBlock);
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	        var _iterator2 = _createForOfIteratorHelper$1(babelHelpers.classPrivateFieldGet(this, _params)),
	          _step2;
	        try {
	          for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	            var param = _step2.value;
	            main_core.Dom.append(main_core.Tag.render(_templateObject6$4 || (_templateObject6$4 = babelHelpers.taggedTemplateLiteral(["<input type=\"hidden\" name=\"", "[PARAMS][]\" value=\"", "\">"])), this.getName(), param), saveBlock);
	          }
	        } catch (err) {
	          _iterator2.e(err);
	        } finally {
	          _iterator2.f();
	        }
	        main_core.Dom.append(saveBlock, this._innerWrapper);
	      }
	      this._model.setField(this.getName(), babelHelpers.classPrivateFieldGet(this, _scopes));
	    }
	  }, {
	    key: "onChange",
	    value: function onChange(params) {
	      var isChanged = params.data.isChanged;
	      if (isChanged) {
	        this.markAsChanged();
	        return;
	      }
	      this._isChanged = false;
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new this(id, settings);
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return DashboardParamsField;
	}(BX.UI.EntityEditorCustom);

	var FieldFactory = /*#__PURE__*/function () {
	  function FieldFactory() {
	    var _this = this;
	    var entityEditorControlFactory = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'BX.UI.EntityEditorControlFactory';
	    babelHelpers.classCallCheck(this, FieldFactory);
	    main_core_events.EventEmitter.subscribe("".concat(entityEditorControlFactory, ":onInitialize"), function (event) {
	      var _event$getCompatData = event.getCompatData(),
	        _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	        eventArgs = _event$getCompatData2[1];
	      eventArgs.methods.dashboardSettings = _this.factory.bind(_this);
	    });
	  }
	  babelHelpers.createClass(FieldFactory, [{
	    key: "factory",
	    value: function factory(type, controlId, settings) {
	      switch (type) {
	        case 'timePeriod':
	          return DateFilterField.create(controlId, settings);
	        case 'dashboardTimePeriod':
	          return DashboardDateFilterField.create(controlId, settings);
	        case 'keyInfo':
	          return KeyInfoField.create(controlId, settings);
	        case 'userNotificationSelector':
	          return UserNotificationField.create(controlId, settings);
	        case 'ownerSelector':
	          return DashboardOwnerField.create(controlId, settings);
	        case 'dashboardParametersSelector':
	          return DashboardParamsField.create(controlId, settings);
	        case 'deleteSuperset':
	          return DeleteSupersetField.create(controlId, settings);
	        case 'clearCache':
	          return ClearCacheField.create(controlId, settings);
	        default:
	          return null;
	      }
	    }
	  }]);
	  return FieldFactory;
	}();

	var SettingsPanel = /*#__PURE__*/function () {
	  function SettingsPanel() {
	    babelHelpers.classCallCheck(this, SettingsPanel);
	  }
	  babelHelpers.createClass(SettingsPanel, null, [{
	    key: "registerFieldFactory",
	    value: function registerFieldFactory(entityEditorControlFactory) {
	      new FieldFactory(entityEditorControlFactory);
	    }
	  }, {
	    key: "registerControllerFactory",
	    value: function registerControllerFactory(entityEditorControllerFactory) {
	      new ControllerFactory(entityEditorControllerFactory);
	    }
	  }]);
	  return SettingsPanel;
	}();

	exports.SettingsPanel = SettingsPanel;

}((this.BX.BIConnector.ApacheSuperset = this.BX.BIConnector.ApacheSuperset || {}),BX.BIConnector,BX,BX,BX.UI.EntitySelector,BX.UI,BX,BX,BX.Event,BX,BX.UI,BX.BIConnector));
//# sourceMappingURL=script.js.map
