this.BX = this.BX || {};
(function (exports,ui_dialogs_messagebox,main_popup,ui_progressround,main_loader,date,sign_ui,color_picker,ui_stamp_uploader,sign_backend,crm_form_fields_selector,crm_requisite_fieldsetViewer,sign_document,main_core,ui_draganddrop_draggable,main_core_events,ui_buttons,sign_tour) {
	'use strict';

	var _templateObject;
	var Dummy = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Dummy, _EventEmitter);
	  /**
	   * Constructor.
	   * @param {Block} block
	   */
	  function Dummy(block) {
	    var _this;
	    babelHelpers.classCallCheck(this, Dummy);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Dummy).call(this));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "data", {});
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "events", {
	      onChange: 'onChange',
	      onColorStyleChange: 'onColorStyleChange'
	    });
	    _this.setEventNamespace('BX.Sign.Blocks.Dummy');
	    _this.block = block;
	    return _this;
	  }

	  /**
	   * Returns true if block is in singleton mode.
	   * @return {boolean}
	   */
	  babelHelpers.createClass(Dummy, [{
	    key: "isSingleton",
	    value: function isSingleton() {
	      return false;
	    }
	    /**
	     * Returns true if style panel mast be showed.
	     * @return {boolean}
	     */
	  }, {
	    key: "isStyleAllowed",
	    value: function isStyleAllowed() {
	      return true;
	    }
	    /**
	     * Sets new data.
	     * @param {any} data
	     */
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      this.data = data ? data : {};
	    }
	    /**
	     * Changes only text key in data.
	     * @param {string} text
	     */
	  }, {
	    key: "setText",
	    value: function setText(text) {
	      this.setData({
	        text: text
	      });
	    }
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	  }, {
	    key: "getInitDimension",
	    value: function getInitDimension() {
	      return {
	        width: 250,
	        height: 28
	      };
	    }
	    /**
	     * Returns current data.
	     * @return {any}
	     */
	  }, {
	    key: "getData",
	    value: function getData() {
	      if (this.data.base64) {
	        this.data.base64 = null;
	      }
	      return this.data;
	    }
	    /**
	     * Returns action button for edit content.
	     * @return {HTMLElement | null}
	     */
	  }, {
	    key: "getActionButton",
	    value: function getActionButton() {
	      return null;
	    }
	    /**
	     * @return {HTMLElement | null}
	     */
	  }, {
	    key: "getBlockCaption",
	    value: function getBlockCaption() {
	      return null;
	    }
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	  }, {
	    key: "getViewContent",
	    value: function getViewContent() {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(this.data.text || '').toString().replaceAll('[br]', '<br>'));
	    }
	    /**
	     * Calls when block starts being resized or moved.
	     */
	  }, {
	    key: "onStartChangePosition",
	    value: function onStartChangePosition() {}
	    /**
	     * Calls when block has placed on document.
	     */
	  }, {
	    key: "onPlaced",
	    value: function onPlaced() {}
	    /**
	     * Calls when block saved.
	     */
	  }, {
	    key: "onSave",
	    value: function onSave() {}
	    /**
	     * Calls when block removed.
	     */
	  }, {
	    key: "onRemove",
	    value: function onRemove() {}
	    /**
	     * Calls when click was out the block.
	     */
	  }, {
	    key: "onClickOut",
	    value: function onClickOut() {
	      this.block.forceSave();
	    }
	  }, {
	    key: "onChange",
	    value: function onChange() {
	      this.emit(this.events.onChange);
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return {};
	    }
	  }, {
	    key: "onStyleChange",
	    value: function onStyleChange() {
	      this.emit(this.events.onColorStyleChange);
	    }
	  }, {
	    key: "onStyleRender",
	    value: function onStyleRender(styles) {}
	  }]);
	  return Dummy;
	}(main_core_events.EventEmitter);
	babelHelpers.defineProperty(Dummy, "defaultTextBlockPaddingStyles", {
	  padding: '5px 8px'
	});

	var BlockWithSynchronizableStyleColor = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(BlockWithSynchronizableStyleColor, _Dummy);
	  function BlockWithSynchronizableStyleColor() {
	    babelHelpers.classCallCheck(this, BlockWithSynchronizableStyleColor);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(BlockWithSynchronizableStyleColor).apply(this, arguments));
	  }
	  babelHelpers.createClass(BlockWithSynchronizableStyleColor, [{
	    key: "updateColor",
	    value: function updateColor(color) {}
	  }]);
	  return BlockWithSynchronizableStyleColor;
	}(Dummy);

	var _templateObject$1;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _calendar = /*#__PURE__*/new WeakMap();
	var _calendarOpened = /*#__PURE__*/new WeakMap();
	var _date = /*#__PURE__*/new WeakMap();
	var _closeCalendar = /*#__PURE__*/new WeakSet();
	var Date = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(Date, _Dummy);
	  function Date() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, Date);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Date)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _closeCalendar);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _calendar, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _calendarOpened, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _date, {
	      writable: true,
	      value: void 0
	    });
	    return _this;
	  }
	  babelHelpers.createClass(Date, [{
	    key: "getInitDimension",
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	    value: function getInitDimension() {
	      return {
	        width: 105,
	        height: 28
	      };
	    }
	    /**
	     * Sets new data.
	     * @param {any} data
	     */
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      this.data = data ? data : {};
	      babelHelpers.classPrivateFieldSet(this, _date, this.data.text);
	    }
	    /**
	     * Calls when action button was clicked.
	     */
	  }, {
	    key: "onActionClick",
	    value: function onActionClick() {
	      var _this2 = this;
	      babelHelpers.classPrivateFieldSet(this, _calendar, BX.calendar({
	        node: this.block.getLayout(),
	        field: this.getViewContent(),
	        value: babelHelpers.classPrivateFieldGet(this, _date),
	        bTime: false,
	        callback_after: function callback_after(date$$1) {
	          _this2.setText(BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')), date$$1, null));
	          _this2.block.renderView();
	        }
	      }));
	      babelHelpers.classPrivateFieldSet(this, _calendarOpened, true);
	    }
	    /**
	     * Returns action button for edit content.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getActionButton",
	    value: function getActionButton() {
	      return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-style-btn --funnel\">\n\t\t\t\t<button onclick=\"", "\" data-role=\"action\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), this.onActionClick.bind(this), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_DATE_ACTION_BUTTON'));
	    }
	    /**
	     * Closes calendar if open.
	     */
	  }, {
	    key: "onStartChangePosition",
	    /**
	     * Calls when block starts being resized or moved.
	     */
	    value: function onStartChangePosition() {
	      _classPrivateMethodGet(this, _closeCalendar, _closeCalendar2).call(this);
	    }
	    /**
	     * Calls when block saved.
	     */
	  }, {
	    key: "onSave",
	    value: function onSave() {
	      _classPrivateMethodGet(this, _closeCalendar, _closeCalendar2).call(this);
	    }
	    /**
	     * Calls when block removed.
	     */
	  }, {
	    key: "onRemove",
	    value: function onRemove() {
	      _classPrivateMethodGet(this, _closeCalendar, _closeCalendar2).call(this);
	    }
	    /**
	     * Calls when click was out the block.
	     */
	  }, {
	    key: "onClickOut",
	    value: function onClickOut() {
	      if (!babelHelpers.classPrivateFieldGet(this, _calendarOpened)) {
	        this.block.forceSave();
	      }
	      babelHelpers.classPrivateFieldSet(this, _calendarOpened, false);
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return _objectSpread(_objectSpread({}, babelHelpers.get(babelHelpers.getPrototypeOf(Date.prototype), "getStyles", this).call(this)), Date.defaultTextBlockPaddingStyles);
	    }
	  }]);
	  return Date;
	}(Dummy);
	function _closeCalendar2() {
	  if (babelHelpers.classPrivateFieldGet(this, _calendar)) {
	    babelHelpers.classPrivateFieldGet(this, _calendar).Close();
	    babelHelpers.classPrivateFieldSet(this, _calendarOpened, false);
	  }
	}

	var _templateObject$2;
	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _onActionClick = /*#__PURE__*/new WeakSet();
	var MyRequisites = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(MyRequisites, _Dummy);
	  function MyRequisites() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, MyRequisites);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(MyRequisites)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onActionClick);
	    return _this;
	  }
	  babelHelpers.createClass(MyRequisites, [{
	    key: "isSingleton",
	    /**
	     * Returns true if block is in singleton mode.
	     * @return {boolean}
	     */
	    value: function isSingleton() {
	      return true;
	    }
	    /**
	     * Returns current data.
	     * @return {any}
	     */
	  }, {
	    key: "getData",
	    value: function getData() {
	      var data = this.data;
	      if (data.text) {
	        data.text = null;
	      }
	      return data;
	    }
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	  }, {
	    key: "getInitDimension",
	    value: function getInitDimension() {
	      return {
	        width: 250,
	        height: 220
	      };
	    }
	    /**
	     * Calls when action button was clicked.
	     * @param {PointerEvent} event
	     */
	  }, {
	    key: "getActionButton",
	    /**
	     * Returns action button for edit content.
	     * @return {HTMLElement}
	     */
	    value: function getActionButton() {
	      return main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-style-btn --funnel\">\n\t\t\t\t<button onclick=\"", "\" data-role=\"action\" data-id=\"action-", "\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$1(this, _onActionClick, _onActionClick2).bind(this), this.block.getCode(), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_ACTION_BUTTON'));
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return _objectSpread$1(_objectSpread$1({}, babelHelpers.get(babelHelpers.getPrototypeOf(MyRequisites.prototype), "getStyles", this).call(this)), MyRequisites.defaultTextBlockPaddingStyles);
	    }
	  }]);
	  return MyRequisites;
	}(Dummy);
	function _onActionClick2(event) {
	  var _this2 = this;
	  var document = this.block.getDocument();
	  var config = document.getConfig();
	  event.stopPropagation();
	  new crm_requisite_fieldsetViewer.FieldsetViewer({
	    entityTypeId: config.crmOwnerTypeCompany,
	    entityId: document.getCompanyId(),
	    events: {
	      onClose: function onClose() {
	        _this2.block.assign();
	      }
	    },
	    fieldListEditorOptions: {
	      fieldsPanelOptions: {
	        filter: {
	          '+categories': ['COMPANY'],
	          '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'address', 'url', 'money', 'boolean', 'double']
	        },
	        presetId: config.crmRequisiteCompanyPresetId,
	        controllerOptions: {
	          hideVirtual: 1,
	          hideRequisites: 0,
	          hideSmartDocument: 1
	        }
	      }
	    }
	  }).show();
	}

	var _templateObject$3, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9;
	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var ColorPicker = main_core.Reflection.getClass('BX.ColorPicker');
	var UI = /*#__PURE__*/function () {
	  function UI() {
	    babelHelpers.classCallCheck(this, UI);
	  }
	  babelHelpers.createClass(UI, null, [{
	    key: "setRect",
	    /**
	     * Sets width/height/top/left to element.
	     * @param {HTMLElement} element
	     * @param {{[key: string]: number}} rect
	     */
	    value: function setRect(element, rect) {
	      Object.keys(rect).map(function (key) {
	        rect[key] = parseInt(rect[key]) + 'px';
	      });
	      main_core.Dom.style(element, rect);
	    }
	    /**
	     * Returns block's layout.
	     * @param {BlockLayoutOptions} options Layout options.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getBlockLayout",
	    value: function getBlockLayout(options) {
	      return main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-wrapper\">\n\t\t\t\t<div class=\"sign-document__block-panel--wrapper\" data-role=\"sign-block__actions\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"sign-document__block-content\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"sign-document__block-actions\">\n\t\t\t\t\t<div class=\"sign-document__block-actions--wrapper\">\n\t\t\t\t\t\t<button class=\"sign-document__block-actions-btn --remove sign-block-action-remove\" data-role=\"removeAction\" onclick=\"", "\"></button>\n\t\t\t\t\t\t<button class=\"sign-document__block-actions-btn --save sign-block-action-save\" data-role=\"saveAction\" onclick=\"", "\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), options.onRemove, options.onSave);
	    }
	    /**
	     * Returns member selector for block.
	     * @param {Array<MemberItem>} members All document's members.
	     * @param {number} selectedValue Selected member.
	     * @param {() => {}} onChange Handler on change value.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getMemberSelector",
	    value: function getMemberSelector(members, selectedValue, onChange) {
	      var menuItems = {};
	      var selectedName = main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NAME_NOT_SET');
	      members.map(function (member) {
	        member.name = member.name || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NAME_NOT_SET');
	        menuItems[member.part] = member.name;
	        if (member.part === selectedValue) {
	          selectedName = member.name;
	        }
	      });
	      var memberSelector = members.length > 1 ? main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<span>", "</span>"])), main_core.Text.encode(selectedName)) : main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<i>", "</i>"])), main_core.Text.encode(selectedName));
	      if (members.length > 1) {
	        sign_ui.UI.bindSimpleMenu({
	          bindElement: memberSelector,
	          items: menuItems,
	          actionHandler: function actionHandler(value) {
	            memberSelector.innerHTML = menuItems[value];
	            onChange(parseInt(value));
	          }
	        });
	      }
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document-block-member-wrapper\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), memberSelector);
	    }
	    /**
	     * Returns resizing area's layout.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getResizeArea",
	    value: function getResizeArea() {
	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__resize-area\">\n\t\t\t\t<div class=\"sign-area-resizable-controls\">\n\t\t\t\t\t<span class=\"sign-document__move-control\"></span>\n\t\t\t\t\t<div class=\"sign-document__resize-control --middle-top\"></div>\n\t\t\t\t\t<div class=\"sign-document__resize-control --right-top\"></div>\n\t\t\t\t\t<div class=\"sign-document__resize-control --middle-right\"></div>\n\t\t\t\t\t<div class=\"sign-document__resize-control --right-bottom\"></div>\n\t\t\t\t\t<div class=\"sign-document__resize-control --middle-bottom\"></div>\n\t\t\t\t\t<div class=\"sign-document__resize-control --left-bottom\"></div>\n\t\t\t\t\t<div class=\"sign-document__resize-control --middle-left\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])));
	    }
	    /**
	     * Returns style panel layout.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getStylePanel",
	    value: function getStylePanel(_actionHandler, collectStyles) {
	      var _collectStyles$color;
	      // font family selector
	      var fonts = {
	        '"Times New Roman", Times': '<span style="font-family: \'Times New Roman\', Times">Times New Roman</span>',
	        '"Courier New"': '<span style="font-family: \'Courier New\'">Courier New</span>',
	        'Arial, Helvetica': '<span style="font-family: Arial, Helvetica">Arial / Helvetica</span>',
	        '"Arial Black", Gadget': '<span style="font-family: \'Arial Black\', Gadget">Arial Black</span>',
	        'Tahoma, Geneva': '<span style="font-family: Tahoma, Geneva">Tahoma / Geneva</span>',
	        'Verdana': '<span style="font-family: Verdana">Verdana</span>',
	        'Georgia, serif': '<span style="font-family: Georgia, serif">Georgia</span>',
	        'monospace': '<span style="font-family: monospace">monospace</span>'
	      };
	      var fontFamily = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-document__block-style-btn --btn-font-family\">", "</div>"])), fonts[collectStyles['font-family']] || 'Font');
	      sign_ui.UI.bindSimpleMenu({
	        bindElement: fontFamily,
	        items: fonts,
	        actionHandler: function actionHandler(value) {
	          fontFamily.innerHTML = fonts[value];
	          _actionHandler('family', value);
	        }
	      });

	      // font size selector

	      var fontSizereal = parseInt(collectStyles['font-size']);
	      var fontSizeValue = 14;
	      if (fontSizereal) {
	        fontSizeValue = fontSizereal;
	      }
	      var fontSize = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-document__block-style-btn --btn-fontsize\">", "</div>"])), fontSizeValue + 'px' || '<i></i>');
	      sign_ui.UI.bindSimpleMenu({
	        bindElement: fontSize,
	        items: ['6px', '7px', '8px', '9px', '10px', '11px', '12px', '13px', '14px', '15px', '16px', '18px', '20px', '22px', '24px', '26px', '28px', '36px', '48px', '72px'],
	        actionHandler: function actionHandler(value) {
	          fontSize.innerHTML = parseInt(value) + 'px';
	          _actionHandler('size', value);
	        },
	        currentValue: fontSizeValue
	      });

	      // color
	      var _UI$getColorSelectorB = UI.getColorSelectorBtn((_collectStyles$color = collectStyles.color) !== null && _collectStyles$color !== void 0 ? _collectStyles$color : '#000', function (color) {
	          return _actionHandler('color', color);
	        }),
	        fontColor = _UI$getColorSelectorB.layout;
	      return main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-style--panel\">\n<!--\t\t\t\t<div class=\"sign-document__block-style&#45;&#45;move-control\"></div>-->\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t<div class=\"sign-document__block-style--separator\"></div>\n\t\t\t\t<div class=\"sign-document__block-style-btn --btn-bold\" data-action=\"bold\"><i></i></div>\n\t\t\t\t<div class=\"sign-document__block-style-btn --btn-italic\" data-action=\"italic\"><i></i></div>\n\t\t\t\t<div class=\"sign-document__block-style-btn --btn-underline\" data-action=\"underline\"><i></i></div>\n\t\t\t\t<div class=\"sign-document__block-style-btn --btn-strike\" data-action=\"through\"><i></i></div>\n\t\t\t\t<div class=\"sign-document__block-style-btn --btn-align-left\" data-action=\"left\"><i></i></div>\n\t\t\t\t<div class=\"sign-document__block-style-btn --btn-align-center\" data-action=\"center\"><i></i></div>\n\t\t\t\t<div class=\"sign-document__block-style-btn --btn-align-right\" data-action=\"right\"><i></i></div>\n\t\t\t\t<div class=\"sign-document__block-style-btn --btn-align-justify\" data-action=\"justify\"><i></i></div>\n\t\t\t</div>\n\t\t"])), fontFamily, fontSize, fontColor);
	    }
	  }, {
	    key: "getColorSelectorBtn",
	    value: function getColorSelectorBtn(defaultColorPickerColor, onColorSelect) {
	      var colorPickerOptions = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      var layout = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-document__block-style-btn --btn-color\">\n\t\t\t\t<span class=\"sign-document__block-style-btn--color-block\"></span> \n\t\t\t\t<span>", "</span>\n\t\t\t</div>"])), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_STYLE_COLOR'));
	      UI.updateColorSelectorBtnColor(layout, defaultColorPickerColor);
	      var updatedColorPickerOptions = _objectSpread$2(_objectSpread$2({}, colorPickerOptions), {}, {
	        bindElement: layout,
	        onColorSelected: function onColorSelected(color) {
	          onColorSelect(color);
	          UI.updateColorSelectorBtnColor(layout, color);
	        }
	      });
	      var picker = new ColorPicker(updatedColorPickerOptions);
	      main_core.Event.bind(layout, 'click', function () {
	        picker.open();
	      });
	      return {
	        layout: layout,
	        colorPicker: picker
	      };
	    }
	  }, {
	    key: "updateColorSelectorBtnColor",
	    value: function updateColorSelectorBtnColor(layout, color) {
	      var circleColor = layout.querySelector('.sign-document__block-style-btn--color-block');
	      if (main_core.Type.isNil(circleColor)) {
	        return;
	      }
	      main_core.Dom.style(circleColor, 'background-color', color);
	    }
	  }]);
	  return UI;
	}();

	var _templateObject$4, _templateObject2$1, _templateObject3$1;
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cache = /*#__PURE__*/new WeakMap();
	var _defaultSignatureColor = /*#__PURE__*/new WeakMap();
	var _availableSignatureColors = /*#__PURE__*/new WeakMap();
	var _selectedSignatureColor = /*#__PURE__*/new WeakMap();
	var _getColorSelectorBtn = /*#__PURE__*/new WeakSet();
	var _getSignatureColor = /*#__PURE__*/new WeakSet();
	var _closeColorPickerPopup = /*#__PURE__*/new WeakSet();
	var MySign = /*#__PURE__*/function (_BlockWithSynchroniza) {
	  babelHelpers.inherits(MySign, _BlockWithSynchroniza);
	  function MySign() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, MySign);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(MySign)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _closeColorPickerPopup);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getSignatureColor);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getColorSelectorBtn);
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _defaultSignatureColor, {
	      writable: true,
	      value: '#0047ab'
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _availableSignatureColors, {
	      writable: true,
	      value: ['#000', babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _defaultSignatureColor), '#8b00ff']
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _selectedSignatureColor, {
	      writable: true,
	      value: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(MySign, [{
	    key: "isSingleton",
	    /**
	     * Returns true if block is in singleton mode.
	     * @return {boolean}
	     */
	    value: function isSingleton() {
	      return true;
	    }
	    /**
	     * Returns true if style panel mast be showed.
	     * @return {boolean}
	     */
	  }, {
	    key: "isStyleAllowed",
	    value: function isStyleAllowed() {
	      return false;
	    }
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	  }, {
	    key: "getInitDimension",
	    value: function getInitDimension() {
	      return {
	        width: 200,
	        height: 70
	      };
	    }
	  }, {
	    key: "updateColor",
	    value: function updateColor(color) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(MySign.prototype), "updateColor", this).call(this, color);
	      var _classPrivateMethodGe = _classPrivateMethodGet$2(this, _getColorSelectorBtn, _getColorSelectorBtn2).call(this),
	        layout = _classPrivateMethodGe.layout,
	        colorPicker = _classPrivateMethodGe.colorPicker;
	      babelHelpers.classPrivateFieldSet(this, _selectedSignatureColor, color);
	      UI.updateColorSelectorBtnColor(layout, color);
	      colorPicker.setSelectedColor(color);
	    }
	    /**
	     * @return {HTMLElement | null}
	     */
	  }, {
	    key: "getBlockCaption",
	    value: function getBlockCaption() {
	      var _classPrivateMethodGe2 = _classPrivateMethodGet$2(this, _getColorSelectorBtn, _getColorSelectorBtn2).call(this),
	        colorSelectorBtnLayout = _classPrivateMethodGe2.layout;
	      return main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div style=\"display: flex; flex-direction: row;\">\n\t\t\t", "\n\t\t\t<div class=\"sign-document__block-style--separator\"></div>\n\t\t\t<div class=\"sign-document-block-member-wrapper\">\n\t\t\t\t<i>", "</i>\n\t\t\t</div>\n\t\t\t</div>\n\t\t"])), colorSelectorBtnLayout, main_core.Loc.getMessage('SIGN_JS_DOCUMENT_SIGN_ACTION_BUTTON'));
	    }
	  }, {
	    key: "getViewContent",
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	    value: function getViewContent() {
	      var _this$block$getPositi = this.block.getPosition(),
	        width = _this$block$getPositi.width,
	        height = _this$block$getPositi.height;
	      var src = null;
	      if (this.dataSrc) {
	        src = this.dataSrc;
	      } else if (this.data.base64) {
	        src = 'data:image;base64,' + this.data.base64;
	      }
	      if (src) {
	        return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<img src=\"", "\" alt=\"\">\n\t\t\t"])), src);
	      } else {
	        return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document__block-content_member-nodata\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Text.encode(this.data.text || this.getPlaceholderLabel()));
	      }
	    }
	    /**
	     * Returns placeholder's label.
	     * @return {string}
	     */
	  }, {
	    key: "getPlaceholderLabel",
	    value: function getPlaceholderLabel() {
	      return main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_MY_SIGN');
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return {
	        'background-position': 'center !important',
	        color: _classPrivateMethodGet$2(this, _getSignatureColor, _getSignatureColor2).call(this)
	      };
	    }
	  }, {
	    key: "onSave",
	    value: function onSave() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(MySign.prototype), "onSave", this).call(this);
	      _classPrivateMethodGet$2(this, _closeColorPickerPopup, _closeColorPickerPopup2).call(this);
	    }
	  }, {
	    key: "onClickOut",
	    value: function onClickOut() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(MySign.prototype), "onClickOut", this).call(this);
	      _classPrivateMethodGet$2(this, _closeColorPickerPopup, _closeColorPickerPopup2).call(this);
	    }
	  }, {
	    key: "onStyleRender",
	    value: function onStyleRender(styles) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(MySign.prototype), "onStyleRender", this).call(this, styles);
	      if (!main_core.Type.isNil(styles.color)) {
	        this.updateColor(styles.color);
	      }
	    }
	  }]);
	  return MySign;
	}(BlockWithSynchronizableStyleColor);
	function _getColorSelectorBtn2() {
	  var _this2 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('colorSelectorBtn', function () {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    return UI.getColorSelectorBtn((_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(_this2, _selectedSignatureColor)) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : babelHelpers.classPrivateFieldGet(_this2, _defaultSignatureColor), function (color) {
	      babelHelpers.classPrivateFieldSet(_this2, _selectedSignatureColor, color);
	      _this2.onStyleChange();
	    }, {
	      colors: [babelHelpers.classPrivateFieldGet(_this2, _availableSignatureColors)],
	      allowCustomColor: false,
	      selectedColor: (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(_this2, _selectedSignatureColor)) !== null && _babelHelpers$classPr2 !== void 0 ? _babelHelpers$classPr2 : babelHelpers.classPrivateFieldGet(_this2, _defaultSignatureColor),
	      colorPreview: false
	    });
	  });
	}
	function _getSignatureColor2() {
	  var _babelHelpers$classPr3;
	  return (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _selectedSignatureColor)) !== null && _babelHelpers$classPr3 !== void 0 ? _babelHelpers$classPr3 : babelHelpers.classPrivateFieldGet(this, _defaultSignatureColor);
	}
	function _closeColorPickerPopup2() {
	  var _classPrivateMethodGe3 = _classPrivateMethodGet$2(this, _getColorSelectorBtn, _getColorSelectorBtn2).call(this),
	    colorPicker = _classPrivateMethodGe3.colorPicker;
	  colorPicker.close();
	}

	var _templateObject$5, _templateObject2$2, _templateObject3$2;
	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _uploader = /*#__PURE__*/new WeakMap();
	var _onActionClick$1 = /*#__PURE__*/new WeakSet();
	var _saveStamp = /*#__PURE__*/new WeakSet();
	var MyStamp = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(MyStamp, _Dummy);
	  function MyStamp(_block) {
	    var _this;
	    babelHelpers.classCallCheck(this, MyStamp);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MyStamp).call(this, _block));
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _saveStamp);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _onActionClick$1);
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _uploader, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _uploader, new ui_stamp_uploader.Uploader({
	      controller: {
	        upload: 'sign.upload.stampUploadController'
	      },
	      contact: {
	        id: 0,
	        label: main_core.Text.encode(_this.block.getDocument().getInitiatorName())
	      }
	    }));
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _uploader).subscribe('onSaveAsync', function (event) {
	      MyStamp.currentBlock["await"](true);
	      return new Promise(function (resolve, reject) {
	        var _eventData$file;
	        var eventData = event.getData();
	        if (!(eventData !== null && eventData !== void 0 && (_eventData$file = eventData.file) !== null && _eventData$file !== void 0 && _eventData$file.serverId)) {
	          reject();
	          return;
	        }
	        _this.dataSrc = eventData.file.serverPreviewUrl;
	        _classPrivateMethodGet$3(babelHelpers.assertThisInitialized(_this), _saveStamp, _saveStamp2).call(babelHelpers.assertThisInitialized(_this), MyStamp.currentBlock, eventData.file.serverId).then(function () {
	          resolve();
	        })["catch"](function (error) {
	          reject(error);
	        });
	      });
	    });
	    return _this;
	  }

	  /**
	   * Returns true if block is in singleton mode.
	   * @return {boolean}
	   */
	  babelHelpers.createClass(MyStamp, [{
	    key: "isSingleton",
	    value: function isSingleton() {
	      return true;
	    }
	    /**
	     * Returns true if style panel mast be showed.
	     * @return {boolean}
	     */
	  }, {
	    key: "isStyleAllowed",
	    value: function isStyleAllowed() {
	      return false;
	    }
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	  }, {
	    key: "getInitDimension",
	    value: function getInitDimension() {
	      return {
	        width: 151,
	        height: 151
	      };
	    }
	    /**
	     * Returns action button for edit content.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getActionButton",
	    value: function getActionButton() {
	      return main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-style-btn --funnel\">\n\t\t\t\t<button onclick=\"", "\" data-role=\"action\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$3(this, _onActionClick$1, _onActionClick2$1).bind(this), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_STAMP_ACTION_BUTTON'));
	    }
	    /**
	     * Calls when action button was clicked.
	     * @param {PointerEvent} event
	     */
	  }, {
	    key: "getViewContent",
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	    value: function getViewContent() {
	      var src = null;
	      if (this.dataSrc) {
	        src = this.dataSrc;
	      } else if (this.data.base64) {
	        src = 'data:image;base64,' + this.data.base64;
	      }
	      if (src) {
	        return main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document__block-content_stamp\" style=\"background-image: url(", ")\"></div>\n\t\t\t"])), src);
	      } else {
	        return main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document__block-content_member-nodata --stamp\"></div>\n\t\t\t"])));
	      }
	    }
	  }]);
	  return MyStamp;
	}(Dummy);
	function _onActionClick2$1(event) {
	  MyStamp.currentBlock = this.block;
	  babelHelpers.classPrivateFieldGet(this, _uploader).show();
	}
	function _saveStamp2(block, fileId) {
	  return sign_backend.Backend.controller({
	    command: 'integration.crm.saveCompanyStamp',
	    postData: {
	      documentId: block.getDocument().getId(),
	      companyId: block.getDocument().getCompanyId(),
	      fileId: fileId
	    }
	  }).then(function (result) {
	    block.assign();
	  })["catch"](function () {});
	}

	var _templateObject$6, _templateObject2$3;
	function ownKeys$3(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$3(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$3(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$3(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$4(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _onActionClick$2 = /*#__PURE__*/new WeakSet();
	var Number = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(Number, _Dummy);
	  function Number() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, Number);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Number)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _onActionClick$2);
	    return _this;
	  }
	  babelHelpers.createClass(Number, [{
	    key: "isSingleton",
	    /**
	     * Returns true if block is in singleton mode.
	     * @return {boolean}
	     */
	    value: function isSingleton() {
	      return true;
	    }
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	  }, {
	    key: "getInitDimension",
	    value: function getInitDimension() {
	      return {
	        width: 100,
	        height: 28
	      };
	    }
	    /**
	     * Calls when action button was clicked.
	     * @param {PointerEvent} event
	     */
	  }, {
	    key: "getActionButton",
	    /**
	     * Returns action button for edit content.
	     * @return {HTMLElement | null}
	     */
	    value: function getActionButton() {
	      return main_core.Tag.render(_templateObject$6 || (_templateObject$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-style-btn --funnel\">\n\t\t\t\t<button onclick=\"", "\" data-role=\"action\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$4(this, _onActionClick$2, _onActionClick2$2).bind(this), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_NUMBER_ACTION_EDIT'));
	    }
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	  }, {
	    key: "getViewContent",
	    value: function getViewContent() {
	      return main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-number\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(this.data.text || '').toString());
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return _objectSpread$3(_objectSpread$3({}, babelHelpers.get(babelHelpers.getPrototypeOf(Number.prototype), "getStyles", this).call(this)), Number.defaultTextBlockPaddingStyles);
	    }
	  }]);
	  return Number;
	}(Dummy);
	function _onActionClick2$2(event) {
	  var _this2 = this;
	  event.stopPropagation();
	  var numeratorUrl = this.block.getDocument().getConfig()['crmNumeratorUrl'];
	  if (!Number.sliderOnMessageBind) {
	    Number.sliderOnMessageBind = true;
	    BX.addCustomEvent('SidePanel.Slider:onMessage', function (event) {
	      if (event.getEventId() === 'numerator-saved-event') {
	        var _event$sender$options, _event$sender, _event$sender$options2, _event$sender$options3;
	        var numeratorData = event.getData();
	        var currentBlock = (_event$sender$options = event === null || event === void 0 ? void 0 : (_event$sender = event.sender) === null || _event$sender === void 0 ? void 0 : (_event$sender$options2 = _event$sender.options) === null || _event$sender$options2 === void 0 ? void 0 : (_event$sender$options3 = _event$sender$options2.data) === null || _event$sender$options3 === void 0 ? void 0 : _event$sender$options3.block) !== null && _event$sender$options !== void 0 ? _event$sender$options : _this2.block;
	        if (numeratorData.type === 'CRM_SMART_DOCUMENT') {
	          currentBlock["await"](true);
	          sign_backend.Backend.controller({
	            command: 'integration.crm.refreshNumberInDocument',
	            postData: {
	              documentId: currentBlock.getDocument().getId()
	            }
	          }).then(function (result) {
	            currentBlock.assign();
	          })["catch"](function () {});
	        }
	      }
	    });
	  }
	  BX.SidePanel.Instance.open(numeratorUrl, {
	    width: 480,
	    cacheable: false,
	    data: {
	      block: this.block
	    }
	  });
	}
	babelHelpers.defineProperty(Number, "sliderOnMessageBind", false);

	var _templateObject$7, _templateObject2$4, _templateObject3$3;
	function ownKeys$4(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$4(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$4(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$4(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$5(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$5(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cache$1 = /*#__PURE__*/new WeakMap();
	var _field = /*#__PURE__*/new WeakMap();
	var _actionButton = /*#__PURE__*/new WeakMap();
	var _onActionClick$3 = /*#__PURE__*/new WeakSet();
	var _setActionButtonLabel = /*#__PURE__*/new WeakSet();
	var _getCrmFieldSelectorPanel = /*#__PURE__*/new WeakSet();
	var Reference = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(Reference, _Dummy);
	  function Reference() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, Reference);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Reference)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _getCrmFieldSelectorPanel);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _setActionButtonLabel);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _onActionClick$3);
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _field, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _actionButton, {
	      writable: true,
	      value: void 0
	    });
	    return _this;
	  }
	  babelHelpers.createClass(Reference, [{
	    key: "setData",
	    /**
	     * Sets new data.
	     * @param {any} data
	     */
	    value: function setData(data) {
	      this.data = data ? data : {};
	      babelHelpers.classPrivateFieldSet(this, _field, this.data.field);
	    }
	    /**
	     * Returns current data.
	     * @return {any}
	     */
	  }, {
	    key: "getData",
	    value: function getData() {
	      var data = this.data;
	      if (data.text && data.field) {
	        delete data.text;
	      }
	      return data;
	    }
	    /**
	     * Calls when block has placed on document.
	     */
	  }, {
	    key: "onPlaced",
	    value: function onPlaced() {
	      _classPrivateMethodGet$5(this, _onActionClick$3, _onActionClick2$3).call(this);
	    }
	    /**
	     * Calls when action button was clicked.
	     */
	  }, {
	    key: "getActionButton",
	    /**
	     * Returns action button for edit content.
	     * @return {HTMLElement | null}
	     */
	    value: function getActionButton() {
	      if (main_core.Type.isUndefined(crm_form_fields_selector.Selector)) {
	        return null;
	      }
	      babelHelpers.classPrivateFieldSet(this, _actionButton, main_core.Tag.render(_templateObject$7 || (_templateObject$7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-style-btn --funnel\">\n\t\t\t\t<button onclick=\"", "\" data-role=\"action\">\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$5(this, _onActionClick$3, _onActionClick2$3).bind(this)));
	      _classPrivateMethodGet$5(this, _setActionButtonLabel, _setActionButtonLabel2).call(this);
	      return babelHelpers.classPrivateFieldGet(this, _actionButton);
	    }
	    /**
	     * Sets label to action button.
	     */
	  }, {
	    key: "getViewContent",
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	    value: function getViewContent() {
	      _classPrivateMethodGet$5(this, _setActionButtonLabel, _setActionButtonLabel2).call(this);
	      var _this$block$getPositi = this.block.getPosition(),
	        width = _this$block$getPositi.width,
	        height = _this$block$getPositi.height;
	      if (this.data.src) {
	        return main_core.Tag.render(_templateObject2$4 || (_templateObject2$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div style=\"width: ", "px; height: ", "px; background: url(", ") no-repeat top; background-size: cover;\">\n\t\t\t\t</div>\n\t\t\t"])), width - 14, height - 14, this.data.src);
	      } else {
	        var className = !this.data.text ? 'sign-document__block-content_member-nodata' : '';
	        return main_core.Tag.render(_templateObject3$3 || (_templateObject3$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), className, main_core.Text.encode(this.data.text || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA')));
	      }
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return _objectSpread$4(_objectSpread$4({}, babelHelpers.get(babelHelpers.getPrototypeOf(Reference.prototype), "getStyles", this).call(this)), Reference.defaultTextBlockPaddingStyles);
	    }
	  }]);
	  return Reference;
	}(Dummy);
	function _onActionClick2$3() {
	  var _this2 = this;
	  _classPrivateMethodGet$5(this, _getCrmFieldSelectorPanel, _getCrmFieldSelectorPanel2).call(this).show().then(function (selectedName) {
	    if (selectedName.length === 0) {
	      return;
	    }
	    babelHelpers.classPrivateFieldSet(_this2, _field, selectedName[0]);
	    _this2.setData({
	      field: babelHelpers.classPrivateFieldGet(_this2, _field)
	    });
	    setTimeout(function () {
	      _this2.block.assign();
	    }, 0);
	  });
	}
	function _setActionButtonLabel2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _actionButton)) {
	    return;
	  }
	  var config = this.block.getDocument().getConfig();
	  var fieldLabel = config.crmEntityFields[babelHelpers.classPrivateFieldGet(this, _field)] || null;
	  if (!fieldLabel) {
	    fieldLabel = main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REFERENCE_ACTION_BUTTON');
	  }
	  babelHelpers.classPrivateFieldGet(this, _actionButton).querySelector('button').innerText = fieldLabel;
	}
	function _getCrmFieldSelectorPanel2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache$1).remember('getFieldSelector', function () {
	    return new crm_form_fields_selector.Selector({
	      multiple: false,
	      controllerOptions: {
	        hideVirtual: 1,
	        hideRequisites: 1,
	        hideSmartDocument: 1
	      },
	      filter: {
	        '+categories': ['CONTACT', 'SMART_DOCUMENT'],
	        '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'address', 'url', 'money', 'boolean', 'double']
	      }
	    });
	  });
	}

	var _templateObject$8, _templateObject2$5, _templateObject3$4;
	function ownKeys$5(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$5(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$5(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$5(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$6(obj, privateSet) { _checkPrivateRedeclaration$6(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$6(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$6(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$6(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _field$1 = /*#__PURE__*/new WeakMap();
	var _actionButton$1 = /*#__PURE__*/new WeakMap();
	var _cache$2 = /*#__PURE__*/new WeakMap();
	var _onActionClick$4 = /*#__PURE__*/new WeakSet();
	var _setActionButtonLabel$1 = /*#__PURE__*/new WeakSet();
	var _getCrmFieldSelectorPanel$1 = /*#__PURE__*/new WeakSet();
	var _getFieldNegativeFilter = /*#__PURE__*/new WeakSet();
	var _getFieldsNameBlackList = /*#__PURE__*/new WeakSet();
	var MyReference = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(MyReference, _Dummy);
	  function MyReference() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, MyReference);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(MyReference)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _getFieldsNameBlackList);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _getFieldNegativeFilter);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _getCrmFieldSelectorPanel$1);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _setActionButtonLabel$1);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _onActionClick$4);
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _field$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _actionButton$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _cache$2, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    return _this;
	  }
	  babelHelpers.createClass(MyReference, [{
	    key: "setData",
	    /**
	     * Sets new data.
	     * @param {any} data
	     */
	    value: function setData(data) {
	      this.data = data ? data : {};
	      babelHelpers.classPrivateFieldSet(this, _field$1, this.data.field);
	    }
	    /**
	     * Returns current data.
	     * @return {any}
	     */
	  }, {
	    key: "getData",
	    value: function getData() {
	      var data = this.data;
	      if (data.text && data.field) {
	        delete data.text;
	      }
	      return data;
	    }
	    /**
	     * Calls when block has placed on document.
	     */
	  }, {
	    key: "onPlaced",
	    value: function onPlaced() {
	      _classPrivateMethodGet$6(this, _onActionClick$4, _onActionClick2$4).call(this);
	    }
	    /**
	     * Calls when action button was clicked.
	     */
	  }, {
	    key: "getActionButton",
	    /**
	     * Returns action button for edit content.
	     * @return {HTMLElement | null}
	     */
	    value: function getActionButton() {
	      if (main_core.Type.isUndefined(crm_form_fields_selector.Selector)) {
	        return null;
	      }
	      babelHelpers.classPrivateFieldSet(this, _actionButton$1, main_core.Tag.render(_templateObject$8 || (_templateObject$8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-style-btn --funnel\">\n\t\t\t\t<button onclick=\"", "\" data-role=\"action\">\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$6(this, _onActionClick$4, _onActionClick2$4).bind(this)));
	      _classPrivateMethodGet$6(this, _setActionButtonLabel$1, _setActionButtonLabel2$1).call(this);
	      return babelHelpers.classPrivateFieldGet(this, _actionButton$1);
	    }
	    /**
	     * Sets label to action button.
	     */
	  }, {
	    key: "getViewContent",
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	    value: function getViewContent() {
	      _classPrivateMethodGet$6(this, _setActionButtonLabel$1, _setActionButtonLabel2$1).call(this);
	      var _this$block$getPositi = this.block.getPosition(),
	        width = _this$block$getPositi.width,
	        height = _this$block$getPositi.height;
	      if (this.data.src) {
	        return main_core.Tag.render(_templateObject2$5 || (_templateObject2$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div style=\"width: ", "px; height: ", "px; background: url(", ") no-repeat top; background-size: cover;\">\n\t\t\t\t</div>\n\t\t\t"])), width - 14, height - 14, this.data.src);
	      } else {
	        var className = !this.data.text ? 'sign-document__block-content_member-nodata' : '';
	        return main_core.Tag.render(_templateObject3$4 || (_templateObject3$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), className, main_core.Text.encode(this.data.text || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_MY_BLOCKS')));
	      }
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return _objectSpread$5(_objectSpread$5({}, babelHelpers.get(babelHelpers.getPrototypeOf(MyReference.prototype), "getStyles", this).call(this)), MyReference.defaultTextBlockPaddingStyles);
	    }
	  }]);
	  return MyReference;
	}(Dummy);
	function _onActionClick2$4() {
	  var _this2 = this;
	  _classPrivateMethodGet$6(this, _getCrmFieldSelectorPanel$1, _getCrmFieldSelectorPanel2$1).call(this).show().then(function (selectedName) {
	    if (selectedName.length === 0) {
	      return;
	    }
	    babelHelpers.classPrivateFieldSet(_this2, _field$1, selectedName[0]);
	    _this2.setData({
	      field: babelHelpers.classPrivateFieldGet(_this2, _field$1)
	    });
	    setTimeout(function () {
	      _this2.block.assign();
	    }, 0);
	  });
	}
	function _setActionButtonLabel2$1() {
	  if (!babelHelpers.classPrivateFieldGet(this, _actionButton$1)) {
	    return;
	  }
	  var config = this.block.getDocument().getConfig();
	  var fieldLabel = config.crmEntityFields[babelHelpers.classPrivateFieldGet(this, _field$1)] || null;
	  if (!fieldLabel) {
	    fieldLabel = main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REFERENCE_ACTION_BUTTON');
	  }
	  babelHelpers.classPrivateFieldGet(this, _actionButton$1).querySelector('button').innerText = fieldLabel;
	}
	function _getCrmFieldSelectorPanel2$1() {
	  var _this3 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$2).remember('getFieldSelector', function () {
	    return new crm_form_fields_selector.Selector({
	      multiple: false,
	      controllerOptions: {
	        hideVirtual: 1,
	        hideRequisites: 1,
	        hideSmartDocument: 1
	      },
	      filter: {
	        '+categories': ['COMPANY', 'SMART_DOCUMENT'],
	        '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'address', 'url', 'money', 'boolean', 'double'],
	        '-fields': [_classPrivateMethodGet$6(_this3, _getFieldNegativeFilter, _getFieldNegativeFilter2).call(_this3)]
	      }
	    });
	  });
	}
	function _getFieldNegativeFilter2() {
	  var blackList = _classPrivateMethodGet$6(this, _getFieldsNameBlackList, _getFieldsNameBlackList2).call(this);
	  return function (field) {
	    return blackList.some(function (blankListFieldName) {
	      return field.name === blankListFieldName;
	    });
	  };
	}
	function _getFieldsNameBlackList2() {
	  return ['COMPANY_LINK', 'COMPANY_REG_ADDRESS', 'COMPANY_ORIGIN_VERSION'];
	}

	var _templateObject$9, _templateObject2$6;
	function ownKeys$6(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$6(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$6(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$6(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$7(obj, privateSet) { _checkPrivateRedeclaration$7(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$7(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$7(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _onActionClick$5 = /*#__PURE__*/new WeakSet();
	var Requisites = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(Requisites, _Dummy);
	  function Requisites() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, Requisites);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Requisites)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _onActionClick$5);
	    return _this;
	  }
	  babelHelpers.createClass(Requisites, [{
	    key: "isSingleton",
	    /**
	     * Returns true if block is in singleton mode.
	     * @return {boolean}
	     */
	    value: function isSingleton() {
	      return true;
	    }
	    /**
	     * Returns current data.
	     * @return {any}
	     */
	  }, {
	    key: "getData",
	    value: function getData() {
	      var data = this.data;
	      if (data.text) {
	        data.text = null;
	      }
	      return data;
	    }
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	  }, {
	    key: "getInitDimension",
	    value: function getInitDimension() {
	      return {
	        width: 250,
	        height: 220
	      };
	    }
	    /**
	     * Calls when action button was clicked.
	     * @param {PointerEvent} event
	     */
	  }, {
	    key: "getActionButton",
	    /**
	     * Returns action button for edit content.
	     * @return {HTMLElement}
	     */
	    value: function getActionButton() {
	      return main_core.Tag.render(_templateObject$9 || (_templateObject$9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-style-btn --funnel\">\n\t\t\t\t<button onclick=\"", "\" data-role=\"action\" data-id=\"action-", "\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$7(this, _onActionClick$5, _onActionClick2$5).bind(this), this.block.getCode(), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_ACTION_BUTTON'));
	    }
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	  }, {
	    key: "getViewContent",
	    value: function getViewContent() {
	      var text = this.data.text || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_REQUISITES');
	      var tagBody = this.data.text ? '' : ' class="sign-document__block-content_member-nodata"';
	      return main_core.Tag.render(_templateObject2$6 || (_templateObject2$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div", ">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), tagBody, main_core.Text.encode(text).toString().replaceAll('[br]', '<br>'));
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return _objectSpread$6(_objectSpread$6({}, babelHelpers.get(babelHelpers.getPrototypeOf(Requisites.prototype), "getStyles", this).call(this)), Requisites.defaultTextBlockPaddingStyles);
	    }
	  }]);
	  return Requisites;
	}(Dummy);
	function _onActionClick2$5(event) {
	  var _this2 = this;
	  var document = this.block.getDocument();
	  var config = document.getConfig();
	  var member = document.getMemberByPart(this.block.getMemberPart());
	  event.stopPropagation();
	  if (!member) {
	    return;
	  }
	  new crm_requisite_fieldsetViewer.FieldsetViewer({
	    entityTypeId: config.crmOwnerTypeContact,
	    entityId: member.cid,
	    events: {
	      onClose: function onClose() {
	        _this2.block.assign();
	      }
	    },
	    fieldListEditorOptions: {
	      fieldsPanelOptions: {
	        filter: {
	          '+categories': ['CONTACT'],
	          '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'address', 'url', 'money', 'boolean', 'double']
	        },
	        presetId: config.crmRequisiteContactPresetId,
	        controllerOptions: {
	          hideVirtual: 1,
	          hideRequisites: 0,
	          hideSmartDocument: 1
	        }
	      }
	    }
	  }).show();
	}

	var _templateObject$a, _templateObject2$7;
	function _classPrivateMethodInitSpec$8(obj, privateSet) { _checkPrivateRedeclaration$8(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$5(obj, privateMap, value) { _checkPrivateRedeclaration$8(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$8(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$8(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cache$3 = /*#__PURE__*/new WeakMap();
	var _defaultSignatureColor$1 = /*#__PURE__*/new WeakMap();
	var _availableSignatureColors$1 = /*#__PURE__*/new WeakMap();
	var _selectedSignatureColor$1 = /*#__PURE__*/new WeakMap();
	var _getColorSelectorBtn$1 = /*#__PURE__*/new WeakSet();
	var _getSignatureColor$1 = /*#__PURE__*/new WeakSet();
	var _closeColorPickerPopup$1 = /*#__PURE__*/new WeakSet();
	var Sign = /*#__PURE__*/function (_BlockWithSynchroniza) {
	  babelHelpers.inherits(Sign, _BlockWithSynchroniza);
	  function Sign() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, Sign);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Sign)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _closeColorPickerPopup$1);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _getSignatureColor$1);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _getColorSelectorBtn$1);
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _cache$3, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _defaultSignatureColor$1, {
	      writable: true,
	      value: '#0047ab'
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _availableSignatureColors$1, {
	      writable: true,
	      value: ['#000', babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _defaultSignatureColor$1), '#8b00ff']
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _selectedSignatureColor$1, {
	      writable: true,
	      value: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(Sign, [{
	    key: "getPlaceholderLabel",
	    /**
	     * Returns placeholder's label.
	     * @return {string}
	     */
	    value: function getPlaceholderLabel() {
	      return main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_SIGN');
	    }
	  }, {
	    key: "isStyleAllowed",
	    value: function isStyleAllowed() {
	      return false;
	    }
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	  }, {
	    key: "getInitDimension",
	    value: function getInitDimension() {
	      return {
	        width: 200,
	        height: 70
	      };
	    }
	  }, {
	    key: "getBlockCaption",
	    value: function getBlockCaption() {
	      var _classPrivateMethodGe = _classPrivateMethodGet$8(this, _getColorSelectorBtn$1, _getColorSelectorBtn2$1).call(this),
	        colorSelectorBtnLayout = _classPrivateMethodGe.layout;
	      return main_core.Tag.render(_templateObject$a || (_templateObject$a = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div style=\"display: flex; flex-direction: row;\">\n\t\t\t", "\n\t\t\t<div class=\"sign-document__block-style--separator\"></div>\n\t\t\t</div>\n\t\t"])), colorSelectorBtnLayout);
	    }
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	  }, {
	    key: "getViewContent",
	    value: function getViewContent() {
	      return main_core.Tag.render(_templateObject2$7 || (_templateObject2$7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document__block-content_member-nodata\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(this.getPlaceholderLabel()));
	    }
	  }, {
	    key: "updateColor",
	    value: function updateColor(color) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sign.prototype), "updateColor", this).call(this, color);
	      var _classPrivateMethodGe2 = _classPrivateMethodGet$8(this, _getColorSelectorBtn$1, _getColorSelectorBtn2$1).call(this),
	        layout = _classPrivateMethodGe2.layout,
	        colorPicker = _classPrivateMethodGe2.colorPicker;
	      babelHelpers.classPrivateFieldSet(this, _selectedSignatureColor$1, color);
	      UI.updateColorSelectorBtnColor(layout, color);
	      colorPicker.setSelectedColor(color);
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return {
	        'background-position': 'center !important',
	        color: _classPrivateMethodGet$8(this, _getSignatureColor$1, _getSignatureColor2$1).call(this)
	      };
	    }
	  }, {
	    key: "onSave",
	    value: function onSave() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sign.prototype), "onSave", this).call(this);
	      _classPrivateMethodGet$8(this, _closeColorPickerPopup$1, _closeColorPickerPopup2$1).call(this);
	    }
	  }, {
	    key: "onClickOut",
	    value: function onClickOut() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sign.prototype), "onClickOut", this).call(this);
	      _classPrivateMethodGet$8(this, _closeColorPickerPopup$1, _closeColorPickerPopup2$1).call(this);
	    }
	  }, {
	    key: "onStyleRender",
	    value: function onStyleRender(styles) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Sign.prototype), "onStyleRender", this).call(this, styles);
	      if (!main_core.Type.isNil(styles.color)) {
	        this.updateColor(styles.color);
	      }
	    }
	  }]);
	  return Sign;
	}(BlockWithSynchronizableStyleColor);
	function _getColorSelectorBtn2$1() {
	  var _this2 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache$3).remember('colorSelectorBtn', function () {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    return UI.getColorSelectorBtn((_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(_this2, _selectedSignatureColor$1)) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : babelHelpers.classPrivateFieldGet(_this2, _defaultSignatureColor$1), function (color) {
	      babelHelpers.classPrivateFieldSet(_this2, _selectedSignatureColor$1, color);
	      _this2.onStyleChange();
	    }, {
	      colors: [babelHelpers.classPrivateFieldGet(_this2, _availableSignatureColors$1)],
	      allowCustomColor: false,
	      selectedColor: (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(_this2, _selectedSignatureColor$1)) !== null && _babelHelpers$classPr2 !== void 0 ? _babelHelpers$classPr2 : babelHelpers.classPrivateFieldGet(_this2, _defaultSignatureColor$1),
	      colorPreview: false
	    });
	  });
	}
	function _getSignatureColor2$1() {
	  var _babelHelpers$classPr3;
	  return (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _selectedSignatureColor$1)) !== null && _babelHelpers$classPr3 !== void 0 ? _babelHelpers$classPr3 : babelHelpers.classPrivateFieldGet(this, _defaultSignatureColor$1);
	}
	function _closeColorPickerPopup2$1() {
	  var _classPrivateMethodGe3 = _classPrivateMethodGet$8(this, _getColorSelectorBtn$1, _getColorSelectorBtn2$1).call(this),
	    colorPicker = _classPrivateMethodGe3.colorPicker;
	  colorPicker.close();
	}

	var _templateObject$b, _templateObject2$8;
	var Stamp = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(Stamp, _Dummy);
	  function Stamp() {
	    babelHelpers.classCallCheck(this, Stamp);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Stamp).apply(this, arguments));
	  }
	  babelHelpers.createClass(Stamp, [{
	    key: "isStyleAllowed",
	    /**
	     * Returns true if style panel mast be showed.
	     * @return {boolean}
	     */
	    value: function isStyleAllowed() {
	      return false;
	    }
	    /**
	     * Returns initial dimension of block.
	     * @return {width: number, height: number}
	     */
	  }, {
	    key: "getInitDimension",
	    value: function getInitDimension() {
	      return {
	        width: 151,
	        height: 151
	      };
	    }
	    /**
	     * Returns placeholder's label.
	     * @return {string}
	     */
	  }, {
	    key: "getPlaceholderLabel",
	    value: function getPlaceholderLabel() {
	      return main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_STAMP');
	    }
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement | string}
	     */
	  }, {
	    key: "getViewContent",
	    value: function getViewContent() {
	      var _this$block$getPositi = this.block.getPosition(),
	        width = _this$block$getPositi.width,
	        height = _this$block$getPositi.height;
	      if (this.data.base64) {
	        return main_core.Tag.render(_templateObject$b || (_templateObject$b = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document__block-content_stamp\" style=\"background-image: url(", ")\"></div>\n\t\t\t"])), src);
	      } else {
	        return main_core.Tag.render(_templateObject2$8 || (_templateObject2$8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document__block-content_member-nodata\">\n\t\t\t\t\t\n\t\t\t\t</div>\n\t\t\t"])));
	      }
	    }
	  }]);
	  return Stamp;
	}(Dummy);

	var _templateObject$c;
	function ownKeys$7(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$7(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$7(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$7(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$9(obj, privateSet) { _checkPrivateRedeclaration$9(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$6(obj, privateMap, value) { _checkPrivateRedeclaration$9(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$9(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$9(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _textContainer = /*#__PURE__*/new WeakMap();
	var _onKeyUp = /*#__PURE__*/new WeakSet();
	var _onPaste = /*#__PURE__*/new WeakSet();
	var Text = /*#__PURE__*/function (_Dummy) {
	  babelHelpers.inherits(Text, _Dummy);
	  function Text(block) {
	    var _this;
	    babelHelpers.classCallCheck(this, Text);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Text).call(this, block));
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _onPaste);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _onKeyUp);
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _textContainer, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('BX.Sign.Blocks.Text');
	    return _this;
	  }

	  /**
	   * Calls when action button was clicked.
	   */
	  babelHelpers.createClass(Text, [{
	    key: "onActionClick",
	    value: function onActionClick() {
	      babelHelpers.classPrivateFieldGet(this, _textContainer).contentEditable = true;
	      babelHelpers.classPrivateFieldGet(this, _textContainer).focus();
	    }
	    /**
	     * Calls when typing in text container.
	     */
	  }, {
	    key: "getText",
	    value: function getText() {
	      return this.data.text;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return babelHelpers.classPrivateFieldGet(this, _textContainer);
	    }
	    /**
	     * Returns type's content in view mode.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getViewContent",
	    value: function getViewContent() {
	      var _this2 = this;
	      var content = this.data.text === main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TEXT_PLACEHOLDER') ? '' : this.data.text;
	      babelHelpers.classPrivateFieldSet(this, _textContainer, main_core.Tag.render(_templateObject$c || (_templateObject$c = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document-block-text\" placeholder=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TEXT_PLACEHOLDER'), main_core.Text.encode(content).replaceAll('[br]', '<br>')));
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _textContainer), 'keyup', _classPrivateMethodGet$9(this, _onKeyUp, _onKeyUp2).bind(this));
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _textContainer), 'paste', function (event) {
	        return setTimeout(_classPrivateMethodGet$9(_this2, _onPaste, _onPaste2).bind(_this2, event));
	      });
	      return babelHelpers.classPrivateFieldGet(this, _textContainer);
	    }
	  }, {
	    key: "getStyles",
	    value: function getStyles() {
	      return _objectSpread$7(_objectSpread$7({}, babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "getStyles", this).call(this)), Text.defaultTextBlockPaddingStyles);
	    }
	  }]);
	  return Text;
	}(Dummy);
	function _onKeyUp2(event) {
	  this.setText(babelHelpers.classPrivateFieldGet(this, _textContainer).innerText.replaceAll("\n", '[br]'));
	  this.onChange();
	}
	function _onPaste2(event) {
	  babelHelpers.classPrivateFieldGet(this, _textContainer).innerHTML = babelHelpers.classPrivateFieldGet(this, _textContainer).innerText.replaceAll('\n', '<br>');
	  var range = document.createRange();
	  range.selectNodeContents(babelHelpers.classPrivateFieldGet(this, _textContainer));
	  range.collapse(false);
	  var selection = document.getSelection();
	  selection.removeAllRanges();
	  selection.addRange(range);
	  this.setText(babelHelpers.classPrivateFieldGet(this, _textContainer).innerHTML.replaceAll('<br>', '[br]'));
	  this.onChange();
	}

	function _classPrivateMethodInitSpec$a(obj, privateSet) { _checkPrivateRedeclaration$a(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$7(obj, privateMap, value) { _checkPrivateRedeclaration$a(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$a(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$a(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _block = /*#__PURE__*/new WeakMap();
	var _buttons = /*#__PURE__*/new WeakMap();
	var _style = /*#__PURE__*/new WeakMap();
	var _onPressButton = /*#__PURE__*/new WeakSet();
	var _applyData = /*#__PURE__*/new WeakSet();
	var Style = /*#__PURE__*/function () {
	  /**
	   * Constructor.
	   * @param {StyleOptions} options
	   */
	  function Style(options) {
	    babelHelpers.classCallCheck(this, Style);
	    _classPrivateMethodInitSpec$a(this, _applyData);
	    _classPrivateMethodInitSpec$a(this, _onPressButton);
	    _classPrivateFieldInitSpec$7(this, _block, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$7(this, _buttons, {
	      writable: true,
	      value: {
	        family: {
	          property: 'font-family',
	          value: null
	        },
	        size: {
	          property: 'font-size',
	          value: 14
	        },
	        color: {
	          property: 'color',
	          value: null
	        },
	        bold: {
	          button: null,
	          property: 'font-weight',
	          value: 'bold',
	          state: false
	        },
	        italic: {
	          button: null,
	          property: 'font-style',
	          value: 'italic',
	          state: false
	        },
	        underline: {
	          button: null,
	          property: 'text-decoration',
	          value: 'underline',
	          state: false
	        },
	        through: {
	          button: null,
	          property: 'text-decoration',
	          value: 'line-through',
	          state: false
	        },
	        left: {
	          button: null,
	          property: 'text-align',
	          value: 'left',
	          state: false,
	          group: 'align'
	        },
	        center: {
	          button: null,
	          property: 'text-align',
	          value: 'center',
	          state: false,
	          group: 'align'
	        },
	        right: {
	          button: null,
	          property: 'text-align',
	          value: 'right',
	          state: false,
	          group: 'align'
	        },
	        justify: {
	          button: null,
	          property: 'text-align',
	          value: 'justify',
	          state: false,
	          group: 'align'
	        }
	      }
	    });
	    _classPrivateFieldInitSpec$7(this, _style, {
	      writable: true,
	      value: {
	        buttonPressed: 'sign-document__block-style--button-pressed'
	      }
	    });
	    babelHelpers.classPrivateFieldSet(this, _block, options.block);
	    if (options.data) {
	      _classPrivateMethodGet$a(this, _applyData, _applyData2).call(this, options.data);
	    }
	  }

	  /**
	   * Handle on panel button click.
	   * @param {string} code
	   */
	  babelHelpers.createClass(Style, [{
	    key: "applyStyles",
	    /**
	     * Applies collected styles to the element.
	     * @param {HTMLElement} element
	     */
	    value: function applyStyles(element) {
	      element.removeAttribute('style');
	      main_core.Dom.style(element, this.collectStyles());
	    }
	  }, {
	    key: "updateFontSize",
	    value: function updateFontSize(fontSize) {
	      if (fontSize) {
	        babelHelpers.classPrivateFieldGet(this, _buttons).size['property'] = 'font-size';
	        babelHelpers.classPrivateFieldGet(this, _buttons).size['value'] = fontSize;
	      }
	    }
	    /**
	     * Collects checked styles in one dataset.
	     * @return {{[key: string]: string}}
	     */
	  }, {
	    key: "collectStyles",
	    value: function collectStyles() {
	      var _this = this;
	      var styles = {};
	      babelHelpers.toConsumableArray(Object.keys(babelHelpers.classPrivateFieldGet(this, _buttons))).map(function (key) {
	        if (babelHelpers.classPrivateFieldGet(_this, _buttons)[key]['state'] || typeof babelHelpers.classPrivateFieldGet(_this, _buttons)[key]['state'] === 'undefined') {
	          var property = babelHelpers.classPrivateFieldGet(_this, _buttons)[key]['property'];
	          var value = babelHelpers.classPrivateFieldGet(_this, _buttons)[key]['value'];
	          if (value === null) {
	            return;
	          }
	          if (babelHelpers.classPrivateFieldGet(_this, _buttons)[key]['group']) {
	            styles[property] = value;
	          } else {
	            styles[property] = (styles[property] ? styles[property] + ' ' : '') + value;
	          }
	        }
	      });
	      return styles;
	    }
	    /**
	     * Returns style panel layout.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var _this2 = this;
	      var layout = UI.getStylePanel(function (code, value) {
	        babelHelpers.classPrivateFieldGet(_this2, _buttons)[code]['value'] = value;
	        babelHelpers.classPrivateFieldGet(_this2, _block).renderStyle();
	      }, this.collectStyles());
	      babelHelpers.toConsumableArray(layout.querySelectorAll('[data-action]')).map(function (button) {
	        var action = button.getAttribute('data-action');
	        if (babelHelpers.classPrivateFieldGet(_this2, _buttons)[action]) {
	          main_core.Event.bind(button, 'click', function () {
	            return _classPrivateMethodGet$a(_this2, _onPressButton, _onPressButton2).call(_this2, action);
	          });
	          babelHelpers.classPrivateFieldGet(_this2, _buttons)[action]['button'] = button;
	          if (babelHelpers.classPrivateFieldGet(_this2, _buttons)[action]['state']) {
	            main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(_this2, _buttons)[action]['button'], babelHelpers.classPrivateFieldGet(_this2, _style).buttonPressed);
	          }
	        }
	      });
	      return layout;
	    }
	  }, {
	    key: "updateColor",
	    value: function updateColor(color) {
	      babelHelpers.classPrivateFieldGet(this, _buttons).color.value = color;
	    }
	  }]);
	  return Style;
	}();
	function _onPressButton2(code) {
	  var _this3 = this;
	  babelHelpers.classPrivateFieldGet(this, _buttons)[code]['state'] = !babelHelpers.classPrivateFieldGet(this, _buttons)[code]['state'];
	  if (babelHelpers.classPrivateFieldGet(this, _buttons)[code]['state']) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _buttons)[code]['button'], babelHelpers.classPrivateFieldGet(this, _style).buttonPressed);
	    if (babelHelpers.classPrivateFieldGet(this, _buttons)[code]['group']) {
	      var group = babelHelpers.classPrivateFieldGet(this, _buttons)[code]['group'];
	      babelHelpers.toConsumableArray(Object.keys(babelHelpers.classPrivateFieldGet(this, _buttons))).map(function (key) {
	        if (key !== code && babelHelpers.classPrivateFieldGet(_this3, _buttons)[key]['group'] === group) {
	          babelHelpers.classPrivateFieldGet(_this3, _buttons)[key]['state'] = false;
	          main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this3, _buttons)[key]['button'], babelHelpers.classPrivateFieldGet(_this3, _style).buttonPressed);
	        }
	      });
	    }
	  } else {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _buttons)[code]['button'], babelHelpers.classPrivateFieldGet(this, _style).buttonPressed);
	  }
	  babelHelpers.classPrivateFieldGet(this, _block).renderStyle();
	}
	function _applyData2(data) {
	  var _this4 = this;
	  babelHelpers.toConsumableArray(Object.keys(babelHelpers.classPrivateFieldGet(this, _buttons))).map(function (key) {
	    var property = babelHelpers.classPrivateFieldGet(_this4, _buttons)[key]['property'];
	    if (typeof data[property] !== 'undefined') {
	      if (typeof babelHelpers.classPrivateFieldGet(_this4, _buttons)[key]['state'] !== 'undefined') {
	        if (data[property].indexOf(babelHelpers.classPrivateFieldGet(_this4, _buttons)[key]['value']) !== -1) {
	          babelHelpers.classPrivateFieldGet(_this4, _buttons)[key]['state'] = true;
	        }
	      } else {
	        babelHelpers.classPrivateFieldGet(_this4, _buttons)[key]['value'] = data[property];
	      }
	    }
	  });
	}

	function ownKeys$8(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$8(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$8(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$8(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$b(obj, privateSet) { _checkPrivateRedeclaration$b(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$8(obj, privateMap, value) { _checkPrivateRedeclaration$b(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$b(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classPrivateMethodGet$b(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _id = /*#__PURE__*/new WeakMap();
	var _code = /*#__PURE__*/new WeakMap();
	var _layout = /*#__PURE__*/new WeakMap();
	var _stylePanel = /*#__PURE__*/new WeakMap();
	var _content = /*#__PURE__*/new WeakMap();
	var _document = /*#__PURE__*/new WeakMap();
	var _memberPart = /*#__PURE__*/new WeakMap();
	var _panelCreated = /*#__PURE__*/new WeakMap();
	var _allowMembers = /*#__PURE__*/new WeakMap();
	var _onClickCallback = /*#__PURE__*/new WeakMap();
	var _onRemoveCallback = /*#__PURE__*/new WeakMap();
	var _contentProviders = /*#__PURE__*/new WeakMap();
	var _currentFontSize = /*#__PURE__*/new WeakMap();
	var _style$1 = /*#__PURE__*/new WeakMap();
	var _firstRenderReady = /*#__PURE__*/new WeakMap();
	var _onClick = /*#__PURE__*/new WeakSet();
	var _createLayout = /*#__PURE__*/new WeakSet();
	var _onContentChange = /*#__PURE__*/new WeakSet();
	var _onColorStyleChange = /*#__PURE__*/new WeakSet();
	var Block = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Block, _EventEmitter);
	  /**
	   * Constructor.
	   * @param {BlockOptions} options
	   */
	  function Block(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Block);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Block).call(this));
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _onColorStyleChange);
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _onContentChange);
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _createLayout);
	    _classPrivateMethodInitSpec$b(babelHelpers.assertThisInitialized(_this), _onClick);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "events", {
	      onColorStyleChange: 'onColorStyleChange'
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _code, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _layout, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _stylePanel, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _content, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _document, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _memberPart, {
	      writable: true,
	      value: 2
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _panelCreated, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _allowMembers, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _onClickCallback, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _onRemoveCallback, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _contentProviders, {
	      writable: true,
	      value: {
	        date: Date,
	        myrequisites: MyRequisites,
	        mysign: MySign,
	        mystamp: MyStamp,
	        number: Number,
	        reference: Reference,
	        myreference: MyReference,
	        requisites: Requisites,
	        sign: Sign,
	        stamp: Stamp,
	        text: Text
	      }
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _currentFontSize, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _style$1, {
	      writable: true,
	      value: {
	        blockContent: '.sign-document__block-content',
	        blockPanel: '.sign-document__block-panel--wrapper',
	        blockLoading: 'sign-document-block-loading',
	        blockEditing: 'sign-document__block-wrapper-editing',
	        pageWithNotAllowed: 'sign-editor__content-document--active-move'
	      }
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _firstRenderReady, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('BX.Sign.Document.Block');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _id, options.id || null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _code, options.code);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _memberPart, options.part);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _document, options.document);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _onClickCallback, options.onClick);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _onRemoveCallback, options.onRemove);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _firstRenderReady, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _stylePanel, new Style({
	      block: babelHelpers.assertThisInitialized(_this),
	      data: options.style
	    }));
	    if (!babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _contentProviders)[babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _code)]) {
	      throw new Error("Content provider for '".concat(babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _code), "' not found."));
	    }
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _content, new (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _contentProviders)[babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _code)])(babelHelpers.assertThisInitialized(_this)));
	    _classPrivateMethodGet$b(babelHelpers.assertThisInitialized(_this), _createLayout, _createLayout2).call(babelHelpers.assertThisInitialized(_this));
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _layout), 'click', _classPrivateMethodGet$b(babelHelpers.assertThisInitialized(_this), _onClick, _onClick2).bind(babelHelpers.assertThisInitialized(_this)));
	    if (options.part > 1) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _allowMembers, true);
	    }
	    _this.renderStyle();
	    _this.setPosition(options.position ? options.position : babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _content).getInitDimension());
	    if (options.data) {
	      setTimeout(function () {
	        _this.setData(options.data);
	      }, 0);
	    }
	    if (!main_core.Type.isUndefined(_classStaticPrivateFieldSpecGet(Block, Block, _signContentColor)[_this.getCode()]) && babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _content) instanceof BlockWithSynchronizableStyleColor) {
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _content).updateColor(_classStaticPrivateFieldSpecGet(Block, Block, _signContentColor)[_this.getCode()]);
	    }
	    return _this;
	  }

	  /**
	   * Returns block's layout.
	   * @return {HTMLElement}
	   */
	  babelHelpers.createClass(Block, [{
	    key: "getLayout",
	    value: function getLayout() {
	      return babelHelpers.classPrivateFieldGet(this, _layout);
	    }
	    /**
	     * Returns Document instance.
	     * @return {Document}
	     */
	  }, {
	    key: "getDocument",
	    value: function getDocument() {
	      return babelHelpers.classPrivateFieldGet(this, _document);
	    }
	    /**
	     * Sets new data to the block.
	     * @param {any} data
	     */
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      babelHelpers.classPrivateFieldGet(this, _content).setData(data);
	      this.renderView();
	    }
	    /**
	     * Sets initial position to the block.
	     * @param {PositionType} position
	     */
	  }, {
	    key: "setPosition",
	    value: function setPosition(position) {
	      UI.setRect(babelHelpers.classPrivateFieldGet(this, _layout), position);
	    }
	    /**
	     * Returns block's data.
	     * @return {any}
	     */
	  }, {
	    key: "getData",
	    value: function getData() {
	      return babelHelpers.classPrivateFieldGet(this, _content).getData();
	    }
	    /**
	     * Returns position.
	     * @return {PositionType}
	     */
	  }, {
	    key: "getPosition",
	    value: function getPosition() {
	      var _babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _layout).getBoundingClientRect(),
	        top = _babelHelpers$classPr.top,
	        left = _babelHelpers$classPr.left,
	        width = _babelHelpers$classPr.width,
	        height = _babelHelpers$classPr.height;
	      top = Math.round(top);
	      left = Math.round(left);
	      width = Math.round(width);
	      height = Math.round(height);
	      var documentRect = babelHelpers.classPrivateFieldGet(this, _document).getLayout().getBoundingClientRect();
	      top -= Math.round(documentRect.top);
	      left -= Math.round(documentRect.left);
	      return {
	        top: top,
	        left: left,
	        width: width,
	        height: height
	      };
	    }
	    /**
	     * Returns block styles.
	     * @return {{{[key: string]: string}}}
	     */
	  }, {
	    key: "getStyle",
	    value: function getStyle() {
	      return _objectSpread$8(_objectSpread$8({}, babelHelpers.classPrivateFieldGet(this, _content).getStyles()), babelHelpers.classPrivateFieldGet(this, _stylePanel).collectStyles());
	    }
	    /**
	     * Returns id.
	     * @return {number|null}
	     */
	  }, {
	    key: "getId",
	    value: function getId() {
	      return babelHelpers.classPrivateFieldGet(this, _id) | null;
	    }
	    /**
	     * Returns code.
	     * @return {string}
	     */
	  }, {
	    key: "getCode",
	    value: function getCode() {
	      return babelHelpers.classPrivateFieldGet(this, _code);
	    }
	    /**
	     * Shows page's areas not allowed for block's placement.
	     */
	  }, {
	    key: "showNotAllowedArea",
	    value: function showNotAllowedArea() {
	      var _this$getDocument$tra = this.getDocument().transferPositionToPage(this.getPosition()),
	        page = _this$getDocument$tra.page;
	      var pageElement = document.querySelector(".sign-editor__content-document--page[data-page=\"".concat(page, "\"]"));
	      main_core.Dom.addClass(pageElement, babelHelpers.classPrivateFieldGet(this, _style$1).pageWithNotAllowed);
	    }
	  }, {
	    key: "hideNotAllowedArea",
	    value: function hideNotAllowedArea() {
	      var _this$getDocument$tra2 = this.getDocument().transferPositionToPage(this.getPosition()),
	        page = _this$getDocument$tra2.page;
	      var pageElement = document.querySelector(".sign-editor__content-document--page[data-page=\"".concat(page, "\"]"));
	      main_core.Dom.removeClass(pageElement, babelHelpers.classPrivateFieldGet(this, _style$1).pageWithNotAllowed);
	    }
	    /**
	     * Returns member part.
	     * @return {number}
	     */
	  }, {
	    key: "getMemberPart",
	    value: function getMemberPart() {
	      return babelHelpers.classPrivateFieldGet(this, _memberPart);
	    }
	    /**
	     * Handler on click to block.
	     */
	  }, {
	    key: "fireAction",
	    /**
	     * Calls block's action.
	     */
	    value: function fireAction() {
	      if (babelHelpers.classPrivateFieldGet(this, _content)['onActionClick']) {
	        if (babelHelpers.classPrivateFieldGet(this, _code) === 'text') {
	          main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _layout), babelHelpers.classPrivateFieldGet(this, _style$1).blockEditing);
	        }
	        babelHelpers.classPrivateFieldGet(this, _content)['onActionClick']();
	      }
	    }
	    /**
	     * Handler on member change.
	     * @param {number} part
	     */
	  }, {
	    key: "onMemberSelect",
	    value: function onMemberSelect(part) {
	      babelHelpers.classPrivateFieldSet(this, _memberPart, part);
	      this.assign();
	    }
	    /**
	     * Sets/removes awaiting class to the block.
	     * @param {boolean} flag
	     */
	  }, {
	    key: "await",
	    value: function _await(flag) {
	      var _this2 = this;
	      var blockLayouts = [];
	      if (!babelHelpers.classPrivateFieldGet(this, _content).isSingleton()) {
	        blockLayouts.push(this.getLayout());
	      } else {
	        var currentCode = this.getCode();
	        babelHelpers.classPrivateFieldGet(this, _document).getBlocks().map(function (block) {
	          if (block.getCode() === currentCode) {
	            blockLayouts.push(block.getLayout());
	          }
	        });
	      }
	      blockLayouts.map(function (blockLayout, key) {
	        if (flag) {
	          if (blockLayouts.length === key + 1) {
	            main_core.Dom.addClass(blockLayout, babelHelpers.classPrivateFieldGet(_this2, _style$1).blockLoading);
	          }
	        } else {
	          main_core.Dom.removeClass(blockLayout, babelHelpers.classPrivateFieldGet(_this2, _style$1).blockLoading);
	        }
	      });
	    }
	    /**
	     * Assigns block to the document (without saving).
	     */
	  }, {
	    key: "assign",
	    value: function assign() {
	      var _this3 = this;
	      var blockLayout = this.getLayout();
	      var blocksData = [];
	      var blocksInstance = [];
	      this["await"](true);
	      if (!babelHelpers.classPrivateFieldGet(this, _content).isSingleton()) {
	        blocksData.push({
	          code: babelHelpers.classPrivateFieldGet(this, _code),
	          part: babelHelpers.classPrivateFieldGet(this, _memberPart),
	          data: this.getData()
	        });
	        blocksInstance.push(this);
	      }
	      // if block is a singleton push all blocks with same code
	      else {
	        babelHelpers.classPrivateFieldGet(this, _document).getBlocks().map(function (block) {
	          if (block.getCode() === _this3.getCode() && block.getMemberPart() === _this3.getMemberPart()) {
	            blocksData.push({
	              code: block.getCode(),
	              part: block.getMemberPart(),
	              data: block.getData()
	            });
	            blocksInstance.push(block);
	          }
	        });
	      }
	      sign_backend.Backend.controller({
	        command: 'blank.assignBlocks',
	        postData: {
	          documentId: babelHelpers.classPrivateFieldGet(this, _document).getId(),
	          blocksData: blocksData
	        },
	        getData: {
	          code: babelHelpers.classPrivateFieldGet(this, _code)
	        }
	      }).then(function (result) {
	        if (main_core.Type.isArray(result)) {
	          result.map(function (block, i) {
	            blocksInstance[i].setData(block.data);
	          });
	        }
	        _this3["await"](false);
	        babelHelpers.classPrivateFieldGet(_this3, _document).showResizeArea(_this3);
	      })["catch"](function (result) {
	        main_core.Dom.remove(blockLayout);
	      });
	    }
	    /**
	     * Renders block within document's layout.
	     */
	  }, {
	    key: "renderView",
	    value: function renderView() {
	      var _this4 = this;
	      var contentTag = babelHelpers.classPrivateFieldGet(this, _layout).querySelector(babelHelpers.classPrivateFieldGet(this, _style$1).blockContent);

	      // content
	      main_core.Dom.clean(contentTag);
	      switch (babelHelpers.classPrivateFieldGet(this, _code).toLowerCase()) {
	        case 'stamp':
	        case 'mystamp':
	        case 'sign':
	        case 'mysign':
	          main_core.Dom.addClass(contentTag, '--image');
	      }
	      var resizeNode = babelHelpers.classPrivateFieldGet(this, _content).getViewContent();
	      main_core.Dom.append(resizeNode, contentTag);
	      main_core.Dom.addClass(resizeNode, '--' + babelHelpers.classPrivateFieldGet(this, _code).toLowerCase());
	      if (babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'requisites' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'myrequisites' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'date' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'number' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'stamp' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'mystamp' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'sign' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'mysign' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'reference' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'myreference' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'text') {
	        resizeNode.style.setProperty('display', 'block');
	        resizeNode.style.setProperty('overflow', 'hidden');
	        if (!this.observerReady) {
	          if (this.getStyle()['font-size']) {
	            this.maxTextSize = parseFloat(this.getStyle()['font-size']);
	          } else {
	            this.maxTextSize = 14;
	          }
	          this.isOverflownX = function (_ref) {
	            var clientHeight = _ref.clientHeight,
	              scrollHeight = _ref.scrollHeight;
	            return scrollHeight > clientHeight;
	          };
	          main_core_events.EventEmitter.subscribe(resizeNode.parentNode.parentNode, 'BX.Sign:setFontSize', function (param) {
	            if (param.data.fontSize) {
	              _this4.maxTextSize = param.data.fontSize;
	              _this4.resizeText({
	                element: param.target.querySelector('.sign-document__block-content > div'),
	                step: 0.5
	              });
	            }
	          });
	          this.resizeText = function (_ref2) {
	            var element = _ref2.element,
	              _ref2$minSize = _ref2.minSize,
	              minSize = _ref2$minSize === void 0 ? 1 : _ref2$minSize,
	              _ref2$step = _ref2.step,
	              step = _ref2$step === void 0 ? 1 : _ref2$step,
	              _ref2$unit = _ref2.unit,
	              unit = _ref2$unit === void 0 ? 'px' : _ref2$unit;
	            if (_this4.intervalTextResize) {
	              clearTimeout(_this4.intervalTextResize);
	            }
	            var i = minSize;
	            var overflow = false;
	            var parent = element.parentNode;
	            while (!overflow && i < _this4.maxTextSize) {
	              element.style.fontSize = "".concat(i).concat(unit);
	              overflow = _this4.isOverflownX(parent);
	              if (!overflow) {
	                i += step;
	              }
	            }
	            babelHelpers.classPrivateFieldSet(_this4, _currentFontSize, "".concat(i - step).concat(unit));
	            element.style.fontSize = babelHelpers.classPrivateFieldGet(_this4, _currentFontSize);
	            _this4.intervalTextResize = setTimeout(function () {
	              element.parentNode.style.setProperty('font-size', element.style.fontSize);
	              element.style.removeProperty('font-size', element.style.fontSize);
	              babelHelpers.classPrivateFieldGet(_this4, _stylePanel).updateFontSize(babelHelpers.classPrivateFieldGet(_this4, _currentFontSize));
	            }, 1000);
	          };
	          if (babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'requisites' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'myrequisites' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'reference' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'myreference' || babelHelpers.classPrivateFieldGet(this, _code).toLowerCase() === 'text') {
	            this.resizeText({
	              element: resizeNode,
	              step: 0.5
	            });
	          }
	          babelHelpers.classPrivateFieldGet(this, _content).subscribe(babelHelpers.classPrivateFieldGet(this, _content).events.onChange, _classPrivateMethodGet$b(this, _onContentChange, _onContentChange2).bind(this));
	          babelHelpers.classPrivateFieldGet(this, _content).subscribe(babelHelpers.classPrivateFieldGet(this, _content).events.onColorStyleChange, _classPrivateMethodGet$b(this, _onColorStyleChange, _onColorStyleChange2).bind(this));
	          this.observerReady = true;
	        }
	        if (babelHelpers.classPrivateFieldGet(this, _firstRenderReady)) {
	          this.resizeText({
	            element: resizeNode,
	            step: 0.5
	          });
	        }
	      }
	      babelHelpers.classPrivateFieldSet(this, _firstRenderReady, true);
	      if (babelHelpers.classPrivateFieldGet(this, _panelCreated)) {
	        return;
	      }

	      // action / style panel
	      var panelTag = babelHelpers.classPrivateFieldGet(this, _layout).querySelector(babelHelpers.classPrivateFieldGet(this, _style$1).blockPanel);
	      main_core.Dom.clean(panelTag);

	      // style
	      if (babelHelpers.classPrivateFieldGet(this, _content).isStyleAllowed()) {
	        main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _stylePanel).getLayout(), panelTag);
	      }

	      // action
	      main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _content).getActionButton(), panelTag);

	      // block caption
	      main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _content).getBlockCaption(), panelTag);

	      // member selector
	      if (babelHelpers.classPrivateFieldGet(this, _allowMembers)) {
	        main_core.Dom.append(UI.getMemberSelector(babelHelpers.classPrivateFieldGet(this, _document).getMembers(), babelHelpers.classPrivateFieldGet(this, _memberPart), this.onMemberSelect.bind(this)), panelTag);
	      }
	      babelHelpers.classPrivateFieldSet(this, _panelCreated, true);
	    }
	  }, {
	    key: "getCurrentFontSize",
	    value: function getCurrentFontSize() {
	      return babelHelpers.classPrivateFieldGet(this, _currentFontSize);
	    }
	    /**
	     * Calls when block starts being resized or moved.
	     */
	  }, {
	    key: "onStartChangePosition",
	    value: function onStartChangePosition() {
	      babelHelpers.classPrivateFieldGet(this, _content).onStartChangePosition();
	    }
	    /**
	     * Calls when block has placed on document.
	     */
	  }, {
	    key: "onPlaced",
	    value: function onPlaced() {
	      babelHelpers.classPrivateFieldGet(this, _content).onPlaced();
	    }
	    /**
	     * Calls when block saved.
	     */
	  }, {
	    key: "onSave",
	    value: function onSave() {
	      babelHelpers.classPrivateFieldGet(this, _content).onSave();
	    }
	    /**
	     * Calls when block removed.
	     */
	  }, {
	    key: "onRemove",
	    value: function onRemove() {
	      babelHelpers.classPrivateFieldGet(this, _content).onRemove();
	    }
	    /**
	     * Calls when click was out the block.
	     */
	  }, {
	    key: "onClickOut",
	    value: function onClickOut() {
	      babelHelpers.classPrivateFieldGet(this, _content).onClickOut();
	    }
	    /**
	     * Set block styles to layout.
	     */
	  }, {
	    key: "renderStyle",
	    value: function renderStyle() {
	      babelHelpers.classPrivateFieldGet(this, _stylePanel).applyStyles(babelHelpers.classPrivateFieldGet(this, _layout).querySelector(babelHelpers.classPrivateFieldGet(this, _style$1).blockContent));
	      babelHelpers.classPrivateFieldGet(this, _content).onStyleRender(babelHelpers.classPrivateFieldGet(this, _stylePanel).collectStyles());
	    }
	    /**
	     * Adjust actions panel.
	     */
	  }, {
	    key: "adjustActionsPanel",
	    value: function adjustActionsPanel() {
	      var blockLayout = this.getLayout();
	      var actionsPanel = blockLayout.querySelector('[data-role="sign-block__actions"]');
	      if (actionsPanel) {
	        var actionsPanelRect = actionsPanel.getBoundingClientRect();
	        var blockRect = blockLayout.getBoundingClientRect();
	        var a = actionsPanelRect.width;
	        var b = blockRect.width;
	        var detectBorder = (a - b) / 2;
	        if (detectBorder > blockRect.left) {
	          actionsPanel.style.marginLeft = detectBorder - 50 + 'px';
	        } else {
	          actionsPanel.style.marginLeft = 0;
	        }
	      }
	    }
	    /**
	     * Force saves the block.
	     */
	  }, {
	    key: "forceSave",
	    value: function forceSave() {
	      babelHelpers.classPrivateFieldGet(this, _layout).querySelector('[data-role="saveAction"]').click();
	    }
	    /**
	     * Creates layout for new block within document.
	     */
	  }, {
	    key: "isRemoved",
	    /**
	     * Returns true, if block was removed.
	     * @return {boolean}
	     */
	    value: function isRemoved() {
	      return babelHelpers.classPrivateFieldGet(this, _layout).hidden === true;
	    }
	  }]);
	  return Block;
	}(main_core_events.EventEmitter);
	function _onClick2() {
	  if (babelHelpers.classPrivateFieldGet(this, _onClickCallback)) {
	    babelHelpers.classPrivateFieldGet(this, _onClickCallback).call(this, this);
	  }
	}
	function _createLayout2() {
	  var _this5 = this;
	  babelHelpers.classPrivateFieldSet(this, _layout, UI.getBlockLayout({
	    onSave: function onSave(event) {
	      _this5.renderView();
	      babelHelpers.classPrivateFieldGet(_this5, _document).unMuteResizeArea();
	      babelHelpers.classPrivateFieldGet(_this5, _document).hideResizeArea();
	      babelHelpers.classPrivateFieldGet(_this5, _document).setSavingMark(false);
	      _this5.onSave();
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this5, _layout), babelHelpers.classPrivateFieldGet(_this5, _style$1).blockEditing);
	      event.stopPropagation();
	    },
	    onRemove: function onRemove(event) {
	      if (babelHelpers.classPrivateFieldGet(_this5, _onRemoveCallback)) {
	        babelHelpers.classPrivateFieldGet(_this5, _onRemoveCallback).call(_this5, _this5);
	      }
	      babelHelpers.classPrivateFieldGet(_this5, _layout).hidden = true;
	      _this5.onRemove();
	      event.stopPropagation();
	    }
	  }));
	  var nodeForPosition = document.body.querySelector('[data-role="sign-editor__content"]');
	  var documentLayoutRect = babelHelpers.classPrivateFieldGet(this, _document).getLayout().getBoundingClientRect();
	  var blockInitDim = babelHelpers.classPrivateFieldGet(this, _content).getInitDimension();
	  var position = {
	    top: Math.min(nodeForPosition.scrollTop + nodeForPosition.offsetHeight / 2, documentLayoutRect.height - blockInitDim.height),
	    left: documentLayoutRect.width / 2 - 100
	  };
	  if (this.getDocument().inDeadZone(position.top, position.top + blockInitDim.height)) {
	    position.top += blockInitDim.height + this.getDocument().getPagesGap();
	  }
	  this.setPosition(position);
	}
	function _onContentChange2() {
	  var content = babelHelpers.classPrivateFieldGet(this, _content);
	  if (!(content instanceof Text)) {
	    return;
	  }
	  this.resizeText({
	    element: content.getContainer(),
	    step: 0.5
	  });
	}
	function _onColorStyleChange2() {
	  var _babelHelpers$classPr2,
	    _this6 = this;
	  if (!(babelHelpers.classPrivateFieldGet(this, _content) instanceof Sign || babelHelpers.classPrivateFieldGet(this, _content) instanceof MySign)) {
	    return;
	  }
	  var newSignColor = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _content).getStyles()) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.color;
	  if (main_core.Type.isNil(newSignColor)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldGet(this, _document).getBlocks().filter(function (block) {
	    return (babelHelpers.classPrivateFieldGet(block, _content) instanceof Sign || babelHelpers.classPrivateFieldGet(block, _content) instanceof MySign) && block.getCode() === _this6.getCode();
	  }).forEach(function (block) {
	    if (babelHelpers.classPrivateFieldGet(block, _content) instanceof BlockWithSynchronizableStyleColor) {
	      babelHelpers.classPrivateFieldGet(block, _content).updateColor(newSignColor);
	      babelHelpers.classPrivateFieldGet(block, _stylePanel).updateColor(newSignColor);
	    }
	  });
	  _classStaticPrivateFieldSpecGet(Block, Block, _signContentColor)[this.getCode()] = newSignColor;
	}
	var _signContentColor = {
	  writable: true,
	  value: {}
	};

	function ownKeys$9(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$9(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$9(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$9(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$c(obj, privateSet) { _checkPrivateRedeclaration$c(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$9(obj, privateMap, value) { _checkPrivateRedeclaration$c(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$c(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$c(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _wrapperLayout = /*#__PURE__*/new WeakMap();
	var _wrapperLayoutRect = /*#__PURE__*/new WeakMap();
	var _layout$1 = /*#__PURE__*/new WeakMap();
	var _fullEditorContent = /*#__PURE__*/new WeakMap();
	var _linkedElement = /*#__PURE__*/new WeakMap();
	var _linkedBlock = /*#__PURE__*/new WeakMap();
	var _style$2 = /*#__PURE__*/new WeakMap();
	var _onResize = /*#__PURE__*/new WeakSet();
	var _onClick$1 = /*#__PURE__*/new WeakSet();
	var _initResize = /*#__PURE__*/new WeakSet();
	var _initMove = /*#__PURE__*/new WeakSet();
	var Resize = /*#__PURE__*/function () {
	  /**
	   * Constructor.
	   * @param {ResizeOptions} options
	   */
	  function Resize(options) {
	    babelHelpers.classCallCheck(this, Resize);
	    _classPrivateMethodInitSpec$c(this, _initMove);
	    _classPrivateMethodInitSpec$c(this, _initResize);
	    _classPrivateMethodInitSpec$c(this, _onClick$1);
	    _classPrivateMethodInitSpec$c(this, _onResize);
	    _classPrivateFieldInitSpec$9(this, _wrapperLayout, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _wrapperLayoutRect, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _layout$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _fullEditorContent, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _linkedElement, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _linkedBlock, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$9(this, _style$2, {
	      writable: true,
	      value: {
	        resizeContainer: '.sign-area-resizable-controls > div',
	        moveContainer: '.sign-document__resize-area',
	        blockEditing: 'sign-document__block-wrapper-editing'
	      }
	    });
	    if (!main_core.Type.isDomNode(options.wrapperLayout)) {
	      throw new Error('Option wrapperLayout is undefined or not valid DOM Element.');
	    }
	    babelHelpers.classPrivateFieldSet(this, _layout$1, UI.getResizeArea());
	    babelHelpers.classPrivateFieldSet(this, _wrapperLayout, options.wrapperLayout);
	    babelHelpers.classPrivateFieldSet(this, _wrapperLayoutRect, babelHelpers.classPrivateFieldGet(this, _wrapperLayout).getBoundingClientRect());
	    babelHelpers.classPrivateFieldSet(this, _fullEditorContent, document.body.querySelector('[data-role="sign-editor__content"]'));
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _layout$1), 'click', _classPrivateMethodGet$c(this, _onClick$1, _onClick2$1).bind(this));
	    // Event.bind(window, 'resize', this.#onResize.bind(this)); // fix ticket 161618

	    _classPrivateMethodGet$c(this, _initResize, _initResize2).call(this);
	    _classPrivateMethodGet$c(this, _initMove, _initMove2).call(this);
	  }

	  /**
	   * Returns layout of resizing area.
	   * @return {HTMLElement}
	   */
	  babelHelpers.createClass(Resize, [{
	    key: "getLayout",
	    value: function getLayout() {
	      return babelHelpers.classPrivateFieldGet(this, _layout$1);
	    }
	    /**
	     * Shows resizing area over the element.
	     * @param {Block} block
	     */
	  }, {
	    key: "show",
	    value: function show(block) {
	      if (babelHelpers.classPrivateFieldGet(this, _linkedBlock) === block) {
	        return;
	      }
	      var pointRect = block.getLayout().getBoundingClientRect();
	      babelHelpers.classPrivateFieldGet(this, _layout$1).style.display = 'block';
	      if (babelHelpers.classPrivateFieldGet(this, _linkedElement)) {
	        babelHelpers.classPrivateFieldGet(this, _linkedElement).removeAttribute('data-active');
	      }
	      babelHelpers.classPrivateFieldSet(this, _linkedBlock, block);
	      babelHelpers.classPrivateFieldSet(this, _linkedElement, block.getLayout());
	      babelHelpers.classPrivateFieldGet(this, _linkedElement).setAttribute('data-active', 1);
	      UI.setRect(babelHelpers.classPrivateFieldGet(this, _layout$1), {
	        top: pointRect.top - babelHelpers.classPrivateFieldGet(this, _wrapperLayoutRect).top + babelHelpers.classPrivateFieldGet(this, _fullEditorContent).scrollTop,
	        left: pointRect.left - babelHelpers.classPrivateFieldGet(this, _wrapperLayoutRect).left,
	        width: pointRect.width + Resize.borderDelta,
	        height: pointRect.height + Resize.borderDelta
	      });
	    }
	    /**
	     * Hides resizing area.
	     */
	  }, {
	    key: "hide",
	    value: function hide() {
	      babelHelpers.classPrivateFieldSet(this, _linkedBlock, null);
	      babelHelpers.classPrivateFieldGet(this, _layout$1).style.display = 'none';
	      if (babelHelpers.classPrivateFieldGet(this, _linkedElement)) {
	        babelHelpers.classPrivateFieldGet(this, _linkedElement).removeAttribute('data-active');
	      }
	    }
	    /**
	     * Returns linked block.
	     * @return {Block|null}
	     */
	  }, {
	    key: "getLinkedBlock",
	    value: function getLinkedBlock() {
	      return babelHelpers.classPrivateFieldGet(this, _linkedBlock);
	    }
	    /**
	     * On window resize.
	     */
	  }]);
	  return Resize;
	}();
	function _onClick2$1(e) {
	  if (this.moving || this.resizing) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _linkedBlock)) {
	    babelHelpers.classPrivateFieldGet(this, _linkedBlock).fireAction();
	  }
	}
	function _initResize2() {
	  var _this = this;
	  var initialRect = null;
	  var draggable = new ui_draganddrop_draggable.Draggable({
	    container: babelHelpers.classPrivateFieldGet(this, _wrapperLayout),
	    draggable: babelHelpers.classPrivateFieldGet(this, _style$2).resizeContainer,
	    type: BX.UI.DragAndDrop.Draggable.HEADLESS
	  });
	  this.textResize = {
	    wrapper: null,
	    content: null
	  };
	  draggable.subscribe('start', function (event) {
	    if (babelHelpers.classPrivateFieldGet(_this, _layout$1).getAttribute('data-disable')) {
	      return;
	    }
	    if (!_this.textResize.wrapper || !_this.textResize.content) {
	      _this.textResize = {
	        wrapper: babelHelpers.classPrivateFieldGet(_this, _linkedBlock).getLayout(),
	        content: babelHelpers.classPrivateFieldGet(_this, _linkedBlock).getLayout().querySelector('.sign-document__block-content')
	      };
	    }
	    main_core_events.EventEmitter.emit('BX.Sign:resizeStart', draggable);
	    initialRect = babelHelpers.classPrivateFieldGet(_this, _layout$1).getBoundingClientRect();
	    babelHelpers.classPrivateFieldGet(_this, _linkedBlock).onStartChangePosition();
	    _this.positionLast = null;
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this, _linkedBlock).getLayout(), babelHelpers.classPrivateFieldGet(_this, _style$2).blockEditing);
	  }).subscribe('end', function (event) {
	    setTimeout(function () {
	      _this.resizing = false;
	    }, 0);
	  }).subscribe('move', function (event) {
	    if (babelHelpers.classPrivateFieldGet(_this, _layout$1).getAttribute('data-disable')) {
	      return;
	    }
	    _this.resizing = true;
	    var left = null;
	    var top = null;
	    var bottomResize = false;
	    var _initialRect = initialRect,
	      width = _initialRect.width,
	      height = _initialRect.height;
	    var data = event.getData();
	    var areaRect = babelHelpers.classPrivateFieldGet(_this, _layout$1).getBoundingClientRect();
	    var wrapperRect = babelHelpers.classPrivateFieldGet(_this, _wrapperLayout).getBoundingClientRect();
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --left-top')) {
	      left = Math.max(0, initialRect.left + data.offsetX - babelHelpers.classPrivateFieldGet(_this, _wrapperLayoutRect).left);
	      top = Math.max(0, initialRect.top + data.offsetY - babelHelpers.classPrivateFieldGet(_this, _wrapperLayoutRect).top + babelHelpers.classPrivateFieldGet(_this, _fullEditorContent).scrollTop);
	      width = initialRect.width - data.offsetX;
	      height = initialRect.height - data.offsetY;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-top')) {
	      top = Math.max(0, initialRect.top + data.offsetY - babelHelpers.classPrivateFieldGet(_this, _wrapperLayoutRect).top + babelHelpers.classPrivateFieldGet(_this, _fullEditorContent).scrollTop);
	      height = initialRect.height - data.offsetY;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --right-top')) {
	      top = Math.max(0, initialRect.top + data.offsetY - babelHelpers.classPrivateFieldGet(_this, _wrapperLayoutRect).top + babelHelpers.classPrivateFieldGet(_this, _fullEditorContent).scrollTop);
	      width = initialRect.width + data.offsetX;
	      height = initialRect.height - data.offsetY;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-right')) {
	      width = initialRect.width + data.offsetX;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --right-bottom')) {
	      width = initialRect.width + data.offsetX;
	      height = initialRect.height + data.offsetY;
	      bottomResize = true;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-bottom')) {
	      height = initialRect.height + data.offsetY;
	      bottomResize = true;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --left-bottom')) {
	      left = initialRect.left + data.offsetX - babelHelpers.classPrivateFieldGet(_this, _wrapperLayoutRect).left;
	      width = initialRect.width - data.offsetX;
	      height = initialRect.height + data.offsetY;
	      bottomResize = true;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-left')) {
	      left = initialRect.left + data.offsetX - babelHelpers.classPrivateFieldGet(_this, _wrapperLayoutRect).left;
	      width = initialRect.width - data.offsetX;
	    }
	    if (width < 60 || height < 20) {
	      return;
	    }
	    var newPosition = {
	      width: width,
	      height: height
	    };
	    if (newPosition['width'] + areaRect.left - wrapperRect.left > wrapperRect.width) {
	      width = newPosition['width'] = wrapperRect.width + wrapperRect.left - areaRect.left;
	    }
	    if (newPosition['height'] + areaRect.top - wrapperRect.top > wrapperRect.height) {
	      height = newPosition['height'] = wrapperRect.height + wrapperRect.top - areaRect.top;
	    }
	    if (left) {
	      if (left < 0) {
	        left = 0;
	      }
	      newPosition['left'] = left;
	    }
	    if (top) {
	      newPosition['top'] = top;
	    }
	    var calcDeathTop = initialRect.top + babelHelpers.classPrivateFieldGet(_this, _fullEditorContent).scrollTop;
	    var notAddDeathMargin = true;
	    if (newPosition.top) {
	      calcDeathTop = newPosition.top;
	      notAddDeathMargin = false;
	    }
	    if (babelHelpers.classPrivateFieldGet(_this, _linkedBlock).getDocument().inDeadZone(calcDeathTop, calcDeathTop + newPosition.height, notAddDeathMargin)) {
	      if (!bottomResize) {
	        babelHelpers.classPrivateFieldGet(_this, _linkedBlock).showNotAllowedArea();
	      }
	      return;
	    }
	    var newPositionLinked = Object.assign({}, _objectSpread$9(_objectSpread$9({}, newPosition), {}, {
	      width: width - Resize.borderDelta,
	      height: height - Resize.borderDelta
	    }));
	    UI.setRect(babelHelpers.classPrivateFieldGet(_this, _layout$1), newPosition);
	    UI.setRect(babelHelpers.classPrivateFieldGet(_this, _linkedElement), newPositionLinked);
	    babelHelpers.classPrivateFieldGet(_this, _linkedBlock).renderView();
	    babelHelpers.classPrivateFieldGet(_this, _linkedBlock).getDocument().setSavingMark(false);
	    babelHelpers.classPrivateFieldGet(_this, _linkedBlock).adjustActionsPanel();
	  });
	}
	function _initMove2() {
	  var _this2 = this;
	  var dragArea = babelHelpers.classPrivateFieldGet(this, _wrapperLayout);
	  var widthInProcess;
	  var draggable = new ui_draganddrop_draggable.Draggable({
	    container: dragArea,
	    draggable: babelHelpers.classPrivateFieldGet(this, _style$2).moveContainer,
	    type: ui_draganddrop_draggable.Draggable.HEADLESS
	  });
	  draggable.subscribe('start', function (event) {
	    if (babelHelpers.classPrivateFieldGet(_this2, _layout$1).getAttribute('data-disable')) {
	      return;
	    }
	    main_core_events.EventEmitter.emit('BX.Sign:moveStart', draggable);
	    if (_this2.resizing) {
	      return;
	    }
	    var _event$getData = event.getData(),
	      source = _event$getData.source;
	    _this2.position = main_core.Dom.getPosition(source);
	    babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).onStartChangePosition();
	    _this2.positionLast = null;
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).getLayout(), babelHelpers.classPrivateFieldGet(_this2, _style$2).blockEditing);
	  }).subscribe('end', function (event) {
	    setTimeout(function () {
	      _this2.moving = false;
	    }, 0);
	    if (!babelHelpers.classPrivateFieldGet(_this2, _linkedBlock)) {
	      return;
	    }
	    if (_this2.resizing) {
	      return;
	    }
	    widthInProcess = null;
	    var data = event.getData();
	    var moveTopDelta = null;
	    if (_this2.positionLast) {
	      moveTopDelta = babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).getDocument().inDeadZone(_this2.positionLast.top, _this2.positionLast.top + _this2.positionLast.height);
	    }
	    babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).hideNotAllowedArea();
	    if (moveTopDelta) {
	      UI.setRect(data.source, {
	        top: _this2.positionLast.top + moveTopDelta
	      });
	      UI.setRect(babelHelpers.classPrivateFieldGet(_this2, _linkedElement), {
	        top: _this2.positionLast.top + moveTopDelta
	      });
	    }
	  }).subscribe('move', function (event) {
	    if (babelHelpers.classPrivateFieldGet(_this2, _layout$1).getAttribute('data-disable')) {
	      return;
	    }
	    if (_this2.resizing) {
	      return;
	    }
	    _this2.moving = true;
	    var data = event.getData();
	    var source = data.source,
	      offsetY = data.offsetY,
	      offsetX = data.offsetX;
	    var areaRect = data.source.getBoundingClientRect();
	    var wrapperRect = babelHelpers.classPrivateFieldGet(_this2, _wrapperLayout).getBoundingClientRect();
	    if (_this2.position) {
	      var newPosition = {
	        top: offsetY - babelHelpers.classPrivateFieldGet(_this2, _wrapperLayoutRect).top + _this2.position.y + babelHelpers.classPrivateFieldGet(_this2, _fullEditorContent).scrollTop,
	        left: offsetX - babelHelpers.classPrivateFieldGet(_this2, _wrapperLayoutRect).left + _this2.position.x,
	        width: widthInProcess ? widthInProcess : _this2.position.width,
	        height: _this2.position.height
	      };
	      if (newPosition.left < 0) {
	        newPosition.left = 0;
	      }
	      if (newPosition.top < 0) {
	        newPosition.top = 0;
	      }
	      if (newPosition.top + areaRect.height > wrapperRect.height) {
	        newPosition.top = wrapperRect.height - areaRect.height;
	      }
	      if (newPosition.left + areaRect.width > wrapperRect.width) {
	        babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).renderView();
	        if (wrapperRect.width - newPosition.left > 50) {
	          widthInProcess = newPosition.width = wrapperRect.width - newPosition.left;
	        } else {
	          newPosition.left = wrapperRect.width - areaRect.width;
	        }
	      }
	      _this2.positionLast = Object.assign({}, newPosition);
	      var moveTopDelta = null;
	      if (_this2.positionLast) {
	        moveTopDelta = babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).getDocument().inDeadZone(_this2.positionLast.top, _this2.positionLast.top + _this2.positionLast.height);
	      }
	      if (moveTopDelta > 0) {
	        babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).showNotAllowedArea();
	      } else {
	        babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).hideNotAllowedArea();
	      }
	      var newPositionLinked = Object.assign({}, _objectSpread$9(_objectSpread$9({}, newPosition), {}, {
	        width: newPosition.width - Resize.borderDelta,
	        height: newPosition.height - Resize.borderDelta
	      }));
	      UI.setRect(source, newPosition);
	      UI.setRect(babelHelpers.classPrivateFieldGet(_this2, _linkedElement), newPositionLinked);
	      babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).getDocument().setSavingMark(false);
	      babelHelpers.classPrivateFieldGet(_this2, _linkedBlock).adjustActionsPanel();
	    }
	  });
	}
	babelHelpers.defineProperty(Resize, "borderDelta", 2);

	var _templateObject$d, _templateObject2$9, _templateObject3$5;
	function _classPrivateMethodInitSpec$d(obj, privateSet) { _checkPrivateRedeclaration$d(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$a(obj, privateMap, value) { _checkPrivateRedeclaration$d(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$d(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$d(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _documentId = /*#__PURE__*/new WeakMap();
	var _companyId = /*#__PURE__*/new WeakMap();
	var _initiatorName = /*#__PURE__*/new WeakMap();
	var _entityId = /*#__PURE__*/new WeakMap();
	var _blankId = /*#__PURE__*/new WeakMap();
	var _pagesMinHeight = /*#__PURE__*/new WeakMap();
	var _disableEdit = /*#__PURE__*/new WeakMap();
	var _closeDemoContent = /*#__PURE__*/new WeakMap();
	var _members = /*#__PURE__*/new WeakMap();
	var _documentLayout = /*#__PURE__*/new WeakMap();
	var _documentLayoutRect = /*#__PURE__*/new WeakMap();
	var _resizeArea = /*#__PURE__*/new WeakMap();
	var _blocks = /*#__PURE__*/new WeakMap();
	var _pagesRects = /*#__PURE__*/new WeakMap();
	var _seams = /*#__PURE__*/new WeakMap();
	var _config = /*#__PURE__*/new WeakMap();
	var _helpBtnElementId = /*#__PURE__*/new WeakMap();
	var _helpBtnHelperArticleCode = /*#__PURE__*/new WeakMap();
	var _initPagesRect = /*#__PURE__*/new WeakSet();
	var _relativeToAbsolute = /*#__PURE__*/new WeakSet();
	var _pixelToPercent = /*#__PURE__*/new WeakSet();
	var _percentToPixel = /*#__PURE__*/new WeakSet();
	var _addBlock = /*#__PURE__*/new WeakSet();
	var _onRepositoryItemClick = /*#__PURE__*/new WeakSet();
	var _initBlocks = /*#__PURE__*/new WeakSet();
	var _initRepository = /*#__PURE__*/new WeakSet();
	var _startDisabledEditDocumentTour = /*#__PURE__*/new WeakSet();
	var _startEditDocumentTour = /*#__PURE__*/new WeakSet();
	var Document = /*#__PURE__*/function () {
	  /**
	   * Constructor.
	   * @param {DocumentOptions} options
	   */
	  function Document(options) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Document);
	    _classPrivateMethodInitSpec$d(this, _startEditDocumentTour);
	    _classPrivateMethodInitSpec$d(this, _startDisabledEditDocumentTour);
	    _classPrivateMethodInitSpec$d(this, _initRepository);
	    _classPrivateMethodInitSpec$d(this, _initBlocks);
	    _classPrivateMethodInitSpec$d(this, _onRepositoryItemClick);
	    _classPrivateMethodInitSpec$d(this, _addBlock);
	    _classPrivateMethodInitSpec$d(this, _percentToPixel);
	    _classPrivateMethodInitSpec$d(this, _pixelToPercent);
	    _classPrivateMethodInitSpec$d(this, _relativeToAbsolute);
	    _classPrivateMethodInitSpec$d(this, _initPagesRect);
	    _classPrivateFieldInitSpec$a(this, _documentId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _companyId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _initiatorName, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _entityId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _blankId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _pagesMinHeight, {
	      writable: true,
	      value: 0
	    });
	    _classPrivateFieldInitSpec$a(this, _disableEdit, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _closeDemoContent, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$a(this, _members, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _documentLayout, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _documentLayoutRect, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _resizeArea, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _blocks, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec$a(this, _pagesRects, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec$a(this, _seams, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec$a(this, _config, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _helpBtnElementId, {
	      writable: true,
	      value: 'sign-editor-help-btn'
	    });
	    _classPrivateFieldInitSpec$a(this, _helpBtnHelperArticleCode, {
	      writable: true,
	      value: '16571388'
	    });
	    if (Document.alreadyInit) {
	      return;
	    }
	    if (!main_core.Type.isDomNode(options.documentLayout)) {
	      throw new Error('Option documentLayout is undefined or not valid DOM Element.');
	    }
	    Document.alreadyInit = true;
	    babelHelpers.classPrivateFieldSet(this, _documentId, options.documentId);
	    babelHelpers.classPrivateFieldSet(this, _companyId, options.companyId);
	    babelHelpers.classPrivateFieldSet(this, _initiatorName, options.initiatorName);
	    babelHelpers.classPrivateFieldSet(this, _entityId, options.entityId);
	    babelHelpers.classPrivateFieldSet(this, _blankId, options.blankId);
	    babelHelpers.classPrivateFieldSet(this, _documentLayout, options.documentLayout);
	    babelHelpers.classPrivateFieldSet(this, _documentLayoutRect, babelHelpers.classPrivateFieldGet(this, _documentLayout).getBoundingClientRect());
	    babelHelpers.classPrivateFieldSet(this, _disableEdit, options.disableEdit);
	    babelHelpers.classPrivateFieldSet(this, _config, options.config);
	    babelHelpers.classPrivateFieldSet(this, _members, options.members.filter(function (member) {
	      return member.cid !== 0;
	    }));
	    _classPrivateMethodGet$d(this, _initPagesRect, _initPagesRect2).call(this);
	    if (!babelHelpers.classPrivateFieldGet(this, _disableEdit)) {
	      babelHelpers.classPrivateFieldSet(this, _resizeArea, new Resize({
	        wrapperLayout: babelHelpers.classPrivateFieldGet(this, _documentLayout)
	      }));
	      main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _resizeArea).getLayout(), babelHelpers.classPrivateFieldGet(this, _documentLayout));
	    }
	    if (options.repositoryItems && !babelHelpers.classPrivateFieldGet(this, _disableEdit)) {
	      _classPrivateMethodGet$d(this, _initRepository, _initRepository2).call(this, options.repositoryItems);
	    }
	    if (options.blocks) {
	      _classPrivateMethodGet$d(this, _initBlocks, _initBlocks2).call(this, options.blocks);
	    }
	    if (options.saveButton) {
	      this.onSave(options);
	    }
	    this.onCloseSlider();
	    main_core.Event.bind(document.body, 'click', function (event) {
	      if (event.target.getAttribute('data-role')) {
	        if (babelHelpers.classPrivateFieldGet(_this, _resizeArea)) {
	          var activeBlock = babelHelpers.classPrivateFieldGet(_this, _resizeArea).getLinkedBlock();
	          if (activeBlock) {
	            activeBlock.onClickOut();
	          }
	        }
	      }
	    });
	    if (options.closeDemoContent) {
	      main_core.Event.bind(options.closeDemoContent, 'click', function (event) {
	        babelHelpers.classPrivateFieldSet(_this, _closeDemoContent, true);
	      });
	    }
	    main_core.Event.bind(document.getElementById(babelHelpers.classPrivateFieldGet(this, _helpBtnElementId)), 'click', function (event) {
	      top.BX.Helper.show("redirect=detail&code=" + babelHelpers.classPrivateFieldGet(_this, _helpBtnHelperArticleCode));
	      event.preventDefault();
	    });
	    if (babelHelpers.classPrivateFieldGet(this, _disableEdit)) {
	      _classPrivateMethodGet$d(this, _startDisabledEditDocumentTour, _startDisabledEditDocumentTour2).call(this);
	    } else {
	      _classPrivateMethodGet$d(this, _startEditDocumentTour, _startEditDocumentTour2).call(this);
	    }
	  }

	  /**
	   * Detects pages rect.
	   */
	  babelHelpers.createClass(Document, [{
	    key: "transferPositionToPage",
	    /**
	     * Transfers absolute position to page position and sets page number.
	     * @param {PositionType} position
	     * @return {PositionType}
	     */
	    value: function transferPositionToPage(position) {
	      position.page = 1;
	      for (var i = 0, c = babelHelpers.classPrivateFieldGet(this, _pagesRects).length; i < c; i++) {
	        var height = babelHelpers.classPrivateFieldGet(this, _pagesRects)[i].height;
	        if (i !== 0)
	          // skip gap for first page
	          {
	            position.top -= Document.gapBetweenPages;
	          }
	        position.top -= height;
	        if (position.top < 0) {
	          position.top += height;
	          break;
	        } else {
	          position.page++;
	        }
	      }
	      return position;
	    }
	    /**
	     * Transfers pixel of position to percent.
	     * @param {PositionType} position
	     * @return {PositionType}
	     */
	  }, {
	    key: "getMinPageHeight",
	    /**
	     * Returns minimal pages height.
	     * @return {number}
	     */
	    value: function getMinPageHeight() {
	      return babelHelpers.classPrivateFieldGet(this, _pagesMinHeight) - Document.marginTop;
	    }
	    /**
	     * Returns document id.
	     * @return {number}
	     */
	  }, {
	    key: "getId",
	    value: function getId() {
	      return babelHelpers.classPrivateFieldGet(this, _documentId);
	    }
	    /**
	     * Returns document entity id.
	     * @return {number}
	     */
	  }, {
	    key: "getEntityId",
	    value: function getEntityId() {
	      return babelHelpers.classPrivateFieldGet(this, _entityId);
	    }
	    /**
	     * Returns document's layout.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      return babelHelpers.classPrivateFieldGet(this, _documentLayout);
	    }
	    /**
	     * Returns number pairs (seams between pages).
	     * @return {Array<Array<number, number>>}
	     */
	  }, {
	    key: "getSeams",
	    value: function getSeams() {
	      return babelHelpers.classPrivateFieldGet(this, _seams);
	    }
	    /**
	     * Returns gap between pages.
	     * @return {number}
	     */
	  }, {
	    key: "getPagesGap",
	    value: function getPagesGap() {
	      return Document.gapBetweenPages + Document.marginTop;
	    }
	    /**
	     * Returns document's members
	     * @return {Array<MemberItem>}
	     */
	  }, {
	    key: "getMembers",
	    value: function getMembers() {
	      return babelHelpers.classPrivateFieldGet(this, _members);
	    }
	    /**
	     * Returns member item by member part.
	     * @param {number} part
	     * @return {null}
	     */
	  }, {
	    key: "getMemberByPart",
	    value: function getMemberByPart(part) {
	      var returnMember = null;
	      babelHelpers.classPrivateFieldGet(this, _members).map(function (member) {
	        if (member.part === part) {
	          returnMember = member;
	        }
	      });
	      return returnMember;
	    }
	    /**
	     * Returns company id.
	     * @return {number}
	     */
	  }, {
	    key: "getCompanyId",
	    value: function getCompanyId() {
	      return babelHelpers.classPrivateFieldGet(this, _companyId);
	    }
	    /**
	     * @return {string}
	     */
	  }, {
	    key: "getInitiatorName",
	    value: function getInitiatorName() {
	      return babelHelpers.classPrivateFieldGet(this, _initiatorName);
	    }
	    /**
	     * Returns Config object.
	     * @return {Config}
	     */
	  }, {
	    key: "getConfig",
	    value: function getConfig() {
	      return babelHelpers.classPrivateFieldGet(this, _config);
	    }
	    /**
	     * Hides resizing area.
	     */
	  }, {
	    key: "hideResizeArea",
	    value: function hideResizeArea() {
	      babelHelpers.classPrivateFieldGet(this, _resizeArea).hide();
	    }
	    /**
	     * Shows resizing area over the block.
	     * @param {Block} block
	     */
	  }, {
	    key: "showResizeArea",
	    value: function showResizeArea(block) {
	      babelHelpers.classPrivateFieldGet(this, _resizeArea).show(block);
	    }
	    /**
	     * Mutes resizing area.
	     */
	  }, {
	    key: "muteResizeArea",
	    value: function muteResizeArea() {
	      babelHelpers.classPrivateFieldGet(this, _resizeArea).getLayout().setAttribute('data-disable', 1);
	    }
	    /**
	     * Unmutes resizing area.
	     */
	  }, {
	    key: "unMuteResizeArea",
	    value: function unMuteResizeArea() {
	      babelHelpers.classPrivateFieldGet(this, _resizeArea).getLayout().removeAttribute('data-disable');
	    }
	    /**
	     * Adds block to the document.
	     * @param {BlockItem} data Block data.
	     * @param {boolean} seamShift If true, will be added seam shift to top's position.
	     * @return {Block}
	     */
	  }, {
	    key: "getBlocks",
	    /**
	     * Returns blocks collection.
	     * @return {Array<Block>}
	     */
	    value: function getBlocks() {
	      return babelHelpers.classPrivateFieldGet(this, _blocks);
	    }
	    /**
	     * Saves document and closes slider.
	     * @return {Promise}
	     */
	  }, {
	    key: "save",
	    value: function save() {
	      var _this2 = this;
	      var postData = [];
	      var realDocumentWidth = document.querySelector('[data-type="document-layout"]').offsetWidth;
	      babelHelpers.classPrivateFieldGet(this, _blocks).map(function (block) {
	        if (block.isRemoved()) {
	          return;
	        }
	        var position = block.getPosition();
	        position = _this2.transferPositionToPage(position);
	        position = _classPrivateMethodGet$d(_this2, _pixelToPercent, _pixelToPercent2).call(_this2, position);
	        position.realDocumentWidthPx = realDocumentWidth ? realDocumentWidth : null;
	        postData.push({
	          id: block.getId(),
	          code: block.getCode(),
	          data: block.getData(),
	          part: block.getMemberPart(),
	          style: block.getStyle(),
	          position: position
	        });
	      });
	      var params = {};
	      if (babelHelpers.classPrivateFieldGet(this, _closeDemoContent)) {
	        params['closeDemoContent'] = true;
	      }
	      return sign_backend.Backend.controller({
	        command: 'blank.save',
	        postData: {
	          documentId: babelHelpers.classPrivateFieldGet(this, _documentId),
	          blocks: postData.length > 0 ? postData : [],
	          params: params
	        }
	      })["catch"](function (response) {
	        var _response$errors$, _response$errors$$cus;
	        if (((_response$errors$ = response.errors[0]) === null || _response$errors$ === void 0 ? void 0 : (_response$errors$$cus = _response$errors$.customData) === null || _response$errors$$cus === void 0 ? void 0 : _response$errors$$cus.field) === 'requisites') {
	          var _response$errors$2;
	          var popup = new main_popup.Popup({
	            id: 'sign_document_error_popup',
	            titleBar: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE_TITLE'),
	            content: BX.util.htmlspecialchars((_response$errors$2 = response.errors[0]) === null || _response$errors$2 === void 0 ? void 0 : _response$errors$2.message),
	            buttons: [new ui_buttons.Button({
	              text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE'),
	              color: ui_buttons.ButtonColor.SUCCESS,
	              onclick: function onclick() {
	                var _response$errors$3, _response$errors$4;
	                sign_backend.Backend.controller({
	                  command: 'document.restoreRequisiteFields',
	                  postData: {
	                    documentId: babelHelpers.classPrivateFieldGet(_this2, _documentId),
	                    presetId: (_response$errors$3 = response.errors[0]) === null || _response$errors$3 === void 0 ? void 0 : _response$errors$3.customData.presetId,
	                    code: (_response$errors$4 = response.errors[0]) === null || _response$errors$4 === void 0 ? void 0 : _response$errors$4.customData.code
	                  }
	                }).then(function () {
	                  babelHelpers.classPrivateFieldGet(_this2, _blocks).map(function (block) {
	                    var _response$errors$5;
	                    if (block.isRemoved()) {
	                      return;
	                    }
	                    if (block.getCode() === ((_response$errors$5 = response.errors[0]) === null || _response$errors$5 === void 0 ? void 0 : _response$errors$5.customData.code)) {
	                      block.assign();
	                    }
	                  });
	                  popup.destroy();
	                })["catch"](function () {
	                  popup.destroy();
	                });
	              }
	            }), new ui_buttons.Button({
	              text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE_BY_HANDS'),
	              color: ui_buttons.ButtonColor.INFO,
	              onclick: function onclick() {
	                babelHelpers.classPrivateFieldGet(_this2, _blocks).map(function (block) {
	                  var _response$errors$6;
	                  if (block.isRemoved()) {
	                    return;
	                  }
	                  if (block.getCode() === ((_response$errors$6 = response.errors[0]) === null || _response$errors$6 === void 0 ? void 0 : _response$errors$6.customData.code)) {
	                    var blockActionButton = document.querySelector('button[data-id="action-' + block.getCode() + '"]');
	                    if (blockActionButton) {
	                      blockActionButton.click();
	                    }
	                  }
	                });
	                popup.destroy();
	              }
	            })]
	          });
	          popup.show();
	        }
	      });
	    }
	    /**
	     * Registers save button.
	     * @param {DocumentOptions} options
	     */
	  }, {
	    key: "onSave",
	    value: function onSave(options) {
	      var _this3 = this;
	      main_core.Event.bind(options.saveButton, 'click', function (e) {
	        if (babelHelpers.classPrivateFieldGet(_this3, _disableEdit)) {
	          if (options.afterSaveCallback) {
	            options.afterSaveCallback();
	          }
	          return;
	        }
	        _this3.save().then(function (result) {
	          //todo: we need to parse response and sets id for each new block
	          if (result === true) {
	            _this3.setSavingMark(true);
	            if (options.afterSaveCallback) {
	              options.afterSaveCallback();
	            }
	          }
	          if (result !== true) {
	            if (options.saveErrorCallback) {
	              options.saveErrorCallback();
	            }
	          }
	        });
	      });
	    }
	    /**
	     * Registers callback on slider close.
	     */
	  }, {
	    key: "onCloseSlider",
	    value: function onCloseSlider() {
	      var _this4 = this;
	      BX.addCustomEvent('SidePanel.Slider:onClose', function (event) {
	        if (event.slider.url.indexOf('/sign/edit/') === 0) {
	          if (!_this4.everythingIsSaved()) {
	            event.denyAction();
	            ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_MESSAGE'), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_TITLE'), function (messageBox) {
	              _this4.setSavingMark(true);
	              messageBox.close();
	              event.slider.close();
	            }, main_core.Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_APPLY'), function (messageBox) {
	              messageBox.close();
	            });
	          }
	        }
	      });
	    }
	    /**
	     * Sets mark to document that everything was saved or not.
	     * @param {boolean} mark
	     */
	  }, {
	    key: "setSavingMark",
	    value: function setSavingMark(mark) {
	      this.setParam('bxSignEditorAllSaved', mark);
	    }
	    /**
	     * Returns true, if everything was saved within editor.
	     * @return {boolean}
	     */
	  }, {
	    key: "everythingIsSaved",
	    value: function everythingIsSaved() {
	      return this.getParam('bxSignEditorAllSaved') === true;
	    }
	  }, {
	    key: "setParam",
	    value: function setParam(name, value) {
	      var slider = BX.SidePanel.Instance.getSliderByWindow(window);
	      if (slider) {
	        slider.getData().set(name, value);
	      }
	    }
	  }, {
	    key: "getParam",
	    value: function getParam(name) {
	      var slider = BX.SidePanel.Instance.getSliderByWindow(window);
	      if (slider) {
	        return slider.getData().get(name);
	      }
	      return undefined;
	    }
	    /**
	     * Returns true if element with specified top & bottom over dead zone.
	     * @param {number} top
	     * @param {number} bottom
	     * @param {boolean} notAddMargin
	     * @return {boolean}
	     */
	  }, {
	    key: "inDeadZone",
	    value: function inDeadZone(top, bottom, notAddMargin) {
	      var seams = this.getSeams();
	      if (seams.length > 0 && notAddMargin !== true) {
	        top += babelHelpers.classPrivateFieldGet(this, _documentLayoutRect).top;
	        bottom += babelHelpers.classPrivateFieldGet(this, _documentLayoutRect).top;
	      }
	      for (var ii = 0, cc = seams.length; ii < cc; ii++) {
	        var seam = seams[ii];

	        // top on page, bottom on seam
	        if (top <= seam[0] && bottom >= seam[0] && bottom <= seam[1]) {
	          return seam[0] - bottom;
	        }

	        // top on one page, bottom on another (seam in a middle)
	        if (top <= seam[0] && bottom >= seam[1]) {
	          //seam[0] - bottom >> shift top
	          //seam[1] - top >> shift bottom
	          return seam[0] - bottom;
	        }

	        // block into a seam
	        if (top >= seam[0] && bottom <= seam[1]) {
	          return seam[0] - bottom;
	        }

	        // top on seam, bottom on page
	        if (top >= seam[0] && top <= seam[1] && bottom >= seam[1]) {
	          return seam[1] - top;
	        }
	      }
	      return 0;
	    }
	  }]);
	  return Document;
	}();
	function _initPagesRect2() {
	  var _this6 = this;
	  var top = 0;
	  babelHelpers.toConsumableArray(babelHelpers.classPrivateFieldGet(this, _documentLayout).querySelectorAll('[data-page] img')).map(function (page) {
	    var pageRect = page.getBoundingClientRect();
	    var pageRectToMath = {};

	    // round getBoundingClientRect
	    for (var pageRectKey in pageRect) {
	      if (main_core.Type.isNumber(pageRect[pageRectKey])) {
	        pageRectToMath[pageRectKey] = Math.round(pageRect[pageRectKey]);
	      } else {
	        pageRectToMath[pageRectKey] = pageRect[pageRectKey];
	      }
	    }

	    // collect seam's gaps
	    babelHelpers.classPrivateFieldGet(_this6, _seams).push([top === 0 ? 0 : pageRectToMath.top - Document.gapBetweenPages, pageRectToMath.top + Document.marginTop]);

	    // correct top after rounding
	    if (top === 0) {
	      top = pageRectToMath.top;
	    } else {
	      top += Document.gapBetweenPages;
	      pageRectToMath.top = top;
	    }

	    // collect pages rects
	    babelHelpers.classPrivateFieldGet(_this6, _pagesRects).push(pageRectToMath);

	    // remember min page height
	    if (!babelHelpers.classPrivateFieldGet(_this6, _pagesMinHeight) || pageRect.height < babelHelpers.classPrivateFieldGet(_this6, _pagesMinHeight)) {
	      babelHelpers.classPrivateFieldSet(_this6, _pagesMinHeight, pageRect.height);
	    }
	  });
	}
	function _relativeToAbsolute2(top, page) {
	  for (var i = 0; i < page - 1; i++) {
	    top += babelHelpers.classPrivateFieldGet(this, _pagesRects)[i].height;
	  }
	  return top;
	}
	function _pixelToPercent2(position) {
	  if (!position.page || typeof babelHelpers.classPrivateFieldGet(this, _pagesRects)[position.page - 1] === 'undefined') {
	    return position;
	  }
	  var pageImageRect = babelHelpers.classPrivateFieldGet(this, _pagesRects)[position.page - 1];
	  position.widthPx = position.width;
	  position.heightPx = position.height;
	  position.left = position.left / pageImageRect.width * 100;
	  position.top = position.top / pageImageRect.height * 100;
	  position.width = position.width / pageImageRect.width * 100;
	  position.height = position.height / pageImageRect.height * 100;
	  return position;
	}
	function _percentToPixel2(position) {
	  if (!position.page || typeof babelHelpers.classPrivateFieldGet(this, _pagesRects)[position.page - 1] === 'undefined') {
	    return position;
	  }
	  var pageImageRect = babelHelpers.classPrivateFieldGet(this, _pagesRects)[position.page - 1];
	  if (position.left) {
	    position.left = position.left * pageImageRect.width / 100;
	  }
	  if (position.top) {
	    position.top = position.top * pageImageRect.height / 100;
	  }
	  if (position.width) {
	    position.width = position.width * pageImageRect.width / 100;
	  }
	  if (position.height) {
	    position.height = position.height * pageImageRect.height / 100;
	  }
	  return position;
	}
	function _addBlock2(data, seamShift) {
	  var _this7 = this;
	  if (data.position) {
	    data.position = _classPrivateMethodGet$d(this, _percentToPixel, _percentToPixel2).call(this, data.position);
	    if (!data.style['font-size']) {
	      data.style['font-size'] = '14px';
	    }
	    if (data !== null && data !== void 0 && data.position['page'] && data !== null && data !== void 0 && data.position['top']) {
	      data.position['top'] = _classPrivateMethodGet$d(this, _relativeToAbsolute, _relativeToAbsolute2).call(this, parseFloat(data.position['top']), parseFloat(data.position['page']));
	      if (seamShift) {
	        data.position.top += (data.position.page - 1) * Document.gapBetweenPages;
	      }
	    }
	  }
	  var block = new Block({
	    id: data.id,
	    code: data.code,
	    part: data.part,
	    document: this,
	    data: data.data,
	    position: data.position,
	    style: data.style,
	    onClick: function onClick(block) {
	      if (babelHelpers.classPrivateFieldGet(_this7, _disableEdit)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldGet(_this7, _resizeArea).show(block);
	      _this7.unMuteResizeArea();
	      block.adjustActionsPanel();
	    },
	    onRemove: function onRemove(block) {
	      babelHelpers.classPrivateFieldGet(_this7, _resizeArea).hide();
	    }
	  });
	  var blockLayout = block.getLayout();
	  babelHelpers.classPrivateFieldGet(this, _blocks).push(block);
	  main_core.Dom.append(blockLayout, babelHelpers.classPrivateFieldGet(this, _documentLayout));
	  return block;
	}
	function _onRepositoryItemClick2(code, part) {
	  part = parseInt(part);
	  this.setSavingMark(false);
	  var block = _classPrivateMethodGet$d(this, _addBlock, _addBlock2).call(this, {
	    code: code,
	    part: part
	  });
	  block.assign();
	  block.onPlaced();
	}
	function _initBlocks2(blocks) {
	  var _this8 = this;
	  blocks.map(function (block) {
	    return _classPrivateMethodGet$d(_this8, _addBlock, _addBlock2).call(_this8, block, true);
	  });
	}
	function _initRepository2(repositoryItems) {
	  var _this9 = this;
	  babelHelpers.toConsumableArray(repositoryItems).map(function (item) {
	    var code = item.getAttribute('data-code');
	    var part = item.getAttribute('data-part');
	    main_core.Event.bind(item, 'click', function (e) {
	      _classPrivateMethodGet$d(_this9, _onRepositoryItemClick, _onRepositoryItemClick2).call(_this9, code, part);
	      e.preventDefault();
	    });
	  });
	}
	function _startDisabledEditDocumentTour2() {
	  var guide = new sign_tour.Guide({
	    steps: [{
	      target: document.getElementById('sign-editor-btn-edit'),
	      title: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_EDIT_TITLE'),
	      text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_EDIT_TEXT')
	    }],
	    id: 'sign-tour-guide-onboarding-master-document-edit-disabled',
	    autoSave: true,
	    simpleMode: true
	  });
	  guide.startOnce();
	}
	function _startEditDocumentTour2() {
	  var helpBtnSpotlight = new BX.SpotLight({
	    targetElement: document.getElementById(babelHelpers.classPrivateFieldGet(this, _helpBtnElementId)),
	    targetVertex: 'middle-center',
	    autoSave: true,
	    id: 'sign-spotlight-onboarding-master-document-edit'
	  });
	  var guide = new sign_tour.Guide({
	    steps: [{
	      target: document.getElementById('sign-editor-bar-content'),
	      title: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_BLOCKS_TITLE'),
	      text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_BLOCKS_TEXT'),
	      position: 'left'
	    }, {
	      target: document.getElementById(babelHelpers.classPrivateFieldGet(this, _helpBtnElementId)),
	      title: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_HELP_TITLE'),
	      text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_HELP_TEXT'),
	      rounded: true,
	      article: babelHelpers.classPrivateFieldGet(this, _helpBtnHelperArticleCode),
	      events: {
	        onShow: function onShow() {
	          return helpBtnSpotlight.show();
	        },
	        onClose: function onClose() {
	          return helpBtnSpotlight.close();
	        }
	      }
	    }],
	    id: 'sign-tour-guide-onboarding-master-document-edit',
	    autoSave: true,
	    simpleMode: true
	  });
	  guide.startOnce();
	}
	babelHelpers.defineProperty(Document, "alreadyInit", false);
	babelHelpers.defineProperty(Document, "marginTop", 0);
	babelHelpers.defineProperty(Document, "gapBetweenPages", 20);
	var _totalImages = /*#__PURE__*/new WeakMap();
	var _progressRound = /*#__PURE__*/new WeakMap();
	var _layout$2 = /*#__PURE__*/new WeakMap();
	var _loader = /*#__PURE__*/new WeakMap();
	var _getProgress = /*#__PURE__*/new WeakSet();
	var _getLoader = /*#__PURE__*/new WeakSet();
	var _getOverlay = /*#__PURE__*/new WeakSet();
	var _getLoaderWrapper = /*#__PURE__*/new WeakSet();
	var _getTitle = /*#__PURE__*/new WeakSet();
	var DocumentMasterLoader = /*#__PURE__*/function () {
	  function DocumentMasterLoader(_ref) {
	    var totalImages = _ref.totalImages;
	    babelHelpers.classCallCheck(this, DocumentMasterLoader);
	    _classPrivateMethodInitSpec$d(this, _getTitle);
	    _classPrivateMethodInitSpec$d(this, _getLoaderWrapper);
	    _classPrivateMethodInitSpec$d(this, _getOverlay);
	    _classPrivateMethodInitSpec$d(this, _getLoader);
	    _classPrivateMethodInitSpec$d(this, _getProgress);
	    _classPrivateFieldInitSpec$a(this, _totalImages, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _progressRound, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _layout$2, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _totalImages, totalImages || null);
	    babelHelpers.classPrivateFieldSet(this, _progressRound, null);
	    babelHelpers.classPrivateFieldSet(this, _layout$2, {
	      overlay: null,
	      title: null,
	      error: null,
	      loader: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _loader, null);
	  }
	  babelHelpers.createClass(DocumentMasterLoader, [{
	    key: "updateLoadPage",
	    value: function updateLoadPage(param) {
	      if (main_core.Type.isNumber(param)) {
	        _classPrivateMethodGet$d(this, _getProgress, _getProgress2).call(this).update(param);
	      }
	    }
	  }, {
	    key: "showError",
	    value: function showError(message) {
	      main_core.Dom.remove(_classPrivateMethodGet$d(this, _getLoaderWrapper, _getLoaderWrapper2).call(this));
	      _classPrivateMethodGet$d(this, _getLoader, _getLoader2).call(this).destroy();
	      _classPrivateMethodGet$d(this, _getTitle, _getTitle2).call(this).innerHTML = message || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_LOAD_ERROR');
	      var linkReload = _classPrivateMethodGet$d(this, _getTitle, _getTitle2).call(this).getElementsByTagName('span')[0];
	      if (linkReload) {
	        linkReload.addEventListener('click', function () {
	          document.location.reload();
	        });
	      }
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      if (!babelHelpers.classPrivateFieldGet(this, _totalImages)) {
	        console.warn('BX.Sign.DocumentMasterLoader: totalImages is not defined');
	        return;
	      }
	      main_core.Dom.append(_classPrivateMethodGet$d(this, _getOverlay, _getOverlay2).call(this), document.body);
	      if (babelHelpers.classPrivateFieldGet(this, _totalImages) > 1) {
	        _classPrivateMethodGet$d(this, _getProgress, _getProgress2).call(this).renderTo(_classPrivateMethodGet$d(this, _getLoaderWrapper, _getLoaderWrapper2).call(this));
	      } else {
	        _classPrivateMethodGet$d(this, _getLoader, _getLoader2).call(this).show(_classPrivateMethodGet$d(this, _getLoaderWrapper, _getLoaderWrapper2).call(this));
	      }
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      var _this5 = this;
	      _classPrivateMethodGet$d(this, _getOverlay, _getOverlay2).call(this).addEventListener('animationend', function () {
	        main_core.Dom.remove(_classPrivateMethodGet$d(_this5, _getOverlay, _getOverlay2).call(_this5));
	        _classPrivateMethodGet$d(_this5, _getLoader, _getLoader2).call(_this5).destroy();
	        babelHelpers.classPrivateFieldSet(_this5, _totalImages, null);
	        babelHelpers.classPrivateFieldSet(_this5, _progressRound, null);
	        babelHelpers.classPrivateFieldSet(_this5, _layout$2, {
	          overlay: null,
	          title: null,
	          error: null,
	          loader: null
	        });
	        babelHelpers.classPrivateFieldSet(_this5, _loader, null);
	      });
	      main_core.Dom.addClass(_classPrivateMethodGet$d(this, _getOverlay, _getOverlay2).call(this), '--hide');
	    }
	  }]);
	  return DocumentMasterLoader;
	}();
	function _getProgress2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _progressRound)) {
	    babelHelpers.classPrivateFieldSet(this, _progressRound, new ui_progressround.ProgressRound({
	      colorBar: '#1ec6fa',
	      colorTrack: '#fff',
	      statusType: ui_progressround.ProgressRoundStatus ? ui_progressround.ProgressRoundStatus.INCIRCLECOUNTER : BX.UI.ProgressRound.Status.INCIRCLECOUNTER,
	      maxValue: babelHelpers.classPrivateFieldGet(this, _totalImages)
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _progressRound);
	}
	function _getLoader2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _loader)) {
	    babelHelpers.classPrivateFieldSet(this, _loader, new main_loader.Loader({
	      size: 100,
	      color: '#1ec6fa'
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _loader);
	}
	function _getOverlay2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$2).overlay) {
	    babelHelpers.classPrivateFieldGet(this, _layout$2).overlay = main_core.Tag.render(_templateObject$d || (_templateObject$d = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document__master-loader-overlay\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _classPrivateMethodGet$d(this, _getTitle, _getTitle2).call(this), _classPrivateMethodGet$d(this, _getLoaderWrapper, _getLoaderWrapper2).call(this));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$2).overlay;
	}
	function _getLoaderWrapper2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$2).loader) {
	    babelHelpers.classPrivateFieldGet(this, _layout$2).loader = main_core.Tag.render(_templateObject2$9 || (_templateObject2$9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document__master-loader-container\"></div>\n\t\t\t"])));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$2).loader;
	}
	function _getTitle2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$2).title) {
	    babelHelpers.classPrivateFieldGet(this, _layout$2).title = main_core.Tag.render(_templateObject3$5 || (_templateObject3$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document__master-loader-title\">", "</div>\n\t\t\t"])), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_LOADING_DOCUMENT_PAGES'));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$2).title;
	}

	exports.Document = Document;
	exports.DocumentMasterLoader = DocumentMasterLoader;

}((this.BX.Sign = this.BX.Sign || {}),BX.UI.Dialogs,BX.Main,BX.UI,BX,BX,BX.Sign,BX,BX.UI.Stamp,BX.Sign,BX.Crm.Form.Fields,BX.Crm.Requisite,BX.Sign,BX,BX.UI.DragAndDrop,BX.Event,BX.UI,BX.Sign.Tour));
//# sourceMappingURL=index.bundle.js.map
