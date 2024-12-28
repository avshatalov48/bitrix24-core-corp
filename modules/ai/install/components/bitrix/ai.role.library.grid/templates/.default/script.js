/* eslint-disable */
this.BX = this.BX || {};
this.BX.AI = this.BX.AI || {};
this.BX.AI.ShareRole = this.BX.AI.ShareRole || {};
(function (exports,main_core_events,main_popup,main_loader,main_core,ui_analytics) {
	'use strict';

	var ListRenderer = /*#__PURE__*/function () {
	  function ListRenderer() {
	    babelHelpers.classCallCheck(this, ListRenderer);
	  }
	  babelHelpers.createClass(ListRenderer, [{
	    key: "render",
	    value: function render() {}
	  }]);
	  return ListRenderer;
	}();

	var _templateObject;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function showNotification(_x) {
	  return _showNotification.apply(this, arguments);
	}
	function _showNotification() {
	  _showNotification = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(content) {
	    return _regeneratorRuntime().wrap(function _callee$(_context) {
	      while (1) switch (_context.prev = _context.next) {
	        case 0:
	          main_core.Runtime.loadExtension('ui.notification').then(function (_ref) {
	            var BX = _ref.BX;
	            BX.UI.Notification.Center.notify({
	              content: content
	            });
	          })["catch"](function () {
	            if (main_core.Type.isElementNode(content)) {
	              // eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-dialogs,no-alert
	              alert(content.innerText);
	            } else {
	              // eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-dialogs,no-alert
	              alert(content);
	            }
	          });
	        case 1:
	        case "end":
	          return _context.stop();
	      }
	    }, _callee);
	  }));
	  return _showNotification.apply(this, arguments);
	}
	function highlightText(text, searchTerm) {
	  if (!searchTerm || !text) {
	    return text;
	  }
	  var lowerSearchTerm = searchTerm.toLowerCase();
	  var regex = new RegExp(lowerSearchTerm, 'gi');
	  return text.replace(regex, function (match) {
	    return "<mark>".concat(match, "</mark>");
	  });
	}
	function wrapTextToHtmlWithWordBreak(text) {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<span style=\"word-break: break-word;\">", "</span>"])), text);
	}

	var _templateObject$1, _templateObject2, _templateObject3, _templateObject4;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _renderShareItemImg = /*#__PURE__*/new WeakSet();
	var _renderShareItemInitials = /*#__PURE__*/new WeakSet();
	var SharesListRenderer = /*#__PURE__*/function (_ListRenderer) {
	  babelHelpers.inherits(SharesListRenderer, _ListRenderer);
	  function SharesListRenderer() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, SharesListRenderer);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(SharesListRenderer)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderShareItemInitials);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderShareItemImg);
	    return _this;
	  }
	  babelHelpers.createClass(SharesListRenderer, [{
	    key: "render",
	    value: function render(sharesList, searchValue) {
	      var _this2 = this;
	      var search = searchValue || null;
	      var itemsElements = sharesList.map(function (item) {
	        var encodedName = main_core.Text.encode(item.name);
	        var highlightedName = highlightText(encodedName, search);
	        return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<li class=\"ai__role-library-grid-shares-popup_shares-list-item\">\n\t\t\t\t\t<div class=\"ai__role-library-grid-shares-popup_shares-list-item-avatar\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ai__role-library-grid-shares-popup_shares-list-item-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</li>\n\t\t\t"])), _classPrivateMethodGet(_this2, _renderShareItemImg, _renderShareItemImg2).call(_this2, item), highlightedName);
	      });
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<ul class=\"ai__role-library-grid-shares-popup_shares-list\">", "</ul>"])), itemsElements);
	    }
	  }]);
	  return SharesListRenderer;
	}(ListRenderer);
	function _renderShareItemImg2(shareItem) {
	  if (main_core.Type.isStringFilled(shareItem.img)) {
	    return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<img src=\"", "\" alt=\"", "\" />"])), shareItem.img, shareItem.name);
	  }
	  return _classPrivateMethodGet(this, _renderShareItemInitials, _renderShareItemInitials2).call(this, shareItem.name);
	}
	function _renderShareItemInitials2(title) {
	  if (!title) {
	    return '';
	  }
	  var initials = title.split(' ').slice(0, 2).map(function (titleWord) {
	    return titleWord[0].toUpperCase();
	  }).join('');
	  return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<span class=\"ai__role-library-grid-shares-popup_shares-list-item-initials\">", "</span>"])), initials);
	}

	var _templateObject$2, _templateObject2$1;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _bindElement = /*#__PURE__*/new WeakMap();
	var _popupContent = /*#__PURE__*/new WeakMap();
	var _isLoading = /*#__PURE__*/new WeakMap();
	var _popup = /*#__PURE__*/new WeakMap();
	var _list = /*#__PURE__*/new WeakMap();
	var _listRenderer = /*#__PURE__*/new WeakMap();
	var _events = /*#__PURE__*/new WeakMap();
	var _useSearch = /*#__PURE__*/new WeakMap();
	var _searchValue = /*#__PURE__*/new WeakMap();
	var _filter = /*#__PURE__*/new WeakMap();
	var _initPopup = /*#__PURE__*/new WeakSet();
	var _renderPopupContent = /*#__PURE__*/new WeakSet();
	var _renderList = /*#__PURE__*/new WeakSet();
	var _updateList = /*#__PURE__*/new WeakSet();
	var _renderSearch = /*#__PURE__*/new WeakSet();
	var PopupWithLoader = /*#__PURE__*/function () {
	  function PopupWithLoader(options) {
	    babelHelpers.classCallCheck(this, PopupWithLoader);
	    _classPrivateMethodInitSpec$1(this, _renderSearch);
	    _classPrivateMethodInitSpec$1(this, _updateList);
	    _classPrivateMethodInitSpec$1(this, _renderList);
	    _classPrivateMethodInitSpec$1(this, _renderPopupContent);
	    _classPrivateMethodInitSpec$1(this, _initPopup);
	    _classPrivateFieldInitSpec(this, _bindElement, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _popupContent, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isLoading, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _popup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _list, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(this, _listRenderer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _events, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _useSearch, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _searchValue, {
	      writable: true,
	      value: ''
	    });
	    _classPrivateFieldInitSpec(this, _filter, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _bindElement, options.bindElement);
	    babelHelpers.classPrivateFieldSet(this, _listRenderer, options.listRenderer);
	    babelHelpers.classPrivateFieldSet(this, _events, options.events || {});
	    babelHelpers.classPrivateFieldSet(this, _filter, options.filter || null);
	    babelHelpers.classPrivateFieldSet(this, _useSearch, options.useSearch === true);
	  }
	  babelHelpers.createClass(PopupWithLoader, [{
	    key: "show",
	    value: function show() {
	      if (!babelHelpers.classPrivateFieldGet(this, _popup)) {
	        _classPrivateMethodGet$1(this, _initPopup, _initPopup2).call(this);
	      }
	      babelHelpers.classPrivateFieldGet(this, _popup).show();
	      if (babelHelpers.classPrivateFieldGet(this, _isLoading)) {
	        var copilotColor = getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary');
	        var loader = new main_loader.Loader({
	          target: babelHelpers.classPrivateFieldGet(this, _popupContent),
	          size: 30,
	          color: copilotColor
	        });
	        loader.show(babelHelpers.classPrivateFieldGet(this, _popupContent).root);
	      }
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      var _babelHelpers$classPr;
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _popup)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.destroy();
	      babelHelpers.classPrivateFieldSet(this, _popup, null);
	      babelHelpers.classPrivateFieldSet(this, _popupContent, null);
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      var _babelHelpers$classPr2;
	      return Boolean((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _popup)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.isShown());
	    }
	  }, {
	    key: "setLoading",
	    value: function setLoading(isLoading) {
	      babelHelpers.classPrivateFieldSet(this, _isLoading, isLoading);
	    }
	  }, {
	    key: "setList",
	    value: function setList(list) {
	      babelHelpers.classPrivateFieldSet(this, _list, list);
	      if (babelHelpers.classPrivateFieldGet(this, _popup)) {
	        babelHelpers.classPrivateFieldGet(this, _popup).setContent(_classPrivateMethodGet$1(this, _renderPopupContent, _renderPopupContent2).call(this));
	      }
	    }
	  }]);
	  return PopupWithLoader;
	}();
	function _initPopup2() {
	  babelHelpers.classPrivateFieldSet(this, _popup, new main_popup.Popup({
	    bindElement: babelHelpers.classPrivateFieldGet(this, _bindElement),
	    cacheable: false,
	    className: 'ai__share-role-library-grid_popup-with-more-info',
	    angle: {
	      position: 'top'
	    },
	    autoHide: true,
	    closeByEsc: true,
	    content: _classPrivateMethodGet$1(this, _renderPopupContent, _renderPopupContent2).call(this),
	    width: 285,
	    minHeight: 190,
	    maxHeight: 300,
	    padding: 16,
	    contentPadding: 0,
	    events: _objectSpread({}, babelHelpers.classPrivateFieldGet(this, _events))
	  }));
	}
	function _renderPopupContent2() {
	  var listWithSearchClassnameModifier = babelHelpers.classPrivateFieldGet(this, _useSearch) ? '--with-search' : '';
	  babelHelpers.classPrivateFieldSet(this, _popupContent, main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__role-library_info-popup\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"ai__role-library_info-popup_list ", "\" ref=\"listContainer\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t<div>\n\t\t"])), _classPrivateMethodGet$1(this, _renderSearch, _renderSearch2).call(this), listWithSearchClassnameModifier, _classPrivateMethodGet$1(this, _renderList, _renderList2).call(this)));
	  return babelHelpers.classPrivateFieldGet(this, _popupContent).root;
	}
	function _renderList2() {
	  var _this = this;
	  var list = babelHelpers.classPrivateFieldGet(this, _list).filter(function (item) {
	    if (babelHelpers.classPrivateFieldGet(_this, _filter)) {
	      return babelHelpers.classPrivateFieldGet(_this, _filter).call(_this, item, babelHelpers.classPrivateFieldGet(_this, _searchValue));
	    }
	    return true;
	  });
	  return babelHelpers.classPrivateFieldGet(this, _listRenderer).render(list, babelHelpers.classPrivateFieldGet(this, _searchValue));
	}
	function _updateList2() {
	  if (babelHelpers.classPrivateFieldGet(this, _popupContent).listContainer) {
	    babelHelpers.classPrivateFieldGet(this, _popupContent).listContainer.innerHTML = '';
	    main_core.Dom.append(_classPrivateMethodGet$1(this, _renderList, _renderList2).call(this), babelHelpers.classPrivateFieldGet(this, _popupContent).listContainer);
	  }
	}
	function _renderSearch2() {
	  var _this2 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _useSearch) === false) {
	    return null;
	  }
	  var container = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ai__role-library_info-popup_search\">\n\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-before-icon ui-ctl-after-icon\">\n\t\t\t\t\t<div class=\"ui-ctl-before ui-ctl-icon-search\"></div>\n\t\t\t\t\t<button ref=\"clear\" class=\"ui-ctl-after ui-ctl-icon-clear\"></button>\n\t\t\t\t\t<input ref=\"input\" type=\"text\" class=\"ui-ctl-element\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])));
	  main_core.bind(container.clear, 'click', function () {
	    container.input.value = '';
	    babelHelpers.classPrivateFieldSet(_this2, _searchValue, '');
	    _classPrivateMethodGet$1(_this2, _updateList, _updateList2).call(_this2);
	  });
	  main_core.bind(container.input, 'input', function (e) {
	    babelHelpers.classPrivateFieldSet(_this2, _searchValue, e.target.value);
	    _classPrivateMethodGet$1(_this2, _updateList, _updateList2).call(_this2);
	  });
	  return container.root;
	}

	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _regeneratorRuntime$1() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$1 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classStaticPrivateMethodGet(receiver, classConstructor, method) { _classCheckPrivateStaticAccess(receiver, classConstructor); return method; }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	var Controller = /*#__PURE__*/function () {
	  function Controller() {
	    babelHelpers.classCallCheck(this, Controller);
	  }
	  babelHelpers.createClass(Controller, null, [{
	    key: "handleClickOnDeleteRoleSwitcher",
	    /**
	     * @var BX.Main.Grid
	     */
	    value: function handleClickOnDeleteRoleSwitcher(event, roleCode, roleName) {
	      event.preventDefault();
	      event.stopPropagation();
	      _classStaticPrivateMethodGet(this, Controller, _sendRowAction).call(this, 'toggle-deleted', {
	        roleCode: roleCode,
	        needDeleted: 1,
	        page: _classStaticPrivateMethodGet(this, Controller, _getCurrentPage).call(this)
	      }, function () {
	        showNotification(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_HIDE', {
	          '#NAME#': "<b>".concat(main_core.Text.encode(roleName), "</b>")
	        }));
	      });
	    }
	  }, {
	    key: "handleClickOnUndoDeleteRoleSwitcher",
	    value: function handleClickOnUndoDeleteRoleSwitcher(event, roleCode, roleName) {
	      event.preventDefault();
	      event.stopPropagation();
	      _classStaticPrivateMethodGet(this, Controller, _sendRowAction).call(this, 'toggle-deleted', {
	        roleCode: roleCode,
	        needDeleted: 0,
	        page: _classStaticPrivateMethodGet(this, Controller, _getCurrentPage).call(this)
	      }, function () {
	        showNotification(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_SHOW', {
	          '#NAME#': "<b>".concat(main_core.Text.encode(roleName), "</b>")
	        }));
	      });
	    }
	  }, {
	    key: "handleClickOnActivateRoleMenuItem",
	    value: function handleClickOnActivateRoleMenuItem(event, roleCode, roleName) {
	      event.preventDefault();
	      event.stopImmediatePropagation();
	      _classStaticPrivateMethodGet(this, Controller, _sendRowAction).call(this, 'toggle-active', {
	        roleCode: roleCode,
	        needActivate: 1,
	        page: _classStaticPrivateMethodGet(this, Controller, _getCurrentPage).call(this)
	      }, function () {
	        showNotification(wrapTextToHtmlWithWordBreak(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_ACTIVATE', {
	          '#NAME#': "<b>".concat(main_core.Text.encode(roleName), "</b>")
	        })));
	      });
	    }
	  }, {
	    key: "handleClickOnDeactivateRoleMenuItem",
	    value: function handleClickOnDeactivateRoleMenuItem(event, roleCode, roleName) {
	      event.preventDefault();
	      event.stopImmediatePropagation();
	      _classStaticPrivateMethodGet(this, Controller, _sendRowAction).call(this, 'toggle-active', {
	        roleCode: roleCode,
	        needActivate: 0,
	        page: _classStaticPrivateMethodGet(this, Controller, _getCurrentPage).call(this)
	      }, function () {
	        showNotification(wrapTextToHtmlWithWordBreak(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_DEACTIVATE', {
	          '#NAME#': "<b>".concat(main_core.Text.encode(roleName), "</b>")
	        })));
	      });
	    }
	  }, {
	    key: "handleClickOnRoleName",
	    value: function () {
	      var _handleClickOnRoleName = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee(event, roleCode) {
	        return _regeneratorRuntime$1().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              event.preventDefault();
	              event.stopImmediatePropagation();
	              this.editRole(roleCode);
	            case 3:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function handleClickOnRoleName(_x, _x2) {
	        return _handleClickOnRoleName.apply(this, arguments);
	      }
	      return handleClickOnRoleName;
	    }()
	  }, {
	    key: "editRole",
	    value: function () {
	      var _editRole = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee2(roleCode) {
	        var formData, fetchRoleByCodePromise, loadRoleMasterExtensionPromise, results, res, RoleMasterPopup, RoleMasterPopupEvents, role, options, popup;
	        return _regeneratorRuntime$1().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              _classStaticPrivateFieldSpecGet(this, Controller, _grid).getLoader().show();
	              _classStaticPrivateFieldSpecGet(this, Controller, _grid).tableFade();
	              formData = new FormData();
	              formData.append('roleCode', roleCode);
	              fetchRoleByCodePromise = main_core.ajax.runAction('ai.shareRole.getRoleByCodeForUpdate', {
	                method: 'POST',
	                data: formData
	              });
	              loadRoleMasterExtensionPromise = main_core.Runtime.loadExtension('ai.role-master');
	              _context2.prev = 6;
	              _context2.next = 9;
	              return Promise.all([fetchRoleByCodePromise, loadRoleMasterExtensionPromise]);
	            case 9:
	              results = _context2.sent;
	              res = results[0];
	              RoleMasterPopup = results[1].RoleMasterPopup;
	              RoleMasterPopupEvents = results[1].RoleMasterPopupEvents;
	              role = res.data.role;
	              options = {
	                roleMaster: {
	                  id: role.code,
	                  text: role.instruction,
	                  name: role.nameTranslate,
	                  avatar: role.avatar,
	                  avatarUrl: role.avatarUrl,
	                  itemsWithAccess: role.accessCodes,
	                  authorId: role.authorId,
	                  description: role.descriptionTranslate
	                }
	              };
	              popup = new RoleMasterPopup(_objectSpread$1(_objectSpread$1({}, options), {}, {
	                popupEvents: {
	                  onPopupDestroy: function onPopupDestroy() {
	                    popup.unsubscribe(RoleMasterPopupEvents.SAVE_SUCCESS, _classStaticPrivateFieldSpecGet(Controller, Controller, _roleSuccessSavingEventHandler));
	                  }
	                },
	                analyticFields: {
	                  c_section: 'list'
	                }
	              }));
	              _classStaticPrivateFieldSpecSet(Controller, Controller, _roleSuccessSavingEventHandler, _classStaticPrivateMethodGet(Controller, Controller, _handleRoleSuccessSaving).bind(Controller));
	              popup.subscribe(RoleMasterPopupEvents.SAVE_SUCCESS, _classStaticPrivateFieldSpecGet(Controller, Controller, _roleSuccessSavingEventHandler));
	              popup.show();
	              _context2.next = 25;
	              break;
	            case 21:
	              _context2.prev = 21;
	              _context2.t0 = _context2["catch"](6);
	              console.error(_context2.t0);
	              showNotification(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_ACTION_OPEN_EDIT_MASTER_ERROR'));
	            case 25:
	              _context2.prev = 25;
	              _classStaticPrivateFieldSpecGet(this, Controller, _grid).getLoader().hide();
	              _classStaticPrivateFieldSpecGet(this, Controller, _grid).tableUnfade();
	              return _context2.finish(25);
	            case 29:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2, this, [[6, 21, 25, 29]]);
	      }));
	      function editRole(_x3) {
	        return _editRole.apply(this, arguments);
	      }
	      return editRole;
	    }()
	  }, {
	    key: "handleClickOnCreateRoleButton",
	    value: function () {
	      var _handleClickOnCreateRoleButton = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee3(button) {
	        var _yield$Runtime$loadEx, RoleMasterPopup, RoleMasterPopupEvents, popup;
	        return _regeneratorRuntime$1().wrap(function _callee3$(_context3) {
	          while (1) switch (_context3.prev = _context3.next) {
	            case 0:
	              _context3.prev = 0;
	              button.setClocking(true);
	              _context3.next = 4;
	              return main_core.Runtime.loadExtension('ai.role-master');
	            case 4:
	              _yield$Runtime$loadEx = _context3.sent;
	              RoleMasterPopup = _yield$Runtime$loadEx.RoleMasterPopup;
	              RoleMasterPopupEvents = _yield$Runtime$loadEx.RoleMasterPopupEvents;
	              popup = new RoleMasterPopup({
	                popupEvents: {
	                  onPopupDestroy: function onPopupDestroy() {
	                    popup.unsubscribe(RoleMasterPopupEvents.SAVE_SUCCESS, _classStaticPrivateFieldSpecGet(Controller, Controller, _roleSuccessSavingEventHandler));
	                  }
	                },
	                analyticFields: {
	                  c_section: 'list'
	                }
	              });
	              _classStaticPrivateFieldSpecSet(Controller, Controller, _roleSuccessSavingEventHandler, _classStaticPrivateMethodGet(Controller, Controller, _handleRoleSuccessSaving).bind(Controller));
	              popup.subscribe(RoleMasterPopupEvents.SAVE_SUCCESS, _classStaticPrivateFieldSpecGet(Controller, Controller, _roleSuccessSavingEventHandler));
	              popup.show();
	              _context3.next = 17;
	              break;
	            case 13:
	              _context3.prev = 13;
	              _context3.t0 = _context3["catch"](0);
	              console.error(_context3.t0);
	              showNotification(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_ROLE_MASTER_OPEN_ERROR'));
	            case 17:
	              _context3.prev = 17;
	              button.setClocking(false);
	              return _context3.finish(17);
	            case 20:
	            case "end":
	              return _context3.stop();
	          }
	        }, _callee3, null, [[0, 13, 17, 20]]);
	      }));
	      function handleClickOnCreateRoleButton(_x4) {
	        return _handleClickOnCreateRoleButton.apply(this, arguments);
	      }
	      return handleClickOnCreateRoleButton;
	    }()
	  }, {
	    key: "handleClickOnRoleIsFavouriteLabel",
	    value: function handleClickOnRoleIsFavouriteLabel(event, roleCode, favourite, roleName) {
	      event.preventDefault();
	      event.stopImmediatePropagation();
	      _classStaticPrivateMethodGet(this, Controller, _sendRowAction).call(this, 'toggle-favourite', {
	        roleCode: roleCode,
	        favourite: favourite,
	        page: _classStaticPrivateMethodGet(this, Controller, _getCurrentPage).call(this)
	      }, function () {
	        var message = favourite === 'true' ? wrapTextToHtmlWithWordBreak(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_FAVOURITE_ADD', {
	          '#NAME#': "<b>".concat(main_core.Text.encode(roleName), "</b>")
	        })) : wrapTextToHtmlWithWordBreak(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_FAVOURITE_REMOVE', {
	          '#NAME#': "<b>".concat(main_core.Text.encode(roleName), "</b>")
	        }));
	        showNotification(message);
	      });
	    }
	  }, {
	    key: "toggleRoleFavourite",
	    value: function toggleRoleFavourite(roleCode, favourite, roleName) {
	      _classStaticPrivateMethodGet(this, Controller, _sendRowAction).call(this, 'toggle-favourite', {
	        roleCode: roleCode,
	        favourite: favourite,
	        page: _classStaticPrivateMethodGet(this, Controller, _getCurrentPage).call(this)
	      }, function () {
	        var message = favourite === 'true' ? wrapTextToHtmlWithWordBreak(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_FAVOURITE_ADD', {
	          '#NAME#': "<b>".concat(main_core.Text.encode(roleName), "</b>")
	        })) : wrapTextToHtmlWithWordBreak(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_FAVOURITE_REMOVE', {
	          '#NAME#': "<b>".concat(main_core.Text.encode(roleName), "</b>")
	        }));
	        showNotification(message);
	      });
	    }
	  }, {
	    key: "applyMultipleAction",
	    value: function applyMultipleAction() {
	      var action = _classStaticPrivateFieldSpecGet(this, Controller, _grid).getActionsPanel().getPanel().querySelector('#action-menu span').dataset.value;
	      var actionWithoutQuotes = action.replaceAll('"', '');
	      var message = _classStaticPrivateMethodGet(this, Controller, _getNotificationMessageForMassAction).call(this, actionWithoutQuotes);
	      _classStaticPrivateMethodGet(this, Controller, _sendRowAction).call(this, actionWithoutQuotes, {
	        selectedShareRolesCodes: _classStaticPrivateFieldSpecGet(this, Controller, _grid).getRows().getSelectedIds(),
	        page: _classStaticPrivateMethodGet(this, Controller, _getCurrentPage).call(this)
	      }, function () {
	        showNotification(message);
	      });
	    }
	  }, {
	    key: "init",
	    value: function init(gridId) {
	      var _BX$Main$gridManager$,
	        _this = this;
	      var isShowTour = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      if (isShowTour) {
	        _classStaticPrivateMethodGet(this, Controller, _showSimpleTour).call(this);
	      }
	      _classStaticPrivateFieldSpecSet(this, Controller, _grid, (_BX$Main$gridManager$ = BX.Main.gridManager.getById(gridId)) === null || _BX$Main$gridManager$ === void 0 ? void 0 : _BX$Main$gridManager$.instance);
	      main_core.bind(_classStaticPrivateFieldSpecGet(this, Controller, _grid).getScrollContainer(), 'scroll', function () {
	        var _classStaticPrivateFi, _classStaticPrivateFi2;
	        (_classStaticPrivateFi = _classStaticPrivateFieldSpecGet(_this, Controller, _categoriesListPopup)) === null || _classStaticPrivateFi === void 0 ? void 0 : _classStaticPrivateFi.hide();
	        (_classStaticPrivateFi2 = _classStaticPrivateFieldSpecGet(_this, Controller, _allSharesListPopup)) === null || _classStaticPrivateFi2 === void 0 ? void 0 : _classStaticPrivateFi2.hide();
	      });
	      _classStaticPrivateMethodGet(Controller, Controller, _updateApplyButtonClassname).call(Controller);
	      _classStaticPrivateMethodGet(Controller, Controller, _observeSelectActionButtonValue).call(Controller);
	      main_core.Event.EventEmitter.subscribe('Grid::updated', function () {
	        _classStaticPrivateMethodGet(Controller, Controller, _updateApplyButtonClassname).call(Controller);
	        _classStaticPrivateMethodGet(Controller, Controller, _observeSelectActionButtonValue).call(Controller);
	        BX.UI.Hint.init(BX('main-grid-table'));
	      });
	      main_core.Event.EventEmitter.subscribe('BX.Main.Filter:apply', function () {
	        ui_analytics.sendData({
	          tool: 'ai',
	          category: 'roles_saving',
	          event: 'use_filter',
	          c_section: 'list',
	          status: 'success'
	        });
	      });
	      BX.UI.Hint.init(BX('main-grid-table'));
	    }
	  }, {
	    key: "handleClickOnSharesCell",
	    value: function () {
	      var _handleClickOnSharesCell = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee4(shareRoleCode, event) {
	        var _this2 = this;
	        var formData, res, list;
	        return _regeneratorRuntime$1().wrap(function _callee4$(_context4) {
	          while (1) switch (_context4.prev = _context4.next) {
	            case 0:
	              event.preventDefault();
	              event.stopImmediatePropagation();
	              if (!_classStaticPrivateFieldSpecGet(this, Controller, _allSharesListPopup)) {
	                _context4.next = 5;
	                break;
	              }
	              _classStaticPrivateFieldSpecGet(this, Controller, _allSharesListPopup).hide();
	              return _context4.abrupt("return");
	            case 5:
	              _classStaticPrivateFieldSpecSet(this, Controller, _allSharesListPopup, new PopupWithLoader({
	                bindElement: event.target,
	                listRenderer: new SharesListRenderer(),
	                events: {
	                  onPopupDestroy: function onPopupDestroy() {
	                    _classStaticPrivateFieldSpecSet(_this2, Controller, _allSharesListPopup, null);
	                  }
	                },
	                filter: function filter(item, searchValue) {
	                  return item.name.toLowerCase().includes(searchValue === null || searchValue === void 0 ? void 0 : searchValue.toLowerCase());
	                },
	                useSearch: true
	              }));
	              _context4.prev = 6;
	              _classStaticPrivateFieldSpecGet(this, Controller, _allSharesListPopup).setLoading(true);
	              _classStaticPrivateFieldSpecGet(this, Controller, _allSharesListPopup).show();
	              formData = new FormData();
	              formData.append('roleCode', shareRoleCode);
	              _context4.next = 13;
	              return main_core.ajax.runAction('ai.shareRole.getShareForRole', {
	                data: formData
	              });
	            case 13:
	              res = _context4.sent;
	              list = res.data.list;
	              _classStaticPrivateFieldSpecGet(this, Controller, _allSharesListPopup).setList(list.slice(5));
	              _context4.next = 24;
	              break;
	            case 18:
	              _context4.prev = 18;
	              _context4.t0 = _context4["catch"](6);
	              console.error(_context4.t0);
	              _context4.next = 23;
	              return showNotification(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_SHOW_ROLE_USERS_ERROR'));
	            case 23:
	              _classStaticPrivateFieldSpecGet(this, Controller, _allSharesListPopup).hide();
	            case 24:
	              _context4.prev = 24;
	              _classStaticPrivateFieldSpecGet(this, Controller, _allSharesListPopup).setLoading(false);
	              return _context4.finish(24);
	            case 27:
	            case "end":
	              return _context4.stop();
	          }
	        }, _callee4, this, [[6, 18, 24, 27]]);
	      }));
	      function handleClickOnSharesCell(_x5, _x6) {
	        return _handleClickOnSharesCell.apply(this, arguments);
	      }
	      return handleClickOnSharesCell;
	    }()
	  }]);
	  return Controller;
	}();
	function _getNotificationMessageForMassAction(actionName) {
	  switch (actionName) {
	    case 'multiple-activate':
	      {
	        return main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_ACTIVATE');
	      }
	    case 'multiple-deactivate':
	      {
	        return main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_DEACTIVATE');
	      }
	    case 'multiple-show-for-me':
	      {
	        return main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_SHOW');
	      }
	    case 'multiple-hide-from-me':
	      {
	        return main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_HIDE');
	      }
	    default:
	      {
	        return main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_ACTION_DEFAULT');
	      }
	  }
	}
	function _observeSelectActionButtonValue() {
	  var panel = _classStaticPrivateFieldSpecGet(this, Controller, _grid).getActionsPanel();
	  if (!panel) {
	    return;
	  }
	  var attributesObserver = new MutationObserver(function () {
	    _classStaticPrivateMethodGet(Controller, Controller, _updateApplyButtonClassname).call(Controller);
	  });
	  var selectActionButton = panel.getControls()[0];
	  attributesObserver.observe(selectActionButton, {
	    childList: false,
	    subtree: true,
	    characterDataOldValue: false,
	    attributes: true,
	    attributeOldValue: true,
	    attributeFilter: ['data-value'],
	    characterData: false
	  });
	}
	function _updateApplyButtonClassname() {
	  var panel = _classStaticPrivateFieldSpecGet(this, Controller, _grid).getActionsPanel();
	  if (!panel) {
	    return;
	  }
	  var values = panel.getValues();
	  var btn = panel.getPanel().querySelector('#apply_button_control.ui-btn');
	  var action = values['action-menu'];
	  if (action === '"select-action"' || action === 'select-action') {
	    main_core.Dom.addClass(btn, 'ui-btn-disabled');
	    main_core.Dom.addClass(btn, 'ai__role-library-grid_share-initials');
	  } else {
	    main_core.Dom.removeClass(btn, 'ui-btn-disabled');
	    main_core.Dom.removeClass(btn, 'ai__role-library-grid_share-initials');
	  }
	}
	function _handleRoleSuccessSaving(event) {
	  _classStaticPrivateMethodGet(this, Controller, _sendRowAction).call(this, 'edit-role', {
	    page: _classStaticPrivateMethodGet(this, Controller, _getCurrentPage).call(this)
	  }, function () {
	    showNotification(main_core.Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_ROLE_SAVE_SUCCESS', {
	      '#NAME#': "<b>".concat(main_core.Text.encode(event.getData().roleTitle), "</b>")
	    }));
	  });
	}
	function _showSimpleTour() {
	  return _showSimpleTour3.apply(this, arguments);
	}
	function _showSimpleTour3() {
	  _showSimpleTour3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee5() {
	    var loadGuideExtensionPromise, loadBannerDispatcherExtensionPromise, result, Guide, BannerDispatcher;
	    return _regeneratorRuntime$1().wrap(function _callee5$(_context5) {
	      while (1) switch (_context5.prev = _context5.next) {
	        case 0:
	          loadGuideExtensionPromise = main_core.Runtime.loadExtension('ui.tour');
	          loadBannerDispatcherExtensionPromise = main_core.Runtime.loadExtension('ui.banner-dispatcher');
	          _context5.next = 4;
	          return Promise.all([loadGuideExtensionPromise, loadBannerDispatcherExtensionPromise]);
	        case 4:
	          result = _context5.sent;
	          Guide = result[0].Guide;
	          BannerDispatcher = result[1].BannerDispatcher;
	          BannerDispatcher.critical.toQueue(function (onDone) {
	            var guide = new Guide({
	              id: 'share-role-grid-create-prompt-hint',
	              simpleMode: true,
	              overlay: false,
	              onEvents: true,
	              autoSave: true,
	              steps: [{
	                target: '.ui-btn.ui-btn-success',
	                title: main_core.Loc.getMessage('ROLE_LIBRARY_GRID_TOUR_TITLE'),
	                text: main_core.Loc.getMessage('ROLE_LIBRARY_GRID_TOUR_DESCRIPTION')
	              }]
	            });
	            main_core.Event.EventEmitter.subscribe('UI.Tour.Guide:onFinish', function () {
	              guide.save();
	              onDone();
	            });
	            guide.start();
	          });
	        case 8:
	        case "end":
	          return _context5.stop();
	      }
	    }, _callee5);
	  }));
	  return _showSimpleTour3.apply(this, arguments);
	}
	function _getCurrentPage() {
	  var currentPageElement = document.body.querySelector('.main-ui-pagination-page.main-ui-pagination-active');
	  return Number.parseInt(currentPageElement === null || currentPageElement === void 0 ? void 0 : currentPageElement.innerText, 10) || 1;
	}
	function _sendRowAction(action, data, callback) {
	  var dataWithAction = _objectSpread$1(babelHelpers.defineProperty({}, _classStaticPrivateFieldSpecGet(this, Controller, _grid).getActionKey(), action), data);
	  _classStaticPrivateFieldSpecGet(this, Controller, _grid).reloadTable('POST', dataWithAction, callback);
	}
	var _grid = {
	  writable: true,
	  value: void 0
	};
	var _categoriesListPopup = {
	  writable: true,
	  value: null
	};
	var _allSharesListPopup = {
	  writable: true,
	  value: null
	};
	var _roleSuccessSavingEventHandler = {
	  writable: true,
	  value: null
	};

	exports.Controller = Controller;

}((this.BX.AI.ShareRole.Library = this.BX.AI.ShareRole.Library || {}),BX.Event,BX.Main,BX,BX,BX.UI.Analytics));
//# sourceMappingURL=script.js.map
