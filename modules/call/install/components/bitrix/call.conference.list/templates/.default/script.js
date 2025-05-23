/* eslint-disable */
(function (exports,main_core,main_core_events,main_popup,ui_dialogs_messagebox,im_lib_clipboard) {
	'use strict';

	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	var namespace = main_core.Reflection.namespace('BX.Messenger.PhpComponent');
	var Utils = main_core.Reflection.getClass('BX.Messenger.v2.Lib.Utils');
	var ConferenceList = /*#__PURE__*/function () {
	  function ConferenceList(params) {
	    babelHelpers.classCallCheck(this, ConferenceList);
	    this.pathToAdd = params.pathToAdd;
	    this.pathToEdit = params.pathToEdit;
	    this.pathToList = params.pathToList;
	    this.sliderWidth = params.sliderWidth || 800;
	    this.gridId = params.gridId;
	    this.gridManager = main_core.Reflection.getClass('top.BX.Main.gridManager');
	    this.init();
	  }
	  babelHelpers.createClass(ConferenceList, [{
	    key: "init",
	    value: function init() {
	      this.bindEvents();
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;
	      main_core_events.EventEmitter.subscribe('Grid::updated', function () {
	        _this.bindGridEvents();
	      });
	      this.bindCreateButtonEvents();
	      this.bindGridEvents();
	    }
	  }, {
	    key: "bindCreateButtonEvents",
	    value: function bindCreateButtonEvents() {
	      var _this2 = this;
	      var emptyListCreateButton = document.querySelector('.im-conference-list-empty-button');
	      if (emptyListCreateButton) {
	        main_core.Event.bind(emptyListCreateButton, 'click', function () {
	          _this2.openCreateSlider();
	        });
	      }
	      var panelCreateButton = document.querySelector('.im-conference-list-panel-button-create');
	      main_core.Event.bind(panelCreateButton, 'click', function () {
	        _this2.openCreateSlider();
	      });
	    }
	  }, {
	    key: "bindGridEvents",
	    value: function bindGridEvents() {
	      var _this3 = this;
	      //grid rows
	      this.rows = document.querySelectorAll('.main-grid-row');
	      this.rows.forEach(function (row) {
	        var conferenceId = row.getAttribute('data-conference-id');
	        var chatId = row.getAttribute('data-chat-id');
	        var publicLink = row.getAttribute('data-public-link');
	        var conferenceIsFinished = !!row.getAttribute('data-conference-finished');

	        //start button
	        var startButton = row.querySelector('.im-conference-list-controls-button-start');
	        main_core.Event.bind(startButton, 'click', /*#__PURE__*/function () {
	          var _ref = babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(event) {
	            var code;
	            return _regeneratorRuntime().wrap(function _callee$(_context) {
	              while (1) switch (_context.prev = _context.next) {
	                case 0:
	                  event.preventDefault();
	                  code = Utils.conference.getCodeByOptions({
	                    link: startButton.dataset.conferenceLink
	                  });
	                  window.BX.Messenger.Public.openConference({
	                    code: code
	                  });
	                case 3:
	                case "end":
	                  return _context.stop();
	              }
	            }, _callee);
	          }));
	          return function (_x) {
	            return _ref.apply(this, arguments);
	          };
	        }());

	        //more button
	        var moreButton = row.querySelector('.im-conference-list-controls-button-more');
	        main_core.Event.bind(moreButton, 'click', function (event) {
	          event.preventDefault();
	          _this3.openContextMenu({
	            buttonNode: moreButton,
	            conferenceId: conferenceId,
	            chatId: chatId
	          });
	        });

	        //copy link button
	        var copyButton = row.querySelector('.im-conference-list-controls-button-copy');
	        main_core.Event.bind(copyButton, 'click', function (event) {
	          event.preventDefault();
	          _this3.copyLink(publicLink);
	        });

	        //chat name link
	        var chatNameLink = row.querySelector('.im-conference-list-chat-name-link');
	        main_core.Event.bind(chatNameLink, 'click', function (event) {
	          event.preventDefault();
	          _this3.openEditSlider(conferenceId);
	        });
	      });
	    }
	  }, {
	    key: "openCreateSlider",
	    value: function openCreateSlider() {
	      this.openSlider(this.pathToAdd);
	    }
	  }, {
	    key: "openEditSlider",
	    value: function openEditSlider(conferenceId) {
	      var pathToEdit = this.pathToEdit.replace('#id#', conferenceId);
	      this.openSlider(pathToEdit);
	    }
	  }, {
	    key: "openSlider",
	    value: function openSlider(path) {
	      this.closeContextMenu();
	      if (main_core.Reflection.getClass('BX.SidePanel')) {
	        BX.SidePanel.Instance.open(path, {
	          width: this.sliderWidth,
	          cacheable: false
	        });
	      }
	    }
	  }, {
	    key: "copyLink",
	    value: function copyLink(link) {
	      im_lib_clipboard.Clipboard.copy(link);
	      if (main_core.Reflection.getClass('BX.UI.Notification.Center')) {
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CONFERENCE_LIST_NOTIFICATION_LINK_COPIED')
	        });
	      }
	    }
	  }, {
	    key: "openContextMenu",
	    value: function openContextMenu(_ref2) {
	      var _this4 = this;
	      var buttonNode = _ref2.buttonNode,
	        conferenceId = _ref2.conferenceId,
	        chatId = _ref2.chatId;
	      main_core.ajax.runComponentAction('bitrix:call.conference.list', "getAllowedOperations", {
	        mode: 'ajax',
	        data: {
	          conferenceId: conferenceId
	        }
	      }).then(function (_ref3) {
	        var _ref3$data = _ref3.data,
	          canDelete = _ref3$data["delete"],
	          canEdit = _ref3$data.edit;
	        if (main_core.Type.isDomNode(buttonNode)) {
	          var menuItems = [{
	            text: main_core.Loc.getMessage('CONFERENCE_LIST_CONTEXT_MENU_CHAT'),
	            onclick: function onclick() {
	              _this4.openChat(chatId);
	            }
	          }];
	          if (canEdit) {
	            menuItems.push({
	              text: main_core.Loc.getMessage('CONFERENCE_LIST_CONTEXT_MENU_EDIT'),
	              onclick: function onclick() {
	                _this4.openEditSlider(conferenceId);
	              }
	            });
	          }
	          if (canDelete) {
	            menuItems.push({
	              text: main_core.Loc.getMessage('CONFERENCE_LIST_CONTEXT_MENU_DELETE'),
	              className: 'im-conference-list-context-menu-item-delete menu-popup-no-icon',
	              onclick: function onclick() {
	                _this4.deleteAction(conferenceId);
	              }
	            });
	          }
	          _this4.menu = new main_popup.Menu({
	            bindElement: buttonNode,
	            items: menuItems,
	            events: {
	              onPopupClose: function onPopupClose() {
	                this.destroy();
	              }
	            }
	          });
	          _this4.menu.show();
	        }
	      })["catch"](function (response) {
	        console.error(response);
	      });
	    }
	  }, {
	    key: "closeContextMenu",
	    value: function closeContextMenu() {
	      if (this.menu) {
	        this.menu.close();
	      }
	    }
	  }, {
	    key: "openChat",
	    value: function openChat(chatId) {
	      this.closeContextMenu();
	      if (main_core.Reflection.getClass('BXIM.openMessenger')) {
	        BXIM.openMessenger('chat' + chatId);
	      }
	    }
	  }, {
	    key: "deleteAction",
	    value: function deleteAction(conferenceId) {
	      var _this5 = this;
	      this.closeContextMenu();
	      main_core.ajax.runComponentAction('bitrix:call.conference.list', "deleteConference", {
	        mode: 'ajax',
	        data: {
	          conferenceId: conferenceId
	        }
	      }).then(function (response) {
	        _this5.onSuccessfulDelete(response);
	      })["catch"](function (response) {
	        _this5.onFailedDelete(response);
	      });
	    }
	  }, {
	    key: "onSuccessfulDelete",
	    value: function onSuccessfulDelete(response) {
	      if (response.data['LAST_ROW'] === true) {
	        top.window.location = this.pathToList;
	        return true;
	      }
	      if (this.gridManager) {
	        this.gridManager.reload(this.gridId);
	      }
	    }
	  }, {
	    key: "onFailedDelete",
	    value: function onFailedDelete(response) {
	      ui_dialogs_messagebox.MessageBox.alert(response["errors"][0].message);
	    }
	  }]);
	  return ConferenceList;
	}();
	namespace.ConferenceList = ConferenceList;

}((this.window = this.window || {}),BX,BX.Event,BX.Main,BX.UI.Dialogs,BX.Messenger.Lib));
//# sourceMappingURL=script.js.map
