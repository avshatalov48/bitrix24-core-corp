/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,main_popup,main_core,ui_entitySelector,humanresources_companyStructure_chartStore,humanresources_companyStructure_api,humanresources_companyStructure_utils,ui_notification) {
	'use strict';

	var _templateObject;
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var employeeType = humanresources_companyStructure_api.memberRoles.employee;
	var disabledButtonClass = 'ui-btn-disabled';
	var _addEmployees = /*#__PURE__*/new WeakSet();
	var _destroyDialog = /*#__PURE__*/new WeakSet();
	var _handleOnTagAdd = /*#__PURE__*/new WeakSet();
	var _handleOnTagRemove = /*#__PURE__*/new WeakSet();
	var _onUserToggle = /*#__PURE__*/new WeakSet();
	var _toggleAddButton = /*#__PURE__*/new WeakSet();
	var AddDialogFooter = /*#__PURE__*/function (_BaseFooter) {
	  babelHelpers.inherits(AddDialogFooter, _BaseFooter);
	  function AddDialogFooter(tab, options) {
	    var _this;
	    babelHelpers.classCallCheck(this, AddDialogFooter);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AddDialogFooter).call(this, tab, options));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _toggleAddButton);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _onUserToggle);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _handleOnTagRemove);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _handleOnTagAdd);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _destroyDialog);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _addEmployees);
	    _this.role = _this.getOption('role');
	    var selectedItems = _this.getDialog().getSelectedItems();
	    _this.userCount = selectedItems.length;
	    _this.users = [];
	    selectedItems.forEach(function (item) {
	      _classPrivateMethodGet(babelHelpers.assertThisInitialized(_this), _onUserToggle, _onUserToggle2).call(babelHelpers.assertThisInitialized(_this), item);
	    });
	    _this.getDialog().subscribe('Item:onSelect', _classPrivateMethodGet(babelHelpers.assertThisInitialized(_this), _handleOnTagAdd, _handleOnTagAdd2).bind(babelHelpers.assertThisInitialized(_this)));
	    _this.getDialog().subscribe('Item:onDeselect', _classPrivateMethodGet(babelHelpers.assertThisInitialized(_this), _handleOnTagRemove, _handleOnTagRemove2).bind(babelHelpers.assertThisInitialized(_this)));
	    return _this;
	  }
	  babelHelpers.createClass(AddDialogFooter, [{
	    key: "render",
	    value: function render() {
	      var _this2 = this;
	      var _ref = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div ref=\"footer\" class=\"hr-add-employee-to-department-dialog__footer\">\n\t\t\t\t<button ref=\"footerAddButton\" class=\"ui-btn ui-btn ui-btn-sm ui-btn-primary ", " ui-btn-round hr-add-employee-dialog-btn-width\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t\t<button ref=\"footerCloseButton\" class=\"ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round hr-add-employee-dialog-btn-width\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), this.users.length === 0 ? disabledButtonClass : '', main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ADD_BUTTON'), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_CANCEL_BUTTON')),
	        footer = _ref.footer,
	        footerAddButton = _ref.footerAddButton,
	        footerCloseButton = _ref.footerCloseButton;
	      this.footerAddButton = footerAddButton;
	      main_core.Event.bind(footerCloseButton, 'click', function (event) {
	        _this2.dialog.hide();
	      });
	      main_core.Event.bind(footerAddButton, 'click', function (event) {
	        var users = _this2.dialog.getSelectedItems();
	        var userIds = users.map(function (item) {
	          return item.getId();
	        });
	        if (userIds.length > 0) {
	          main_core.Dom.addClass(footerAddButton, 'ui-btn-wait');
	          _classPrivateMethodGet(_this2, _addEmployees, _addEmployees2).call(_this2, userIds);
	        }
	      });
	      return footer;
	    }
	  }]);
	  return AddDialogFooter;
	}(ui_entitySelector.BaseFooter);
	function _addEmployees2(_x) {
	  return _addEmployees3.apply(this, arguments);
	}
	function _addEmployees3() {
	  _addEmployees3 = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(userIds) {
	    var _this$getOption, _nodeStorage$heads, _nodeStorage$employee, _ref2, _data$userCount;
	    var store, nodeId, _yield$ajax$runAction, data, errors, nodeStorage, newMemberUserIds, heads, employees, newUserCount, countDiff;
	    return _regeneratorRuntime().wrap(function _callee$(_context) {
	      while (1) switch (_context.prev = _context.next) {
	        case 0:
	          if (this.userCount) {
	            _context.next = 2;
	            break;
	          }
	          return _context.abrupt("return");
	        case 2:
	          if (!this.isAdding) {
	            _context.next = 4;
	            break;
	          }
	          return _context.abrupt("return");
	        case 4:
	          this.isAdding = true;
	          store = humanresources_companyStructure_chartStore.useChartStore();
	          nodeId = (_this$getOption = this.getOption('nodeId')) !== null && _this$getOption !== void 0 ? _this$getOption : store.focusedNode;
	          _context.next = 9;
	          return main_core.ajax.runAction('humanresources.api.Structure.Node.Member.addUserMember', {
	            data: {
	              nodeId: nodeId,
	              userIds: userIds,
	              roleXmlId: this.role
	            }
	          });
	        case 9:
	          _yield$ajax$runAction = _context.sent;
	          data = _yield$ajax$runAction.data;
	          errors = _yield$ajax$runAction.errors;
	          nodeStorage = store.departments.get(nodeId);
	          if (!(!nodeStorage || errors.length > 0)) {
	            _context.next = 16;
	            break;
	          }
	          _classPrivateMethodGet(this, _destroyDialog, _destroyDialog2).call(this);
	          return _context.abrupt("return");
	        case 16:
	          newMemberUserIds = new Set(this.users.map(function (user) {
	            return user.id;
	          }));
	          heads = (_nodeStorage$heads = nodeStorage.heads) !== null && _nodeStorage$heads !== void 0 ? _nodeStorage$heads : [];
	          heads = heads.filter(function (user) {
	            return !newMemberUserIds.has(user.id);
	          });
	          employees = ((_nodeStorage$employee = nodeStorage.employees) !== null && _nodeStorage$employee !== void 0 ? _nodeStorage$employee : []).filter(function (user) {
	            return !newMemberUserIds.has(user.id);
	          });
	          (_ref2 = this.role === employeeType ? employees : heads).push.apply(_ref2, babelHelpers.toConsumableArray(this.users));
	          nodeStorage.heads = heads;
	          nodeStorage.employees = employees;
	          newUserCount = (_data$userCount = data.userCount) !== null && _data$userCount !== void 0 ? _data$userCount : 0;
	          countDiff = newUserCount - nodeStorage.userCount;
	          nodeStorage.userCount = newUserCount;
	          if (countDiff > 1 || this.users.length > 1) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Text.encode(main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_EMPLOYEES_ADD', {
	                '#DEPARTMENT#': nodeStorage.name
	              })),
	              autoHideDelay: 2000
	            });
	          }
	          if (countDiff === 1 && this.users.length === 1) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Text.encode(main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_EMPLOYEE_ADD', {
	                '#DEPARTMENT#': nodeStorage.name
	              })),
	              autoHideDelay: 2000
	            });
	          }
	          _classPrivateMethodGet(this, _destroyDialog, _destroyDialog2).call(this);
	        case 29:
	        case "end":
	          return _context.stop();
	      }
	    }, _callee, this);
	  }));
	  return _addEmployees3.apply(this, arguments);
	}
	function _destroyDialog2() {
	  this.isAdding = false;
	  this.getDialog().destroy();
	}
	function _handleOnTagAdd2(event) {
	  var _event$getData = event.getData(),
	    item = _event$getData.item;
	  _classPrivateMethodGet(this, _onUserToggle, _onUserToggle2).call(this, item);
	}
	function _handleOnTagRemove2(event) {
	  var _event$getData2 = event.getData(),
	    item = _event$getData2.item;
	  _classPrivateMethodGet(this, _onUserToggle, _onUserToggle2).call(this, item, false);
	}
	function _onUserToggle2(item) {
	  var isSelected = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
	  if (!isSelected) {
	    this.users = this.users.filter(function (user) {
	      return user.id !== item.id;
	    });
	    this.userCount -= 1;
	    _classPrivateMethodGet(this, _toggleAddButton, _toggleAddButton2).call(this);
	    return;
	  }
	  var userData = humanresources_companyStructure_utils.getUserStoreItemByDialogItem(item, this.role);
	  this.users = [].concat(babelHelpers.toConsumableArray(this.users), [userData]);
	  this.userCount += 1;
	  _classPrivateMethodGet(this, _toggleAddButton, _toggleAddButton2).call(this);
	}
	function _toggleAddButton2() {
	  if (this.userCount === 0) {
	    main_core.Dom.addClass(this.footerAddButton, disabledButtonClass);
	    return;
	  }
	  main_core.Dom.removeClass(this.footerAddButton, disabledButtonClass);
	}

	var _templateObject$1, _templateObject2, _templateObject3;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var employeeType$1 = humanresources_companyStructure_api.memberRoles.employee;
	var headType = humanresources_companyStructure_api.memberRoles.head;
	var deputyHeadType = humanresources_companyStructure_api.memberRoles.deputyHead;
	var _addRoleSwitcher = /*#__PURE__*/new WeakSet();
	var _toggleRoleSwitcherMenu = /*#__PURE__*/new WeakSet();
	var _changeRole = /*#__PURE__*/new WeakSet();
	var AddDialogHeader = /*#__PURE__*/function (_BaseHeader) {
	  babelHelpers.inherits(AddDialogHeader, _BaseHeader);
	  function AddDialogHeader(context, options) {
	    var _this$getOption;
	    var _this;
	    babelHelpers.classCallCheck(this, AddDialogHeader);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AddDialogHeader).call(this, context, options));
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _changeRole);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _toggleRoleSwitcherMenu);
	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _addRoleSwitcher);
	    _this.role = (_this$getOption = _this.getOption('role')) !== null && _this$getOption !== void 0 ? _this$getOption : employeeType$1;
	    return _this;
	  }
	  babelHelpers.createClass(AddDialogHeader, [{
	    key: "render",
	    value: function render() {
	      var _this2 = this;
	      var title = this.role === headType ? main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_HEAD_DIALOG_HEADER_TITLE') : main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_EMPLOYEE_DIALOG_HEADER_TITLE');
	      var _ref = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div ref=\"header\" class=\"hr-add-employee-to-department-dialog__header\">\n\t\t\t\t<div ref=\"headerCloseButton\" class=\"hr-add-employee-to-department-dialog__header-close_button\"></div>\n\t\t\t\t<span class=\"hr-add-employee-to-department-dialog__header-title\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t"])), title),
	        header = _ref.header,
	        headerCloseButton = _ref.headerCloseButton;
	      main_core.Event.bind(headerCloseButton, 'click', function (event) {
	        _this2.getDialog().hide();
	      });
	      this.header = header;
	      if (this.role === employeeType$1) {
	        var employeeAddSubtitle = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"hr-add-employee-to-department-dialog__header-subtitle\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"])), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_EMPLOYEE_DIALOG_HEADER_SUBTITLE'));
	        main_core.Dom.append(employeeAddSubtitle, this.header);
	      } else {
	        _classPrivateMethodGet$1(this, _addRoleSwitcher, _addRoleSwitcher2).call(this);
	      }
	      return header;
	    }
	  }]);
	  return AddDialogHeader;
	}(ui_entitySelector.BaseHeader);
	function _addRoleSwitcher2() {
	  var _this3 = this;
	  var _ref2 = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div ref=\"roleSwitcherContainer\" class=\"hr-add-employee-to-department-dialog__role_switcher-container\">\n\t\t\t\t<span class=\"hr-add-employee-to-department-dialog__role_switcher_title\">\n\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t<div ref=\"roleSwitcher\" class=\"hr-add-employee-to-department-dialog__role_switcher\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_TITLE'), main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_HEAD_TITLE')),
	    roleSwitcherContainer = _ref2.roleSwitcherContainer,
	    roleSwitcher = _ref2.roleSwitcher;
	  main_core.Dom.append(roleSwitcherContainer, this.header);
	  this.roleSwitcher = roleSwitcher;
	  main_core.Event.bind(this.roleSwitcher, 'click', function () {
	    _classPrivateMethodGet$1(_this3, _toggleRoleSwitcherMenu, _toggleRoleSwitcherMenu2).call(_this3);
	  });
	}
	function _toggleRoleSwitcherMenu2() {
	  var _this4 = this;
	  var roleSwitcherId = "".concat(this.getDialog().id, "-role-switcher");
	  var oldRoleSwitcherMenu = main_popup.PopupManager.getPopupById(roleSwitcherId);
	  if (oldRoleSwitcherMenu) {
	    oldRoleSwitcherMenu.destroy();
	    return;
	  }
	  var roleSwitcherMenu = new main_popup.Menu({
	    id: roleSwitcherId,
	    bindElement: this.roleSwitcher,
	    autoHide: true,
	    closeByEsc: true,
	    maxWidth: 263,
	    events: {
	      onPopupDestroy: function onPopupDestroy() {
	        main_core.Dom.removeClass(_this4.roleSwitcher, '--focused');
	      }
	    }
	  });
	  var menuItems = [{
	    text: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_HEAD_TITLE'),
	    onclick: function onclick() {
	      _this4.roleSwitcher.innerText = main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_HEAD_TITLE');
	      _classPrivateMethodGet$1(_this4, _changeRole, _changeRole2).call(_this4, headType);
	      roleSwitcherMenu.destroy();
	    }
	  }, {
	    text: main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_DEPUTY_HEAD_TITLE'),
	    onclick: function onclick() {
	      _this4.roleSwitcher.innerText = main_core.Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ROLE_DEPUTY_HEAD_TITLE');
	      _classPrivateMethodGet$1(_this4, _changeRole, _changeRole2).call(_this4, deputyHeadType);
	      roleSwitcherMenu.destroy();
	    }
	  }];
	  menuItems.forEach(function (menuItem) {
	    return roleSwitcherMenu.addMenuItem(menuItem);
	  });
	  if (roleSwitcherMenu.isShown) {
	    roleSwitcherMenu.destroy();
	    return;
	  }
	  roleSwitcherMenu.show();
	  main_core.Dom.addClass(this.roleSwitcher, '--focused');
	}
	function _changeRole2(role) {
	  this.getDialog().getRecentTab().setFooter(AddDialogFooter, {
	    role: role
	  });
	  this.getDialog().setFooter(AddDialogFooter, {
	    role: role
	  });
	}

	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var dialogId = 'hr-add-user-to-department-dialog';
	var employeeType$2 = humanresources_companyStructure_api.memberRoles.employee;
	var headType$1 = humanresources_companyStructure_api.memberRoles.head;
	var _role = /*#__PURE__*/new WeakMap();
	var _type = /*#__PURE__*/new WeakMap();
	var _dialog = /*#__PURE__*/new WeakMap();
	var _nodeId = /*#__PURE__*/new WeakMap();
	var _createDialog = /*#__PURE__*/new WeakSet();
	var AddUserDialog = /*#__PURE__*/function () {
	  function AddUserDialog() {
	    var _options$nodeId;
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, AddUserDialog);
	    _classPrivateMethodInitSpec$2(this, _createDialog);
	    _classPrivateFieldInitSpec(this, _role, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _type, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _nodeId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _type, options.type === 'head' ? 'head' : 'employee');
	    this.id = "".concat(dialogId, "-").concat(babelHelpers.classPrivateFieldGet(this, _type));
	    babelHelpers.classPrivateFieldSet(this, _role, options.type === 'head' ? headType$1 : employeeType$2);
	    babelHelpers.classPrivateFieldSet(this, _nodeId, (_options$nodeId = options.nodeId) !== null && _options$nodeId !== void 0 ? _options$nodeId : null);
	    _classPrivateMethodGet$2(this, _createDialog, _createDialog2).call(this);
	  }
	  babelHelpers.createClass(AddUserDialog, [{
	    key: "show",
	    value: function show() {
	      babelHelpers.classPrivateFieldGet(this, _dialog).show();
	    }
	  }], [{
	    key: "openDialog",
	    value: function openDialog() {
	      var _options$type;
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var previousDialog = ui_entitySelector.Dialog.getById("".concat(dialogId, "-").concat((_options$type = options.type) !== null && _options$type !== void 0 ? _options$type : 'employee'));
	      if (previousDialog) {
	        previousDialog.show();
	        return;
	      }
	      var instance = new AddUserDialog(options);
	      instance.show();
	    }
	  }]);
	  return AddUserDialog;
	}();
	function _createDialog2() {
	  babelHelpers.classPrivateFieldSet(this, _dialog, new ui_entitySelector.Dialog({
	    id: this.id,
	    width: 400,
	    height: 511,
	    multiple: true,
	    cacheable: false,
	    dropdownMode: true,
	    compactView: false,
	    enableSearch: true,
	    showAvatars: true,
	    autoHide: false,
	    header: AddDialogHeader,
	    popupOptions: {
	      overlay: {
	        opacity: 40
	      }
	    },
	    headerOptions: {
	      role: babelHelpers.classPrivateFieldGet(this, _role)
	    },
	    footer: AddDialogFooter,
	    footerOptions: {
	      role: babelHelpers.classPrivateFieldGet(this, _role),
	      nodeId: babelHelpers.classPrivateFieldGet(this, _nodeId)
	    },
	    entities: [{
	      id: 'user',
	      options: {
	        intranetUsersOnly: true,
	        inviteEmployeeLink: false
	      }
	    }]
	  }));
	}

	exports.AddUserDialog = AddUserDialog;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Main,BX,BX.UI.EntitySelector,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX.Humanresources.CompanyStructure,BX));
