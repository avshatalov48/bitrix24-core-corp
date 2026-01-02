/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core_events,main_date,sign_v2_signSettings,sign_v2_b2e_userParty,sign_v2_b2e_reminderSelector,sign_type,main_core,main_loader,ui_entitySelector,sign_v2_api,sign_v2_documentSummary,sign_v2_langSelector,sign_v2_datetimeLimitSelector,sign_v2_helper,ui_progressbar,sign_v2_b2e_hcmLinkPartyChecker) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var EntityTypes = Object.freeze({
	  User: 'user',
	  Company: 'company',
	  Role: 'structure-node-role'
	});
	var _api = /*#__PURE__*/new WeakMap();
	var _ui = /*#__PURE__*/new WeakMap();
	var _dataDialog = /*#__PURE__*/new WeakMap();
	var _loader = /*#__PURE__*/new WeakMap();
	var _data = /*#__PURE__*/new WeakMap();
	var _viewData = /*#__PURE__*/new WeakMap();
	var _load = /*#__PURE__*/new WeakSet();
	var _loadCompany = /*#__PURE__*/new WeakSet();
	var _loadUser = /*#__PURE__*/new WeakSet();
	var _loadRole = /*#__PURE__*/new WeakSet();
	var _refreshView = /*#__PURE__*/new WeakSet();
	var _getLoader = /*#__PURE__*/new WeakSet();
	var _hideLoader = /*#__PURE__*/new WeakSet();
	var _showLoader = /*#__PURE__*/new WeakSet();
	var Item = /*#__PURE__*/function () {
	  function Item(_data2) {
	    babelHelpers.classCallCheck(this, Item);
	    _classPrivateMethodInitSpec(this, _showLoader);
	    _classPrivateMethodInitSpec(this, _hideLoader);
	    _classPrivateMethodInitSpec(this, _getLoader);
	    _classPrivateMethodInitSpec(this, _refreshView);
	    _classPrivateMethodInitSpec(this, _loadRole);
	    _classPrivateMethodInitSpec(this, _loadUser);
	    _classPrivateMethodInitSpec(this, _loadCompany);
	    _classPrivateMethodInitSpec(this, _load);
	    _classPrivateFieldInitSpec(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _ui, {
	      writable: true,
	      value: {
	        container: HTMLDivElement = null,
	        avatar: HTMLDivElement = null,
	        title: {
	          container: HTMLDivElement = null,
	          header: HTMLDivElement = null,
	          footer: HTMLDivElement = null
	        }
	      }
	    });
	    _classPrivateFieldInitSpec(this, _dataDialog, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _loader, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _data, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _viewData, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _api, new sign_v2_api.Api());
	    babelHelpers.classPrivateFieldSet(this, _data, _data2);
	    babelHelpers.classPrivateFieldGet(this, _ui).container = this.getLayout();
	    if (main_core.Type.isStringFilled(_data2 === null || _data2 === void 0 ? void 0 : _data2.entityType) && main_core.Type.isInteger(_data2 === null || _data2 === void 0 ? void 0 : _data2.entityId)) {
	      _classPrivateMethodGet(this, _load, _load2).call(this);
	    }
	  }
	  babelHelpers.createClass(Item, [{
	    key: "setItemData",
	    value: function setItemData(data) {
	      if (babelHelpers.classPrivateFieldGet(this, _data).entityId === data.entityId && babelHelpers.classPrivateFieldGet(this, _data).entityType === data.entityType) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _data, data);
	      if (main_core.Type.isStringFilled(data === null || data === void 0 ? void 0 : data.entityType) && main_core.Type.isInteger(data === null || data === void 0 ? void 0 : data.entityId)) {
	        _classPrivateMethodGet(this, _load, _load2).call(this);
	      }
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      if (babelHelpers.classPrivateFieldGet(this, _ui).container) {
	        return babelHelpers.classPrivateFieldGet(this, _ui).container;
	      }
	      var modifier = babelHelpers.classPrivateFieldGet(this, _data).entityType === EntityTypes.User ? ' --user' : '';
	      babelHelpers.classPrivateFieldGet(this, _ui).avatar = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-b2e-send__party_item-info-avatar", "\"></div>"])), modifier);
	      babelHelpers.classPrivateFieldGet(this, _ui).title.header = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-b2e-send__party_item-info-header\"></div>"])));
	      babelHelpers.classPrivateFieldGet(this, _ui).title.footer = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-b2e-send__party_item-info-footer\"></div>"])));
	      babelHelpers.classPrivateFieldGet(this, _ui).title.container = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-send__party_item-info-title\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _ui).title.header, babelHelpers.classPrivateFieldGet(this, _ui).title.footer);
	      babelHelpers.classPrivateFieldGet(this, _ui).container = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-send__party_item-info\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _ui).avatar, babelHelpers.classPrivateFieldGet(this, _ui).title.container);
	      return babelHelpers.classPrivateFieldGet(this, _ui).container;
	    }
	  }]);
	  return Item;
	}();
	function _load2() {
	  return _load3.apply(this, arguments);
	}
	function _load3() {
	  _load3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	    return _regeneratorRuntime().wrap(function _callee$(_context) {
	      while (1) switch (_context.prev = _context.next) {
	        case 0:
	          _classPrivateMethodGet(this, _showLoader, _showLoader2).call(this);
	          _context.t0 = babelHelpers.classPrivateFieldGet(this, _data).entityType;
	          _context.next = _context.t0 === EntityTypes.Company ? 4 : _context.t0 === EntityTypes.User ? 7 : _context.t0 === EntityTypes.Role ? 10 : 13;
	          break;
	        case 4:
	          _context.next = 6;
	          return _classPrivateMethodGet(this, _loadCompany, _loadCompany2).call(this);
	        case 6:
	          return _context.abrupt("break", 14);
	        case 7:
	          _context.next = 9;
	          return _classPrivateMethodGet(this, _loadUser, _loadUser2).call(this);
	        case 9:
	          return _context.abrupt("break", 14);
	        case 10:
	          _context.next = 12;
	          return _classPrivateMethodGet(this, _loadRole, _loadRole2).call(this);
	        case 12:
	          return _context.abrupt("break", 14);
	        case 13:
	          return _context.abrupt("break", 14);
	        case 14:
	          _classPrivateMethodGet(this, _refreshView, _refreshView2).call(this);
	          _classPrivateMethodGet(this, _hideLoader, _hideLoader2).call(this);
	        case 16:
	        case "end":
	          return _context.stop();
	      }
	    }, _callee, this);
	  }));
	  return _load3.apply(this, arguments);
	}
	function _loadCompany2() {
	  var _this = this;
	  return babelHelpers.classPrivateFieldGet(this, _api).loadB2eCompanyList().then(function (data) {
	    if (main_core.Type.isObject(data.companies) && main_core.Type.isArray(data.companies)) {
	      var _data$companies$filte;
	      var company = (_data$companies$filte = data.companies.filter(function (company) {
	        return company.id === babelHelpers.classPrivateFieldGet(_this, _data).entityId;
	      })[0]) !== null && _data$companies$filte !== void 0 ? _data$companies$filte : null;
	      if (company === null) {
	        return;
	      }
	      var footer = main_core.Type.isBoolean(data === null || data === void 0 ? void 0 : data.showTaxId) && data !== null && data !== void 0 && data.showTaxId && company !== null && company !== void 0 && company.rqInn ? main_core.Loc.getMessage('SIGN_DOCUMENT_SUMMARY_COMPANY_INN', {
	        '%innValue%': main_core.Text.encode(company === null || company === void 0 ? void 0 : company.rqInn)
	      }) : null;
	      babelHelpers.classPrivateFieldSet(_this, _viewData, {
	        header: company === null || company === void 0 ? void 0 : company.title,
	        footer: footer,
	        avatar: null
	      });
	      _classPrivateMethodGet(_this, _refreshView, _refreshView2).call(_this);
	    }
	    _classPrivateMethodGet(_this, _hideLoader, _hideLoader2).call(_this);
	  })["catch"](function (response) {
	    console.log(response);
	  });
	}
	function _loadUser2() {
	  var _this2 = this;
	  return new Promise(function (resolve) {
	    babelHelpers.classPrivateFieldSet(_this2, _dataDialog, new ui_entitySelector.Dialog({
	      entities: [{
	        id: EntityTypes.User
	      }],
	      events: {
	        'onLoad': function onLoad(event) {
	          var _babelHelpers$classPr;
	          var user = (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(_this2, _dataDialog).getSelectedItems()[0]) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : null;
	          if (main_core.Type.isObject(user)) {
	            var _user$customData$get, _user$customData, _user$customData2, _user$customData$get2, _user$customData3, _user$avatar;
	            var lastName = (_user$customData$get = user === null || user === void 0 ? void 0 : (_user$customData = user.customData) === null || _user$customData === void 0 ? void 0 : _user$customData.get('lastName')) !== null && _user$customData$get !== void 0 ? _user$customData$get : '';
	            babelHelpers.classPrivateFieldSet(_this2, _viewData, {
	              header: (user === null || user === void 0 ? void 0 : (_user$customData2 = user.customData) === null || _user$customData2 === void 0 ? void 0 : _user$customData2.get('name')) + ' ' + lastName,
	              footer: (_user$customData$get2 = user === null || user === void 0 ? void 0 : (_user$customData3 = user.customData) === null || _user$customData3 === void 0 ? void 0 : _user$customData3.get('position')) !== null && _user$customData$get2 !== void 0 ? _user$customData$get2 : '',
	              avatar: (_user$avatar = user === null || user === void 0 ? void 0 : user.avatar) !== null && _user$avatar !== void 0 ? _user$avatar : null
	            });
	            _classPrivateMethodGet(_this2, _refreshView, _refreshView2).call(_this2);
	          }
	          _classPrivateMethodGet(_this2, _hideLoader, _hideLoader2).call(_this2);
	          resolve();
	        }
	      },
	      preselectedItems: [[EntityTypes.User, babelHelpers.classPrivateFieldGet(_this2, _data).entityId]]
	    }));
	    babelHelpers.classPrivateFieldGet(_this2, _dataDialog).load();
	  });
	}
	function _loadRole2() {
	  var _this3 = this;
	  return new Promise(function (resolve) {
	    babelHelpers.classPrivateFieldSet(_this3, _dataDialog, new ui_entitySelector.Dialog({
	      entities: [{
	        id: EntityTypes.Role,
	        dynamicLoad: true
	      }],
	      events: {
	        'onLoad': function onLoad(event) {
	          var _babelHelpers$classPr2;
	          var data = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(_this3, _dataDialog).getSelectedItems()[0]) !== null && _babelHelpers$classPr2 !== void 0 ? _babelHelpers$classPr2 : null;
	          if (main_core.Type.isObject(data)) {
	            var _data$title, _data$avatar;
	            babelHelpers.classPrivateFieldSet(_this3, _viewData, {
	              header: (_data$title = data.title) !== null && _data$title !== void 0 ? _data$title : '',
	              footer: '',
	              avatar: (_data$avatar = data === null || data === void 0 ? void 0 : data.avatar) !== null && _data$avatar !== void 0 ? _data$avatar : null
	            });
	            _classPrivateMethodGet(_this3, _refreshView, _refreshView2).call(_this3);
	          }
	          _classPrivateMethodGet(_this3, _hideLoader, _hideLoader2).call(_this3);
	          resolve();
	        }
	      },
	      preselectedItems: [[EntityTypes.Role, babelHelpers.classPrivateFieldGet(_this3, _data).entityId]]
	    }));
	    babelHelpers.classPrivateFieldGet(_this3, _dataDialog).load();
	  });
	}
	function _refreshView2() {
	  var _babelHelpers$classPr3;
	  if ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _viewData)) !== null && _babelHelpers$classPr3 !== void 0 && _babelHelpers$classPr3.avatar) {
	    babelHelpers.classPrivateFieldGet(this, _ui).avatar.style.backgroundImage = "url(\"".concat(babelHelpers.classPrivateFieldGet(this, _viewData).avatar, "\")");
	  }
	  babelHelpers.classPrivateFieldGet(this, _ui).title.header.innerText = babelHelpers.classPrivateFieldGet(this, _viewData).header;
	  babelHelpers.classPrivateFieldGet(this, _ui).title.header.title = babelHelpers.classPrivateFieldGet(this, _viewData).header;
	  babelHelpers.classPrivateFieldGet(this, _ui).title.footer.innerText = babelHelpers.classPrivateFieldGet(this, _viewData).footer;
	  babelHelpers.classPrivateFieldGet(this, _ui).title.footer.title = babelHelpers.classPrivateFieldGet(this, _viewData).footer;
	}
	function _getLoader2() {
	  if (babelHelpers.classPrivateFieldGet(this, _loader)) {
	    return babelHelpers.classPrivateFieldGet(this, _loader);
	  }
	  babelHelpers.classPrivateFieldSet(this, _loader, new BX.Loader({
	    target: babelHelpers.classPrivateFieldGet(this, _ui).container,
	    mode: 'inline',
	    size: 40
	  }));
	  return babelHelpers.classPrivateFieldGet(this, _loader);
	}
	function _hideLoader2() {
	  babelHelpers.classPrivateFieldGet(this, _ui).title.container.style.display = 'flex';
	  babelHelpers.classPrivateFieldGet(this, _ui).avatar.style.display = 'block';
	  _classPrivateMethodGet(this, _getLoader, _getLoader2).call(this).hide();
	}
	function _showLoader2() {
	  babelHelpers.classPrivateFieldGet(this, _ui).avatar.style.display = 'none';
	  babelHelpers.classPrivateFieldGet(this, _ui).title.container.style.display = 'none';
	  _classPrivateMethodGet(this, _getLoader, _getLoader2).call(this).show(babelHelpers.classPrivateFieldGet(this, _ui).container);
	}

	var _ReminderSelectorOpti, _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5$1, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16, _templateObject17, _templateObject18, _templateObject19, _templateObject20, _templateObject21;
	function _regeneratorRuntime$1() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$1 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var ReminderSelectorOptionsByRole = (_ReminderSelectorOpti = {}, babelHelpers.defineProperty(_ReminderSelectorOpti, sign_type.MemberRole.assignee, {
	  preSelectedType: sign_type.Reminder.oncePerDay
	}), babelHelpers.defineProperty(_ReminderSelectorOpti, sign_type.MemberRole.signer, {
	  preSelectedType: sign_type.Reminder.twicePerDay
	}), _ReminderSelectorOpti);
	var idleCommunication = 'idle';
	var _ui$1 = /*#__PURE__*/new WeakMap();
	var _communicationSelectedOption = /*#__PURE__*/new WeakMap();
	var _items = /*#__PURE__*/new WeakMap();
	var _partiesData = /*#__PURE__*/new WeakMap();
	var _documentSummary = /*#__PURE__*/new WeakMap();
	var _documentData = /*#__PURE__*/new WeakMap();
	var _langSelector = /*#__PURE__*/new WeakMap();
	var _dateTimeLimitSelector = /*#__PURE__*/new WeakMap();
	var _progress = /*#__PURE__*/new WeakMap();
	var _progressOverlay = /*#__PURE__*/new WeakMap();
	var _progressContainer = /*#__PURE__*/new WeakMap();
	var _itemsToHide = /*#__PURE__*/new WeakMap();
	var _reminderSelectorByRole = /*#__PURE__*/new WeakMap();
	var _documentMode = /*#__PURE__*/new WeakMap();
	var _isExistingTemplate = /*#__PURE__*/new WeakMap();
	var _isOpenedFromRobot = /*#__PURE__*/new WeakMap();
	var _templateFolderId = /*#__PURE__*/new WeakMap();
	var _analytics = /*#__PURE__*/new WeakMap();
	var _hcmLinkPartyChecker = /*#__PURE__*/new WeakMap();
	var _provider = /*#__PURE__*/new WeakMap();
	var _getProgressAnimateLayout = /*#__PURE__*/new WeakSet();
	var _getPartyLayout = /*#__PURE__*/new WeakSet();
	var _renderLangAndDateContainer = /*#__PURE__*/new WeakSet();
	var _renderLangSelectorLayout = /*#__PURE__*/new WeakSet();
	var _renderDateSignUntilSelectorLayout = /*#__PURE__*/new WeakSet();
	var _renderGoskeyAlertForSignUntilSelector = /*#__PURE__*/new WeakSet();
	var _getProgressOverlay = /*#__PURE__*/new WeakSet();
	var _getTemplateProgressOverlay = /*#__PURE__*/new WeakSet();
	var _getCloseBtn = /*#__PURE__*/new WeakSet();
	var _getAllTemplatesBtn = /*#__PURE__*/new WeakSet();
	var _checkFillAndStartProgress = /*#__PURE__*/new WeakSet();
	var _groupCheckFillAndStartProgress = /*#__PURE__*/new WeakSet();
	var _sleep = /*#__PURE__*/new WeakSet();
	var _refreshView$1 = /*#__PURE__*/new WeakSet();
	var _getCommunicationsLayout = /*#__PURE__*/new WeakSet();
	var _showProgressOverlay = /*#__PURE__*/new WeakSet();
	var _hideProgressOverlay = /*#__PURE__*/new WeakSet();
	var _getReminderSelectorLayout = /*#__PURE__*/new WeakSet();
	var _getOrCreateReminderSelectorForRole = /*#__PURE__*/new WeakSet();
	var _saveReminderTypesForRoles = /*#__PURE__*/new WeakSet();
	var _getFirstDocumentUidFromGroup = /*#__PURE__*/new WeakSet();
	var _isTemplateMode = /*#__PURE__*/new WeakSet();
	var _isGroupDocuments = /*#__PURE__*/new WeakSet();
	var _getGroupIdFromGroup = /*#__PURE__*/new WeakSet();
	var _onHcmLinkCheckerUpdateValidation = /*#__PURE__*/new WeakSet();
	var DocumentSend = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(DocumentSend, _EventEmitter);
	  function DocumentSend(documentSendConfig) {
	    var _this;
	    babelHelpers.classCallCheck(this, DocumentSend);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DocumentSend).call(this));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onHcmLinkCheckerUpdateValidation);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getGroupIdFromGroup);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _isGroupDocuments);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _isTemplateMode);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getFirstDocumentUidFromGroup);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _saveReminderTypesForRoles);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getOrCreateReminderSelectorForRole);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getReminderSelectorLayout);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _hideProgressOverlay);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _showProgressOverlay);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getCommunicationsLayout);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _refreshView$1);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _sleep);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _groupCheckFillAndStartProgress);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _checkFillAndStartProgress);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getAllTemplatesBtn);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getCloseBtn);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getTemplateProgressOverlay);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getProgressOverlay);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _renderGoskeyAlertForSignUntilSelector);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _renderDateSignUntilSelectorLayout);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _renderLangSelectorLayout);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _renderLangAndDateContainer);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getPartyLayout);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getProgressAnimateLayout);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "events", Object.freeze({
	      onTemplateComplete: 'onTemplateComplete'
	    }));
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _ui$1, {
	      writable: true,
	      value: {
	        container: HTMLDivElement = null,
	        employeesTitle: HTMLParagraphElement
	      }
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _communicationSelectedOption, {
	      writable: true,
	      value: {
	        company: {
	          id: 'company',
	          option: idleCommunication
	        },
	        employee: {
	          id: 'employee',
	          option: idleCommunication
	        },
	        validation: {
	          id: 'validation',
	          option: idleCommunication
	        }
	      }
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _items, {
	      writable: true,
	      value: {
	        company: Item,
	        representative: Item,
	        employees: sign_v2_b2e_userParty.UserParty,
	        reviewers: sign_v2_b2e_userParty.UserParty,
	        editor: Item
	      }
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _partiesData, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _documentSummary, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _documentData, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _langSelector, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _dateTimeLimitSelector, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _progress, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _progressOverlay, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _progressContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _itemsToHide, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _reminderSelectorByRole, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _documentMode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _isExistingTemplate, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _isOpenedFromRobot, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _templateFolderId, {
	      writable: true,
	      value: 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _analytics, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _hcmLinkPartyChecker, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _provider, {
	      writable: true,
	      value: null
	    });
	    _this.setEventNamespace('BX.Sign.V2.B2e.DocumentSend');
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _items).company = new Item({
	      entityType: EntityTypes.Company
	    });
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _items).representative = new Item({
	      entityType: EntityTypes.User
	    });
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _items).editor = new Item({
	      entityType: EntityTypes.User
	    });
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _items).employees = new sign_v2_b2e_userParty.UserParty({
	      mode: 'view',
	      role: sign_type.MemberRole.signer
	    });
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _items).reviewers = new sign_v2_b2e_userParty.UserParty({
	      mode: 'view',
	      role: sign_type.MemberRole.reviewer
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _documentSummary, new sign_v2_documentSummary.DocumentSummary({
	      events: {
	        changeTitle: function changeTitle(event) {
	          var data = event.getData();
	          _this.emit('changeTitle', data);
	        },
	        showEditor: function showEditor(event) {
	          var data = event.getData();
	          _this.emit('showEditor', data);
	        }
	      }
	    }));
	    var region = documentSendConfig.region,
	      languages = documentSendConfig.languages,
	      documentMode = documentSendConfig.documentMode,
	      isOpenedFromRobot = documentSendConfig.isOpenedFromRobot,
	      analytics = documentSendConfig.analytics,
	      templateFolderId = documentSendConfig.templateFolderId;
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _isOpenedFromRobot, isOpenedFromRobot);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _langSelector, new sign_v2_langSelector.LangSelector(region, languages));
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _documentData, {});
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _analytics, analytics);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _documentMode, documentMode);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _templateFolderId, templateFolderId);
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _ui$1).employeesTitle = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<p class=\"sign-b2e-send__party_signing-employees\">\n\t\t\t\t", "\n\t\t\t</p>\n\t\t"])), main_core.Loc.getMessage('SIGN_SEND_SIGNING_EMPLOYEES', {
	      '#CNT#': 0
	    }));
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _progress, new ui_progressbar.ProgressBar({
	      maxValue: 100,
	      value: 0,
	      colorTrack: '#dfe3e6'
	    }));
	    [sign_type.MemberRole.assignee, sign_type.MemberRole.signer].forEach(function (role) {
	      _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _getOrCreateReminderSelectorForRole, _getOrCreateReminderSelectorForRole2).call(babelHelpers.assertThisInitialized(_this), role);
	    });
	    if (!_classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _isTemplateMode, _isTemplateMode2).call(babelHelpers.assertThisInitialized(_this))) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _hcmLinkPartyChecker, new sign_v2_b2e_hcmLinkPartyChecker.HcmLinkPartyChecker({
	        api: new sign_v2_api.Api()
	      }));
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _hcmLinkPartyChecker).subscribe('updateValidation', function (event) {
	        return _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _onHcmLinkCheckerUpdateValidation, _onHcmLinkCheckerUpdateValidation2).call(babelHelpers.assertThisInitialized(_this), event);
	      });
	    }
	    if (!_classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _isTemplateMode, _isTemplateMode2).call(babelHelpers.assertThisInitialized(_this))) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _dateTimeLimitSelector, new sign_v2_datetimeLimitSelector.DatetimeLimitSelector());
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _dateTimeLimitSelector).subscribe('beforeDateModify', function () {
	        _this.emit('disableComplete');
	      }).subscribe('afterDateModify', function () {
	        _this.emit('enableComplete');
	      });
	    }
	    return _this;
	  }
	  babelHelpers.createClass(DocumentSend, [{
	    key: "getLayout",
	    value: function getLayout() {
	      var _this2 = this;
	      var layout = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-send\">\n\t\t\t\t<h1 class=\"sign-b2e-settings__header\">", "</h1>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_HEADER_1'));
	      var summaryTitle = _classPrivateMethodGet$1(this, _isTemplateMode, _isTemplateMode2).call(this) ? main_core.Loc.getMessage('SIGN_DOCUMENT_SUMMARY_TEMPLATE_TITLE') : main_core.Loc.getMessage('SIGN_DOCUMENT_SUMMARY_TITLE');
	      babelHelpers.classPrivateFieldSet(this, _itemsToHide, []);
	      var summaryLayout = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-settings__item\">\n\t\t\t\t<p class=\"sign-b2e-settings__item_title\">\n\t\t\t\t\t", "\n\t\t\t\t</p>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), summaryTitle, babelHelpers.classPrivateFieldGet(this, _documentSummary).getLayout(), _classPrivateMethodGet$1(this, _renderLangAndDateContainer, _renderLangAndDateContainer2).call(this));
	      babelHelpers.classPrivateFieldGet(this, _itemsToHide).push(summaryLayout);
	      var usersLayout = null;
	      if (!_classPrivateMethodGet$1(this, _isTemplateMode, _isTemplateMode2).call(this)) {
	        usersLayout = main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-b2e-settings__item\">\n\t\t\t\t\t<p class=\"sign-b2e-settings__item_title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</p>\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"sign-b2e-send__config-container\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_SECOND_PARTY'), babelHelpers.classPrivateFieldGet(this, _ui$1).employeesTitle, babelHelpers.classPrivateFieldGet(this, _items).employees.getLayout(), _classPrivateMethodGet$1(this, _getCommunicationsLayout, _getCommunicationsLayout2).call(this, 'employee'), _classPrivateMethodGet$1(this, _getReminderSelectorLayout, _getReminderSelectorLayout2).call(this, sign_type.MemberRole.signer), babelHelpers.classPrivateFieldGet(this, _hcmLinkPartyChecker).render());
	        babelHelpers.classPrivateFieldGet(this, _itemsToHide).push(usersLayout);
	      }
	      var itemTitleText = _classPrivateMethodGet$1(this, _isTemplateMode, _isTemplateMode2).call(this) ? main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_FIRST_PARTY_TEMPLATE') : main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_FIRST_PARTY');
	      var companyLayout = main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-settings__item\">\n\t\t\t\t<p class=\"sign-b2e-settings__item_title\">\n\t\t\t\t\t", "\n\t\t\t\t</p>\n\t\t\t\t<div class=\"sign-b2e-send__company-items\">\n\t\t\t\t\t<div class=\"sign-b2e-send__company-items_flex\">\n\t\t\t\t\t\t<p class=\"sign-b2e-send__company-items_item-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</p>\n\t\t\t\t\t\t<span class=\"sign-b2e-send__company-items_shrunk\"></span>\n\t\t\t\t\t\t<p class=\"sign-b2e-send__company-items_item-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</p>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"sign-b2e-send__company-items_flex\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<span class=\"sign-b2e-send__company-items_shrunk sign-b2e-send__party-item-separator\">\n\t\t\t\t\t\t\t&#43;\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"sign-b2e-send__config-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), itemTitleText, main_core.Loc.getMessage('SIGN_SEND_SIGNING_COMPANY'), main_core.Loc.getMessage('SIGN_SEND_SIGNING_REPRESENTATIVE'), babelHelpers.classPrivateFieldGet(this, _items).company.getLayout(), babelHelpers.classPrivateFieldGet(this, _items).representative.getLayout(), _classPrivateMethodGet$1(this, _getCommunicationsLayout, _getCommunicationsLayout2).call(this, 'company'), _classPrivateMethodGet$1(this, _getReminderSelectorLayout, _getReminderSelectorLayout2).call(this, sign_type.MemberRole.assignee));
	      babelHelpers.classPrivateFieldGet(this, _itemsToHide).push(companyLayout);
	      main_core.Dom.append(summaryLayout, layout);
	      var reviewerHeaderText = _classPrivateMethodGet$1(this, _isTemplateMode, _isTemplateMode2).call(this) ? main_core.Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_HEAD_REVIEWER_TEMPLATE') : main_core.Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_HEAD_REVIEWER');
	      var validationTitles = babelHelpers.defineProperty({}, sign_type.MemberRole.editor, {
	        header: main_core.Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_HEAD_EDITOR'),
	        hint: main_core.Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_TITLE_EDITOR')
	      });
	      babelHelpers.classPrivateFieldGet(this, _partiesData).validation.forEach(function (_ref) {
	        var role = _ref.role;
	        if (role === sign_type.MemberRole.reviewer) {
	          return;
	        }
	        var roleBlockLayout = babelHelpers.classPrivateFieldGet(_this2, _items)[role].getLayout();
	        if (!roleBlockLayout) {
	          return;
	        }
	        var _validationTitles$rol = validationTitles[role],
	          hint = _validationTitles$rol.hint,
	          header = _validationTitles$rol.header;
	        var validationLayout = _classPrivateMethodGet$1(_this2, _getPartyLayout, _getPartyLayout2).call(_this2, header, hint, roleBlockLayout);
	        babelHelpers.classPrivateFieldGet(_this2, _itemsToHide).push(validationLayout);
	        main_core.Dom.append(validationLayout, layout);
	      });
	      if (babelHelpers.classPrivateFieldGet(this, _items).reviewers.getPreselectedUserData().length > 0) {
	        var reviewerListLayout = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-b2e-document-send-reviewers\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _items).reviewers.getLayout());
	        var reviewersLayout = _classPrivateMethodGet$1(this, _getPartyLayout, _getPartyLayout2).call(this, reviewerHeaderText, main_core.Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_TITLE_REVIEWER_MSGVER_1'), reviewerListLayout);
	        babelHelpers.classPrivateFieldGet(this, _itemsToHide).push(reviewersLayout);
	        main_core.Dom.append(reviewersLayout, layout);
	      }
	      main_core.Dom.append(companyLayout, layout);
	      if (!main_core.Type.isNull(usersLayout)) {
	        main_core.Dom.append(usersLayout, layout);
	      }
	      babelHelpers.classPrivateFieldSet(this, _progressContainer, main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<div class=\"send-b2e-progress-container\"></div>"]))));
	      babelHelpers.classPrivateFieldGet(this, _progress).renderTo(babelHelpers.classPrivateFieldGet(this, _progressContainer));
	      babelHelpers.classPrivateFieldSet(this, _progressOverlay, _classPrivateMethodGet$1(this, _isTemplateMode, _isTemplateMode2).call(this) ? _classPrivateMethodGet$1(this, _getTemplateProgressOverlay, _getTemplateProgressOverlay2).call(this) : _classPrivateMethodGet$1(this, _getProgressOverlay, _getProgressOverlay2).call(this));
	      main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _progressOverlay), 'display', 'none');
	      this.emit('appendOverlay', {
	        overlay: babelHelpers.classPrivateFieldGet(this, _progressOverlay)
	      });
	      sign_v2_helper.Hint.create(layout);
	      return layout;
	    }
	  }, {
	    key: "setExistingTemplate",
	    value: function setExistingTemplate() {
	      babelHelpers.classPrivateFieldSet(this, _isExistingTemplate, true);
	    }
	  }, {
	    key: "resetUserPartyPopup",
	    value: function resetUserPartyPopup() {
	      babelHelpers.classPrivateFieldGet(this, _items).employees.resetUserPartyPopup();
	      babelHelpers.classPrivateFieldGet(this, _items).reviewers.resetUserPartyPopup();
	      return this;
	    }
	  }, {
	    key: "setProvider",
	    value: function setProvider(provider) {
	      babelHelpers.classPrivateFieldSet(this, _provider, provider);
	      return this;
	    }
	  }, {
	    key: "setPartiesData",
	    value: function setPartiesData(parties) {
	      var _parties$company,
	        _parties$representati,
	        _this3 = this;
	      babelHelpers.classPrivateFieldSet(this, _partiesData, parties);
	      if (main_core.Type.isNumber(parties === null || parties === void 0 ? void 0 : (_parties$company = parties.company) === null || _parties$company === void 0 ? void 0 : _parties$company.entityId)) {
	        var _parties$company2, _parties$company3;
	        babelHelpers.classPrivateFieldGet(this, _items).company.setItemData({
	          entityId: parties === null || parties === void 0 ? void 0 : (_parties$company2 = parties.company) === null || _parties$company2 === void 0 ? void 0 : _parties$company2.entityId,
	          entityType: parties === null || parties === void 0 ? void 0 : (_parties$company3 = parties.company) === null || _parties$company3 === void 0 ? void 0 : _parties$company3.entityType
	        });
	      }
	      if (main_core.Type.isNumber(parties === null || parties === void 0 ? void 0 : (_parties$representati = parties.representative) === null || _parties$representati === void 0 ? void 0 : _parties$representati.entityId)) {
	        var _parties$representati2, _parties$representati3;
	        babelHelpers.classPrivateFieldGet(this, _items).representative.setItemData({
	          entityId: parties === null || parties === void 0 ? void 0 : (_parties$representati2 = parties.representative) === null || _parties$representati2 === void 0 ? void 0 : _parties$representati2.entityId,
	          entityType: parties === null || parties === void 0 ? void 0 : (_parties$representati3 = parties.representative) === null || _parties$representati3 === void 0 ? void 0 : _parties$representati3.entityType
	        });
	      }
	      if (main_core.Type.isArrayFilled(parties === null || parties === void 0 ? void 0 : parties.employees)) {
	        babelHelpers.classPrivateFieldGet(this, _items).employees.setUserIds(parties === null || parties === void 0 ? void 0 : parties.employees.map(function (employee) {
	          return {
	            entityId: employee.entityId,
	            entityType: employee.entityType
	          };
	        }));
	      }
	      if (main_core.Type.isArrayFilled(parties === null || parties === void 0 ? void 0 : parties.validation)) {
	        var reviewerIdList = [];
	        parties.validation.forEach(function (party) {
	          var entityId = party.entityId,
	            entityType = party.entityType,
	            role = party.role;
	          if (role === sign_type.MemberRole.reviewer) {
	            reviewerIdList.push({
	              entityId: entityId,
	              entityType: entityType
	            });
	            return;
	          }
	          babelHelpers.classPrivateFieldGet(_this3, _items)[role].setItemData({
	            entityId: entityId,
	            entityType: entityType
	          });
	        });
	        if (reviewerIdList.length > 0) {
	          babelHelpers.classPrivateFieldGet(this, _items).reviewers.setUserIds(reviewerIdList);
	        }
	      }
	      _classPrivateMethodGet$1(this, _refreshView$1, _refreshView2$1).call(this);
	      return this;
	    }
	  }, {
	    key: "sendForSign",
	    value: function () {
	      var _sendForSign = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee() {
	        var api, documentTemplateId, _yield$api$template$c, templateId, groupId, configureDocumentGroupPromise, groupCheckFillAndStartProgressPromise, _iterator, _step, _step$value, uid, configureDocumentPromise, checkFillAndStartProgressPromise;
	        return _regeneratorRuntime$1().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              api = new sign_v2_api.Api();
	              _context.prev = 1;
	              this.emit('disableBack');
	              _context.next = 5;
	              return _classPrivateMethodGet$1(this, _saveReminderTypesForRoles, _saveReminderTypesForRoles2).call(this);
	            case 5:
	              if (!babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector)) {
	                _context.next = 8;
	                break;
	              }
	              _context.next = 8;
	              return babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector).saveSelectedDateForUids();
	            case 8:
	              _classPrivateMethodGet$1(this, _showProgressOverlay, _showProgressOverlay2).call(this);
	              if (!_classPrivateMethodGet$1(this, _isTemplateMode, _isTemplateMode2).call(this)) {
	                _context.next = 18;
	                break;
	              }
	              documentTemplateId = this.documentData.values().next().value.templateUid;
	              _context.next = 13;
	              return api.template.completeTemplate(documentTemplateId, babelHelpers.classPrivateFieldGet(this, _templateFolderId));
	            case 13:
	              _yield$api$template$c = _context.sent;
	              templateId = _yield$api$template$c.template.id;
	              this.emit('onTemplateComplete', {
	                templateId: templateId
	              });
	              _context.next = 45;
	              break;
	            case 18:
	              if (!_classPrivateMethodGet$1(this, _isGroupDocuments, _isGroupDocuments2).call(this)) {
	                _context.next = 26;
	                break;
	              }
	              groupId = _classPrivateMethodGet$1(this, _getGroupIdFromGroup, _getGroupIdFromGroup2).call(this);
	              configureDocumentGroupPromise = api.configureDocumentGroup(groupId);
	              groupCheckFillAndStartProgressPromise = _classPrivateMethodGet$1(this, _groupCheckFillAndStartProgress, _groupCheckFillAndStartProgress2).call(this, groupId);
	              _context.next = 24;
	              return Promise.all([configureDocumentGroupPromise, groupCheckFillAndStartProgressPromise]);
	            case 24:
	              _context.next = 45;
	              break;
	            case 26:
	              _iterator = _createForOfIteratorHelper(this.documentData);
	              _context.prev = 27;
	              _iterator.s();
	            case 29:
	              if ((_step = _iterator.n()).done) {
	                _context.next = 37;
	                break;
	              }
	              _step$value = babelHelpers.slicedToArray(_step.value, 1), uid = _step$value[0];
	              configureDocumentPromise = api.configureDocument(uid);
	              checkFillAndStartProgressPromise = _classPrivateMethodGet$1(this, _checkFillAndStartProgress, _checkFillAndStartProgress2).call(this, uid);
	              _context.next = 35;
	              return Promise.all([configureDocumentPromise, checkFillAndStartProgressPromise]);
	            case 35:
	              _context.next = 29;
	              break;
	            case 37:
	              _context.next = 42;
	              break;
	            case 39:
	              _context.prev = 39;
	              _context.t0 = _context["catch"](27);
	              _iterator.e(_context.t0);
	            case 42:
	              _context.prev = 42;
	              _iterator.f();
	              return _context.finish(42);
	            case 45:
	              return _context.abrupt("return", true);
	            case 48:
	              _context.prev = 48;
	              _context.t1 = _context["catch"](1);
	              console.error(_context.t1);
	              _classPrivateMethodGet$1(this, _hideProgressOverlay, _hideProgressOverlay2).call(this);
	              this.emit('enableBack');
	              return _context.abrupt("return", false);
	            case 54:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, this, [[1, 48], [27, 39, 42, 45]]);
	      }));
	      function sendForSign() {
	        return _sendForSign.apply(this, arguments);
	      }
	      return sendForSign;
	    }()
	  }, {
	    key: "deleteDocument",
	    value: function deleteDocument(uid) {
	      babelHelpers.classPrivateFieldGet(this, _documentSummary).deleteItem(uid);
	    }
	  }, {
	    key: "setDocumentsBlock",
	    value: function setDocumentsBlock(documents) {
	      var documentsObject = Object.fromEntries(documents);
	      babelHelpers.classPrivateFieldGet(this, _documentSummary).setItems(documentsObject);
	    }
	  }, {
	    key: "isDateTimeLimitSelectorValid",
	    value: function isDateTimeLimitSelectorValid() {
	      return babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector) ? babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector).isValid() : true;
	    }
	  }, {
	    key: "documentData",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _documentData);
	    },
	    set: function set(documentData) {
	      var _this4 = this,
	        _babelHelpers$classPr,
	        _babelHelpers$classPr2;
	      documentData.forEach(function (data) {
	        var uid = data.uid,
	          id = data.id,
	          title = data.title,
	          blocks = data.blocks,
	          externalId = data.externalId,
	          isTemplate = data.isTemplate,
	          entityId = data.entityId,
	          urls = data.urls;
	        babelHelpers.classPrivateFieldGet(_this4, _documentSummary).addItem(uid, {
	          uid: uid,
	          id: id,
	          title: title,
	          blocks: blocks,
	          externalId: externalId,
	          isTemplate: isTemplate,
	          entityId: entityId,
	          urls: urls
	        });
	      });
	      babelHelpers.classPrivateFieldSet(this, _documentData, documentData);
	      var uids = babelHelpers.toConsumableArray(documentData.values()).map(function (data) {
	        return data.uid;
	      });
	      var lastUid = babelHelpers.toConsumableArray(documentData.values()).pop().uid;
	      var documentGroupUids = babelHelpers.toConsumableArray(documentData.keys());
	      babelHelpers.classPrivateFieldGet(this, _langSelector).setDocumentUids(uids);
	      if (!main_core.Type.isNull(babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector))) {
	        var _documentData$values$;
	        babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector).setDocumentUids(uids);
	        var untilDate = documentData === null || documentData === void 0 ? void 0 : (_documentData$values$ = documentData.values().next().value) === null || _documentData$values$ === void 0 ? void 0 : _documentData$values$.dateSignUntilUserTime;
	        if (untilDate) {
	          babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector).setDate(new Date(untilDate));
	        }
	      }
	      babelHelpers.classPrivateFieldGet(this, _items).employees.setDocumentUid(lastUid);
	      babelHelpers.classPrivateFieldGet(this, _items).reviewers.setDocumentUid(lastUid);
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _hcmLinkPartyChecker)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.setDocumentGroupUids(documentGroupUids);
	      void ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _hcmLinkPartyChecker)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.check());
	    }
	  }, {
	    key: "hcmLinkEnabled",
	    set: function set(value) {
	      var _babelHelpers$classPr3;
	      (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _hcmLinkPartyChecker)) === null || _babelHelpers$classPr3 === void 0 ? void 0 : _babelHelpers$classPr3.setEnabled(value);
	    }
	  }]);
	  return DocumentSend;
	}(main_core_events.EventEmitter);
	function _getProgressAnimateLayout2() {
	  var createDocumentOverlapLayout = function createDocumentOverlapLayout(docsCount) {
	    return main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-b2e-overlay__overlap-docs\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), Array.from({
	      length: docsCount
	    }).map(function () {
	      return main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<div class=\"sign-b2e-overlay__overlap-doc\"></div>\n\t\t\t\t\t\t"])));
	    }));
	  };
	  return main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-overlay__animate-layout\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), createDocumentOverlapLayout(4), createDocumentOverlapLayout(3));
	}
	function _getPartyLayout2(header, hint, layout) {
	  return main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-settings__item\">\n\t\t\t\t<p class=\"sign-b2e-settings__item_title\">\n\t\t\t\t\t", "\n\t\t\t\t</p>\n\t\t\t\t<div class=\"sign-b2e-send__party_item\">\n\t\t\t\t\t<p class=\"sign-b2e-send__company-items_item-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</p>\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), header, hint, layout, _classPrivateMethodGet$1(this, _getCommunicationsLayout, _getCommunicationsLayout2).call(this, 'validation'));
	}
	function _renderLangAndDateContainer2() {
	  var _babelHelpers$classPr4;
	  var showSignUntil = !main_core.Type.isNull(babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector));
	  var dateSignUntilLayout = showSignUntil ? _classPrivateMethodGet$1(this, _renderDateSignUntilSelectorLayout, _renderDateSignUntilSelectorLayout2).call(this) : null;
	  var goskeySignUntilNotice = showSignUntil && ((_babelHelpers$classPr4 = babelHelpers.classPrivateFieldGet(this, _provider)) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4.code) === sign_type.ProviderCode.goskey ? _classPrivateMethodGet$1(this, _renderGoskeyAlertForSignUntilSelector, _renderGoskeyAlertForSignUntilSelector2).call(this) : null;
	  return main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-send__lang-dt-container\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$1(this, _renderLangSelectorLayout, _renderLangSelectorLayout2).call(this), dateSignUntilLayout, goskeySignUntilNotice);
	}
	function _renderLangSelectorLayout2() {
	  return main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-send__lang-selector\">\n\t\t\t\t", "\n\t\t\t\t<span\n\t\t\t\t\tdata-hint=\"", "\"\n\t\t\t\t></span>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _langSelector).getLayout(), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_LANG_SELECTOR_HINT'));
	}
	function _renderDateSignUntilSelectorLayout2() {
	  return main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-send__datetime-limit-selector\">\n\t\t\t\t", "\n\t\t\t\t<span\n\t\t\t\t\tdata-hint=\"", "\"\n\t\t\t\t></span>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _dateTimeLimitSelector).getLayout(), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_DATETIME_LIMIT_SELECTOR_HINT'));
	}
	function _renderGoskeyAlertForSignUntilSelector2() {
	  return main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<p class=\"sign-wizard__notice\">\n\t\t\t\t", "\n\t\t\t</p>\n\t\t"])), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_DATETIME_LIMIT_SELECTOR_GOSKEY_ALERT'));
	}
	function _getProgressOverlay2() {
	  var closeDescriptionText = main_core.Loc.getMessage('SIGN_SEND_CLOSE_DESCRIPTION');
	  return main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"send-b2e-overlay\">\n\t\t\t\t<div class=\"sign-b2e-overlay-content\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"sign-b2e-overlay-progress-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"sign-b2e-overlay-close-description\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$1(this, _getProgressAnimateLayout, _getProgressAnimateLayout2).call(this), main_core.Loc.getMessage('SIGN_SEND_PROGRESS_TITLE'), closeDescriptionText, _classPrivateMethodGet$1(this, _getCloseBtn, _getCloseBtn2).call(this), babelHelpers.classPrivateFieldGet(this, _progressContainer));
	}
	function _getTemplateProgressOverlay2() {
	  var templateTitle = babelHelpers.classPrivateFieldGet(this, _isExistingTemplate) ? main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATE_CHANGED') : main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATE_CREATED');
	  return main_core.Tag.render(_templateObject17 || (_templateObject17 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-template-status\">\n\t\t\t\t<div class=\"sign-b2e-template-status-inner\">\n\t\t\t\t\t<div class=\"sign-b2e-template-status-img\"></div>\n\t\t\t\t\t<div class=\"sign-b2e-template-status-title\">", "</div>\n\t\t\t\t\t<div class=\"sign-b2e-template-status-info\">", "</div>\n\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t </div>\n\t\t"])), templateTitle, main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATE_CREATED_INFO'), _classPrivateMethodGet$1(this, _getAllTemplatesBtn, _getAllTemplatesBtn2).call(this));
	}
	function _getCloseBtn2() {
	  var _this5 = this;
	  return main_core.Tag.render(_templateObject18 || (_templateObject18 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button\n\t\t\t\tclass=\"ui-btn ui-btn-light-border ui-btn-round\"\n\t\t\t\tonclick=\"", "\">\n\t\t\t\t", "\n\t\t\t</button>\n\t\t"])), function () {
	    return _this5.emit('close');
	  }, main_core.Loc.getMessage('SIGN_SEND_CLOSE_BTN'));
	}
	function _getAllTemplatesBtn2() {
	  if (babelHelpers.classPrivateFieldGet(this, _isOpenedFromRobot)) {
	    return null;
	  }
	  return main_core.Tag.render(_templateObject19 || (_templateObject19 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-btn ui-btn-light-border ui-btn-round\" onclick=\"BX.SidePanel.Instance.close();\">\n\t\t\t\t", "\n\t\t\t</button>\n\t\t"])), main_core.Loc.getMessage('SIGN_SETTINGS_TEMPLATES_LIST'));
	}
	function _checkFillAndStartProgress2(_x) {
	  return _checkFillAndStartProgress3.apply(this, arguments);
	}
	function _checkFillAndStartProgress3() {
	  _checkFillAndStartProgress3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee2(uid) {
	    var api, completed, result;
	    return _regeneratorRuntime$1().wrap(function _callee2$(_context2) {
	      while (1) switch (_context2.prev = _context2.next) {
	        case 0:
	          api = new sign_v2_api.Api();
	          completed = false;
	        case 2:
	          if (completed) {
	            _context2.next = 12;
	            break;
	          }
	          _context2.next = 5;
	          return api.getDocumentFillAndStartProgress(uid);
	        case 5:
	          result = _context2.sent;
	          completed = result.completed;
	          babelHelpers.classPrivateFieldGet(this, _progress).update(Math.round(result.progress));
	          // eslint-disable-next-line no-await-in-loop
	          _context2.next = 10;
	          return _classPrivateMethodGet$1(this, _sleep, _sleep2).call(this, 1000);
	        case 10:
	          _context2.next = 2;
	          break;
	        case 12:
	        case "end":
	          return _context2.stop();
	      }
	    }, _callee2, this);
	  }));
	  return _checkFillAndStartProgress3.apply(this, arguments);
	}
	function _groupCheckFillAndStartProgress2(_x2) {
	  return _groupCheckFillAndStartProgress3.apply(this, arguments);
	}
	function _groupCheckFillAndStartProgress3() {
	  _groupCheckFillAndStartProgress3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee3(groupId) {
	    var api, completed, result;
	    return _regeneratorRuntime$1().wrap(function _callee3$(_context3) {
	      while (1) switch (_context3.prev = _context3.next) {
	        case 0:
	          api = new sign_v2_api.Api();
	          completed = false;
	        case 2:
	          if (completed) {
	            _context3.next = 12;
	            break;
	          }
	          _context3.next = 5;
	          return api.getDocumentGroupFillAndStartProgress(groupId);
	        case 5:
	          result = _context3.sent;
	          completed = result.completed;
	          babelHelpers.classPrivateFieldGet(this, _progress).update(Math.round(result.progress));
	          // eslint-disable-next-line no-await-in-loop
	          _context3.next = 10;
	          return _classPrivateMethodGet$1(this, _sleep, _sleep2).call(this, 2000);
	        case 10:
	          _context3.next = 2;
	          break;
	        case 12:
	        case "end":
	          return _context3.stop();
	      }
	    }, _callee3, this);
	  }));
	  return _groupCheckFillAndStartProgress3.apply(this, arguments);
	}
	function _sleep2(ms) {
	  return new Promise(function (resolve) {
	    setTimeout(resolve, ms);
	  });
	}
	function _refreshView2$1() {
	  var _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7;
	  babelHelpers.classPrivateFieldGet(this, _ui$1).employeesTitle.innerText = main_core.Loc.getMessage('SIGN_SEND_SIGNING_EMPLOYEES', {
	    '#CNT#': (_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldGet(this, _partiesData)) === null || _babelHelpers$classPr6 === void 0 ? void 0 : (_babelHelpers$classPr7 = _babelHelpers$classPr6.employees) === null || _babelHelpers$classPr7 === void 0 ? void 0 : _babelHelpers$classPr7.length) !== null && _babelHelpers$classPr5 !== void 0 ? _babelHelpers$classPr5 : 0
	  });
	}
	function _getCommunicationsLayout2(communicationChannelId) {
	  return main_core.Tag.render(_templateObject20 || (_templateObject20 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-send__communications\">\n\t\t\t\t<span class=\"sign-b2e-send__communications_title\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t\t<div class=\"sign-b2e-send__communications_communication-type\">\n\t\t\t\t\t<span class=\"sign-b2e-send__communications_communication-type-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t<span\n\t\t\t\t\t\tdata-hint=\"", "\"\n\t\t\t\t\t></span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_COMMUNICATION_TITLE_MSGVER_1'), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_COMMUNICATION_CHANEL_IDLE'), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_COMMUNICATION_CHANEL_HINT'));
	}
	function _showProgressOverlay2() {
	  babelHelpers.classPrivateFieldGet(this, _progress).update(0);
	  this.emit('hidePreview');
	  babelHelpers.classPrivateFieldGet(this, _itemsToHide).forEach(function (item) {
	    return main_core.Dom.hide(item);
	  });
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _progressOverlay), 'display', 'flex');
	  this.emit('showOverlay');
	}
	function _hideProgressOverlay2() {
	  babelHelpers.classPrivateFieldGet(this, _itemsToHide).forEach(function (item) {
	    return main_core.Dom.show(item);
	  });
	  this.emit('hideOverlay');
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _progressOverlay), 'display', 'none');
	  this.emit('showPreview');
	}
	function _getReminderSelectorLayout2(role) {
	  return main_core.Tag.render(_templateObject21 || (_templateObject21 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-b2e-send__reminder-selector\">\n\t\t\t\t", "\n\t\t\t\t<span\n\t\t\t\t\tdata-hint=\"", "\"\n\t\t\t\t></span>\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$1(this, _getOrCreateReminderSelectorForRole, _getOrCreateReminderSelectorForRole2).call(this, role).getLayout(), main_core.Loc.getMessage('SIGN_DOCUMENT_SEND_REMINDER_TYPE_SELECTOR_HINT'));
	}
	function _getOrCreateReminderSelectorForRole2(role) {
	  var _babelHelpers$classPr8, _babelHelpers$classPr9, _ReminderSelectorOpti2;
	  (_babelHelpers$classPr9 = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldGet(this, _reminderSelectorByRole))[role]) !== null && _babelHelpers$classPr9 !== void 0 ? _babelHelpers$classPr9 : _babelHelpers$classPr8[role] = new sign_v2_b2e_reminderSelector.ReminderSelector((_ReminderSelectorOpti2 = ReminderSelectorOptionsByRole[role]) !== null && _ReminderSelectorOpti2 !== void 0 ? _ReminderSelectorOpti2 : {});
	  return babelHelpers.classPrivateFieldGet(this, _reminderSelectorByRole)[role];
	}
	function _saveReminderTypesForRoles2() {
	  var uid = _classPrivateMethodGet$1(this, _getFirstDocumentUidFromGroup, _getFirstDocumentUidFromGroup2).call(this);
	  var promises = Object.entries(babelHelpers.classPrivateFieldGet(this, _reminderSelectorByRole)).map(function (_ref2) {
	    var _ref3 = babelHelpers.slicedToArray(_ref2, 2),
	      role = _ref3[0],
	      selector = _ref3[1];
	    return selector.save(uid, role);
	  });
	  return Promise.all(promises);
	}
	function _getFirstDocumentUidFromGroup2() {
	  return this.documentData.keys().next().value;
	}
	function _isTemplateMode2() {
	  return sign_v2_signSettings.isTemplateMode(babelHelpers.classPrivateFieldGet(this, _documentMode));
	}
	function _isGroupDocuments2() {
	  return this.documentData.size > 1;
	}
	function _getGroupIdFromGroup2() {
	  return this.documentData.values().next().value.groupId;
	}
	function _onHcmLinkCheckerUpdateValidation2(event) {
	  var _event$data;
	  var enableComplete = (_event$data = event === null || event === void 0 ? void 0 : event.data) !== null && _event$data !== void 0 ? _event$data : false;
	  this.emit(enableComplete ? 'enableComplete' : 'disableComplete');
	}

	exports.DocumentSend = DocumentSend;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Event,BX.Main,BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign,BX,BX,BX.UI.EntitySelector,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.UI,BX.Sign.V2.B2e));
//# sourceMappingURL=document-send.bundle.js.map
