/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var Base = /*#__PURE__*/function () {
	  function Base(payload) {
	    babelHelpers.classCallCheck(this, Base);
	    babelHelpers.defineProperty(this, "payload", null);
	    babelHelpers.defineProperty(this, "markers", {});
	    this.payload = payload;
	  }
	  babelHelpers.createClass(Base, [{
	    key: "setMarkers",
	    value: function setMarkers(markers) {
	      this.markers = markers;
	      return this;
	    }
	  }, {
	    key: "getMarkers",
	    value: function getMarkers() {
	      return this.markers;
	    }
	    /**
	     * Returns data in pretty style.
	     *
	     * @return {*}
	     */
	  }, {
	    key: "getPrettifiedData",
	    value: function getPrettifiedData() {
	      return this.payload;
	    }
	    /**
	     * Returns data in raw style.
	     *
	     * @return {*}
	     */
	  }, {
	    key: "getRawData",
	    value: function getRawData() {
	      return this.payload;
	    }
	  }]);
	  return Base;
	}();

	var Text = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Text, _Base);
	  /**
	   *
	   * @param {TextPayload} payload
	   */
	  // eslint-disable-next-line no-useless-constructor
	  function Text(payload) {
	    babelHelpers.classCallCheck(this, Text);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Text).call(this, payload));
	  }
	  babelHelpers.createClass(Text, [{
	    key: "setMarkers",
	    value: function setMarkers(markers) {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "setMarkers", this).call(this, markers);
	    }
	  }, {
	    key: "getMarkers",
	    value: function getMarkers() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "getMarkers", this).call(this);
	    }
	  }, {
	    key: "getPrettifiedData",
	    value: function getPrettifiedData() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "getPrettifiedData", this).call(this);
	    }
	  }, {
	    key: "getRawData",
	    value: function getRawData() {
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Text.prototype), "getRawData", this).call(this);
	    }
	  }]);
	  return Text;
	}(Base);

	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var Base$1 = Base;
	var Text$1 = Text;
	var _moduleId = /*#__PURE__*/new WeakMap();
	var _contextId = /*#__PURE__*/new WeakMap();
	var _contextParameters = /*#__PURE__*/new WeakMap();
	var _payload = /*#__PURE__*/new WeakMap();
	var _historyState = /*#__PURE__*/new WeakMap();
	var _historyGroupId = /*#__PURE__*/new WeakMap();
	var _parameters = /*#__PURE__*/new WeakMap();
	var _analyticParameters = /*#__PURE__*/new WeakMap();
	var _addSystemParameters = /*#__PURE__*/new WeakSet();
	var _registerPullListener = /*#__PURE__*/new WeakSet();
	var _send = /*#__PURE__*/new WeakSet();
	var _isOffline = /*#__PURE__*/new WeakSet();
	var Engine = /*#__PURE__*/function () {
	  function Engine() {
	    babelHelpers.classCallCheck(this, Engine);
	    _classPrivateMethodInitSpec(this, _isOffline);
	    _classPrivateMethodInitSpec(this, _send);
	    _classPrivateMethodInitSpec(this, _registerPullListener);
	    _classPrivateMethodInitSpec(this, _addSystemParameters);
	    _classPrivateFieldInitSpec(this, _moduleId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _contextId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _contextParameters, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _payload, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _historyState, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _historyGroupId, {
	      writable: true,
	      value: -1
	    });
	    _classPrivateFieldInitSpec(this, _parameters, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(this, _analyticParameters, {
	      writable: true,
	      value: {}
	    });
	  }
	  babelHelpers.createClass(Engine, [{
	    key: "setPayload",
	    /**
	     * Sets Payload for Engine.
	     *
	     * @param {PayloadBase} payload
	     * @return {Engine}
	     */
	    value: function setPayload(payload) {
	      babelHelpers.classPrivateFieldSet(this, _payload, payload);
	      return this;
	    }
	  }, {
	    key: "getPayload",
	    value: function getPayload() {
	      return babelHelpers.classPrivateFieldGet(this, _payload);
	    }
	    /**
	     * Sets allowed (by core) parameters for Engine.
	     *
	     * @param {{[key: string]: string}} parameters
	     * @return {Engine}
	     */
	  }, {
	    key: "setParameters",
	    value: function setParameters(parameters) {
	      babelHelpers.classPrivateFieldSet(this, _parameters, parameters);
	      return this;
	    }
	  }, {
	    key: "setAnalyticParameters",
	    value: function setAnalyticParameters(parameters) {
	      babelHelpers.classPrivateFieldSet(this, _analyticParameters, parameters);
	      return this;
	    }
	    /**
	     * Sets current module id. Its should be Bitrix's module.
	     *
	     * @param {string} moduleId
	     * @return {Engine}
	     */
	  }, {
	    key: "setModuleId",
	    value: function setModuleId(moduleId) {
	      babelHelpers.classPrivateFieldSet(this, _moduleId, moduleId);
	      return this;
	    }
	  }, {
	    key: "getModuleId",
	    value: function getModuleId() {
	      return babelHelpers.classPrivateFieldGet(this, _moduleId);
	    }
	    /**
	     * Sets current context id. Its may be just a string unique within the moduleId.
	     *
	     * @param {string} contextId
	     * @return {Engine}
	     */
	  }, {
	    key: "setContextId",
	    value: function setContextId(contextId) {
	      babelHelpers.classPrivateFieldSet(this, _contextId, contextId);
	      return this;
	    }
	  }, {
	    key: "getContextId",
	    value: function getContextId() {
	      return babelHelpers.classPrivateFieldGet(this, _contextId);
	    }
	  }, {
	    key: "setContextParameters",
	    value: function setContextParameters(contextParameters) {
	      babelHelpers.classPrivateFieldSet(this, _contextParameters, contextParameters);
	      return this;
	    }
	    /**
	     * Write or not history, in depend on $state.
	     *
	     * @param {boolean} state
	     * @return {Engine}
	     */
	  }, {
	    key: "setHistoryState",
	    value: function setHistoryState(state) {
	      babelHelpers.classPrivateFieldSet(this, _historyState, state);
	      return this;
	    }
	    /**
	     * Set group ID for save history.
	     * -1 - no grouped, 0 - first item of group
	     * @param id
	     * @return {Engine}
	     */
	  }, {
	    key: "setHistoryGroupId",
	    value: function setHistoryGroupId(id) {
	      babelHelpers.classPrivateFieldSet(this, _historyGroupId, id);
	      return this;
	    }
	  }, {
	    key: "setBannerLaunched",
	    value: function setBannerLaunched() {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.setBannerLaunchedUrl, {
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      });
	    }
	  }, {
	    key: "checkAgreement",
	    value: function () {
	      var _checkAgreement = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	              return _context.abrupt("return", _classPrivateMethodGet(this, _send, _send2).call(this, Engine.checkAgreementUrl, {
	                parameters: babelHelpers.classPrivateFieldGet(this, _parameters),
	                agreementCode: 'AI_BOX_AGREEMENT'
	              }));
	            case 2:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function checkAgreement() {
	        return _checkAgreement.apply(this, arguments);
	      }
	      return checkAgreement;
	    }()
	  }, {
	    key: "acceptAgreement",
	    value: function acceptAgreement() {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.acceptAgreementUrl, {
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters),
	        agreementCode: 'AI_BOX_AGREEMENT'
	      });
	    }
	    /**
	     * Makes request for text completions.
	     *
	     * @return {Promise}
	     */
	  }, {
	    key: "textCompletions",
	    value: function textCompletions() {
	      var _babelHelpers$classPr;
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.textCompletionsUrl, {
	        prompt: babelHelpers.classPrivateFieldGet(this, _payload).getRawData().prompt,
	        engineCode: babelHelpers.classPrivateFieldGet(this, _payload).getRawData().engineCode,
	        roleCode: (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _payload).getRawData()) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.roleCode,
	        markers: babelHelpers.classPrivateFieldGet(this, _payload).getMarkers(),
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      });
	    }
	    /**
	     * Makes request for image completions.
	     *
	     * @return {Promise}
	     */
	  }, {
	    key: "imageCompletions",
	    value: function imageCompletions() {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.imageCompletionsUrl, {
	        prompt: babelHelpers.classPrivateFieldGet(this, _payload).getRawData().prompt,
	        engineCode: babelHelpers.classPrivateFieldGet(this, _payload).getRawData().engineCode,
	        markers: babelHelpers.classPrivateFieldGet(this, _payload).getMarkers(),
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      });
	    }
	  }, {
	    key: "getTooling",
	    value: function getTooling(category) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      babelHelpers.classPrivateFieldGet(this, _parameters).category = babelHelpers.classPrivateFieldGet(this, _parameters).promptCategory;
	      var data = {
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters),
	        category: babelHelpers.classPrivateFieldGet(this, _parameters).promptCategory,
	        moduleId: babelHelpers.classPrivateFieldGet(this, _moduleId),
	        context: babelHelpers.classPrivateFieldGet(this, _contextId)
	      };
	      return main_core.ajax.runAction('ai.prompt.getPromptsForUser', {
	        data: main_core.Http.Data.convertObjectToFormData(data),
	        method: 'POST',
	        start: false,
	        preparePost: false
	      });
	    }
	  }, {
	    key: "getImagePickerTooling",
	    value: function getImagePickerTooling() {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters),
	        category: 'image'
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.getToolingUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	  }, {
	    key: "getImageCopilotTooling",
	    value: function getImageCopilotTooling() {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.getImageToolingUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	  }, {
	    key: "getImageEngineParams",
	    value: function getImageEngineParams(engineCode) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        engineCode: engineCode,
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.getImageParamsUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	  }, {
	    key: "getRolesDialogData",
	    value: function () {
	      var _getRolesDialogData = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
	        var data;
	        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	              data = {
	                parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	              };
	              return _context2.abrupt("return", _classPrivateMethodGet(this, _send, _send2).call(this, Engine.getRolesDialogDataUrl, data));
	            case 3:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2, this);
	      }));
	      function getRolesDialogData() {
	        return _getRolesDialogData.apply(this, arguments);
	      }
	      return getRolesDialogData;
	    }()
	  }, {
	    key: "getRoles",
	    value: function () {
	      var _getRoles = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
	        var data;
	        return _regeneratorRuntime().wrap(function _callee3$(_context3) {
	          while (1) switch (_context3.prev = _context3.next) {
	            case 0:
	              _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	              data = {
	                parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	              };
	              return _context3.abrupt("return", _classPrivateMethodGet(this, _send, _send2).call(this, Engine.getRolesListUrl, data));
	            case 3:
	            case "end":
	              return _context3.stop();
	          }
	        }, _callee3, this);
	      }));
	      function getRoles() {
	        return _getRoles.apply(this, arguments);
	      }
	      return getRoles;
	    }()
	  }, {
	    key: "addRoleToFavouriteList",
	    value: function () {
	      var _addRoleToFavouriteList = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4(roleCode) {
	        var data;
	        return _regeneratorRuntime().wrap(function _callee4$(_context4) {
	          while (1) switch (_context4.prev = _context4.next) {
	            case 0:
	              _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	              data = {
	                roleCode: roleCode,
	                parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	              };
	              return _context4.abrupt("return", _classPrivateMethodGet(this, _send, _send2).call(this, Engine.addRoleToFavouriteListUrl, data));
	            case 3:
	            case "end":
	              return _context4.stop();
	          }
	        }, _callee4, this);
	      }));
	      function addRoleToFavouriteList(_x) {
	        return _addRoleToFavouriteList.apply(this, arguments);
	      }
	      return addRoleToFavouriteList;
	    }()
	  }, {
	    key: "removeRoleFromFavouriteList",
	    value: function () {
	      var _removeRoleFromFavouriteList = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5(roleCode) {
	        var data;
	        return _regeneratorRuntime().wrap(function _callee5$(_context5) {
	          while (1) switch (_context5.prev = _context5.next) {
	            case 0:
	              _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	              data = {
	                roleCode: roleCode,
	                parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	              };
	              return _context5.abrupt("return", _classPrivateMethodGet(this, _send, _send2).call(this, Engine.removeRoleFromFavouriteListUrl, data));
	            case 3:
	            case "end":
	              return _context5.stop();
	          }
	        }, _callee5, this);
	      }));
	      function removeRoleFromFavouriteList(_x2) {
	        return _removeRoleFromFavouriteList.apply(this, arguments);
	      }
	      return removeRoleFromFavouriteList;
	    }()
	  }, {
	    key: "installKit",
	    value: function installKit(code) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        code: code,
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.installKitUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	    /**
	     * Send user's acceptation of agreement.
	     *
	     * @return {Promise<string>}
	     */
	  }, {
	    key: "acceptImageAgreement",
	    value: function acceptImageAgreement(engineCode) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        engineCode: engineCode,
	        sessid: main_core.Loc.getMessage('bitrix_sessid'),
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.imageAcceptationUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	  }, {
	    key: "acceptTextAgreement",
	    value: function acceptTextAgreement(engineCode) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        engineCode: engineCode,
	        sessid: main_core.Loc.getMessage('bitrix_sessid'),
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return new Promise(function (resolve, reject) {
	        var fd = main_core.Http.Data.convertObjectToFormData(data);
	        var xhr = main_core.ajax({
	          method: 'POST',
	          dataType: 'json',
	          url: Engine.textAcceptationUrl,
	          data: fd,
	          start: false,
	          preparePost: false,
	          onsuccess: function onsuccess(response) {
	            if (response.status === 'error') {
	              reject(response);
	            } else {
	              resolve(response);
	            }
	          },
	          onfailure: reject
	        });
	        xhr.send(fd);
	      });
	    }
	  }, {
	    key: "saveImage",
	    value: function saveImage(imageUrl) {
	      _classPrivateMethodGet(this, _addSystemParameters, _addSystemParameters2).call(this);
	      var data = {
	        pictureUrl: imageUrl,
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.saveImageUrl, data);
	    }
	  }, {
	    key: "getFeedbackData",
	    value: function getFeedbackData() {
	      var data = {
	        parameters: babelHelpers.classPrivateFieldGet(this, _parameters)
	      };
	      return _classPrivateMethodGet(this, _send, _send2).call(this, Engine.textFeedbackDataUrl, data);
	    }
	    /**
	     * Adds additional system parameters.
	     */
	  }]);
	  return Engine;
	}();
	function _addSystemParameters2() {
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_module = babelHelpers.classPrivateFieldGet(this, _moduleId);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_context = babelHelpers.classPrivateFieldGet(this, _contextId);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_context_parameters = babelHelpers.classPrivateFieldGet(this, _contextParameters);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_history = babelHelpers.classPrivateFieldGet(this, _historyState);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_history_group_id = babelHelpers.classPrivateFieldGet(this, _historyGroupId);
	  babelHelpers.classPrivateFieldGet(this, _parameters).bx_analytic = babelHelpers.classPrivateFieldGet(this, _analyticParameters);
	}
	function _registerPullListener2(queueHash, resolve, reject) {
	  main_core.addCustomEvent('onPullEvent-ai', function (command, params) {
	    var hash = params.hash,
	      data = params.data,
	      error = params.error;
	    if (command === 'onQueueJobExecute' && hash === queueHash) {
	      resolve({
	        data: data
	      });
	    } else if (command === 'onQueueJobFail' && hash === queueHash) {
	      reject({
	        errors: [error]
	      });
	    }
	  });
	}
	function _send2(url, data) {
	  var _this = this;
	  if (_classPrivateMethodGet(this, _isOffline, _isOffline2).call(this)) {
	    return Promise.reject(new Error(main_core.Loc.getMessage('AI_ENGINE_INTERNET_PROBLEM')));
	  }
	  return new Promise(function (resolve, reject) {
	    var fd = main_core.Http.Data.convertObjectToFormData(data);
	    var xhr = main_core.ajax({
	      method: 'POST',
	      dataType: 'json',
	      url: url,
	      data: fd,
	      start: false,
	      preparePost: false,
	      onsuccess: function onsuccess(response) {
	        if (response.status === 'error') {
	          reject(response);
	        } else {
	          var _response$data;
	          var queueHash = (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.queue;
	          if (queueHash) {
	            _classPrivateMethodGet(_this, _registerPullListener, _registerPullListener2).call(_this, queueHash, resolve, reject);
	          } else {
	            resolve(response);
	          }
	        }
	      },
	      onfailure: function onfailure(res, resData) {
	        if (res === 'processing' && (resData === null || resData === void 0 ? void 0 : resData.bProactive) === true) {
	          reject(resData.data);
	        }
	        reject(res);
	      }
	    });
	    xhr.send(fd);
	  });
	}
	function _isOffline2() {
	  return !window.navigator.onLine;
	}
	babelHelpers.defineProperty(Engine, "textCompletionsUrl", '/bitrix/services/main/ajax.php?action=ai.api.text.completions');
	babelHelpers.defineProperty(Engine, "imageCompletionsUrl", '/bitrix/services/main/ajax.php?action=ai.api.image.completions');
	babelHelpers.defineProperty(Engine, "textAcceptationUrl", '/bitrix/services/main/ajax.php?action=ai.api.text.acceptation');
	babelHelpers.defineProperty(Engine, "textFeedbackDataUrl", '/bitrix/services/main/ajax.php?action=ai.api.text.getFeedbackData');
	babelHelpers.defineProperty(Engine, "imageAcceptationUrl", '/bitrix/services/main/ajax.php?action=ai.api.image.acceptation');
	babelHelpers.defineProperty(Engine, "saveImageUrl", '/bitrix/services/main/ajax.php?action=ai.api.image.save');
	babelHelpers.defineProperty(Engine, "getToolingUrl", '/bitrix/services/main/ajax.php?action=ai.api.tooling.get');
	babelHelpers.defineProperty(Engine, "getImageToolingUrl", '/bitrix/services/main/ajax.php?action=ai.api.image.getTooling');
	babelHelpers.defineProperty(Engine, "getImageParamsUrl", '/bitrix/services/main/ajax.php?action=ai.api.image.getParams');
	babelHelpers.defineProperty(Engine, "installKitUrl", '/bitrix/services/main/ajax.php?action=ai.api.tooling.installKit');
	babelHelpers.defineProperty(Engine, "getRolesListUrl", '/bitrix/services/main/ajax.php?action=ai.api.role.list');
	babelHelpers.defineProperty(Engine, "getRolesDialogDataUrl", '/bitrix/services/main/ajax.php?action=ai.api.role.picker');
	babelHelpers.defineProperty(Engine, "addRoleToFavouriteListUrl", '/bitrix/services/main/ajax.php?action=ai.api.role.addfavorite');
	babelHelpers.defineProperty(Engine, "removeRoleFromFavouriteListUrl", '/bitrix/services/main/ajax.php?action=ai.api.role.removefavorite');
	babelHelpers.defineProperty(Engine, "acceptAgreementUrl", '/bitrix/services/main/ajax.php?action=ai.api.agreement.accept');
	babelHelpers.defineProperty(Engine, "checkAgreementUrl", '/bitrix/services/main/ajax.php?action=ai.api.agreement.check');
	babelHelpers.defineProperty(Engine, "setBannerLaunchedUrl", '/bitrix/services/main/ajax.php?action=ai.api.tooling.setLaunched');

	exports.Base = Base$1;
	exports.Text = Text$1;
	exports.Engine = Engine;

}((this.BX.AI = this.BX.AI || {}),BX));
//# sourceMappingURL=engine.bundle.js.map
