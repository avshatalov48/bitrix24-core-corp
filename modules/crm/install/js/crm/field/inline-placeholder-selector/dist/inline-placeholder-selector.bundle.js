/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_entitySelector) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var InlinePlaceholderSelectorMode = {
	  INPUT: 'input',
	  TEXTAREA: 'textarea'
	};
	var _mode = /*#__PURE__*/new WeakMap();
	var _value = /*#__PURE__*/new WeakMap();
	var _target = /*#__PURE__*/new WeakMap();
	var _multiple = /*#__PURE__*/new WeakMap();
	var _menuButton = /*#__PURE__*/new WeakMap();
	var _dialog = /*#__PURE__*/new WeakMap();
	var _inputElement = /*#__PURE__*/new WeakMap();
	var _entityTypeIds = /*#__PURE__*/new WeakMap();
	var _onBeforeMenuOpen = /*#__PURE__*/new WeakMap();
	var _getDialog = /*#__PURE__*/new WeakSet();
	var _render = /*#__PURE__*/new WeakSet();
	var _renderFormElement = /*#__PURE__*/new WeakSet();
	var _openMenu = /*#__PURE__*/new WeakSet();
	var _onSelect = /*#__PURE__*/new WeakSet();
	var InlinePlaceholderSelector = /*#__PURE__*/function () {
	  function InlinePlaceholderSelector(params) {
	    var _params$mode, _params$value, _params$multiple;
	    babelHelpers.classCallCheck(this, InlinePlaceholderSelector);
	    _classPrivateMethodInitSpec(this, _onSelect);
	    _classPrivateMethodInitSpec(this, _openMenu);
	    _classPrivateMethodInitSpec(this, _renderFormElement);
	    _classPrivateMethodInitSpec(this, _render);
	    _classPrivateMethodInitSpec(this, _getDialog);
	    _classPrivateFieldInitSpec(this, _mode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _value, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _target, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _multiple, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _menuButton, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _inputElement, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _entityTypeIds, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _onBeforeMenuOpen, {
	      writable: true,
	      value: void 0
	    });
	    if (!main_core.Type.isDomNode(params.target)) {
	      throw new Error('Target DOM node not found');
	    }
	    var entityTypeIds = main_core.Type.isArrayFilled(params.entityTypeIds) ? params.entityTypeIds : [];
	    entityTypeIds = entityTypeIds.filter(function (entityTypeId) {
	      return BX.CrmEntityType.isDefined(entityTypeId);
	    });
	    babelHelpers.classPrivateFieldSet(this, _target, params.target);
	    babelHelpers.classPrivateFieldSet(this, _entityTypeIds, entityTypeIds);
	    babelHelpers.classPrivateFieldSet(this, _mode, (_params$mode = params.mode) !== null && _params$mode !== void 0 ? _params$mode : InlinePlaceholderSelectorMode.INPUT);
	    babelHelpers.classPrivateFieldSet(this, _value, (_params$value = params.value) !== null && _params$value !== void 0 ? _params$value : '');
	    babelHelpers.classPrivateFieldSet(this, _multiple, (_params$multiple = params.multiple) !== null && _params$multiple !== void 0 ? _params$multiple : false);
	    babelHelpers.classPrivateFieldSet(this, _onBeforeMenuOpen, main_core.Type.isFunction(params.onBeforeMenuOpen) ? params.onBeforeMenuOpen : null);
	  }
	  babelHelpers.createClass(InlinePlaceholderSelector, [{
	    key: "setEntityTypeIds",
	    value: function setEntityTypeIds(entityTypeIds) {
	      babelHelpers.classPrivateFieldSet(this, _entityTypeIds, entityTypeIds);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      main_core.Dom.append(_classPrivateMethodGet(this, _render, _render2).call(this), babelHelpers.classPrivateFieldGet(this, _target));
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      var _babelHelpers$classPr;
	      return (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _inputElement).value) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : '';
	    }
	  }]);
	  return InlinePlaceholderSelector;
	}();
	function _getDialog2() {
	  var _this = this;
	  if (main_core.Type.isNull(babelHelpers.classPrivateFieldGet(this, _onBeforeMenuOpen)) && babelHelpers.classPrivateFieldGet(this, _dialog)) {
	    return babelHelpers.classPrivateFieldGet(this, _dialog);
	  }
	  var entity = babelHelpers.classPrivateFieldGet(this, _multiple) ? {
	    id: 'multiple_placeholder',
	    dynamicLoad: true,
	    dynamicSearch: false,
	    searchable: true,
	    options: {
	      entityTypeIds: babelHelpers.classPrivateFieldGet(this, _entityTypeIds)
	    }
	  } : {
	    id: 'placeholder',
	    dynamicLoad: true,
	    dynamicSearch: false,
	    searchable: true,
	    options: {
	      entityTypeId: babelHelpers.classPrivateFieldGet(this, _entityTypeIds)[0]
	    }
	  };
	  babelHelpers.classPrivateFieldSet(this, _dialog, new ui_entitySelector.Dialog({
	    targetNode: babelHelpers.classPrivateFieldGet(this, _menuButton),
	    multiple: false,
	    showAvatars: false,
	    dropdownMode: true,
	    compactView: true,
	    enableSearch: true,
	    entities: [entity],
	    events: {
	      'Item:onSelect': function ItemOnSelect(event) {
	        var _event$getData = event.getData(),
	          selectedItem = _event$getData.item;
	        _classPrivateMethodGet(_this, _onSelect, _onSelect2).call(_this, selectedItem);
	      }
	    }
	  }));
	  return babelHelpers.classPrivateFieldGet(this, _dialog);
	}
	function _render2() {
	  babelHelpers.classPrivateFieldSet(this, _menuButton, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span \n\t\t\tonclick=\"", "\"\n\t\t\tclass=\"crm-inline-placeholder-selector-dotted\"\n\t\t\t></span>\n\t\t"])), _classPrivateMethodGet(this, _openMenu, _openMenu2).bind(this)));
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-inline-placeholder-selector\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet(this, _renderFormElement, _renderFormElement2).call(this), babelHelpers.classPrivateFieldGet(this, _menuButton));
	}
	function _renderFormElement2() {
	  if (babelHelpers.classPrivateFieldGet(this, _mode) === InlinePlaceholderSelectorMode.TEXTAREA) {
	    babelHelpers.classPrivateFieldSet(this, _inputElement, main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<textarea class=\"ui-ctl-element\" name=\"subject\"></textarea>"]))));
	    babelHelpers.classPrivateFieldGet(this, _inputElement).value = babelHelpers.classPrivateFieldGet(this, _value);
	    return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-ctl ui-ctl-textarea ui-ctl-no-resize ui-ctl-w100\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _inputElement));
	  }
	  babelHelpers.classPrivateFieldSet(this, _inputElement, main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<input type=\"text\" class=\"ui-ctl-element\" name=\"subject\">"]))));
	  babelHelpers.classPrivateFieldGet(this, _inputElement).value = babelHelpers.classPrivateFieldGet(this, _value);
	  return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _inputElement));
	}
	function _openMenu2() {
	  return _openMenu3.apply(this, arguments);
	}
	function _openMenu3() {
	  _openMenu3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	    return _regeneratorRuntime().wrap(function _callee$(_context) {
	      while (1) switch (_context.prev = _context.next) {
	        case 0:
	          if (!babelHelpers.classPrivateFieldGet(this, _onBeforeMenuOpen)) {
	            _context.next = 3;
	            break;
	          }
	          _context.next = 3;
	          return babelHelpers.classPrivateFieldGet(this, _onBeforeMenuOpen).call(this);
	        case 3:
	          _classPrivateMethodGet(this, _getDialog, _getDialog2).call(this).show();
	        case 4:
	        case "end":
	          return _context.stop();
	      }
	    }, _callee, this);
	  }));
	  return _openMenu3.apply(this, arguments);
	}
	function _onSelect2(selectedItem) {
	  var placeholder = "{".concat(selectedItem.customData.get('text'), "}");
	  var cursorPosition = babelHelpers.classPrivateFieldGet(this, _inputElement).selectionStart;
	  var currentValue = babelHelpers.classPrivateFieldGet(this, _inputElement).value;
	  babelHelpers.classPrivateFieldGet(this, _inputElement).value = currentValue.slice(0, cursorPosition) + placeholder + currentValue.slice(cursorPosition);
	  var newCursorPosition = cursorPosition + placeholder.length;
	  babelHelpers.classPrivateFieldGet(this, _inputElement).setSelectionRange(newCursorPosition, newCursorPosition);
	  babelHelpers.classPrivateFieldGet(this, _inputElement).focus();
	  _classPrivateMethodGet(this, _getDialog, _getDialog2).call(this).deselectAll();
	}

	exports.InlinePlaceholderSelectorMode = InlinePlaceholderSelectorMode;
	exports.InlinePlaceholderSelector = InlinePlaceholderSelector;

}((this.BX.Crm.Field = this.BX.Crm.Field || {}),BX,BX.UI.EntitySelector));
//# sourceMappingURL=inline-placeholder-selector.bundle.js.map
