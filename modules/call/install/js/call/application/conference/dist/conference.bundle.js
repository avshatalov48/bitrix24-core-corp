/* eslint-disable */
this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
(function (exports,call_lib_analytics,call_lib_callTokenManager,call_lib_settingsManager,im_debug,im_application_launch,im_component_conference_conferencePublic,im_model,im_controller,im_lib_cookie,im_lib_localstorage,im_lib_logger,im_lib_clipboard,im_lib_desktop,im_const,ui_notificationManager,ui_notification,ui_buttons,ui_progressround,ui_viewer,ui_vue,ui_vue_vuex,main_core,promise,main_date,main_core_events,pull_client,im_provider_pull,im_v2_lib_desktopApi,Call,rest_client,im_lib_utils) {
	'use strict';

	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var ConferenceChannel = /*#__PURE__*/function () {
	  function ConferenceChannel() {
	    babelHelpers.classCallCheck(this, ConferenceChannel);
	    this.callMultiBroadcastClient = new Call.CallMultiChannel('call_conf_controller_multi_channel');
	  }
	  babelHelpers.createClass(ConferenceChannel, [{
	    key: "sendRequest",
	    value: function () {
	      var _sendRequest = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(conferenceCode) {
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              if (im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	                _context.next = 2;
	                break;
	              }
	              return _context.abrupt("return", Promise.resolve([]));
	            case 2:
	              _context.next = 4;
	              return this.callMultiBroadcastClient.broadcastRequest(conferenceCode, {
	                timeout: 100
	              });
	            case 4:
	              return _context.abrupt("return", _context.sent);
	            case 5:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this);
	      }));
	      function sendRequest(_x) {
	        return _sendRequest.apply(this, arguments);
	      }
	      return sendRequest;
	    }()
	  }, {
	    key: "setExecuter",
	    value: function setExecuter(handle) {
	      this.callMultiBroadcastClient.executer(handle);
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!_classStaticPrivateFieldSpecGet(this, ConferenceChannel, _instance)) {
	        _classStaticPrivateFieldSpecSet(this, ConferenceChannel, _instance, new this());
	      }
	      return _classStaticPrivateFieldSpecGet(this, ConferenceChannel, _instance);
	    }
	  }]);
	  return ConferenceChannel;
	}();
	var _instance = {
	  writable: true,
	  value: void 0
	};

	var RestAuth = Object.freeze({
	  guest: 'guest'
	});
	var CallRestClient = /*#__PURE__*/function () {
	  function CallRestClient(params) {
	    babelHelpers.classCallCheck(this, CallRestClient);
	    this.queryAuthRestore = false;
	    this.setAuthId(RestAuth.guest);
	    this.restClient = new rest_client.RestClient({
	      endpoint: params.endpoint,
	      queryParams: this.queryParams,
	      cors: true
	    });
	  }
	  babelHelpers.createClass(CallRestClient, [{
	    key: "setAuthId",
	    value: function setAuthId(authId) {
	      var customAuthId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      if (babelHelpers["typeof"](this.queryParams) !== 'object') {
	        this.queryParams = {};
	      }
	      if (authId == RestAuth.guest || typeof authId === 'string' && authId.match(/^[a-f0-9]{32}$/)) {
	        this.queryParams.call_auth_id = authId;
	      } else {
	        console.error("%CallRestClient.setAuthId: auth is not correct (%c".concat(authId, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }
	      if (authId == RestAuth.guest && typeof customAuthId === 'string' && customAuthId.match(/^[a-f0-9]{32}$/)) {
	        this.queryParams.call_custom_auth_id = customAuthId;
	      }
	      return true;
	    }
	  }, {
	    key: "setChatId",
	    value: function setChatId(chatId) {
	      if (babelHelpers["typeof"](this.queryParams) !== 'object') {
	        this.queryParams = {};
	      }
	      this.queryParams.call_chat_id = chatId;
	    }
	  }, {
	    key: "setConfId",
	    value: function setConfId(alias) {
	      if (babelHelpers["typeof"](this.queryParams) !== 'object') {
	        this.queryParams = {};
	      }
	      this.queryParams.videoconf_id = alias;
	    }
	  }, {
	    key: "setPassword",
	    value: function setPassword(password) {
	      if (babelHelpers["typeof"](this.queryParams) !== 'object') {
	        this.queryParams = {};
	      }
	      this.queryParams.videoconf_password = password;
	    }
	  }, {
	    key: "callMethod",
	    value: function callMethod(method, params, callback, sendCallback) {
	      var _this = this;
	      var logTag = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;
	      if (!logTag) {
	        logTag = im_lib_utils.Utils.getLogTrackingParams({
	          name: method
	        });
	      }
	      var promise$$1 = new BX.Promise();

	      // TODO: Callbacks methods will not work!
	      this.restClient.callMethod(method, params, null, sendCallback, logTag).then(function (result) {
	        _this.queryAuthRestore = false;
	        promise$$1.fulfill(result);
	      })["catch"](function (result) {
	        var error = result.error();
	        if (error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER') {
	          _this.setAuthId(error.ex.hash);
	          if (method === RestMethod.widgetUserRegister) {
	            console.warn("BX.LiveChatRestClient: ".concat(error.ex.error_description, " (").concat(error.ex.error, ")"));
	            _this.queryAuthRestore = false;
	            promise$$1.reject(result);
	            return false;
	          }
	          if (!_this.queryAuthRestore) {
	            console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');
	            _this.queryAuthRestore = true;
	            _this.restClient.callMethod(method, params, null, sendCallback, logTag).then(function (result) {
	              _this.queryAuthRestore = false;
	              promise$$1.fulfill(result);
	            })["catch"](function (result) {
	              _this.queryAuthRestore = false;
	              promise$$1.reject(result);
	            });
	            return false;
	          }
	        }
	        _this.queryAuthRestore = false;
	        promise$$1.reject(result);
	      });
	      return promise$$1;
	    }
	  }, {
	    key: "callBatch",
	    value: function callBatch(calls, callback, bHaltOnError, sendCallback, logTag) {
	      var _this2 = this;
	      var resultCallback = function resultCallback(result) {
	        for (var method in calls) {
	          if (!calls.hasOwnProperty(method)) {
	            continue;
	          }
	          var _error = result[method].error();
	          if (_error && _error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER') {
	            _this2.setAuthId(_error.ex.hash);
	            if (method === RestMethod.widgetUserRegister) {
	              console.warn("BX.LiveChatRestClient: ".concat(_error.ex.error_description, " (").concat(_error.ex.error, ")"));
	              _this2.queryAuthRestore = false;
	              callback(result);
	              return false;
	            }
	            if (!_this2.queryAuthRestore) {
	              console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');
	              _this2.queryAuthRestore = true;
	              _this2.restClient.callBatch(calls, callback, bHaltOnError, sendCallback, logTag);
	              return false;
	            }
	          }
	        }
	        _this2.queryAuthRestore = false;
	        callback(result);
	        return true;
	      };
	      return this.restClient.callBatch(calls, resultCallback, bHaltOnError, sendCallback, logTag);
	    }
	  }]);
	  return CallRestClient;
	}();

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var BALLOON_OFFSET_CLASS_NAME = 'bx-call-control-notification-right-offset';
	var _stopRecordCall = /*#__PURE__*/new WeakSet();
	var ConferenceApplication = /*#__PURE__*/function () {
	  /* region 01. Initialize */
	  function ConferenceApplication() {
	    var _this$params$callToke,
	      _this = this;
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, ConferenceApplication);
	    _classPrivateMethodInitSpec(this, _stopRecordCall);
	    this.inited = false;
	    this.hardwareInited = false;
	    this.dialogInited = false;
	    this.initPromise = new BX.Promise();
	    this.params = params;
	    this.params.userId = this.params.userId ? parseInt(this.params.userId) : 0;
	    this.params.siteId = this.params.siteId || '';
	    this.params.chatId = this.params.chatId ? parseInt(this.params.chatId) : 0;
	    this.params.dialogId = this.params.chatId ? 'chat' + this.params.chatId.toString() : '0';
	    this.params.passwordRequired = !!this.params.passwordRequired;
	    this.params.isBroadcast = !!this.params.isBroadcast;
	    BX.Messenger.Lib.Logger.setConfig(params.loggerConfig);
	    this.messagesQueue = [];
	    this.template = null;
	    this.rootNode = this.params.node || document.createElement('div');
	    this.event = new ui_vue.VueVendorV2();
	    this.callContainer = null;
	    // this.callView = null;
	    this.preCall = null;
	    this.currentCall = null;
	    this.callToken = (_this$params$callToke = this.params.callToken) !== null && _this$params$callToke !== void 0 ? _this$params$callToke : null;
	    this.videoStrategy = null;
	    this.callDetails = {};
	    this.showFeedback = true;
	    this.callScheme = null;
	    this.promotedToAdminTimeoutValue = 10 * 1000; // 10 sec
	    this.promotedToAdminTimeout = null;
	    this.featureConfig = {};
	    (params.featureConfig || []).forEach(function (limit) {
	      _this.featureConfig[limit.id] = limit;
	    });
	    this.localVideoStream = null;

	    // save reconnect camera
	    this.lastUsedCameraId = null;
	    this.reconnectingCameraId = null;
	    this.conferencePageTagInterval = null;
	    this.onCallUserInvitedHandler = this.onCallUserInvited.bind(this);
	    this.onCallUserJoinedHandler = this.onCallUserJoined.bind(this);
	    this.onCallUserStateChangedHandler = this.onCallUserStateChanged.bind(this);
	    this.onCallUserMicrophoneStateHandler = this.onCallUserMicrophoneState.bind(this);
	    this.onCallUserCameraStateHandler = this.onCallUserCameraState.bind(this);
	    this.onNeedResetMediaDevicesStateHandler = this.onNeedResetMediaDevicesState.bind(this);
	    this.onCallUserVideoPausedHandler = this.onCallUserVideoPaused.bind(this);
	    this.onCallLocalMediaReceivedHandler = BX.debounce(this.onCallLocalMediaReceived.bind(this), 1000);
	    this.onCallRemoteMediaReceivedHandler = this.onCallRemoteMediaReceived.bind(this);
	    this.onCallRemoteMediaStoppedHandler = this.onCallRemoteMediaStopped.bind(this);
	    this.onCallUserVoiceStartedHandler = this.onCallUserVoiceStarted.bind(this);
	    this.onCallUserVoiceStoppedHandler = this.onCallUserVoiceStopped.bind(this);
	    this.onUserStatsReceivedHandler = this.onUserStatsReceived.bind(this);
	    this.onCallUserScreenStateHandler = this.onCallUserScreenState.bind(this);
	    this.onCallUserRecordStateHandler = this.onCallUserRecordState.bind(this);
	    this.onCallUserFloorRequestHandler = this.onCallUserFloorRequest.bind(this);
	    this.onMicrophoneLevelHandler = this.onMicrophoneLevel.bind(this);
	    this._onCallJoinHandler = this.onCallJoin.bind(this);
	    this.onCallFailureHandler = this.onCallFailure.bind(this);
	    this.onCallLeaveHandler = this.onCallLeave.bind(this);
	    this.onCallDestroyHandler = this.onCallDestroy.bind(this);
	    this.onInputFocusHandler = this.onInputFocus.bind(this);
	    this.onInputBlurHandler = this.onInputBlur.bind(this);
	    this.onReconnectingHandler = this.onReconnecting.bind(this);
	    this.onReconnectedHandler = this.onReconnected.bind(this);
	    this.onReconnectingFailedHandler = this.onReconnectingFailed.bind(this);
	    this.onUpdateLastUsedCameraIdHandler = this.onUpdateLastUsedCameraId.bind(this);
	    this.onCallConnectionQualityChangedHandler = this.onCallConnectionQualityChanged.bind(this);
	    this.onCallToggleRemoteParticipantVideoHandler = this.onCallToggleRemoteParticipantVideo.bind(this);
	    this._onGetUserMediaEndedHandler = this.onGetUserMediaEnded.bind(this);
	    this._onSwitchTrackRecordStatusHandler = this.onUpdateCallCopilotState.bind(this);
	    this.onCameraPublishingHandler = this.onCameraPublishing.bind(this);
	    this.onMicrophonePublishingdHandler = this.onMicrophonePublishingd.bind(this);
	    this._onAllParticipantsAudioMutedHandler = this._onAllParticipantsAudioMuted.bind(this);
	    this._onAllParticipantsVideoMutedHandler = this._onAllParticipantsVideoMuted.bind(this);
	    this._onAllParticipantsScreenshareHandler = this._onAllParticipantsScreenshareMuted.bind(this);
	    this._onYouMuteAllParticipantsHandler = this._onYouMuteAllParticipants.bind(this);
	    this._onRoomSettingsChangedHandler = this._onRoomSettingsChanged.bind(this);
	    this._onUserPermissionsChangedHandler = this._onUserPermissionsChanged.bind(this);
	    this._onUserRoleChangedHandler = this._onUserRoleChanged.bind(this);
	    this._onParticipantMutedHandler = this._onParticipantMuted.bind(this);
	    this.onPreCallDestroyHandler = this.onPreCallDestroy.bind(this);
	    this.onPreCallUserStateChangedHandler = this.onPreCallUserStateChanged.bind(this);
	    this.waitingForCallStatus = false;
	    this.waitingForCallStatusTimeout = null;
	    this.callEventReceived = false;
	    this.callRecordState = Call.View.RecordState.Stopped;
	    this.callRecordInitiatorId = null;
	    this.callRecordInfo = null;
	    this.floatingScreenShareWindow = null;
	    this.webScreenSharePopup = null;
	    this.screenShareStartTime = null;
	    this.mutePopup = null;
	    this.riseYouHandToTalkPopup = null;
	    this.allowMutePopup = true;
	    this.loopTimers = {};
	    this.isFileChooserActive = false;
	    this.pictureInPictureDebounceForOpen = null;
	    this.isWindowFocus = true;
	    if (im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	      ConferenceChannel.getInstance().setExecuter(function (callUuid) {
	        var currentCallUuid = _this.params.alias;
	        var hasView = Boolean(_this.callView);
	        var hasActiveCall = currentCallUuid === callUuid && hasView;
	        if (hasActiveCall) {
	          BXDesktopSystem.SetActiveTab();
	        }
	        return hasActiveCall;
	      });
	    }
	    this.initDesktopEvents().then(function () {
	      return _this.initAdditionalEvents();
	    }).then(function () {
	      return _this.initRestClient();
	    }).then(function () {
	      return _this.subscribePreCallChanges();
	    }).then(function () {
	      return _this.subscribeNotifierEvents();
	    }).then(function () {
	      return _this.initPullClient();
	    }).then(function () {
	      return _this.initCore();
	    }).then(function () {
	      return _this.setModelData();
	    }).then(function () {
	      return _this.initComponent();
	    }).then(function () {
	      return _this.initCallInterface();
	    }).then(function () {
	      return _this.initHardware();
	    }).then(function () {
	      return _this.initUserComplete();
	    })["catch"](function (error) {
	      console.error('Init error', error);
	    });
	  }
	  /* region 01. Initialize methods */
	  babelHelpers.createClass(ConferenceApplication, [{
	    key: "initDesktopEvents",
	    value: function initDesktopEvents() {
	      var _this2 = this;
	      if (!im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	        return new Promise(function (resolve, reject) {
	          return resolve();
	        });
	      }
	      this.floatingScreenShareWindow = new Call.FloatingScreenShare({
	        onBackToCallClick: this.onFloatingScreenShareBackToCallClick.bind(this),
	        onStopSharingClick: this.onFloatingScreenShareStopClick.bind(this),
	        onChangeScreenClick: this.onFloatingScreenShareChangeScreenClick.bind(this)
	      });
	      if (this.floatingScreenShareWindow) {
	        im_v2_lib_desktopApi.DesktopApi.subscribe("BXScreenMediaSharing", function (id, title, x, y, width, height, app) {
	          _this2.floatingScreenShareWindow.setSharingData({
	            title: title,
	            x: x,
	            y: y,
	            width: width,
	            height: height,
	            app: app
	          }).then(function () {
	            _this2.floatingScreenShareWindow.show();
	          })["catch"](function (error) {
	            im_lib_logger.Logger.error('setSharingData error', error);
	          });
	        });
	      }
	      im_v2_lib_desktopApi.DesktopApi.subscribe('bxImUpdateCounterMessage', function (counter) {
	        if (!_this2.controller) {
	          return false;
	        }
	        _this2.controller.getStore().commit('conference/common', {
	          messageCount: counter
	        });
	      });
	      main_core_events.EventEmitter.subscribe(im_const.EventType.textarea.focus, this.onInputFocusHandler);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.textarea.blur, this.onInputBlurHandler);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.conference.userRenameFocus, this.onInputFocusHandler);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.conference.userRenameBlur, this.onInputBlurHandler);
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initAdditionalEvents",
	    value: function initAdditionalEvents() {
	      var _this3 = this;
	      window.addEventListener('focus', function () {
	        _this3.onWindowFocus();
	      });
	      window.addEventListener('blur', function () {
	        _this3.onWindowBlur();
	      });
	      document.body.addEventListener('click', function (evt) {
	        _this3.onDocumentBodyClick(evt);
	      });
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initRestClient",
	    value: function initRestClient() {
	      this.restClient = new CallRestClient({
	        endpoint: this.getHost() + '/rest'
	      });
	      this.restClient.setConfId(this.params.conferenceId);
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "subscribePreCallChanges",
	    value: function subscribePreCallChanges() {
	      BX.addCustomEvent(window, 'CallEvents::callCreated', this.onCallCreated.bind(this));
	    }
	  }, {
	    key: "subscribeNotifierEvents",
	    value: function subscribeNotifierEvents() {
	      var _this4 = this;
	      ui_notificationManager.Notifier.subscribe('click', function (event) {
	        var _event$getData = event.getData(),
	          id = _event$getData.id;
	        if (id.startsWith('im-videconf')) {
	          _this4.toggleChat();
	        }
	      });
	    }
	  }, {
	    key: "initPullClient",
	    value: function initPullClient() {
	      if (!this.params.isIntranetOrExtranet) {
	        this.pullClient = new pull_client.PullClient({
	          serverEnabled: true,
	          userId: this.params.userId,
	          siteId: this.params.siteId,
	          restClient: this.restClient,
	          skipStorageInit: true,
	          configTimestamp: 0,
	          skipCheckRevision: true,
	          getPublicListMethod: 'im.call.channel.public.list'
	        });
	        return new Promise(function (resolve, reject) {
	          return resolve();
	        });
	      } else {
	        this.pullClient = BX.PULL;
	        return this.pullClient.start().then(function () {
	          return new Promise(function (resolve, reject) {
	            return resolve();
	          });
	        });
	      }
	    }
	  }, {
	    key: "initCore",
	    value: function initCore() {
	      var _this5 = this;
	      this.controller = new im_controller.Controller({
	        host: this.getHost(),
	        siteId: this.params.siteId,
	        userId: this.params.userId,
	        languageId: this.params.language,
	        pull: {
	          client: this.pullClient
	        },
	        rest: {
	          client: this.restClient
	        },
	        vuexBuilder: {
	          database: !im_lib_utils.Utils.browser.isIe(),
	          databaseName: 'imol/call',
	          databaseType: ui_vue_vuex.VuexBuilder.DatabaseType.localStorage,
	          models: [im_model.ConferenceModel.create(), im_model.CallModel.create()]
	        }
	      });
	      window.BX.Messenger.Application.Core = {
	        controller: this.controller
	      };
	      return new Promise(function (resolve, reject) {
	        _this5.controller.ready().then(function () {
	          return resolve();
	        });
	      });
	    }
	  }, {
	    key: "setModelData",
	    value: function setModelData() {
	      var _this6 = this;
	      this.controller.getStore().commit('application/set', {
	        dialog: {
	          chatId: this.getChatId(),
	          dialogId: this.getDialogId()
	        },
	        options: {
	          darkBackground: true
	        }
	      });

	      //set presenters ID list
	      var presentersIds = this.params.presenters.map(function (presenter) {
	        return presenter['id'];
	      });
	      this.controller.getStore().dispatch('conference/setBroadcastMode', {
	        broadcastMode: this.params.isBroadcast
	      });
	      this.controller.getStore().dispatch('conference/setPresenters', {
	        presenters: presentersIds
	      });

	      //set presenters info in users model
	      this.params.presenters.forEach(function (presenter) {
	        _this6.controller.getStore().dispatch('users/set', presenter);
	      });
	      if (this.params.passwordRequired) {
	        this.controller.getStore().commit('conference/common', {
	          passChecked: false
	        });
	      }
	      if (this.params.conferenceTitle) {
	        this.controller.getStore().dispatch('conference/setConferenceTitle', {
	          conferenceTitle: this.params.conferenceTitle
	        });
	      }
	      if (this.params.alias) {
	        this.controller.getStore().commit('conference/setAlias', {
	          alias: this.params.alias
	        });
	      }
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initComponent",
	    value: function initComponent() {
	      var _this7 = this;
	      if (this.getStartupErrorCode()) {
	        this.setError(this.getStartupErrorCode());
	      }
	      return new Promise(function (resolve, reject) {
	        _this7.controller.createVue(_this7, {
	          el: _this7.rootNode,
	          data: function data() {
	            return {
	              dialogId: _this7.getDialogId()
	            };
	          },
	          template: "<bx-im-component-conference-public :dialogId=\"dialogId\"/>"
	        }).then(function (vue) {
	          _this7.template = vue;
	          resolve();
	        })["catch"](function (error) {
	          return reject(error);
	        });
	      });
	    }
	  }, {
	    key: "initCallInterface",
	    value: function initCallInterface() {
	      var _this8 = this;
	      return new Promise(function (resolve, reject) {
	        try {
	          _this8.callContainer = document.getElementById('bx-im-component-call-container');
	          var hiddenButtons = ['document'];
	          if (_this8.isViewerMode()) {
	            hiddenButtons = ['camera', 'microphone', 'screen', 'record', 'floorRequest', 'document'];
	          }
	          if (!_this8.params.isIntranetOrExtranet) {
	            hiddenButtons.push('record');
	          }
	          if (!Call.Util.isConferenceChatEnabled()) {
	            hiddenButtons.push('chat');
	          }
	          _this8.callView = new Call.View({
	            container: _this8.callContainer,
	            showChatButtons: true,
	            showUsersButton: true,
	            showShareButton: _this8.getFeatureState('screenSharing') !== ConferenceApplication.FeatureState.Disabled,
	            showRecordButton: _this8.getFeatureState('record') !== ConferenceApplication.FeatureState.Disabled,
	            userLimit: Call.Util.getUserLimit(),
	            isIntranetOrExtranet: !!_this8.params.isIntranetOrExtranet,
	            language: _this8.params.language,
	            layout: im_lib_utils.Utils.device.isMobile() ? Call.View.Layout.Mobile : Call.View.Layout.Centered,
	            uiState: Call.View.UiState.Preparing,
	            blockedButtons: ['camera', 'microphone', 'floorRequest', 'screen', 'record', 'copilot'],
	            localUserState: Call.UserState.Idle,
	            hiddenTopButtons: !_this8.isBroadcast() || _this8.getBroadcastPresenters().length > 1 ? [] : ['grid'],
	            hiddenButtons: hiddenButtons,
	            broadcastingMode: _this8.isBroadcast(),
	            broadcastingPresenters: _this8.getBroadcastPresenters(),
	            isCopilotFeaturesEnabled: false,
	            isCopilotActive: false,
	            isWindowFocus: _this8.isWindowFocus,
	            isVideoconf: true
	          });
	          _this8.callView.subscribe(Call.View.Event.onButtonClick, _this8.onCallButtonClick.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onReplaceCamera, _this8.onCallReplaceCamera.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onReplaceMicrophone, _this8.onCallReplaceMicrophone.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onReplaceSpeaker, _this8.onCallReplaceSpeaker.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onHasMainStream, _this8.onCallViewHasMainStream.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onChangeHdVideo, _this8.onCallViewChangeHdVideo.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onChangeMicAutoParams, _this8.onCallViewChangeMicAutoParams.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onChangeFaceImprove, _this8.onCallViewChangeFaceImprove.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onUserRename, _this8.onCallViewUserRename.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onUserPinned, _this8.onCallViewUserPinned.bind(_this8));
	          _this8.callView.subscribe(Call.View.Event.onToggleSubscribe, _this8.onCallToggleSubscribe.bind(_this8));
	          _this8.callView.setCallback(Call.View.Event.onTurnOffParticipantMic, _this8._onCallViewTurnOffParticipantMic.bind(_this8));
	          _this8.callView.setCallback(Call.View.Event.onTurnOffParticipantCam, _this8._onCallViewTurnOffParticipantCam.bind(_this8));
	          _this8.callView.setCallback(Call.View.Event.onTurnOffParticipantScreenshare, _this8._onCallViewTurnOffParticipantScreenshare.bind(_this8));
	          _this8.callView.setCallback(Call.View.Event.onAllowSpeakPermission, _this8._onCallViewAllowSpeakPermission.bind(_this8));
	          _this8.callView.setCallback(Call.View.Event.onDisallowSpeakPermission, _this8._onCallViewDisallowSpeakPermission.bind(_this8));
	          _this8.callView.blockAddUser();
	          _this8.callView.blockHistoryButton();
	          if (!im_lib_utils.Utils.device.isMobile()) {
	            _this8.callView.show();
	          }
	          resolve();
	        } catch (error) {
	          im_lib_logger.Logger.error('creating call interface conference', error);
	          var errorCode = 'UNKNOWN_ERROR';
	          if (main_core.Type.isString(error)) {
	            errorCode = error;
	          } else if (main_core.Type.isPlainObject(error) && error.code) {
	            errorCode = error.code == 'access_denied' ? 'ACCESS_DENIED' : error.code;
	          }
	          _this8.onCallFailure({
	            code: errorCode,
	            message: error.message || ""
	          });
	          reject('call interface error');
	        }
	      });
	    }
	  }, {
	    key: "initUserComplete",
	    value: function initUserComplete() {
	      var _this9 = this;
	      return new Promise(function (resolve, reject) {
	        _this9.initUser().then(function () {
	          return _this9.tryJoinExistingCall();
	        }).then(function () {
	          return _this9.initCall();
	        }).then(function () {
	          return _this9.startPageTagInterval();
	        }).then(function () {
	          return _this9.initPullHandlers();
	        }).then(function () {
	          return _this9.subscribeToStoreChanges();
	        }).then(function () {
	          return _this9.initComplete();
	        }).then(function () {
	          return resolve;
	        })["catch"](function (error) {
	          return reject(error);
	        });
	      });
	    }
	    /* endregion 01. Initialize methods */
	    /* region 02. initUserComplete methods */
	  }, {
	    key: "initUser",
	    value: function initUser() {
	      var _this10 = this;
	      return new Promise(function (resolve, reject) {
	        if (_this10.getStartupErrorCode() || !_this10.getConference().common.passChecked) {
	          return reject();
	        }
	        if (_this10.params.userId > 0) {
	          _this10.controller.setUserId(_this10.params.userId);
	          if (_this10.params.isIntranetOrExtranet) {
	            _this10.switchToSessAuth();
	            _this10.controller.getStore().commit('conference/user', {
	              id: _this10.params.userId
	            });
	          } else {
	            var hashFromCookie = _this10.getUserHashCookie();
	            if (hashFromCookie) {
	              call_lib_callTokenManager.CallTokenManager.setQueryParams({
	                call_auth_id: hashFromCookie,
	                videoconf_id: _this10.params.conferenceId
	              });
	              _this10.restClient.setAuthId(hashFromCookie);
	              _this10.restClient.setChatId(_this10.getChatId());
	              _this10.controller.getStore().commit('conference/user', {
	                id: _this10.params.userId,
	                hash: hashFromCookie
	              });
	              _this10.pullClient.start();
	            }
	          }
	          _this10.controller.getStore().commit('conference/common', {
	            inited: true
	          });
	          return resolve();
	        } else {
	          _this10.restClient.setAuthId('guest');
	          _this10.restClient.setChatId(_this10.getChatId());
	          if (typeof BX.SidePanel !== 'undefined') {
	            BX.SidePanel.Instance.disableAnchorBinding();
	          }
	          return _this10.restClient.callMethod('im.call.user.register', {
	            alias: _this10.params.alias,
	            user_hash: _this10.getUserHashCookie() || ''
	          }).then(function (result) {
	            BX.message.USER_ID = result.data().id;
	            _this10.controller.getStore().commit('conference/user', {
	              id: result.data().id,
	              hash: result.data().hash
	            });
	            _this10.controller.setUserId(result.data().id);
	            _this10.callView.setLocalUserId(result.data().id);
	            Call.Engine.setCurrentUserId(_this10.controller.getUserId());
	            Call.EngineLegacy.setCurrentUserId(_this10.controller.getUserId());
	            call_lib_callTokenManager.CallTokenManager.setUserToken(result.data().userToken);
	            if (result.data().created) {
	              _this10.params.userCount++;
	            }
	            _this10.controller.getStore().commit('conference/common', {
	              inited: true
	            });
	            call_lib_callTokenManager.CallTokenManager.setQueryParams({
	              call_auth_id: result.data().hash,
	              videoconf_id: _this10.params.conferenceId
	            });
	            _this10.restClient.setAuthId(result.data().hash);
	            _this10.pullClient.start();
	            return resolve();
	          });
	        }
	      });
	    }
	  }, {
	    key: "startPageTagInterval",
	    value: function startPageTagInterval() {
	      var _this11 = this;
	      return new Promise(function (resolve) {
	        clearInterval(_this11.conferencePageTagInterval);
	        _this11.conferencePageTagInterval = setInterval(function () {
	          im_lib_localstorage.LocalStorage.set(_this11.params.siteId, _this11.params.userId, _this11.callEngine.getConferencePageTag(_this11.params.dialogId), "Y", 2);
	        }, 1000);
	        resolve();
	      });
	    }
	  }, {
	    key: "tryJoinExistingCall",
	    value: function tryJoinExistingCall() {
	      var _this12 = this;
	      return new Promise(function (resolve, reject) {
	        var url = call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled ? 'call.Call.tryJoinCall' : 'im.call.tryJoinCall';
	        var callTypeKey = call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled ? 'callType' : 'type';
	        BX.ajax.runAction(url, {
	          data: babelHelpers.defineProperty({
	            entityType: 'chat',
	            entityId: _this12.params.dialogId,
	            provider: Call.Provider.Bitrix
	          }, callTypeKey, Call.Type.Permanent)
	        }).then(function (result) {
	          var data = result.data;
	          im_lib_logger.Logger.warn('tryJoinCall', data);
	          if (data.success) {
	            _this12.waitingForCallStatus = true;
	            _this12.callScheme = data.call.SCHEME;
	            if (_this12.callScheme === Call.CallScheme.jwt) {
	              _this12.callToken = data.callToken;
	              Call.Engine.instantiateCall(data.call, data.callToken, data.logToken, data.userData);
	            } else {
	              Call.EngineLegacy.instantiateCall(data.call, data.users, data.logToken, data.connectionData, data.userData);
	            }
	            _this12.waitingForCallStatusTimeout = setTimeout(function () {
	              _this12.waitingForCallStatus = false;
	              if (!_this12.callEventReceived) {
	                _this12.setConferenceStatus(false);
	              }
	              _this12.callEventReceived = false;
	            }, 5000);
	          } else {
	            _this12.setConferenceStatus(false);
	          }
	          resolve();
	        })["catch"](function () {
	          _this12.setConferenceStatus(false);
	          resolve();
	        });
	      });
	    }
	  }, {
	    key: "initCall",
	    value: function initCall() {
	      var _this13 = this;
	      return new Promise(function (resolve) {
	        if (_this13.callScheme) {
	          _this13.callEngine = _this13.callScheme === Call.CallScheme.jwt ? Call.Engine : Call.EngineLegacy;
	        } else {
	          _this13.callEngine = call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled ? Call.Engine : Call.EngineLegacy;
	        }
	        Call.Engine.setRestClient(_this13.restClient);
	        Call.Engine.setPullClient(_this13.pullClient);

	        // this is a workaround to use actual parameters for conference guests in Util
	        // since we don't know if a call is legacy or not when we make a REST request
	        Call.EngineLegacy.setRestClient(_this13.restClient);
	        Call.EngineLegacy.setPullClient(_this13.pullClient);
	        _this13.callView.unblockButtons(['chat']);
	        resolve();
	      });
	    }
	  }, {
	    key: "initPullHandlers",
	    value: function initPullHandlers() {
	      this.pullClient.subscribe(new im_provider_pull.ImCallPullHandler({
	        store: this.controller.getStore(),
	        application: this,
	        controller: this.controller
	      }));
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "subscribeToStoreChanges",
	    value: function subscribeToStoreChanges() {
	      var _this14 = this;
	      this.controller.getStore().subscribe(function (mutation, state) {
	        var payload = mutation.payload,
	          type = mutation.type;
	        if (type === 'users/update' && payload.fields.name) {
	          if (!_this14.callView) {
	            return false;
	          }
	          _this14.callView.updateUserData(babelHelpers.defineProperty({}, payload.id, {
	            name: payload.fields.name
	          }));
	        } else if (type === 'dialogues/set') {
	          if (payload[0].dialogId !== _this14.getDialogId()) {
	            return false;
	          }
	          if (!im_lib_utils.Utils.platform.isBitrixDesktop()) {
	            _this14.callView.setButtonCounter('chat', payload[0].counter);
	          }
	        } else if (type === 'dialogues/update') {
	          if (payload.dialogId !== _this14.getDialogId()) {
	            return false;
	          }
	          if (typeof payload.fields.counter === 'number' && _this14.callView) {
	            if (im_lib_utils.Utils.platform.isBitrixDesktop()) {
	              if (payload.actionName === "decreaseCounter" && !payload.dialogMuted && typeof payload.fields.previousCounter === 'number') {
	                var counter = payload.fields.counter;
	                if (_this14.getConference().common.messageCount) {
	                  counter = _this14.getConference().common.messageCount - (payload.fields.previousCounter - counter);
	                  if (counter < 0) {
	                    counter = 0;
	                  }
	                }
	                _this14.callView.setButtonCounter('chat', counter);
	              }
	            } else {
	              _this14.callView.setButtonCounter('chat', payload.fields.counter);
	            }
	          }
	          if (typeof payload.fields.name !== 'undefined') {
	            document.title = payload.fields.name.toString();
	          }
	        } else if (type === 'conference/common' && typeof payload.messageCount === 'number') {
	          if (_this14.callView) {
	            _this14.callView.setButtonCounter('chat', payload.messageCount);
	          }
	        }
	      });
	    }
	  }, {
	    key: "initComplete",
	    value: function initComplete() {
	      if (this.isExternalUser()) {
	        this.callView.localUser.userModel.allowRename = true;
	      }
	      if (this.getConference().common.inited) {
	        this.inited = true;
	        this.initPromise.resolve(this);
	      }
	      if (im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	        im_v2_lib_desktopApi.DesktopApi.emitToMainWindow('bxConferenceLoadComplete', []);
	      }
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	    /* endregion 02. initUserComplete methods */
	    /* endregion 01. Initialize */
	    /* region 02. Methods */
	    /* region 01. Call methods */
	  }, {
	    key: "initHardware",
	    value: function initHardware() {
	      var _this15 = this;
	      return new Promise(function (resolve, reject) {
	        Call.Hardware.init().then(function () {
	          if (_this15.hardwareInited) {
	            resolve();
	            return true;
	          }
	          if (Object.values(Call.Hardware.microphoneList).length === 0) {
	            _this15.setError(im_const.ConferenceErrorCode.missingMicrophone);
	          }
	          if (!_this15.isViewerMode()) {
	            _this15.checkAvailableCamera();
	            _this15.checkAvailableMicrophone();
	            _this15.callView.enableMediaSelection();
	          }
	          Call.Hardware.subscribe(Call.Hardware.Events.deviceChanged, _this15._onDeviceChange.bind(_this15));
	          _this15.hardwareInited = true;
	          resolve();
	        })["catch"](function (error) {
	          if (error === 'NO_WEBRTC' && _this15.isHttps()) {
	            _this15.setError(im_const.ConferenceErrorCode.unsupportedBrowser);
	          } else if (error === 'NO_WEBRTC' && !_this15.isHttps()) {
	            _this15.setError(im_const.ConferenceErrorCode.unsafeConnection);
	          }
	          im_lib_logger.Logger.error('Init hardware error', error);
	          reject(error);
	        });
	      });
	    }
	  }, {
	    key: "_onDeviceChange",
	    value: function _onDeviceChange(e) {
	      var _this16 = this;
	      if (!this.currentCall || !this.currentCall.ready) {
	        this.checkAvailableCamera();
	        this.checkAvailableMicrophone();
	        return;
	      }
	      var allAddedDevice = e.data.added;
	      var allRemovedDevice = e.data.removed;
	      var removed = Call.Hardware.getRemovedUsedDevices(e.data.removed, {
	        microphoneId: this.currentCall.microphoneId,
	        cameraId: this.currentCall.cameraId,
	        speakerId: this.callView.speakerId
	      });
	      if (allAddedDevice) {
	        setTimeout(function () {
	          return _this16.useDevicesInCurrentCall(allAddedDevice);
	        }, 500);
	      }
	      if (removed.length > 0) {
	        BX.UI.Notification.Center.notify({
	          content: BX.message("IM_CALL_DEVICES_DETACHED") + "<br><ul>" + removed.map(function (deviceInfo) {
	            return "<li>" + deviceInfo.label;
	          }) + "</ul>",
	          position: "top-right",
	          autoHideDelay: 10000,
	          closeButton: true,
	          //category: "call-device-change",
	          actions: [{
	            title: BX.message("IM_CALL_DEVICES_CLOSE"),
	            events: {
	              click: function click(event, balloon) {
	                balloon.close();
	              }
	            }
	          }]
	        });
	      }
	      if (allRemovedDevice) {
	        setTimeout(function () {
	          return _this16.removeDevicesFromCurrentCall(allRemovedDevice);
	        }, 500);
	      }
	    }
	  }, {
	    key: "setDefaultCameraIfNeeded",
	    value: function setDefaultCameraIfNeeded() {
	      if (!Call.Hardware.hasCamera() || !!Call.Hardware.defaultCamera && !!Call.Hardware.cameraList.length && Call.Hardware.cameraList.some(function (cameraItem) {
	        return cameraItem.deviceId === Call.Hardware.defaultCamera;
	      })) {
	        return;
	      }
	      if (!Call.Hardware.defaultCamera && !!Call.Hardware.cameraList.length) {
	        Call.Hardware.defaultCamera = Call.Hardware.cameraList[0].deviceId;
	        return;
	      }
	      Call.Hardware.defaultCamera = '';
	    }
	  }, {
	    key: "setDefaultMicrophoneIfNeeded",
	    value: function setDefaultMicrophoneIfNeeded() {
	      if (!Call.Hardware.hasMicrophone() || !!Call.Hardware.defaultMicrophone && !!Call.Hardware.microphoneList.length && Call.Hardware.microphoneList.some(function (micItem) {
	        return micItem.deviceId === Call.Hardware.defaultMicrophone;
	      })) {
	        return;
	      }
	      if (!Call.Hardware.defaultMicrophone && !!Call.Hardware.microphoneList.length) {
	        Call.Hardware.defaultMicrophone = Call.Hardware.microphoneList[0].deviceId;
	        return;
	      }
	      Call.Hardware.defaultMicrophone = '';
	    }
	  }, {
	    key: "onBlockCameraButton",
	    value: function onBlockCameraButton() {
	      if (!this.callView) {
	        return;
	      }
	      this.callView.blockSwitchCamera();
	    }
	  }, {
	    key: "onUnblockCameraButton",
	    value: function onUnblockCameraButton() {
	      if (!this.callView) {
	        return;
	      }
	      this.callView.unblockSwitchCamera();
	    }
	  }, {
	    key: "onBlockMicrophoneButton",
	    value: function onBlockMicrophoneButton() {
	      if (!this.callView) {
	        return;
	      }
	      this.callView.blockSwitchMicrophone();
	    }
	  }, {
	    key: "onUnblockMicrophoneButton",
	    value: function onUnblockMicrophoneButton() {
	      if (!this.callView) {
	        return;
	      }
	      this.callView.unblockSwitchMicrophone();
	    }
	  }, {
	    key: "checkAvailableCamera",
	    value: function checkAvailableCamera() {
	      var isCameraButtonHasBlocked = this.callView.isButtonBlocked('camera');
	      if (!this.currentCall && !Call.Hardware.hasCamera()) {
	        this.onBlockCameraButton();
	      } else {
	        this.onUnblockCameraButton();
	      }
	      this.setDefaultCameraIfNeeded();
	      var isActiveState = Call.Hardware.hasCamera() && Call.Hardware.defaultCamera;
	      if (!this.currentCall && isCameraButtonHasBlocked) {
	        this.template.$emit('setCameraState', isActiveState);
	        this.template.$emit('cameraSelected', Call.Hardware.defaultCamera);
	      }
	      this.callView.updateButtons();
	    }
	  }, {
	    key: "checkAvailableMicrophone",
	    value: function checkAvailableMicrophone() {
	      if (!this.currentCall && !Call.Hardware.hasMicrophone()) {
	        this.onBlockMicrophoneButton();
	      } else {
	        this.onUnblockMicrophoneButton();
	      }
	      this.setDefaultMicrophoneIfNeeded();
	      var isActiveState = Call.Hardware.hasMicrophone();
	      if (!this.currentCall) {
	        this.template.$emit('setMicState', isActiveState);
	        this.template.$emit('micSelected', Call.Hardware.defaultCamera);
	      }
	      this.callView.updateButtons();
	    }
	  }, {
	    key: "useDevicesInCurrentCall",
	    value: function useDevicesInCurrentCall(deviceList) {
	      var isForceUse = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      if (!this.currentCall || !this.currentCall.ready) {
	        return;
	      }
	      for (var i = 0; i < deviceList.length; i++) {
	        var deviceInfo = deviceList[i];
	        switch (deviceInfo.kind) {
	          case "audioinput":
	            if (deviceInfo.deviceId === 'default' || isForceUse) {
	              var newDeviceId = Call.Hardware.getDefaultDeviceIdByGroupId(deviceInfo.groupId, 'audioinput');
	              this.currentCall.setMicrophoneId(newDeviceId);
	              this.callView.setMicrophoneId(newDeviceId);
	            }
	            this.checkAvailableMicrophone();
	            break;
	          case "videoinput":
	            if (deviceInfo.deviceId === 'default' || isForceUse) {
	              this.currentCall.setCameraId(deviceInfo.deviceId);
	            }
	            if (this.reconnectingCameraId === deviceInfo.deviceId && !Call.Hardware.isCameraOn) {
	              this.updateCameraSettingsInCurrentCallAfterReconnecting(deviceInfo.deviceId);
	            }
	            this.checkAvailableCamera();
	            break;
	          case "audiooutput":
	            if (this.callView && deviceInfo.deviceId === 'default' || isForceUse) {
	              var _newDeviceId = Call.Hardware.getDefaultDeviceIdByGroupId(deviceInfo.groupId, 'audiooutput');
	              this.callView.setSpeakerId(_newDeviceId);
	            }
	            break;
	        }
	      }
	    }
	  }, {
	    key: "removeDevicesFromCurrentCall",
	    value: function removeDevicesFromCurrentCall(deviceList) {
	      if (!this.currentCall || !this.currentCall.ready) {
	        return;
	      }
	      for (var i = 0; i < deviceList.length; i++) {
	        var deviceInfo = deviceList[i];
	        switch (deviceInfo.kind) {
	          case "audioinput":
	            if (this.currentCall.microphoneId == deviceInfo.deviceId) {
	              var microphoneIds = Object.keys(Call.Hardware.microphoneList);
	              var deviceId = void 0;
	              if (microphoneIds.includes('default')) {
	                var deviceGroup = Call.Hardware.getDeviceGroupIdByDeviceId('default', 'audioinput');
	                deviceId = Call.Hardware.getDefaultDeviceIdByGroupId(deviceGroup, 'audioinput');
	              }
	              if (!deviceId) {
	                deviceId = microphoneIds.length > 0 ? microphoneIds[0] : "";
	              }
	              this.currentCall.setMicrophoneId(deviceId);
	              if (this.currentCall.provider === Call.Provider.Bitrix) {
	                this.callView.setMicrophoneId(deviceId);
	              }
	            }
	            this.checkAvailableMicrophone();
	            break;
	          case "videoinput":
	            if (this.currentCall.cameraId == deviceInfo.deviceId) {
	              var cameraIds = Object.keys(Call.Hardware.cameraList);
	              this.currentCall.setCameraId(cameraIds.length > 0 ? cameraIds[0] : "");
	            }
	            this.checkAvailableCamera();
	            break;
	          case "audiooutput":
	            if (this.callView && this.callView.speakerId == deviceInfo.deviceId) {
	              var speakerIds = Object.keys(Call.Hardware.audioOutputList);
	              var _deviceId = void 0;
	              if (speakerIds.includes('default')) {
	                var _deviceGroup = Call.Hardware.getDeviceGroupIdByDeviceId('default', 'audiooutput');
	                _deviceId = Call.Hardware.getDefaultDeviceIdByGroupId(_deviceGroup, 'audiooutput');
	              }
	              if (!_deviceId) {
	                this.callView.setSpeakerId(speakerIds.length > 0 ? speakerIds[0] : "");
	              }
	            }
	            break;
	        }
	      }
	    }
	  }, {
	    key: "startCall",
	    value: function startCall(videoEnabled) {
	      var _this17 = this;
	      var viewerMode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      if (this.initCallPromise) {
	        return;
	      }
	      if (im_lib_utils.Utils.device.isMobile()) {
	        this.callView.show();
	        this.callView.setButtonCounter('chat', this.getDialogData().counter);
	      } else {
	        this.callView.setLayout(Call.View.Layout.Grid);
	      }
	      this.callView.setUiState(Call.View.UiState.Calling);
	      if (videoEnabled && !Call.Hardware.hasCamera()) {
	        this.showNotification(BX.message('IM_CALL_NO_CAMERA_ERROR'));
	        videoEnabled = false;
	      }
	      if (this.localVideoStream) {
	        if (!videoEnabled) {
	          this.stopLocalVideoStream();
	        }
	      }
	      this.controller.getStore().commit('conference/startCall');
	      var callTokenPromise = Promise.resolve(this.callToken);
	      if (call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled && !this.callToken) {
	        callTokenPromise = call_lib_callTokenManager.CallTokenManager.getToken(this.params.chatId);
	      }
	      callTokenPromise.then(function (callToken) {
	        _this17.callToken = callToken;
	        call_lib_callTokenManager.CallTokenManager.setToken(_this17.params.chatId, _this17.callToken);
	        return _this17.callEngine.createCall(_this17.getCallConfig(videoEnabled)).then(function (e) {
	          console.warn('call created', e);
	          im_lib_logger.Logger.warn('call created', e);
	          _this17.currentCall = e.call;
	          //this.currentCall.useHdVideo(Call.Hardware.preferHdQuality);
	          _this17.currentCall.useHdVideo(true);
	          if (_this17.promotedToAdminTimeout) {
	            clearTimeout(_this17.promotedToAdminTimeout);
	          }
	          if (!call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled) {
	            _this17.onUpdateCallCopilotState({
	              isTrackRecordOn: _this17.currentCall.isCopilotActive
	            });
	          }
	          if (Call.Hardware.defaultMicrophone) {
	            _this17.currentCall.setMicrophoneId(Call.Hardware.defaultMicrophone);
	          }
	          if (Call.Hardware.defaultCamera) {
	            _this17.currentCall.setCameraId(Call.Hardware.defaultCamera);
	          }
	          _this17.checkAvailableMicrophone();
	          _this17.checkAvailableCamera();
	          if (!im_lib_utils.Utils.device.isMobile()) {
	            _this17.callView.setLayout(Call.View.Layout.Grid);
	          }
	          if (call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled) {
	            var userData = _this17.controller.getStore().getters['users/get'](_this17.controller.getUserId(), true);
	            _this17.callView.appendUsers([userData.id]);
	            _this17.callView.updateUserData(babelHelpers.defineProperty({}, userData.id, userData));
	          } else {
	            _this17.callView.appendUsers(_this17.currentCall.getUsers());
	            Call.Util.getUsers(_this17.currentCall.id, _this17.getCallUsers(true)).then(function (userData) {
	              _this17.controller.getStore().dispatch('users/set', Object.values(userData));
	              _this17.controller.getStore().dispatch('conference/setUsers', {
	                users: Object.keys(userData)
	              });
	              _this17.callView.updateUserData(userData);
	            });
	          }
	          _this17.releasePreCall();
	          _this17.bindCallEvents();
	          _this17.updateCallUser(_this17.currentCall.userId, {
	            microphoneState: !Call.Hardware.isMicrophoneMuted
	          });
	          if (e.isNew) {
	            var _this17$currentCall, _this17$currentCall$a;
	            call_lib_analytics.Analytics.getInstance().onStartVideoconf({
	              callId: (_this17$currentCall = _this17.currentCall) === null || _this17$currentCall === void 0 ? void 0 : _this17$currentCall.uuid,
	              withVideo: videoEnabled,
	              mediaParams: {
	                video: Call.Hardware.isCameraOn,
	                audio: !Call.Hardware.isMicrophoneMuted
	              },
	              status: call_lib_analytics.Analytics.AnalyticsStatus.success,
	              isCopilotActive: _this17.currentCall.isCopilotActive,
	              userCounter: (_this17$currentCall$a = _this17.currentCall.associatedEntity) === null || _this17$currentCall$a === void 0 ? void 0 : _this17$currentCall$a.userCounter
	            });
	            _this17.currentCall.inviteUsers();
	          } else {
	            var _this17$currentCall2;
	            _this17.currentCall.answer({
	              joinAsViewer: viewerMode
	            });
	            call_lib_analytics.Analytics.getInstance().onJoinVideoconf({
	              callId: (_this17$currentCall2 = _this17.currentCall) === null || _this17$currentCall2 === void 0 ? void 0 : _this17$currentCall2.uuid,
	              withVideo: videoEnabled,
	              mediaParams: {
	                video: Call.Hardware.isCameraOn,
	                audio: !Call.Hardware.isMicrophoneMuted
	              },
	              status: call_lib_analytics.Analytics.AnalyticsStatus.success
	            });
	          }
	          _this17.onUpdateLastUsedCameraId();
	        });
	      })["catch"](function (error) {
	        im_lib_logger.Logger.error('creating call error', error);
	        var errorCode = 'UNKNOWN_ERROR';
	        if (main_core.Type.isString(error)) {
	          errorCode = error;
	        } else if (main_core.Type.isObject(error) && error instanceof Call.JoinResponseError) {
	          errorCode = error.name;
	        } else if (main_core.Type.isPlainObject(error) && error.code) {
	          errorCode = error.code == 'access_denied' ? 'ACCESS_DENIED' : error.code;
	        }
	        call_lib_analytics.Analytics.getInstance().onStartCallError({
	          callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	          errorCode: errorCode,
	          errorMessage: error === null || error === void 0 ? void 0 : error.message
	        });
	        _this17.initCallPromise = null;
	      });
	    }
	    /**
	     * @param {int} callUuid
	     * @param {object} options
	     */
	  }, {
	    key: "joinCall",
	    value: function joinCall(callId, callUuid, options) {
	      var _this18 = this;
	      if (this.initCallPromise) {
	        return;
	      }
	      var video = BX.prop.getBoolean(options, "video", false);
	      var joinAsViewer = BX.prop.getBoolean(options, "joinAsViewer", false);
	      Call.Hardware.isCameraOn = !!video;
	      if (im_lib_utils.Utils.device.isMobile()) {
	        this.callView.show();
	      } else {
	        this.callView.setLayout(Call.View.Layout.Grid);
	      }
	      if (joinAsViewer) {
	        this.callView.setLocalUserDirection(Call.EndpointDirection.RecvOnly);
	      } else {
	        this.callView.setLocalUserDirection(Call.EndpointDirection.SendRecv);
	      }
	      this.callView.setUiState(Call.View.UiState.Calling);
	      var isLegacyCall = Boolean(callId) || this.callScheme === Call.CallScheme.classic || !this.callScheme && !call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled;
	      this.initCallPromise = Promise.resolve(this.callToken);
	      if (!isLegacyCall && !this.callToken) {
	        this.initCallPromise = call_lib_callTokenManager.CallTokenManager.getToken(this.params.chatId);
	      }
	      this.initCallPromise.then(function (callToken) {
	        if (isLegacyCall) {
	          return Call.EngineLegacy.getCallWithId(callId, _this18.getCallConfig(video));
	        }
	        _this18.callToken = callToken;
	        call_lib_callTokenManager.CallTokenManager.setToken(_this18.params.chatId, _this18.callToken);
	        return Call.Engine.getCallWithId(callUuid, _this18.getCallConfig(video, callUuid));
	      }).then(function (result) {
	        var _this18$currentCall, _this18$currentCall2;
	        _this18.currentCall = result.call;
	        _this18.releasePreCall();
	        _this18.bindCallEvents();
	        if (_this18.promotedToAdminTimeout) {
	          clearTimeout(_this18.promotedToAdminTimeout);
	        }
	        if (((_this18$currentCall = _this18.currentCall) === null || _this18$currentCall === void 0 ? void 0 : _this18$currentCall.scheme) === Call.CallScheme.classic || !call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled) {
	          _this18.onUpdateCallCopilotState({
	            isTrackRecordOn: _this18.currentCall.isCopilotActive
	          });
	        }
	        _this18.controller.getStore().commit('conference/startCall');
	        if (((_this18$currentCall2 = _this18.currentCall) === null || _this18$currentCall2 === void 0 ? void 0 : _this18$currentCall2.scheme) === Call.CallScheme.jwt) {
	          var userData = _this18.controller.getStore().getters['users/get'](_this18.controller.getUserId(), true);
	          _this18.callView.appendUsers([userData.id]);
	          _this18.callView.updateUserData(babelHelpers.defineProperty({}, userData.id, userData));
	        } else {
	          _this18.callView.appendUsers(_this18.currentCall.getUsers());
	          Call.Util.getUsers(_this18.currentCall.id, _this18.getCallUsers(true)).then(function (userData) {
	            _this18.controller.getStore().dispatch('users/set', Object.values(userData));
	            _this18.controller.getStore().dispatch('conference/setUsers', {
	              users: Object.keys(userData)
	            });
	            _this18.callView.updateUserData(userData);
	          });
	        }
	        if (!joinAsViewer) {
	          //this.currentCall.useHdVideo(Call.Hardware.preferHdQuality);
	          _this18.currentCall.useHdVideo(true);
	          if (Call.Hardware.defaultMicrophone) {
	            _this18.currentCall.setMicrophoneId(Call.Hardware.defaultMicrophone);
	          }
	          if (Call.Hardware.defaultCamera) {
	            _this18.currentCall.setCameraId(Call.Hardware.defaultCamera);
	          }
	          _this18.checkAvailableMicrophone();
	          _this18.checkAvailableCamera();
	          _this18.updateCallUser(_this18.currentCall.userId, {
	            microphoneState: !Call.Hardware.isMicrophoneMuted
	          });
	        }
	        _this18.currentCall.answer({
	          joinAsViewer: joinAsViewer
	        });
	        _this18.onUpdateLastUsedCameraId();
	      })["catch"](function (error) {
	        console.error(error);
	        var errorCode = 'UNKNOWN_ERROR';
	        if (main_core.Type.isString(error)) {
	          errorCode = error;
	        } else if (main_core.Type.isObject(error) && error instanceof Call.JoinResponseError) {
	          errorCode = error.name;
	        } else if (main_core.Type.isPlainObject(error) && error.code) {
	          errorCode = error.code === 'access_denied' ? 'ACCESS_DENIED' : error.code;
	        }
	        call_lib_analytics.Analytics.getInstance().onJoinCallError({
	          callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	          errorCode: errorCode,
	          callId: callUuid,
	          errorMessage: error === null || error === void 0 ? void 0 : error.message
	        });
	        _this18.initCallPromise = null;
	      });
	    }
	  }, {
	    key: "endCall",
	    value: function endCall() {
	      var _this$currentCall;
	      var finishCall = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      if (this.isRecording()) {
	        call_lib_analytics.Analytics.getInstance().onRecordStop({
	          callId: this.currentCall.uuid,
	          callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	          subSection: finishCall ? call_lib_analytics.Analytics.AnalyticsSubSection.contextMenu : call_lib_analytics.Analytics.AnalyticsSubSection.window,
	          element: finishCall ? call_lib_analytics.Analytics.AnalyticsElement.finishForAllButton : call_lib_analytics.Analytics.AnalyticsElement.disconnectButton,
	          recordTime: Call.Util.getRecordTimeText(this.callRecordInfo)
	        });
	        this.callRecordInfo = null;
	        BXDesktopSystem.CallRecordStop();
	      }
	      _classPrivateMethodGet(this, _stopRecordCall, _stopRecordCall2).call(this);
	      this.setConferenceHasErrorInCall(false);
	      this.showFeedback = !!((_this$currentCall = this.currentCall) !== null && _this$currentCall !== void 0 && _this$currentCall.wasConnected);
	      if (this.currentCall) {
	        this.callDetails = {
	          id: this.currentCall.uuid,
	          provider: this.currentCall.provider,
	          userCount: this.currentCall.users.length,
	          browser: Call.Util.getBrowserForStatistics(),
	          isMobile: BX.browser.IsMobile(),
	          isConference: true
	        };
	        this.removeCallEvents();
	        this.removeAdditionalEvents();
	        this.currentCall.hangup(false, '', finishCall);
	      }
	      if (im_lib_utils.Utils.platform.isBitrixDesktop()) {
	        if (this.floatingScreenShareWindow) {
	          this.floatingScreenShareWindow.destroy();
	          this.floatingScreenShareWindow = null;
	        }
	        window.close();
	        // if the conference was opened incorrectly, then "window.close();" may not work in some cases
	        // as a workaround, we can redirect the user back to the portal's front page.
	        location.href = '/';
	      } else {
	        this.callView.releaseLocalMedia();
	        this.callView.close();
	        this.closeReconnectionBaloon();
	        this.setError(im_const.ConferenceErrorCode.userLeftCall);
	        this.controller.getStore().commit('conference/endCall');
	      }
	      if (this.riseYouHandToTalkPopup) {
	        this.riseYouHandToTalkPopup.close();
	        this.riseYouHandToTalkPopup = null;
	      }
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.textarea.focus, this.onInputFocusHandler);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.textarea.blur, this.onInputBlurHandler);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.conference.userRenameFocus, this.onInputFocusHandler);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.conference.userRenameBlur, this.onInputBlurHandler);
	    }
	  }, {
	    key: "restart",
	    value: function restart() {
	      console.trace(" restart");
	      if (this.currentCall) {
	        this.removeCallEvents();
	        this.currentCall = null;
	      }
	      if (this.promotedToAdminTimeout) {
	        clearTimeout(this.promotedToAdminTimeout);
	      }
	      if (this.callView) {
	        this.callView.releaseLocalMedia();
	        this.callView.close();
	        this.closeReconnectionBaloon();
	        this.callView.destroy();
	        this.callView = null;
	      }
	      this.initCallInterface();
	      this.initCall();
	      this.controller.getStore().commit('conference/endCall');
	    }
	  }, {
	    key: "kickFromCall",
	    value: function kickFromCall() {
	      this.setError(im_const.ConferenceErrorCode.kickedFromCall);
	      this.pullClient.disconnect();
	      this.endCall();
	    }
	  }, {
	    key: "getCallUsers",
	    value: function getCallUsers(includeSelf) {
	      if (!this.currentCall) {
	        return [];
	      }
	      var result = Object.keys(this.currentCall.getUsers());
	      if (includeSelf) {
	        result.push(this.currentCall.userId);
	      }
	      return result;
	    }
	  }, {
	    key: "getActiveCallUsers",
	    value: function getActiveCallUsers() {
	      var userStates = this.currentCall.getUsers();
	      var activeUsers = [];
	      for (var userId in userStates) {
	        if (userStates.hasOwnProperty(userId)) {
	          if (userStates[userId] === Call.UserState.Connected || userStates[userId] === Call.UserState.Connecting || userStates[userId] === Call.UserState.Calling) {
	            activeUsers.push(userId);
	          }
	        }
	      }
	      return activeUsers;
	    }
	  }, {
	    key: "setLocalVideoStream",
	    value: function setLocalVideoStream(stream) {
	      this.localVideoStream = stream;
	    }
	  }, {
	    key: "updateMediaDevices",
	    value: function updateMediaDevices() {
	      Call.Hardware.getCurrentDeviceList();
	      console.log('updateMediaDevices');
	    }
	  }, {
	    key: "stopLocalVideoStream",
	    value: function stopLocalVideoStream() {
	      if (this.localVideoStream) {
	        this.localVideoStream.getTracks().forEach(function (tr) {
	          return tr.stop();
	        });
	      }
	      this.localVideoStream = null;
	    }
	  }, {
	    key: "setSelectedCamera",
	    value: function setSelectedCamera(cameraId) {
	      if (this.callView) {
	        this.callView.setCameraId(cameraId);
	      }
	    }
	  }, {
	    key: "setSelectedMic",
	    value: function setSelectedMic(micId) {
	      if (this.callView) {
	        this.callView.setMicrophoneId(micId);
	      }
	    }
	  }, {
	    key: "getFeature",
	    value: function getFeature(id) {
	      if (typeof this.featureConfig[id] === 'undefined') {
	        return {
	          id: id,
	          state: ConferenceApplication.FeatureState.Enabled,
	          articleCode: ''
	        };
	      }
	      return this.featureConfig[id];
	    }
	  }, {
	    key: "getFeatureState",
	    value: function getFeatureState(id) {
	      return this.getFeature(id).state;
	    }
	  }, {
	    key: "canRecord",
	    value: function canRecord() {
	      return im_lib_utils.Utils.platform.isBitrixDesktop() && im_lib_utils.Utils.platform.getDesktopVersion() >= 54;
	    }
	  }, {
	    key: "isRecording",
	    value: function isRecording() {
	      return this.canRecord() && this.callRecordState != Call.View.RecordState.Stopped;
	    }
	  }, {
	    key: "showFeatureLimitSlider",
	    value: function showFeatureLimitSlider(id) {
	      var articleCode = this.getFeature(id).articleCode;
	      if (!articleCode || !window.BX.UI.InfoHelper) {
	        console.warn('Limit article not found', id);
	        return false;
	      }
	      window.BX.UI.InfoHelper.show(articleCode);
	      return true;
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(notificationText, actions) {
	      if (!actions) {
	        actions = [];
	      }
	      BX.UI.Notification.Center.notify({
	        content: main_core.Text.encode(notificationText),
	        position: "top-right",
	        autoHideDelay: 5000,
	        closeButton: true,
	        actions: actions
	      });
	    }
	  }, {
	    key: "showMicMutedNotification",
	    value: function showMicMutedNotification() {
	      var _this19 = this;
	      if (this.mutePopup || !this.callView || this.riseYouHandToTalkPopup || !Call.Util.havePermissionToBroadcast('mic')) {
	        return;
	      }
	      this.mutePopup = new Call.Hint({
	        bindElement: this.callView.buttons.microphone.elements.icon,
	        targetContainer: this.callView.container,
	        buttons: [this.createUnmuteButton()],
	        onClose: function onClose() {
	          _this19.allowMutePopup = false;
	          _this19.mutePopup.destroy();
	          _this19.mutePopup = null;
	        }
	      });
	      this.mutePopup.show();
	    }
	  }, {
	    key: "createUnmuteButton",
	    value: function createUnmuteButton() {
	      var _this20 = this;
	      return new BX.UI.Button({
	        baseClass: "ui-btn ui-btn-icon-mic",
	        text: BX.message("IM_CALL_UNMUTE_MIC"),
	        size: BX.UI.Button.Size.EXTRA_SMALL,
	        color: BX.UI.Button.Color.LIGHT_BORDER,
	        noCaps: true,
	        round: true,
	        events: {
	          click: function click() {
	            _this20.onCallViewToggleMuteButtonClick({
	              data: {
	                muted: false
	              }
	            });
	            _this20.mutePopup.destroy();
	            _this20.mutePopup = null;
	          }
	        }
	      });
	    }
	  }, {
	    key: "showWebScreenSharePopup",
	    value: function showWebScreenSharePopup() {
	      if (this.webScreenSharePopup) {
	        this.webScreenSharePopup.show();
	        return;
	      }
	      this.webScreenSharePopup = new Call.WebScreenSharePopup({
	        bindElement: this.callView.buttons.screen.elements.root,
	        targetContainer: this.callView.container,
	        onClose: function () {
	          this.webScreenSharePopup.destroy();
	          this.webScreenSharePopup = null;
	        }.bind(this),
	        onStopSharingClick: function () {
	          this.onCallViewToggleScreenSharingButtonClick();
	          this.webScreenSharePopup.destroy();
	          this.webScreenSharePopup = null;
	        }.bind(this)
	      });
	      this.webScreenSharePopup.show();
	    }
	  }, {
	    key: "isViewerMode",
	    value: function isViewerMode() {
	      var viewerMode = false;
	      var isBroadcast = this.isBroadcast();
	      if (isBroadcast) {
	        var presenters = this.getBroadcastPresenters();
	        var currentUserId = this.controller.getStore().state.application.common.userId;
	        var isCurrentUserPresenter = presenters.includes(currentUserId);
	        viewerMode = isBroadcast && !isCurrentUserPresenter;
	      }
	      return viewerMode;
	    }
	  }, {
	    key: "onCallCreated",
	    value: function onCallCreated(e) {
	      var _this21 = this;
	      im_lib_logger.Logger.warn('we got event onCallCreated', e);
	      if (this.preCall || this.currentCall) {
	        return;
	      }
	      var call = e.call;
	      if (call.associatedEntity.type === 'chat' && call.associatedEntity.id === this.params.dialogId) {
	        this.preCall = e.call;
	        this.updatePreCallCounter();
	        this.preCall.addEventListener(Call.Event.onUserStateChanged, this.onPreCallUserStateChangedHandler);
	        this.preCall.addEventListener(Call.Event.onDestroy, this.onPreCallDestroyHandler);
	        if (this.waitingForCallStatus) {
	          this.callEventReceived = true;
	        }
	        this.setConferenceStatus(true);
	        this.setConferenceStartDate(e.call.startDate);
	      }
	      var userReadyToJoin = this.getConference().common.userReadyToJoin;
	      if (userReadyToJoin) {
	        var viewerMode = this.isViewerMode();
	        var videoEnabled = this.getConference().common.joinWithVideo;
	        im_lib_logger.Logger.warn('ready to join call after waiting', videoEnabled, viewerMode);
	        setTimeout(function () {
	          Call.Hardware.init().then(function () {
	            if (viewerMode && _this21.preCall) {
	              _this21.joinCall(_this21.preCall.id, _this21.preCall.uuid, {
	                joinAsViewer: true
	              });
	            } else {
	              _this21.joinCall(_this21.preCall.id, _this21.preCall.uuid, {
	                video: videoEnabled
	              });
	            }
	          });
	        }, 1000);
	      }
	    }
	  }, {
	    key: "releasePreCall",
	    value: function releasePreCall() {
	      if (this.preCall) {
	        this.preCall.removeEventListener(Call.Event.onUserStateChanged, this.onPreCallUserStateChangedHandler);
	        this.preCall.removeEventListener(Call.Event.onDestroy, this.onPreCallDestroyHandler);
	        this.preCall = null;
	      }
	    }
	  }, {
	    key: "onPreCallDestroy",
	    value: function onPreCallDestroy(e) {
	      if (this.waitingForCallStatusTimeout) {
	        clearTimeout(this.waitingForCallStatusTimeout);
	      }
	      this.setConferenceStatus(false);
	      this.releasePreCall();
	    }
	  }, {
	    key: "onPreCallUserStateChanged",
	    value: function onPreCallUserStateChanged(e) {
	      this.updatePreCallCounter();
	    }
	  }, {
	    key: "updatePreCallCounter",
	    value: function updatePreCallCounter() {
	      if (this.preCall) {
	        this.controller.getStore().commit('conference/common', {
	          userInCallCount: this.preCall.getParticipatingUsers().length
	        });
	      } else {
	        this.controller.getStore().commit('conference/common', {
	          userInCallCount: 0
	        });
	      }
	    }
	  }, {
	    key: "createVideoStrategy",
	    value: function createVideoStrategy() {
	      if (this.videoStrategy) {
	        this.videoStrategy.destroy();
	      }
	      var strategyType = im_lib_utils.Utils.device.isMobile() ? VideoStrategy.Type.OnlySpeaker : VideoStrategy.Type.AllowAll;
	      this.videoStrategy = new VideoStrategy({
	        call: this.currentCall,
	        callView: this.callView,
	        strategyType: strategyType
	      });
	    }
	  }, {
	    key: "removeVideoStrategy",
	    value: function removeVideoStrategy() {
	      if (this.videoStrategy) {
	        this.videoStrategy.destroy();
	      }
	      this.videoStrategy = null;
	    }
	  }, {
	    key: "onCallReplaceCamera",
	    value: function onCallReplaceCamera(event) {
	      var cameraId = event.data.deviceId;
	      if (this.reconnectingCameraId) {
	        this.setReconnectingCameraId(null);
	      }
	      Call.Hardware.defaultCamera = cameraId;
	      if (this.currentCall) {
	        this.currentCall.setCameraId(cameraId);
	      } else {
	        this.template.$emit('cameraSelected', cameraId);
	      }
	    }
	  }, {
	    key: "onCallReplaceMicrophone",
	    value: function onCallReplaceMicrophone(event) {
	      var microphoneId = event.data.deviceId;
	      Call.Hardware.defaultMicrophone = microphoneId.deviceId;
	      if (this.callView) {
	        this.callView.setMicrophoneId(microphoneId);
	      }
	      if (this.currentCall) {
	        this.currentCall.setMicrophoneId(microphoneId);
	      } else {
	        this.template.$emit('micSelected', event.data.deviceId);
	      }
	    }
	  }, {
	    key: "onCallReplaceSpeaker",
	    value: function onCallReplaceSpeaker(event) {
	      Call.Hardware.defaultSpeaker = event.data.deviceId;
	    }
	  }, {
	    key: "onCallViewHasMainStream",
	    value: function onCallViewHasMainStream(event) {
	      if (this.currentCall && this.currentCall.provider === Call.Provider.Bitrix) {
	        this.currentCall.setMainStream(event.data.userId);
	      }
	    }
	  }, {
	    key: "_onCallViewTurnOffParticipantMic",
	    value: function _onCallViewTurnOffParticipantMic(e) {
	      this.currentCall.turnOffParticipantStream({
	        typeOfStream: 'mic',
	        userId: e.userId,
	        fromUserId: this.callEngine.getCurrentUserId()
	      });
	      call_lib_analytics.Analytics.getInstance().onTurnOffParticipantStream({
	        callId: this._getCallIdentifier(this.currentCall),
	        callType: this.getCallType(),
	        typeOfSetting: 'mic'
	      });
	    }
	  }, {
	    key: "_onCallViewTurnOffParticipantCam",
	    value: function _onCallViewTurnOffParticipantCam(e) {
	      this.currentCall.turnOffParticipantStream({
	        typeOfStream: 'cam',
	        userId: e.userId,
	        fromUserId: this.callEngine.getCurrentUserId()
	      });
	      call_lib_analytics.Analytics.getInstance().onTurnOffParticipantStream({
	        callId: this._getCallIdentifier(this.currentCall),
	        callType: this.getCallType(),
	        typeOfSetting: 'cam'
	      });
	    }
	  }, {
	    key: "_onCallViewTurnOffParticipantScreenshare",
	    value: function _onCallViewTurnOffParticipantScreenshare(e) {
	      this.currentCall.turnOffParticipantStream({
	        typeOfStream: 'screenshare',
	        userId: e.userId,
	        fromUserId: this.callEngine.getCurrentUserId()
	      });
	      call_lib_analytics.Analytics.getInstance().onTurnOffParticipantStream({
	        callId: this._getCallIdentifier(this.currentCall),
	        callType: this.getCallType(),
	        typeOfSetting: 'screenshare'
	      });
	    }
	  }, {
	    key: "_onCallViewAllowSpeakPermission",
	    value: function _onCallViewAllowSpeakPermission(e) {
	      this.currentCall.allowSpeakPermission({
	        allow: true,
	        userId: e.userId
	      });
	      call_lib_analytics.Analytics.getInstance().onAllowPermissionToSpeakResponse({
	        callId: this._getCallIdentifier(this.currentCall),
	        callType: this.getCallType()
	      });
	    }
	  }, {
	    key: "_onCallViewDisallowSpeakPermission",
	    value: function _onCallViewDisallowSpeakPermission(e) {
	      this.currentCall.allowSpeakPermission({
	        allow: false,
	        userId: e.userId
	      });
	      call_lib_analytics.Analytics.getInstance().onDisallowPermissionToSpeakResponse({
	        callId: this._getCallIdentifier(this.currentCall),
	        callType: this.getCallType()
	      });
	    }
	  }, {
	    key: "onCallViewChangeHdVideo",
	    value: function onCallViewChangeHdVideo(event) {
	      Call.Hardware.preferHdQuality = event.data.allowHdVideo;
	    }
	  }, {
	    key: "onCallViewChangeMicAutoParams",
	    value: function onCallViewChangeMicAutoParams(event) {
	      Call.Hardware.enableMicAutoParameters = event.data.allowMicAutoParams;
	    }
	  }, {
	    key: "onCallViewChangeFaceImprove",
	    value: function onCallViewChangeFaceImprove(event) {
	      if (!im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	        return;
	      }
	      im_v2_lib_desktopApi.DesktopApi.setCameraSmoothingStatus(event.data.faceImproveEnabled);
	    }
	  }, {
	    key: "onCallViewUserRename",
	    value: function onCallViewUserRename(event) {
	      var newName = event.data.newName;
	      if (!this.isExternalUser()) {
	        return false;
	      }
	      if (im_lib_utils.Utils.device.isMobile()) {
	        this.renameGuestMobile(newName);
	      } else {
	        this.renameGuest(newName);
	      }
	    }
	  }, {
	    key: "onCallViewUserPinned",
	    value: function onCallViewUserPinned(event) {
	      if (event.data.userId) {
	        this.updateCallUser(event.data.userId, {
	          pinned: true
	        });
	        return true;
	      }
	      this.controller.getStore().dispatch('call/unpinUser');
	      return true;
	    }
	  }, {
	    key: "onCallToggleSubscribe",
	    value: function onCallToggleSubscribe(e) {
	      if (this.currentCall && this.currentCall.provider === Call.Provider.Bitrix && e.data) {
	        this.currentCall.toggleRemoteParticipantVideo(e.data.participantIds, e.data.showVideo, true);
	      }
	    }
	  }, {
	    key: "renameGuest",
	    value: function renameGuest(newName) {
	      var _this22 = this;
	      this.callView.localUser.userModel.renameRequested = true;
	      this.setUserName(newName).then(function () {
	        _this22.callView.localUser.userModel.wasRenamed = true;
	        im_lib_logger.Logger.log('setting name to', newName);
	      })["catch"](function (error) {
	        im_lib_logger.Logger.error('error setting name', error);
	      });
	    }
	  }, {
	    key: "renameGuestMobile",
	    value: function renameGuestMobile(newName) {
	      var _this23 = this;
	      this.setUserName(newName).then(function () {
	        im_lib_logger.Logger.log('setting mobile name to', newName);
	        if (_this23.callView.renameSlider) {
	          _this23.callView.renameSlider.close();
	        }
	      })["catch"](function (error) {
	        im_lib_logger.Logger.error('error setting name', error);
	      });
	    }
	  }, {
	    key: "onCallButtonClick",
	    value: function onCallButtonClick(event) {
	      var buttonName = event.data.buttonName;
	      im_lib_logger.Logger.warn('Button clicked!', buttonName);
	      var handlers = {
	        hangup: this.onCallViewHangupButtonClick.bind(this),
	        hangupOptions: this._onCallViewHangupOptionsButtonClick.bind(this),
	        close: this.onCallViewCloseButtonClick.bind(this),
	        //inviteUser: this.onCallViewInviteUserButtonClick.bind(this),
	        toggleMute: this.onCallViewToggleMuteButtonClick.bind(this),
	        toggleScreenSharing: this.onCallViewToggleScreenSharingButtonClick.bind(this),
	        record: this.onCallViewRecordButtonClick.bind(this),
	        toggleVideo: this.onCallViewToggleVideoButtonClick.bind(this),
	        toggleSpeaker: this.onCallViewToggleSpeakerButtonClick.bind(this),
	        showChat: this.onCallViewShowChatButtonClick.bind(this),
	        toggleUsers: this.onCallViewToggleUsersButtonClick.bind(this),
	        share: this.onCallViewShareButtonClick.bind(this),
	        fullscreen: this.onCallViewFullScreenButtonClick.bind(this),
	        floorRequest: this.onCallViewFloorRequestButtonClick.bind(this),
	        feedback: this.onCallViewFeedbackButtonClick.bind(this),
	        callcontrol: this._onCallcontrolButtonClick.bind(this),
	        onUserClick: this.onCallUserClick.bind(this),
	        copilot: this.onCallCopilotButtonClick.bind(this)
	      };
	      if (handlers[buttonName]) {
	        handlers[buttonName](event);
	      } else {
	        im_lib_logger.Logger.error('Button handler not found!', buttonName);
	      }
	    }
	  }, {
	    key: "onCallViewHangupButtonClick",
	    value: function onCallViewHangupButtonClick(e) {
	      var _this$currentCall2;
	      call_lib_analytics.Analytics.getInstance().onDisconnectCall({
	        callId: (_this$currentCall2 = this.currentCall) === null || _this$currentCall2 === void 0 ? void 0 : _this$currentCall2.uuid,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	        subSection: call_lib_analytics.Analytics.AnalyticsSubSection.finishButton,
	        mediaParams: {
	          video: Call.Hardware.isCameraOn,
	          audio: !Call.Hardware.isMicrophoneMuted
	        }
	      });
	      this.stopLocalVideoStream();
	      this.endCall();
	    }
	  }, {
	    key: "_onCallViewHangupOptionsButtonClick",
	    value: function _onCallViewHangupOptionsButtonClick() {
	      var _this24 = this;
	      if (this.hangupOptionsMenu) {
	        this.hangupOptionsMenu.destroy();
	        return;
	      }
	      var targetNodeWidth = this.callView.buttons.hangupOptions.elements.root.offsetWidth;
	      var menuItems = [{
	        text: BX.message("CALL_M_BTN_HANGUP_OPTION_FINISH"),
	        onclick: function onclick() {
	          var _this24$currentCall, _this24$currentCall2, _this24$currentCall3;
	          call_lib_analytics.Analytics.getInstance().onFinishCall({
	            callId: (_this24$currentCall = _this24.currentCall) === null || _this24$currentCall === void 0 ? void 0 : _this24$currentCall.uuid,
	            callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	            status: call_lib_analytics.Analytics.AnalyticsStatus.finishedForAll,
	            chatId: (_this24$currentCall2 = _this24.currentCall) === null || _this24$currentCall2 === void 0 ? void 0 : _this24$currentCall2.associatedEntity.id,
	            callUsersCount: _this24.getCallUsers(true).length,
	            callLength: Call.Util.getTimeText((_this24$currentCall3 = _this24.currentCall) === null || _this24$currentCall3 === void 0 ? void 0 : _this24$currentCall3.startDate)
	          });
	          _this24.stopLocalVideoStream();
	          _this24.endCall(true);
	        }
	      }, {
	        text: BX.message("CALL_M_BTN_HANGUP_OPTION_LEAVE"),
	        onclick: function onclick() {
	          var _this24$currentCall4;
	          call_lib_analytics.Analytics.getInstance().onDisconnectCall({
	            callId: (_this24$currentCall4 = _this24.currentCall) === null || _this24$currentCall4 === void 0 ? void 0 : _this24$currentCall4.uuid,
	            callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	            subSection: call_lib_analytics.Analytics.AnalyticsSubSection.contextMenu,
	            mediaParams: {
	              video: Call.Hardware.isCameraOn,
	              audio: !Call.Hardware.isMicrophoneMuted
	            }
	          });
	          _this24.stopLocalVideoStream();
	          _this24.endCall(false);
	        }
	      }];
	      this.hangupOptionsMenu = new BX.PopupMenuWindow({
	        className: 'bx-messenger-videocall-hangup-options-container',
	        background: '#22272B',
	        contentBackground: '#22272B',
	        darkMode: true,
	        contentBorderRadius: '6px',
	        angle: false,
	        bindElement: this.callView.buttons.hangupOptions.elements.root,
	        targetContainer: this.container,
	        offsetTop: -15,
	        bindOptions: {
	          position: "top"
	        },
	        cacheable: false,
	        subMenuOptions: {
	          maxWidth: 450
	        },
	        events: {
	          onShow: function onShow(event) {
	            var popup = event.getTarget();
	            popup.getPopupContainer().style.display = 'block'; // bad hack

	            var offsetLeft = targetNodeWidth / 2 - popup.getPopupContainer().offsetWidth / 2;
	            popup.setOffset({
	              offsetLeft: offsetLeft + 40,
	              offsetTop: 0
	            });
	            popup.setAngle({
	              offset: popup.getPopupContainer().offsetWidth / 2 - 17
	            });
	          },
	          onDestroy: function onDestroy() {
	            return _this24.hangupOptionsMenu = null;
	          }
	        },
	        items: menuItems
	      });
	      this.hangupOptionsMenu.show();
	    }
	  }, {
	    key: "onCallViewCloseButtonClick",
	    value: function onCallViewCloseButtonClick(e) {
	      this.stopLocalVideoStream();
	      this.endCall();
	    }
	  }, {
	    key: "onCallViewToggleMuteButtonClick",
	    value: function onCallViewToggleMuteButtonClick(event) {
	      /*if (!Call.Hardware.hasMicrophone() &&  !event.data.muted)
	      {
	      	return;
	      }*/

	      call_lib_analytics.Analytics.getInstance().onToggleMicrophone({
	        muted: event.data.muted,
	        callId: this.currentCall ? this.currentCall.uuid : 0,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	      });

	      /*if (this.currentCall && !this.currentCall.microphoneId && !event.data.muted)
	      {
	      	this.currentCall.setMicrophoneId(Call.Hardware.defaultMicrophone);
	      }
	      	Call.Hardware.isMicrophoneMuted = event.data.muted;
	      if (!this.currentCall)
	      {
	      	this.template.$emit('setMicState', !event.data.muted);
	      }
	      	if (this.isRecording())
	      {
	      	BXDesktopSystem.CallRecordMute(event.data.muted);
	      }
	      	if (this.currentCall?.userId)
	      {
	      	this.updateCallUser(this.currentCall.userId, {microphoneState: !event.data.muted});
	      }*/

	      this._onCallViewToggleMuteHandler(event.data);
	    }
	  }, {
	    key: "_onCallViewToggleMuteHandler",
	    value: function _onCallViewToggleMuteHandler(e) {
	      var _this$currentCall3;
	      if (!e.muted && !(Call.Hardware !== null && Call.Hardware !== void 0 && Call.Hardware.hasMicrophone())) {
	        return;
	      }
	      var currentRoom = this.currentCall && this.currentCall.currentRoom && this.currentCall.currentRoom();
	      if (currentRoom && currentRoom.speaker != this.userId && !e.muted) {
	        this.currentCall.requestRoomSpeaker();
	        return;
	      }
	      if (this.currentCall && !this.currentCall.microphoneId && !e.muted) {
	        this.currentCall.setMicrophoneId(Call.Hardware.defaultMicrophone);
	      }
	      Call.Hardware.setIsMicrophoneMuted({
	        isMicrophoneMuted: e.muted,
	        calledProgrammatically: !!e.calledProgrammatically
	      });
	      if (this.floatingWindow) {
	        this.floatingWindow.setAudioMuted(e.muted);
	      }
	      if (this.mutePopup) {
	        this.mutePopup.close();
	      }
	      if (!e.muted) {
	        if (!Call.Util.havePermissionToBroadcast('mic')) {
	          this.showRiseYouHandToTalkNotification({
	            initiatorName: this.lastCalledChangeSettingsUserName
	          });
	        } else {
	          this.allowMutePopup = true;
	        }
	      }
	      if (this.isRecording()) {
	        BXDesktopSystem.CallRecordMute(e.muted);
	      }
	      if ((_this$currentCall3 = this.currentCall) !== null && _this$currentCall3 !== void 0 && _this$currentCall3.userId) {
	        this.updateCallUser(this.currentCall.userId, {
	          microphoneState: !e.muted
	        });
	      }
	      if (!this.currentCall) {
	        this.template.$emit('setMicState', !e.muted);
	      }
	    }
	  }, {
	    key: "onCallViewToggleScreenSharingButtonClick",
	    value: function onCallViewToggleScreenSharingButtonClick() {
	      call_lib_analytics.Analytics.getInstance().onScreenShareBtnClick({
	        callId: this.currentCall.uuid,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	      });
	      if (this.getFeatureState('screenSharing') === ConferenceApplication.FeatureState.Limited) {
	        this.showFeatureLimitSlider('screenSharing');
	        return;
	      }
	      if (this.getFeatureState('screenSharing') === ConferenceApplication.FeatureState.Disabled) {
	        return;
	      }
	      if (this.currentCall.isScreenSharingStarted()) {
	        this.currentCall.stopScreenSharing();
	        if (this.isRecording()) {
	          BXDesktopSystem.CallRecordStopSharing();
	        }
	        if (this.floatingScreenShareWindow) {
	          this.floatingScreenShareWindow.close();
	        }
	        if (this.webScreenSharePopup) {
	          this.webScreenSharePopup.close();
	        }
	      } else {
	        BX.ajax.runAction('call.Call.onShareScreen', {
	          data: {
	            callUuid: this.currentCall.uuid
	          }
	        });
	        this.currentCall.startScreenSharing();
	        this.togglePictureInPictureCallWindow();
	      }
	    }
	  }, {
	    key: "togglePictureInPictureCallWindow",
	    value: function togglePictureInPictureCallWindow() {
	      var config = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var isActiveStatePictureInPictureCallWindow = this.currentCall && (this.currentCall.isScreenSharingStarted() || config.isForceOpen) && !config.isForceClose;
	      if (!this.callView) {
	        return;
	      }
	      this.callView.isActivePiPFromController = isActiveStatePictureInPictureCallWindow;
	      this.callView.toggleStatePictureInPictureCallWindow(isActiveStatePictureInPictureCallWindow);
	    }
	  }, {
	    key: "onCallViewRecordButtonClick",
	    value: function onCallViewRecordButtonClick(event) {
	      call_lib_analytics.Analytics.getInstance().onRecordBtnClick({
	        callId: this.currentCall.uuid,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	      });
	      if (event.data.recordState === Call.View.RecordState.Started) {
	        if (this.getFeatureState('record') === ConferenceApplication.FeatureState.Limited) {
	          this.showFeatureLimitSlider('record');
	          return;
	        }
	        if (this.getFeatureState('record') === ConferenceApplication.FeatureState.Disabled) {
	          return;
	        }
	        if (this.canRecord()) {
	          // TODO: create popup menu with choice type of record - im/install/js/im/call/controller.js:1635
	          // Call.View.RecordType.Video / Call.View.RecordType.Audio

	          this.callView.setButtonActive('record', true);
	        } else {
	          if (window.BX.Helper) {
	            window.BX.Helper.show("redirect=detail&code=22079566");
	          }
	          return;
	        }
	      } else if (event.data.recordState === Call.View.RecordState.Paused) {
	        if (this.canRecord()) {
	          BXDesktopSystem.CallRecordPause(true);
	        }
	      } else if (event.data.recordState === Call.View.RecordState.Resumed) {
	        if (this.canRecord()) {
	          BXDesktopSystem.CallRecordPause(false);
	        }
	      } else if (event.data.recordState === Call.View.RecordState.Stopped) {
	        this.callView.setButtonActive('record', false);
	      }
	      this.currentCall.sendRecordState({
	        action: event.data.recordState,
	        date: new Date()
	      });
	      this.callRecordState = event.data.recordState;
	    }
	  }, {
	    key: "_onCallViewToggleVideoButtonClickHandler",
	    value: function _onCallViewToggleVideoButtonClickHandler(e) {
	      if (!Call.Hardware.initialized) {
	        return;
	      }
	      if (e.video && Object.values(Call.Hardware.cameraList).length === 0) {
	        this.showNotification(main_core.Loc.getMessage('IM_CALL_CAMERA_ERROR_FALLBACK_TO_MIC'));
	        return;
	      }
	      Call.Hardware.setIsCameraOn({
	        isCameraOn: e.video,
	        calledProgrammatically: Boolean(e.calledProgrammatically)
	      });
	      if (this.currentCall && !e.video && !this.currentCall.isScreenSharingStarted()) {
	        this.callView.releaseLocalMedia();
	      }
	      if (this.currentCall && !this.currentCall.cameraId && e.video) {
	        this.currentCall.setCameraId(Call.Hardware.defaultCamera);
	      }
	      if (!this.currentCall) {
	        this.template.$emit('setCameraState', e.video);
	      }
	    }
	  }, {
	    key: "onCallViewToggleVideoButtonClick",
	    value: function onCallViewToggleVideoButtonClick(event) {
	      /*if (!Call.Hardware.hasCamera() &&  event.data.video)
	      {
	      	this.showNotification(BX.message('IM_CALL_NO_CAMERA_ERROR'));
	      	return;
	      }*/

	      call_lib_analytics.Analytics.getInstance().onToggleCamera({
	        video: event.data.video,
	        callId: this.currentCall ? this.currentCall.uuid : 0,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	      });
	      this._onCallViewToggleVideoButtonClickHandler(event.data);

	      /*Call.Hardware.isCameraOn = event.data.video;
	      if (this.currentCall)
	      {
	      	if (!Call.Hardware.initialized)
	      	{
	      		return;
	      	}
	      	if (event.data.video && Object.values(Call.Hardware.cameraList).length === 0)
	      	{
	      		return;
	      	}
	      	if(!event.data.video)
	      	{
	      		this.callView.releaseLocalMedia();
	      	}
	      		if (!this.currentCall.cameraId && event.data.video)
	      	{
	      		this.currentCall.setCameraId(Call.Hardware.defaultCamera);
	      	}
	      }
	      else
	      {
	      	this.template.$emit('setCameraState', event.data.video);
	      }*/
	    }
	  }, {
	    key: "onCallViewToggleSpeakerButtonClick",
	    value: function onCallViewToggleSpeakerButtonClick(event) {
	      this.callView.muteSpeaker(!event.data.speakerMuted);
	      if (event.data.fromHotKey) {
	        BX.UI.Notification.Center.notify({
	          content: BX.message(this.callView.speakerMuted ? 'IM_M_CALL_MUTE_SPEAKERS_OFF' : 'IM_M_CALL_MUTE_SPEAKERS_ON'),
	          position: "top-right",
	          autoHideDelay: 3000,
	          closeButton: true
	        });
	      }
	    }
	  }, {
	    key: "onCallViewShareButtonClick",
	    value: function onCallViewShareButtonClick() {
	      var notifyWidth = 400;
	      if (im_lib_utils.Utils.device.isMobile() && document.body.clientWidth < 400) {
	        notifyWidth = document.body.clientWidth - 40;
	      }
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('BX_IM_VIDEOCONF_LINK_COPY_DONE'),
	        autoHideDelay: 4000,
	        width: notifyWidth
	      });
	      im_lib_clipboard.Clipboard.copy(this.getDialogData()["public"].link);
	    }
	  }, {
	    key: "onCallViewFullScreenButtonClick",
	    value: function onCallViewFullScreenButtonClick() {
	      this.toggleFullScreen();
	    }
	  }, {
	    key: "onFloatingScreenShareBackToCallClick",
	    value: function onFloatingScreenShareBackToCallClick() {
	      im_v2_lib_desktopApi.DesktopApi.activateWindow();
	      im_v2_lib_desktopApi.DesktopApi.changeTab("im");
	      if (this.floatingScreenShareWindow) {
	        this.floatingScreenShareWindow.hide();
	      }
	    }
	  }, {
	    key: "onFloatingScreenShareStopClick",
	    value: function onFloatingScreenShareStopClick() {
	      im_v2_lib_desktopApi.DesktopApi.activateWindow();
	      im_v2_lib_desktopApi.DesktopApi.changeTab("im");
	      this.onCallViewToggleScreenSharingButtonClick();
	    }
	  }, {
	    key: "onFloatingScreenShareChangeScreenClick",
	    value: function onFloatingScreenShareChangeScreenClick() {
	      if (this.currentCall) {
	        this.currentCall.startScreenSharing(true);
	      }
	    }
	  }, {
	    key: "updateWindowFocusState",
	    value: function updateWindowFocusState(isActive) {
	      if (isActive === this.isWindowFocus) {
	        return;
	      }
	      this.isWindowFocus = isActive;
	      if (this.callView) {
	        this.callView.setWindowFocusState(this.isWindowFocus);
	      }
	    }
	  }, {
	    key: "clearPictureInPictureDebounceForOpen",
	    value: function clearPictureInPictureDebounceForOpen() {
	      if (this.pictureInPictureDebounceForOpen) {
	        clearTimeout(this.pictureInPictureDebounceForOpen);
	        this.pictureInPictureDebounceForOpen = null;
	      }
	    }
	  }, {
	    key: "onInputFileOpenedStateUpdate",
	    value: function onInputFileOpenedStateUpdate(isActive) {
	      var _this25 = this;
	      if (isActive) {
	        this.clearPictureInPictureDebounceForOpen();
	        this.togglePictureInPictureCallWindow({
	          isForceClose: true
	        });
	        this.isFileChooserActive = true;
	      }
	      if (!isActive && !this.pictureInPictureDebounceForOpen) {
	        this.pictureInPictureDebounceForOpen = setTimeout(function () {
	          _this25.togglePictureInPictureCallWindow();
	          _this25.isFileChooserActive = false;
	          _this25.pictureInPictureDebounceForOpen = null;
	        }, 1000);
	      }
	    }
	  }, {
	    key: "onDocumentBodyClick",
	    value: function onDocumentBodyClick() {
	      var _event = event,
	        target = _event.target;
	      if (target.matches('input[type="file"]')) {
	        this.onInputFileOpenedStateUpdate(true);
	      }
	    }
	  }, {
	    key: "onWindowFocus",
	    value: function onWindowFocus() {
	      if (im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	        this.onWindowDesktopFocus();
	      }
	      if (im_v2_lib_desktopApi.DesktopApi.isDesktop() && this.isFileChooserActive) {
	        this.onInputFileOpenedStateUpdate(false);
	      }
	      this.updateWindowFocusState(true);
	    }
	  }, {
	    key: "onWindowBlur",
	    value: function onWindowBlur() {
	      if (im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	        this.onWindowDesktopBlur();
	      }
	      this.updateWindowFocusState(false);
	    }
	  }, {
	    key: "onWindowDesktopFocus",
	    value: function onWindowDesktopFocus() {
	      if (this.floatingScreenShareWindow) {
	        this.floatingScreenShareWindow.hide();
	      }
	    }
	  }, {
	    key: "onWindowDesktopBlur",
	    value: function onWindowDesktopBlur() {
	      if (this.floatingScreenShareWindow && this.currentCall && this.currentCall.isScreenSharingStarted()) {
	        this.floatingScreenShareWindow.show();
	      }
	    }
	  }, {
	    key: "isFullScreen",
	    value: function isFullScreen() {
	      if ("webkitFullscreenElement" in document) {
	        return !!document.webkitFullscreenElement;
	      } else if ("fullscreenElement" in document) {
	        return !!document.fullscreenElement;
	      }
	      return false;
	    }
	  }, {
	    key: "toggleFullScreen",
	    value: function toggleFullScreen() {
	      if (this.isFullScreen()) {
	        this.exitFullScreen();
	      } else {
	        this.enterFullScreen();
	      }
	    }
	  }, {
	    key: "enterFullScreen",
	    value: function enterFullScreen() {
	      if (!this.callView) {
	        return;
	      }
	      var element = this.callView.elements.root;
	      try {
	        var requestFullscreen = element.requestFullscreen || element.webkitRequestFullscreen || element.mozRequestFullScreen || element.msRequestFullscreen;
	        if (requestFullscreen) {
	          requestFullscreen.call(element)["catch"](function (error) {
	            console.error('Failed to enter fullscreen mode:', error);
	          });
	        } else {
	          console.warn('Fullscreen API is not supported in this browser');
	        }
	      } catch (e) {
	        console.error('Error attempting to enable fullscreen mode:', e);
	      }
	    }
	  }, {
	    key: "exitFullScreen",
	    value: function exitFullScreen() {
	      try {
	        var exitFullscreen = document.exitFullscreen || document.mozCancelFullScreen || document.webkitExitFullscreen || document.msExitFullscreen || document.cancelFullScreen;
	        if (exitFullscreen) {
	          exitFullscreen.call(document)["catch"](function (error) {
	            console.error('Failed to exit fullscreen mode:', error);
	          });
	        } else {
	          console.warn('Fullscreen API is not fully supported in this browser');
	        }
	      } catch (e) {
	        console.error('Error attempting to exit fullscreen mode:', e);
	      }
	    }
	  }, {
	    key: "onCallViewShowChatButtonClick",
	    value: function onCallViewShowChatButtonClick() {
	      var _this$currentCall4;
	      call_lib_analytics.Analytics.getInstance().onShowChat({
	        callId: (_this$currentCall4 = this.currentCall) === null || _this$currentCall4 === void 0 ? void 0 : _this$currentCall4.uuid,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	      });
	      this.toggleChat();
	    }
	  }, {
	    key: "onCallViewToggleUsersButtonClick",
	    value: function onCallViewToggleUsersButtonClick() {
	      this.toggleUserList();
	    }
	  }, {
	    key: "_onCallcontrolButtonClick",
	    value: function _onCallcontrolButtonClick(e) {
	      var _this26 = this;
	      if (!Call.Util.canControlChangeSettings()) {
	        return;
	      }
	      if (this.participantsPermissionPopup) {
	        this.participantsPermissionPopup.close();
	        return;
	      }
	      this.participantsPermissionPopup = new Call.ParticipantsPermissionPopup({
	        turnOffAllParticipansStream: function turnOffAllParticipansStream(options) {
	          var _options$data;
	          _this26._onCallViewTurnOffAllParticipansStreamButtonClick(options);
	          call_lib_analytics.Analytics.getInstance().onTurnOffAllParticipansStream({
	            callId: _this26._getCallIdentifier(_this26.currentCall),
	            callType: _this26.getCallType(),
	            typeOfStream: (_options$data = options.data) === null || _options$data === void 0 ? void 0 : _options$data.typeOfStream
	          });
	        },
	        onPermissionChanged: function onPermissionChanged(options) {
	          _this26.currentCall.changeSettings(options);
	          if (!options.settingEnabled)
	            // send only when it turned off
	            {
	              call_lib_analytics.Analytics.getInstance().onCallSettingsChanged({
	                callId: _this26._getCallIdentifier(_this26.currentCall),
	                callType: _this26.getCallType(),
	                typeOfSetting: options.typeOfSetting,
	                settingEnabled: options.settingEnabled
	              });
	            }
	        },
	        onClose: function onClose() {
	          _this26.participantsPermissionPopup = null;
	          _this26._afterCloseParticipantsPermissionPopup();
	        },
	        onOpen: function onOpen() {
	          _this26._afterOpenParticipantsPermissionPopup();
	          call_lib_analytics.Analytics.getInstance().onOpenCallSettings({
	            callId: _this26._getCallIdentifier(_this26.currentCall),
	            callType: _this26.getCallType()
	          });
	        }
	      });
	      if (this.participantsPermissionPopup) {
	        this.participantsPermissionPopup.toggle();
	      }
	    }
	  }, {
	    key: "onCallViewFeedbackButtonClick",
	    value: function onCallViewFeedbackButtonClick() {
	      var _this27 = this;
	      BX.loadExt('ui.feedback.form').then(function () {
	        BX.UI.Feedback.Form.open({
	          id: "call_feedback_".concat(_this27.currentCall.uuid, "-").concat(_this27.currentCall.instanceId, "-").concat(Math.random()),
	          forms: [{
	            zones: ['ru', 'by', 'kz'],
	            id: 406,
	            sec: '9lhjhn',
	            lang: 'ru'
	          }, {
	            zones: ['de'],
	            id: 754,
	            sec: '6upe49',
	            lang: 'de'
	          }, {
	            zones: ['es'],
	            id: 750,
	            sec: 'whk4la',
	            lang: 'es'
	          }, {
	            zones: ['com.br'],
	            id: 752,
	            sec: 'is01cs',
	            lang: 'com.br'
	          }, {
	            zones: ['en'],
	            id: 748,
	            sec: 'pds0h6',
	            lang: 'en'
	          }],
	          presets: {
	            sender_page: 'call',
	            call_type: _this27.currentCall.provider,
	            call_amount: _this27.currentCall.users.length + 1,
	            call_id: "id: ".concat(_this27.currentCall.uuid, ", instanceId: ").concat(_this27.currentCall.instanceId),
	            id_of_user: _this27.currentCall.userId,
	            from_domain: location.origin
	          }
	        });
	      });
	    }
	  }, {
	    key: "onCallUserClick",
	    value: function onCallUserClick(e) {
	      call_lib_analytics.Analytics.getInstance().onClickUser({
	        callId: this.currentCall.uuid,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	        layout: Object.keys(Call.View.Layout).find(function (key) {
	          return Call.View.Layout[key] === e.layout;
	        })
	      });
	    }
	  }, {
	    key: "onCallCopilotButtonClick",
	    value: function onCallCopilotButtonClick() {
	      this.onChangeStateCopilot();

	      // todo: code below supports only legacy provider;
	      // to support the new provider, logic from the common call controller must be added
	      /* if (!Util.isAIServiceEnabled())
	      {
	      	BX.SidePanel.Instance.open(CallAI.serviceEnabled, {
	      		cacheable: false
	      	});
	      	return;
	      }
	      	this.copilotPopup = new Call.CopilotPopup({
	      	isCopilotActive: this.currentCall.isCopilotActive,
	      	isCopilotFeaturesEnabled: this.currentCall.isCopilotFeaturesEnabled,
	      	updateCopilotState: () => {
	      		this.onChangeStateCopilot();
	      	},
	      	onClose: () => {
	      		this.copilotPopup = null;
	      	}
	      });
	      	if (this.copilotPopup)
	      {
	      	this.copilotPopup.toggle();
	      } */
	    }
	  }, {
	    key: "onUpdateCallCopilotState",
	    value: function onUpdateCallCopilotState(_ref) {
	      var isTrackRecordOn = _ref.isTrackRecordOn;
	      var updateCopilotActive = this.currentCall.scheme ? this.currentCall.scheme === Call.CallScheme.classic : !call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled;
	      if (updateCopilotActive) {
	        this.currentCall.isCopilotActive = isTrackRecordOn;
	      }
	      this.callView.updateCopilotState(this.currentCall.isCopilotActive);
	    }
	  }, {
	    key: "_onBlockCameraButton",
	    value: function _onBlockCameraButton() {
	      if (!this.callView) {
	        return;
	      }
	      this.callView.blockSwitchCamera();
	    }
	  }, {
	    key: "_onUnblockCameraButton",
	    value: function _onUnblockCameraButton() {
	      if (!this.callView) {
	        return;
	      }
	      this.callView.unblockSwitchCamera();
	    }
	  }, {
	    key: "_onBlockMicrophoneButton",
	    value: function _onBlockMicrophoneButton() {
	      if (!this.callView) {
	        return;
	      }
	      this.callView.blockSwitchMicrophone();
	    }
	  }, {
	    key: "_onUnblockMicrophoneButton",
	    value: function _onUnblockMicrophoneButton() {
	      if (!this.callView) {
	        return;
	      }
	      this.callView.unblockSwitchMicrophone();
	    }
	  }, {
	    key: "onCameraPublishing",
	    value: function onCameraPublishing(e) {
	      if (e.publishing) {
	        this.onBlockCameraButton();
	      } else {
	        this.onUnblockCameraButton();
	      }
	      if (this.callView) {
	        this.callView.updateButtons();
	      }
	    }
	  }, {
	    key: "onMicrophonePublishingd",
	    value: function onMicrophonePublishingd(e) {
	      if (!this.callView) {
	        return;
	      }
	      if (e.publishing) {
	        this.onBlockMicrophoneButton();
	      } else {
	        this.onUnblockMicrophoneButton();
	      }
	      if (this.callView) {
	        this.callView.updateButtons();
	      }
	    }
	  }, {
	    key: "onChangeStateCopilot",
	    value: function onChangeStateCopilot() {
	      var _this28 = this;
	      var action = !this.currentCall.isCopilotActive ? 'call.Track.start' : 'call.Track.stop';
	      BX.ajax.runAction(action, {
	        data: {
	          callId: this.currentCall.id
	        }
	      }).then(function () {
	        _this28.onUpdateCallCopilotState({
	          isTrackRecordOn: !_this28.currentCall.isCopilotActive
	        });
	      });
	    }
	  }, {
	    key: "onCallViewFloorRequestButtonClick",
	    value: function onCallViewFloorRequestButtonClick() {
	      var _this29 = this;
	      call_lib_analytics.Analytics.getInstance().onFloorRequest({
	        callId: this.currentCall.uuid,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	      });
	      var floorState = this.callView.getUserFloorRequestState(this.callEngine.getCurrentUserId());
	      var talkingState = this.callView.getUserTalking(this.callEngine.getCurrentUserId());
	      this.callView.setUserFloorRequestState(this.callEngine.getCurrentUserId(), !floorState);
	      if (this.currentCall) {
	        this.currentCall.requestFloor(!floorState);
	      }
	      clearTimeout(this.callViewFloorRequestTimeout);
	      if (talkingState && !floorState) {
	        this.callViewFloorRequestTimeout = setTimeout(function () {
	          if (_this29.currentCall) {
	            _this29.currentCall.requestFloor(false);
	          }
	        }, 1500);
	      }
	      if (this.riseYouHandToTalkPopup && !floorState) {
	        this.riseYouHandToTalkPopup.close();
	        this.riseYouHandToTalkPopup = null;
	      }
	    }
	  }, {
	    key: "_onCallViewTurnOffAllParticipansStreamButtonClick",
	    value: function _onCallViewTurnOffAllParticipansStreamButtonClick(options) {
	      if (this.currentCall) {
	        this.currentCall.turnOffAllParticipansStream(options);
	      }
	    }
	  }, {
	    key: "bindCallEvents",
	    value: function bindCallEvents() {
	      this.currentCall.addEventListener(Call.Event.onUserInvited, this.onCallUserInvitedHandler);
	      this.currentCall.addEventListener(Call.Event.onUserJoined, this.onCallUserJoinedHandler);
	      this.currentCall.addEventListener(Call.Event.onDestroy, this.onCallDestroyHandler);
	      this.currentCall.addEventListener(Call.Event.onUserStateChanged, this.onCallUserStateChangedHandler);
	      this.currentCall.addEventListener(Call.Event.onUserMicrophoneState, this.onCallUserMicrophoneStateHandler);
	      this.currentCall.addEventListener(Call.Event.onUserCameraState, this.onCallUserCameraStateHandler);
	      this.currentCall.addEventListener(Call.Event.onNeedResetMediaDevicesState, this.onNeedResetMediaDevicesStateHandler);
	      this.currentCall.addEventListener(Call.Event.onUserVideoPaused, this.onCallUserVideoPausedHandler);
	      this.currentCall.addEventListener(Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler);
	      this.currentCall.addEventListener(Call.Event.onRemoteMediaReceived, this.onCallRemoteMediaReceivedHandler);
	      this.currentCall.addEventListener(Call.Event.onRemoteMediaStopped, this.onCallRemoteMediaStoppedHandler);
	      this.currentCall.addEventListener(Call.Event.onUserVoiceStarted, this.onCallUserVoiceStartedHandler);
	      this.currentCall.addEventListener(Call.Event.onUserVoiceStopped, this.onCallUserVoiceStoppedHandler);
	      this.currentCall.addEventListener(Call.Event.onUserStatsReceived, this.onUserStatsReceivedHandler);
	      this.currentCall.addEventListener(Call.Event.onUserScreenState, this.onCallUserScreenStateHandler);
	      this.currentCall.addEventListener(Call.Event.onUserRecordState, this.onCallUserRecordStateHandler);
	      this.currentCall.addEventListener(Call.Event.onUserFloorRequest, this.onCallUserFloorRequestHandler);
	      this.currentCall.addEventListener(Call.Event.onMicrophoneLevel, this.onMicrophoneLevelHandler);
	      //this.currentCall.addEventListener(Call.Event.onDeviceListUpdated, this._onCallDeviceListUpdatedHandler);
	      this.currentCall.addEventListener(Call.Event.onCallFailure, this.onCallFailureHandler);
	      this.currentCall.addEventListener(Call.Event.onJoin, this._onCallJoinHandler);
	      this.currentCall.addEventListener(Call.Event.onLeave, this.onCallLeaveHandler);
	      this.currentCall.addEventListener(Call.Event.onReconnecting, this.onReconnectingHandler);
	      this.currentCall.addEventListener(Call.Event.onReconnected, this.onReconnectedHandler);
	      this.currentCall.addEventListener(Call.Event.onReconnectingFailed, this.onReconnectingFailedHandler);
	      this.currentCall.addEventListener(Call.Event.onUpdateLastUsedCameraId, this.onUpdateLastUsedCameraIdHandler);
	      this.currentCall.addEventListener(Call.Event.onConnectionQualityChanged, this.onCallConnectionQualityChangedHandler);
	      this.currentCall.addEventListener(Call.Event.onToggleRemoteParticipantVideo, this.onCallToggleRemoteParticipantVideoHandler);
	      this.currentCall.addEventListener(Call.Event.onGetUserMediaEnded, this._onGetUserMediaEndedHandler);
	      this.currentCall.addEventListener(Call.Event.onSwitchTrackRecordStatus, this._onSwitchTrackRecordStatusHandler);
	      this.currentCall.addEventListener(Call.Event.onCameraPublishing, this.onCameraPublishingHandler);
	      this.currentCall.addEventListener(Call.Event.onMicrophonePublishing, this.onMicrophonePublishingdHandler);
	      this.currentCall.addEventListener(Call.Event.onAllParticipantsAudioMuted, this._onAllParticipantsAudioMutedHandler);
	      this.currentCall.addEventListener(Call.Event.onAllParticipantsVideoMuted, this._onAllParticipantsVideoMutedHandler);
	      this.currentCall.addEventListener(Call.Event.onAllParticipantsScreenshareMuted, this._onAllParticipantsScreenshareHandler);
	      this.currentCall.addEventListener(Call.Event.onRoomSettingsChanged, this._onRoomSettingsChangedHandler);
	      this.currentCall.addEventListener(Call.Event.onUserPermissionsChanged, this._onUserPermissionsChangedHandler);
	      this.currentCall.addEventListener(Call.Event.onUserRoleChanged, this._onUserRoleChangedHandler);
	      this.currentCall.addEventListener(Call.Event.onYouMuteAllParticipants, this._onYouMuteAllParticipantsHandler);
	      this.currentCall.addEventListener(Call.Event.onParticipantMuted, this._onParticipantMutedHandler);
	    }
	  }, {
	    key: "removeCallEvents",
	    value: function removeCallEvents() {
	      this.currentCall.removeEventListener(Call.Event.onUserInvited, this.onCallUserInvitedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserJoined, this.onCallUserJoinedHandler);
	      this.currentCall.removeEventListener(Call.Event.onDestroy, this.onCallDestroyHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserStateChanged, this.onCallUserStateChangedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserMicrophoneState, this.onCallUserMicrophoneStateHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserCameraState, this.onCallUserCameraStateHandler);
	      this.currentCall.removeEventListener(Call.Event.onNeedResetMediaDevicesState, this.onNeedResetMediaDevicesStateHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserVideoPaused, this.onCallUserVideoPausedHandler);
	      this.currentCall.removeEventListener(Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler);
	      this.currentCall.removeEventListener(Call.Event.onRemoteMediaReceived, this.onCallRemoteMediaReceivedHandler);
	      this.currentCall.removeEventListener(Call.Event.onRemoteMediaStopped, this.onCallRemoteMediaStoppedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserVoiceStarted, this.onCallUserVoiceStartedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserVoiceStopped, this.onCallUserVoiceStoppedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserStatsReceived, this.onUserStatsReceivedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserScreenState, this.onCallUserScreenStateHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserRecordState, this.onCallUserRecordStateHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserFloorRequest, this.onCallUserFloorRequestHandler);
	      this.currentCall.removeEventListener(Call.Event.onMicrophoneLevel, this.onMicrophoneLevelHandler);
	      this.currentCall.removeEventListener(Call.Event.onConnectionQualityChanged, this.onCallConnectionQualityChangedHandler);
	      this.currentCall.removeEventListener(Call.Event.onToggleRemoteParticipantVideo, this.onCallToggleRemoteParticipantVideoHandler);
	      //this.currentCall.removeEventListener(Call.Event.onDeviceListUpdated, this._onCallDeviceListUpdatedHandler);
	      this.currentCall.removeEventListener(Call.Event.onCallFailure, this.onCallFailureHandler);
	      this.currentCall.removeEventListener(Call.Event.onLeave, this.onCallLeaveHandler);
	      this.currentCall.removeEventListener(Call.Event.onReconnecting, this.onReconnectingHandler);
	      this.currentCall.removeEventListener(Call.Event.onReconnected, this.onReconnectedHandler);
	      this.currentCall.removeEventListener(Call.Event.onReconnectingFailed, this.onReconnectingFailedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUpdateLastUsedCameraId, this.onUpdateLastUsedCameraIdHandler);
	      this.currentCall.removeEventListener(Call.Event.onGetUserMediaEnded, this._onGetUserMediaEndedHandler);
	      this.currentCall.removeEventListener(Call.Event.onSwitchTrackRecordStatus, this._onSwitchTrackRecordStatusHandler);
	      this.currentCall.removeEventListener(Call.Event.onCameraPublishing, this.onCameraPublishingHandler);
	      this.currentCall.removeEventListener(Call.Event.onMicrophonePublishing, this.onMicrophonePublishingdHandler);
	      this.currentCall.removeEventListener(Call.Event.onAllParticipantsAudioMuted, this._onAllParticipantsAudioMutedHandler);
	      this.currentCall.removeEventListener(Call.Event.onAllParticipantsVideoMuted, this._onAllParticipantsVideoMutedHandler);
	      this.currentCall.removeEventListener(Call.Event.onAllParticipantsScreenshareMuted, this._onAllParticipantsScreenshareHandler);
	      this.currentCall.removeEventListener(Call.Event.onRoomSettingsChanged, this._onRoomSettingsChangedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserPermissionsChanged, this._onUserPermissionsChangedHandler);
	      this.currentCall.removeEventListener(Call.Event.onUserRoleChanged, this._onUserRoleChangedHandler);
	      this.currentCall.removeEventListener(Call.Event.onYouMuteAllParticipants, this._onYouMuteAllParticipantsHandler);
	      this.currentCall.removeEventListener(Call.Event.onParticipantMuted, this._onParticipantMutedHandler);
	    }
	  }, {
	    key: "removeAdditionalEvents",
	    value: function removeAdditionalEvents() {
	      var _this30 = this;
	      window.removeEventListener('focus', function () {
	        _this30.onWindowFocus();
	      });
	      window.removeEventListener('blur', function () {
	        _this30.onWindowBlur();
	      });
	      document.body.removeEventListener('click', function (evt) {
	        _this30.onDocumentBodyClick(evt);
	      });
	    }
	  }, {
	    key: "onCallUserInvited",
	    value: function onCallUserInvited(e) {
	      var _this31 = this;
	      this.callView.addUser(e.userId);
	      Call.Util.getUsers(this.currentCall.id, [e.userId]).then(function (userData) {
	        _this31.controller.getStore().dispatch('users/set', Object.values(userData));
	        _this31.controller.getStore().dispatch('conference/setUsers', {
	          users: Object.keys(userData)
	        });
	        _this31.callView.updateUserData(userData);
	      });
	    }
	  }, {
	    key: "onCallUserJoined",
	    value: function onCallUserJoined(e) {
	      if (this.callView) {
	        this.callView.updateUserData(e.userData);
	        this.callView.addUser(e.userId, Call.UserState.Connected);
	      }
	    }
	  }, {
	    key: "onCallUserStateChanged",
	    value: function onCallUserStateChanged(e) {
	      if (e.state !== Call.UserState.Connected && this.loopTimers[e.userId] === undefined) {
	        this.loopConnectionQuality(e.userId, 1);
	      }
	      if (e.state === Call.UserState.Connected) {
	        this.clearConnectionQualityTimer(e.userId);
	        this.callView.setUserConnectionQuality(e.userId, 5);
	      }
	      if (e.state === Call.UserState.Ready && e.previousState === Call.UserState.Connected && !Object.keys(this.currentCall).includes(e.userId)) {
	        e.state = Call.UserState.Idle;
	      }
	      if (e.state === Call.UserState.Idle && e.userId === this.callRecordInitiatorId) {
	        this.callRecordState = Call.View.RecordState.Stopped;
	        this.callView.setRecordState(this.callView.getDefaultRecordState());
	        this.callView.setButtonActive('record', false);
	      }
	      this.callView.setUserState(e.userId, e.state);
	      this.updateCallUser(e.userId, {
	        state: e.state
	      });
	      if (!this.isRecording()) {
	        this.callView.getConnectedUserCount(false) ? this.callView.unblockButtons(['record']) : this.callView.blockButtons(['record']);
	      }
	      /*if (e.direction)
	      {
	      	this.callView.setUserDirection(e.userId, e.direction);
	      }*/
	    }
	  }, {
	    key: "onCallUserMicrophoneState",
	    value: function onCallUserMicrophoneState(e) {
	      if (e.userId == this.currentCall.userId) {
	        Call.Hardware.isMicrophoneMuted = !e.microphoneState;
	      } else {
	        this.callView.setUserMicrophoneState(e.userId, e.microphoneState);
	        this.updateCallUser(e.userId, {
	          microphoneState: e.microphoneState
	        });
	      }
	    }
	  }, {
	    key: "onCallUserCameraState",
	    value: function onCallUserCameraState(e) {
	      this.callView.setUserCameraState(e.userId, e.cameraState);
	      this.updateCallUser(e.userId, {
	        cameraState: e.cameraState
	      });
	    }
	  }, {
	    key: "onNeedResetMediaDevicesState",
	    value: function onNeedResetMediaDevicesState(e) {
	      Call.Hardware.isMicrophoneMuted = true;
	      Call.Hardware.isCameraOn = false;
	    }
	  }, {
	    key: "onCallUserVideoPaused",
	    value: function onCallUserVideoPaused(e) {
	      this.callView.setUserVideoPaused(e.userId, e.videoPaused);
	    }
	  }, {
	    key: "onCallLocalMediaReceived",
	    value: function onCallLocalMediaReceived(e) {
	      console.log("Received local media stream " + e);
	      if (this.callView) {
	        var flipVideo = e.tag == "main" || e.mediaRenderer ? Call.Hardware.enableMirroring : false;
	        this.callView.setLocalStream(e);
	        this.callView.flipLocalVideo(flipVideo);
	        this.callView.setButtonActive("screen", this.currentCall.isScreenSharingStarted());
	        if (this.currentCall.isScreenSharingStarted()) {
	          this.screenShareStartTime = new Date();
	          call_lib_analytics.Analytics.getInstance().onScreenShareStarted({
	            callId: this.currentCall.uuid,
	            callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	          });
	          this.togglePictureInPictureCallWindow();
	          if (!im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	            this.showWebScreenSharePopup();
	          }
	          this.callView.updateButtons();
	        } else {
	          call_lib_analytics.Analytics.getInstance().onScreenShareStopped({
	            callId: this.currentCall.uuid,
	            callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	            status: call_lib_analytics.Analytics.AnalyticsStatus.success,
	            screenShareLength: Call.Util.getTimeText(this.screenShareStartTime)
	          });
	          this.screenShareStartTime = null;
	          this.togglePictureInPictureCallWindow();
	          if (this.floatingScreenShareWindow) {
	            this.floatingScreenShareWindow.close();
	          }
	          if (this.webScreenSharePopup) {
	            this.webScreenSharePopup.close();
	          }
	        }
	        if (!this.currentCall.callFromMobile && !this.isViewerMode()) {
	          this.checkAvailableCamera();
	          this.checkAvailableMicrophone();
	        }
	      }
	      if (this.currentCall && Call.Hardware.isCameraOn && e.tag === 'main' && e.stream.getVideoTracks().length === 0) {
	        Call.Hardware.isCameraOn = false;
	      }
	    }
	  }, {
	    key: "onCallRemoteMediaReceived",
	    value: function onCallRemoteMediaReceived(e) {
	      var getStreamType = function getStreamType(stream) {
	        var _stream$getVideoTrack, _stream$getAudioTrack;
	        if (stream !== null && stream !== void 0 && (_stream$getVideoTrack = stream.getVideoTracks()) !== null && _stream$getVideoTrack !== void 0 && _stream$getVideoTrack.length) {
	          return 'video';
	        }
	        if (stream !== null && stream !== void 0 && (_stream$getAudioTrack = stream.getAudioTracks()) !== null && _stream$getAudioTrack !== void 0 && _stream$getAudioTrack.length) {
	          return 'audio';
	        }
	        return null;
	      };
	      if (this.callView) {
	        if ('track' in e) {
	          this.callView.setUserMedia(e.userId, e.kind, e.track);
	        }
	        if ('mediaRenderer' in e && e.mediaRenderer.kind === 'audio' && getStreamType(e.mediaRenderer.stream) === 'audio') {
	          this.callView.setUserMedia(e.userId, 'audio', e.mediaRenderer.stream.getAudioTracks()[0]);
	        }
	        if ('mediaRenderer' in e && e.mediaRenderer.kind === 'sharing' && getStreamType(e.mediaRenderer.stream) === 'audio') {
	          this.callView.setUserMedia(e.userId, 'sharingAudio', e.mediaRenderer.stream.getAudioTracks()[0]);
	        }
	        if ('mediaRenderer' in e && (e.mediaRenderer.kind === 'video' || e.mediaRenderer.kind === 'sharing') && getStreamType(e.mediaRenderer.stream) === 'video') {
	          this.callView.setVideoRenderer(e.userId, e.mediaRenderer);
	        }
	      }
	    }
	  }, {
	    key: "onCallRemoteMediaStopped",
	    value: function onCallRemoteMediaStopped(e) {
	      if (this.callView) {
	        if ('mediaRenderer' in e) {
	          if (e.kind === 'video' || e.kind === 'sharing') {
	            e.mediaRenderer.stream = null;
	            this.callView.setVideoRenderer(e.userId, e.mediaRenderer);
	          }
	        } else {
	          this.callView.setUserMedia(e.userId, e.kind, null);
	        }
	      }
	    }
	  }, {
	    key: "isLegacyCall",
	    value: function isLegacyCall(provider) {
	      var scheme = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      if (scheme) {
	        return scheme === Call.CallScheme.classic;
	      }
	      var isLegacyPlainCall = provider === Call.Provider.Plain && !call_lib_settingsManager.CallSettingsManager.isJwtInPlainCallsEnabled();
	      var isLegacyBitrixCall = provider === Call.Provider.Bitrix && !call_lib_settingsManager.CallSettingsManager.jwtCallsEnabled;
	      return isLegacyPlainCall || isLegacyBitrixCall;
	    }
	  }, {
	    key: "_getCallIdentifier",
	    value: function _getCallIdentifier(call) {
	      if (!call) {
	        return null;
	      }
	      return this.isLegacyCall(call.provider, call.scheme) ? call.id : call.uuid;
	    }
	  }, {
	    key: "getCallType",
	    value: function getCallType() {
	      return call_lib_analytics.Analytics.AnalyticsType.videoconf;
	    }
	  }, {
	    key: "loopConnectionQuality",
	    value: function loopConnectionQuality(userId, quality) {
	      var _this32 = this;
	      var timeout = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 200;
	      if (this.callView) {
	        this.loopTimers[userId] = setTimeout(function () {
	          _this32.callView.setUserConnectionQuality(userId, quality);
	          var newQuality = quality >= 4 ? 1 : quality + 1;
	          _this32.loopConnectionQuality(userId, newQuality, timeout);
	        }, timeout);
	      }
	    }
	  }, {
	    key: "clearConnectionQualityTimer",
	    value: function clearConnectionQualityTimer(userId) {
	      if (this.loopTimers[userId] !== undefined) {
	        clearTimeout(this.loopTimers[userId]);
	        delete this.loopTimers[userId];
	      }
	    }
	  }, {
	    key: "onCallConnectionQualityChanged",
	    value: function onCallConnectionQualityChanged(e) {
	      this.clearConnectionQualityTimer(e.userId);
	      this.callView.setUserConnectionQuality(e.userId, e.score);
	    }
	  }, {
	    key: "onGetUserMediaEnded",
	    value: function onGetUserMediaEnded() {
	      this.updateMediaDevices();
	    }
	  }, {
	    key: "onCallToggleRemoteParticipantVideo",
	    value: function onCallToggleRemoteParticipantVideo(e) {
	      if (this.toogleParticipantsVideoBaloon) {
	        if (e.isVideoShown) {
	          this.toogleParticipantsVideoBaloon.close();
	        }
	        return;
	      }
	      if (!e.isVideoShown) {
	        this.toogleParticipantsVideoBaloon = BX.UI.Notification.Center.notify({
	          content: main_core.Text.encode(BX.message('IM_M_CALL_REMOTE_PARTICIPANTS_VIDEO_MUTED')),
	          autoHide: false,
	          position: "top-right",
	          closeButton: false
	        });
	      }
	    }
	  }, {
	    key: "onCallUserVoiceStarted",
	    value: function onCallUserVoiceStarted(e) {
	      if (e.local) {
	        if (this.currentCall.muted && this.allowMutePopup) {
	          this.showMicMutedNotification();
	        }
	        return;
	      }
	      this.callView.setUserTalking(e.userId, true);
	      if (e.userId == this.callView.localUser.id) {
	        this.callView.setUserFloorRequestState(e.userId, false);
	      }
	      this.updateCallUser(e.userId, {
	        talking: true,
	        floorRequestState: false
	      });
	    }
	  }, {
	    key: "onCallUserVoiceStopped",
	    value: function onCallUserVoiceStopped(e) {
	      this.callView.setUserTalking(e.userId, false);
	      this.updateCallUser(e.userId, {
	        talking: false
	      });
	    }
	  }, {
	    key: "_onBlockUnblockCamMicButtons",
	    value: function _onBlockUnblockCamMicButtons() {
	      if (Call.Util.havePermissionToBroadcast('cam')) {
	        this._onUnblockCameraButton();
	      } else {
	        this._onBlockCameraButton();
	      }
	      if (Call.Util.havePermissionToBroadcast('mic')) {
	        this._onUnblockMicrophoneButton();
	      } else {
	        this._onBlockMicrophoneButton();
	      }
	    }
	  }, {
	    key: "_onAllParticipantsAudioMuted",
	    value: function _onAllParticipantsAudioMuted(e) {
	      var userModel = this.callView.userRegistry.get(e.userId);
	      var content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-mic-muted"></div>' + main_core.Text.encode(Call.Util.getCustomMessage("CALL_USER_TURNED_OFF_MIC_FOR_ALL_MSGVER_1", {
	        gender: userModel.data.gender ? userModel.data.gender.toUpperCase() : 'M',
	        name: userModel.data.name
	      }));
	      if (content && (!e.reason || e.reason !== 'settings')) {
	        this.createCallControlNotify({
	          content: content,
	          isAllow: false
	        });
	      }
	      if (Call.Util.isRegularUser(Call.Util.getCurrentUserRole())) {
	        this._onCallViewToggleMuteHandler({
	          muted: true,
	          calledProgrammatically: true
	        });
	      }
	    }
	  }, {
	    key: "_onAllParticipantsVideoMuted",
	    value: function _onAllParticipantsVideoMuted(e) {
	      var userModel = this.callView.userRegistry.get(e.userId);
	      var content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-cam-muted"></div>' + main_core.Text.encode(Call.Util.getCustomMessage("CALL_USER_TURNED_OFF_CAM_FOR_ALL_MSGVER_1", {
	        gender: userModel.data.gender ? userModel.data.gender.toUpperCase() : 'M',
	        name: userModel.data.name
	      }));
	      if (content && (!e.reason || e.reason !== 'settings')) {
	        this.createCallControlNotify({
	          content: content,
	          isAllow: false
	        });
	      }
	      if (Call.Util.isRegularUser(Call.Util.getCurrentUserRole())) {
	        this._onCallViewToggleVideoButtonClickHandler({
	          video: false,
	          calledProgrammatically: true
	        });
	      }
	    }
	  }, {
	    key: "_onAllParticipantsScreenshareMuted",
	    value: function _onAllParticipantsScreenshareMuted(e) {
	      var userModel = this.callView.userRegistry.get(e.userId);
	      var content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-cam-muted"></div>' + main_core.Text.encode(Call.Util.getCustomMessage("CALL_USER_TURNED_OFF_SCREENSHARE_FOR_ALL_MSGVER_1", {
	        gender: userModel.data.gender ? userModel.data.gender.toUpperCase() : 'M',
	        name: userModel.data.name
	      }));
	      if (content && (!e.reason || e.reason !== 'settings')) {
	        this.createCallControlNotify({
	          content: content,
	          isAllow: false
	        });
	      }
	      if (this.currentCall.isScreenSharingStarted() && Call.Util.isRegularUser(Call.Util.getCurrentUserRole())) {
	        this.onCallViewToggleScreenSharingButtonClick();
	      }
	    }
	  }, {
	    key: "_onParticipantMuted",
	    value: function _onParticipantMuted(e) {
	      var _e$data;
	      if ((_e$data = e.data) !== null && _e$data !== void 0 && _e$data.track.muted)
	        // tbh always should be in "true"..
	        {
	          var contentIcon = 'mic';
	          var contentPhrase = '';
	          var initiatorUserModel = this.callView.userRegistry.get(e.data.fromUserId);
	          var targetUserModel = this.callView.userRegistry.get(e.data.toUserId);
	          var initiatorGender = initiatorUserModel.data.gender ? initiatorUserModel.data.gender.toUpperCase() : 'M';
	          if (e.data.toUserId == this.callEngine.getCurrentUserId()) {
	            if (e.data.track.type === 0) {
	              contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_YOUR_MIC_MSGVER_1' + '_' + initiatorGender;
	              this._onCallViewToggleMuteHandler({
	                muted: true,
	                calledProgrammatically: true
	              });
	            } else if (e.data.track.type === 1) {
	              contentIcon = 'cam';
	              contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_YOUR_CAM_MSGVER_1' + '_' + initiatorGender;
	              this._onCallViewToggleVideoButtonClickHandler({
	                video: false,
	                calledProgrammatically: true
	              });
	            } else if (e.data.track.type === 2) {
	              contentIcon = 'screenshare';
	              contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_YOUR_SCREENSHARE' + '_' + initiatorGender;
	              if (this.currentCall.isScreenSharingStarted()) {
	                this.onCallViewToggleScreenSharingButtonClick();
	              }
	            }
	          } else {
	            if (e.data.fromUserId == this.callEngine.getCurrentUserId()) {
	              if (e.data.track.type === 0) {
	                contentPhrase = 'CALL_CONTROL_YOU_TURNED_OFF_USER_MIC';
	              } else if (e.data.track.type === 1) {
	                contentIcon = 'cam';
	                contentPhrase = 'CALL_CONTROL_YOU_TURNED_OFF_USER_CAM';
	              } else if (e.data.track.type === 2) {
	                contentIcon = 'screenshare';
	                contentPhrase = 'CALL_CONTROL_YOU_TURNED_OFF_USER_SCREENSHARE';
	              }
	            } else {
	              if (e.data.track.type === 0) {
	                contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_USER_MIC_MSGVER_1' + '_' + initiatorGender;
	              } else if (e.data.track.type === 1) {
	                contentIcon = 'cam';
	                contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_USER_CAM_MSGVER_1' + '_' + initiatorGender;
	              } else if (e.data.track.type === 2) {
	                contentIcon = 'screenshare';
	                contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_USER_SCREENSHARE' + '_' + initiatorGender;
	              }
	            }
	          }
	          var content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-' + contentIcon + '-muted"></div>' + main_core.Text.encode(Call.Util.getCustomMessage(contentPhrase, {
	            gender: initiatorGender,
	            initiator_name: initiatorUserModel.data.name,
	            target_name: targetUserModel.data.name
	          }));
	          this.createCallControlNotify({
	            content: content,
	            isAllow: false
	          });
	        }
	    }
	  }, {
	    key: "_onYouMuteAllParticipants",
	    value: function _onYouMuteAllParticipants(e) {
	      var typesOfMute = {
	        0: 'mic',
	        1: 'cam',
	        2: 'screenshare'
	      };
	      var typesOfMuteMessage = {
	        0: 'CALL_YOU_TURNED_OFF_MIC_FOR_ALL_MSGVER_1',
	        1: 'CALL_YOU_TURNED_OFF_CAM_FOR_ALL_MSGVER_1',
	        2: 'CALL_YOU_TURNED_OFF_SCREENSHARE_FOR_ALL_MSGVER_1'
	      };
	      var content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-' + typesOfMute[e.data.track.type] + '-muted"></div>' + BX.message[typesOfMuteMessage[e.data.track.type]];
	      this.createCallControlNotify({
	        content: content,
	        isAllow: false
	      });
	    }
	  }, {
	    key: "_onUserPermissionsChanged",
	    value: function _onUserPermissionsChanged(e) {
	      var initiatorUserModel = this.callView.userRegistry.get(e.data.fromUserId);
	      var initiatorGender = initiatorUserModel.data.gender ? initiatorUserModel.data.gender.toUpperCase() : 'M';
	      var contentPhrase = 'CALL_ADMIN_ALLOWED_TURN_ON_ALL_FOR_YOU_BY_HANDRAISE_' + initiatorGender;
	      if (!e.data.allow) {
	        contentPhrase = 'CALL_ADMIN_NOT_ALLOWED_TURN_ON_ALL_FOR_YOU_BY_HANDRAISE_' + initiatorGender;
	        var floorState = this.callView.getUserFloorRequestState(this.callEngine.getCurrentUserId());
	        if (floorState) {
	          this.onCallViewFloorRequestButtonClick();
	        }
	      }
	      var content = main_core.Text.encode(Call.Util.getCustomMessage(contentPhrase, {
	        gender: initiatorGender,
	        initiator_name: initiatorUserModel.data.name
	      }));
	      if (content) {
	        this.createCallControlNotify({
	          content: content,
	          isAllow: e.data.allow
	        });
	      }
	      if (this.callView) {
	        this.callView.setUserPermissionToSpeakState(e.data.toUserId, e.data.allow);
	      }
	      this.callView.updateButtons();
	      this._onBlockUnblockCamMicButtons();
	    }
	  }, {
	    key: "_onUserRoleChanged",
	    value: function _onUserRoleChanged(e) {
	      var _this33 = this;
	      if (e.data.toUserId == this.callEngine.getCurrentUserId()) {
	        var content = BX.message('CALL_YOU_HAVE_BEEN_APPOINTED_AS_ADMIN');
	        if (content) {
	          this.promotedToAdminTimeout = setTimeout(function () {
	            return _this33.createCallControlNotify({
	              content: content,
	              isAllow: true
	            });
	          }, this.promotedToAdminTimeoutValue);
	        }
	        if (this.riseYouHandToTalkPopup) {
	          this.riseYouHandToTalkPopup.close();
	          this.riseYouHandToTalkPopup = null;
	        }
	        if (this.callView) {
	          this.callView.updateButtons();
	          this.callView.updateFloorRequestNotification();
	        }
	        this._onBlockUnblockCamMicButtons();
	      }
	    }
	  }, {
	    key: "_onRoomSettingsChanged",
	    value: function _onRoomSettingsChanged(e) {
	      var typesOfMute = {
	        'audio': 'mic',
	        'video': 'cam',
	        'screen_share': 'screenshare'
	      };
	      var content = '';
	      var isAllow = false;
	      var initiatorUserModel = this.callView.userRegistry.get(e.data.fromUserId);
	      var initiatorGender = initiatorUserModel.data.gender ? initiatorUserModel.data.gender.toUpperCase() : 'M';
	      if (e.data.eft === true) {
	        if (e.data.fromUserId == this.currentCall.userId) {
	          var typesOfMuteMessage = {
	            'audio': 'CALL_YOU_PROHIBITED_MIC_FOR_ALL_BY_SETTINGS',
	            'video': 'CALL_YOU_PROHIBITED_CAM_FOR_ALL_BY_SETTINGS',
	            'screen_share': 'CALL_YOU_PROHIBITED_SCREENSHARE_FOR_ALL_BY_SETTINGS'
	          };
	          content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-' + typesOfMute[e.data.act] + '-muted"></div>' + BX.message[typesOfMuteMessage[e.data.act]];
	        } else {
	          var _typesOfMuteMessage = {
	            'audio': 'CALL_ADMIN_PROHIBITED_MIC_FOR_ALL_BY_SETTINGS',
	            'video': 'CALL_ADMIN_PROHIBITED_CAM_FOR_ALL_BY_SETTINGS',
	            'screen_share': 'CALL_ADMIN_PROHIBITED_SCREENSHARE_FOR_ALL_BY_SETTINGS'
	          };
	          var contentPhrase = _typesOfMuteMessage[e.data.act] + '_' + initiatorGender;
	          content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-' + typesOfMute[e.data.act] + '-muted"></div>' + main_core.Text.encode(Call.Util.getCustomMessage(contentPhrase, {
	            gender: initiatorGender,
	            initiator_name: initiatorUserModel.data.name
	          }));
	        }
	      } else {
	        isAllow = true;
	        if (e.data.fromUserId == this.currentCall.userId) {
	          if (this.riseYouHandToTalkPopup) {
	            this.riseYouHandToTalkPopup.close();
	            this.riseYouHandToTalkPopup = null;
	          }
	          var _typesOfMuteMessage2 = {
	            'audio': 'CALL_YOU_ALLOWED_MIC_FOR_ALL_BY_SETTINGS',
	            'video': 'CALL_YOU_ALLOWED_CAM_FOR_ALL_BY_SETTINGS',
	            'screen_share': 'CALL_YOU_ALLOWED_SCREENSHARE_FOR_ALL_BY_SETTINGS'
	          };
	          content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-' + typesOfMute[e.data.act] + '-unmuted"></div>' + BX.message[_typesOfMuteMessage2[e.data.act]];
	        } else {
	          var _typesOfMuteMessage3 = {
	            'audio': 'CALL_ADMIN_ALLOWED_MIC_FOR_ALL_BY_SETTINGS',
	            'video': 'CALL_ADMIN_ALLOWED_CAM_FOR_ALL_BY_SETTINGS',
	            'screen_share': 'CALL_ADMIN_ALLOWED_SCREENSHARE_FOR_ALL_BY_SETTINGS'
	          };
	          var _contentPhrase = _typesOfMuteMessage3[e.data.act] + '_' + initiatorGender;
	          content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-' + typesOfMute[e.data.act] + '-unmuted"></div>' + main_core.Text.encode(Call.Util.getCustomMessage(_contentPhrase, {
	            gender: initiatorGender,
	            initiator_name: initiatorUserModel.data.name
	          }));
	          if (this.riseYouHandToTalkPopup && e.data.act === 'audio') {
	            this.riseYouHandToTalkPopup.close();
	            this.riseYouHandToTalkPopup = null;
	          }
	        }
	      }
	      if (this.callView && !isAllow) {
	        this.callView.setAllUserPermissionToSpeakState(false);
	      }
	      if (content) {
	        this.createCallControlNotify({
	          content: content,
	          isAllow: isAllow
	        });
	      }
	      if (this.participantsPermissionPopup) {
	        this.participantsPermissionPopup.updateStatePermissions();
	      }
	      if (e.data.eft === true && e.data.act === 'audio' && !Call.Util.havePermissionToBroadcast('mic')) {
	        this.lastCalledChangeSettingsUserName = initiatorUserModel.data.name;
	        this.showRiseYouHandToTalkNotification({
	          initiatorName: this.lastCalledChangeSettingsUserName
	        });
	      }
	      this.callView.updateButtons();
	      this._onBlockUnblockCamMicButtons();
	    }
	  }, {
	    key: "showRiseYouHandToTalkNotification",
	    value: function showRiseYouHandToTalkNotification(params) {
	      var _this34 = this;
	      if (!this.callView) {
	        return;
	      }
	      if (this.riseYouHandToTalkPopup) {
	        return;
	      }
	      if (this.mutePopup) {
	        this.mutePopup.close();
	        this.mutePopup = null;
	      }
	      this.riseYouHandToTalkPopup = new Call.Hint({
	        callFolded: false,
	        bindElement: this.callView.buttons.microphone.elements.icon,
	        targetContainer: this.callView.container,
	        icon: 'raise-hand',
	        showAngle: true,
	        initiatorName: params.initiatorName,
	        customClassName: 'bx-call-view-popup-call-hint-rise-hand-to-talk',
	        autoCloseDelay: 30 * 60 * 1000,
	        // show it 30 minutes
	        customRender: function customRender() {
	          var handRaiseContentElement = main_core.Dom.create("div", {
	            props: {
	              className: "bx-call-view-popup-call-hint-rise-block"
	            },
	            children: [main_core.Loc.getMessage('CALL_ADMIN_PROHIBITED_TURN_ON_PARTICIPANTS_MICROPHONES_HINT', {
	              '#INITIATOR_NAME#': this.initiatorName,
	              '[hint-label]': "<div class=\"bx-call-view-popup-call-hint-text\">",
	              '[/hint-label]': '</div>',
	              '#RISE_HAND_ICON#': "<div class=\"bx-call-view-popup-call-hint-hand-raise-icon\"></div>",
	              '[label-or]': "<div class=\"bx-call-view-popup-call-hint-hand-raise-or-label\">",
	              '[/label-or]': '</div>',
	              '#REQUEST_NOW_BUTTON#': "<div class = \"bx-call-view-popup-call-hint-button-placeholder\"></div>"
	            })]
	          });
	          var buttonPlaceholder = handRaiseContentElement.getElementsByClassName('bx-call-view-popup-call-hint-button-placeholder')[0];
	          buttonPlaceholder.replaceWith(this.createAskSpeakButton().render());
	          return handRaiseContentElement;
	        },
	        buttons: [],
	        onClose: function onClose() {
	          _this34.riseYouHandToTalkPopup.close();
	          _this34.riseYouHandToTalkPopup = null;
	        },
	        onAskSpeakButtonClicked: function onAskSpeakButtonClicked() {
	          _this34.onCallViewFloorRequestButtonClick();
	          if (_this34.riseYouHandToTalkPopup) {
	            _this34.riseYouHandToTalkPopup.close();
	            _this34.riseYouHandToTalkPopup = null;
	          }
	        }
	      });
	      this.riseYouHandToTalkPopup.show();
	    }
	  }, {
	    key: "_afterOpenParticipantsPermissionPopup",
	    value: function _afterOpenParticipantsPermissionPopup() {
	      if (this.participantsPermissionPopup) {
	        var balloons = BX.UI.Notification.Center.balloons;
	        for (var baloonId in balloons) {
	          var _balloons$baloonId$co;
	          (_balloons$baloonId$co = balloons[baloonId].container) === null || _balloons$baloonId$co === void 0 ? void 0 : _balloons$baloonId$co.classList.add(BALLOON_OFFSET_CLASS_NAME);
	          balloons[baloonId].offsetClassNameSetted = true;
	        }
	      }
	    }
	  }, {
	    key: "_afterCloseParticipantsPermissionPopup",
	    value: function _afterCloseParticipantsPermissionPopup() {
	      var balloons = BX.UI.Notification.Center.balloons;
	      for (var baloonId in balloons) {
	        var _balloons$baloonId$co2;
	        (_balloons$baloonId$co2 = balloons[baloonId].container) === null || _balloons$baloonId$co2 === void 0 ? void 0 : _balloons$baloonId$co2.classList.remove(BALLOON_OFFSET_CLASS_NAME);
	        balloons[baloonId].offsetClassNameSetted = false;
	      }
	    }
	  }, {
	    key: "createCallControlNotify",
	    value: function createCallControlNotify(_p) {
	      if (!this.callView) {
	        return;
	      }
	      var balloonClassName = 'ui-notification-balloon-content bx-call-control-notification ';
	      if (_p.isAllow === false) {
	        balloonClassName += 'bx-call-control-notification-disallow ';
	      } else {
	        balloonClassName += 'bx-call-control-notification-allow ';
	      }
	      BX.UI.Notification.Center.notify({
	        content: _p.content,
	        position: "top-right",
	        autoHideDelay: 8000,
	        category: _p.category || '',
	        closeButton: true,
	        render: function render() {
	          var actions = this.getActions().map(function (action) {
	            return action.getContainer();
	          });
	          return BX.create("div", {
	            props: {
	              className: balloonClassName
	            },
	            children: [BX.create("div", {
	              props: {
	                className: "ui-notification-balloon-message"
	              },
	              html: this.getContent()
	            }), BX.create("div", {
	              props: {
	                className: "ui-notification-balloon-actions"
	              },
	              children: actions
	            }), this.isCloseButtonVisible() ? this.getCloseButton() : null]
	          });
	        }
	      });
	      this._afterOpenParticipantsPermissionPopup();
	    }
	  }, {
	    key: "onUserStatsReceived",
	    value: function onUserStatsReceived(e) {
	      if (this.callView) {
	        this.callView.setUserStats(e.userId, e.report);
	      }
	    }
	  }, {
	    key: "onCallUserScreenState",
	    value: function onCallUserScreenState(e) {
	      if (this.callView) {
	        this.callView.setUserScreenState(e.userId, e.screenState);
	      }
	      this.updateCallUser(e.userId, {
	        screenState: e.screenState
	      });
	    }
	  }, {
	    key: "onCallUserRecordState",
	    value: function onCallUserRecordState(event) {
	      this.callRecordState = event.recordState.state;
	      this.callView.setRecordState(event.recordState);
	      this.callRecordInitiatorId = event.userId;
	      if (!this.canRecord() || event.userId != this.controller.getUserId()) {
	        return true;
	      }
	      if (event.recordState.state === Call.View.RecordState.Stopped) {
	        this.callRecordInitiatorId = null;
	      } else {
	        this.callRecordInfo = event.recordState;
	      }
	      if (event.recordState.state === Call.View.RecordState.Started && event.recordState.userId == this.controller.getUserId()) {
	        var windowId = window.bxdWindowId || window.document.title;
	        var fileName = BX.message('IM_CALL_RECORD_NAME');
	        var dialogId = this.currentCall.associatedEntity.id;
	        var dialogName = this.currentCall.associatedEntity.name;
	        var callId = this.currentCall.uuid;
	        var callDate = BX.Main.Date.format(this.params.formatRecordDate || 'd.m.Y');
	        if (fileName) {
	          fileName = fileName.replace('#CHAT_TITLE#', dialogName).replace('#CALL_ID#', callId).replace('#DATE#', callDate);
	        } else {
	          fileName = "call_record_".concat(callId);
	        }
	        this.callEngine.getRestClient().callMethod("call.Call.onStartRecord", {
	          callUuid: this.currentCall.uuid
	        });
	        call_lib_analytics.Analytics.getInstance().onRecordStart({
	          callId: this.currentCall.uuid,
	          callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	        });
	        BXDesktopSystem.CallRecordStart({
	          windowId: windowId,
	          fileName: fileName,
	          callId: callId,
	          // now not used
	          callDate: callDate,
	          dialogId: dialogId,
	          dialogName: dialogName,
	          muted: Call.Hardware.isMicrophoneMuted,
	          cropTop: 72,
	          cropBottom: 90,
	          shareMethod: 'im.disk.record.share',
	          callType: call_lib_analytics.Analytics.AnalyticsType.videoconf
	        });
	      } else if (event.recordState.state === Call.View.RecordState.Stopped) {
	        call_lib_analytics.Analytics.getInstance().onRecordStop({
	          callId: this.currentCall.uuid,
	          callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	          subSection: call_lib_analytics.Analytics.AnalyticsSubSection.window,
	          element: call_lib_analytics.Analytics.AnalyticsElement.recordButton,
	          recordTime: Call.Util.getRecordTimeText(this.callRecordInfo)
	        });
	        this.callRecordInfo = null;
	        BXDesktopSystem.CallRecordStop();
	      }
	      return true;
	    }
	  }, {
	    key: "onCallUserFloorRequest",
	    value: function onCallUserFloorRequest(e) {
	      this.callView.setUserFloorRequestState(e.userId, e.requestActive);
	      this.updateCallUser(e.userId, {
	        floorRequestState: e.requestActive
	      });
	    }
	  }, {
	    key: "onMicrophoneLevel",
	    value: function onMicrophoneLevel(e) {
	      this.callView.setMicrophoneLevel(e.level);
	    }
	  }, {
	    key: "onCallJoin",
	    value: function onCallJoin(e) {
	      if (!e.local) {
	        return;
	      }
	      if (!this.isViewerMode()) {
	        this.callView.unblockButtons(['floorRequest', 'screen']);
	        this.checkAvailableCamera();
	        this.checkAvailableMicrophone();
	      }
	      if (this.callView.getConnectedUserCount(false)) {
	        this.callView.unblockButtons(['record']);
	      }
	      this.callView.setUiState(Call.View.UiState.Connected);
	    }
	  }, {
	    key: "onCallFailure",
	    value: function onCallFailure(e) {
	      this.setConferenceHasErrorInCall(true);
	      var errorCode = e.code || e.name || e.error;
	      var errorMessage = '';
	      var isUnknownError = false;
	      if (e.name === 'VoxConnectionError' || e.name === 'AuthResult') {
	        Call.Util.reportConnectionResult(e.call.id, false);
	      }
	      switch (errorCode) {
	        case Call.StartCallErrorCode.ErrorUnexpectedAnswer:
	          errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_UNEXPECTED_ANSWER');
	          break;
	        case Call.StartCallErrorCode.BlankAnswerWithErrorCode:
	          errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_BLANK_ANSWER');
	          break;
	        case Call.StartCallErrorCode.BlankAnswer:
	          errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_BLANK_ANSWER');
	          break;
	        case Call.StartCallErrorCode.AccessDenied:
	          errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_ACCESS_DENIED');
	          break;
	        case Call.StartCallErrorCode.NoWebrtc:
	          errorMessage = main_core.Loc.getMessage(this.isHttps ? 'IM_CALL_NO_WEBRT' : 'IM_CALL_ERROR_HTTPS_REQUIRED');
	          break;
	        case Call.StartCallErrorCode.UnknownError:
	          isUnknownError = true;
	          errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_UNKNOWN');
	          break;
	        case Call.StartCallErrorCode.NetworkError:
	          errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_NETWORK');
	          break;
	        case Call.StartCallErrorCode.NotAllowedError:
	          errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_HARDWARE_ACCESS_DENIED');
	          break;
	        case Call.StartCallErrorCode.NotReadableError:
	          errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_HARDWARE');
	          break;
	        default:
	          if (errorCode === Call.StartCallErrorCode.AuthorizeError || e.name === 'AuthResult') {
	            errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_AUTHORIZATION');
	          } else if (errorCode == 403 && e.name === 'Failed') {
	            errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_HARDWARE_ACCESS_DENIED');
	          } else {
	            isUnknownError = true;
	            errorMessage = main_core.Loc.getMessage('IM_CALL_ERROR_UNKNOWN_WITH_CODE', {
	              '#ERROR_CODE#': errorCode
	            });
	          }
	      }
	      if (this.callView) {
	        if (isUnknownError) {
	          this.callView.showSelfTest();
	        } else if (errorCode === Call.DisconnectReason.SecurityKeyChanged) {
	          this.callView.showSecurityKeyError();
	        } else {
	          this.callView.showFatalError({
	            text: errorMessage
	          });
	        }
	      } else {
	        this.showNotification(errorMessage);
	      }
	      this.autoCloseCallView = false;
	      if (this.currentCall) {
	        this.removeVideoStrategy();
	        this.removeCallEvents();
	        if (this.currentCallIsNew) {
	          // todo: possibly delete
	          this.callEngine.getRestClient().callMethod('im.call.interrupt', {
	            callId: this.currentCall.id
	          });
	        }
	        this.currentCall.destroy();
	        this.currentCall = null;
	        this.currentCallIsNew = false;
	      }
	      if (this.promotedToAdminTimeout) {
	        clearTimeout(this.promotedToAdminTimeout);
	      }
	      Call.Hardware.isMicrophoneMuted = false;
	    }
	  }, {
	    key: "onCallLeave",
	    value: function onCallLeave(e) {
	      if (!e.local) {
	        return;
	      }
	      if (this.webScreenSharePopup) {
	        this.webScreenSharePopup.close();
	      }
	      this.togglePictureInPictureCallWindow({
	        isForceClose: true
	      });
	      if (!this.getActiveCallUsers().length) {
	        var _this$currentCall5, _this$currentCall6;
	        call_lib_analytics.Analytics.getInstance().onFinishCall({
	          callId: (_this$currentCall5 = this.currentCall) === null || _this$currentCall5 === void 0 ? void 0 : _this$currentCall5.uuid,
	          callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	          status: call_lib_analytics.Analytics.AnalyticsStatus.lastUserLeft,
	          chatId: (_this$currentCall6 = this.currentCall) === null || _this$currentCall6 === void 0 ? void 0 : _this$currentCall6.associatedEntity.id,
	          callUsersCount: this.getCallUsers(true).length,
	          callLength: Call.Util.getTimeText(this.currentCall.startDate)
	        });
	      }
	      this.endCall();
	    }
	  }, {
	    key: "onCallDestroy",
	    value: function onCallDestroy(e) {
	      this.currentCall = null;
	      if (this.promotedToAdminTimeout) {
	        clearTimeout(this.promotedToAdminTimeout);
	      }
	      if (this.floatingScreenShareWindow) {
	        this.floatingScreenShareWindow.close;
	      }
	      if (this.webScreenSharePopup) {
	        this.webScreenSharePopup.close();
	      }
	      if (this.riseYouHandToTalkPopup) {
	        this.riseYouHandToTalkPopup.close();
	        this.riseYouHandToTalkPopup = null;
	      }
	      this.restart();
	    }
	  }, {
	    key: "onCheckDevicesSave",
	    value: function onCheckDevicesSave(changedValues) {
	      if (changedValues['camera']) {
	        Call.Hardware.defaultCamera = changedValues['camera'];
	      }
	      if (changedValues['microphone']) {
	        Call.Hardware.defaultMicrophone = changedValues['microphone'];
	      }
	      if (changedValues['audioOutput']) {
	        Call.Hardware.defaultSpeaker = changedValues['audioOutput'];
	      }
	      if (changedValues['preferHDQuality']) {
	        Call.Hardware.preferHdQuality = changedValues['preferHDQuality'];
	      }
	      if (changedValues['enableMicAutoParameters']) {
	        Call.Hardware.enableMicAutoParameters = changedValues['enableMicAutoParameters'];
	      }
	    }
	  }, {
	    key: "setCameraState",
	    value: function setCameraState(state) {
	      Call.Hardware.isCameraOn = state;
	    }
	  }, {
	    key: "isChatShowed",
	    /* endregion 01. Call methods */
	    /* region 02. Component methods */
	    /* region 01. General actions */
	    value: function isChatShowed() {
	      return this.getConference().common.showChat;
	    }
	  }, {
	    key: "toggleChat",
	    value: function toggleChat() {
	      var rightPanelMode = this.getConference().common.rightPanelMode;
	      if (rightPanelMode === im_const.ConferenceRightPanelMode.hidden) {
	        this.controller.getStore().dispatch('conference/changeRightPanelMode', {
	          mode: im_const.ConferenceRightPanelMode.chat
	        });
	        this.callView.setButtonActive('chat', true);
	      } else if (rightPanelMode === im_const.ConferenceRightPanelMode.chat) {
	        this.controller.getStore().dispatch('conference/changeRightPanelMode', {
	          mode: im_const.ConferenceRightPanelMode.hidden
	        });
	        this.callView.setButtonActive('chat', false);
	      } else if (rightPanelMode === im_const.ConferenceRightPanelMode.users) {
	        this.controller.getStore().dispatch('conference/changeRightPanelMode', {
	          mode: im_const.ConferenceRightPanelMode.split
	        });
	        this.callView.setButtonActive('chat', true);
	      } else if (rightPanelMode === im_const.ConferenceRightPanelMode.split) {
	        this.controller.getStore().dispatch('conference/changeRightPanelMode', {
	          mode: im_const.ConferenceRightPanelMode.users
	        });
	        this.callView.setButtonActive('chat', false);
	      }
	    }
	  }, {
	    key: "toggleUserList",
	    value: function toggleUserList() {
	      var rightPanelMode = this.getConference().common.rightPanelMode;
	      if (rightPanelMode === im_const.ConferenceRightPanelMode.hidden) {
	        this.controller.getStore().dispatch('conference/changeRightPanelMode', {
	          mode: im_const.ConferenceRightPanelMode.users
	        });
	        this.callView.setButtonActive('users', true);
	      } else if (rightPanelMode === im_const.ConferenceRightPanelMode.users) {
	        this.controller.getStore().dispatch('conference/changeRightPanelMode', {
	          mode: im_const.ConferenceRightPanelMode.hidden
	        });
	        this.callView.setButtonActive('users', false);
	      } else if (rightPanelMode === im_const.ConferenceRightPanelMode.chat) {
	        this.controller.getStore().dispatch('conference/changeRightPanelMode', {
	          mode: im_const.ConferenceRightPanelMode.split
	        });
	        this.callView.setButtonActive('users', true);
	      } else if (rightPanelMode === im_const.ConferenceRightPanelMode.split) {
	        this.controller.getStore().dispatch('conference/changeRightPanelMode', {
	          mode: im_const.ConferenceRightPanelMode.chat
	        });
	        this.callView.setButtonActive('users', false);
	      }
	    }
	  }, {
	    key: "pinUser",
	    value: function pinUser(user) {
	      if (!this.callView) {
	        return false;
	      }
	      this.callView.pinUser(user.id);
	      this.callView.setLayout(Call.View.Layout.Centered);
	    }
	  }, {
	    key: "unpinUser",
	    value: function unpinUser() {
	      if (!this.callView) {
	        return false;
	      }
	      this.callView.unpinUser();
	    }
	  }, {
	    key: "changeBackground",
	    value: function changeBackground() {
	      if (!Call.Hardware) {
	        return false;
	      }
	      Call.BackgroundDialog.open();
	    }
	  }, {
	    key: "openChat",
	    value: function openChat(user) {
	      im_v2_lib_desktopApi.DesktopApi.emitToMainWindow('bxConferenceOpenChat', [user.id]);
	    }
	  }, {
	    key: "openProfile",
	    value: function openProfile(user) {
	      im_v2_lib_desktopApi.DesktopApi.emitToMainWindow('bxConferenceOpenProfile', [user.id]);
	    }
	  }, {
	    key: "setDialogInited",
	    value: function setDialogInited() {
	      this.dialogInited = true;
	      var dialogData = this.getDialogData();
	      document.title = dialogData.name;
	    }
	  }, {
	    key: "changeVideoconfUrl",
	    value: function changeVideoconfUrl(newUrl) {
	      window.history.pushState("", "", newUrl);
	    }
	  }, {
	    key: "sendNewMessageNotify",
	    value: function sendNewMessageNotify(params) {
	      if (!this.checkIfMessageNotifyIsNeeded(params)) {
	        return false;
	      }
	      var text = im_lib_utils.Utils.text.purify(params.message.text, params.message.params, params.files);
	      var avatar = '';
	      var userName = '';

	      // avatar and username only for non-system messages
	      if (params.message.senderId > 0 && params.message.system !== 'Y') {
	        var messageAuthor = this.controller.getStore().getters['users/get'](params.message.senderId, true);
	        userName = messageAuthor.name;
	        avatar = messageAuthor.avatar;
	      }
	      ui_notificationManager.Notifier.notify({
	        id: "im-videconf-".concat(params.message.id),
	        title: userName,
	        icon: avatar,
	        text: text
	      });
	      return true;
	    }
	  }, {
	    key: "checkIfMessageNotifyIsNeeded",
	    value: function checkIfMessageNotifyIsNeeded(params) {
	      if (!Call.Util.isConferenceChatEnabled()) {
	        return false;
	      }
	      var rightPanelMode = this.getConference().common.rightPanelMode;
	      return !im_lib_utils.Utils.device.isMobile() && params.chatId === this.getChatId() && (rightPanelMode !== im_const.ConferenceRightPanelMode.chat || rightPanelMode !== im_const.ConferenceRightPanelMode.split) && params.message.senderId !== this.controller.getUserId() && !this.getConference().common.error;
	    }
	  }, {
	    key: "onInputFocus",
	    value: function onInputFocus(e) {
	      this.callView.setHotKeyTemporaryBlock(true);
	    }
	  }, {
	    key: "onInputBlur",
	    value: function onInputBlur(e) {
	      this.callView.setHotKeyTemporaryBlock(false);
	    }
	  }, {
	    key: "closeReconnectionBaloon",
	    value: function closeReconnectionBaloon() {
	      if (this.reconnectionBaloon) {
	        this.reconnectionBaloon.close();
	        this.reconnectionBaloon = null;
	      }
	    }
	  }, {
	    key: "setReconnectingCameraId",
	    value: function setReconnectingCameraId(id) {
	      this.reconnectingCameraId = id;
	      if (id) {
	        this.updateCameraSettingsInCurrentCallAfterReconnecting(id);
	      }
	    }
	  }, {
	    key: "updateCameraSettingsInCurrentCallAfterReconnecting",
	    value: function updateCameraSettingsInCurrentCallAfterReconnecting(cameraId) {
	      if (this.currentCall.cameraId === cameraId) {
	        return;
	      }
	      var devicesList = Call.Hardware.getCameraList();
	      if (!devicesList.find(function (device) {
	        return device.deviceId === cameraId;
	      })) {
	        return;
	      }
	      this.currentCall.setCameraId(cameraId);
	      this.setReconnectingCameraId(null);
	    }
	  }, {
	    key: "onUpdateLastUsedCameraId",
	    value: function onUpdateLastUsedCameraId() {
	      var cameraId = this.currentCall.cameraId;
	      if (cameraId) {
	        this.lastUsedCameraId = cameraId;
	      }
	    }
	  }, {
	    key: "onReconnecting",
	    value: function onReconnecting(e) {
	      if (!(this.currentCall.provider === Call.Provider.Bitrix || this.currentCall.provider === Call.Provider.Plain)) {
	        // todo: restore after fixing balloon resurrection issue
	        // related to multiple simultaneous calls to the balloon manager
	        // now it's enabled for Bitrix24 calls as a temp solution
	        return false;
	      }
	      call_lib_analytics.Analytics.getInstance().onReconnect({
	        callId: this.currentCall.uuid,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	        reconnectionReason: e.reconnectionReason,
	        reconnectionReasonInfo: e.reconnectionReasonInfo,
	        reconnectionEventCount: e.reconnectionEventCount
	      });

	      // noinspection UnreachableCodeJS

	      if (this.reconnectionBaloon) {
	        return;
	      }
	      this.reconnectionBaloon = BX.UI.Notification.Center.notify({
	        content: main_core.Text.encode(BX.message('IM_CALL_RECONNECTING')),
	        autoHide: false,
	        position: "top-right",
	        closeButton: false
	      });
	    }
	  }, {
	    key: "onReconnected",
	    value: function onReconnected() {
	      this.setReconnectingCameraId(this.lastUsedCameraId);
	      if (!(this.currentCall.provider === Call.Provider.Bitrix || this.currentCall.provider === Call.Provider.Plain)) {
	        // todo: restore after fixing balloon resurrection issue
	        // related to multiple simultaneous calls to the balloon manager
	        // now it's enabled for Bitrix24 calls as a temp solution
	        return false;
	      }

	      // noinspection UnreachableCodeJS
	      this.closeReconnectionBaloon();
	    }
	  }, {
	    key: "onReconnectingFailed",
	    value: function onReconnectingFailed(e) {
	      var _this$currentCall7;
	      call_lib_analytics.Analytics.getInstance().onReconnectError({
	        callId: (_this$currentCall7 = this.currentCall) === null || _this$currentCall7 === void 0 ? void 0 : _this$currentCall7.id,
	        callType: call_lib_analytics.Analytics.AnalyticsType.videoconf,
	        errorCode: e === null || e === void 0 ? void 0 : e.code
	      });
	    }
	  }, {
	    key: "setUserWasRenamed",
	    value: function setUserWasRenamed() {
	      if (this.callView) {
	        this.callView.localUser.userModel.wasRenamed = true;
	      }
	    }
	    /* endregion 01. General actions */
	    /* region 02. Store actions */
	  }, {
	    key: "setError",
	    value: function setError(errorCode) {
	      var currentError = this.getConference().common.error;
	      // if user kicked from call - dont show him end of call form
	      if (currentError && currentError === im_const.ConferenceErrorCode.kickedFromCall) {
	        return;
	      }
	      this.controller.getStore().commit('conference/setError', {
	        errorCode: errorCode
	      });
	    }
	  }, {
	    key: "toggleSmiles",
	    value: function toggleSmiles() {
	      this.controller.getStore().commit('conference/toggleSmiles');
	    }
	  }, {
	    key: "setJoinType",
	    value: function setJoinType(joinWithVideo) {
	      this.controller.getStore().commit('conference/setJoinType', {
	        joinWithVideo: joinWithVideo
	      });
	    }
	  }, {
	    key: "setConferenceStatus",
	    value: function setConferenceStatus(conferenceStarted) {
	      this.controller.getStore().commit('conference/setConferenceStatus', {
	        conferenceStarted: conferenceStarted
	      });
	    }
	  }, {
	    key: "setConferenceHasErrorInCall",
	    value: function setConferenceHasErrorInCall(hasErrorInCall) {
	      this.controller.getStore().commit('conference/setConferenceHasErrorInCall', {
	        hasErrorInCall: hasErrorInCall
	      });
	    }
	  }, {
	    key: "setConferenceStartDate",
	    value: function setConferenceStartDate(conferenceStartDate) {
	      this.controller.getStore().commit('conference/setConferenceStartDate', {
	        conferenceStartDate: conferenceStartDate
	      });
	    }
	  }, {
	    key: "setUserReadyToJoin",
	    value: function setUserReadyToJoin() {
	      this.controller.getStore().commit('conference/setUserReadyToJoin');
	    }
	  }, {
	    key: "updateCallUser",
	    value: function updateCallUser(userId, fields) {
	      this.controller.getStore().dispatch('call/updateUser', {
	        id: userId,
	        fields: fields
	      });
	    }
	    /* endregion 02. Store actions */
	    /* region 03. Rest actions */
	  }, {
	    key: "setUserName",
	    value: function setUserName(name) {
	      var _this35 = this;
	      return new Promise(function (resolve, reject) {
	        _this35.restClient.callMethod('im.call.user.update', {
	          name: name,
	          chat_id: _this35.getChatId()
	        }).then(function () {
	          resolve();
	        })["catch"](function (error) {
	          reject(error);
	        });
	      });
	    }
	  }, {
	    key: "checkPassword",
	    value: function checkPassword(password) {
	      var _this36 = this;
	      return new Promise(function (resolve, reject) {
	        _this36.restClient.callMethod('im.videoconf.password.check', {
	          password: password,
	          alias: _this36.params.alias
	        }).then(function (result) {
	          if (result.data() === true) {
	            _this36.restClient.setPassword(password);
	            _this36.controller.getStore().commit('conference/common', {
	              passChecked: true
	            });
	            _this36.initUserComplete();
	            resolve();
	          } else {
	            reject();
	          }
	        })["catch"](function (result) {
	          console.error('Password check error', result);
	        });
	      });
	    }
	  }, {
	    key: "changeLink",
	    value: function changeLink() {
	      var _this37 = this;
	      return new Promise(function (resolve, reject) {
	        _this37.restClient.callMethod('im.videoconf.share.change', {
	          dialog_id: _this37.getDialogId()
	        }).then(function () {
	          resolve();
	        })["catch"](function (error) {
	          reject(error);
	        });
	      });
	    }
	    /* endregion 03. Rest actions */
	    /* endregion 02. Component methods */
	    /* endregion 02. Methods */
	    /* region 03. Utils */
	  }, {
	    key: "ready",
	    value: function ready() {
	      if (this.inited) {
	        var promise$$1 = new BX.Promise();
	        promise$$1.resolve(this);
	        return promise$$1;
	      }
	      return this.initPromise;
	    }
	  }, {
	    key: "getConference",
	    value: function getConference() {
	      return this.controller.getStore().state.conference;
	    }
	  }, {
	    key: "isBroadcast",
	    value: function isBroadcast() {
	      return this.getConference().common.isBroadcast;
	    }
	  }, {
	    key: "getBroadcastPresenters",
	    value: function getBroadcastPresenters() {
	      return this.getConference().common.presenters;
	    }
	  }, {
	    key: "isExternalUser",
	    value: function isExternalUser() {
	      return !!this.getUserHash();
	    }
	  }, {
	    key: "getCallConfig",
	    value: function getCallConfig(videoEnabled, callUuid) {
	      var _this$getDialogData;
	      var callConfig = {
	        videoEnabled: videoEnabled,
	        type: Call.Type.Permanent,
	        entityType: 'chat',
	        entityId: this.getDialogId(),
	        provider: Call.Provider.Bitrix,
	        enableMicAutoParameters: Call.Hardware.enableMicAutoParameters,
	        joinExisting: true,
	        token: this.callToken,
	        chatInfo: {
	          advanced: {
	            chatType: 'videoconf',
	            entityData1: '',
	            entityData2: '',
	            entityData3: '',
	            entityId: this.getDialogId(),
	            entityType: 'VIDEOCONF'
	          },
	          avatar: '/bitrix/js/im/images/blank.gif',
	          avatarColor: '#ab7761',
	          id: this.getDialogId(),
	          chatId: this.getChatId(),
	          name: this.params.conferenceTitle,
	          type: 'chat',
	          userCounter: (_this$getDialogData = this.getDialogData()) === null || _this$getDialogData === void 0 ? void 0 : _this$getDialogData.userCounter
	        }
	      };
	      if (callUuid) {
	        callConfig.roomId = callUuid;
	      }
	      return callConfig;
	    }
	  }, {
	    key: "getChatId",
	    value: function getChatId() {
	      return parseInt(this.params.chatId);
	    }
	  }, {
	    key: "getDialogId",
	    value: function getDialogId() {
	      return this.params.dialogId;
	    }
	  }, {
	    key: "getDialogData",
	    value: function getDialogData() {
	      if (!this.dialogInited) {
	        return false;
	      }
	      return this.controller.getStore().getters['dialogues/get'](this.getDialogId());
	    }
	  }, {
	    key: "getHost",
	    value: function getHost() {
	      return location.origin || '';
	    }
	  }, {
	    key: "getStartupErrorCode",
	    value: function getStartupErrorCode() {
	      return this.params.startupErrorCode ? this.params.startupErrorCode : '';
	    }
	  }, {
	    key: "isHttps",
	    value: function isHttps() {
	      return location.protocol === 'https:';
	    }
	  }, {
	    key: "getUserHash",
	    value: function getUserHash() {
	      return this.getConference().user.hash;
	    }
	  }, {
	    key: "getUserHashCookie",
	    value: function getUserHashCookie() {
	      var userHash = '';
	      var cookie = im_lib_cookie.Cookie.get(null, 'BITRIX_CALL_HASH');
	      if (typeof cookie === 'string' && cookie.match(/^[a-f0-9]{32}$/)) {
	        userHash = cookie;
	      }
	      return userHash;
	    }
	  }, {
	    key: "switchToSessAuth",
	    value: function switchToSessAuth() {
	      this.restClient.restClient.queryParams = undefined;
	      return true;
	    } /* endregion 03. Utils */
	  }]);
	  return ConferenceApplication;
	}();
	function _stopRecordCall2() {
	  if (this.isRecording()) {
	    this.callRecordState = Call.View.RecordState.Stopped;
	    this.callView.setRecordState(this.callView.getDefaultRecordState());
	    this.callView.setButtonActive('record', false);
	    this.currentCall.sendRecordState({
	      action: Call.View.RecordState.Stopped,
	      userId: this.currentCall.userId
	    });
	  }
	}
	ConferenceApplication.FeatureState = {
	  Enabled: 'enabled',
	  Disabled: 'disabled',
	  Limited: 'limited'
	};

	exports.ConferenceApplication = ConferenceApplication;
	exports.ConferenceChannel = ConferenceChannel;

}((this.BX.Messenger.Application = this.BX.Messenger.Application || {}),BX.Call.Lib,BX.Call.Lib,BX.Call.Lib,BX,BX.Messenger.Application,BX.Messenger,BX.Messenger.Model,BX.Messenger,BX.Messenger.Lib,BX.Messenger.Lib,BX.Messenger.Lib,BX.Messenger.Lib,BX.Messenger.Lib,BX.Messenger.Const,BX.UI.NotificationManager,BX,BX.UI,BX.UI,BX.UI.Viewer,BX,BX,BX,BX,BX.Main,BX.Event,BX,BX.Messenger.Provider.Pull,BX.Messenger.v2.Lib,BX.Call,BX,BX.Messenger.Lib));
//# sourceMappingURL=conference.bundle.js.map
