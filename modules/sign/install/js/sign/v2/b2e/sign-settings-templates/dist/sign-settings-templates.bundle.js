/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_cache,main_core_events,main_date,ui_vue3,main_sidepanel,ui_dialogs_messagebox,humanresources_hcmlink_dataMapper,ui_buttons,sign_v2_b2e_hcmLinkEmployeeSelector,sign_v2_b2e_documentTemplateSend,sign_v2_b2e_documentTemplateUserParty,sign_v2_api,sign_v2_b2e_documentTemplateFilling,ui_vue3_pinia,ui_wizard) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var useDocumentTemplateFillingStore = ui_vue3_pinia.defineStore('sign-b2e-document-template-filling-store', {
	  state: function state() {
	    return {
	      documents: [],
	      settings: {},
	      createdDocuments: [],
	      sendProgress: 0,
	      configured: false,
	      ruRegionFieldsVisible: false
	    };
	  },
	  actions: {
	    setDocuments: function setDocuments(documents) {
	      this.documents = documents;
	    },
	    setSettings: function setSettings(settings) {
	      this.settings = settings;
	    },
	    setCreatedDocuments: function setCreatedDocuments(documents) {
	      this.createdDocuments = documents;
	    },
	    setSendProgress: function setSendProgress(value) {
	      this.sendProgress = value;
	    },
	    setConfigured: function setConfigured(value) {
	      this.configured = value;
	    },
	    setRuRegionFieldsVisible: function setRuRegionFieldsVisible(value) {
	      this.ruRegionFieldsVisible = value;
	    },
	    removeDocument: function removeDocument(uid) {
	      this.setDocuments(this.documents.filter(function (doc) {
	        return doc.uid !== uid;
	      }));
	      var updatedSettings = _objectSpread({}, this.settings);
	      delete updatedSettings[uid];
	      this.setSettings(updatedSettings);
	    }
	  }
	});

	var _templateObject, _templateObject2, _templateObject3;
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _cache = /*#__PURE__*/new WeakMap();
	var _documentFilling = /*#__PURE__*/new WeakMap();
	var _documentUserParty = /*#__PURE__*/new WeakMap();
	var _documentSend = /*#__PURE__*/new WeakMap();
	var _templateIds = /*#__PURE__*/new WeakMap();
	var _store = /*#__PURE__*/new WeakMap();
	var _api = /*#__PURE__*/new WeakMap();
	var _sendLayout = /*#__PURE__*/new WeakMap();
	var _wizardLayout = /*#__PURE__*/new WeakMap();
	var _piniaInitStubApp = /*#__PURE__*/new WeakMap();
	var _sliderUrl = /*#__PURE__*/new WeakMap();
	var _bypassSliderCloseCheck = /*#__PURE__*/new WeakMap();
	var _container = /*#__PURE__*/new WeakMap();
	var _confirmPopup = /*#__PURE__*/new WeakMap();
	var _region = /*#__PURE__*/new WeakMap();
	var _getDocumentFillingStep = /*#__PURE__*/new WeakSet();
	var _createDocumentsAndSaveToStorageIfNotCreated = /*#__PURE__*/new WeakSet();
	var _updateCreatedDocumentsSettings = /*#__PURE__*/new WeakSet();
	var _updateDocumentSettings = /*#__PURE__*/new WeakSet();
	var _getEmployeeSelectionStep = /*#__PURE__*/new WeakSet();
	var _getStepsMetadata = /*#__PURE__*/new WeakSet();
	var _getLayout = /*#__PURE__*/new WeakSet();
	var _createHead = /*#__PURE__*/new WeakSet();
	var _showSendLayout = /*#__PURE__*/new WeakSet();
	var _hideSendLayout = /*#__PURE__*/new WeakSet();
	var _configure = /*#__PURE__*/new WeakSet();
	var _waitForFillAndStartComplete = /*#__PURE__*/new WeakSet();
	var _sleep = /*#__PURE__*/new WeakSet();
	var _loadTemplates = /*#__PURE__*/new WeakSet();
	var _getDocumentsByTemplateIds = /*#__PURE__*/new WeakSet();
	var _closeTemplateGridSlider = /*#__PURE__*/new WeakSet();
	var _showCompleteNotification = /*#__PURE__*/new WeakSet();
	var _waitAllIntegrationMapped = /*#__PURE__*/new WeakSet();
	var _getFirstNotMappedIntegration = /*#__PURE__*/new WeakSet();
	var _waitIntegrationSync = /*#__PURE__*/new WeakSet();
	var _setSendProgress = /*#__PURE__*/new WeakSet();
	var _closeSlider = /*#__PURE__*/new WeakSet();
	var _isMasterSlider = /*#__PURE__*/new WeakSet();
	var _setConfigured = /*#__PURE__*/new WeakSet();
	var _showNotMappedPopup = /*#__PURE__*/new WeakSet();
	var _isConfigured = /*#__PURE__*/new WeakSet();
	var _waitAllIntegrationEmployeesSelected = /*#__PURE__*/new WeakSet();
	var _getFirstNotEmployeesSelectedIntegration = /*#__PURE__*/new WeakSet();
	var _isCreatedDocumentsHasIntegration = /*#__PURE__*/new WeakSet();
	var _getCreateDocumentsUids = /*#__PURE__*/new WeakSet();
	var _waitEmployeeSelect = /*#__PURE__*/new WeakSet();
	var _showNotSelectedEmployeePopup = /*#__PURE__*/new WeakSet();
	var _convertEmployeesToUserMap = /*#__PURE__*/new WeakSet();
	var _getCreatedDocumentUidsByHcmLinkId = /*#__PURE__*/new WeakSet();
	var _getHcmPopupOptions = /*#__PURE__*/new WeakSet();
	var _getHcmPopupLayout = /*#__PURE__*/new WeakSet();
	var _subscribeSliderCloseEvent = /*#__PURE__*/new WeakSet();
	var _unmountVueApps = /*#__PURE__*/new WeakSet();
	var _isNeedShowCloseConfirm = /*#__PURE__*/new WeakSet();
	var _showCloseConfirm = /*#__PURE__*/new WeakSet();
	var _closeConfimPopup = /*#__PURE__*/new WeakSet();
	var _isRuRegionFieldsVisible = /*#__PURE__*/new WeakSet();
	var B2ETemplatesSignSettings = /*#__PURE__*/function () {
	  function B2ETemplatesSignSettings() {
	    var _this = this;
	    var _templateIds2 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
	    var sliderUrl = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	    babelHelpers.classCallCheck(this, B2ETemplatesSignSettings);
	    _classPrivateMethodInitSpec(this, _isRuRegionFieldsVisible);
	    _classPrivateMethodInitSpec(this, _closeConfimPopup);
	    _classPrivateMethodInitSpec(this, _showCloseConfirm);
	    _classPrivateMethodInitSpec(this, _isNeedShowCloseConfirm);
	    _classPrivateMethodInitSpec(this, _unmountVueApps);
	    _classPrivateMethodInitSpec(this, _subscribeSliderCloseEvent);
	    _classPrivateMethodInitSpec(this, _getHcmPopupLayout);
	    _classPrivateMethodInitSpec(this, _getHcmPopupOptions);
	    _classPrivateMethodInitSpec(this, _getCreatedDocumentUidsByHcmLinkId);
	    _classPrivateMethodInitSpec(this, _convertEmployeesToUserMap);
	    _classPrivateMethodInitSpec(this, _showNotSelectedEmployeePopup);
	    _classPrivateMethodInitSpec(this, _waitEmployeeSelect);
	    _classPrivateMethodInitSpec(this, _getCreateDocumentsUids);
	    _classPrivateMethodInitSpec(this, _isCreatedDocumentsHasIntegration);
	    _classPrivateMethodInitSpec(this, _getFirstNotEmployeesSelectedIntegration);
	    _classPrivateMethodInitSpec(this, _waitAllIntegrationEmployeesSelected);
	    _classPrivateMethodInitSpec(this, _isConfigured);
	    _classPrivateMethodInitSpec(this, _showNotMappedPopup);
	    _classPrivateMethodInitSpec(this, _setConfigured);
	    _classPrivateMethodInitSpec(this, _isMasterSlider);
	    _classPrivateMethodInitSpec(this, _closeSlider);
	    _classPrivateMethodInitSpec(this, _setSendProgress);
	    _classPrivateMethodInitSpec(this, _waitIntegrationSync);
	    _classPrivateMethodInitSpec(this, _getFirstNotMappedIntegration);
	    _classPrivateMethodInitSpec(this, _waitAllIntegrationMapped);
	    _classPrivateMethodInitSpec(this, _showCompleteNotification);
	    _classPrivateMethodInitSpec(this, _closeTemplateGridSlider);
	    _classPrivateMethodInitSpec(this, _getDocumentsByTemplateIds);
	    _classPrivateMethodInitSpec(this, _loadTemplates);
	    _classPrivateMethodInitSpec(this, _sleep);
	    _classPrivateMethodInitSpec(this, _waitForFillAndStartComplete);
	    _classPrivateMethodInitSpec(this, _configure);
	    _classPrivateMethodInitSpec(this, _hideSendLayout);
	    _classPrivateMethodInitSpec(this, _showSendLayout);
	    _classPrivateMethodInitSpec(this, _createHead);
	    _classPrivateMethodInitSpec(this, _getLayout);
	    _classPrivateMethodInitSpec(this, _getStepsMetadata);
	    _classPrivateMethodInitSpec(this, _getEmployeeSelectionStep);
	    _classPrivateMethodInitSpec(this, _updateDocumentSettings);
	    _classPrivateMethodInitSpec(this, _updateCreatedDocumentsSettings);
	    _classPrivateMethodInitSpec(this, _createDocumentsAndSaveToStorageIfNotCreated);
	    _classPrivateMethodInitSpec(this, _getDocumentFillingStep);
	    _classPrivateFieldInitSpec(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    _classPrivateFieldInitSpec(this, _documentFilling, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _documentUserParty, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _documentSend, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _templateIds, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _store, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _sendLayout, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _wizardLayout, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _piniaInitStubApp, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _sliderUrl, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _bypassSliderCloseCheck, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _container, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _confirmPopup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _region, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _region, main_core.Extension.getSettings('sign.v2.b2e.sign-settings-templates').get('region'));
	    babelHelpers.classPrivateFieldSet(this, _templateIds, _templateIds2);
	    babelHelpers.classPrivateFieldSet(this, _store, ui_vue3_pinia.createPinia());
	    babelHelpers.classPrivateFieldSet(this, _api, new sign_v2_api.Api());
	    babelHelpers.classPrivateFieldSet(this, _piniaInitStubApp, ui_vue3.BitrixVue.createApp({}));
	    babelHelpers.classPrivateFieldGet(this, _piniaInitStubApp).use(babelHelpers.classPrivateFieldGet(this, _store));
	    useDocumentTemplateFillingStore().setRuRegionFieldsVisible(_classPrivateMethodGet(this, _isRuRegionFieldsVisible, _isRuRegionFieldsVisible2).call(this));
	    babelHelpers.classPrivateFieldSet(this, _documentSend, new sign_v2_b2e_documentTemplateSend.DocumentTemplateSend(babelHelpers.classPrivateFieldGet(this, _store)));
	    babelHelpers.classPrivateFieldGet(this, _documentSend).subscribe('close', function () {
	      return _classPrivateMethodGet(_this, _closeSlider, _closeSlider2).call(_this);
	    });
	    babelHelpers.classPrivateFieldSet(this, _documentFilling, new sign_v2_b2e_documentTemplateFilling.DocumentTemplateFilling({
	      store: babelHelpers.classPrivateFieldGet(this, _store)
	    }));
	    babelHelpers.classPrivateFieldSet(this, _documentUserParty, new sign_v2_b2e_documentTemplateUserParty.DocumentTemplateUserParty());
	    babelHelpers.classPrivateFieldSet(this, _sliderUrl, sliderUrl);
	    _classPrivateMethodGet(this, _subscribeSliderCloseEvent, _subscribeSliderCloseEvent2).call(this);
	  }
	  babelHelpers.createClass(B2ETemplatesSignSettings, [{
	    key: "renderToContainer",
	    value: function () {
	      var _renderToContainer = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(container) {
	        var _this2 = this;
	        var wizard;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              if (!main_core.Type.isNull(container)) {
	                _context.next = 2;
	                break;
	              }
	              return _context.abrupt("return");
	            case 2:
	              babelHelpers.classPrivateFieldSet(this, _container, container);
	              _context.next = 5;
	              return _classPrivateMethodGet(this, _loadTemplates, _loadTemplates2).call(this);
	            case 5:
	              wizard = new ui_wizard.Wizard(_classPrivateMethodGet(this, _getStepsMetadata, _getStepsMetadata2).call(this), {
	                back: {
	                  className: 'ui-btn-light-border'
	                },
	                next: {
	                  className: 'ui-btn-success'
	                },
	                complete: {
	                  className: 'ui-btn-success',
	                  title: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_COMPLETE_TITLE'),
	                  onComplete: function onComplete() {
	                    return _classPrivateMethodGet(_this2, _closeSlider, _closeSlider2).call(_this2);
	                  }
	                },
	                cancel: {
	                  className: 'ui-btn-light-border',
	                  onCancel: function onCancel() {
	                    return _classPrivateMethodGet(_this2, _closeSlider, _closeSlider2).call(_this2);
	                  }
	                },
	                swapButtons: true
	              });
	              main_core.Dom.append(_classPrivateMethodGet(this, _getLayout, _getLayout2).call(this, wizard), container);
	              wizard.moveOnStep(0);
	            case 8:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function renderToContainer(_x) {
	        return _renderToContainer.apply(this, arguments);
	      }
	      return renderToContainer;
	    }()
	  }]);
	  return B2ETemplatesSignSettings;
	}();
	function _getDocumentFillingStep2() {
	  var documentFilling = babelHelpers.classPrivateFieldGet(this, _documentFilling);
	  return {
	    get content() {
	      return documentFilling.getLayout();
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_STEP_DOCUMENT_TITLE'),
	    beforeCompletion: function beforeCompletion() {
	      return documentFilling.validate();
	    }
	  };
	}
	function _createDocumentsAndSaveToStorageIfNotCreated2() {
	  return _createDocumentsAndSaveToStorageIfNotCreated3.apply(this, arguments);
	}
	function _createDocumentsAndSaveToStorageIfNotCreated3() {
	  _createDocumentsAndSaveToStorageIfNotCreated3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
	    var store, documents, templateIds, _yield$babelHelpers$c, items;
	    return _regeneratorRuntime().wrap(function _callee3$(_context3) {
	      while (1) switch (_context3.prev = _context3.next) {
	        case 0:
	          store = useDocumentTemplateFillingStore();
	          if (!(store.createdDocuments.length > 0)) {
	            _context3.next = 3;
	            break;
	          }
	          return _context3.abrupt("return");
	        case 3:
	          documents = store.documents;
	          templateIds = documents.map(function (document) {
	            return document.templateId;
	          });
	          _context3.next = 7;
	          return babelHelpers.classPrivateFieldGet(this, _api).template.registerDocuments(templateIds, babelHelpers.classPrivateFieldGet(this, _documentUserParty).isRejectExcludedEnabled());
	        case 7:
	          _yield$babelHelpers$c = _context3.sent;
	          items = _yield$babelHelpers$c.items;
	          store.setCreatedDocuments(items);
	        case 10:
	        case "end":
	          return _context3.stop();
	      }
	    }, _callee3, this);
	  }));
	  return _createDocumentsAndSaveToStorageIfNotCreated3.apply(this, arguments);
	}
	function _updateCreatedDocumentsSettings2() {
	  return _updateCreatedDocumentsSettings3.apply(this, arguments);
	}
	function _updateCreatedDocumentsSettings3() {
	  _updateCreatedDocumentsSettings3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4() {
	    var _this9 = this;
	    var store, templateDocuments, settings, createdDocuments, _loop, _i, _Object$entries;
	    return _regeneratorRuntime().wrap(function _callee4$(_context5) {
	      while (1) switch (_context5.prev = _context5.next) {
	        case 0:
	          store = useDocumentTemplateFillingStore();
	          templateDocuments = store.documents;
	          settings = store.settings;
	          createdDocuments = store.createdDocuments;
	          _loop = /*#__PURE__*/_regeneratorRuntime().mark(function _loop() {
	            var _Object$entries$_i, templateDocumentUid, documentSettings, templateDocument, templateId, templateCreatedDocument;
	            return _regeneratorRuntime().wrap(function _loop$(_context4) {
	              while (1) switch (_context4.prev = _context4.next) {
	                case 0:
	                  _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2), templateDocumentUid = _Object$entries$_i[0], documentSettings = _Object$entries$_i[1];
	                  templateDocument = templateDocuments.find(function (document) {
	                    return document.uid === templateDocumentUid;
	                  });
	                  templateId = templateDocument === null || templateDocument === void 0 ? void 0 : templateDocument.templateId;
	                  if (templateId) {
	                    _context4.next = 5;
	                    break;
	                  }
	                  throw new Error('templateId not found');
	                case 5:
	                  templateCreatedDocument = createdDocuments.find(function (value) {
	                    return value.templateId === templateId;
	                  });
	                  if (templateCreatedDocument) {
	                    _context4.next = 8;
	                    break;
	                  }
	                  throw new Error('templateCreatedDocument not found');
	                case 8:
	                  _context4.next = 10;
	                  return _classPrivateMethodGet(_this9, _updateDocumentSettings, _updateDocumentSettings2).call(_this9, templateCreatedDocument.document, documentSettings);
	                case 10:
	                case "end":
	                  return _context4.stop();
	              }
	            }, _loop);
	          });
	          _i = 0, _Object$entries = Object.entries(settings);
	        case 6:
	          if (!(_i < _Object$entries.length)) {
	            _context5.next = 11;
	            break;
	          }
	          return _context5.delegateYield(_loop(), "t0", 8);
	        case 8:
	          _i++;
	          _context5.next = 6;
	          break;
	        case 11:
	        case "end":
	          return _context5.stop();
	      }
	    }, _callee4);
	  }));
	  return _updateCreatedDocumentsSettings3.apply(this, arguments);
	}
	function _updateDocumentSettings2(_x2, _x3) {
	  return _updateDocumentSettings3.apply(this, arguments);
	}
	function _updateDocumentSettings3() {
	  _updateDocumentSettings3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5(document, settings) {
	    var uid, formattedDate, tsFromUserTime;
	    return _regeneratorRuntime().wrap(function _callee5$(_context6) {
	      while (1) switch (_context6.prev = _context6.next) {
	        case 0:
	          uid = document.uid;
	          if (!(_classPrivateMethodGet(this, _isRuRegionFieldsVisible, _isRuRegionFieldsVisible2).call(this) && settings.registrationNumber.length > 0 && document.externalIdSourceType !== 'hcmlink')) {
	            _context6.next = 4;
	            break;
	          }
	          _context6.next = 4;
	          return babelHelpers.classPrivateFieldGet(this, _api).changeExternalId(uid, settings.registrationNumber);
	        case 4:
	          if (!(_classPrivateMethodGet(this, _isRuRegionFieldsVisible, _isRuRegionFieldsVisible2).call(this) && document.externalDateCreateSourceType !== 'hcmlink')) {
	            _context6.next = 8;
	            break;
	          }
	          formattedDate = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('SHORT_DATE_FORMAT'), settings.creationDate);
	          _context6.next = 8;
	          return babelHelpers.classPrivateFieldGet(this, _api).changeExternalDate(uid, formattedDate);
	        case 8:
	          tsFromUserTime = main_date.Timezone.UserTime.toUTCTimestamp(settings.signingDate);
	          _context6.next = 11;
	          return babelHelpers.classPrivateFieldGet(this, _api).modifyDateSignUntil(uid, tsFromUserTime);
	        case 11:
	        case "end":
	          return _context6.stop();
	      }
	    }, _callee5, this);
	  }));
	  return _updateDocumentSettings3.apply(this, arguments);
	}
	function _getEmployeeSelectionStep2() {
	  var _this3 = this;
	  var userParty = babelHelpers.classPrivateFieldGet(this, _documentUserParty);
	  return {
	    get content() {
	      return userParty.getLayout();
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_STEP_EMPLOYEES_TITLE'),
	    beforeCompletion: function () {
	      var _beforeCompletion = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
	        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              if (userParty.validate()) {
	                _context2.next = 2;
	                break;
	              }
	              return _context2.abrupt("return", false);
	            case 2:
	              _context2.prev = 2;
	              _classPrivateMethodGet(_this3, _showSendLayout, _showSendLayout2).call(_this3);
	              _context2.next = 6;
	              return _classPrivateMethodGet(_this3, _createDocumentsAndSaveToStorageIfNotCreated, _createDocumentsAndSaveToStorageIfNotCreated2).call(_this3);
	            case 6:
	              _context2.next = 8;
	              return _classPrivateMethodGet(_this3, _updateCreatedDocumentsSettings, _updateCreatedDocumentsSettings2).call(_this3);
	            case 8:
	              _context2.next = 10;
	              return babelHelpers.classPrivateFieldGet(_this3, _documentUserParty).syncMembers();
	            case 10:
	              _context2.next = 12;
	              return _classPrivateMethodGet(_this3, _waitAllIntegrationMapped, _waitAllIntegrationMapped2).call(_this3);
	            case 12:
	              _context2.next = 14;
	              return _classPrivateMethodGet(_this3, _waitAllIntegrationEmployeesSelected, _waitAllIntegrationEmployeesSelected2).call(_this3);
	            case 14:
	              _context2.next = 16;
	              return _classPrivateMethodGet(_this3, _configure, _configure2).call(_this3);
	            case 16:
	              _classPrivateMethodGet(_this3, _setConfigured, _setConfigured2).call(_this3);
	              _context2.next = 19;
	              return _classPrivateMethodGet(_this3, _waitForFillAndStartComplete, _waitForFillAndStartComplete2).call(_this3);
	            case 19:
	              _classPrivateMethodGet(_this3, _closeTemplateGridSlider, _closeTemplateGridSlider2).call(_this3);
	              _classPrivateMethodGet(_this3, _showCompleteNotification, _showCompleteNotification2).call(_this3);
	              return _context2.abrupt("return", true);
	            case 24:
	              _context2.prev = 24;
	              _context2.t0 = _context2["catch"](2);
	              if (_context2.t0) {
	                console.error(_context2.t0);
	              }
	              if (!_classPrivateMethodGet(_this3, _isConfigured, _isConfigured2).call(_this3)) {
	                _context2.next = 29;
	                break;
	              }
	              return _context2.abrupt("return", true);
	            case 29:
	              _classPrivateMethodGet(_this3, _hideSendLayout, _hideSendLayout2).call(_this3);
	              return _context2.abrupt("return", false);
	            case 31:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2, null, [[2, 24]]);
	      }));
	      function beforeCompletion() {
	        return _beforeCompletion.apply(this, arguments);
	      }
	      return beforeCompletion;
	    }()
	  };
	}
	function _getStepsMetadata2() {
	  return {
	    documentFillingStep: _classPrivateMethodGet(this, _getDocumentFillingStep, _getDocumentFillingStep2).call(this),
	    employeeSelectionStep: _classPrivateMethodGet(this, _getEmployeeSelectionStep, _getEmployeeSelectionStep2).call(this)
	  };
	}
	function _getLayout2(wizard) {
	  babelHelpers.classPrivateFieldSet(this, _wizardLayout, wizard.getLayout());
	  babelHelpers.classPrivateFieldSet(this, _sendLayout, babelHelpers.classPrivateFieldGet(this, _documentSend).getLayout());
	  main_core.Dom.hide(babelHelpers.classPrivateFieldGet(this, _sendLayout));
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-settings__scope sign-settings --no-background --b2e --templates\">\n\t\t\t\t<div class=\"sign-settings__sidebar\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet(this, _createHead, _createHead2).call(this), babelHelpers.classPrivateFieldGet(this, _wizardLayout), babelHelpers.classPrivateFieldGet(this, _sendLayout));
	}
	function _createHead2() {
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('headLayout', function () {
	    return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-settings__head\">\n\t\t\t\t\t<div>\n\t\t\t\t\t\t<p class=\"sign-settings__head_title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</p>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_HEAD_TITLE'));
	  });
	}
	function _showSendLayout2() {
	  _classPrivateMethodGet(this, _setSendProgress, _setSendProgress2).call(this, 0);
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _wizardLayout), 'display', 'none');
	  main_core.Dom.show(babelHelpers.classPrivateFieldGet(this, _sendLayout));
	}
	function _hideSendLayout2() {
	  main_core.Dom.hide(babelHelpers.classPrivateFieldGet(this, _sendLayout));
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _wizardLayout), 'display', 'block');
	}
	function _configure2() {
	  return _configure3.apply(this, arguments);
	}
	function _configure3() {
	  _configure3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6() {
	    var createdDocuments, _iterator, _step, createdDocument;
	    return _regeneratorRuntime().wrap(function _callee6$(_context7) {
	      while (1) switch (_context7.prev = _context7.next) {
	        case 0:
	          createdDocuments = useDocumentTemplateFillingStore().createdDocuments;
	          _iterator = _createForOfIteratorHelper(createdDocuments);
	          _context7.prev = 2;
	          _iterator.s();
	        case 4:
	          if ((_step = _iterator.n()).done) {
	            _context7.next = 10;
	            break;
	          }
	          createdDocument = _step.value;
	          _context7.next = 8;
	          return babelHelpers.classPrivateFieldGet(this, _api).configureDocument(createdDocument.document.uid);
	        case 8:
	          _context7.next = 4;
	          break;
	        case 10:
	          _context7.next = 15;
	          break;
	        case 12:
	          _context7.prev = 12;
	          _context7.t0 = _context7["catch"](2);
	          _iterator.e(_context7.t0);
	        case 15:
	          _context7.prev = 15;
	          _iterator.f();
	          return _context7.finish(15);
	        case 18:
	        case "end":
	          return _context7.stop();
	      }
	    }, _callee6, this, [[2, 12, 15, 18]]);
	  }));
	  return _configure3.apply(this, arguments);
	}
	function _waitForFillAndStartComplete2() {
	  return _waitForFillAndStartComplete3.apply(this, arguments);
	}
	function _waitForFillAndStartComplete3() {
	  _waitForFillAndStartComplete3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee7() {
	    var createdDocuments, ids, completed, result;
	    return _regeneratorRuntime().wrap(function _callee7$(_context8) {
	      while (1) switch (_context8.prev = _context8.next) {
	        case 0:
	          createdDocuments = useDocumentTemplateFillingStore().createdDocuments;
	          ids = createdDocuments.map(function (value) {
	            return value.document.id;
	          });
	          completed = false;
	        case 3:
	          if (completed) {
	            _context8.next = 13;
	            break;
	          }
	          _context8.next = 6;
	          return babelHelpers.classPrivateFieldGet(this, _api).getManyDocumentFillAndStartProgress(ids);
	        case 6:
	          result = _context8.sent;
	          completed = result.completed;
	          _classPrivateMethodGet(this, _setSendProgress, _setSendProgress2).call(this, Math.round(result.progress));
	          // eslint-disable-next-line no-await-in-loop
	          _context8.next = 11;
	          return _classPrivateMethodGet(this, _sleep, _sleep2).call(this, 1000);
	        case 11:
	          _context8.next = 3;
	          break;
	        case 13:
	        case "end":
	          return _context8.stop();
	      }
	    }, _callee7, this);
	  }));
	  return _waitForFillAndStartComplete3.apply(this, arguments);
	}
	function _sleep2(ms) {
	  return new Promise(function (resolve) {
	    setTimeout(resolve, ms);
	  });
	}
	function _loadTemplates2() {
	  return _loadTemplates3.apply(this, arguments);
	}
	function _loadTemplates3() {
	  _loadTemplates3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee8() {
	    var templateDocuments;
	    return _regeneratorRuntime().wrap(function _callee8$(_context9) {
	      while (1) switch (_context9.prev = _context9.next) {
	        case 0:
	          _context9.next = 2;
	          return _classPrivateMethodGet(this, _getDocumentsByTemplateIds, _getDocumentsByTemplateIds2).call(this, babelHelpers.classPrivateFieldGet(this, _templateIds));
	        case 2:
	          templateDocuments = _context9.sent;
	          useDocumentTemplateFillingStore().setDocuments(templateDocuments);
	        case 4:
	        case "end":
	          return _context9.stop();
	      }
	    }, _callee8, this);
	  }));
	  return _loadTemplates3.apply(this, arguments);
	}
	function _getDocumentsByTemplateIds2(_x4) {
	  return _getDocumentsByTemplateIds3.apply(this, arguments);
	}
	function _getDocumentsByTemplateIds3() {
	  _getDocumentsByTemplateIds3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee9(templateIds) {
	    return _regeneratorRuntime().wrap(function _callee9$(_context10) {
	      while (1) switch (_context10.prev = _context10.next) {
	        case 0:
	          return _context10.abrupt("return", babelHelpers.classPrivateFieldGet(this, _api).loadDocumentsByTemplateIds(templateIds));
	        case 1:
	        case "end":
	          return _context10.stop();
	      }
	    }, _callee9, this);
	  }));
	  return _getDocumentsByTemplateIds3.apply(this, arguments);
	}
	function _closeTemplateGridSlider2() {
	  var prevSlider = window.top.BX.SidePanel.Instance.getPreviousSlider();
	  if (prevSlider && prevSlider.getUrl().indexOf('templates/folder/?folderId=')) {
	    prevSlider.close();
	  }
	}
	function _showCompleteNotification2() {
	  var notificationText = babelHelpers.classPrivateFieldGet(this, _templateIds).length > 1 ? main_core.Loc.getMessage('SIGN_SETTINGS_COMPLETE_NOTIFICATION_TEXT_GROUP') : main_core.Loc.getMessage('SIGN_SETTINGS_COMPLETE_NOTIFICATION_TEXT');
	  window.top.BX.UI.Notification.Center.notify({
	    content: notificationText,
	    autoHideDelay: 4000
	  });
	}
	function _waitAllIntegrationMapped2() {
	  return _waitAllIntegrationMapped3.apply(this, arguments);
	}
	function _waitAllIntegrationMapped3() {
	  _waitAllIntegrationMapped3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee10() {
	    var notMapped;
	    return _regeneratorRuntime().wrap(function _callee10$(_context11) {
	      while (1) switch (_context11.prev = _context11.next) {
	        case 0:
	          _context11.next = 3;
	          return _classPrivateMethodGet(this, _getFirstNotMappedIntegration, _getFirstNotMappedIntegration2).call(this);
	        case 3:
	          notMapped = _context11.sent;
	          if (!notMapped) {
	            _context11.next = 9;
	            break;
	          }
	          _context11.next = 7;
	          return _classPrivateMethodGet(this, _waitIntegrationSync, _waitIntegrationSync2).call(this, notMapped);
	        case 7:
	          _context11.next = 10;
	          break;
	        case 9:
	          return _context11.abrupt("break", 12);
	        case 10:
	          _context11.next = 0;
	          break;
	        case 12:
	        case "end":
	          return _context11.stop();
	      }
	    }, _callee10, this);
	  }));
	  return _waitAllIntegrationMapped3.apply(this, arguments);
	}
	function _getFirstNotMappedIntegration2() {
	  return _getFirstNotMappedIntegration3.apply(this, arguments);
	}
	function _getFirstNotMappedIntegration3() {
	  _getFirstNotMappedIntegration3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee11() {
	    var createdDocumentUids, integrations;
	    return _regeneratorRuntime().wrap(function _callee11$(_context12) {
	      while (1) switch (_context12.prev = _context12.next) {
	        case 0:
	          if (_classPrivateMethodGet(this, _isCreatedDocumentsHasIntegration, _isCreatedDocumentsHasIntegration2).call(this)) {
	            _context12.next = 2;
	            break;
	          }
	          return _context12.abrupt("return", null);
	        case 2:
	          createdDocumentUids = _classPrivateMethodGet(this, _getCreateDocumentsUids, _getCreateDocumentsUids2).call(this);
	          _context12.next = 5;
	          return babelHelpers.classPrivateFieldGet(this, _api).checkNotMappedMembersHrIntegrationByDocuments(createdDocumentUids);
	        case 5:
	          integrations = _context12.sent;
	          return _context12.abrupt("return", integrations.find(function (integration) {
	            return integration.userIds.length > 0;
	          }));
	        case 7:
	        case "end":
	          return _context12.stop();
	      }
	    }, _callee11, this);
	  }));
	  return _getFirstNotMappedIntegration3.apply(this, arguments);
	}
	function _waitIntegrationSync2(integration) {
	  var _this4 = this;
	  return new Promise(function (resolve, reject) {
	    _classPrivateMethodGet(_this4, _showNotMappedPopup, _showNotMappedPopup2).call(_this4, resolve, reject, integration);
	  });
	}
	function _setSendProgress2(value) {
	  useDocumentTemplateFillingStore().setSendProgress(value);
	}
	function _closeSlider2() {
	  var slider = BX.SidePanel.Instance.getTopSlider();
	  if (slider && _classPrivateMethodGet(this, _isMasterSlider, _isMasterSlider2).call(this, slider)) {
	    slider.close();
	  }
	}
	function _isMasterSlider2(slider) {
	  return /sign-b2e-templates-settings-\d+-(template|folder)/.test(slider.getUrl());
	}
	function _setConfigured2() {
	  var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	  useDocumentTemplateFillingStore().setConfigured(value);
	}
	function _showNotMappedPopup2(resolve, reject, integration) {
	  var shouldPopupCloseReject = true;
	  var popup = ui_dialogs_messagebox.MessageBox.create({
	    message: _classPrivateMethodGet(this, _getHcmPopupLayout, _getHcmPopupLayout2).call(this, main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_NOT_MAPPED_POPUP_TITLE'), main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_NOT_MAPPED_POPUP_DESCRIPTION')),
	    buttons: [new ui_buttons.Button({
	      text: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_NOT_MAPPED_POPUP_OK'),
	      size: ui_buttons.ButtonSize.S,
	      color: ui_buttons.ButtonColor.PRIMARY,
	      round: true,
	      onclick: function onclick() {
	        shouldPopupCloseReject = false;
	        popup.close();
	        humanresources_hcmlink_dataMapper.Mapper.openSlider({
	          companyId: integration.integrationId,
	          userIds: new Set(integration.allUserIds),
	          mode: humanresources_hcmlink_dataMapper.Mapper.MODE_DIRECT
	        }, {
	          onCloseHandler: function onCloseHandler() {
	            return resolve();
	          }
	        });
	      }
	    }), new ui_buttons.Button({
	      text: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_NOT_MAPPED_POPUP_CANCEL'),
	      size: ui_buttons.ButtonSize.S,
	      color: ui_buttons.ButtonColor.LIGHT_BORDER,
	      round: true,
	      onclick: function onclick() {
	        return popup.close();
	      }
	    })],
	    modal: false,
	    popupOptions: _objectSpread$1({
	      events: {
	        onPopupClose: function onPopupClose() {
	          if (shouldPopupCloseReject) {
	            reject();
	          }
	        }
	      }
	    }, _classPrivateMethodGet(this, _getHcmPopupOptions, _getHcmPopupOptions2).call(this))
	  });
	  popup.show();
	}
	function _isConfigured2() {
	  return useDocumentTemplateFillingStore().configured;
	}
	function _waitAllIntegrationEmployeesSelected2() {
	  return _waitAllIntegrationEmployeesSelected3.apply(this, arguments);
	}
	function _waitAllIntegrationEmployeesSelected3() {
	  _waitAllIntegrationEmployeesSelected3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee12() {
	    var notSelected;
	    return _regeneratorRuntime().wrap(function _callee12$(_context13) {
	      while (1) switch (_context13.prev = _context13.next) {
	        case 0:
	          _context13.next = 3;
	          return _classPrivateMethodGet(this, _getFirstNotEmployeesSelectedIntegration, _getFirstNotEmployeesSelectedIntegration2).call(this);
	        case 3:
	          notSelected = _context13.sent;
	          if (!notSelected) {
	            _context13.next = 9;
	            break;
	          }
	          _context13.next = 7;
	          return _classPrivateMethodGet(this, _waitEmployeeSelect, _waitEmployeeSelect2).call(this, notSelected);
	        case 7:
	          _context13.next = 10;
	          break;
	        case 9:
	          return _context13.abrupt("break", 12);
	        case 10:
	          _context13.next = 0;
	          break;
	        case 12:
	        case "end":
	          return _context13.stop();
	      }
	    }, _callee12, this);
	  }));
	  return _waitAllIntegrationEmployeesSelected3.apply(this, arguments);
	}
	function _getFirstNotEmployeesSelectedIntegration2() {
	  return _getFirstNotEmployeesSelectedIntegration3.apply(this, arguments);
	}
	function _getFirstNotEmployeesSelectedIntegration3() {
	  _getFirstNotEmployeesSelectedIntegration3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee13() {
	    var createdDocumentUids, integrations;
	    return _regeneratorRuntime().wrap(function _callee13$(_context14) {
	      while (1) switch (_context14.prev = _context14.next) {
	        case 0:
	          if (_classPrivateMethodGet(this, _isCreatedDocumentsHasIntegration, _isCreatedDocumentsHasIntegration2).call(this)) {
	            _context14.next = 2;
	            break;
	          }
	          return _context14.abrupt("return", null);
	        case 2:
	          createdDocumentUids = _classPrivateMethodGet(this, _getCreateDocumentsUids, _getCreateDocumentsUids2).call(this);
	          _context14.next = 5;
	          return babelHelpers.classPrivateFieldGet(this, _api).loadBulkMultipleVacancyMemberHrIntegrations(createdDocumentUids);
	        case 5:
	          integrations = _context14.sent;
	          return _context14.abrupt("return", integrations.find(function (integration) {
	            return integration.employees.length > 0;
	          }));
	        case 7:
	        case "end":
	          return _context14.stop();
	      }
	    }, _callee13, this);
	  }));
	  return _getFirstNotEmployeesSelectedIntegration3.apply(this, arguments);
	}
	function _isCreatedDocumentsHasIntegration2() {
	  var store = useDocumentTemplateFillingStore();
	  var createdDocuments = store.createdDocuments;
	  return createdDocuments.some(function (value) {
	    return value.document.hcmLinkCompanyId > 0;
	  });
	}
	function _getCreateDocumentsUids2() {
	  var store = useDocumentTemplateFillingStore();
	  var createdDocuments = store.createdDocuments;
	  return createdDocuments.map(function (value) {
	    return value.document.uid;
	  });
	}
	function _waitEmployeeSelect2(integration) {
	  var _this5 = this;
	  return new Promise(function (resolve, reject) {
	    _classPrivateMethodGet(_this5, _showNotSelectedEmployeePopup, _showNotSelectedEmployeePopup2).call(_this5, resolve, reject, integration);
	  });
	}
	function _showNotSelectedEmployeePopup2(resolve, reject, integration) {
	  var _this6 = this;
	  var shouldPopupCloseReject = true;
	  var popup = ui_dialogs_messagebox.MessageBox.create({
	    message: _classPrivateMethodGet(this, _getHcmPopupLayout, _getHcmPopupLayout2).call(this, main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_EMPLOYEES_NOT_SELECTED_POPUP_TITLE'), main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_EMPLOYEES_NOT_SELECTED_POPUP_DESCRIPTION')),
	    buttons: [new ui_buttons.Button({
	      text: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_EMPLOYEES_NOT_SELECTED_POPUP_OK'),
	      size: ui_buttons.ButtonSize.S,
	      color: ui_buttons.ButtonColor.PRIMARY,
	      round: true,
	      onclick: function onclick() {
	        shouldPopupCloseReject = false;
	        popup.close();
	        sign_v2_b2e_hcmLinkEmployeeSelector.HcmLinkVacancyChooser.openSlider({
	          api: babelHelpers.classPrivateFieldGet(_this6, _api),
	          documentGroupUids: _classPrivateMethodGet(_this6, _getCreatedDocumentUidsByHcmLinkId, _getCreatedDocumentUidsByHcmLinkId2).call(_this6, integration.company.id),
	          employees: _classPrivateMethodGet(_this6, _convertEmployeesToUserMap, _convertEmployeesToUserMap2).call(_this6, integration.employees),
	          companyTitle: integration.company.title
	        }, {
	          onCloseHandler: function onCloseHandler() {
	            return resolve();
	          }
	        });
	      }
	    }), new ui_buttons.Button({
	      text: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_EMPLOYEES_NOT_SELECTED_POPUP_CANCEL'),
	      size: ui_buttons.ButtonSize.S,
	      color: ui_buttons.ButtonColor.LIGHT_BORDER,
	      round: true,
	      onclick: function onclick() {
	        return popup.close();
	      }
	    })],
	    modal: false,
	    popupOptions: _objectSpread$1({
	      events: {
	        onPopupClose: function onPopupClose() {
	          if (shouldPopupCloseReject) {
	            reject();
	          }
	        }
	      }
	    }, _classPrivateMethodGet(this, _getHcmPopupOptions, _getHcmPopupOptions2).call(this))
	  });
	  popup.show();
	}
	function _convertEmployeesToUserMap2(employees) {
	  var employeesMap = new Map();
	  employees.forEach(function (value) {
	    employeesMap.set(value.userId, value);
	  });
	  return employeesMap;
	}
	function _getCreatedDocumentUidsByHcmLinkId2(companyId) {
	  var store = useDocumentTemplateFillingStore();
	  var createdDocuments = store.createdDocuments;
	  return createdDocuments.filter(function (value) {
	    return value.document.hcmLinkCompanyId === companyId;
	  }).map(function (value) {
	    return value.document.uid;
	  });
	}
	function _getHcmPopupOptions2() {
	  return {
	    targetContainer: babelHelpers.classPrivateFieldGet(this, _container),
	    borderRadius: '20px',
	    padding: 0,
	    contentPadding: 24
	  };
	}
	function _getHcmPopupLayout2(title, description) {
	  return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"sign-settings-templates-hcm-popup-warning\"></div>\n\t\t\t\t<div class=\"sign-settings-templates-hcm-popup-title\">\n\t\t\t\t\t", "\t\t\t\n\t\t\t\t</div>\n\t\t\t\t<div class=\"sign-settings-templates-hcm-popup-description\">\n\t\t\t\t\t", "\t\t\t\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(title), main_core.Text.encode(description));
	}
	function _subscribeSliderCloseEvent2() {
	  var _this7 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _sliderUrl)) {
	    return;
	  }
	  var onClose = function onClose(event) {
	    var _event$getData = event.getData(),
	      _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	      eventDataItem = _event$getData2[0];
	    var slider = eventDataItem === null || eventDataItem === void 0 ? void 0 : eventDataItem.getSlider();
	    if (slider.getUrl() !== babelHelpers.classPrivateFieldGet(_this7, _sliderUrl)) {
	      return;
	    }
	    if (_classPrivateMethodGet(_this7, _isNeedShowCloseConfirm, _isNeedShowCloseConfirm2).call(_this7)) {
	      eventDataItem.denyAction();
	      _classPrivateMethodGet(_this7, _showCloseConfirm, _showCloseConfirm2).call(_this7, slider);
	      return;
	    }
	    main_core_events.EventEmitter.unsubscribe('SidePanel.Slider:onClose', onClose);
	    _classPrivateMethodGet(_this7, _unmountVueApps, _unmountVueApps2).call(_this7);
	    _classPrivateMethodGet(_this7, _closeConfimPopup, _closeConfimPopup2).call(_this7);
	  };
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', onClose);
	}
	function _unmountVueApps2() {
	  babelHelpers.classPrivateFieldGet(this, _documentFilling).unmount();
	  babelHelpers.classPrivateFieldGet(this, _documentSend).unmount();
	  babelHelpers.classPrivateFieldGet(this, _documentUserParty).unmount();
	}
	function _isNeedShowCloseConfirm2() {
	  if (babelHelpers.classPrivateFieldGet(this, _bypassSliderCloseCheck)) {
	    return false;
	  }
	  return _classPrivateMethodGet(this, _getCreateDocumentsUids, _getCreateDocumentsUids2).call(this).length === 0;
	}
	function _showCloseConfirm2(slider) {
	  var _this8 = this;
	  if (babelHelpers.classPrivateFieldGet(this, _confirmPopup) === null) {
	    babelHelpers.classPrivateFieldSet(this, _confirmPopup, new ui_dialogs_messagebox.MessageBox({
	      message: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_CLOSE_CONFIRM'),
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	      okCaption: main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_CLOSE_CONFIRM_OK'),
	      onOk: function onOk(messageBox) {
	        messageBox.close();
	        babelHelpers.classPrivateFieldSet(_this8, _bypassSliderCloseCheck, true);
	        slider.close();
	        babelHelpers.classPrivateFieldSet(_this8, _bypassSliderCloseCheck, false);
	      }
	    }));
	  }
	  babelHelpers.classPrivateFieldGet(this, _confirmPopup).show();
	}
	function _closeConfimPopup2() {
	  var _babelHelpers$classPr;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _confirmPopup)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.close();
	}
	function _isRuRegionFieldsVisible2() {
	  return babelHelpers.classPrivateFieldGet(this, _region) === 'ru';
	}

	exports.useDocumentTemplateFillingStore = useDocumentTemplateFillingStore;
	exports.B2ETemplatesSignSettings = B2ETemplatesSignSettings;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Cache,BX.Event,BX.Main,BX.Vue3,BX.SidePanel,BX.UI.Dialogs,BX.Humanresources.Hcmlink,BX.UI,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2,BX.Sign.V2.B2e,BX.Vue3.Pinia,BX.Ui));
