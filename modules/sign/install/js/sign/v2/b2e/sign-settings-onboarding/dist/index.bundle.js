/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_cache,main_loader,sign_v2_b2e_submitDocumentInfo,sign_v2_helper,ui_wizard,sign_v2_api) {
	'use strict';

	var documentImage = "/bitrix/js/sign/v2/b2e/sign-settings-onboarding/dist/images/document-img.svg";

	var _templateObject, _templateObject2;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var emptyStateHelpdeskCode = 26311976;
	var _cache = /*#__PURE__*/new WeakMap();
	var _containerId = /*#__PURE__*/new WeakMap();
	var _wizard = /*#__PURE__*/new WeakMap();
	var _template = /*#__PURE__*/new WeakMap();
	var _api = /*#__PURE__*/new WeakMap();
	var _hasCompanies = /*#__PURE__*/new WeakMap();
	var _getCompaniesNotFoundEmptyState = /*#__PURE__*/new WeakSet();
	var _getStepsMetadata = /*#__PURE__*/new WeakSet();
	var _getSubmitDocumentInfoStep = /*#__PURE__*/new WeakSet();
	var _enableNoStepMode = /*#__PURE__*/new WeakSet();
	var _getLayout = /*#__PURE__*/new WeakSet();
	var B2EOnboardingSignSettings = /*#__PURE__*/function () {
	  function B2EOnboardingSignSettings() {
	    var containerId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	    var hasCompanies = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	    babelHelpers.classCallCheck(this, B2EOnboardingSignSettings);
	    _classPrivateMethodInitSpec(this, _getLayout);
	    _classPrivateMethodInitSpec(this, _enableNoStepMode);
	    _classPrivateMethodInitSpec(this, _getSubmitDocumentInfoStep);
	    _classPrivateMethodInitSpec(this, _getStepsMetadata);
	    _classPrivateMethodInitSpec(this, _getCompaniesNotFoundEmptyState);
	    _classPrivateFieldInitSpec(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    _classPrivateFieldInitSpec(this, _containerId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _wizard, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _template, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	    _classPrivateFieldInitSpec(this, _hasCompanies, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _hasCompanies, hasCompanies);
	    babelHelpers.classPrivateFieldSet(this, _containerId, containerId);
	    var currentSlider = BX.SidePanel.Instance.getTopSlider();
	    babelHelpers.classPrivateFieldSet(this, _wizard, new ui_wizard.Wizard(_classPrivateMethodGet(this, _getStepsMetadata, _getStepsMetadata2).call(this, this), {
	      back: {
	        className: 'ui-btn-light-border'
	      },
	      next: {
	        className: 'ui-btn-success'
	      },
	      complete: {
	        className: 'ui-btn-success',
	        title: main_core.Loc.getMessage('SIGN_SETTINGS_ONBOARDING_COMPLETE_TITLE'),
	        onComplete: function onComplete() {
	          return currentSlider === null || currentSlider === void 0 ? void 0 : currentSlider.close();
	        }
	      },
	      swapButtons: true
	    }));
	  }
	  babelHelpers.createClass(B2EOnboardingSignSettings, [{
	    key: "renderToContainer",
	    value: function () {
	      var _renderToContainer = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(container) {
	        var loader, response;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              if (!main_core.Type.isNull(container)) {
	                _context.next = 2;
	                break;
	              }
	              return _context.abrupt("return");
	            case 2:
	              loader = new main_loader.Loader({
	                target: container
	              });
	              void loader.show();
	              if (babelHelpers.classPrivateFieldGet(this, _hasCompanies)) {
	                _context.next = 8;
	                break;
	              }
	              main_core.Dom.append(_classPrivateMethodGet(this, _getCompaniesNotFoundEmptyState, _getCompaniesNotFoundEmptyState2).call(this), container);
	              void loader.hide();
	              return _context.abrupt("return");
	            case 8:
	              response = null;
	              _context.prev = 9;
	              _context.next = 12;
	              return babelHelpers.classPrivateFieldGet(this, _api).template.installOnboardingTemplate();
	            case 12:
	              response = _context.sent;
	              _context.next = 20;
	              break;
	            case 15:
	              _context.prev = 15;
	              _context.t0 = _context["catch"](9);
	              main_core.Dom.append(_classPrivateMethodGet(this, _getCompaniesNotFoundEmptyState, _getCompaniesNotFoundEmptyState2).call(this), container);
	              void loader.hide();
	              return _context.abrupt("return");
	            case 20:
	              babelHelpers.classPrivateFieldSet(this, _template, response.template);
	              void loader.hide();
	              main_core.Dom.append(_classPrivateMethodGet(this, _getLayout, _getLayout2).call(this), container);
	              babelHelpers.classPrivateFieldGet(this, _wizard).moveOnStep(0);
	            case 24:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this, [[9, 15]]);
	      }));
	      function renderToContainer(_x) {
	        return _renderToContainer.apply(this, arguments);
	      }
	      return renderToContainer;
	    }()
	  }]);
	  return B2EOnboardingSignSettings;
	}();
	function _getCompaniesNotFoundEmptyState2() {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-settings__scope sign-settings --b2e --employee\">\n\t\t\t\t<div class=\"sign-settings__sidebar\">\n\t\t\t\t\t<div class=\"sign-settings__empty-state\">\n\t\t\t\t\t\t<div class=\"sign-settings__empty-state_icon\">\n\t\t\t\t\t\t\t<img src=\"", "\" alt=\"\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<p class=\"sign-settings__empty-state_title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</p>\n\t\t\t\t\t\t<p class=\"sign-settings__empty-state_text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</p>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), documentImage, main_core.Loc.getMessage('SIGN_SETTINGS_ONBOARDING_EMPTY_STATE_TITLE'), sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_SETTINGS_ONBOARDING_EMPTY_STATE_DESCRIPTION'), emptyStateHelpdeskCode, sign_v2_helper.Helpdesk.defaultRedirectValue, ['sign-settings__empty-state_link']));
	}
	function _getStepsMetadata2(signSettings) {
	  return {
	    submitDocumentInfo: _classPrivateMethodGet(this, _getSubmitDocumentInfoStep, _getSubmitDocumentInfoStep2).call(this, signSettings)
	  };
	}
	function _getSubmitDocumentInfoStep2(signSettings) {
	  var submitDocumentInfo = null;
	  return {
	    get content() {
	      submitDocumentInfo = new sign_v2_b2e_submitDocumentInfo.SubmitDocumentInfo({
	        template: {
	          uid: babelHelpers.classPrivateFieldGet(signSettings, _template).uid,
	          title: babelHelpers.classPrivateFieldGet(signSettings, _template).title
	        },
	        company: babelHelpers.classPrivateFieldGet(signSettings, _template).company,
	        fields: [],
	        isOnboarding: true
	      });
	      _classPrivateMethodGet(signSettings, _enableNoStepMode, _enableNoStepMode2).call(signSettings);
	      return submitDocumentInfo.getLayout();
	    },
	    beforeCompletion: function () {
	      var _beforeCompletion = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
	        var result;
	        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              result = false;
	              _context2.prev = 1;
	              _context2.next = 4;
	              return submitDocumentInfo.sendForSign();
	            case 4:
	              result = _context2.sent;
	              _context2.next = 11;
	              break;
	            case 7:
	              _context2.prev = 7;
	              _context2.t0 = _context2["catch"](1);
	              console.error(_context2.t0);
	              result = false;
	            case 11:
	              return _context2.abrupt("return", result);
	            case 12:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2, null, [[1, 7]]);
	      }));
	      function beforeCompletion() {
	        return _beforeCompletion.apply(this, arguments);
	      }
	      return beforeCompletion;
	    }()
	  };
	}
	function _enableNoStepMode2() {
	  main_core.Dom.addClass(_classPrivateMethodGet(this, _getLayout, _getLayout2).call(this), 'no-step-mode');
	}
	function _getLayout2() {
	  var _this = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('headLayout', function () {
	    return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-settings__scope sign-settings --b2e\">\n\t\t\t\t\t<div class=\"sign-settings__sidebar\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(_this, _wizard).getLayout());
	  });
	}

	exports.B2EOnboardingSignSettings = B2EOnboardingSignSettings;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Cache,BX,BX.Sign.V2.B2e,BX.Sign.V2,BX.Ui,BX.Sign.V2));
