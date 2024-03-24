/* eslint-disable */
this.BX = this.BX || {};
this.BX.BIConnector = this.BX.BIConnector || {};
(function (exports,biconnector_apacheSupersetAnalytics,ui_iconSet_main,ui_designTokens,main_core_events,main_core,ui_notification) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var SidePanel = BX.SidePanel;
	var _reloadParent = /*#__PURE__*/new WeakSet();
	var SettingController = /*#__PURE__*/function (_BX$UI$EntityEditorCo) {
	  babelHelpers.inherits(SettingController, _BX$UI$EntityEditorCo);
	  function SettingController(id, settings) {
	    var _settings$config$dash, _settings$config;
	    var _this;
	    babelHelpers.classCallCheck(this, SettingController);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SettingController).call(this));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _reloadParent);
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
	      _classPrivateMethodGet(this, _reloadParent, _reloadParent2).call(this);
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
	function _reloadParent2() {
	  var _parent$BX$Main$gridM;
	  var previousSlider = BX.SidePanel.Instance.getPreviousSlider(BX.SidePanel.Instance.getSliderByWindow(window));
	  var parent = previousSlider ? previousSlider.getWindow() : top;
	  if (!parent.BX.Main || !parent.BX.Main.gridManager) {
	    return;
	  }
	  var gridInstance = (_parent$BX$Main$gridM = parent.BX.Main.gridManager.getById('biconnector_superset_dashboard_grid')) === null || _parent$BX$Main$gridM === void 0 ? void 0 : _parent$BX$Main$gridM.instance;
	  if (gridInstance) {
	    gridInstance.reload();
	  }
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

	var _templateObject$1, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8;
	var DateFilterField = /*#__PURE__*/function (_BX$UI$EntityEditorLi) {
	  babelHelpers.inherits(DateFilterField, _BX$UI$EntityEditorLi);
	  function DateFilterField(id, settings) {
	    var _this;
	    babelHelpers.classCallCheck(this, DateFilterField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DateFilterField).call(this));
	    _this.dateSelectorBlock = null;
	    _this.toInput = null;
	    _this.startInput = null;
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
	      }
	      if (isRangeSelected) {
	        main_core.Dom.removeClass(this._selectContainer, 'ui-ctl-w100');
	        main_core.Dom.addClass(this._innerWrapper, 'ui-entity-editor-content-block__range');
	        main_core.Dom.addClass(this._selectContainer, 'ui-ctl-date-range');
	        this.dateSelectorBlock = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-ctl-dropdown-range-group\"></div>"])));
	        var dateStartValue = main_core.Text.encode(this.getModel().getField(this.getDateStartFieldName()));
	        this.startInput = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<input class=\"ui-ctl-element\" type=\"text\" value=\"", "\" name=\"", "\">"])), dateStartValue, this.getDateStartFieldName());
	        main_core.Event.bind(this.startInput, 'click', function () {
	          DateFilterField.showCalendar(_this2.startInput);
	        });
	        main_core.Event.bind(this.startInput, 'change', function () {
	          _this2.onChange();
	        });
	        main_core.Event.bind(this.startInput, 'input', function () {
	          _this2.onChange();
	        });
	        main_core.Dom.append(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-before-icon ui-ctl-datetime\">\n\t\t\t\t\t\t<div class=\"ui-ctl-before ui-ctl-icon-calendar\"></div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), this.startInput), this.dateSelectorBlock);
	        main_core.Dom.append(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-ctl-dropdown-range-line\">\n\t\t\t\t\t\t<span class=\"ui-ctl-dropdown-range-line-item\"></span>\n\t\t\t\t\t</div>\n\t\t\t\t"]))), this.dateSelectorBlock);
	        var dateEndValue = main_core.Text.encode(this.getModel().getField(this.getDateEndFieldName()));
	        this.endInput = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<input class=\"ui-ctl-element\" type=\"text\" value=\"", "\" name=\"", "\">"])), dateEndValue, this.getDateEndFieldName());
	        main_core.Event.bind(this.endInput, 'click', function () {
	          DateFilterField.showCalendar(_this2.endInput);
	        });
	        main_core.Event.bind(this.endInput, 'change', function () {
	          _this2.onChange();
	        });
	        main_core.Event.bind(this.endInput, 'input', function () {
	          _this2.onChange();
	        });
	        main_core.Dom.append(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-before-icon ui-ctl-datetime\">\n\t\t\t\t\t\t<div class=\"ui-ctl-before ui-ctl-icon-calendar\"></div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), this.endInput), this.dateSelectorBlock);
	        main_core.Dom.append(this.dateSelectorBlock, this._innerWrapper);
	      } else {
	        main_core.Dom.addClass(this._selectContainer, 'ui-ctl-w100');
	        main_core.Dom.removeClass(this._innerWrapper, 'ui-entity-editor-content-block__range');
	        main_core.Dom.removeClass(this._selectContainer, 'ui-ctl-date-range');
	      }
	    }
	  }, {
	    key: "layoutHint",
	    value: function layoutHint() {
	      var hintContainer = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"biconnector-superset-settings-panel-range__hint\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.getHintText());
	      main_core.Dom.insertBefore(hintContainer, this._container);
	    }
	  }, {
	    key: "getHintText",
	    value: function getHintText() {
	      var hintLink = "\n\t\t\t<a \n\t\t\t\tclass=\"biconnector-superset-settings-panel-range__hint-link\"\n\t\t\t\tonclick=\"top.BX.Helper.show('redirect=detail&code=19123608')\"\n\t\t\t>\n\t\t\t\t".concat(main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_FIELD_HINT_LINK'), "\n\t\t\t</a>\n\t\t");
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
	    key: "save",
	    value: function save() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(DateFilterField.prototype), "save", this).call(this);
	      this._model.setField(this.getDateStartFieldName(), null);
	      this._model.setField(this.getDateEndFieldName(), null);
	      if (main_core.Type.isDomNode(this.endInput)) {
	        this._model.setField(this.getDateEndFieldName(), this.endInput.value);
	      }
	      if (main_core.Type.isDomNode(this.startInput)) {
	        this._model.setField(this.getDateStartFieldName(), this.startInput.value);
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

	var _templateObject$2, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5$1, _templateObject6$1, _templateObject7$1, _templateObject8$1, _templateObject9;
	var KeyInfoField = /*#__PURE__*/function (_BX$UI$EntityEditorCu) {
	  babelHelpers.inherits(KeyInfoField, _BX$UI$EntityEditorCu);
	  function KeyInfoField() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, KeyInfoField);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(KeyInfoField)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "refreshKeyLock", false);
	    return _this;
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
	        top.BX.Helper.show('redirect=detail&code=19123608');
	      });
	      main_core.Dom.replace(hint.querySelector('link'), link);
	      main_core.Dom.insertBefore(hint, this._container);
	      this._innerWrapper = main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["<div class='ui-entity-editor-content-block ui-ctl-custom biconnector-superset-settings-panel-key-info-container'></div>"])));
	      main_core.Dom.append(this._innerWrapper, this._wrapper);
	      var value = main_core.Text.encode(this.getValue());
	      this.keyInput = main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"password\" class=\"ui-ctl-element\" readonly value=\"", "\">\n\t\t"])), value);
	      this.eyeButton = main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-btn-link ui-btn\">\n\t\t\t\t<span class=\"ui-icon-set --crossed-eye\"></span>\n\t\t\t</button>\n\t\t"])));
	      main_core.Event.bind(this.eyeButton, 'click', this.toggleKey.bind(this));
	      var copyButton = main_core.Tag.render(_templateObject7$1 || (_templateObject7$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-btn-link ui-btn\">\n\t\t\t\t<span class=\"ui-icon-set --copy-plates\"></span>\n\t\t\t</button>\n\t\t"])));
	      main_core.Event.bind(copyButton, 'click', this.copyText.bind(this));
	      var content = main_core.Tag.render(_templateObject8$1 || (_templateObject8$1 = babelHelpers.taggedTemplateLiteral(["\t\n\t\t\t<div class=\"ui-ctl ui-ctl__combined-input ui-ctl-w100\">\n\t\t\t\t<div class=\"ui-ctl-icon__set ui-ctl-after\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.eyeButton, copyButton, this.keyInput);
	      main_core.Dom.append(content, this._innerWrapper);
	      this.refreshButton = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-btn-primary ui-btn icon-set-element\">\n\t\t\t\t\t<div class=\"ui-icon-set --refresh-7\"></div>\n\t\t\t\t\t<div class=\"icon-set-element__class\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t</button>\n\t\t"])), main_core.Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_KEY_FIELD_REFRESH_BUTTON'));
	      main_core.Event.bind(this.refreshButton, 'click', this.refreshKey.bind(this));
	      main_core.Dom.append(this.refreshButton, this._innerWrapper);
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
	      var _this2 = this;
	      if (this.refreshKeyLock) {
	        return;
	      }
	      this.refreshKeyLock = true;
	      main_core.Dom.addClass(this.refreshButton, 'ui-btn-disabled');
	      main_core.ajax.runComponentAction('bitrix:biconnector.apachesuperset.setting', 'changeBiToken', {
	        mode: 'class'
	      }).then(function (response) {
	        var generatedKey = response.data;
	        if (main_core.Type.isStringFilled(generatedKey)) {
	          _this2.keyInput.value = main_core.Text.encode(generatedKey);
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
	        _this2.refreshKeyLock = false;
	        main_core.Dom.removeClass(_this2.refreshButton, 'ui-btn-disabled');
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

	var FieldFactory = /*#__PURE__*/function () {
	  function FieldFactory() {
	    var _this = this;
	    var entityEditorControlFactory = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'BX.UI.EntityEditorControlFactory';
	    babelHelpers.classCallCheck(this, FieldFactory);
	    main_core_events.EventEmitter.subscribe(entityEditorControlFactory + ':onInitialize', function (event) {
	      var _event$getCompatData = event.getCompatData(),
	        _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	        eventArgs = _event$getCompatData2[1];
	      eventArgs.methods['dashboardSettings'] = _this.factory.bind(_this);
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

}((this.BX.BIConnector.ApacheSuperset = this.BX.BIConnector.ApacheSuperset || {}),BX.BIConnector,BX,BX,BX.Event,BX,BX));
//# sourceMappingURL=script.js.map
