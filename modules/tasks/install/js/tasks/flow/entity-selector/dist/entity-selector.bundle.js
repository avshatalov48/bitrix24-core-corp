/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_infoHelper,main_core_events,main_core,ui_entitySelector) {
	'use strict';

	var _templateObject;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _onCreateFlowButtonClicked = /*#__PURE__*/new WeakSet();
	var _onFlowCreated = /*#__PURE__*/new WeakSet();
	var Footer = /*#__PURE__*/function (_DefaultFooter) {
	  babelHelpers.inherits(Footer, _DefaultFooter);
	  function Footer() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, Footer);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Footer)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onFlowCreated);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onCreateFlowButtonClicked);
	    return _this;
	  }
	  babelHelpers.createClass(Footer, [{
	    key: "render",
	    value: function render() {
	      var element = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"ui-selector-footer-link ui-selector-footer-link-add\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), main_core.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_FOOTER_CREATE_FLOW'));
	      main_core.Event.bind(element, 'click', _classPrivateMethodGet(this, _onCreateFlowButtonClicked, _onCreateFlowButtonClicked2).bind(this));
	      return element;
	    }
	  }]);
	  return Footer;
	}(ui_entitySelector.DefaultFooter);
	function _onCreateFlowButtonClicked2() {
	  var _this2 = this;
	  return new Promise(function (resolve) {
	    // eslint-disable-next-line promise/catch-or-return
	    top.BX.Runtime.loadExtension('tasks.flow.edit-form').then( /*#__PURE__*/function () {
	      var _ref = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(exports) {
	        var editForm;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              _context.next = 2;
	              return exports.EditForm.createInstance({
	                flowName: ''
	              });
	            case 2:
	              editForm = _context.sent;
	              editForm.subscribe('afterSave', function (baseEvent) {
	                resolve(baseEvent.getData());
	              });
	              editForm.subscribe('afterClose', function (baseEvent) {
	                resolve();
	              });
	            case 5:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee);
	      }));
	      return function (_x) {
	        return _ref.apply(this, arguments);
	      };
	    }());
	  }).then(function (createdFlowData) {
	    if (createdFlowData) {
	      _classPrivateMethodGet(_this2, _onFlowCreated, _onFlowCreated2).call(_this2, createdFlowData);
	    }
	  });
	}
	function _onFlowCreated2(createdFlowData) {
	  var item = this.getDialog().addItem({
	    tabs: 'recents',
	    id: createdFlowData.id,
	    entityId: 'flow',
	    title: createdFlowData.name,
	    customData: {
	      groupId: createdFlowData.groupId,
	      templateId: createdFlowData.templateId
	    }
	  });
	  item.select();
	}

	var AbstractToggleFlow = /*#__PURE__*/function () {
	  function AbstractToggleFlow() {
	    babelHelpers.classCallCheck(this, AbstractToggleFlow);
	  }
	  babelHelpers.createClass(AbstractToggleFlow, [{
	    key: "onSelectFlow",
	    value: function onSelectFlow(event, itemBeforeUpdate) {
	      throw new Error('AbstractToggleFlow: Calling an abstract changeFlow() without implementation');
	    }
	  }, {
	    key: "onDeselectFlow",
	    value: function onDeselectFlow(event, selectedItem) {
	      throw new Error('AbstractToggleFlow: Calling an abstract unChangeFlow() without implementation');
	    }
	  }, {
	    key: "showConfirmChangeFlow",
	    value: function showConfirmChangeFlow(doneCallback, _cancelCallback) {
	      BX.UI.Dialogs.MessageBox.show({
	        message: main_core.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CHANGE_MESSAGE'),
	        title: main_core.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CHANGE_TITLE'),
	        onOk: function onOk() {
	          doneCallback();
	        },
	        okCaption: main_core.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CHANGE_OK_CAPTION'),
	        cancelCallback: function cancelCallback(messageBox) {
	          _cancelCallback();
	          messageBox.close();
	        },
	        cancelCaption: main_core.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CHANGE_CANCEL_CAPTION'),
	        buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	        popupOptions: {
	          events: {
	            onPopupClose: function onPopupClose() {
	              _cancelCallback();
	            }
	          }
	        }
	      });
	    }
	  }]);
	  return AbstractToggleFlow;
	}();

	var Scope = Object.freeze({
	  taskView: 'taskView',
	  taskEdit: 'taskEdit'
	});

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _params = /*#__PURE__*/new WeakMap();
	var _bindFlow = /*#__PURE__*/new WeakSet();
	var _unBindFlow = /*#__PURE__*/new WeakSet();
	var _shouldShowConfirmChangeFlow = /*#__PURE__*/new WeakSet();
	var _getEditorText = /*#__PURE__*/new WeakSet();
	var _removeBBCode = /*#__PURE__*/new WeakSet();
	var TaskEditToggleFlow = /*#__PURE__*/function (_AbstractToggleFlow) {
	  babelHelpers.inherits(TaskEditToggleFlow, _AbstractToggleFlow);
	  function TaskEditToggleFlow(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, TaskEditToggleFlow);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TaskEditToggleFlow).call(this));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _removeBBCode);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getEditorText);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _shouldShowConfirmChangeFlow);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _unBindFlow);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _bindFlow);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _params, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _params, params);
	    return _this;
	  }
	  babelHelpers.createClass(TaskEditToggleFlow, [{
	    key: "onSelectFlow",
	    value: function onSelectFlow(event, itemBeforeUpdate) {
	      var dialog = event.getTarget();
	      var selectedItem = event.getData().item;
	      var flowId = parseInt(selectedItem.id, 10);
	      var groupId = parseInt(selectedItem.customData.get('groupId'), 10);
	      var templateId = parseInt(selectedItem.customData.get('templateId'), 10);
	      window.onbeforeunload = function () {};
	      if (_classPrivateMethodGet$1(this, _shouldShowConfirmChangeFlow, _shouldShowConfirmChangeFlow2).call(this)) {
	        var rollback = function rollback() {
	          dialog.getItem(itemBeforeUpdate).select(true);
	        };
	        this.showConfirmChangeFlow(_classPrivateMethodGet$1(this, _bindFlow, _bindFlow2).bind(this, flowId, groupId, templateId), rollback);
	        return;
	      }
	      _classPrivateMethodGet$1(this, _bindFlow, _bindFlow2).call(this, flowId, groupId, templateId);
	    }
	  }, {
	    key: "onDeselectFlow",
	    value: function onDeselectFlow(event, selectedItem) {
	      if (selectedItem !== null) {
	        return;
	      }
	      window.onbeforeunload = function () {};
	      if (_classPrivateMethodGet$1(this, _shouldShowConfirmChangeFlow, _shouldShowConfirmChangeFlow2).call(this)) {
	        var rollback = function rollback() {
	          var dialog = event.getTarget();
	          var deselectedItem = event.getData().item;
	          dialog.getItem(deselectedItem).select(true);
	        };
	        this.showConfirmChangeFlow(_classPrivateMethodGet$1(this, _unBindFlow, _unBindFlow2).bind(this), rollback);
	        return;
	      }
	      _classPrivateMethodGet$1(this, _unBindFlow, _unBindFlow2).call(this);
	    }
	  }]);
	  return TaskEditToggleFlow;
	}(AbstractToggleFlow);
	function _bindFlow2(flowId, groupId, templateId) {
	  var currentUri = new BX.Uri(decodeURI(location.href));
	  currentUri.setQueryParam('FLOW_ID', flowId);
	  currentUri.setQueryParam('GROUP_ID', groupId);
	  if (templateId) {
	    currentUri.setQueryParam('TEMPLATE', templateId);
	  } else {
	    currentUri.removeQueryParam('TEMPLATE');
	  }
	  currentUri.removeQueryParam('EVENT_TYPE');
	  currentUri.removeQueryParam('EVENT_TASK_ID');
	  currentUri.removeQueryParam('EVENT_OPTIONS');
	  currentUri.removeQueryParam('NO_FLOW');
	  var immutable = babelHelpers.classPrivateFieldGet(this, _params).immutable;
	  Object.entries(immutable).forEach(function (_ref) {
	    var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	      key = _ref2[0],
	      value = _ref2[1];
	    currentUri.setQueryParam(key, value);
	  });
	  var demoSuffix = babelHelpers.classPrivateFieldGet(this, _params).isFeatureTrialable ? 'Y' : 'N';
	  currentUri.setQueryParams({
	    ta_cat: 'task_operations',
	    ta_sec: 'flows',
	    ta_sub: 'flows_grid',
	    ta_el: 'flow_selector',
	    p1: "isDemo_".concat(demoSuffix)
	  });
	  location.href = currentUri.getPath() + currentUri.getQuery();
	}
	function _unBindFlow2() {
	  var currentUri = new BX.Uri(decodeURI(location.href));
	  currentUri.removeQueryParam('FLOW_ID', 'GROUP_ID', 'TEMPLATE');
	  currentUri.removeQueryParam('EVENT_TYPE');
	  currentUri.removeQueryParam('EVENT_TASK_ID');
	  currentUri.removeQueryParam('EVENT_OPTIONS');
	  currentUri.setQueryParam('NO_FLOW', 1);
	  var immutable = babelHelpers.classPrivateFieldGet(this, _params).immutable;
	  Object.entries(immutable).forEach(function (_ref3) {
	    var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
	      key = _ref4[0],
	      value = _ref4[1];
	    currentUri.setQueryParam(key, value);
	  });
	  location.href = currentUri.getPath() + currentUri.getQuery();
	}
	function _shouldShowConfirmChangeFlow2() {
	  var description = _classPrivateMethodGet$1(this, _getEditorText, _getEditorText2).call(this).trim();
	  var hasDescription = description.length > 0;
	  if (!hasDescription) {
	    return false;
	  }
	  var isNewTask = Number(babelHelpers.classPrivateFieldGet(this, _params).taskId) === 0;
	  if (isNewTask) {
	    return true;
	  }
	  var taskDescription = babelHelpers.classPrivateFieldGet(this, _params).taskDescription;
	  return !_classPrivateMethodGet$1(this, _removeBBCode, _removeBBCode2).call(this, taskDescription).includes(description);
	}
	function _getEditorText2() {
	  var container = document.querySelector('[data-bx-id="task-edit-editor-container"]');
	  var isBBCode = main_core.Dom.style(container.querySelector('.bxhtmled-iframe-cnt'), 'display') === 'none';
	  if (isBBCode) {
	    var textArea = container.querySelector('.bxhtmled-textarea');
	    return textArea.value;
	  }
	  var editor = container.querySelector('.bx-editor-iframe').contentDocument;
	  return editor.body.innerText;
	}
	function _removeBBCode2(text) {
	  return text.replaceAll(/\[(user|icon|color|size|url|b|i|u|s)(?:=[^\]]*)?](.*?)\[\/\1]/gi, '$2');
	}

	function _regeneratorRuntime$1() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$1 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _taskId = /*#__PURE__*/new WeakMap();
	var _currentFlowId = /*#__PURE__*/new WeakMap();
	var _unBindFlow$1 = /*#__PURE__*/new WeakSet();
	var _updateFlow = /*#__PURE__*/new WeakSet();
	var TaskViewToggleFlow = /*#__PURE__*/function (_AbstractToggleFlow) {
	  babelHelpers.inherits(TaskViewToggleFlow, _AbstractToggleFlow);
	  function TaskViewToggleFlow(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, TaskViewToggleFlow);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TaskViewToggleFlow).call(this));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _updateFlow);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _unBindFlow$1);
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _taskId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _currentFlowId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _taskId, params.taskId);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _currentFlowId, params.flowId);
	    return _this;
	  }
	  babelHelpers.createClass(TaskViewToggleFlow, [{
	    key: "onSelectFlow",
	    value: function onSelectFlow(event, itemBeforeUpdate) {
	      var selectedItem = event.getData().item;
	      var flowId = parseInt(selectedItem.id, 10);
	      _classPrivateMethodGet$2(this, _updateFlow, _updateFlow2).call(this, flowId);
	    }
	  }, {
	    key: "onDeselectFlow",
	    value: function onDeselectFlow(event, selectedItem) {
	      var unSelectedItem = event.getData().item;
	      var unSelectedFlowId = parseInt(unSelectedItem.id, 10);
	      if (unSelectedFlowId === babelHelpers.classPrivateFieldGet(this, _currentFlowId)) {
	        _classPrivateMethodGet$2(this, _unBindFlow$1, _unBindFlow2$1).call(this);
	      }
	    }
	  }]);
	  return TaskViewToggleFlow;
	}(AbstractToggleFlow);
	function _unBindFlow2$1() {
	  _classPrivateMethodGet$2(this, _updateFlow, _updateFlow2).call(this, 0);
	}
	function _updateFlow2(_x) {
	  return _updateFlow3.apply(this, arguments);
	}
	function _updateFlow3() {
	  _updateFlow3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$1().mark(function _callee(flowId) {
	    var flowIdBeforeUpdate;
	    return _regeneratorRuntime$1().wrap(function _callee$(_context) {
	      while (1) switch (_context.prev = _context.next) {
	        case 0:
	          flowIdBeforeUpdate = babelHelpers.classPrivateFieldGet(this, _currentFlowId);
	          babelHelpers.classPrivateFieldSet(this, _currentFlowId, flowId);
	          _context.prev = 2;
	          _context.next = 5;
	          return main_core.ajax.runAction('tasks.task.update', {
	            data: {
	              taskId: babelHelpers.classPrivateFieldGet(this, _taskId),
	              fields: {
	                FLOW_ID: flowId
	              }
	            }
	          });
	        case 5:
	          _context.next = 10;
	          break;
	        case 7:
	          _context.prev = 7;
	          _context.t0 = _context["catch"](2);
	          babelHelpers.classPrivateFieldSet(this, _currentFlowId, flowIdBeforeUpdate);
	        case 10:
	        case "end":
	          return _context.stop();
	      }
	    }, _callee, this, [[2, 7]]);
	  }));
	  return _updateFlow3.apply(this, arguments);
	}

	var ToggleFlowFactory = /*#__PURE__*/function () {
	  function ToggleFlowFactory() {
	    babelHelpers.classCallCheck(this, ToggleFlowFactory);
	  }
	  babelHelpers.createClass(ToggleFlowFactory, null, [{
	    key: "get",
	    value: function get(toggleFlowParams) {
	      // eslint-disable-next-line sonarjs/no-small-switch
	      switch (toggleFlowParams.scope) {
	        case Scope.taskView:
	          return new TaskViewToggleFlow(toggleFlowParams);
	        default:
	          return new TaskEditToggleFlow(toggleFlowParams);
	      }
	    }
	  }]);
	  return ToggleFlowFactory;
	}();

	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _params$1 = /*#__PURE__*/new WeakMap();
	var _toggleFlow = /*#__PURE__*/new WeakMap();
	var _dialog = /*#__PURE__*/new WeakMap();
	var _selectedItemBeforeUpdate = /*#__PURE__*/new WeakMap();
	var _createDialog = /*#__PURE__*/new WeakSet();
	var _createFlow = /*#__PURE__*/new WeakSet();
	var _addFooter = /*#__PURE__*/new WeakSet();
	var EntitySelectorDialog = /*#__PURE__*/function () {
	  function EntitySelectorDialog(params) {
	    babelHelpers.classCallCheck(this, EntitySelectorDialog);
	    _classPrivateMethodInitSpec$3(this, _addFooter);
	    _classPrivateMethodInitSpec$3(this, _createFlow);
	    _classPrivateMethodInitSpec$3(this, _createDialog);
	    _classPrivateFieldInitSpec$2(this, _params$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _toggleFlow, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _selectedItemBeforeUpdate, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _params$1, params);
	    babelHelpers.classPrivateFieldSet(this, _toggleFlow, ToggleFlowFactory.get(babelHelpers.classPrivateFieldGet(this, _params$1).toggleFlowParams));
	    babelHelpers.classPrivateFieldSet(this, _selectedItemBeforeUpdate, null);
	  }
	  babelHelpers.createClass(EntitySelectorDialog, [{
	    key: "show",
	    value: function show(target) {
	      if (!babelHelpers.classPrivateFieldGet(this, _params$1).isFeatureEnabled) {
	        ui_infoHelper.InfoHelper.show(babelHelpers.classPrivateFieldGet(this, _params$1).flowLimitCode);
	        return;
	      }
	      if (!babelHelpers.classPrivateFieldGet(this, _dialog)) {
	        _classPrivateMethodGet$3(this, _createDialog, _createDialog2).call(this, target);
	      }
	      babelHelpers.classPrivateFieldGet(this, _dialog).show();
	    }
	  }]);
	  return EntitySelectorDialog;
	}();
	function _createDialog2(target) {
	  var _this = this;
	  babelHelpers.classPrivateFieldSet(this, _dialog, new ui_entitySelector.Dialog({
	    targetNode: target,
	    width: 350,
	    height: 400,
	    multiple: false,
	    dropdownMode: true,
	    enableSearch: true,
	    cacheable: true,
	    preselectedItems: [['flow', babelHelpers.classPrivateFieldGet(this, _params$1).flowId]],
	    entities: [{
	      id: 'flow',
	      options: {
	        onlyActive: true
	      },
	      dynamicLoad: true,
	      dynamicSearch: true
	    }],
	    events: {
	      'Item:onBeforeSelect': function ItemOnBeforeSelect(event) {
	        var dialog = event.getTarget();
	        babelHelpers.classPrivateFieldSet(_this, _selectedItemBeforeUpdate, dialog.getSelectedItems()[0]);
	      },
	      'Item:onBeforeDeselect': function ItemOnBeforeDeselect(event) {
	        var dialog = event.getTarget();
	        babelHelpers.classPrivateFieldSet(_this, _selectedItemBeforeUpdate, dialog.getSelectedItems()[0]);
	        dialog.hide();
	      },
	      'Item:onSelect': function ItemOnSelect(event) {
	        babelHelpers.classPrivateFieldGet(_this, _toggleFlow).onSelectFlow(event, babelHelpers.classPrivateFieldGet(_this, _selectedItemBeforeUpdate));
	      },
	      'Item:onDeselect': function ItemOnDeselect(event) {
	        setTimeout(function () {
	          var _dialog$getSelectedIt;
	          var dialog = event.getTarget();
	          babelHelpers.classPrivateFieldGet(_this, _toggleFlow).onDeselectFlow(event, (_dialog$getSelectedIt = dialog.getSelectedItems()[0]) !== null && _dialog$getSelectedIt !== void 0 ? _dialog$getSelectedIt : null);
	        }, 100);
	      },
	      'Search:onItemCreateAsync': function SearchOnItemCreateAsync(event) {
	        return new Promise(function (resolve) {
	          /** @type  {BX.UI.EntitySelector.Item} */
	          var _event$getData = event.getData(),
	            searchQuery = _event$getData.searchQuery;
	          /** @type  {BX.UI.EntitySelector.Dialog} */
	          var dialog = event.getTarget();
	          _classPrivateMethodGet$3(_this, _createFlow, _createFlow2).call(_this, searchQuery.getQuery()).then(function (createdFlowData) {
	            if (createdFlowData) {
	              var item = dialog.addItem({
	                tabs: 'recents',
	                id: createdFlowData.id,
	                entityId: 'flow',
	                title: createdFlowData.name,
	                customData: {
	                  groupId: createdFlowData.groupId,
	                  templateId: createdFlowData.templateId
	                }
	              });
	              item.select();
	              resolve();
	            } else {
	              resolve();
	            }
	          });
	        });
	      }
	    },
	    searchOptions: {
	      allowCreateItem: !babelHelpers.classPrivateFieldGet(this, _params$1).isExtranet,
	      footerOptions: {
	        label: BX.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CREATE_BUTTON')
	      }
	    },
	    recentTabOptions: {
	      stub: 'BX.Tasks.Flow.EmptyStub',
	      stubOptions: {
	        showArrow: !babelHelpers.classPrivateFieldGet(this, _params$1).isExtranet
	      }
	    }
	  }));
	  if (!babelHelpers.classPrivateFieldGet(this, _params$1).isExtranet) {
	    babelHelpers.classPrivateFieldSet(this, _dialog, _classPrivateMethodGet$3(this, _addFooter, _addFooter2).call(this, babelHelpers.classPrivateFieldGet(this, _dialog)));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _dialog);
	}
	function _createFlow2(flowName) {
	  return main_core.Runtime.loadExtension('tasks.flow.edit-form').then(function (exports) {
	    return exports.EditForm.createInstance({
	      flowName: flowName
	    });
	  }).then(function (editForm) {
	    return new Promise(function (resolve) {
	      editForm.subscribe('afterSave', function (baseEvent) {
	        resolve(baseEvent.getData());
	      });
	      editForm.subscribe('afterClose', function () {
	        resolve();
	      });
	    });
	  });
	}
	function _addFooter2(dialog) {
	  var footer = new Footer(babelHelpers.classPrivateFieldGet(this, _dialog));
	  dialog.setFooter(footer.render());
	  return dialog;
	}

	var _templateObject$1, _templateObject2;
	function _classPrivateMethodInitSpec$4(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _showArrow = /*#__PURE__*/new WeakMap();
	var _renderArrow = /*#__PURE__*/new WeakSet();
	var EmptyStub = /*#__PURE__*/function (_BaseStub) {
	  babelHelpers.inherits(EmptyStub, _BaseStub);
	  function EmptyStub(tab, options) {
	    var _this;
	    babelHelpers.classCallCheck(this, EmptyStub);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(EmptyStub).call(this, tab, options));
	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _renderArrow);
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _showArrow, {
	      writable: true,
	      value: true
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _showArrow, options.showArrow);
	    return _this;
	  }
	  babelHelpers.createClass(EmptyStub, [{
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-flow__stub-container\">\n\t\t\t    <div class=\"tasks-flow__stub-title\">\n\t\t\t        ", "\n\t\t\t    </div>\n\t\t\t    <div class=\"tasks-flow__stub-icon\"></div>\n\t\t\t    <div class=\"tasks-flow__stub-subtitle-container\">\n\t\t\t    \t", "\n\t\t\t    \t<div class=\"tasks-flow__stub-subtitle-text\">\n\t\t\t\t \t\t", "\n\t\t\t\t\t</div>\n\t\t\t    </div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_STUB_TITLE'), babelHelpers.classPrivateFieldGet(this, _showArrow) ? _classPrivateMethodGet$4(this, _renderArrow, _renderArrow2).call(this) : '', main_core.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_STUB_SUBTITLE', {
	        '[helpdesklink]': '<a class="tasks-flow__stub-link" href="javascript:top.BX.Helper.show(\'redirect=detail&code=21307026\');">',
	        '[/helpdesklink]': '</a>'
	      }));
	    }
	  }]);
	  return EmptyStub;
	}(ui_entitySelector.BaseStub);
	function _renderArrow2() {
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-flow__stub-subtitle-arrow\"></div>\n\t\t"])));
	}

	var _templateObject$2, _templateObject2$1, _templateObject3;
	function _regeneratorRuntime$2() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime$2 = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec$5(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$5(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _flowSelectorContainer = /*#__PURE__*/new WeakMap();
	var _taskId$1 = /*#__PURE__*/new WeakMap();
	var _isExtranet = /*#__PURE__*/new WeakMap();
	var _canEditTask = /*#__PURE__*/new WeakMap();
	var _toggleFlowParams = /*#__PURE__*/new WeakMap();
	var _flowParams = /*#__PURE__*/new WeakMap();
	var _dialog$1 = /*#__PURE__*/new WeakMap();
	var _subscribeEvents = /*#__PURE__*/new WeakSet();
	var _onTaskUpdated = /*#__PURE__*/new WeakSet();
	var _loadFlowData = /*#__PURE__*/new WeakSet();
	var _updateFlow$1 = /*#__PURE__*/new WeakSet();
	var _render = /*#__PURE__*/new WeakSet();
	var _createDialog$1 = /*#__PURE__*/new WeakSet();
	var _renderFlowName = /*#__PURE__*/new WeakSet();
	var _renderEfficiency = /*#__PURE__*/new WeakSet();
	var _prepareEfficiency = /*#__PURE__*/new WeakSet();
	var EntitySelector = /*#__PURE__*/function () {
	  function EntitySelector(_params) {
	    babelHelpers.classCallCheck(this, EntitySelector);
	    _classPrivateMethodInitSpec$5(this, _prepareEfficiency);
	    _classPrivateMethodInitSpec$5(this, _renderEfficiency);
	    _classPrivateMethodInitSpec$5(this, _renderFlowName);
	    _classPrivateMethodInitSpec$5(this, _createDialog$1);
	    _classPrivateMethodInitSpec$5(this, _render);
	    _classPrivateMethodInitSpec$5(this, _updateFlow$1);
	    _classPrivateMethodInitSpec$5(this, _loadFlowData);
	    _classPrivateMethodInitSpec$5(this, _onTaskUpdated);
	    _classPrivateMethodInitSpec$5(this, _subscribeEvents);
	    _classPrivateFieldInitSpec$4(this, _flowSelectorContainer, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(this, _taskId$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(this, _isExtranet, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(this, _canEditTask, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(this, _toggleFlowParams, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(this, _flowParams, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$4(this, _dialog$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _taskId$1, _params.taskId);
	    babelHelpers.classPrivateFieldSet(this, _isExtranet, _params.isExtranet);
	    babelHelpers.classPrivateFieldSet(this, _canEditTask, _params.canEditTask);
	    babelHelpers.classPrivateFieldSet(this, _toggleFlowParams, _params.toggleFlowParams);
	    babelHelpers.classPrivateFieldSet(this, _flowParams, _params.flowParams);
	    babelHelpers.classPrivateFieldSet(this, _flowSelectorContainer, document.getElementById('tasks-flow-selector-container'));
	    _classPrivateMethodGet$5(this, _subscribeEvents, _subscribeEvents2).call(this);
	  }
	  babelHelpers.createClass(EntitySelector, [{
	    key: "show",
	    value: function show(target) {
	      if (!main_core.Type.isDomNode(target)) {
	        throw new TypeError('HTMLElement for render flow entity selector not found');
	      }
	      main_core.Dom.clean(target);
	      main_core.Dom.append(_classPrivateMethodGet$5(this, _render, _render2).call(this), target);
	    }
	  }]);
	  return EntitySelector;
	}();
	function _subscribeEvents2() {
	  BX.PULL.subscribe({
	    type: BX.PullClient.SubscriptionType.Server,
	    moduleId: 'tasks',
	    command: 'task_update',
	    callback: _classPrivateMethodGet$5(this, _onTaskUpdated, _onTaskUpdated2).bind(this)
	  });
	}
	function _onTaskUpdated2(_x, _x2, _x3) {
	  return _onTaskUpdated3.apply(this, arguments);
	}
	function _onTaskUpdated3() {
	  _onTaskUpdated3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$2().mark(function _callee(params, extra, command) {
	    var _params$AFTER$FLOW_ID;
	    var isEventByCurrentTask, isEventContainsFlow, flowId, isFlowChange, flowData;
	    return _regeneratorRuntime$2().wrap(function _callee$(_context) {
	      while (1) switch (_context.prev = _context.next) {
	        case 0:
	          isEventByCurrentTask = parseInt(params === null || params === void 0 ? void 0 : params.TASK_ID, 10) === babelHelpers.classPrivateFieldGet(this, _taskId$1);
	          isEventContainsFlow = !main_core.Type.isUndefined(params.AFTER.FLOW_ID);
	          if (!(!isEventByCurrentTask || !isEventContainsFlow)) {
	            _context.next = 4;
	            break;
	          }
	          return _context.abrupt("return");
	        case 4:
	          flowId = Number((_params$AFTER$FLOW_ID = params.AFTER.FLOW_ID) !== null && _params$AFTER$FLOW_ID !== void 0 ? _params$AFTER$FLOW_ID : 0);
	          isFlowChange = babelHelpers.classPrivateFieldGet(this, _flowParams).id !== flowId;
	          if (isFlowChange) {
	            _context.next = 8;
	            break;
	          }
	          return _context.abrupt("return");
	        case 8:
	          flowData = {
	            id: 0,
	            name: '',
	            efficiency: 0
	          };
	          if (!(flowId !== 0)) {
	            _context.next = 13;
	            break;
	          }
	          _context.next = 12;
	          return _classPrivateMethodGet$5(this, _loadFlowData, _loadFlowData2).call(this, flowId);
	        case 12:
	          flowData = _context.sent;
	        case 13:
	          _classPrivateMethodGet$5(this, _updateFlow$1, _updateFlow2$1).call(this, flowData);
	        case 14:
	        case "end":
	          return _context.stop();
	      }
	    }, _callee, this);
	  }));
	  return _onTaskUpdated3.apply(this, arguments);
	}
	function _loadFlowData2(_x4) {
	  return _loadFlowData3.apply(this, arguments);
	}
	function _loadFlowData3() {
	  _loadFlowData3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime$2().mark(function _callee2(flowId) {
	    var flowResponse;
	    return _regeneratorRuntime$2().wrap(function _callee2$(_context2) {
	      while (1) switch (_context2.prev = _context2.next) {
	        case 0:
	          _context2.next = 2;
	          return main_core.ajax.runAction('tasks.flow.Flow.get', {
	            data: {
	              flowId: flowId
	            }
	          });
	        case 2:
	          flowResponse = _context2.sent;
	          return _context2.abrupt("return", flowResponse.data);
	        case 4:
	        case "end":
	          return _context2.stop();
	      }
	    }, _callee2);
	  }));
	  return _loadFlowData3.apply(this, arguments);
	}
	function _updateFlow2$1(flowData) {
	  babelHelpers.classPrivateFieldGet(this, _flowParams).id = flowData.id;
	  babelHelpers.classPrivateFieldGet(this, _flowParams).name = flowData.name;
	  babelHelpers.classPrivateFieldGet(this, _flowParams).efficiency = flowData.efficiency;
	  this.show(babelHelpers.classPrivateFieldGet(this, _flowSelectorContainer));
	}
	function _render2() {
	  var _this = this;
	  var flowFeatureEnabledClass = babelHelpers.classPrivateFieldGet(this, _flowParams).isFeatureEnabled ? '' : '--tariff-lock';
	  var flowCanChangeClass = babelHelpers.classPrivateFieldGet(this, _canEditTask) ? 'ui-btn-dropdown' : '--disable';
	  var flowBtnClasses = 'ui-btn ui-btn-round ui-btn-xs ui-btn-no-caps';
	  var container = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button \n\t\t\t\tclass=\"tasks-flow__selector ", " ", " ", "\" \n\t\t\t\tid=\"tasks-flow-selector\"\n\t\t\t>\t\t\n\t\t\t</button>\n\t\t"])), flowBtnClasses, flowFeatureEnabledClass, flowCanChangeClass);
	  if (babelHelpers.classPrivateFieldGet(this, _canEditTask)) {
	    main_core.Event.bind(container, 'click', function () {
	      var _babelHelpers$classPr;
	      babelHelpers.classPrivateFieldSet(_this, _dialog$1, (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(_this, _dialog$1)) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : _classPrivateMethodGet$5(_this, _createDialog$1, _createDialog2$1).call(_this));
	      babelHelpers.classPrivateFieldGet(_this, _dialog$1).show(babelHelpers.classPrivateFieldGet(_this, _flowSelectorContainer));
	    });
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _flowParams).id) {
	    main_core.Dom.addClass(container, 'ui-btn-secondary-light');
	    container.append(_classPrivateMethodGet$5(this, _renderFlowName, _renderFlowName2).call(this, babelHelpers.classPrivateFieldGet(this, _flowParams).name));
	    container.append(_classPrivateMethodGet$5(this, _renderEfficiency, _renderEfficiency2).call(this, babelHelpers.classPrivateFieldGet(this, _flowParams).efficiency));
	  } else {
	    main_core.Dom.addClass(container, 'ui-btn-base-light');
	    container.append(_classPrivateMethodGet$5(this, _renderFlowName, _renderFlowName2).call(this, main_core.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_FLOW_EMPTY')));
	    if (!babelHelpers.classPrivateFieldGet(this, _canEditTask)) {
	      main_core.Dom.addClass(container, '--hide');
	    }
	  }
	  return container;
	}
	function _createDialog2$1() {
	  babelHelpers.classPrivateFieldSet(this, _dialog$1, new EntitySelectorDialog({
	    isExtranet: babelHelpers.classPrivateFieldGet(this, _isExtranet),
	    toggleFlowParams: babelHelpers.classPrivateFieldGet(this, _toggleFlowParams),
	    flowId: babelHelpers.classPrivateFieldGet(this, _flowParams).id,
	    flowLimitCode: babelHelpers.classPrivateFieldGet(this, _flowParams).limitCode,
	    isFeatureEnabled: babelHelpers.classPrivateFieldGet(this, _flowParams).isFeatureEnabled,
	    isFeatureTrialable: babelHelpers.classPrivateFieldGet(this, _flowParams).isFeatureTrialable
	  }));
	  return babelHelpers.classPrivateFieldGet(this, _dialog$1);
	}
	function _renderFlowName2(name) {
	  return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"tasks-flow__selector-text\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), main_core.Text.encode(name));
	}
	function _renderEfficiency2(efficiency) {
	  return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"tasks-flow__selector-efficiency\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), _classPrivateMethodGet$5(this, _prepareEfficiency, _prepareEfficiency2).call(this, efficiency));
	}
	function _prepareEfficiency2(efficiency) {
	  return "".concat(efficiency, "%");
	}

	exports.EmptyStub = EmptyStub;
	exports.EntitySelector = EntitySelector;

}((this.BX.Tasks.Flow = this.BX.Tasks.Flow || {}),BX.UI,BX.Event,BX,BX.UI.EntitySelector));
