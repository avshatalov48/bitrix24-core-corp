this.BX = this.BX || {};
(function (exports,main_core_cache,ui_progressbar,ui_designTokens,main_core,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _entityIds = /*#__PURE__*/new WeakMap();
	var _containerId = /*#__PURE__*/new WeakMap();
	var _progressBar = /*#__PURE__*/new WeakMap();
	var _wrapper = /*#__PURE__*/new WeakMap();
	var _messages = /*#__PURE__*/new WeakMap();
	var _getProgressBarOptions = /*#__PURE__*/new WeakSet();
	var _getWrapperElement = /*#__PURE__*/new WeakSet();
	var _showErrors = /*#__PURE__*/new WeakSet();
	var _createErrorElement = /*#__PURE__*/new WeakSet();
	var _getContainerElement = /*#__PURE__*/new WeakSet();
	var Panel = /*#__PURE__*/function () {
	  function Panel(params) {
	    babelHelpers.classCallCheck(this, Panel);
	    _classPrivateMethodInitSpec(this, _getContainerElement);
	    _classPrivateMethodInitSpec(this, _createErrorElement);
	    _classPrivateMethodInitSpec(this, _showErrors);
	    _classPrivateMethodInitSpec(this, _getWrapperElement);
	    _classPrivateMethodInitSpec(this, _getProgressBarOptions);
	    _classPrivateFieldInitSpec(this, _entityIds, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(this, _containerId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _progressBar, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _wrapper, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _messages, {
	      writable: true,
	      value: void 0
	    });
	    var entityIds = params.entityIds,
	      containerId = params.containerId,
	      onAfterTextClick = params.onAfterTextClick,
	      messages = params.messages;
	    babelHelpers.classPrivateFieldSet(this, _entityIds, entityIds);
	    babelHelpers.classPrivateFieldSet(this, _containerId, containerId);
	    babelHelpers.classPrivateFieldSet(this, _messages, messages);
	    var _options = _classPrivateMethodGet(this, _getProgressBarOptions, _getProgressBarOptions2).call(this);
	    if (main_core.Type.isFunction(onAfterTextClick)) {
	      _options.clickAfterCallback = onAfterTextClick;
	    }
	    babelHelpers.classPrivateFieldSet(this, _progressBar, new ui_progressbar.ProgressBar(_options));
	  }
	  babelHelpers.createClass(Panel, [{
	    key: "render",
	    value: function render() {
	      main_core.Dom.append(_classPrivateMethodGet(this, _getWrapperElement, _getWrapperElement2).call(this), _classPrivateMethodGet(this, _getContainerElement, _getContainerElement2).call(this));
	      babelHelpers.classPrivateFieldGet(this, _progressBar).renderTo(babelHelpers.classPrivateFieldGet(this, _wrapper));
	    }
	  }, {
	    key: "setProgress",
	    value: function setProgress(value) {
	      var maxValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      if (maxValue !== null) {
	        babelHelpers.classPrivateFieldGet(this, _progressBar).setMaxValue(maxValue);
	      }
	      babelHelpers.classPrivateFieldGet(this, _progressBar).update(value);
	      return this;
	    }
	  }, {
	    key: "showResult",
	    value: function showResult(errors) {
	      var _this = this;
	      var textBefore = '';
	      var successCount = babelHelpers.classPrivateFieldGet(this, _progressBar).getValue() - errors.length;
	      if (successCount > 0) {
	        textBefore += main_core.Loc.getMessage(babelHelpers.classPrivateFieldGet(this, _messages).successCount, {
	          '#COUNT#': successCount
	        });
	      }
	      var failedCount = errors.length;
	      if (failedCount > 0) {
	        if (textBefore !== '') {
	          textBefore += '. ';
	        }
	        textBefore += main_core.Loc.getMessage(babelHelpers.classPrivateFieldGet(this, _messages).failedCount, {
	          '#COUNT#': failedCount
	        });
	        _classPrivateMethodGet(this, _showErrors, _showErrors2).call(this, errors);
	      }
	      babelHelpers.classPrivateFieldGet(this, _progressBar).setTextBefore(textBefore).setClickAfterCallback(function () {
	        return _this.close();
	      }).setTextAfter(main_core.Loc.getMessage('RECYCLEBIN_DM_PROGRESSBAR_CLOSE'));
	      return this;
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      var _this2 = this;
	      var force = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      this.hide();
	      if (force) {
	        this.remove();
	        return;
	      }
	      setTimeout(function () {
	        return _this2.remove();
	      }, 400);
	    }
	  }, {
	    key: "remove",
	    value: function remove() {
	      main_core.Dom.remove(babelHelpers.classPrivateFieldGet(this, _wrapper));
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _wrapper), '--hidden');
	    }
	  }]);
	  return Panel;
	}();
	function _getProgressBarOptions2() {
	  var options = {
	    value: 0,
	    maxValue: 0,
	    statusType: ui_progressbar.ProgressBar.Status.COUNTER,
	    textBefore: main_core.Loc.getMessage(babelHelpers.classPrivateFieldGet(this, _messages).textBefore),
	    textAfter: main_core.Loc.getMessage(babelHelpers.classPrivateFieldGet(this, _messages).textAfter),
	    colorBar: '#ebcd2c',
	    colorTrack: '#f2e59e'
	  };
	  if (main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldGet(this, _entityIds))) {
	    options.maxValue = babelHelpers.classPrivateFieldGet(this, _entityIds).length;
	  }
	  return options;
	}
	function _getWrapperElement2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _wrapper)) {
	    babelHelpers.classPrivateFieldSet(this, _wrapper, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"recyclebin-list-grid-panel\"></div>"]))));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _wrapper);
	}
	function _showErrors2(errors) {
	  var _this3 = this;
	  errors.forEach(function (error) {
	    main_core.Dom.append(_classPrivateMethodGet(_this3, _createErrorElement, _createErrorElement2).call(_this3, error), babelHelpers.classPrivateFieldGet(_this3, _wrapper));
	  });
	}
	function _createErrorElement2(error) {
	  var title = error.customData.info.title;
	  var message = error.message;
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"recyclebin-list-grid-panel-row\">", ": ", "</div>"])), main_core.Text.encode(title), main_core.Text.encode(message));
	}
	function _getContainerElement2() {
	  return document.getElementById(babelHelpers.classPrivateFieldGet(this, _containerId));
	}

	var State = {
	  intermediate: 0,
	  running: 1,
	  completed: 2,
	  stopped: 3,
	  error: 4
	};

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _id = /*#__PURE__*/new WeakMap();
	var _settings = /*#__PURE__*/new WeakMap();
	var _params = /*#__PURE__*/new WeakMap();
	var _requestIsRunning = /*#__PURE__*/new WeakMap();
	var _state = /*#__PURE__*/new WeakMap();
	var _errors = /*#__PURE__*/new WeakMap();
	var _processedItemCount = /*#__PURE__*/new WeakMap();
	var _totalItemCount = /*#__PURE__*/new WeakMap();
	var _panel = /*#__PURE__*/new WeakMap();
	var _action = /*#__PURE__*/new WeakMap();
	var Progress = /*#__PURE__*/function () {
	  function Progress(id, settings) {
	    var _settings$params;
	    babelHelpers.classCallCheck(this, Progress);
	    _classPrivateFieldInitSpec$1(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _settings, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _requestIsRunning, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _state, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _errors, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec$1(this, _processedItemCount, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _totalItemCount, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _panel, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _action, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _id, id);
	    babelHelpers.classPrivateFieldSet(this, _settings, settings);
	    babelHelpers.classPrivateFieldSet(this, _action, settings.action);
	    babelHelpers.classPrivateFieldSet(this, _panel, settings.panel);
	    this.emitter = settings.emitter;
	    babelHelpers.classPrivateFieldSet(this, _params, (_settings$params = settings.params) !== null && _settings$params !== void 0 ? _settings$params : {});
	    this.startRequest = this.startRequest.bind(this);
	    this.closePanel = this.closePanel.bind(this);
	    this.onRequestSuccess = this.onRequestSuccess.bind(this);
	    this.onRequestFailure = this.onRequestFailure.bind(this);
	  }
	  babelHelpers.createClass(Progress, [{
	    key: "setParams",
	    value: function setParams(params) {
	      babelHelpers.classPrivateFieldSet(this, _params, main_core.Runtime.merge(babelHelpers.classPrivateFieldGet(this, _params), params));
	      return this;
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      if (babelHelpers.classPrivateFieldGet(this, _state) === State.stopped) {
	        babelHelpers.classPrivateFieldSet(this, _state, State.intermediate);
	      }
	      this.startRequest();
	    }
	  }, {
	    key: "startRequest",
	    value: function startRequest() {
	      var _this = this;
	      if (babelHelpers.classPrivateFieldGet(this, _state) === State.stopped) {
	        return;
	      }
	      if (babelHelpers.classPrivateFieldGet(this, _requestIsRunning)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _requestIsRunning, true);
	      babelHelpers.classPrivateFieldSet(this, _state, State.running);
	      var data = {
	        params: main_core.Type.isPlainObject(babelHelpers.classPrivateFieldGet(this, _params)) ? babelHelpers.classPrivateFieldGet(this, _params) : {}
	      };
	      main_core.ajax.runAction(babelHelpers.classPrivateFieldGet(this, _action), {
	        data: data
	      }).then(function (result) {
	        var _result$data;
	        return _this.onRequestSuccess((_result$data = result.data) !== null && _result$data !== void 0 ? _result$data : {});
	      }, function (result) {
	        var _result$data2;
	        return _this.onRequestFailure((_result$data2 = result.data) !== null && _result$data2 !== void 0 ? _result$data2 : {});
	      })["catch"](function (error) {
	        console.error(error);
	      });
	    }
	  }, {
	    key: "onRequestSuccess",
	    value: function onRequestSuccess(result) {
	      babelHelpers.classPrivateFieldSet(this, _requestIsRunning, false);
	      if (babelHelpers.classPrivateFieldGet(this, _state) === State.stopped) {
	        return;
	      }
	      var status = result.status,
	        errors = result.errors,
	        processedItems = result.processedItems,
	        totalItems = result.totalItems;
	      if (status === 'ERROR') {
	        babelHelpers.classPrivateFieldSet(this, _state, State.error);
	      } else if (status === 'COMPLETED') {
	        babelHelpers.classPrivateFieldSet(this, _state, State.completed);
	      }
	      if (babelHelpers.classPrivateFieldGet(this, _state) === State.error) {
	        console.error(babelHelpers.classPrivateFieldGet(this, _errors));
	      } else {
	        babelHelpers.classPrivateFieldSet(this, _processedItemCount, processedItems !== null && processedItems !== void 0 ? processedItems : 0);
	        babelHelpers.classPrivateFieldSet(this, _totalItemCount, totalItems !== null && totalItems !== void 0 ? totalItems : 0);
	      }
	      if (main_core.Type.isArrayFilled(errors)) {
	        babelHelpers.classPrivateFieldSet(this, _errors, [].concat(babelHelpers.toConsumableArray(babelHelpers.classPrivateFieldGet(this, _errors)), babelHelpers.toConsumableArray(errors)));
	      }
	      this.refresh();
	      if (babelHelpers.classPrivateFieldGet(this, _state) === State.running) {
	        setTimeout(this.startRequest, this.getTimeout());
	      } else if (babelHelpers.classPrivateFieldGet(this, _state) === State.completed && !main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldGet(this, _errors))) {
	        setTimeout(this.closePanel, this.getTimeout());
	      }
	      this.emitStateChange();
	    }
	  }, {
	    key: "onRequestFailure",
	    value: function onRequestFailure(data) {
	      babelHelpers.classPrivateFieldSet(this, _requestIsRunning, false);
	      babelHelpers.classPrivateFieldSet(this, _state, State.error);
	      this.refresh();
	      this.emitStateChange();
	    }
	  }, {
	    key: "emitStateChange",
	    value: function emitStateChange() {
	      this.emitter.emit('ON_AUTORUN_PROCESS_STATE_CHANGE', new main_core_events.BaseEvent({
	        data: {
	          state: babelHelpers.classPrivateFieldGet(this, _state),
	          processedItemCount: babelHelpers.classPrivateFieldGet(this, _processedItemCount),
	          totalItemCount: babelHelpers.classPrivateFieldGet(this, _totalItemCount),
	          errors: babelHelpers.classPrivateFieldGet(this, _errors)
	        }
	      }));
	    }
	  }, {
	    key: "refresh",
	    value: function refresh() {
	      if (babelHelpers.classPrivateFieldGet(this, _state) === State.running) {
	        babelHelpers.classPrivateFieldGet(this, _panel).setProgress(babelHelpers.classPrivateFieldGet(this, _processedItemCount), babelHelpers.classPrivateFieldGet(this, _totalItemCount));
	      } else if (babelHelpers.classPrivateFieldGet(this, _state) === State.completed) {
	        babelHelpers.classPrivateFieldGet(this, _panel).setProgress(babelHelpers.classPrivateFieldGet(this, _processedItemCount), babelHelpers.classPrivateFieldGet(this, _totalItemCount)).showResult(babelHelpers.classPrivateFieldGet(this, _errors));
	      } else if (babelHelpers.classPrivateFieldGet(this, _state) === State.stopped) {
	        this.closePanel();
	      }
	    }
	  }, {
	    key: "getTimeout",
	    value: function getTimeout() {
	      var DEFAULT_TIMEOUT = 2000;
	      return main_core.Type.isNumber(babelHelpers.classPrivateFieldGet(this, _settings).timeout) ? babelHelpers.classPrivateFieldGet(this, _settings).timeout : DEFAULT_TIMEOUT;
	    }
	  }, {
	    key: "reset",
	    value: function reset() {
	      babelHelpers.classPrivateFieldSet(this, _errors, []);
	      babelHelpers.classPrivateFieldSet(this, _processedItemCount, 0);
	      babelHelpers.classPrivateFieldSet(this, _totalItemCount, 0);
	      this.refresh();
	    }
	  }, {
	    key: "closePanel",
	    value: function closePanel() {
	      babelHelpers.classPrivateFieldGet(this, _panel).close();
	    }
	  }]);
	  return Progress;
	}();

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _isRunning = /*#__PURE__*/new WeakMap();
	var _entityIds$1 = /*#__PURE__*/new WeakMap();
	var _hasLayout = /*#__PURE__*/new WeakMap();
	var _progress = /*#__PURE__*/new WeakMap();
	var _panel$1 = /*#__PURE__*/new WeakMap();
	var _settings$1 = /*#__PURE__*/new WeakMap();
	var _operationHash = /*#__PURE__*/new WeakMap();
	var _messages$1 = /*#__PURE__*/new WeakMap();
	var _action$1 = /*#__PURE__*/new WeakMap();
	var _setAction = /*#__PURE__*/new WeakSet();
	var _getPrepareActionPath = /*#__PURE__*/new WeakSet();
	var _getProcessActionPath = /*#__PURE__*/new WeakSet();
	var _getProgress = /*#__PURE__*/new WeakSet();
	var _getPanel = /*#__PURE__*/new WeakSet();
	/**
	 * @memberOf BX.Recyclebin
	 */
	var DeletionManager = /*#__PURE__*/function () {
	  babelHelpers.createClass(DeletionManager, null, [{
	    key: "getInstance",
	    value: function getInstance(id, settings) {
	      if (!DeletionManager.items.has(id)) {
	        var instance = new DeletionManager(id, settings);
	        DeletionManager.items.set(id, instance);
	      }
	      return DeletionManager.items.get(id);
	    }
	  }]);
	  function DeletionManager(id, settings) {
	    babelHelpers.classCallCheck(this, DeletionManager);
	    _classPrivateMethodInitSpec$1(this, _getPanel);
	    _classPrivateMethodInitSpec$1(this, _getProgress);
	    _classPrivateMethodInitSpec$1(this, _getProcessActionPath);
	    _classPrivateMethodInitSpec$1(this, _getPrepareActionPath);
	    _classPrivateMethodInitSpec$1(this, _setAction);
	    babelHelpers.defineProperty(this, "id", null);
	    _classPrivateFieldInitSpec$2(this, _isRunning, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec$2(this, _entityIds$1, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec$2(this, _hasLayout, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _progress, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _panel$1, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _settings$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _operationHash, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _messages$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _action$1, {
	      writable: true,
	      value: void 0
	    });
	    this.id = id;
	    babelHelpers.classPrivateFieldSet(this, _settings$1, settings);
	    this.emitter = new main_core_events.EventEmitter();
	    this.emitter.setEventNamespace('Recyclebin.DeletionManager');
	    this.progressChangeHandler = this.progressChangeHandler.bind(this);
	  }
	  babelHelpers.createClass(DeletionManager, [{
	    key: "setMessages",
	    value: function setMessages(messages) {
	      babelHelpers.classPrivateFieldSet(this, _messages$1, messages);
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "setEntityIds",
	    value: function setEntityIds(entityIds) {
	      babelHelpers.classPrivateFieldSet(this, _entityIds$1, entityIds);
	      return this;
	    }
	  }, {
	    key: "executeRestore",
	    value: function executeRestore() {
	      if (this.isRunning()) {
	        return;
	      }
	      _classPrivateMethodGet$1(this, _setAction, _setAction2).call(this, 'restore');
	      this.layout();
	      this.run();
	    }
	  }, {
	    key: "executeDelete",
	    value: function executeDelete() {
	      var _this = this;
	      if (this.isRunning()) {
	        return;
	      }
	      BX.Recyclebin.confirm(main_core.Loc.getMessage('RECYCLEBIN_DM_CONFIRM_REMOVE_TITLE'), null, {
	        buttonSet: [{
	          text: main_core.Loc.getMessage('RECYCLEBIN_DM_CONFIRM_REMOVE_YES'),
	          type: 'green',
	          code: 'continue',
	          "default": true
	        }]
	      }).then(function () {
	        _classPrivateMethodGet$1(_this, _setAction, _setAction2).call(_this, 'delete');
	        _this.layout();
	        _this.run();
	      });
	    }
	  }, {
	    key: "isRunning",
	    value: function isRunning() {
	      return babelHelpers.classPrivateFieldGet(this, _isRunning);
	    }
	  }, {
	    key: "layout",
	    value: function layout() {
	      if (babelHelpers.classPrivateFieldGet(this, _hasLayout)) {
	        this.clearLayout();
	      }
	      _classPrivateMethodGet$1(this, _getPanel, _getPanel2).call(this).render();
	      babelHelpers.classPrivateFieldSet(this, _hasLayout, true);
	    }
	  }, {
	    key: "run",
	    value: function run() {
	      var _babelHelpers$classPr,
	        _this2 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _isRunning)) {
	        return;
	      }
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _progress)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.reset();
	      babelHelpers.classPrivateFieldSet(this, _isRunning, true);
	      this.enableGridFilter(false);
	      var params = {
	        gridId: this.id
	      };
	      if (main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldGet(this, _entityIds$1))) {
	        params.entityIds = babelHelpers.classPrivateFieldGet(this, _entityIds$1);
	      }
	      main_core.ajax.runAction(_classPrivateMethodGet$1(this, _getPrepareActionPath, _getPrepareActionPath2).call(this), {
	        data: {
	          params: params
	        }
	      }).then(function (response) {
	        var hash = response.data.hash;
	        if (!main_core.Type.isStringFilled(hash)) {
	          _this2.reset();
	          return;
	        }
	        babelHelpers.classPrivateFieldSet(_this2, _operationHash, hash);
	        _classPrivateMethodGet$1(_this2, _getProgress, _getProgress2).call(_this2).setParams({
	          hash: hash
	        }).run();
	        _this2.emitter.subscribe('ON_AUTORUN_PROCESS_STATE_CHANGE', _this2.progressChangeHandler);
	      })["catch"](function (error) {
	        console.error(error);
	      });
	    }
	  }, {
	    key: "getCancelActionPath",
	    value: function getCancelActionPath() {
	      var path = 'recyclebin.api.DeletionManager';
	      var action = babelHelpers.classPrivateFieldGet(this, _action$1);
	      if (action === 'delete') {
	        return "".concat(path, ".cancelDeletion");
	      }
	      if (action === 'restore') {
	        return "".concat(path, ".cancelRestore");
	      }
	      throw new Error("Unknown action: ".concat(action));
	    }
	  }, {
	    key: "getOperationHash",
	    value: function getOperationHash() {
	      return babelHelpers.classPrivateFieldGet(this, _operationHash);
	    }
	  }, {
	    key: "reset",
	    value: function reset() {
	      var clearLayout = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      babelHelpers.classPrivateFieldSet(this, _operationHash, null);
	      babelHelpers.classPrivateFieldSet(this, _isRunning, false);
	      this.emitter.unsubscribe('ON_AUTORUN_PROCESS_STATE_CHANGE', this.progressChangeHandler);
	      if (babelHelpers.classPrivateFieldGet(this, _hasLayout) && clearLayout) {
	        window.setTimeout(this.clearLayout.bind(this), 2000);
	      }
	      this.enableGridFilter(true);
	      BX.Main.gridManager.reload(this.getId());
	    }
	  }, {
	    key: "clearLayout",
	    value: function clearLayout() {
	      babelHelpers.classPrivateFieldGet(this, _panel$1).close(true);
	      babelHelpers.classPrivateFieldSet(this, _panel$1, null);
	      babelHelpers.classPrivateFieldSet(this, _progress, null);
	      babelHelpers.classPrivateFieldSet(this, _hasLayout, false);
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {
	      var _this3 = this;
	      var hash = this.getOperationHash();
	      main_core.ajax.runAction(this.getCancelActionPath(), {
	        data: {
	          params: {
	            hash: hash
	          }
	        }
	      }).then(function () {
	        _this3.clearLayout();
	      })["catch"](function (error) {
	        console.error(error);
	      });
	      _classPrivateMethodGet$1(this, _getPanel, _getPanel2).call(this).hide();
	    }
	  }, {
	    key: "progressChangeHandler",
	    value: function progressChangeHandler(_ref) {
	      var data = _ref.data;
	      var state = data.state,
	        errors = data.errors;
	      if (state === State.completed || state === State.stopped) {
	        this.reset(!main_core.Type.isArrayFilled(errors));
	      }
	    }
	  }, {
	    key: "resetEntityIds",
	    value: function resetEntityIds() {
	      babelHelpers.classPrivateFieldSet(this, _entityIds$1, []);
	    }
	  }, {
	    key: "enableGridFilter",
	    value: function enableGridFilter(enable) {
	      var container = document.getElementById("".concat(this.id, "_search_container"));
	      if (!container) {
	        return;
	      }
	      var className = 'main-ui-disable';
	      if (enable) {
	        main_core.Dom.removeClass(container, className);
	      } else {
	        main_core.Dom.addClass(container, className);
	      }
	    }
	  }]);
	  return DeletionManager;
	}();
	function _setAction2(action) {
	  babelHelpers.classPrivateFieldSet(this, _action$1, action);
	}
	function _getPrepareActionPath2() {
	  var path = 'recyclebin.api.DeletionManager';
	  var action = babelHelpers.classPrivateFieldGet(this, _action$1);
	  if (action === 'delete') {
	    return "".concat(path, ".prepareDeletion");
	  }
	  if (action === 'restore') {
	    return "".concat(path, ".prepareRestore");
	  }
	  throw new Error("Unknown action: ".concat(action));
	}
	function _getProcessActionPath2() {
	  var path = 'recyclebin.api.DeletionManager';
	  var action = babelHelpers.classPrivateFieldGet(this, _action$1);
	  if (action === 'delete') {
	    return "".concat(path, ".processDeletion");
	  }
	  if (action === 'restore') {
	    return "".concat(path, ".processRestore");
	  }
	  throw new Error("Unknown action: ".concat(action));
	}
	function _getProgress2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _progress)) {
	    babelHelpers.classPrivateFieldSet(this, _progress, new Progress(this.id, {
	      action: _classPrivateMethodGet$1(this, _getProcessActionPath, _getProcessActionPath2).call(this),
	      panel: _classPrivateMethodGet$1(this, _getPanel, _getPanel2).call(this),
	      emitter: this.emitter,
	      params: {
	        moduleId: babelHelpers.classPrivateFieldGet(this, _settings$1).moduleId
	      }
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _progress);
	}
	function _getPanel2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _panel$1)) {
	    var _babelHelpers$classPr2;
	    var params = {
	      entityIds: babelHelpers.classPrivateFieldGet(this, _entityIds$1),
	      containerId: babelHelpers.classPrivateFieldGet(this, _settings$1).containerId,
	      onAfterTextClick: this.cancel.bind(this),
	      messages: (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _messages$1)) !== null && _babelHelpers$classPr2 !== void 0 ? _babelHelpers$classPr2 : []
	    };
	    babelHelpers.classPrivateFieldSet(this, _panel$1, new Panel(params));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _panel$1);
	}
	babelHelpers.defineProperty(DeletionManager, "items", new main_core.Cache.MemoryCache());

	exports.DeletionManager = DeletionManager;

}((this.BX.Recyclebin = this.BX.Recyclebin || {}),BX.Cache,BX.UI,BX,BX,BX.Event));
//# sourceMappingURL=deletion-manager.bundle.js.map
