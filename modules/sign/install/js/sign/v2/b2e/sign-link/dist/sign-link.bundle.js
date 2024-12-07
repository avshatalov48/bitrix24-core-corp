/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,ui_buttons,ui_sidepanelContent,ui_designTokens,main_date,main_core,sign_v2_api) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _container = /*#__PURE__*/new WeakMap();
	var _memberId = /*#__PURE__*/new WeakMap();
	var _loaded = /*#__PURE__*/new WeakMap();
	var _errorCode = /*#__PURE__*/new WeakMap();
	var _errorMessage = /*#__PURE__*/new WeakMap();
	var _uri = /*#__PURE__*/new WeakMap();
	var _showHelpdeskGoskey = /*#__PURE__*/new WeakMap();
	var _api = /*#__PURE__*/new WeakMap();
	var _requireBrowser = /*#__PURE__*/new WeakMap();
	var _mobileAllowed = /*#__PURE__*/new WeakMap();
	var _employeeData = /*#__PURE__*/new WeakMap();
	var _renderMemberInfo = /*#__PURE__*/new WeakMap();
	var _slider = /*#__PURE__*/new WeakMap();
	var _frameEventHandler = /*#__PURE__*/new WeakMap();
	var _cache = /*#__PURE__*/new WeakMap();
	var _loadData = /*#__PURE__*/new WeakSet();
	var _getLoader = /*#__PURE__*/new WeakSet();
	var _renderError = /*#__PURE__*/new WeakSet();
	var _getErrorTitle = /*#__PURE__*/new WeakSet();
	var _renderUrl = /*#__PURE__*/new WeakSet();
	var _renderContinueInBrowserPage = /*#__PURE__*/new WeakSet();
	var _renderDownloadSignedDocForEmployee = /*#__PURE__*/new WeakSet();
	var _renderMemberInfoBlock = /*#__PURE__*/new WeakSet();
	var _needToShowPageForEmployee = /*#__PURE__*/new WeakSet();
	var _isNeedToContinueInBrowser = /*#__PURE__*/new WeakSet();
	var _isNeedToContinueOnDesktop = /*#__PURE__*/new WeakSet();
	var _isDesktopApp = /*#__PURE__*/new WeakSet();
	var _handleIframeEvent = /*#__PURE__*/new WeakSet();
	var SignLink = /*#__PURE__*/function () {
	  function SignLink() {
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, SignLink);
	    _classPrivateMethodInitSpec(this, _handleIframeEvent);
	    _classPrivateMethodInitSpec(this, _isDesktopApp);
	    _classPrivateMethodInitSpec(this, _isNeedToContinueOnDesktop);
	    _classPrivateMethodInitSpec(this, _isNeedToContinueInBrowser);
	    _classPrivateMethodInitSpec(this, _needToShowPageForEmployee);
	    _classPrivateMethodInitSpec(this, _renderMemberInfoBlock);
	    _classPrivateMethodInitSpec(this, _renderDownloadSignedDocForEmployee);
	    _classPrivateMethodInitSpec(this, _renderContinueInBrowserPage);
	    _classPrivateMethodInitSpec(this, _renderUrl);
	    _classPrivateMethodInitSpec(this, _getErrorTitle);
	    _classPrivateMethodInitSpec(this, _renderError);
	    _classPrivateMethodInitSpec(this, _getLoader);
	    _classPrivateMethodInitSpec(this, _loadData);
	    _classPrivateFieldInitSpec(this, _container, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _memberId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _loaded, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _errorCode, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _errorMessage, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _uri, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _showHelpdeskGoskey, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _requireBrowser, {
	      writable: true,
	      value: true
	    });
	    _classPrivateFieldInitSpec(this, _mobileAllowed, {
	      writable: true,
	      value: true
	    });
	    _classPrivateFieldInitSpec(this, _employeeData, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(this, _renderMemberInfo, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _slider, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _frameEventHandler, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    babelHelpers.classPrivateFieldSet(this, _api, new sign_v2_api.Api());
	    babelHelpers.classPrivateFieldSet(this, _memberId, options.memberId);
	    babelHelpers.classPrivateFieldSet(this, _requireBrowser, (options === null || options === void 0 ? void 0 : options.requireBrowser) || true);
	    babelHelpers.classPrivateFieldSet(this, _mobileAllowed, (options === null || options === void 0 ? void 0 : options.mobileAllowed) || true);
	    babelHelpers.classPrivateFieldSet(this, _slider, (options === null || options === void 0 ? void 0 : options.slider) || null);
	  }
	  babelHelpers.createClass(SignLink, [{
	    key: "preloadData",
	    value: function preloadData() {
	      return _classPrivateMethodGet(this, _loadData, _loadData2).call(this);
	    }
	  }, {
	    key: "openSlider",
	    value: function () {
	      var _openSlider = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(options) {
	        var signLink;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              if (babelHelpers.classPrivateFieldGet(this, _loaded)) {
	                _context.next = 3;
	                break;
	              }
	              _context.next = 3;
	              return _classPrivateMethodGet(this, _loadData, _loadData2).call(this);
	            case 3:
	              signLink = this;
	              BX.SidePanel.Instance.open('sign:stub:sign-link', {
	                width: 900,
	                cacheable: false,
	                allowCrossOrigin: true,
	                allowCrossDomain: true,
	                allowChangeHistory: false,
	                // newWindowUrl: link,
	                copyLinkLabel: true,
	                newWindowLabel: true,
	                loader: '/bitrix/js/intranet/sidepanel/bindings/images/sign_mask.svg',
	                label: {
	                  text: main_core.Loc.getMessage('SIGN_V2_B2E_LINK_SLIDER_TITLE'),
	                  bgColor: '#C48300'
	                },
	                contentCallback: function contentCallback() {
	                  return Promise.resolve(true).then(function () {
	                    return signLink.render();
	                  });
	                },
	                events: options === null || options === void 0 ? void 0 : options.events
	              });
	              babelHelpers.classPrivateFieldSet(this, _slider, BX.SidePanel.Instance.getSlider('sign:stub:sign-link'));
	            case 6:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function openSlider(_x) {
	        return _openSlider.apply(this, arguments);
	      }
	      return openSlider;
	    }()
	  }, {
	    key: "renderTo",
	    value: function renderTo(node) {
	      if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	        babelHelpers.classPrivateFieldSet(this, _container, document.createElement('div'));
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _container), 'sign-ui-signing-link-container');
	      }
	      main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _container), node);
	      this.render();
	    }
	  }, {
	    key: "render",
	    value: function () {
	      var _render = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
	        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	                babelHelpers.classPrivateFieldSet(this, _container, document.createElement('div'));
	                main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _container), 'sign-ui-signing-link-container');
	              }
	              if (babelHelpers.classPrivateFieldGet(this, _loaded)) {
	                _context2.next = 6;
	                break;
	              }
	              main_core.Dom.append(_classPrivateMethodGet(this, _getLoader, _getLoader2).call(this), babelHelpers.classPrivateFieldGet(this, _container));
	              _context2.next = 5;
	              return _classPrivateMethodGet(this, _loadData, _loadData2).call(this);
	            case 5:
	              main_core.Dom.remove(_classPrivateMethodGet(this, _getLoader, _getLoader2).call(this), babelHelpers.classPrivateFieldGet(this, _container));
	            case 6:
	              if (babelHelpers.classPrivateFieldGet(this, _uri)) {
	                if (_classPrivateMethodGet(this, _isNeedToContinueInBrowser, _isNeedToContinueInBrowser2).call(this) || _classPrivateMethodGet(this, _isNeedToContinueOnDesktop, _isNeedToContinueOnDesktop2).call(this)) {
	                  _classPrivateMethodGet(this, _renderContinueInBrowserPage, _renderContinueInBrowserPage2).call(this);
	                } else if (_classPrivateMethodGet(this, _needToShowPageForEmployee, _needToShowPageForEmployee2).call(this)) {
	                  _classPrivateMethodGet(this, _renderDownloadSignedDocForEmployee, _renderDownloadSignedDocForEmployee2).call(this);
	                } else {
	                  _classPrivateMethodGet(this, _renderUrl, _renderUrl2).call(this);
	                }
	              } else {
	                _classPrivateMethodGet(this, _renderError, _renderError2).call(this, _classPrivateMethodGet(this, _getErrorTitle, _getErrorTitle2).call(this, babelHelpers.classPrivateFieldGet(this, _errorCode)), babelHelpers.classPrivateFieldGet(this, _errorMessage));
	              }
	              return _context2.abrupt("return", babelHelpers.classPrivateFieldGet(this, _container));
	            case 8:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2, this);
	      }));
	      function render() {
	        return _render.apply(this, arguments);
	      }
	      return render;
	    }()
	  }]);
	  return SignLink;
	}();
	function _loadData2() {
	  return _loadData3.apply(this, arguments);
	}
	function _loadData3() {
	  _loadData3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
	    var _this2 = this;
	    return _regeneratorRuntime().wrap(function _callee3$(_context3) {
	      while (1) switch (_context3.prev = _context3.next) {
	        case 0:
	          return _context3.abrupt("return", babelHelpers.classPrivateFieldGet(this, _api).getLinkForSigning(babelHelpers.classPrivateFieldGet(this, _memberId), false).then(function (data) {
	            var _data$requireBrowser, _data$mobileAllowed, _data$employeeData;
	            if ((data === null || data === void 0 ? void 0 : data.status) === 'error') {
	              throw data;
	            }
	            babelHelpers.classPrivateFieldSet(_this2, _uri, data.uri);
	            babelHelpers.classPrivateFieldSet(_this2, _showHelpdeskGoskey, data.showHelpdeskGoskey);
	            babelHelpers.classPrivateFieldSet(_this2, _requireBrowser, (_data$requireBrowser = data === null || data === void 0 ? void 0 : data.requireBrowser) !== null && _data$requireBrowser !== void 0 ? _data$requireBrowser : true);
	            babelHelpers.classPrivateFieldSet(_this2, _mobileAllowed, (_data$mobileAllowed = data === null || data === void 0 ? void 0 : data.mobileAllowed) !== null && _data$mobileAllowed !== void 0 ? _data$mobileAllowed : true);
	            babelHelpers.classPrivateFieldSet(_this2, _employeeData, (_data$employeeData = data === null || data === void 0 ? void 0 : data.employeeData) !== null && _data$employeeData !== void 0 ? _data$employeeData : {});
	            babelHelpers.classPrivateFieldSet(_this2, _loaded, true);
	          })["catch"](function (errors) {
	            var _errors$errors, _errors$errors$, _errors$errors2, _errors$errors2$;
	            babelHelpers.classPrivateFieldSet(_this2, _loaded, true);
	            babelHelpers.classPrivateFieldSet(_this2, _errorCode, errors === null || errors === void 0 ? void 0 : (_errors$errors = errors.errors) === null || _errors$errors === void 0 ? void 0 : (_errors$errors$ = _errors$errors[0]) === null || _errors$errors$ === void 0 ? void 0 : _errors$errors$.code);
	            babelHelpers.classPrivateFieldSet(_this2, _errorMessage, errors === null || errors === void 0 ? void 0 : (_errors$errors2 = errors.errors) === null || _errors$errors2 === void 0 ? void 0 : (_errors$errors2$ = _errors$errors2[0]) === null || _errors$errors2$ === void 0 ? void 0 : _errors$errors2$.message);
	          }));
	        case 1:
	        case "end":
	          return _context3.stop();
	      }
	    }, _callee3, this);
	  }));
	  return _loadData3.apply(this, arguments);
	}
	function _getLoader2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('mask', function () {
	    return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-ui-signing-link-loading-mask\"></div>\n\t\t\t"])));
	  });
	}
	function _renderError2(title, message) {
	  title = title || main_core.Loc.getMessage('SIGN_V2_B2E_LINK_ERROR_TITLE_PLACEHOLDER');
	  title = main_core.Tag.safe(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["", ""])), title);
	  message = message || main_core.Loc.getMessage('SIGN_V2_B2E_LINK_ERROR_MESSAGE_PLACEHOLDER');
	  message = main_core.Tag.safe(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["", ""])), message);
	  var el = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-slider-no-access\">\n\t\t\t\t<div class=\"ui-slider-no-access-inner\">\n\t\t\t\t\t<div class=\"ui-slider-no-access-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-slider-no-access-subtitle\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-slider-no-access-img\">\n\t\t\t\t\t\t<div class=\"ui-slider-no-access-img-inner\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), title, message);
	  main_core.Dom.append(el, babelHelpers.classPrivateFieldGet(this, _container));
	}
	function _getErrorTitle2(errorCode) {
	  if (errorCode === 'ACCESS_DENIED') {
	    return main_core.Loc.getMessage('SIGN_V2_B2E_LINK_ERROR_CODE_ACCESS_DENIED');
	  }
	  return main_core.Loc.getMessage('SIGN_V2_B2E_LINK_ERROR_TITLE_PLACEHOLDER');
	}
	function _renderUrl2() {
	  var _this = this;
	  main_core.Dom.append(_classPrivateMethodGet(this, _getLoader, _getLoader2).call(this), babelHelpers.classPrivateFieldGet(this, _container));

	  // redirect if opened directly (new tab)
	  if (!BX.SidePanel.Instance.isOpen() || main_core.Browser.isMobile()) {
	    window.location.href = babelHelpers.classPrivateFieldGet(this, _uri);
	    return;
	  }
	  BX.SidePanel.Instance.newWindowUrl = window.location.href;
	  babelHelpers.classPrivateFieldSet(this, _frameEventHandler, function (event) {
	    return _classPrivateMethodGet(_this, _handleIframeEvent, _handleIframeEvent2).call(_this, event);
	  });
	  main_core.Event.bind(top, 'message', babelHelpers.classPrivateFieldGet(this, _frameEventHandler));
	  var frameStyles = 'position: absolute; left: 0; top: 0; padding: 0;' + ' border: none; margin: 0; width: 100%; height: 100%;';
	  var onloadHandler = function onloadHandler() {
	    main_core.Dom.remove(_classPrivateMethodGet(_this, _getLoader, _getLoader2).call(_this));
	  };
	  var iframe = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<iframe \n\t\t\t\tsrc=\"", "\" \n\t\t\t\treferrerpolicy=\"strict-origin\" \n\t\t\t\tstyle=\"", "\"\n\t\t\t\tonload=\"", "\"\n\t\t\t></iframe>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _uri), frameStyles, onloadHandler);
	  main_core.Dom.append(iframe, babelHelpers.classPrivateFieldGet(this, _container));
	}
	function _renderContinueInBrowserPage2() {
	  main_core.Dom.append(main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-ui-signing-link__empty-state\">\n\t\t\t\t<div class=\"sign-ui-signing-link__empty-state_icon\"></div>\n\t\t\t\t<div class=\"sign-ui-signing-link__empty-state_title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"sign-ui-signing-link__empty-state_desc\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<a\n\t\t\t\t\thref=\"", "\"\n\t\t\t\t\ttarget=\"_blank\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-primary ui-btn-round\"\n\t\t\t\t>\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(main_core.Loc.getMessage('SIGN_V2_B2E_LINK_DESKTOP_TITLE')), main_core.Text.encode(main_core.Loc.getMessage('SIGN_V2_B2E_LINK_DESKTOP_TEXT')), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _uri)), main_core.Text.encode(main_core.Loc.getMessage('SIGN_V2_B2E_LINK_DESKTOP_BUTTON'))), babelHelpers.classPrivateFieldGet(this, _container));
	}
	function _renderDownloadSignedDocForEmployee2() {
	  main_core.Dom.append(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-ui-signing-link__employee\">\n\t\t\t\t<div class=\"sign-ui-signing-link__employee-header\">\n\t\t\t\t\t<div class=\"sign-ui-signing-link__employee-header-header\">\n\t\t\t\t\t\t<h2>", "</h2>\n\t\t\t\t\t\t<p>", "</p>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"sign-ui-signing-link__employee-doc\">\n\t\t\t\t\t<p>", "</p>\n\t\t\t\t\t<div>\n\t\t\t\t\t\t<div>\n\t\t\t\t\t\t\t<span class=\"sign-ui-signing-link__employee-doc--icon\"></span>\n\t\t\t\t\t\t\t<div class=\"sign-ui-signing-link__employee-doc--info\">\n\t\t\t\t\t\t\t\t<div class=\"sign-ui-signing-link__employee-doc--info-title\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"sign-ui-signing-link__employee-doc--info-date\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<a href=\"", "\" class=\"ui-btn ui-btn-success ui-btn-round ui-btn-sm\" download>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<div onclick=\"BX.SidePanel.Instance.open('", "')\" class=\"sign-ui-signing-link__employee-alldocs\" target=\"_blank\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _employeeData).document.title), main_core.Loc.getMessage('SIGN_V2_B2E_LINK_EMPLOYEE_DISCLAIMER_MSGVER1'), babelHelpers.classPrivateFieldGet(this, _renderMemberInfo) ? _classPrivateMethodGet(this, _renderMemberInfoBlock, _renderMemberInfoBlock2).call(this, babelHelpers.classPrivateFieldGet(this, _employeeData).member) : '', main_core.Loc.getMessage('SIGN_V2_B2E_LINK_EMPLOYEE_SIGNED_DOC_MSG'), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _employeeData).document.title), main_core.Loc.getMessage('SIGN_V2_B2E_LINK_EMPLOYEE_DOCUMENT_DATE', {
	    '#DATE#': main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT'), babelHelpers.classPrivateFieldGet(this, _employeeData).dateSignedTs)
	  }), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _employeeData).uri.signedDocument), main_core.Loc.getMessage('SIGN_V2_B2E_LINK_EMPLOYEE_SIGNED_DOC_BTN'), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _employeeData).uri.allDocuments), main_core.Loc.getMessage('SIGN_V2_B2E_LINK_EMPLOYEE_BUTTON_ALLDOCS')), babelHelpers.classPrivateFieldGet(this, _container));
	}
	function _renderMemberInfoBlock2(memberInfo) {
	  return main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-ui-signing-link__employee-header-person\">\n\t\t\t\t<div class=\"sign-ui-signing-link__employee-header-person-photo\">\n\t\t\t\t\t<img src=\"", "\" alt=\"\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"sign-ui-signing-link__employee-header-person-text\">\n\t\t\t\t\t", "\n\t\t\t\t\t<br>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(memberInfo === null || memberInfo === void 0 ? void 0 : memberInfo.photo), main_core.Text.encode(memberInfo === null || memberInfo === void 0 ? void 0 : memberInfo.name), main_core.Text.encode(memberInfo === null || memberInfo === void 0 ? void 0 : memberInfo.position));
	}
	function _needToShowPageForEmployee2() {
	  var _babelHelpers$classPr;
	  return ((_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _employeeData)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.signed) === true;
	}
	function _isNeedToContinueInBrowser2() {
	  return babelHelpers.classPrivateFieldGet(this, _requireBrowser) && _classPrivateMethodGet(this, _isDesktopApp, _isDesktopApp2).call(this);
	}
	function _isNeedToContinueOnDesktop2() {
	  return main_core.Browser.isMobile() && !babelHelpers.classPrivateFieldGet(this, _mobileAllowed);
	}
	function _isDesktopApp2() {
	  // return window.navigator.userAgent.includes('BitrixDesktop');
	  return typeof BXDesktopSystem != "undefined" || typeof BXDesktopWindow != "undefined";
	}
	function _handleIframeEvent2(event) {
	  if (babelHelpers.classPrivateFieldGet(this, _uri).indexOf(event.origin) !== 0) {
	    return;
	  }
	  var message = {
	    type: '',
	    data: undefined
	  };
	  if (main_core.Type.isString(event === null || event === void 0 ? void 0 : event.data)) {
	    message.type = event.data;
	  }
	  if (message.type === 'BX:SidePanel:close') {
	    var _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _slider)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.close();
	    main_core.Event.unbind(window, 'message', babelHelpers.classPrivateFieldGet(this, _frameEventHandler));
	  }
	}

	exports.SignLink = SignLink;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.UI,BX.UI.Sidepanel.Content,BX,BX.Main,BX,BX.Sign.V2));
//# sourceMappingURL=sign-link.bundle.js.map
