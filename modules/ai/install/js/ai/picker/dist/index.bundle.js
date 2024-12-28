/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ai_payload_textpayload,ui_iconSet_icon_actions,ui_notification,clipboard,ui_buttons,main_core_events,main_popup,ui_iconSet_main,ui_iconSet_actions,ai_engine,ai_ajaxErrorHandler,ai_agreement,ui_iconSet_api_core,main_core) {
	'use strict';

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _messages = /*#__PURE__*/new WeakMap();
	var Loc = /*#__PURE__*/function () {
	  function Loc() {
	    babelHelpers.classCallCheck(this, Loc);
	    _classPrivateFieldInitSpec(this, _messages, {
	      writable: true,
	      value: {
	        header: null,
	        submit: null,
	        action_use: null,
	        action_copy: null,
	        action_copy_notify: null,
	        max_capacity: null,
	        placeholder: null
	      }
	    });
	  }
	  babelHelpers.createClass(Loc, [{
	    key: "setSpace",
	    /**
	     * Sets language space. For different interface may be used different phrases.
	     * See all bunches of phrases in lang/config.php.
	     *
	     * @param {string} spaceCode
	     */
	    value: function setSpace(spaceCode) {
	      var _this = this;
	      Object.keys(babelHelpers.classPrivateFieldGet(this, _messages)).forEach(function (key) {
	        babelHelpers.classPrivateFieldGet(_this, _messages)[key] = main_core.Loc.getMessage("AI_JS_PICKER_".concat(spaceCode.toUpperCase(), "_").concat(key.toUpperCase()));
	      });
	      Loc.generalKeys.forEach(function (key) {
	        babelHelpers.classPrivateFieldGet(_this, _messages)[key] = main_core.Loc.getMessage("AI_JS_PICKER_GENERAL_".concat(key.toUpperCase()));
	      });
	    }
	    /**
	     * Returns phrase by certain message code.
	     *
	     * @param {messageCode} messageCode
	     * @return {string}
	     */
	  }, {
	    key: "getMessage",
	    value: function getMessage(messageCode) {
	      return babelHelpers.classPrivateFieldGet(this, _messages)[messageCode];
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!Loc.instance) {
	        Loc.instance = new Loc();
	      }
	      return Loc.instance;
	    }
	  }]);
	  return Loc;
	}();
	babelHelpers.defineProperty(Loc, "generalKeys", ['help_link', 'agree_with_terms']);

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _loc = /*#__PURE__*/new WeakMap();
	var Base = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Base, _EventEmitter);
	  function Base(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, Base);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Base).call(this, props));
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _loc, {
	      writable: true,
	      value: void 0
	    });
	    _this.props = props;
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _loc, Loc.getInstance());
	    _this.setEventNamespace('AI:Picker:UI');
	    return _this;
	  }
	  babelHelpers.createClass(Base, [{
	    key: "getMessage",
	    value: function getMessage(code) {
	      return babelHelpers.classPrivateFieldGet(this, _loc).getMessage(code);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      return null;
	    }
	  }]);
	  return Base;
	}(main_core_events.EventEmitter);

	var _templateObject;
	var IconClose = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(IconClose, _Base);
	  function IconClose() {
	    babelHelpers.classCallCheck(this, IconClose);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(IconClose).apply(this, arguments));
	  }
	  babelHelpers.createClass(IconClose, [{
	    key: "render",
	    value: function render() {
	      var icon = new ui_iconSet_api_core.Icon({
	        icon: ui_iconSet_api_core.Actions.CROSS_40,
	        size: 24
	      });
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_icon-close\" onclick=\"", "\">", "</div>\n\t\t"])), this.props.onClick, icon.render());
	    }
	  }]);
	  return IconClose;
	}(Base);

	var _templateObject$1, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _articleCode = /*#__PURE__*/new WeakMap();
	var _className = /*#__PURE__*/new WeakMap();
	var _renderHelpLink = /*#__PURE__*/new WeakSet();
	var Header = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Header, _Base);
	  function Header(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, Header);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Header).call(this, props));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderHelpLink);
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _articleCode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _className, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _articleCode, main_core.Type.isNumber(props.articleCode) ? props.articleCode : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _className, main_core.Type.isString(props.className) ? props.className : '');
	    _this.setEventNamespace('AI:Picker:Header');
	    return _this;
	  }
	  babelHelpers.createClass(Header, [{
	    key: "render",
	    value: function render() {
	      var _this2 = this;
	      var closeIcon = new IconClose({
	        onClick: function onClick() {
	          _this2.emit('click-close-icon');
	        }
	      });
	      return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_header ", "\">\n\t\t\t\t<div class=\"ai__picker_header-icon\"></div>\n\t\t\t\t<div style=\"margin-top: -10px;\">\n\t\t\t\t\t<h3 class=\"ui-typography-heading-h3 ui- ai__picker_header-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</h3>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _className), this.getMessage('header'), _classPrivateMethodGet(this, _renderHelpLink, _renderHelpLink2).call(this), closeIcon.render());
	    }
	  }]);
	  return Header;
	}(Base);
	function _renderHelpLink2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _articleCode) || !top.BX || !top.BX.Helper) {
	    return null;
	  }
	  var helpLink = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a href=\"\" class=\"ai__picker_header-subtitle\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), this.getMessage('help_link'));
	  var articleCode = babelHelpers.classPrivateFieldGet(this, _articleCode);
	  main_core.bind(helpLink, 'click', function (e) {
	    if (top.BX && top.BX.Helper) {
	      top.BX.Helper.show("redirect=detail&code=".concat(articleCode));
	    }
	    e.preventDefault();
	    return false;
	  });
	  return helpLink;
	}

	var _templateObject$2, _templateObject2$1, _templateObject3;
	var HistoryBase = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(HistoryBase, _Base);
	  function HistoryBase(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, HistoryBase);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(HistoryBase).call(this, props));
	    _this.setEventNamespace('AI:Picker:History');
	    _this.onGenerate = props.onGenerate;
	    _this.onLoadHistory = props.onLoadHistory;
	    _this.onSelect = props.onSelect;
	    _this.notifiers = new Map();
	    _this.listWrapper = _this.getListWrapper();
	    _this.items = props.items || [];
	    _this.capacity = props.capacity || 30;
	    _this.isHistoryLoaded = true;
	    return _this;
	  }

	  /**
	   * Called when user want to use HistoryItem somewhere outside.
	   *
	   * @param {HistoryItem} item
	   */
	  babelHelpers.createClass(HistoryBase, [{
	    key: "onSelectClick",
	    value: function onSelectClick(item) {
	      this.onSelect(item);
	    }
	    /**
	     * Shows notification near the Node.
	     *
	     * @param {HTMLElement} node Near this node notification will appear.
	     * @param {string} code Unique id.
	     * @param message Notification message.
	     */
	  }, {
	    key: "showNotify",
	    value: function showNotify(node, code, message) {
	      var _this2 = this;
	      if (!this.notifiers.has(code)) {
	        var popup = new main_popup.Popup(code, node, {
	          content: message,
	          darkMode: true,
	          autoHide: true,
	          angle: true,
	          offsetLeft: 20,
	          bindOptions: {
	            position: 'top'
	          }
	        });
	        main_core.bind(node, 'mouseout', function () {
	          setTimeout(function () {
	            _this2.notifiers.get(code).close();
	          }, 300);
	        });
	        this.notifiers.set(code, popup);
	      }
	      this.notifiers.get(code).show();
	    }
	    /**
	     * Builds History container after loading History items.
	     *
	     */
	  }, {
	    key: "buildHistory",
	    value: function buildHistory() {
	      // you must implement this method
	    }
	  }, {
	    key: "addNewItem",
	    value: function addNewItem(item) {
	      // you must implement this method
	    }
	    /**
	     * Returns label with note about History limitation.
	     *
	     * @param {number} capacity
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getCapacityLabel",
	    value: function getCapacityLabel(capacity) {
	      var iconColor = getComputedStyle(document.body).getPropertyValue('--ui-color-base-35');
	      var arrowIcon = new ui_iconSet_api_core.Icon({
	        icon: ui_iconSet_api_core.Actions.ARROW_TOP,
	        size: 14,
	        color: iconColor
	      });
	      return main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker-text_capacity-label\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"ai__picker-text_capacity-label-text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), arrowIcon.render(), this.getMessage('max_capacity').replace('#capacity#', capacity));
	    }
	    /**
	     * Returns the loader or text when loading History.
	     *
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getListWrapper",
	    value: function getListWrapper() {
	      return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_list-wrapper\">\n\t\t\t\t<div class=\"ai__picker_list-wrapper-loader-text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('AI_JS_PICKER_HISTORY_LOADING'));
	    }
	    /**
	     * Returns wrapper for History.
	     *
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "getWrapper",
	    value: function getWrapper() {
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_history\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.listWrapper);
	    }
	    /**
	     * Returns to parent.
	     *
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "render",
	    value: function render() {
	      return this.getWrapper();
	    }
	  }, {
	    key: "loadHistory",
	    value: function loadHistory() {
	      var _this3 = this;
	      if (!this.onLoadHistory) {
	        return null;
	      }
	      return new Promise(function (resolve, reject) {
	        _this3.onLoadHistory().then(function (res) {
	          _this3.isHistoryLoaded = true;
	          _this3.items = res.data.items;
	          _this3.capacity = res.data.capacity;
	          resolve(res);
	        })["catch"](function (error) {
	          reject(error);
	        });
	      });
	    }
	  }, {
	    key: "generate",
	    value: function generate(message, engineCode) {
	      // you must implement this method
	    }
	  }]);
	  return HistoryBase;
	}(Base);

	var _templateObject$3, _templateObject2$2;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _text = /*#__PURE__*/new WeakMap();
	var _textElem = /*#__PURE__*/new WeakMap();
	var _container = /*#__PURE__*/new WeakMap();
	var _isAnimate = /*#__PURE__*/new WeakMap();
	var _renderTextContainer = /*#__PURE__*/new WeakSet();
	var ImageLoader = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(ImageLoader, _Base);
	  function ImageLoader() {
	    var _this;
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, ImageLoader);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ImageLoader).call(this, props));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _renderTextContainer);
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _text, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _textElem, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _container, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _isAnimate, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _text, main_core.Type.isString(props === null || props === void 0 ? void 0 : props.text) ? props.text : '');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _textElem, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _container, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _isAnimate, false);
	    return _this;
	  }
	  babelHelpers.createClass(ImageLoader, [{
	    key: "render",
	    value: function render() {
	      babelHelpers.classPrivateFieldSet(this, _textElem, _classPrivateMethodGet$1(this, _renderTextContainer, _renderTextContainer2).call(this));
	      babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_image-loader-container ", "\">\n\t\t\t\t<div class=\"ai__picker_image-loader\">\n\t\t\t\t\t<div class=\"ai__picker_image-loader-star ai__picker_image-loader-right-star --pulse\"></div>\n\t\t\t\t\t<div class=\"ai__picker_image-loader-left-star-container\">\n\t\t\t\t\t\t<div class=\"ai__picker_image-loader-left-star ai__picker_image-loader-star --pulse\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ai__picker_image-loader-square\">\n\t\t\t\t\t\t<div class=\"ai__picker_image-loader-square-image\"></div>\n\t\t\t\t\t\t<div class=\"ai__picker_image-loader-square-star ai__picker_image-loader-star --pulse\"></div>\n\t\t\t\t\t\t<div class=\"ai__picker_image-loader-square-loader-line\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ai__picker_image-loader-text-container\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _isAnimate) ? '--animating' : '', babelHelpers.classPrivateFieldGet(this, _textElem)));
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "setText",
	    value: function setText(text) {
	      babelHelpers.classPrivateFieldSet(this, _text, text);
	      if (babelHelpers.classPrivateFieldGet(this, _textElem)) {
	        babelHelpers.classPrivateFieldGet(this, _textElem).innerText = text;
	      }
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      babelHelpers.classPrivateFieldSet(this, _isAnimate, true);
	      if (babelHelpers.classPrivateFieldGet(this, _container)) {
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _container), '--animating');
	      }
	    }
	  }, {
	    key: "stop",
	    value: function stop() {
	      babelHelpers.classPrivateFieldSet(this, _isAnimate, false);
	      if (babelHelpers.classPrivateFieldGet(this, _container)) {
	        main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _container), '--animating');
	      }
	    }
	  }]);
	  return ImageLoader;
	}(Base);
	function _renderTextContainer2() {
	  return main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"ai__picker_image-loader-text\">", "</div>"])), babelHelpers.classPrivateFieldGet(this, _text));
	}

	var _templateObject$4, _templateObject2$3, _templateObject3$1, _templateObject4;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var HistoryImageGroupItemState = Object.freeze({
	  EMPTY: 'empty',
	  ERROR: 'error',
	  GENERATING: 'generating',
	  IN_LINE_FOR_GENERATING: 'in_line_for_generating',
	  IMAGE_LOADING: 'image_loading',
	  IMAGE_LOADING_SUCCESS: 'image_loading_success',
	  IMAGE_LOADING_ERROR: 'image_loading_error'
	});
	var _image = /*#__PURE__*/new WeakMap();
	var _state = /*#__PURE__*/new WeakMap();
	var _loader = /*#__PURE__*/new WeakMap();
	var _imageElement = /*#__PURE__*/new WeakMap();
	var _itemElement = /*#__PURE__*/new WeakMap();
	var _onSelect = /*#__PURE__*/new WeakMap();
	var _renderActionButton = /*#__PURE__*/new WeakSet();
	var _prepareImageToSelect = /*#__PURE__*/new WeakSet();
	var _renderLoader = /*#__PURE__*/new WeakSet();
	var _renderImageElement = /*#__PURE__*/new WeakSet();
	var HistoryImageGroupItem = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(HistoryImageGroupItem, _Base);
	  function HistoryImageGroupItem(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, HistoryImageGroupItem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(HistoryImageGroupItem).call(this, props));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _renderImageElement);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _renderLoader);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _prepareImageToSelect);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _renderActionButton);
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _image, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _state, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _loader, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _imageElement, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _itemElement, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _onSelect, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _onSelect, main_core.Type.isFunction(props.onSelect) ? props.onSelect : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _state, props.state);
	    _this.setImage(props.image);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _loader, new ImageLoader());
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _itemElement, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _imageElement, null);
	    return _this;
	  }
	  babelHelpers.createClass(HistoryImageGroupItem, [{
	    key: "render",
	    value: function render() {
	      babelHelpers.classPrivateFieldSet(this, _imageElement, _classPrivateMethodGet$2(this, _renderImageElement, _renderImageElement2).call(this));
	      babelHelpers.classPrivateFieldSet(this, _itemElement, main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_history-image-group-item --empty\">\n\t\t\t\t<div class=\"ai__picker_history-image-group-item-controls\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$2(this, _renderActionButton, _renderActionButton2).call(this), _classPrivateMethodGet$2(this, _renderLoader, _renderLoader2).call(this), babelHelpers.classPrivateFieldGet(this, _imageElement)));
	      return babelHelpers.classPrivateFieldGet(this, _itemElement);
	    }
	  }, {
	    key: "setImage",
	    value: function setImage(image) {
	      babelHelpers.classPrivateFieldSet(this, _image, image);
	      if (babelHelpers.classPrivateFieldGet(this, _imageElement)) {
	        babelHelpers.classPrivateFieldSet(this, _state, babelHelpers.classPrivateFieldGet(this, _image) ? HistoryImageGroupItemState.IMAGE_LOADING : HistoryImageGroupItemState.IMAGE_LOADING_ERROR);
	        if (babelHelpers.classPrivateFieldGet(this, _state) === HistoryImageGroupItemState.IMAGE_LOADING_ERROR) {
	          main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _itemElement), '--empty');
	          main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _itemElement), '--error');
	        }
	        babelHelpers.classPrivateFieldGet(this, _imageElement).setAttribute('src', babelHelpers.classPrivateFieldGet(this, _image));
	      }
	    }
	  }, {
	    key: "getImage",
	    value: function getImage() {
	      return babelHelpers.classPrivateFieldGet(this, _image);
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return babelHelpers.classPrivateFieldGet(this, _state);
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return babelHelpers.classPrivateFieldGet(this, _state) === HistoryImageGroupItemState.EMPTY;
	    }
	  }, {
	    key: "isInQueue",
	    value: function isInQueue() {
	      return babelHelpers.classPrivateFieldGet(this, _state) === HistoryImageGroupItemState.IN_LINE_FOR_GENERATING;
	    }
	  }, {
	    key: "isGenerating",
	    value: function isGenerating() {
	      return babelHelpers.classPrivateFieldGet(this, _state) === HistoryImageGroupItemState.GENERATING;
	    }
	  }, {
	    key: "setGeneratingState",
	    value: function setGeneratingState() {
	      babelHelpers.classPrivateFieldSet(this, _state, HistoryImageGroupItemState.GENERATING);
	      babelHelpers.classPrivateFieldGet(this, _loader).start();
	    }
	  }]);
	  return HistoryImageGroupItem;
	}(Base);
	function _renderActionButton2() {
	  var _this2 = this;
	  var actionUseBtnClassname = 'ai__picker_text-history-item-action-btn --paste --accent';
	  var useBtn = main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button\n\t\t\t\tclass=\"", "\"\n\t\t\t>\n\t\t\t\t<span class=\"ai__picker_text-history-item-action-icon\"></span>\n\t\t\t\t", "\n\t\t\t</button>\n\t\t"])), actionUseBtnClassname, this.getMessage('action_use'));
	  main_core.bind(useBtn, 'click', /*#__PURE__*/babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	    return _regeneratorRuntime().wrap(function _callee$(_context) {
	      while (1) switch (_context.prev = _context.next) {
	        case 0:
	          try {
	            babelHelpers.classPrivateFieldGet(_this2, _onSelect).call(_this2, _classPrivateMethodGet$2(_this2, _prepareImageToSelect, _prepareImageToSelect2).call(_this2, babelHelpers.classPrivateFieldGet(_this2, _image)));
	          } catch (err) {
	            console.error(err);
	          }
	        case 1:
	        case "end":
	          return _context.stop();
	      }
	    }, _callee);
	  })));
	  return useBtn;
	}
	function _prepareImageToSelect2(image) {
	  var url = new URL(image);
	  url.searchParams.set('t', Date.now());
	  return url.href;
	}
	function _renderLoader2() {
	  if (babelHelpers.classPrivateFieldGet(this, _state) === HistoryImageGroupItemState.GENERATING) {
	    babelHelpers.classPrivateFieldGet(this, _loader).start();
	  }
	  return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_history-image-group-item-loader\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _loader).render());
	}
	function _renderImageElement2() {
	  var _this3 = this;
	  var imageElement = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<img\n\t\t\t\tloading=\"lazy\" \n\t\t\t\tclass=\"ai__picker_history-image-group-item-image\"\n\t\t\t/>\n\t\t"])));
	  if (babelHelpers.classPrivateFieldGet(this, _state) !== HistoryImageGroupItemState.GENERATING) {
	    imageElement.setAttribute('src', babelHelpers.classPrivateFieldGet(this, _image));
	  }
	  imageElement.onload = function () {
	    babelHelpers.classPrivateFieldSet(_this3, _state, HistoryImageGroupItemState.IMAGE_LOADING_SUCCESS);
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this3, _itemElement), '--empty');
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this3, _itemElement), '--error');
	    babelHelpers.classPrivateFieldGet(_this3, _loader).getLayout().remove();
	  };
	  imageElement.onerror = function () {
	    if (babelHelpers.classPrivateFieldGet(_this3, _state) === HistoryImageGroupItemState.GENERATING || babelHelpers.classPrivateFieldGet(_this3, _state) === HistoryImageGroupItemState.EMPTY) {
	      babelHelpers.classPrivateFieldSet(_this3, _state, HistoryImageGroupItemState.IMAGE_LOADING_ERROR);
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(_this3, _itemElement), '--empty');
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(_this3, _itemElement), '--error');
	      babelHelpers.classPrivateFieldGet(_this3, _loader).getLayout().remove();
	    }
	  };
	  return imageElement;
	}

	var _templateObject$5, _templateObject2$4;
	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$5(obj, privateMap, value) { _checkPrivateRedeclaration$5(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _title = /*#__PURE__*/new WeakMap();
	var _size = /*#__PURE__*/new WeakMap();
	var _item = /*#__PURE__*/new WeakMap();
	var _itemsContainer = /*#__PURE__*/new WeakMap();
	var _items = /*#__PURE__*/new WeakMap();
	var _layout = /*#__PURE__*/new WeakMap();
	var _isNew = /*#__PURE__*/new WeakMap();
	var _onSelect$1 = /*#__PURE__*/new WeakMap();
	var _renderItems = /*#__PURE__*/new WeakSet();
	var _getGroupImages = /*#__PURE__*/new WeakSet();
	var HistoryImageGroup = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(HistoryImageGroup, _Base);
	  function HistoryImageGroup() {
	    var _props$item;
	    var _this;
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, HistoryImageGroup);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(HistoryImageGroup).call(this, props));
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getGroupImages);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _renderItems);
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _title, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _size, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _item, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _itemsContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _items, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _layout, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _isNew, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$5(babelHelpers.assertThisInitialized(_this), _onSelect$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _title, main_core.Type.isString(props === null || props === void 0 ? void 0 : (_props$item = props.item) === null || _props$item === void 0 ? void 0 : _props$item.payload) ? props.item.payload : '');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _size, main_core.Type.isInteger(props.size) ? props.size : 4);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _item, props.item || []);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _isNew, main_core.Type.isBoolean(props.isNew) ? props.isNew : false);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _onSelect$1, main_core.Type.isFunction(props.onSelect) ? props.onSelect : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _items, []);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _layout, null);
	    return _this;
	  }
	  babelHelpers.createClass(HistoryImageGroup, [{
	    key: "render",
	    value: function render() {
	      babelHelpers.classPrivateFieldSet(this, _layout, main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_history-image-group\">\n\t\t\t\t<div class=\"ai__picker_history-image-group-title\">", "</div>\n\t\t\t\t<div class=\"ai__picker_history-image-group-items\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), BX.util.htmlspecialchars(babelHelpers.classPrivateFieldGet(this, _title)), _classPrivateMethodGet$3(this, _renderItems, _renderItems2).call(this)));
	      return babelHelpers.classPrivateFieldGet(this, _layout);
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      return babelHelpers.classPrivateFieldGet(this, _layout);
	    }
	  }, {
	    key: "remove",
	    value: function remove() {
	      if (this.getLayout()) {
	        this.getLayout().remove();
	      }
	    }
	  }, {
	    key: "addImage",
	    value: function addImage(image) {
	      var loadingItem = babelHelpers.classPrivateFieldGet(this, _items).find(function (item) {
	        return item.isGenerating();
	      });
	      if (!loadingItem) {
	        return;
	      }
	      loadingItem.setImage(image);
	      var emptyItem = babelHelpers.classPrivateFieldGet(this, _items).find(function (item) {
	        return item.isInQueue();
	      });
	      if (!emptyItem) {
	        return;
	      }
	      emptyItem.setGeneratingState();
	    }
	  }, {
	    key: "geGeneratedImagesCount",
	    value: function geGeneratedImagesCount() {
	      return babelHelpers.classPrivateFieldGet(this, _items).filter(function (item) {
	        return item.getState() === HistoryImageGroupItemState.IMAGE_LOADING || item.getState() === HistoryImageGroupItemState.IMAGE_LOADING_ERROR || item.getState() === HistoryImageGroupItemState.IMAGE_LOADING_SUCCESS;
	      }).length;
	    }
	  }]);
	  return HistoryImageGroup;
	}(Base);
	function _renderItems2() {
	  var _this2 = this;
	  babelHelpers.classPrivateFieldSet(this, _itemsContainer, main_core.Tag.render(_templateObject2$4 || (_templateObject2$4 = babelHelpers.taggedTemplateLiteral(["<div class=\"ai__picker_history-image-group-items\"></div>"]))));
	  _classPrivateMethodGet$3(this, _getGroupImages, _getGroupImages2).call(this).forEach(function (image) {
	    var state = babelHelpers.classPrivateFieldGet(_this2, _isNew) ? HistoryImageGroupItemState.GENERATING : HistoryImageGroupItemState.EMPTY;
	    var newItem = new HistoryImageGroupItem({
	      image: image,
	      state: state,
	      onSelect: babelHelpers.classPrivateFieldGet(_this2, _onSelect$1)
	    });
	    newItem.subscribe('select', function (event) {
	      _this2.emit('select', {
	        item: event.data.item
	      });
	    });
	    babelHelpers.classPrivateFieldGet(_this2, _items).push(newItem);
	    main_core.Dom.append(newItem.render(), babelHelpers.classPrivateFieldGet(_this2, _itemsContainer));
	  });
	  return babelHelpers.classPrivateFieldGet(this, _itemsContainer);
	}
	function _getGroupImages2() {
	  var result = [];
	  var images = babelHelpers.classPrivateFieldGet(this, _item).groupData || JSON.parse(babelHelpers.classPrivateFieldGet(this, _item).data);
	  for (var imageIndex = 0; imageIndex < babelHelpers.classPrivateFieldGet(this, _size); imageIndex++) {
	    var image = images[imageIndex] || '';
	    result.push(image);
	  }
	  return result;
	}

	var _templateObject$6;
	var ImageHistoryEmptyState = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(ImageHistoryEmptyState, _Base);
	  function ImageHistoryEmptyState() {
	    babelHelpers.classCallCheck(this, ImageHistoryEmptyState);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ImageHistoryEmptyState).apply(this, arguments));
	  }
	  babelHelpers.createClass(ImageHistoryEmptyState, [{
	    key: "render",
	    value: function render() {
	      var text = main_core.Loc.getMessage('AI_JS_PICKER_IMAGE_EMPTY_STATE');
	      return main_core.Tag.render(_templateObject$6 || (_templateObject$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_image-history-empty-state\">\n\t\t\t\t<div class=\"ai__picker_image-history-empty-state-icon\"></div>\n\t\t\t\t<div class=\"ai__picker_image-history-empty-state-text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), text);
	    }
	  }]);
	  return ImageHistoryEmptyState;
	}(Base);

	var _templateObject$7;
	function _classPrivateMethodInitSpec$4(obj, privateSet) { _checkPrivateRedeclaration$6(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$6(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _addHistoryItem = /*#__PURE__*/new WeakSet();
	var _addNewHistoryItem = /*#__PURE__*/new WeakSet();
	var _renderImageGroup = /*#__PURE__*/new WeakSet();
	var _createImageGroup = /*#__PURE__*/new WeakSet();
	var _createItemWithPrompt = /*#__PURE__*/new WeakSet();
	var _removeHistoryImageGroup = /*#__PURE__*/new WeakSet();
	var HistoryImage = /*#__PURE__*/function (_HistoryBase) {
	  babelHelpers.inherits(HistoryImage, _HistoryBase);
	  function HistoryImage() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, HistoryImage);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(HistoryImage)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _removeHistoryImageGroup);
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _createItemWithPrompt);
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _createImageGroup);
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _renderImageGroup);
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _addNewHistoryItem);
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _addHistoryItem);
	    return _this;
	  }
	  babelHelpers.createClass(HistoryImage, [{
	    key: "render",
	    value: function render() {
	      this.buildHistory();
	      return this.getWrapper();
	    }
	  }, {
	    key: "generate",
	    value: function generate(prompt) {
	      var _this2 = this;
	      if (!this.onGenerate) {
	        return null;
	      }
	      if (this.items.length === 0) {
	        main_core.Dom.clean(this.listWrapper);
	      }
	      var item = _classPrivateMethodGet$4(this, _createItemWithPrompt, _createItemWithPrompt2).call(this, prompt);
	      var historyImageGroup = _classPrivateMethodGet$4(this, _addNewHistoryItem, _addNewHistoryItem2).call(this, item);
	      return new Promise(function (resolve, reject) {
	        _this2.onGenerate(prompt).then(function (res) {
	          _this2.items.push(res.data.last);
	          var images = JSON.parse(res.data.result);
	          images.forEach(function (image) {
	            historyImageGroup.addImage(image);
	          });
	          resolve(res);
	        })["catch"](function (err) {
	          _classPrivateMethodGet$4(_this2, _removeHistoryImageGroup, _removeHistoryImageGroup2).call(_this2, historyImageGroup);
	          reject(err);
	        });
	      });
	    }
	  }, {
	    key: "buildHistory",
	    value: function buildHistory() {
	      var _this3 = this;
	      main_core.Dom.clean(this.listWrapper);
	      if (this.items.length === 0) {
	        var emptyState = new ImageHistoryEmptyState();
	        main_core.Dom.append(emptyState.render(), this.listWrapper);
	      }
	      this.items.forEach(function (historyItem) {
	        try {
	          _classPrivateMethodGet$4(_this3, _addHistoryItem, _addHistoryItem2).call(_this3, historyItem);
	        } catch (e) {
	          console.error('AI.Picker: history item error', e, historyItem);
	        }
	      });
	      if (this.items.length > 3) {
	        main_core.Dom.append(this.getCapacityLabel(this.capacity), this.listWrapper);
	      }
	    }
	  }]);
	  return HistoryImage;
	}(HistoryBase);
	function _addHistoryItem2(item) {
	  var imageGroup = _classPrivateMethodGet$4(this, _createImageGroup, _createImageGroup2).call(this, item);
	  var imageGroupWrapper = _classPrivateMethodGet$4(this, _renderImageGroup, _renderImageGroup2).call(this, imageGroup);
	  main_core.Dom.append(imageGroupWrapper, this.listWrapper);
	  main_core.Dom.style(imageGroupWrapper, 'opacity', 1);
	  return imageGroup;
	}
	function _addNewHistoryItem2(item) {
	  var imageGroup = _classPrivateMethodGet$4(this, _createImageGroup, _createImageGroup2).call(this, item, true);
	  var imageGroupWrapper = _classPrivateMethodGet$4(this, _renderImageGroup, _renderImageGroup2).call(this, imageGroup);
	  main_core.Dom.prepend(imageGroupWrapper, this.listWrapper);
	  var _Dom$getPosition = main_core.Dom.getPosition(imageGroupWrapper),
	    height = _Dom$getPosition.height;
	  main_core.Dom.style(imageGroupWrapper, 'height', 0);
	  requestAnimationFrame(function () {
	    main_core.Dom.style(imageGroupWrapper, {
	      opacity: 1,
	      height: "".concat(height, "px")
	    });
	  });
	  return imageGroup;
	}
	function _renderImageGroup2(imageGroup) {
	  var wrapper = main_core.Tag.render(_templateObject$7 || (_templateObject$7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__history-image_item-wrapper\"></div>\n\t\t"])));
	  main_core.Dom.append(imageGroup.render(), wrapper);
	  return wrapper;
	}
	function _createImageGroup2(item) {
	  var isNew = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	  return new HistoryImageGroup({
	    item: item,
	    size: HistoryImage.imagesInItem,
	    isNew: isNew,
	    onSelect: this.onSelect
	  });
	}
	function _createItemWithPrompt2(payload) {
	  return {
	    payload: payload,
	    id: Math.random(),
	    groupData: [],
	    data: ''
	  };
	}
	function _removeHistoryImageGroup2(group) {
	  if (!group.getLayout() || !group.getLayout().parentElement) {
	    return;
	  }
	  var groupWrapper = group.getLayout().parentElement;
	  main_core.Dom.style(groupWrapper, 'height', 0);
	  main_core.Dom.style(groupWrapper, 'padding-bottom', 0);
	  main_core.bind(groupWrapper, 'transitionend', function () {
	    main_core.Dom.remove(groupWrapper);
	  });
	}
	babelHelpers.defineProperty(HistoryImage, "imagesInItem", 1);

	var _templateObject$8;
	var TextLoader = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(TextLoader, _Base);
	  function TextLoader() {
	    babelHelpers.classCallCheck(this, TextLoader);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TextLoader).apply(this, arguments));
	  }
	  babelHelpers.createClass(TextLoader, [{
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject$8 || (_templateObject$8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_text-loader\">\n\t\t\t\t<div class=\"ai__picker_text-loader-line --one\"></div>\n\t\t\t\t<div class=\"ai__picker_text-loader-line --two\"></div>\n\t\t\t\t<div class=\"ai__picker_text-loader-line --three\"></div>\n\t\t\t\t<div class=\"ai__picker_text-loader-line --four\"></div>\n\t\t\t\t<div class=\"ai__picker_text-loader-cursor\">\n\t\t\t\t\t<div class=\"ai__picker_text-loader-cursor-inner\">\n\t\t\t\t\t\t<div class=\"ai__picker_text-loader-cursor-icon\"></div>\n\t\t\t\t\t\t<span class=\"ai__picker_text-loader-cursor-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('AI_JS_PICKER_TEXT_LOADER'));
	    }
	  }]);
	  return TextLoader;
	}(Base);

	var _templateObject$9, _templateObject2$5, _templateObject3$2, _templateObject4$1, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10;
	function _regeneratorRuntime$1() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$1 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec$5(obj, privateSet) { _checkPrivateRedeclaration$7(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$6(obj, privateMap, value) { _checkPrivateRedeclaration$7(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$7(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _previousItemsContainer = /*#__PURE__*/new WeakMap();
	var _previousItemsListContainer = /*#__PURE__*/new WeakMap();
	var _previousItemsLabel = /*#__PURE__*/new WeakMap();
	var _lastItem = /*#__PURE__*/new WeakMap();
	var _lastItemContainer = /*#__PURE__*/new WeakMap();
	var _loaderContainer = /*#__PURE__*/new WeakMap();
	var _isShowCapacityLabel = /*#__PURE__*/new WeakMap();
	var _onCopy = /*#__PURE__*/new WeakMap();
	var _generateNewItem = /*#__PURE__*/new WeakSet();
	var _showLoader = /*#__PURE__*/new WeakSet();
	var _hideLoader = /*#__PURE__*/new WeakSet();
	var _renderLastItem = /*#__PURE__*/new WeakSet();
	var _renderLoaderContainer = /*#__PURE__*/new WeakSet();
	var _renderPreviousItems = /*#__PURE__*/new WeakSet();
	var _addNewItem = /*#__PURE__*/new WeakSet();
	var _handleFailedGenerate = /*#__PURE__*/new WeakSet();
	var _renderHistoryItem = /*#__PURE__*/new WeakSet();
	var _renderHistoryItemWrapper = /*#__PURE__*/new WeakSet();
	var _renderHistoryItemDivider = /*#__PURE__*/new WeakSet();
	var _makeElemFixedWithSavingPosition = /*#__PURE__*/new WeakSet();
	var _addSpaceNodeForHistoryItem = /*#__PURE__*/new WeakSet();
	var _addCapacityLabelIfNeeded = /*#__PURE__*/new WeakSet();
	var HistoryText = /*#__PURE__*/function (_HistoryBase) {
	  babelHelpers.inherits(HistoryText, _HistoryBase);
	  function HistoryText(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, HistoryText);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(HistoryText).call(this, props));
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _addCapacityLabelIfNeeded);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _addSpaceNodeForHistoryItem);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _makeElemFixedWithSavingPosition);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _renderHistoryItemDivider);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _renderHistoryItemWrapper);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _renderHistoryItem);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _handleFailedGenerate);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _addNewItem);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _renderPreviousItems);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _renderLoaderContainer);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _renderLastItem);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _hideLoader);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _showLoader);
	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _generateNewItem);
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _previousItemsContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _previousItemsListContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _previousItemsLabel, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _lastItem, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _lastItemContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _loaderContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _isShowCapacityLabel, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$6(babelHelpers.assertThisInitialized(_this), _onCopy, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _onCopy, props.onCopy);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _isShowCapacityLabel, false);
	    return _this;
	  }
	  babelHelpers.createClass(HistoryText, [{
	    key: "generate",
	    value: function () {
	      var _generate = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee(message) {
	        return _regeneratorRuntime$1().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              if (this.onGenerate) {
	                _context.next = 2;
	                break;
	              }
	              return _context.abrupt("return", null);
	            case 2:
	              this.emit('ai-generate-start');
	              _context.next = 5;
	              return Promise.all([_classPrivateMethodGet$5(this, _showLoader, _showLoader2).call(this), this.moveLastToHistory()]);
	            case 5:
	              return _context.abrupt("return", _classPrivateMethodGet$5(this, _generateNewItem, _generateNewItem2).call(this, message));
	            case 6:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function generate(_x) {
	        return _generate.apply(this, arguments);
	      }
	      return generate;
	    }()
	  }, {
	    key: "render",
	    value: function render() {
	      this.buildHistory();
	      return this.getWrapper();
	    }
	    /**
	     * Called when user want to copy HistoryItem in buffer.
	     *
	     * @param {HistoryItem} item
	     * @param {PointerEvent} event
	     */
	  }, {
	    key: "onCopyClick",
	    value: function onCopyClick(item, event) {
	      this.showNotify(event.target, "action_copy_notify_".concat(item.id), this.getMessage('action_copy_notify'));
	      babelHelpers.classPrivateFieldGet(this, _onCopy).call(this, item);
	    }
	  }, {
	    key: "buildHistory",
	    /**
	     * Builds History container after loading History items.
	     *
	     */
	    value: function buildHistory() {
	      var _this2 = this;
	      main_core.Dom.style(this.listWrapper, 'opacity', 0);
	      main_core.Dom.style(this.listWrapper, 'transform', 'translateY(-30px)');
	      main_core.Dom.clean(this.listWrapper);
	      setTimeout(function () {
	        var firstItem = _this2.items[0];
	        _classPrivateMethodGet$5(_this2, _renderLastItem, _renderLastItem2).call(_this2, firstItem);
	        _classPrivateMethodGet$5(_this2, _renderPreviousItems, _renderPreviousItems2).call(_this2, _this2.items.slice(1));
	        _classPrivateMethodGet$5(_this2, _addCapacityLabelIfNeeded, _addCapacityLabelIfNeeded2).call(_this2);
	        main_core.Dom.style(_this2.listWrapper, {
	          opacity: 1,
	          transform: 'translateY(0)'
	        });
	        main_core.bindOnce(_this2.listWrapper, 'transitionend', function () {
	          main_core.Dom.style(_this2.listWrapper, 'transform', null);
	        });
	      }, 50);
	    }
	  }, {
	    key: "moveLastToHistory",
	    value: function moveLastToHistory() {
	      var _this3 = this;
	      return new Promise(function (resolve) {
	        var _babelHelpers$classPr, _babelHelpers$classPr2;
	        var lastNodeWrapper = (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(_this3, _lastItemContainer)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.firstElementChild;
	        if (!lastNodeWrapper) {
	          resolve(true);
	        }
	        if (_this3.items.length > 0 && !babelHelpers.classPrivateFieldGet(_this3, _previousItemsLabel)) {
	          babelHelpers.classPrivateFieldSet(_this3, _previousItemsLabel, _classPrivateMethodGet$5(_this3, _renderHistoryItemDivider, _renderHistoryItemDivider2).call(_this3, main_core.Loc.getMessage('AI_JS_PICKER_TEXT_PREVIOUS_ITEMS_LABEL')));
	          babelHelpers.classPrivateFieldGet(_this3, _previousItemsContainer).prepend(babelHelpers.classPrivateFieldGet(_this3, _previousItemsLabel));
	        }
	        main_core.Dom.removeClass(lastNodeWrapper.firstElementChild, '--first');
	        _classPrivateMethodGet$5(_this3, _makeElemFixedWithSavingPosition, _makeElemFixedWithSavingPosition2).call(_this3, lastNodeWrapper);
	        var spaceNodeForNewItem = _classPrivateMethodGet$5(_this3, _addSpaceNodeForHistoryItem, _addSpaceNodeForHistoryItem2).call(_this3);
	        main_core.bindOnce(lastNodeWrapper, 'transitionend', function () {
	          spaceNodeForNewItem.remove();
	          main_core.Dom.prepend(lastNodeWrapper, babelHelpers.classPrivateFieldGet(_this3, _previousItemsListContainer));
	          lastNodeWrapper.style = null;
	          resolve(true);
	        });
	        var loaderHeight = ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(_this3, _loaderContainer)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.offsetHeight) || 0;
	        var lastNodeHeight = lastNodeWrapper.offsetHeight;
	        var shift = -main_core.Dom.getRelativePosition(lastNodeWrapper, spaceNodeForNewItem).y - (lastNodeHeight - loaderHeight);
	        main_core.Dom.style(lastNodeWrapper, 'transform', "translateY(".concat(shift, "px)"));
	        main_core.Dom.style(spaceNodeForNewItem, 'height', "".concat(lastNodeWrapper.offsetHeight, "px"));
	      });
	    }
	  }, {
	    key: "moveTopHistoryItem",
	    value: function moveTopHistoryItem() {
	      var _this4 = this;
	      return new Promise(function (resolve) {
	        babelHelpers.classPrivateFieldGet(_this4, _lastItemContainer).style = null;
	        var firstHistoryItem = babelHelpers.classPrivateFieldGet(_this4, _previousItemsListContainer).children[0];
	        _classPrivateMethodGet$5(_this4, _makeElemFixedWithSavingPosition, _makeElemFixedWithSavingPosition2).call(_this4, firstHistoryItem);
	        var spaceNodeForHistoryItem = _classPrivateMethodGet$5(_this4, _addSpaceNodeForHistoryItem, _addSpaceNodeForHistoryItem2).call(_this4, firstHistoryItem);
	        requestAnimationFrame(function () {
	          var shift = -main_core.Dom.getRelativePosition(babelHelpers.classPrivateFieldGet(_this4, _lastItem), spaceNodeForHistoryItem).y;
	          main_core.bindOnce(firstHistoryItem, 'transitionend', function () {
	            firstHistoryItem.style = null;
	            babelHelpers.classPrivateFieldGet(_this4, _lastItemContainer).prepend(firstHistoryItem);
	            resolve(true);
	          });
	          main_core.bindOnce(spaceNodeForHistoryItem, 'transitionend', function () {
	            spaceNodeForHistoryItem.remove();
	          });
	          main_core.Dom.style(firstHistoryItem, 'transform', "translateY(".concat(-shift, "px"));
	          main_core.Dom.addClass(firstHistoryItem.children[0], '--first');
	          main_core.Dom.style(spaceNodeForHistoryItem, 'height', '0px');
	          var firstHistoryItemHeight = main_core.Dom.getPosition(firstHistoryItem).height;
	          main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this4, _lastItem), 'height', "".concat(firstHistoryItemHeight, "px"));
	        });
	      });
	    }
	  }]);
	  return HistoryText;
	}(HistoryBase);
	function _generateNewItem2(_x2) {
	  return _generateNewItem3.apply(this, arguments);
	}
	function _generateNewItem3() {
	  _generateNewItem3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee2(message) {
	    var res;
	    return _regeneratorRuntime$1().wrap(function _callee2$(_context2) {
	      while (1) switch (_context2.prev = _context2.next) {
	        case 0:
	          _context2.prev = 0;
	          _context2.next = 3;
	          return this.onGenerate(message);
	        case 3:
	          res = _context2.sent;
	          _context2.next = 6;
	          return _classPrivateMethodGet$5(this, _addNewItem, _addNewItem2).call(this, res.data.last);
	        case 6:
	          this.emit('ai-generate-finish');
	          return _context2.abrupt("return", res);
	        case 10:
	          _context2.prev = 10;
	          _context2.t0 = _context2["catch"](0);
	          _classPrivateMethodGet$5(this, _handleFailedGenerate, _handleFailedGenerate2).call(this, _context2.t0);
	          throw _context2.t0;
	        case 14:
	        case "end":
	          return _context2.stop();
	      }
	    }, _callee2, this, [[0, 10]]);
	  }));
	  return _generateNewItem3.apply(this, arguments);
	}
	function _showLoader2() {
	  var _this5 = this;
	  return new Promise(function (resolve) {
	    var _babelHelpers$classPr3;
	    if (!babelHelpers.classPrivateFieldGet(_this5, _lastItemContainer)) {
	      resolve(true);
	    }
	    main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this5, _lastItem), 'height', "".concat((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(_this5, _lastItemContainer)) === null || _babelHelpers$classPr3 === void 0 ? void 0 : _babelHelpers$classPr3.scrollHeight, "px"));
	    main_core.bindOnce(babelHelpers.classPrivateFieldGet(_this5, _lastItem), 'transitionend', function () {
	      resolve(true);
	    });
	    babelHelpers.classPrivateFieldGet(_this5, _loaderContainer).hidden = false;
	    main_core.Dom.append(new TextLoader().render(), babelHelpers.classPrivateFieldGet(_this5, _loaderContainer));
	    main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this5, _loaderContainer), 'opacity', 1);
	    main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this5, _lastItem), 'height', "".concat(babelHelpers.classPrivateFieldGet(_this5, _loaderContainer).offsetHeight, "px"));
	  });
	}
	function _hideLoader2() {
	  var _this6 = this;
	  return new Promise(function (resolve) {
	    if (babelHelpers.classPrivateFieldGet(_this6, _loaderContainer).hidden === true) {
	      resolve(true);
	    }
	    main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this6, _loaderContainer), 'opacity', 0);
	    main_core.bindOnce(babelHelpers.classPrivateFieldGet(_this6, _loaderContainer), 'transitionend', function () {
	      main_core.Dom.clean(babelHelpers.classPrivateFieldGet(_this6, _loaderContainer));
	      babelHelpers.classPrivateFieldGet(_this6, _loaderContainer).hidden = true;
	      resolve(true);
	    });
	  });
	}
	function _renderLastItem2(item) {
	  babelHelpers.classPrivateFieldSet(this, _lastItem, main_core.Tag.render(_templateObject$9 || (_templateObject$9 = babelHelpers.taggedTemplateLiteral(["<div class=\"ai__picker__text-history-last\"></div>"]))));
	  _classPrivateMethodGet$5(this, _renderLoaderContainer, _renderLoaderContainer2).call(this);
	  babelHelpers.classPrivateFieldSet(this, _lastItemContainer, main_core.Tag.render(_templateObject2$5 || (_templateObject2$5 = babelHelpers.taggedTemplateLiteral(["<div class=\"ai__picker__text-history-last-item\"></div>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _lastItemContainer), babelHelpers.classPrivateFieldGet(this, _lastItem));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _loaderContainer), babelHelpers.classPrivateFieldGet(this, _lastItem));
	  main_core.Dom.clean(this.listWrapper);
	  if (item) {
	    var itemWrapper = _classPrivateMethodGet$5(this, _renderHistoryItemWrapper, _renderHistoryItemWrapper2).call(this);
	    var itemNode = _classPrivateMethodGet$5(this, _renderHistoryItem, _renderHistoryItem2).call(this, item, true);
	    main_core.Dom.append(itemNode, itemWrapper);
	    main_core.Dom.append(itemWrapper, babelHelpers.classPrivateFieldGet(this, _lastItemContainer));
	  }
	  main_core.Dom.prepend(babelHelpers.classPrivateFieldGet(this, _lastItem), this.listWrapper);
	}
	function _renderLoaderContainer2() {
	  babelHelpers.classPrivateFieldSet(this, _loaderContainer, main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker__text-history-loader\"></div>\n\t\t"]))));
	  babelHelpers.classPrivateFieldGet(this, _loaderContainer).hidden = true;
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _loaderContainer), 'opacity', 0);
	  return babelHelpers.classPrivateFieldGet(this, _loaderContainer);
	}
	function _renderPreviousItems2(items) {
	  var _this7 = this;
	  babelHelpers.classPrivateFieldSet(this, _previousItemsContainer, main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker__text-history-previous\"></div>\n\t\t"]))));
	  if (items.length > 1) {
	    babelHelpers.classPrivateFieldSet(this, _previousItemsLabel, _classPrivateMethodGet$5(this, _renderHistoryItemDivider, _renderHistoryItemDivider2).call(this, main_core.Loc.getMessage('AI_JS_PICKER_TEXT_PREVIOUS_ITEMS_LABEL')));
	    main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _previousItemsLabel), babelHelpers.classPrivateFieldGet(this, _previousItemsContainer));
	  }
	  babelHelpers.classPrivateFieldSet(this, _previousItemsListContainer, main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"ai__picker__text-history-previous-items\"></div>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _previousItemsListContainer), babelHelpers.classPrivateFieldGet(this, _previousItemsContainer));
	  items.slice(1).forEach(function (item) {
	    var node = _classPrivateMethodGet$5(_this7, _renderHistoryItem, _renderHistoryItem2).call(_this7, item);
	    var nodeWrapper = _classPrivateMethodGet$5(_this7, _renderHistoryItemWrapper, _renderHistoryItemWrapper2).call(_this7);
	    main_core.Dom.append(node, nodeWrapper);
	    main_core.Dom.append(nodeWrapper, babelHelpers.classPrivateFieldGet(_this7, _previousItemsListContainer));
	  });
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _previousItemsContainer), this.listWrapper);
	}
	function _addNewItem2(item) {
	  var _this8 = this;
	  return new Promise(function (resolve) {
	    main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this8, _lastItemContainer), {
	      opacity: 0,
	      transform: 'translateY(-5px)'
	    });
	    _this8.items.unshift(item);
	    _classPrivateMethodGet$5(_this8, _addCapacityLabelIfNeeded, _addCapacityLabelIfNeeded2).call(_this8);
	    _classPrivateMethodGet$5(_this8, _hideLoader, _hideLoader2).call(_this8).then(function () {
	      var firstItemWrapper = _classPrivateMethodGet$5(_this8, _renderHistoryItemWrapper, _renderHistoryItemWrapper2).call(_this8);
	      var firstItemNode = _classPrivateMethodGet$5(_this8, _renderHistoryItem, _renderHistoryItem2).call(_this8, item, true);
	      main_core.Dom.append(firstItemNode, firstItemWrapper);
	      main_core.Dom.append(firstItemWrapper, babelHelpers.classPrivateFieldGet(_this8, _lastItemContainer));
	      babelHelpers.classPrivateFieldGet(_this8, _lastItemContainer).style = null;
	      main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this8, _lastItem), 'height', "".concat(babelHelpers.classPrivateFieldGet(_this8, _lastItem).scrollHeight, "px"));
	      var clearLastItemContainerStyle = function clearLastItemContainerStyle() {
	        babelHelpers.classPrivateFieldGet(_this8, _lastItemContainer).removeAttribute('style');
	      };
	      main_core.bindOnce(babelHelpers.classPrivateFieldGet(_this8, _lastItemContainer), 'transitionend', function () {
	        clearLastItemContainerStyle();
	        resolve(true);
	      });
	    })["catch"](function (err) {
	      // eslint-disable-next-line no-console
	      console.error(err);
	    });
	  });
	}
	function _handleFailedGenerate2() {
	  return _handleFailedGenerate3.apply(this, arguments);
	}
	function _handleFailedGenerate3() {
	  _handleFailedGenerate3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee3() {
	    return _regeneratorRuntime$1().wrap(function _callee3$(_context3) {
	      while (1) switch (_context3.prev = _context3.next) {
	        case 0:
	          _context3.next = 2;
	          return _classPrivateMethodGet$5(this, _hideLoader, _hideLoader2).call(this);
	        case 2:
	          if (!(this.items.length === 0)) {
	            _context3.next = 5;
	            break;
	          }
	          this.emit('ai-generate-failed');
	          return _context3.abrupt("return");
	        case 5:
	          _context3.next = 7;
	          return this.moveTopHistoryItem();
	        case 7:
	          this.emit('ai-generate-failed');
	        case 8:
	        case "end":
	          return _context3.stop();
	      }
	    }, _callee3, this);
	  }));
	  return _handleFailedGenerate3.apply(this, arguments);
	}
	function _renderHistoryItem2(item, justAdded) {
	  if (!item) {
	    return null;
	  }
	  var itemClassname = "ai__picker_text-history-item ".concat(justAdded ? '--first' : '');
	  var actionBtnAccentModifier = justAdded ? '--accent' : '';
	  var actionCopyBtnClassname = 'ai__picker_text-history-item-action-btn --copy';
	  var actionUseBtnClassname = "ai__picker_text-history-item-action-btn --paste ".concat(actionBtnAccentModifier);
	  return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<article\n\t\t\t\tclass=\"", "\"\n\t\t\t>\n\t\t\t\t<div class=\"ai__picker_text-history-item-text\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ai__picker_text-history-item-actions\">\n\t\t\t\t\t<div class=\"ai__picker_text-history-item-action\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"", "\"\n\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ai__picker_text-history-item-action-icon\"></span>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ai__picker_text-history-item-action\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"", "\"\n\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<span class=\"ai__picker_text-history-item-action-icon\"></span>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</article>\n\t\t"])), itemClassname, main_core.Text.encode(item.data).replaceAll(/(\r\n|\r|\n)/g, '<br>'), actionUseBtnClassname, this.onSelectClick.bind(this, item), this.getMessage('action_use'), actionCopyBtnClassname, this.onCopyClick.bind(this, item), this.getMessage('action_copy'));
	}
	function _renderHistoryItemWrapper2() {
	  return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<div class=\"ai__picker_text-history-item-wrapper\"></div>"])));
	}
	function _renderHistoryItemDivider2(text) {
	  var textElem = text ? main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<span class=\"ai__picker_text-history-item-divider-text\">", "</span>"])), text) : '';
	  return main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_text-history-item-divider\">\n\t\t\t\t<hr class=\"ai__picker_text-history-item-divider-line\"/>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), textElem);
	}
	function _makeElemFixedWithSavingPosition2(elem) {
	  var position = main_core.Dom.getPosition(elem);
	  main_core.Dom.style(elem, {
	    position: 'fixed',
	    top: "".concat(position.y, "px"),
	    left: "".concat(position.x, "px"),
	    width: "".concat(position.width, "px")
	  });
	  return elem;
	}
	function _addSpaceNodeForHistoryItem2(historyItem) {
	  var historyItemHeight = main_core.Dom.getPosition(historyItem).height;
	  var spaceNodeForHistoryItem = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["<div class=\"ai__picker_text-history-space-for-new-item\"></div>"])));
	  main_core.Dom.style(spaceNodeForHistoryItem, 'height', "".concat(historyItemHeight, "px"));
	  if (historyItem) {
	    main_core.Dom.insertBefore(spaceNodeForHistoryItem, historyItem.nextSibling);
	  } else {
	    babelHelpers.classPrivateFieldGet(this, _previousItemsListContainer).prepend(spaceNodeForHistoryItem);
	  }
	  return spaceNodeForHistoryItem;
	}
	function _addCapacityLabelIfNeeded2() {
	  if (this.items.length > Math.round(this.capacity / 2) && !babelHelpers.classPrivateFieldGet(this, _isShowCapacityLabel)) {
	    main_core.Dom.append(this.getCapacityLabel(this.capacity), this.listWrapper);
	    babelHelpers.classPrivateFieldSet(this, _isShowCapacityLabel, true);
	  }
	}

	var _templateObject$a, _templateObject2$6;
	function _classPrivateMethodInitSpec$6(obj, privateSet) { _checkPrivateRedeclaration$8(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$7(obj, privateMap, value) { _checkPrivateRedeclaration$8(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$8(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$6(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _textarea = /*#__PURE__*/new WeakMap();
	var _text$1 = /*#__PURE__*/new WeakMap();
	var _placeholder = /*#__PURE__*/new WeakMap();
	var _renderTextArea = /*#__PURE__*/new WeakSet();
	var _handleInput = /*#__PURE__*/new WeakSet();
	var TextField = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(TextField, _Base);
	  function TextField(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, TextField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TextField).call(this));
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _handleInput);
	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _renderTextArea);
	    _classPrivateFieldInitSpec$7(babelHelpers.assertThisInitialized(_this), _textarea, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$7(babelHelpers.assertThisInitialized(_this), _text$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$7(babelHelpers.assertThisInitialized(_this), _placeholder, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _textarea, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _text$1, main_core.Type.isString(props.value) ? props.value : '');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _placeholder, main_core.Type.isString(props.placeholder) ? props.placeholder : '');
	    return _this;
	  }
	  babelHelpers.createClass(TextField, [{
	    key: "getValue",
	    value: function getValue() {
	      return babelHelpers.classPrivateFieldGet(this, _textarea).value;
	    }
	  }, {
	    key: "setValue",
	    value: function setValue(text) {
	      if (main_core.Type.isString(text) && babelHelpers.classPrivateFieldGet(this, _textarea)) {
	        babelHelpers.classPrivateFieldGet(this, _textarea).value = text;
	      }
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject$a || (_templateObject$a = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_textarea_wrapper\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$6(this, _renderTextArea, _renderTextArea2).call(this));
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      babelHelpers.classPrivateFieldGet(this, _textarea).disabled = true;
	    }
	  }, {
	    key: "enable",
	    value: function enable() {
	      babelHelpers.classPrivateFieldGet(this, _textarea).disabled = false;
	    }
	  }, {
	    key: "focus",
	    value: function focus() {
	      var contentLength = babelHelpers.classPrivateFieldGet(this, _textarea).value.length;
	      babelHelpers.classPrivateFieldGet(this, _textarea).setSelectionRange(contentLength, contentLength);
	      babelHelpers.classPrivateFieldGet(this, _textarea).focus();
	    }
	  }, {
	    key: "isDisabled",
	    value: function isDisabled() {
	      return babelHelpers.classPrivateFieldGet(this, _textarea).disabled;
	    }
	  }]);
	  return TextField;
	}(Base);
	function _renderTextArea2() {
	  babelHelpers.classPrivateFieldSet(this, _textarea, main_core.Tag.render(_templateObject2$6 || (_templateObject2$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<textarea\n\t\t\t\tclass=\"ai__picker_textarea\"\n\t\t\t\tplaceholder=\"", "\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t</textarea>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _placeholder), babelHelpers.classPrivateFieldGet(this, _text$1)));
	  main_core.bind(babelHelpers.classPrivateFieldGet(this, _textarea), 'input', _classPrivateMethodGet$6(this, _handleInput, _handleInput2).bind(this));
	  this.setValue(babelHelpers.classPrivateFieldGet(this, _text$1));
	  return babelHelpers.classPrivateFieldGet(this, _textarea);
	}
	function _handleInput2(e) {
	  var value = e.target.value;
	  main_core_events.EventEmitter.emit(this, 'input', {
	    value: value
	  });
	  this.setValue(value);
	}

	var _templateObject$b, _templateObject2$7;
	function _classPrivateMethodInitSpec$7(obj, privateSet) { _checkPrivateRedeclaration$9(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$8(obj, privateMap, value) { _checkPrivateRedeclaration$9(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$9(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$7(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var TextMessageSubmitButtonIcon = Object.freeze({
	  PENCIL: 'pencil',
	  BRUSH: 'brush'
	});
	var _submitBtn = /*#__PURE__*/new WeakMap();
	var _textField = /*#__PURE__*/new WeakMap();
	var _hintPopup = /*#__PURE__*/new WeakMap();
	var _buttonIcon = /*#__PURE__*/new WeakMap();
	var _container$1 = /*#__PURE__*/new WeakMap();
	var _submitBtnContainer = /*#__PURE__*/new WeakMap();
	var _isLoading = /*#__PURE__*/new WeakMap();
	var _isValidButtonIcon = /*#__PURE__*/new WeakSet();
	var _renderButton = /*#__PURE__*/new WeakSet();
	var _getTextArea = /*#__PURE__*/new WeakSet();
	var _handleTextareaInput = /*#__PURE__*/new WeakSet();
	var _getButtonState = /*#__PURE__*/new WeakSet();
	var _setSubmitBtnState = /*#__PURE__*/new WeakSet();
	var TextMessage = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(TextMessage, _Base);
	  function TextMessage(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, TextMessage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TextMessage).call(this, props));
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _setSubmitBtnState);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _getButtonState);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _handleTextareaInput);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _getTextArea);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _renderButton);
	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _isValidButtonIcon);
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _submitBtn, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _textField, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _hintPopup, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _buttonIcon, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _container$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _submitBtnContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$8(babelHelpers.assertThisInitialized(_this), _isLoading, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('AI:Picker:TextMessage');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _hintPopup, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _container$1, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _buttonIcon, _classPrivateMethodGet$7(babelHelpers.assertThisInitialized(_this), _isValidButtonIcon, _isValidButtonIcon2).call(babelHelpers.assertThisInitialized(_this), props.submitButtonIcon) ? props.submitButtonIcon : 'pencil');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _isLoading, props.isLoading);
	    return _this;
	  }
	  babelHelpers.createClass(TextMessage, [{
	    key: "focus",
	    value: function focus() {
	      if (!babelHelpers.classPrivateFieldGet(this, _textField)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldGet(this, _textField).focus();
	    }
	  }, {
	    key: "getButton",
	    value: function getButton() {
	      var _this2 = this;
	      var btn = new ui_buttons.Button({
	        text: this.getMessage('submit'),
	        round: true,
	        color: ui_buttons.Button.Color.PRIMARY,
	        icon: ui_buttons.ButtonIcon.SEARCH,
	        onclick: function onclick(button) {
	          if (button.getState() === null && babelHelpers.classPrivateFieldGet(_this2, _textField).getValue() !== '') {
	            _this2.emit('submit', {
	              text: babelHelpers.classPrivateFieldGet(_this2, _textField).getValue()
	            });
	          }
	        },
	        state: this.props.message ? '' : ui_buttons.Button.State.DISABLED,
	        className: "ai__picker_submit-btn --".concat(babelHelpers.classPrivateFieldGet(this, _buttonIcon))
	      });
	      babelHelpers.classPrivateFieldSet(this, _submitBtn, btn);
	      return btn.render();
	    }
	  }, {
	    key: "closeMenu",
	    value: function closeMenu() {
	      if (babelHelpers.classPrivateFieldGet(this, _hintPopup)) {
	        babelHelpers.classPrivateFieldGet(this, _hintPopup).close();
	      }
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      babelHelpers.classPrivateFieldSet(this, _submitBtnContainer, main_core.Tag.render(_templateObject$b || (_templateObject$b = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	      babelHelpers.classPrivateFieldSet(this, _container$1, main_core.Tag.render(_templateObject2$7 || (_templateObject2$7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker_text-message\">\n\t\t\t\t<div class=\"ai__picker_text-message_text-field-wrapper\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$7(this, _getTextArea, _getTextArea2).call(this).render(), babelHelpers.classPrivateFieldGet(this, _submitBtnContainer)));
	      _classPrivateMethodGet$7(this, _renderButton, _renderButton2).call(this);
	      return babelHelpers.classPrivateFieldGet(this, _container$1);
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      if (babelHelpers.classPrivateFieldGet(this, _textField)) {
	        babelHelpers.classPrivateFieldGet(this, _textField).disable();
	      }
	      _classPrivateMethodGet$7(this, _setSubmitBtnState, _setSubmitBtnState2).call(this, ui_buttons.Button.State.DISABLED);
	    }
	  }, {
	    key: "enable",
	    value: function enable() {
	      babelHelpers.classPrivateFieldGet(this, _textField).enable();
	      if (babelHelpers.classPrivateFieldGet(this, _textField).getValue()) {
	        _classPrivateMethodGet$7(this, _setSubmitBtnState, _setSubmitBtnState2).call(this, null);
	      }
	    }
	  }, {
	    key: "startLoading",
	    value: function startLoading() {
	      babelHelpers.classPrivateFieldSet(this, _isLoading, true);
	      babelHelpers.classPrivateFieldGet(this, _textField).disable();
	      _classPrivateMethodGet$7(this, _setSubmitBtnState, _setSubmitBtnState2).call(this, ui_buttons.Button.State.CLOCKING);
	    }
	  }, {
	    key: "finishLoading",
	    value: function finishLoading() {
	      babelHelpers.classPrivateFieldSet(this, _isLoading, false);
	      babelHelpers.classPrivateFieldGet(this, _textField).enable();
	      var btnState = _classPrivateMethodGet$7(this, _getButtonState, _getButtonState2).call(this);
	      _classPrivateMethodGet$7(this, _setSubmitBtnState, _setSubmitBtnState2).call(this, btnState);
	    }
	  }]);
	  return TextMessage;
	}(Base);
	function _isValidButtonIcon2(buttonIcon) {
	  return Object.values(TextMessageSubmitButtonIcon).includes(buttonIcon);
	}
	function _renderButton2() {
	  if (babelHelpers.classPrivateFieldGet(this, _submitBtnContainer)) {
	    babelHelpers.classPrivateFieldGet(this, _submitBtnContainer).innerHTML = '';
	    main_core.Dom.append(this.getButton(), babelHelpers.classPrivateFieldGet(this, _submitBtnContainer));
	  }
	}
	function _getTextArea2() {
	  var placeholder = this.getMessage('placeholder');
	  var textarea = new TextField({
	    value: this.props.message,
	    placeholder: placeholder
	  });
	  babelHelpers.classPrivateFieldSet(this, _textField, textarea);
	  main_core_events.EventEmitter.subscribe(textarea, 'input', _classPrivateMethodGet$7(this, _handleTextareaInput, _handleTextareaInput2).bind(this));
	  return textarea;
	}
	function _handleTextareaInput2(event) {
	  if (event.data.value && babelHelpers.classPrivateFieldGet(this, _isLoading) === false) {
	    _classPrivateMethodGet$7(this, _setSubmitBtnState, _setSubmitBtnState2).call(this, null);
	  } else {
	    _classPrivateMethodGet$7(this, _setSubmitBtnState, _setSubmitBtnState2).call(this, ui_buttons.Button.State.DISABLED);
	  }
	}
	function _getButtonState2() {
	  if (babelHelpers.classPrivateFieldGet(this, _isLoading)) {
	    return ui_buttons.Button.State.CLOCKING;
	  }
	  if (!babelHelpers.classPrivateFieldGet(this, _textField).getValue()) {
	    return ui_buttons.Button.State.DISABLED;
	  }
	  return null;
	}
	function _setSubmitBtnState2(state) {
	  if (babelHelpers.classPrivateFieldGet(this, _submitBtn)) {
	    babelHelpers.classPrivateFieldGet(this, _submitBtn).getContainer().blur();
	    babelHelpers.classPrivateFieldGet(this, _submitBtn).setState(state);
	  }
	}

	var UI = {
	  Base: Base,
	  IconClose: IconClose,
	  Header: Header,
	  HistoryImage: HistoryImage,
	  HistoryText: HistoryText,
	  TextMessage: TextMessage
	};

	var _templateObject$c;
	function _regeneratorRuntime$2() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$2 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec$8(obj, privateSet) { _checkPrivateRedeclaration$a(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$a(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$8(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _isAgreementError = /*#__PURE__*/new WeakSet();
	var _handleAgreementError = /*#__PURE__*/new WeakSet();
	var PickerBase = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(PickerBase, _Base);
	  function PickerBase() {
	    var _this;
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, PickerBase);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PickerBase).call(this, props));
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _handleAgreementError);
	    _classPrivateMethodInitSpec$8(babelHelpers.assertThisInitialized(_this), _isAgreementError);
	    _this.onGenerate = props.onGenerate;
	    _this.onLoadHistory = props.onLoadHistory;
	    _this.onTariffRestriction = props.onTariffRestriction;
	    _this.startMessage = props.startMessage;
	    _this.engines = props.engines;
	    _this.items = [];
	    _this.capacity = 30;
	    _this.historyContainer = null;
	    _this.isToolingLoading = false;
	    _this.engine = props.engine;
	    _this.context = props.context;
	    _this.isResultCopied = false;
	    _this.isResultSelected = false;
	    _this.onSelect = props.onSelect;
	    _this.pickerType = '';
	    _this.setEventNamespace('AI:PickerBase');
	    return _this;
	  }
	  babelHelpers.createClass(PickerBase, [{
	    key: "render",
	    value: function render() {
	      throw new Error('You must implement render method');
	    }
	  }, {
	    key: "setEngineParameters",
	    value: function setEngineParameters(parameters) {
	      if (this.engine) {
	        this.engine.setParameters(parameters);
	      }
	    }
	  }, {
	    key: "setOnGenerate",
	    value: function setOnGenerate(onGenerate) {
	      this.onGenerate = onGenerate;
	    }
	  }, {
	    key: "setEngine",
	    value: function setEngine(engine) {
	      this.engine = engine;
	    }
	  }, {
	    key: "setOnLoadHistory",
	    value: function setOnLoadHistory(onLoadHistory) {
	      this.onLoadHistory = onLoadHistory;
	    }
	  }, {
	    key: "setStartMessage",
	    value: function setStartMessage(startMessage) {
	      this.startMessage = startMessage;
	    }
	  }, {
	    key: "setContext",
	    value: function setContext(context) {
	      this.context = context;
	    }
	  }, {
	    key: "isResultUsed",
	    value: function isResultUsed() {
	      return this.isResultCopied || this.isResultSelected;
	    }
	  }, {
	    key: "resetResultUsedFlag",
	    value: function resetResultUsedFlag() {
	      this.isResultCopied = false;
	      this.isResultSelected = false;
	    }
	  }, {
	    key: "closeAllMenus",
	    value: function closeAllMenus() {
	      this.textMessage.closeMenu();
	    }
	  }, {
	    key: "initTooling",
	    value: function () {
	      var _initTooling = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$2().mark(function _callee(category) {
	        var _this2 = this;
	        var res;
	        return _regeneratorRuntime$2().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              this.isToolingLoading = true;
	              if (this.textMessage) {
	                this.textMessage.startLoading();
	              }
	              _context.prev = 2;
	              _context.next = 5;
	              return this.engine.getImagePickerTooling();
	            case 5:
	              res = _context.sent;
	              this.engines = res.data.engines;
	              this.items = res.data.history.items;
	              this.capacity = res.data.history.capacity;
	              if (this.textMessage) {
	                this.textMessage.finishLoading();
	                this.textMessage.focus();
	              }
	              _context.next = 16;
	              break;
	            case 12:
	              _context.prev = 12;
	              _context.t0 = _context["catch"](2);
	              console.error(_context.t0);
	              BX.UI.Notification.Center.notify({
	                id: 'AI_JS_PICKER_INIT_ERROR',
	                content: main_core.Loc.getMessage('AI_JS_PICKER_INIT_ERROR'),
	                showOnTopWindow: true
	              });
	            case 16:
	              _context.prev = 16;
	              if (this.history) {
	                this.history.items = this.items;
	                main_core.Dom.style(this.historyContainer, 'opacity', 0);
	                main_core.bindOnce(this.historyContainer, 'transitionend', function () {
	                  main_core.Dom.clean(_this2.historyContainer);
	                  main_core.Dom.append(_this2.history.render(), _this2.historyContainer);
	                  main_core.Dom.style(_this2.historyContainer, 'opacity', 1);
	                });
	              }
	              if (this.textMessage) {
	                this.textMessage.finishLoading();
	              }
	              this.isToolingLoading = false;
	              return _context.finish(16);
	            case 21:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this, [[2, 12, 16, 21]]);
	      }));
	      function initTooling(_x) {
	        return _initTooling.apply(this, arguments);
	      }
	      return initTooling;
	    }()
	  }, {
	    key: "renderTextMessage",
	    value: function renderTextMessage() {
	      this.initTextMessage();
	      return main_core.Tag.render(_templateObject$c || (_templateObject$c = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker-text_message-field\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.textMessage.render());
	    }
	  }, {
	    key: "initTextMessage",
	    value: function initTextMessage() {
	      this.textMessage = new UI.TextMessage({
	        message: this.startMessage,
	        engines: this.engines,
	        submitButtonIcon: this.getTextMessageSubmitButtonIcon(),
	        hint: this.getHint(),
	        context: this.context,
	        isLoading: this.isToolingLoading
	      });
	      this.textMessage.subscribe('submit', this.handleTextMessageSubmit.bind(this));
	    }
	  }, {
	    key: "handleSelect",
	    value: function handleSelect(event) {
	      this.isResultSelected = true;
	      this.emit('select', {
	        item: event.data.item
	      });
	    }
	  }, {
	    key: "handleCopy",
	    value: function handleCopy(event) {
	      this.isResultCopied = true;
	      this.emit('copy', {
	        item: event.data.item
	      });
	    }
	  }, {
	    key: "getTextMessageSubmitButtonIcon",
	    value: function getTextMessageSubmitButtonIcon() {
	      return TextMessageSubmitButtonIcon.PENCIL;
	    }
	  }, {
	    key: "getHint",
	    value: function getHint() {
	      return null;
	    }
	  }, {
	    key: "handleTextMessageSubmit",
	    value: function handleTextMessageSubmit(event) {
	      var prompt = event.data.text;
	      this.generate(prompt);
	    }
	  }, {
	    key: "generate",
	    value: function generate(prompt) {
	      var _this3 = this;
	      this.textMessage.startLoading();
	      this.history.generate(prompt).then(function () {
	        _this3.textMessage.finishLoading();
	      })["catch"](function (err) {
	        var _err$errors;
	        _this3.textMessage.finishLoading();
	        var firstError = (_err$errors = err.errors) === null || _err$errors === void 0 ? void 0 : _err$errors[0];
	        if (_classPrivateMethodGet$8(_this3, _isAgreementError, _isAgreementError2).call(_this3, firstError)) {
	          _classPrivateMethodGet$8(_this3, _handleAgreementError, _handleAgreementError2).call(_this3, firstError, prompt);
	        } else if ((firstError === null || firstError === void 0 ? void 0 : firstError.code) === 'LIMIT_IS_EXCEEDED_MONTHLY' || (firstError === null || firstError === void 0 ? void 0 : firstError.code) === 'LIMIT_IS_EXCEEDED_DAILY' || (firstError === null || firstError === void 0 ? void 0 : firstError.code) === 'LIMIT_IS_EXCEEDED_BAAS' || (firstError === null || firstError === void 0 ? void 0 : firstError.code) === 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF') {
	          ai_ajaxErrorHandler.AjaxErrorHandler.handleImageGenerateError({
	            errorCode: firstError === null || firstError === void 0 ? void 0 : firstError.code,
	            baasOptions: {
	              bindElement: null,
	              useSlider: true,
	              context: 'notSet'
	            }
	          });
	          _this3.textMessage.finishLoading();
	        } else {
	          _this3.handleGenerateFail();
	        }
	      });
	    }
	  }, {
	    key: "handleGenerateFail",
	    value: function handleGenerateFail() {
	      BX.UI.Notification.Center.notify({
	        id: 'AI_JS_PICKER_TEXT_GENERATE_FAILED',
	        content: main_core.Loc.getMessage('AI_JS_PICKER_TEXT_GENERATE_FAILED'),
	        showOnTopWindow: true
	      });
	      this.textMessage.finishLoading();
	    }
	  }]);
	  return PickerBase;
	}(Base);
	function _isAgreementError2(err) {
	  return (err === null || err === void 0 ? void 0 : err.code) === 'AGREEMENT_IS_NOT_ACCEPTED';
	}
	function _handleAgreementError2(err, prompt) {
	  var _this4 = this;
	  var agreementData = err.customData;
	  var currentEngine = this.engines.find(function (e) {
	    return e.selected;
	  });
	  var agreement = new ai_agreement.Agreement({
	    agreement: {
	      title: agreementData.title,
	      text: agreementData.text,
	      accepted: agreementData.accepted
	    },
	    engineCode: currentEngine.code,
	    engine: this.engine,
	    type: this.pickerType
	  });
	  agreement.showAgreementPopup(function () {
	    _this4.generate(prompt);
	  });
	}

	var _templateObject$d, _templateObject2$8, _templateObject3$3;
	function _regeneratorRuntime$3() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$3 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec$9(obj, privateSet) { _checkPrivateRedeclaration$b(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$9(obj, privateMap, value) { _checkPrivateRedeclaration$b(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$b(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$9(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _onCopy$1 = /*#__PURE__*/new WeakMap();
	var _renderHistory = /*#__PURE__*/new WeakSet();
	var _renderHistoryLoadingState = /*#__PURE__*/new WeakSet();
	var PickerText = /*#__PURE__*/function (_PickerBase) {
	  babelHelpers.inherits(PickerText, _PickerBase);
	  function PickerText() {
	    var _this;
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, PickerText);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PickerText).call(this, props));
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _renderHistoryLoadingState);
	    _classPrivateMethodInitSpec$9(babelHelpers.assertThisInitialized(_this), _renderHistory);
	    _classPrivateFieldInitSpec$9(babelHelpers.assertThisInitialized(_this), _onCopy$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _onCopy$1, props.onCopy);
	    _this.pickerType = 'text';
	    _this.setEventNamespace('AI:PickerText');
	    return _this;
	  }
	  babelHelpers.createClass(PickerText, [{
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject$d || (_templateObject$d = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker-text\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.renderTextMessage(), _classPrivateMethodGet$9(this, _renderHistory, _renderHistory2).call(this));
	    }
	  }, {
	    key: "initHistory",
	    value: function initHistory() {
	      var _this2 = this;
	      var generate = function generate(prompt) {
	        var _engine$code;
	        var engine = _this2.engines.find(function (e) {
	          return e.selected;
	        });
	        var engineCode = (_engine$code = engine === null || engine === void 0 ? void 0 : engine.code) !== null && _engine$code !== void 0 ? _engine$code : _this2.engines[0].code;
	        return _this2.onGenerate(prompt, engineCode);
	      };
	      this.history = new UI.HistoryText({
	        items: this.items,
	        capacity: this.capacity,
	        onGenerate: generate,
	        onSelect: this.onSelect,
	        onCopy: babelHelpers.classPrivateFieldGet(this, _onCopy$1)
	      });
	    }
	  }, {
	    key: "acceptAgreement",
	    value: function () {
	      var _acceptAgreement = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$3().mark(function _callee(engineCode) {
	        return _regeneratorRuntime$3().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              return _context.abrupt("return", this.engine.acceptTextAgreement(engineCode));
	            case 1:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function acceptAgreement(_x) {
	        return _acceptAgreement.apply(this, arguments);
	      }
	      return acceptAgreement;
	    }()
	  }]);
	  return PickerText;
	}(PickerBase);
	function _renderHistory2() {
	  this.initHistory();
	  this.historyContainer = main_core.Tag.render(_templateObject2$8 || (_templateObject2$8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker-text_history\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.isToolingLoading ? _classPrivateMethodGet$9(this, _renderHistoryLoadingState, _renderHistoryLoadingState2).call(this) : this.history.render());
	  return this.historyContainer;
	}
	function _renderHistoryLoadingState2() {
	  return main_core.Tag.render(_templateObject3$3 || (_templateObject3$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker-text_history-loader\">", "</div>\n\t\t"])), main_core.Loc.getMessage('AI_JS_PICKER_HISTORY_LOADING'));
	}

	var _templateObject$e, _templateObject2$9, _templateObject3$4;
	function _classPrivateMethodInitSpec$a(obj, privateSet) { _checkPrivateRedeclaration$c(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$c(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$a(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _renderHistory$1 = /*#__PURE__*/new WeakSet();
	var _renderHistoryLoadingState$1 = /*#__PURE__*/new WeakSet();
	var PickerImage = /*#__PURE__*/function (_PickerBase) {
	  babelHelpers.inherits(PickerImage, _PickerBase);
	  function PickerImage() {
	    var _this;
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, PickerImage);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PickerImage).call(this, props));
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _renderHistoryLoadingState$1);
	    _classPrivateMethodInitSpec$a(babelHelpers.assertThisInitialized(_this), _renderHistory$1);
	    _this.pickerType = 'image';
	    _this.setEventNamespace('AI:PickerImage');
	    return _this;
	  }
	  babelHelpers.createClass(PickerImage, [{
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject$e || (_templateObject$e = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker-image\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.renderTextMessage(), _classPrivateMethodGet$a(this, _renderHistory$1, _renderHistory2$1).call(this));
	    }
	  }, {
	    key: "getTextMessageSubmitButtonIcon",
	    value: function getTextMessageSubmitButtonIcon() {
	      return TextMessageSubmitButtonIcon.BRUSH;
	    }
	  }, {
	    key: "initHistory",
	    value: function initHistory() {
	      var _this2 = this;
	      var generate = function generate(prompt) {
	        var _engine$code;
	        var engine = _this2.engines.find(function (e) {
	          return e.selected;
	        });
	        var engineCode = (_engine$code = engine === null || engine === void 0 ? void 0 : engine.code) !== null && _engine$code !== void 0 ? _engine$code : _this2.engines[0].code;
	        return _this2.onGenerate(prompt, engineCode);
	      };
	      this.history = new HistoryImage({
	        items: this.items,
	        capacity: this.capacity,
	        onGenerate: generate,
	        onSelect: this.onSelect
	      });
	    }
	  }, {
	    key: "getHint",
	    value: function getHint() {
	      if (main_core.Loc.getMessage('LANGUAGE_ID') !== 'en') {
	        return {
	          title: main_core.Loc.getMessage('AI_JS_PICKER_IMAGE_HINT_TITLE'),
	          text: main_core.Loc.getMessage('AI_JS_PICKER_IMAGE_HINT_TEXT')
	        };
	      }
	      return null;
	    }
	  }, {
	    key: "acceptAgreement",
	    value: function acceptAgreement(engineCode) {
	      return this.engine.acceptImageAgreement(engineCode);
	    }
	  }, {
	    key: "isResultUsed",
	    value: function isResultUsed() {
	      return this.isResultSelected;
	    }
	  }]);
	  return PickerImage;
	}(PickerBase);
	function _renderHistory2$1() {
	  this.initHistory();
	  this.historyContainer = main_core.Tag.render(_templateObject2$9 || (_templateObject2$9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker-image_history\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.isToolingLoading ? _classPrivateMethodGet$a(this, _renderHistoryLoadingState$1, _renderHistoryLoadingState2$1).call(this) : this.history.render());
	  return this.historyContainer;
	}
	function _renderHistoryLoadingState2$1() {
	  return main_core.Tag.render(_templateObject3$4 || (_templateObject3$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker-text_history-loader\">", "</div>\n\t\t"])), main_core.Loc.getMessage('AI_JS_PICKER_HISTORY_LOADING'));
	}

	var _templateObject$f;
	function _classPrivateFieldInitSpec$a(obj, privateMap, value) { _checkPrivateRedeclaration$d(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$d(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _button = /*#__PURE__*/new WeakMap();
	var _isShow = /*#__PURE__*/new WeakMap();
	var ScrollTopButton = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(ScrollTopButton, _Base);
	  function ScrollTopButton(props) {
	    var _this;
	    babelHelpers.classCallCheck(this, ScrollTopButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ScrollTopButton).call(this, props));
	    _classPrivateFieldInitSpec$a(babelHelpers.assertThisInitialized(_this), _button, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$a(babelHelpers.assertThisInitialized(_this), _isShow, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('AI:Picker:ScrollTopButton');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _button, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _isShow, true);
	    return _this;
	  }
	  babelHelpers.createClass(ScrollTopButton, [{
	    key: "render",
	    value: function render() {
	      var _this2 = this;
	      var icon = new ui_iconSet_api_core.Icon({
	        icon: ui_iconSet_api_core.Actions.CHEVRON_UP,
	        size: 26
	      });
	      babelHelpers.classPrivateFieldSet(this, _button, main_core.Tag.render(_templateObject$f || (_templateObject$f = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ai__picker_go-top-btn\">\n\t\t\t\t", "\n\t\t\t</button>\n\t\t"])), icon.render()));
	      main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _button), {
	        visibility: babelHelpers.classPrivateFieldGet(this, _isShow) ? '' : 'hidden'
	      });
	      main_core.bind(babelHelpers.classPrivateFieldGet(this, _button), 'click', function () {
	        _this2.emit('click');
	      });
	      return babelHelpers.classPrivateFieldGet(this, _button);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var _this3 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _isShow)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _isShow, true);
	      if (babelHelpers.classPrivateFieldGet(this, _button)) {
	        main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _button), 'visibility', null);
	        setTimeout(function () {
	          main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this3, _button), 'opacity', 1);
	        }, 10);
	      }
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      var _this4 = this;
	      if (!babelHelpers.classPrivateFieldGet(this, _isShow)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _isShow, false);
	      if (babelHelpers.classPrivateFieldGet(this, _button)) {
	        main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _button), 'opacity', 0);
	        main_core.bindOnce(babelHelpers.classPrivateFieldGet(this, _button), 'transitionend', function () {
	          main_core.Dom.style(babelHelpers.classPrivateFieldGet(_this4, _button), 'visibility', 'hidden');
	        });
	      }
	    }
	  }, {
	    key: "isShow",
	    value: function isShow() {
	      babelHelpers.classPrivateFieldSet(this, _isShow, true);
	    }
	  }]);
	  return ScrollTopButton;
	}(Base);

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$b(obj, privateSet) { _checkPrivateRedeclaration$e(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$b(obj, privateMap, value) { _checkPrivateRedeclaration$e(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$e(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$b(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _analyticLabel = /*#__PURE__*/new WeakMap();
	var _putOpenLabel = /*#__PURE__*/new WeakSet();
	var _putGenerateLabel = /*#__PURE__*/new WeakSet();
	var _putCopyLabel = /*#__PURE__*/new WeakSet();
	var _putPasteLabel = /*#__PURE__*/new WeakSet();
	var _putCancelLabel = /*#__PURE__*/new WeakSet();
	var _putLabel = /*#__PURE__*/new WeakSet();
	var PickerAnalytic = /*#__PURE__*/function () {
	  function PickerAnalytic(props) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, PickerAnalytic);
	    _classPrivateMethodInitSpec$b(this, _putLabel);
	    _classPrivateMethodInitSpec$b(this, _putCancelLabel);
	    _classPrivateMethodInitSpec$b(this, _putPasteLabel);
	    _classPrivateMethodInitSpec$b(this, _putCopyLabel);
	    _classPrivateMethodInitSpec$b(this, _putGenerateLabel);
	    _classPrivateMethodInitSpec$b(this, _putOpenLabel);
	    _classPrivateFieldInitSpec$b(this, _analyticLabel, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.defineProperty(this, "labels", Object.freeze({
	      open: function open() {
	        return _classPrivateMethodGet$b(_this, _putOpenLabel, _putOpenLabel2).call(_this);
	      },
	      generate: function generate(text) {
	        return _classPrivateMethodGet$b(_this, _putGenerateLabel, _putGenerateLabel2).call(_this, text);
	      },
	      copy: function copy() {
	        return _classPrivateMethodGet$b(_this, _putCopyLabel, _putCopyLabel2).call(_this);
	      },
	      paste: function paste() {
	        return _classPrivateMethodGet$b(_this, _putPasteLabel, _putPasteLabel2).call(_this);
	      },
	      cancel: function cancel() {
	        return _classPrivateMethodGet$b(_this, _putCancelLabel, _putCancelLabel2).call(_this);
	      }
	    }));
	    babelHelpers.classPrivateFieldSet(this, _analyticLabel, props.analyticLabel);
	  }
	  babelHelpers.createClass(PickerAnalytic, [{
	    key: "getAnalyticLabel",
	    value: function getAnalyticLabel() {
	      return babelHelpers.classPrivateFieldGet(this, _analyticLabel);
	    }
	  }, {
	    key: "setAnalyticLabel",
	    value: function setAnalyticLabel(analyticLabel) {
	      babelHelpers.classPrivateFieldSet(this, _analyticLabel, analyticLabel);
	    }
	  }]);
	  return PickerAnalytic;
	}();
	function _putOpenLabel2() {
	  _classPrivateMethodGet$b(this, _putLabel, _putLabel2).call(this, 'open');
	}
	function _putGenerateLabel2(text) {
	  var croppedText = text ? text.slice(0, 50) : '';
	  _classPrivateMethodGet$b(this, _putLabel, _putLabel2).call(this, 'generate', {
	    text: croppedText
	  });
	}
	function _putCopyLabel2() {
	  return _classPrivateMethodGet$b(this, _putLabel, _putLabel2).call(this, 'copy');
	}
	function _putPasteLabel2() {
	  return _classPrivateMethodGet$b(this, _putLabel, _putLabel2).call(this, 'past');
	}
	function _putCancelLabel2() {
	  return _classPrivateMethodGet$b(this, _putLabel, _putLabel2).call(this, 'cancel');
	}
	function _putLabel2(action) {
	  var params = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	  var url = '/bitrix/images/1.gif';
	  var timestamp = Date.now();
	  var data = _objectSpread({
	    module: 'ai',
	    context: babelHelpers.classPrivateFieldGet(this, _analyticLabel),
	    action: "picker.".concat(action),
	    ts: timestamp
	  }, params);
	  var preparedData = main_core.ajax.prepareData(data);
	  if (preparedData) {
	    url += (url.includes('?') ? '&' : '?') + preparedData;
	  }
	  main_core.ajax({
	    method: 'GET',
	    url: url
	  });
	}

	var _templateObject$g, _templateObject2$a, _templateObject3$5;
	function _regeneratorRuntime$4() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$4 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec$c(obj, privateSet) { _checkPrivateRedeclaration$f(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$c(obj, privateMap, value) { _checkPrivateRedeclaration$f(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$f(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$c(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _startMessage = /*#__PURE__*/new WeakMap();
	var _onSelectCallback = /*#__PURE__*/new WeakMap();
	var _onTariffRestriction = /*#__PURE__*/new WeakMap();
	var _engine = /*#__PURE__*/new WeakMap();
	var _popups = /*#__PURE__*/new WeakMap();
	var _currentPopup = /*#__PURE__*/new WeakMap();
	var _popupContainer = /*#__PURE__*/new WeakMap();
	var _contentWrapper = /*#__PURE__*/new WeakMap();
	var _scrollTopButton = /*#__PURE__*/new WeakMap();
	var _saveImages = /*#__PURE__*/new WeakMap();
	var _engines = /*#__PURE__*/new WeakMap();
	var _promptsHistory = /*#__PURE__*/new WeakMap();
	var _articleCode$1 = /*#__PURE__*/new WeakMap();
	var _analytic = /*#__PURE__*/new WeakMap();
	var _analyticLabel$1 = /*#__PURE__*/new WeakMap();
	var _pickerImage = /*#__PURE__*/new WeakMap();
	var _pickerText = /*#__PURE__*/new WeakMap();
	var _verticalMargin = /*#__PURE__*/new WeakMap();
	var _onSelect$2 = /*#__PURE__*/new WeakSet();
	var _show = /*#__PURE__*/new WeakSet();
	var _getScrollWidth = /*#__PURE__*/new WeakSet();
	var _fixOverlayFreez = /*#__PURE__*/new WeakSet();
	var _registerPopup = /*#__PURE__*/new WeakSet();
	var _sendCancelAnalyticLabelIfNeeded = /*#__PURE__*/new WeakSet();
	var _adjustPopupPosition = /*#__PURE__*/new WeakSet();
	var _getPopupPosition = /*#__PURE__*/new WeakSet();
	var _getPopupMaxHeight = /*#__PURE__*/new WeakSet();
	var _setContent = /*#__PURE__*/new WeakSet();
	var _getContentMaxHeight = /*#__PURE__*/new WeakSet();
	var _renderPopupContent = /*#__PURE__*/new WeakSet();
	var _renderPopupHeader = /*#__PURE__*/new WeakSet();
	var _initPickerText = /*#__PURE__*/new WeakSet();
	var _initPickerImage = /*#__PURE__*/new WeakSet();
	var _handleSelect = /*#__PURE__*/new WeakSet();
	var _handleCopy = /*#__PURE__*/new WeakSet();
	var _handleImageSelect = /*#__PURE__*/new WeakSet();
	var _getCSection = /*#__PURE__*/new WeakSet();
	var Picker = /*#__PURE__*/function () {
	  function Picker(_options) {
	    babelHelpers.classCallCheck(this, Picker);
	    _classPrivateMethodInitSpec$c(this, _getCSection);
	    _classPrivateMethodInitSpec$c(this, _handleImageSelect);
	    _classPrivateMethodInitSpec$c(this, _handleCopy);
	    _classPrivateMethodInitSpec$c(this, _handleSelect);
	    _classPrivateMethodInitSpec$c(this, _initPickerImage);
	    _classPrivateMethodInitSpec$c(this, _initPickerText);
	    _classPrivateMethodInitSpec$c(this, _renderPopupHeader);
	    _classPrivateMethodInitSpec$c(this, _renderPopupContent);
	    _classPrivateMethodInitSpec$c(this, _getContentMaxHeight);
	    _classPrivateMethodInitSpec$c(this, _setContent);
	    _classPrivateMethodInitSpec$c(this, _getPopupMaxHeight);
	    _classPrivateMethodInitSpec$c(this, _getPopupPosition);
	    _classPrivateMethodInitSpec$c(this, _adjustPopupPosition);
	    _classPrivateMethodInitSpec$c(this, _sendCancelAnalyticLabelIfNeeded);
	    _classPrivateMethodInitSpec$c(this, _registerPopup);
	    _classPrivateMethodInitSpec$c(this, _fixOverlayFreez);
	    _classPrivateMethodInitSpec$c(this, _getScrollWidth);
	    _classPrivateMethodInitSpec$c(this, _show);
	    _classPrivateMethodInitSpec$c(this, _onSelect$2);
	    _classPrivateFieldInitSpec$c(this, _startMessage, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _onSelectCallback, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _onTariffRestriction, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _engine, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _popups, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _currentPopup, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _popupContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _contentWrapper, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _scrollTopButton, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _saveImages, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _engines, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _promptsHistory, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _articleCode$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _analytic, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _analyticLabel$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _pickerImage, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _pickerText, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$c(this, _verticalMargin, {
	      writable: true,
	      value: void 0
	    });
	    // super(options);
	    babelHelpers.classPrivateFieldSet(this, _engine, new ai_engine.Engine());
	    babelHelpers.classPrivateFieldSet(this, _popups, new Map());
	    babelHelpers.classPrivateFieldSet(this, _popupContainer, _options.popupContainer || document.body);
	    babelHelpers.classPrivateFieldSet(this, _startMessage, _options.startMessage);
	    babelHelpers.classPrivateFieldSet(this, _onSelectCallback, _options.onSelect);
	    babelHelpers.classPrivateFieldSet(this, _onTariffRestriction, _options.onTariffRestriction);
	    babelHelpers.classPrivateFieldSet(this, _articleCode$1, null);
	    babelHelpers.classPrivateFieldSet(this, _analyticLabel$1, _options.analyticLabel);
	    babelHelpers.classPrivateFieldSet(this, _saveImages, _options.saveImages === true);
	    babelHelpers.classPrivateFieldSet(this, _engines, {});
	    babelHelpers.classPrivateFieldSet(this, _promptsHistory, {});
	    babelHelpers.classPrivateFieldSet(this, _verticalMargin, 25);
	    babelHelpers.classPrivateFieldSet(this, _analytic, new PickerAnalytic({
	      analyticLabel: babelHelpers.classPrivateFieldGet(this, _analyticLabel$1)
	    }));
	    babelHelpers.classPrivateFieldGet(this, _engine).setModuleId(_options.moduleId).setContextId(_options.contextId).setHistoryState(_options.history);
	    babelHelpers.classPrivateFieldSet(this, _pickerImage, null);
	    babelHelpers.classPrivateFieldSet(this, _pickerText, null);
	  }
	  babelHelpers.createClass(Picker, [{
	    key: "initTooling",
	    value: function () {
	      var _initTooling = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$4().mark(function _callee() {
	        var res;
	        return _regeneratorRuntime$4().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              _context.next = 2;
	              return babelHelpers.classPrivateFieldGet(this, _engine).getTooling('text');
	            case 2:
	              res = _context.sent;
	              babelHelpers.classPrivateFieldGet(this, _engines).text = res.data.engines;
	              babelHelpers.classPrivateFieldGet(this, _promptsHistory).text = res.data.history;
	              return _context.abrupt("return", true);
	            case 6:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function initTooling() {
	        return _initTooling.apply(this, arguments);
	      }
	      return initTooling;
	    }()
	    /**
	     * Sets language space. For different interface may be used different phrases.
	     * See all bunches of phrases in lang/config.php.
	     *
	     * @param {LangSpace} spaceCode
	     * @return {Picker}
	     */
	  }, {
	    key: "setLangSpace",
	    value: function setLangSpace(spaceCode) {
	      Loc.getInstance().setSpace(spaceCode);
	      return this;
	    }
	  }, {
	    key: "setSelectCallback",
	    value: function setSelectCallback(callback) {
	      babelHelpers.classPrivateFieldSet(this, _onSelectCallback, callback);
	    }
	  }, {
	    key: "setEngineParameters",
	    value: function setEngineParameters(parameters) {
	      if (babelHelpers.classPrivateFieldGet(this, _engine)) {
	        babelHelpers.classPrivateFieldGet(this, _engine).setParameters(parameters);
	        if (babelHelpers.classPrivateFieldGet(this, _pickerImage)) {
	          babelHelpers.classPrivateFieldGet(this, _pickerImage).setEngineParameters(parameters);
	        }
	        if (babelHelpers.classPrivateFieldGet(this, _pickerText)) {
	          babelHelpers.classPrivateFieldGet(this, _pickerText).setEngineParameters(parameters);
	        }
	      }
	    }
	  }, {
	    key: "setStartMessage",
	    value: function setStartMessage(message) {
	      babelHelpers.classPrivateFieldSet(this, _startMessage, main_core.Type.isString(message) ? message : babelHelpers.classPrivateFieldGet(this, _startMessage));
	    }
	    /**
	     * Shows popup for text completion.
	     */
	  }, {
	    key: "text",
	    value: function text() {
	      babelHelpers.classPrivateFieldGet(this, _analytic).labels.open();
	      babelHelpers.classPrivateFieldSet(this, _articleCode$1, 17587362);
	      var popup = babelHelpers.classPrivateFieldGet(this, _popups).get('text');
	      if (babelHelpers.classPrivateFieldGet(this, _pickerText)) {
	        var scroll = babelHelpers.classPrivateFieldGet(this, _popupContainer) === document.body ? window.pageYOffset : 0;
	        popup.setBindElement({
	          left: babelHelpers.classPrivateFieldGet(this, _popupContainer).offsetWidth - popup.getWidth() - 25,
	          top: 25 + scroll
	        });
	        popup.adjustPosition();
	        babelHelpers.classPrivateFieldSet(this, _currentPopup, popup);
	        babelHelpers.classPrivateFieldGet(this, _pickerText).resetResultUsedFlag();
	        _classPrivateMethodGet$c(this, _show, _show2).call(this);
	      } else {
	        _classPrivateMethodGet$c(this, _initPickerText, _initPickerText2).call(this);
	        _classPrivateMethodGet$c(this, _registerPopup, _registerPopup2).call(this, 'text', babelHelpers.classPrivateFieldGet(this, _pickerText).render({
	          textMessageText: babelHelpers.classPrivateFieldGet(this, _startMessage)
	        }), {
	          contentClassname: ''
	        });
	        babelHelpers.classPrivateFieldGet(this, _pickerText).resetResultUsedFlag();
	        _classPrivateMethodGet$c(this, _show, _show2).call(this);
	      }
	    }
	    /**
	     * Shows popup for image completion.
	     */
	  }, {
	    key: "image",
	    value: function () {
	      var _image = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$4().mark(function _callee2() {
	        var isRestrictedByEula, Feature, popup, scroll;
	        return _regeneratorRuntime$4().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              isRestrictedByEula = main_core.Extension.getSettings('ai.picker').get('isRestrictedByEula');
	              Feature = null;
	              if (!isRestrictedByEula) {
	                _context2.next = 16;
	                break;
	              }
	              _context2.next = 5;
	              return main_core.Runtime.loadExtension('bitrix24.license.feature');
	            case 5:
	              Feature = _context2.sent;
	              _context2.prev = 6;
	              _context2.next = 9;
	              return Feature.Feature.checkEulaRestrictions('ai_available_by_version');
	            case 9:
	              _context2.next = 14;
	              break;
	            case 11:
	              _context2.prev = 11;
	              _context2.t0 = _context2["catch"](6);
	              if (main_core.Type.isFunction(_context2.t0 === null || _context2.t0 === void 0 ? void 0 : _context2.t0.callback)) {
	                _context2.t0 === null || _context2.t0 === void 0 ? void 0 : _context2.t0.callback();
	              }
	            case 14:
	              _context2.next = 21;
	              break;
	            case 16:
	              babelHelpers.classPrivateFieldGet(this, _analytic).labels.open();
	              babelHelpers.classPrivateFieldSet(this, _articleCode$1, 17586054);
	              if (babelHelpers.classPrivateFieldGet(this, _pickerImage)) {
	                popup = babelHelpers.classPrivateFieldGet(this, _popups).get('image');
	                scroll = babelHelpers.classPrivateFieldGet(this, _popupContainer) === document.body ? window.pageYOffset : 0;
	                babelHelpers.classPrivateFieldSet(this, _currentPopup, popup);
	                popup.setBindElement({
	                  left: babelHelpers.classPrivateFieldGet(this, _popupContainer).offsetWidth - popup.getWidth() - 25,
	                  top: 25 + scroll
	                });
	                popup.adjustPosition();
	              } else {
	                _classPrivateMethodGet$c(this, _initPickerImage, _initPickerImage2).call(this);
	                _classPrivateMethodGet$c(this, _registerPopup, _registerPopup2).call(this, 'image', babelHelpers.classPrivateFieldGet(this, _pickerImage).render(), {
	                  width: 550,
	                  contentClassname: '--image',
	                  headerClassname: '--image'
	                });
	              }
	              babelHelpers.classPrivateFieldGet(this, _pickerImage).resetResultUsedFlag();
	              _classPrivateMethodGet$c(this, _show, _show2).call(this);
	            case 21:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2, this, [[6, 11]]);
	      }));
	      function image() {
	        return _image.apply(this, arguments);
	      }
	      return image;
	    }()
	    /**
	     * Called when user want to use HistoryItem somewhere outside.
	     * @param {HistoryItem} item
	     * @param {Promise} promise
	     */
	  }]);
	  return Picker;
	}();
	function _onSelect2(item, promise) {
	  if (main_core.Type.isFunction(babelHelpers.classPrivateFieldGet(this, _onSelectCallback))) {
	    babelHelpers.classPrivateFieldGet(this, _onSelectCallback).call(this, item, promise);
	  }
	  babelHelpers.classPrivateFieldGet(this, _currentPopup).close();
	}
	function _show2() {
	  babelHelpers.classPrivateFieldGet(this, _currentPopup).show();
	}
	function _getScrollWidth2() {
	  var div = main_core.Tag.render(_templateObject$g || (_templateObject$g = babelHelpers.taggedTemplateLiteral(["<div style=\"overflow-y: scroll; width: 50px; height: 50px; opacity: 0; pointer-events: none; position: absolute;\"></div>"])));
	  main_core.Dom.append(div, document.body);
	  var scrollWidth = div.offsetWidth - div.clientWidth;
	  main_core.Dom.remove(div);
	  return scrollWidth;
	}
	function _fixOverlayFreez2(popupId) {
	  if (!popupId) {
	    return;
	  }
	  var overlayNode = babelHelpers.classPrivateFieldGet(this, _popups).get(popupId).overlay.element;
	  main_core.Dom.style(overlayNode, 'padding-right', "".concat(_classPrivateMethodGet$c(this, _getScrollWidth, _getScrollWidth2).call(this), "px"));
	}
	function _registerPopup2(popupId, content, options) {
	  var _this = this;
	  var popupWidth = (options === null || options === void 0 ? void 0 : options.width) || 450;
	  var contentClassname = options.contentClassname || '';
	  var headerClassname = options.headerClassname || '';
	  var adjustPosition = _classPrivateMethodGet$c(this, _adjustPopupPosition, _adjustPopupPosition2).bind(this);
	  if (!babelHelpers.classPrivateFieldGet(this, _popups).has(popupId)) {
	    babelHelpers.classPrivateFieldGet(this, _popups).set(popupId, new main_popup.Popup({
	      bindElement: _classPrivateMethodGet$c(this, _getPopupPosition, _getPopupPosition2).call(this, popupWidth),
	      className: 'ai__picker-popup',
	      autoHide: true,
	      closeByEsc: false,
	      width: popupWidth,
	      height: _classPrivateMethodGet$c(this, _getPopupMaxHeight, _getPopupMaxHeight2).call(this),
	      disableScroll: true,
	      padding: 0,
	      borderRadius: '12px',
	      contentBorderRadius: '12px',
	      overlay: {
	        backgroundColor: '#fff',
	        opacity: 50
	      },
	      animation: {
	        showClassName: 'ai__picker-popup-show',
	        closeClassName: 'ai__picker-popup-hide',
	        closeAnimationType: 'animation'
	      },
	      targetContainer: babelHelpers.classPrivateFieldGet(this, _popupContainer),
	      events: {
	        onPopupShow: function onPopupShow() {
	          _classPrivateMethodGet$c(_this, _fixOverlayFreez, _fixOverlayFreez2).call(_this, popupId);
	          main_core.Dom.style(document.body, 'overflow-x', 'hidden');
	        },
	        onPopupAfterClose: function onPopupAfterClose() {
	          main_core.Dom.style(document.body, 'overflow-x', null);
	        },
	        onAfterShow: function onAfterShow() {
	          main_core.bind(window, 'resize', adjustPosition);
	        },
	        onPopupClose: function onPopupClose() {
	          _classPrivateMethodGet$c(_this, _sendCancelAnalyticLabelIfNeeded, _sendCancelAnalyticLabelIfNeeded2).call(_this);
	          if (babelHelpers.classPrivateFieldGet(_this, _pickerImage)) {
	            babelHelpers.classPrivateFieldGet(_this, _pickerImage).closeAllMenus();
	          }
	          if (babelHelpers.classPrivateFieldGet(_this, _pickerText)) {
	            babelHelpers.classPrivateFieldGet(_this, _pickerText).closeAllMenus();
	          }
	          main_core.unbind(window, 'resize', adjustPosition);
	        }
	      }
	    }));
	  }
	  babelHelpers.classPrivateFieldSet(this, _currentPopup, babelHelpers.classPrivateFieldGet(this, _popups).get(popupId));
	  if (babelHelpers.classPrivateFieldGet(this, _currentPopup).isShown()) {
	    babelHelpers.classPrivateFieldGet(this, _currentPopup).close();
	  }
	  _classPrivateMethodGet$c(this, _setContent, _setContent2).call(this, babelHelpers.classPrivateFieldGet(this, _currentPopup), _classPrivateMethodGet$c(this, _renderPopupContent, _renderPopupContent2).call(this, content, {
	    contentClassname: contentClassname,
	    headerClassname: headerClassname
	  }));
	}
	function _sendCancelAnalyticLabelIfNeeded2() {
	  if (babelHelpers.classPrivateFieldGet(this, _pickerText) && !babelHelpers.classPrivateFieldGet(this, _pickerText).isResultUsed()) {
	    babelHelpers.classPrivateFieldGet(this, _analytic).labels.cancel();
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _pickerImage) && !babelHelpers.classPrivateFieldGet(this, _pickerImage).isResultUsed()) {
	    babelHelpers.classPrivateFieldGet(this, _analytic).labels.cancel();
	  }
	}
	function _adjustPopupPosition2() {
	  babelHelpers.classPrivateFieldGet(this, _currentPopup).setBindElement(_classPrivateMethodGet$c(this, _getPopupPosition, _getPopupPosition2).call(this));
	  babelHelpers.classPrivateFieldGet(this, _currentPopup).setHeight(_classPrivateMethodGet$c(this, _getPopupMaxHeight, _getPopupMaxHeight2).call(this));
	  babelHelpers.classPrivateFieldGet(this, _currentPopup).adjustPosition();
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _contentWrapper), 'height', "".concat(_classPrivateMethodGet$c(this, _getContentMaxHeight, _getContentMaxHeight2).call(this), "px"));
	}
	function _getPopupPosition2(popupWidthParam) {
	  var scroll = babelHelpers.classPrivateFieldGet(this, _popupContainer) === document.body ? window.pageYOffset : 0;
	  var popupWidth = popupWidthParam || babelHelpers.classPrivateFieldGet(this, _currentPopup).getWidth();
	  return {
	    left: babelHelpers.classPrivateFieldGet(this, _popupContainer).offsetWidth - popupWidth - 25,
	    top: 25 + scroll
	  };
	}
	function _getPopupMaxHeight2() {
	  var height = babelHelpers.classPrivateFieldGet(this, _popupContainer).clientHeight > window.innerHeight ? window.innerHeight : babelHelpers.classPrivateFieldGet(this, _popupContainer).clientHeight;
	  return height - babelHelpers.classPrivateFieldGet(this, _verticalMargin) * 2;
	}
	function _setContent2(popup, content) {
	  popup.setContent(content);
	}
	function _getContentMaxHeight2() {
	  var headerHeight = 94;
	  return babelHelpers.classPrivateFieldGet(this, _currentPopup).getHeight() - headerHeight;
	}
	function _renderPopupContent2(contentElem, options) {
	  var _this2 = this;
	  var contentStyle = "height: ".concat(_classPrivateMethodGet$c(this, _getContentMaxHeight, _getContentMaxHeight2).call(this), "px");
	  var contentClassname = (options === null || options === void 0 ? void 0 : options.contentClassname) || '';
	  var headerClassname = (options === null || options === void 0 ? void 0 : options.headerClassname) || '';
	  babelHelpers.classPrivateFieldSet(this, _contentWrapper, main_core.Tag.render(_templateObject2$a || (_templateObject2$a = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"ai__picker_content ", " ", "\"\n\t\t\t\tstyle=\"", "\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), main_core.Browser.isMac() ? '--is-mac-os' : '', contentClassname, contentStyle, contentElem));
	  main_core.bind(babelHelpers.classPrivateFieldGet(this, _contentWrapper), 'scroll', function () {
	    if (babelHelpers.classPrivateFieldGet(_this2, _contentWrapper).scrollTop > 200) {
	      babelHelpers.classPrivateFieldGet(_this2, _scrollTopButton).show();
	    } else {
	      babelHelpers.classPrivateFieldGet(_this2, _scrollTopButton).hide();
	    }
	    if (babelHelpers.classPrivateFieldGet(_this2, _pickerImage)) {
	      babelHelpers.classPrivateFieldGet(_this2, _pickerImage).closeAllMenus();
	    }
	    if (babelHelpers.classPrivateFieldGet(_this2, _pickerText)) {
	      babelHelpers.classPrivateFieldGet(_this2, _pickerText).closeAllMenus();
	    }
	  });
	  babelHelpers.classPrivateFieldSet(this, _scrollTopButton, new ScrollTopButton());
	  babelHelpers.classPrivateFieldGet(this, _scrollTopButton).hide();
	  babelHelpers.classPrivateFieldGet(this, _scrollTopButton).subscribe('click', function () {
	    babelHelpers.classPrivateFieldGet(_this2, _contentWrapper).scrollTo({
	      top: 0
	    });
	  });
	  return main_core.Tag.render(_templateObject3$5 || (_templateObject3$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__picker\">\n\t\t\t\t<div>\n\t\t\t\t\t<div class=\"ai__picker-header\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$c(this, _renderPopupHeader, _renderPopupHeader2).call(this, {
	    className: headerClassname
	  }), babelHelpers.classPrivateFieldGet(this, _contentWrapper), babelHelpers.classPrivateFieldGet(this, _scrollTopButton).render());
	}
	function _renderPopupHeader2() {
	  var _this3 = this;
	  var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	  var header = new UI.Header({
	    articleCode: babelHelpers.classPrivateFieldGet(this, _articleCode$1),
	    className: options.className
	  });
	  header.subscribe('click-close-icon', function () {
	    babelHelpers.classPrivateFieldGet(_this3, _currentPopup).close();
	  });
	  return header.render();
	}
	function _initPickerText2() {
	  return _initPickerText3.apply(this, arguments);
	}
	function _initPickerText3() {
	  _initPickerText3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$4().mark(function _callee3() {
	    var _this5 = this;
	    var generate;
	    return _regeneratorRuntime$4().wrap(function _callee3$(_context3) {
	      while (1) switch (_context3.prev = _context3.next) {
	        case 0:
	          if (!babelHelpers.classPrivateFieldGet(this, _pickerText)) {
	            _context3.next = 2;
	            break;
	          }
	          return _context3.abrupt("return");
	        case 2:
	          babelHelpers.classPrivateFieldSet(this, _pickerText, new PickerText({
	            onTariffRestriction: babelHelpers.classPrivateFieldGet(this, _onTariffRestriction),
	            onSelect: _classPrivateMethodGet$c(this, _handleSelect, _handleSelect2).bind(this),
	            onCopy: _classPrivateMethodGet$c(this, _handleCopy, _handleCopy2).bind(this)
	          }));
	          generate = function generate(prompt, engineCode) {
	            babelHelpers.classPrivateFieldGet(_this5, _engine).setPayload(new ai_payload_textpayload.Text({
	              engineCode: engineCode,
	              prompt: prompt
	            }));
	            babelHelpers.classPrivateFieldGet(_this5, _analytic).labels.generate(prompt);
	            return babelHelpers.classPrivateFieldGet(_this5, _engine).textCompletions();
	          };
	          babelHelpers.classPrivateFieldGet(this, _pickerText).setOnGenerate(generate);
	          babelHelpers.classPrivateFieldGet(this, _pickerText).setStartMessage(babelHelpers.classPrivateFieldGet(this, _startMessage));
	          babelHelpers.classPrivateFieldGet(this, _pickerText).setEngine(babelHelpers.classPrivateFieldGet(this, _engine));
	          babelHelpers.classPrivateFieldGet(this, _pickerText).setContext(babelHelpers.classPrivateFieldGet(this, _popupContainer));
	          babelHelpers.classPrivateFieldGet(this, _pickerText).initTooling('text');
	          babelHelpers.classPrivateFieldGet(this, _pickerText).subscribe('select', _classPrivateMethodGet$c(this, _handleSelect, _handleSelect2).bind(this));
	        case 10:
	        case "end":
	          return _context3.stop();
	      }
	    }, _callee3, this);
	  }));
	  return _initPickerText3.apply(this, arguments);
	}
	function _initPickerImage2() {
	  var _this4 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _pickerImage)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _pickerImage, new PickerImage({
	    onTariffRestriction: babelHelpers.classPrivateFieldGet(this, _onTariffRestriction),
	    onSelect: _classPrivateMethodGet$c(this, _handleImageSelect, _handleImageSelect2).bind(this),
	    context: babelHelpers.classPrivateFieldGet(this, _popupContainer)
	  }));
	  var generate = function generate(prompt, engineCode) {
	    babelHelpers.classPrivateFieldGet(_this4, _engine).setPayload(new ai_payload_textpayload.Text({
	      prompt: prompt,
	      engineCode: engineCode
	    }));
	    babelHelpers.classPrivateFieldGet(_this4, _analytic).labels.generate(prompt);
	    babelHelpers.classPrivateFieldGet(_this4, _engine).setAnalyticParameters({
	      type: 'create_image',
	      c_section: _classPrivateMethodGet$c(_this4, _getCSection, _getCSection2).call(_this4)
	    });
	    return babelHelpers.classPrivateFieldGet(_this4, _engine).imageCompletions();
	  };
	  babelHelpers.classPrivateFieldGet(this, _pickerImage).setOnGenerate(generate);
	  babelHelpers.classPrivateFieldGet(this, _pickerImage).setStartMessage(babelHelpers.classPrivateFieldGet(this, _startMessage));
	  babelHelpers.classPrivateFieldGet(this, _pickerImage).setEngine(babelHelpers.classPrivateFieldGet(this, _engine));
	  babelHelpers.classPrivateFieldGet(this, _pickerImage).setContext(babelHelpers.classPrivateFieldGet(this, _popupContainer));
	  babelHelpers.classPrivateFieldGet(this, _pickerImage).initTooling('image');
	  babelHelpers.classPrivateFieldGet(this, _pickerImage).subscribe('select', _classPrivateMethodGet$c(this, _handleSelect, _handleSelect2).bind(this));
	}
	function _handleSelect2(item) {
	  babelHelpers.classPrivateFieldGet(this, _analytic).labels.paste();
	  _classPrivateMethodGet$c(this, _onSelect$2, _onSelect2).call(this, item);
	}
	function _handleCopy2(item) {
	  BX.clipboard.copy(item.data);
	  babelHelpers.classPrivateFieldGet(this, _analytic).labels.copy();
	}
	function _handleImageSelect2(_x) {
	  return _handleImageSelect3.apply(this, arguments);
	}
	function _handleImageSelect3() {
	  _handleImageSelect3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$4().mark(function _callee4(pictureUrl) {
	    var _this6 = this;
	    var promise;
	    return _regeneratorRuntime$4().wrap(function _callee4$(_context4) {
	      while (1) switch (_context4.prev = _context4.next) {
	        case 0:
	          babelHelpers.classPrivateFieldGet(this, _analytic).labels.paste();
	          if (babelHelpers.classPrivateFieldGet(this, _saveImages)) {
	            promise = new Promise(function (resolve, reject) {
	              babelHelpers.classPrivateFieldGet(_this6, _engine).saveImage(pictureUrl).then(function (res) {
	                resolve(res.data);
	              })["catch"](function (err) {
	                reject(err);
	              });
	            });
	            _classPrivateMethodGet$c(this, _onSelect$2, _onSelect2).call(this, pictureUrl, promise);
	          } else {
	            _classPrivateMethodGet$c(this, _onSelect$2, _onSelect2).call(this, pictureUrl);
	          }
	        case 2:
	        case "end":
	          return _context4.stop();
	      }
	    }, _callee4, this);
	  }));
	  return _handleImageSelect3.apply(this, arguments);
	}
	function _getCSection2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _analyticLabel$1)) {
	    return '';
	  }
	  return babelHelpers.classPrivateFieldGet(this, _analyticLabel$1).split('_').map(function (word) {
	    return word[0].toUpperCase() + word.slice(1);
	  }).join('');
	}
	babelHelpers.defineProperty(Picker, "LangSpace", {
	  text: 'text',
	  image: 'image'
	});

	exports.Picker = Picker;

}((this.BX.AI = this.BX.AI || {}),BX.AI.Payload,BX,BX,BX,BX.UI,BX.Event,BX.Main,BX,BX,BX.AI,BX.AI,BX.AI,BX.UI.IconSet,BX));
//# sourceMappingURL=index.bundle.js.map
