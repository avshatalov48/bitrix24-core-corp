/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,sign_v2_api,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var buttonClassList = ['ui-btn', 'ui-btn-sm', 'ui-btn-light-border', 'ui-btn-round'];
	var _createDocumentDetails = /*#__PURE__*/new WeakSet();
	var _createNumber = /*#__PURE__*/new WeakSet();
	var _createTitleEditor = /*#__PURE__*/new WeakSet();
	var _toggleTitleEditor = /*#__PURE__*/new WeakSet();
	var _focusInput = /*#__PURE__*/new WeakSet();
	var _modifyDocumentTitle = /*#__PURE__*/new WeakSet();
	var _createEditDocumentBtn = /*#__PURE__*/new WeakSet();
	var DocumentSummary = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(DocumentSummary, _EventEmitter);
	  function DocumentSummary() {
	    var _this;
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, DocumentSummary);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DocumentSummary).call(this));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _createEditDocumentBtn);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _modifyDocumentTitle);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _focusInput);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _toggleTitleEditor);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _createTitleEditor);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _createNumber);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _createDocumentDetails);
	    _this.setEventNamespace('BX.Sign.V2.DocumentSummary');
	    _this.subscribeFromOptions(options.events);
	    return _this;
	  }
	  babelHelpers.createClass(DocumentSummary, [{
	    key: "addItem",
	    value: function addItem(uid, itemDetails) {
	      if (!this.items) {
	        this.items = {};
	      }
	      this.items[uid] = itemDetails;
	    }
	  }, {
	    key: "deleteItem",
	    value: function deleteItem(uid) {
	      if (!this.items || !this.items[uid]) {
	        return;
	      }
	      delete this.items[uid];
	    }
	  }, {
	    key: "setItems",
	    value: function setItems(documentObject) {
	      var _this2 = this;
	      this.items = {};
	      Object.keys(documentObject).forEach(function (uid) {
	        _this2.items[uid] = documentObject[uid];
	      });
	    }
	  }, {
	    key: "setNumber",
	    value: function setNumber(uid, number) {
	      this.items[uid].number = number;
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      var container = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document-summary-wrapper\"></div>\n\t\t"])));
	      for (var _i = 0, _Object$values = Object.values(this.items); _i < _Object$values.length; _i++) {
	        var item = _Object$values[_i];
	        var itemBlock = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-document-summary\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _classPrivateMethodGet(this, _createDocumentDetails, _createDocumentDetails2).call(this, item), _classPrivateMethodGet(this, _createEditDocumentBtn, _createEditDocumentBtn2).call(this, item.id, item.uid));
	        main_core.Dom.append(itemBlock, container);
	      }
	      return container;
	    }
	  }]);
	  return DocumentSummary;
	}(main_core_events.EventEmitter);
	function _createDocumentDetails2(item) {
	  var _item$title,
	    _this3 = this;
	  var title = main_core.Text.encode((_item$title = item.title) !== null && _item$title !== void 0 ? _item$title : '');
	  return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document-summary__details\">\n\t\t\t\t<div class=\"sign-document-summary__details_title\">\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"sign-document-summary__details_title-text\"\n\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"sign-document-summary__details_edit-title-btn\"\n\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), title, title, function (_ref) {
	    var button = _ref.target;
	    _classPrivateMethodGet(_this3, _toggleTitleEditor, _toggleTitleEditor2).call(_this3, item, button, true);
	  }, _classPrivateMethodGet(this, _createNumber, _createNumber2).call(this, item.externalId));
	}
	function _createNumber2(number) {
	  if (!number) {
	    return null;
	  }
	  var title = main_core.Loc.getMessage('SIGN_DOCUMENT_SUMMARY_REG_NUMBER', {
	    '#NUMBER#': main_core.Text.encode(number)
	  });
	  return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document-summary__reg_number\" title=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), title, title);
	}
	function _createTitleEditor2(item) {
	  var _item$title2,
	    _this4 = this;
	  var okButtonClassName = [].concat(babelHelpers.toConsumableArray(buttonClassList.slice(0, 2)), ['ui-btn-primary', 'sign-document-summary__title-editor_ok-btn']).join(' ');
	  var discardButtonClassName = [].concat(babelHelpers.toConsumableArray(buttonClassList.slice(0, 3)), ['sign-document-summary__title-editor_discard-btn']).join(' ');
	  var input = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<input type=\"text\" class=\"ui-ctl-element\" maxlength=\"255\" />"])));
	  input.value = (_item$title2 = item.title) !== null && _item$title2 !== void 0 ? _item$title2 : '';
	  _classPrivateMethodGet(this, _focusInput, _focusInput2).call(this, input);
	  return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-document-summary__title-editor\">\n\t\t\t\t<div class=\"sign-document-summary__title-editor_controls\">\n\t\t\t\t\t<span class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"", "\"\n\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t\t</span>\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"", "\"\n\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<p class=\"sign-document-summary__title-editor_help\">\n\t\t\t\t\t", "\n\t\t\t\t</p>\n\t\t\t</div>\n\t\t"])), input, okButtonClassName, /*#__PURE__*/function () {
	    var _ref3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(_ref2) {
	      var target;
	      return _regeneratorRuntime().wrap(function _callee$(_context) {
	        while (1) switch (_context.prev = _context.next) {
	          case 0:
	            target = _ref2.target;
	            main_core.Dom.addClass(target, 'ui-btn-wait');
	            _context.next = 4;
	            return _classPrivateMethodGet(_this4, _modifyDocumentTitle, _modifyDocumentTitle2).call(_this4, item, input.value);
	          case 4:
	            main_core.Dom.removeClass(target, 'ui-btn-wait');
	            _classPrivateMethodGet(_this4, _toggleTitleEditor, _toggleTitleEditor2).call(_this4, item, target, false);
	          case 6:
	          case "end":
	            return _context.stop();
	        }
	      }, _callee);
	    }));
	    return function (_x) {
	      return _ref3.apply(this, arguments);
	    };
	  }(), discardButtonClassName, function (_ref4) {
	    var target = _ref4.target;
	    _classPrivateMethodGet(_this4, _toggleTitleEditor, _toggleTitleEditor2).call(_this4, item, target, false);
	  }, main_core.Loc.getMessage('SIGN_DOCUMENT_SUMMARY_TITLE_EDITOR_HELP'));
	}
	function _toggleTitleEditor2(item, button, shouldShow) {
	  var summaryNode = button.closest('.sign-document-summary');
	  if (shouldShow) {
	    main_core.Dom.clean(summaryNode);
	    main_core.Dom.append(_classPrivateMethodGet(this, _createTitleEditor, _createTitleEditor2).call(this, item), summaryNode);
	    return;
	  }
	  main_core.Dom.replace(summaryNode.firstElementChild, _classPrivateMethodGet(this, _createDocumentDetails, _createDocumentDetails2).call(this, item));
	  main_core.Dom.append(_classPrivateMethodGet(this, _createEditDocumentBtn, _createEditDocumentBtn2).call(this, item.id, item.uid), summaryNode);
	}
	function _focusInput2(input) {
	  var observer = new MutationObserver(function () {
	    if (input.isConnected) {
	      input.focus();
	      observer.disconnect();
	    }
	  });
	  observer.observe(document.body, {
	    childList: true,
	    subtree: true
	  });
	}
	function _modifyDocumentTitle2(_x2, _x3) {
	  return _modifyDocumentTitle3.apply(this, arguments);
	}
	function _modifyDocumentTitle3() {
	  _modifyDocumentTitle3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2(item, newValue) {
	    var api, titleData;
	    return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	      while (1) switch (_context2.prev = _context2.next) {
	        case 0:
	          if (!(item.title === newValue)) {
	            _context2.next = 2;
	            break;
	          }
	          return _context2.abrupt("return");
	        case 2:
	          _context2.prev = 2;
	          api = new sign_v2_api.Api();
	          _context2.next = 6;
	          return api.modifyTitle(item.uid, newValue);
	        case 6:
	          titleData = _context2.sent;
	          this.items[item.uid].title = newValue;
	          this.emit('changeTitle', {
	            uid: item.uid,
	            title: newValue,
	            blankTitle: titleData.blankTitle
	          });
	          _context2.next = 14;
	          break;
	        case 11:
	          _context2.prev = 11;
	          _context2.t0 = _context2["catch"](2);
	          console.error(_context2.t0);
	        case 14:
	        case "end":
	          return _context2.stop();
	      }
	    }, _callee2, this, [[2, 11]]);
	  }));
	  return _modifyDocumentTitle3.apply(this, arguments);
	}
	function _createEditDocumentBtn2(id, uid) {
	  var _this5 = this;
	  return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span\n\t\t\t\tclass=\"", "\" data-id=\"", "\"\n\t\t\t\tonclick=\"", "\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), buttonClassList.join(' '), id, function () {
	    return _this5.emit('showEditor', {
	      uid: uid
	    });
	  }, main_core.Loc.getMessage('SIGN_DOCUMENT_SUMMARY_EDIT'));
	}

	exports.DocumentSummary = DocumentSummary;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX.Sign.V2,BX.Event));
//# sourceMappingURL=document-summary.bundle.js.map
