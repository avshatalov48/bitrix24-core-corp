(function (exports,bizproc_automation,main_core,main_core_events,ui_entitySelector,tasks_entitySelector) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Tasks.Automation.Activity');
	var TagType = {
	  EXPRESSION: 'expression',
	  NEW: 'new',
	  SIMPLE: 'simple'
	};
	var _form = /*#__PURE__*/new WeakMap();
	var _selectedGroupId = /*#__PURE__*/new WeakMap();
	var _groupIdSelector = /*#__PURE__*/new WeakMap();
	var _selectedTags = /*#__PURE__*/new WeakMap();
	var _expressionTags = /*#__PURE__*/new WeakMap();
	var _newTags = /*#__PURE__*/new WeakMap();
	var _tagsSelector = /*#__PURE__*/new WeakMap();
	var _dependsOnSelector = /*#__PURE__*/new WeakMap();
	var _selectedDependentTasks = /*#__PURE__*/new WeakMap();
	var _getGroupIdSelector = /*#__PURE__*/new WeakSet();
	var _recreateTagSelector = /*#__PURE__*/new WeakSet();
	var _loadTagsDialog = /*#__PURE__*/new WeakSet();
	var _getTagsSelector = /*#__PURE__*/new WeakSet();
	var _fillTagsSelector = /*#__PURE__*/new WeakSet();
	var _updateSavedTags = /*#__PURE__*/new WeakSet();
	var _fetchTags = /*#__PURE__*/new WeakSet();
	var _addTag = /*#__PURE__*/new WeakSet();
	var _removeTag = /*#__PURE__*/new WeakSet();
	var _getDependsOnSelector = /*#__PURE__*/new WeakSet();
	var _addDependsOnTaskId = /*#__PURE__*/new WeakSet();
	var _removeDependsOnTaskId = /*#__PURE__*/new WeakSet();
	var Task2Activity = /*#__PURE__*/function () {
	  function Task2Activity(options) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Task2Activity);
	    _classPrivateMethodInitSpec(this, _removeDependsOnTaskId);
	    _classPrivateMethodInitSpec(this, _addDependsOnTaskId);
	    _classPrivateMethodInitSpec(this, _getDependsOnSelector);
	    _classPrivateMethodInitSpec(this, _removeTag);
	    _classPrivateMethodInitSpec(this, _addTag);
	    _classPrivateMethodInitSpec(this, _fetchTags);
	    _classPrivateMethodInitSpec(this, _updateSavedTags);
	    _classPrivateMethodInitSpec(this, _fillTagsSelector);
	    _classPrivateMethodInitSpec(this, _getTagsSelector);
	    _classPrivateMethodInitSpec(this, _loadTagsDialog);
	    _classPrivateMethodInitSpec(this, _recreateTagSelector);
	    _classPrivateMethodInitSpec(this, _getGroupIdSelector);
	    _classPrivateFieldInitSpec(this, _form, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _selectedGroupId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _groupIdSelector, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _selectedTags, {
	      writable: true,
	      value: new Map()
	    });
	    _classPrivateFieldInitSpec(this, _expressionTags, {
	      writable: true,
	      value: new Set()
	    });
	    _classPrivateFieldInitSpec(this, _newTags, {
	      writable: true,
	      value: new Set()
	    });
	    _classPrivateFieldInitSpec(this, _tagsSelector, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _dependsOnSelector, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _selectedDependentTasks, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _form, document.forms.namedItem(options.formName));
	    if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _form)['GROUP_ID'])) {
	      babelHelpers.classPrivateFieldGet(this, _form).appendChild(main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<select name=\"GROUP_ID\" hidden=\"true\"></select>"]))));
	    }
	    babelHelpers.classPrivateFieldSet(this, _selectedGroupId, options.selectedGroupId);
	    options.selectedTags.forEach(function (tag) {
	      if (tag.type === TagType.EXPRESSION) {
	        babelHelpers.classPrivateFieldGet(_this, _expressionTags).add(tag.name);
	      } else if (tag.type === TagType.SIMPLE && main_core.Type.isNumber(tag.id)) {
	        babelHelpers.classPrivateFieldGet(_this, _selectedTags).set(tag.id, tag.name);
	      } else if (tag.type === TagType.NEW) {
	        babelHelpers.classPrivateFieldGet(_this, _newTags).add(tag.name);
	      }
	    });
	    babelHelpers.classPrivateFieldSet(this, _selectedDependentTasks, new Set(options.dependsOn));
	  }
	  babelHelpers.createClass(Task2Activity, [{
	    key: "render",
	    value: function render() {
	      this.watchAllowTimeTracking();
	      this.renderGroupId();
	      this.renderTags();
	      this.renderDependsOn();
	    }
	  }, {
	    key: "watchAllowTimeTracking",
	    value: function watchAllowTimeTracking() {
	      var allowTrackingElement = babelHelpers.classPrivateFieldGet(this, _form)['ALLOW_TIME_TRACKING'][1];
	      var timeEstimateElements = [babelHelpers.classPrivateFieldGet(this, _form)['TIME_ESTIMATE_H'], babelHelpers.classPrivateFieldGet(this, _form)['TIME_ESTIMATE_M']];
	      var timeEstimateFieldElements = timeEstimateElements.map(function (element) {
	        return element.parentElement.parentElement;
	      });
	      if (allowTrackingElement) {
	        var manageTimeEstimateFields = function manageTimeEstimateFields() {
	          if (allowTrackingElement.checked) {
	            timeEstimateFieldElements.forEach(function (element) {
	              element.style.display = '';
	            });
	          } else {
	            timeEstimateFieldElements.forEach(function (element) {
	              element.style.display = 'none';
	            });
	          }
	        };
	        manageTimeEstimateFields();
	        allowTrackingElement.onchange = manageTimeEstimateFields;
	      }
	    }
	  }, {
	    key: "renderGroupId",
	    value: function renderGroupId() {
	      var _this2 = this;
	      var groupIdElement = document.getElementById('bizproc-task2activity-group-id');
	      if (groupIdElement) {
	        var selector = bizproc_automation.SelectorManager.getSelectorByTarget(groupIdElement);
	        if (selector) {
	          selector.subscribe('Field:Selected', function (event) {
	            var _event$getData = event.getData(),
	              field = _event$getData.field;
	            babelHelpers.classPrivateFieldSet(_this2, _selectedGroupId, field.Expression);
	            _classPrivateMethodGet(_this2, _getGroupIdSelector, _getGroupIdSelector2).call(_this2).addTag({
	              id: field.Expression,
	              title: field.Expression,
	              entityId: 'project'
	            });
	            _classPrivateMethodGet(_this2, _recreateTagSelector, _recreateTagSelector2).call(_this2);
	          });
	        }
	        _classPrivateMethodGet(this, _getGroupIdSelector, _getGroupIdSelector2).call(this).renderTo(groupIdElement);
	      }
	    }
	  }, {
	    key: "renderTags",
	    value: function renderTags() {
	      var _this3 = this;
	      var tagsElement = document.getElementById('bizproc-task2activity-tags');
	      if (tagsElement) {
	        var selector = bizproc_automation.SelectorManager.getSelectorByTarget(tagsElement);
	        if (selector) {
	          selector.subscribe('Field:Selected', function (event) {
	            var _event$getData2 = event.getData(),
	              field = _event$getData2.field;
	            _classPrivateMethodGet(_this3, _getTagsSelector, _getTagsSelector2).call(_this3).addTag({
	              id: field.Expression,
	              title: field.Expression,
	              entityId: 'task-tag',
	              customData: {
	                type: TagType.EXPRESSION
	              }
	            });
	          });
	        }
	        _classPrivateMethodGet(this, _getTagsSelector, _getTagsSelector2).call(this).renderTo(tagsElement);
	      }
	    }
	  }, {
	    key: "renderDependsOn",
	    value: function renderDependsOn() {
	      var _this4 = this;
	      var dependsOnElement = document.getElementById('bizproc-task2activity-depends-on');
	      if (dependsOnElement) {
	        var selector = bizproc_automation.SelectorManager.getSelectorByTarget(dependsOnElement);
	        if (selector) {
	          selector.subscribe('Field:Selected', function (event) {
	            var _event$getData3 = event.getData(),
	              field = _event$getData3.field;
	            _classPrivateMethodGet(_this4, _getDependsOnSelector, _getDependsOnSelector2).call(_this4).addTag({
	              id: field.Expression,
	              title: field.Expression,
	              entityId: 'task'
	            });
	          });
	        }
	        _classPrivateMethodGet(this, _getDependsOnSelector, _getDependsOnSelector2).call(this).renderTo(dependsOnElement);
	      }
	    }
	  }]);
	  return Task2Activity;
	}();
	function _getGroupIdSelector2() {
	  if (main_core.Type.isNil(babelHelpers.classPrivateFieldGet(this, _groupIdSelector))) {
	    var self = this;
	    babelHelpers.classPrivateFieldSet(this, _groupIdSelector, new ui_entitySelector.TagSelector({
	      multiple: false,
	      events: {
	        onTagAdd: function onTagAdd(event) {
	          var _event$getData4 = event.getData(),
	            tag = _event$getData4.tag;
	          main_core.Dom.clean(babelHelpers.classPrivateFieldGet(self, _form)['GROUP_ID']);
	          babelHelpers.classPrivateFieldSet(self, _selectedGroupId, tag.id);
	          babelHelpers.classPrivateFieldGet(self, _form)['GROUP_ID'].append(new Option(tag.getTitle(), tag.getId(), true, true));
	          _classPrivateMethodGet(self, _recreateTagSelector, _recreateTagSelector2).call(self);
	        },
	        onTagRemove: function onTagRemove(event) {
	          main_core.Dom.clean(babelHelpers.classPrivateFieldGet(self, _form)['GROUP_ID']);
	          babelHelpers.classPrivateFieldSet(self, _selectedGroupId, undefined);
	          _classPrivateMethodGet(self, _recreateTagSelector, _recreateTagSelector2).call(self);
	        }
	      },
	      dialogOptions: {
	        preselectedItems: main_core.Type.isNumber(babelHelpers.classPrivateFieldGet(this, _selectedGroupId)) ? [['project', babelHelpers.classPrivateFieldGet(this, _selectedGroupId)]] : undefined,
	        entities: [{
	          id: 'project'
	        }]
	      }
	    }));
	    if (main_core.Type.isString(babelHelpers.classPrivateFieldGet(this, _selectedGroupId))) {
	      babelHelpers.classPrivateFieldGet(this, _groupIdSelector).addTag({
	        id: babelHelpers.classPrivateFieldGet(this, _selectedGroupId),
	        entityId: 'project',
	        title: babelHelpers.classPrivateFieldGet(this, _selectedGroupId)
	      });
	    }
	  }
	  return babelHelpers.classPrivateFieldGet(this, _groupIdSelector);
	}
	function _recreateTagSelector2() {
	  return _recreateTagSelector3.apply(this, arguments);
	}
	function _recreateTagSelector3() {
	  _recreateTagSelector3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	    return _regeneratorRuntime().wrap(function _callee$(_context) {
	      while (1) switch (_context.prev = _context.next) {
	        case 0:
	          _context.next = 2;
	          return _classPrivateMethodGet(this, _loadTagsDialog, _loadTagsDialog2).call(this);
	        case 2:
	          if (main_core.Type.isNil(babelHelpers.classPrivateFieldGet(this, _tagsSelector))) {
	            _context.next = 8;
	            break;
	          }
	          _context.next = 5;
	          return new Promise(function (resolve, reject) {
	            return setTimeout(400, resolve);
	          });
	        case 5:
	          babelHelpers.classPrivateFieldGet(this, _tagsSelector).getDialog().destroy();
	          main_core.Dom.remove(babelHelpers.classPrivateFieldGet(this, _tagsSelector).getOuterContainer());
	          babelHelpers.classPrivateFieldSet(this, _tagsSelector, null);
	        case 8:
	          babelHelpers.classPrivateFieldGet(this, _form).querySelectorAll("input[name=\"TAG_NAMES[]\"]").forEach(function (element) {
	            return main_core.Dom.remove(element);
	          });
	          this.renderTags();
	        case 10:
	        case "end":
	          return _context.stop();
	      }
	    }, _callee, this);
	  }));
	  return _recreateTagSelector3.apply(this, arguments);
	}
	function _loadTagsDialog2() {
	  return _loadTagsDialog3.apply(this, arguments);
	}
	function _loadTagsDialog3() {
	  _loadTagsDialog3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
	    return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	      while (1) switch (_context2.prev = _context2.next) {
	        case 0:
	          if (!babelHelpers.classPrivateFieldGet(this, _tagsSelector).getDialog().isLoading()) {
	            _context2.next = 3;
	            break;
	          }
	          _context2.next = 3;
	          return _classPrivateMethodGet(this, _fetchTags, _fetchTags2).call(this);
	        case 3:
	        case "end":
	          return _context2.stop();
	      }
	    }, _callee2, this);
	  }));
	  return _loadTagsDialog3.apply(this, arguments);
	}
	function _getTagsSelector2() {
	  if (main_core.Type.isNil(babelHelpers.classPrivateFieldGet(this, _tagsSelector))) {
	    var self = this;
	    var selectedGroupId = !main_core.Type.isString(babelHelpers.classPrivateFieldGet(this, _selectedGroupId)) ? babelHelpers.classPrivateFieldGet(this, _selectedGroupId) : undefined;
	    babelHelpers.classPrivateFieldSet(this, _tagsSelector, new ui_entitySelector.TagSelector({
	      multiple: true,
	      events: {
	        onTagAdd: function onTagAdd(event) {
	          var _tag$getCustomData$ge;
	          var _event$getData5 = event.getData(),
	            tag = _event$getData5.tag;
	          var type = (_tag$getCustomData$ge = tag.getCustomData().get('type')) !== null && _tag$getCustomData$ge !== void 0 ? _tag$getCustomData$ge : TagType.SIMPLE;
	          _classPrivateMethodGet(self, _addTag, _addTag2).call(self, type, tag.getId(), tag.getTitle());
	        },
	        onTagRemove: function onTagRemove(event) {
	          var _event$getData6 = event.getData(),
	            tag = _event$getData6.tag;
	          _classPrivateMethodGet(self, _removeTag, _removeTag2).call(self, tag.getId());
	        }
	      },
	      dialogOptions: {
	        width: 400,
	        height: 300,
	        dropdownMode: true,
	        enableSearch: true,
	        compactView: true,
	        searchOptions: {
	          allowCreateItem: false
	        },
	        footer: tasks_entitySelector.Footer,
	        footerOptions: {
	          groupId: selectedGroupId
	        },
	        offsetTop: 12,
	        entities: [{
	          id: 'task-tag',
	          options: {
	            groupId: selectedGroupId
	          }
	        }]
	      }
	    }));
	    _classPrivateMethodGet(this, _fillTagsSelector, _fillTagsSelector2).call(this);
	  }
	  return babelHelpers.classPrivateFieldGet(this, _tagsSelector);
	}
	function _fillTagsSelector2() {
	  var _this5 = this;
	  _classPrivateMethodGet(this, _updateSavedTags, _updateSavedTags2).call(this).then(function (_ref) {
	    var newTags = _ref.newTags,
	      newSelectedTags = _ref.newSelectedTags;
	    var expressionTags = babelHelpers.classPrivateFieldGet(_this5, _expressionTags);
	    babelHelpers.classPrivateFieldSet(_this5, _expressionTags, new Set());
	    var _iterator = _createForOfIteratorHelper(newSelectedTags.entries()),
	      _step;
	    try {
	      for (_iterator.s(); !(_step = _iterator.n()).done;) {
	        var _step$value = babelHelpers.slicedToArray(_step.value, 2),
	          tagId = _step$value[0],
	          tagName = _step$value[1];
	        babelHelpers.classPrivateFieldGet(_this5, _tagsSelector).addTag({
	          id: tagId,
	          title: tagName,
	          entityId: 'task-tag',
	          customData: {
	            type: TagType.SIMPLE
	          }
	        });
	      }
	    } catch (err) {
	      _iterator.e(err);
	    } finally {
	      _iterator.f();
	    }
	    var _iterator2 = _createForOfIteratorHelper(newTags.values()),
	      _step2;
	    try {
	      for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	        var _tagName = _step2.value;
	        babelHelpers.classPrivateFieldGet(_this5, _tagsSelector).addTag({
	          id: String(Math.random()),
	          title: _tagName,
	          entityId: 'task-tag',
	          customData: {
	            type: TagType.NEW
	          }
	        });
	      }
	    } catch (err) {
	      _iterator2.e(err);
	    } finally {
	      _iterator2.f();
	    }
	    var _iterator3 = _createForOfIteratorHelper(expressionTags.values()),
	      _step3;
	    try {
	      for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	        var _tagName2 = _step3.value;
	        babelHelpers.classPrivateFieldGet(_this5, _tagsSelector).addTag({
	          id: _tagName2,
	          title: _tagName2,
	          entityId: 'task-tag',
	          customData: {
	            type: TagType.EXPRESSION
	          }
	        });
	      }

	      // const preselectedItems = Array.from(this.#selectedTags.keys()).map(tagId => ['task-tag', tagId]);
	      // this.#tagsSelector.getDialog().setPreselectedItems(preselectedItems);
	    } catch (err) {
	      _iterator3.e(err);
	    } finally {
	      _iterator3.f();
	    }
	  });
	}
	function _updateSavedTags2() {
	  return _updateSavedTags3.apply(this, arguments);
	}
	function _updateSavedTags3() {
	  _updateSavedTags3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
	    var knownTags, knownTagNames, _iterator5, _step5, tag, newSelectedTags, newTags, _iterator6, _step6, _step6$value, tagId, tagName, _iterator7, _step7, _tagName3;
	    return _regeneratorRuntime().wrap(function _callee3$(_context3) {
	      while (1) switch (_context3.prev = _context3.next) {
	        case 0:
	          _context3.next = 2;
	          return _classPrivateMethodGet(this, _fetchTags, _fetchTags2).call(this);
	        case 2:
	          knownTags = new Set();
	          knownTagNames = new Map();
	          _iterator5 = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _tagsSelector).getDialog().getItems());
	          try {
	            for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
	              tag = _step5.value;
	              knownTags.add(tag.getId());
	              knownTagNames.set(tag.getTitle(), tag.getId());
	            }
	          } catch (err) {
	            _iterator5.e(err);
	          } finally {
	            _iterator5.f();
	          }
	          newSelectedTags = new Map();
	          newTags = new Set();
	          _iterator6 = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _selectedTags).entries());
	          try {
	            for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
	              _step6$value = babelHelpers.slicedToArray(_step6.value, 2), tagId = _step6$value[0], tagName = _step6$value[1];
	              if (knownTags.has(tagId)) {
	                newSelectedTags.set(tagId, tagName);
	              } else {
	                newTags.add(tagName);
	              }
	            }
	          } catch (err) {
	            _iterator6.e(err);
	          } finally {
	            _iterator6.f();
	          }
	          _iterator7 = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _newTags).values());
	          try {
	            for (_iterator7.s(); !(_step7 = _iterator7.n()).done;) {
	              _tagName3 = _step7.value;
	              if (knownTagNames.has(_tagName3)) {
	                newSelectedTags.set(knownTagNames.get(_tagName3), _tagName3);
	              } else {
	                newTags.add(_tagName3);
	              }
	            }
	          } catch (err) {
	            _iterator7.e(err);
	          } finally {
	            _iterator7.f();
	          }
	          return _context3.abrupt("return", {
	            newSelectedTags: newSelectedTags,
	            newTags: newTags
	          });
	        case 13:
	        case "end":
	          return _context3.stop();
	      }
	    }, _callee3, this);
	  }));
	  return _updateSavedTags3.apply(this, arguments);
	}
	function _fetchTags2() {
	  return _fetchTags3.apply(this, arguments);
	}
	function _fetchTags3() {
	  _fetchTags3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4() {
	    var tagsDialog;
	    return _regeneratorRuntime().wrap(function _callee4$(_context4) {
	      while (1) switch (_context4.prev = _context4.next) {
	        case 0:
	          tagsDialog = babelHelpers.classPrivateFieldGet(this, _tagsSelector).getDialog();
	          if (tagsDialog.isLoaded()) {
	            _context4.next = 4;
	            break;
	          }
	          _context4.next = 4;
	          return new Promise(function (resolve, reject) {
	            var onLoad = function onLoad() {
	              tagsDialog.unsubscribe('onLoadError', onLoadError);
	              resolve();
	            };
	            var onLoadError = function onLoadError() {
	              tagsDialog.unsubscribe('onLoad', onLoad);
	              reject();
	            };
	            tagsDialog.subscribeOnce('onLoad', onLoad);
	            tagsDialog.subscribeOnce('onLoadError', onLoadError);
	            tagsDialog.load();
	          });
	        case 4:
	        case "end":
	          return _context4.stop();
	      }
	    }, _callee4, this);
	  }));
	  return _fetchTags3.apply(this, arguments);
	}
	function _addTag2(type, id, name) {
	  if (type === TagType.SIMPLE) {
	    babelHelpers.classPrivateFieldGet(this, _selectedTags).set(id, name);
	  } else if (type === TagType.EXPRESSION) {
	    babelHelpers.classPrivateFieldGet(this, _expressionTags).add(name);
	  } else if (type === TagType.NEW) {
	    babelHelpers.classPrivateFieldGet(this, _newTags).add(name);
	  } else {
	    return;
	  }
	  babelHelpers.classPrivateFieldGet(this, _form).appendChild(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input name=\"TAG_NAMES[]\" value=\"", "\" hidden/>\n\t\t"])), main_core.Text.encode(name)));
	}
	function _removeTag2(id) {
	  var name = null;
	  if (babelHelpers.classPrivateFieldGet(this, _expressionTags).has(id)) {
	    name = id;
	    babelHelpers.classPrivateFieldGet(this, _expressionTags)["delete"](id);
	  } else if (babelHelpers.classPrivateFieldGet(this, _selectedTags).has(id)) {
	    name = babelHelpers.classPrivateFieldGet(this, _selectedTags).get(id);
	    babelHelpers.classPrivateFieldGet(this, _selectedTags)["delete"](id);
	  } else if (babelHelpers.classPrivateFieldGet(this, _newTags).has(id)) {
	    name = id;
	    babelHelpers.classPrivateFieldGet(this, _newTags)["delete"](id);
	  }
	  var tagValueElement = babelHelpers.classPrivateFieldGet(this, _form).querySelector("input[name=\"TAG_NAMES[]\"][value=\"".concat(main_core.Text.encode(name), "\"]"));
	  if (tagValueElement) {
	    main_core.Dom.remove(tagValueElement);
	  }
	}
	function _getDependsOnSelector2() {
	  if (main_core.Type.isNil(babelHelpers.classPrivateFieldGet(this, _dependsOnSelector))) {
	    var self = this;
	    babelHelpers.classPrivateFieldSet(this, _dependsOnSelector, new ui_entitySelector.TagSelector({
	      multiple: true,
	      events: {
	        onTagAdd: function onTagAdd(event) {
	          var _event$getData7 = event.getData(),
	            tag = _event$getData7.tag;
	          _classPrivateMethodGet(self, _addDependsOnTaskId, _addDependsOnTaskId2).call(self, tag.getId());
	        },
	        onTagRemove: function onTagRemove(event) {
	          var _event$getData8 = event.getData(),
	            tag = _event$getData8.tag;
	          _classPrivateMethodGet(self, _removeDependsOnTaskId, _removeDependsOnTaskId2).call(self, tag.getId());
	        }
	      },
	      dialogOptions: {
	        width: 400,
	        height: 300,
	        dropdownMode: true,
	        compactView: true,
	        enableSearch: true,
	        searchOptions: {
	          allowCreateItem: false
	        },
	        offsetTop: 12,
	        entities: [{
	          id: 'task'
	        }],
	        preselectedItems: Array.from(babelHelpers.classPrivateFieldGet(this, _selectedDependentTasks)).filter(function (taskId) {
	          return main_core.Type.isNumber(taskId);
	        }).map(function (taskId) {
	          return ['task', taskId];
	        })
	      }
	    }));
	    var _iterator4 = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _selectedDependentTasks).values()),
	      _step4;
	    try {
	      for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	        var taskId = _step4.value;
	        if (main_core.Type.isString(taskId)) {
	          babelHelpers.classPrivateFieldGet(this, _dependsOnSelector).addTag({
	            id: taskId,
	            title: taskId,
	            entityId: 'task'
	          });
	        }
	      }
	    } catch (err) {
	      _iterator4.e(err);
	    } finally {
	      _iterator4.f();
	    }
	  }
	  return babelHelpers.classPrivateFieldGet(this, _dependsOnSelector);
	}
	function _addDependsOnTaskId2(id) {
	  babelHelpers.classPrivateFieldGet(this, _selectedDependentTasks).add(id);
	  babelHelpers.classPrivateFieldGet(this, _form).appendChild(main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input name=\"DEPENDS_ON[]\" value=\"", "\" hidden/>\n\t\t"])), main_core.Text.encode(id)));
	}
	function _removeDependsOnTaskId2(id) {
	  babelHelpers.classPrivateFieldGet(this, _selectedDependentTasks)["delete"](id);
	  var taskIdElement = babelHelpers.classPrivateFieldGet(this, _form).querySelector("input[name=\"DEPENDS_ON[]\"][value=\"".concat(main_core.Text.encode(id), "\"]"));
	  if (taskIdElement) {
	    main_core.Dom.remove(taskIdElement);
	  }
	}
	namespace.Task2Activity = Task2Activity;

}((this.window = this.window || {}),BX.Bizproc.Automation,BX,BX.Event,BX.UI.EntitySelector,BX.Tasks.EntitySelector));
//# sourceMappingURL=script.js.map
