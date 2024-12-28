/* eslint-disable */
(function (exports,main_core,main_core_events,ui_dialogs_messagebox,ui_notification) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm.Copilot.CallAssessmentList');
	var _id = /*#__PURE__*/new WeakMap();
	var _targetNode = /*#__PURE__*/new WeakMap();
	var _checked = /*#__PURE__*/new WeakMap();
	var _readOnly = /*#__PURE__*/new WeakMap();
	var _changeCallAssessmentActive = /*#__PURE__*/new WeakSet();
	var ActiveField = /*#__PURE__*/function () {
	  function ActiveField(_ref) {
	    var id = _ref.id,
	      targetNodeId = _ref.targetNodeId,
	      checked = _ref.checked,
	      readOnly = _ref.readOnly;
	    babelHelpers.classCallCheck(this, ActiveField);
	    _classPrivateMethodInitSpec(this, _changeCallAssessmentActive);
	    _classPrivateFieldInitSpec(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _targetNode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _checked, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _readOnly, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _id, id);
	    babelHelpers.classPrivateFieldSet(this, _targetNode, document.getElementById(targetNodeId));
	    babelHelpers.classPrivateFieldSet(this, _checked, checked);
	    babelHelpers.classPrivateFieldSet(this, _readOnly, readOnly);
	  }
	  babelHelpers.createClass(ActiveField, [{
	    key: "init",
	    value: function init() {
	      var _this = this;
	      void main_core.Runtime.loadExtension('ui.switcher').then(function (exports) {
	        var Switcher = exports.Switcher;
	        var switcher = new Switcher({
	          checked: babelHelpers.classPrivateFieldGet(_this, _checked),
	          disabled: babelHelpers.classPrivateFieldGet(_this, _readOnly),
	          handlers: {
	            checked: function checked(event) {
	              event.stopPropagation();
	              _classPrivateMethodGet(_this, _changeCallAssessmentActive, _changeCallAssessmentActive2).call(_this, false);
	            },
	            unchecked: function unchecked(event) {
	              event.stopPropagation();
	              _classPrivateMethodGet(_this, _changeCallAssessmentActive, _changeCallAssessmentActive2).call(_this, true);
	            }
	          }
	        });
	        switcher.renderTo(babelHelpers.classPrivateFieldGet(_this, _targetNode));
	      });
	    }
	  }]);
	  return ActiveField;
	}();
	function _changeCallAssessmentActive2(isEnabled) {
	  var _this2 = this;
	  main_core.Runtime.throttle(function () {
	    main_core.ajax.runAction('crm.copilot.callassessment.active', {
	      data: {
	        id: babelHelpers.classPrivateFieldGet(_this2, _id),
	        isEnabled: isEnabled ? 'Y' : 'N'
	      }
	    })["catch"](function (response) {
	      ui_notification.UI.Notification.Center.notify({
	        content: main_core.Text.encode(response.errors[0].message),
	        autoHideDelay: 6000
	      });
	      throw response;
	    });
	  }, 100)();
	}
	namespace.ActiveField = ActiveField;

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace$1 = main_core.Reflection.namespace('BX.Crm.Copilot.CallAssessmentList');
	var _grid = /*#__PURE__*/new WeakMap();
	var _reloadGridTimeoutId = /*#__PURE__*/new WeakMap();
	var _bindEvents = /*#__PURE__*/new WeakSet();
	var _handleItemDelete = /*#__PURE__*/new WeakSet();
	var _showError = /*#__PURE__*/new WeakSet();
	var _reloadGridAfterTimeout = /*#__PURE__*/new WeakSet();
	var Grid = /*#__PURE__*/function () {
	  function Grid(gridId) {
	    babelHelpers.classCallCheck(this, Grid);
	    _classPrivateMethodInitSpec$1(this, _reloadGridAfterTimeout);
	    _classPrivateMethodInitSpec$1(this, _showError);
	    _classPrivateMethodInitSpec$1(this, _handleItemDelete);
	    _classPrivateMethodInitSpec$1(this, _bindEvents);
	    _classPrivateFieldInitSpec$1(this, _grid, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$1(this, _reloadGridTimeoutId, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _grid, BX.Main.gridManager.getInstanceById(gridId));
	  }
	  babelHelpers.createClass(Grid, [{
	    key: "init",
	    value: function init() {
	      _classPrivateMethodGet$1(this, _bindEvents, _bindEvents2).call(this);
	    }
	  }]);
	  return Grid;
	}();
	function _bindEvents2() {
	  main_core_events.EventEmitter.subscribe('BX.Crm.Copilot.CallAssessment:onClickDelete', _classPrivateMethodGet$1(this, _handleItemDelete, _handleItemDelete2).bind(this));
	}
	function _handleItemDelete2(event) {
	  var _this = this;
	  var id = main_core.Text.toInteger(event.data.id);
	  if (!id) {
	    _classPrivateMethodGet$1(this, _showError, _showError2).call(this, main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_NOT_FOUND'));
	    return;
	  }
	  ui_dialogs_messagebox.MessageBox.show({
	    title: main_core.Loc.getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_TITLE'),
	    message: main_core.Loc.getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_MESSAGE'),
	    modal: true,
	    buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	    onYes: function onYes(messageBox) {
	      main_core.ajax.runAction('crm.controller.copilot.callassessment.delete', {
	        data: {
	          id: id
	        }
	      }).then(function (response) {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CRM_TYPE_ITEM_DELETE_NOTIFICATION')
	        });
	        _classPrivateMethodGet$1(_this, _reloadGridAfterTimeout, _reloadGridAfterTimeout2).call(_this);
	      })["catch"](function (_ref) {
	        var _errors$0$message, _errors$;
	        var errors = _ref.errors;
	        _classPrivateMethodGet$1(_this, _showError, _showError2).call(_this, (_errors$0$message = (_errors$ = errors[0]) === null || _errors$ === void 0 ? void 0 : _errors$.message) !== null && _errors$0$message !== void 0 ? _errors$0$message : main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_ITEM_DELETE_ERROR'));
	      });
	      messageBox.close();
	    }
	  });
	}
	function _showError2(message) {
	  ui_notification.UI.Notification.Center.notify({
	    content: main_core.Text.encode(message),
	    autoHideDelay: 6000
	  });
	}
	function _reloadGridAfterTimeout2() {
	  var _this2 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _grid)) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _reloadGridTimeoutId) > 0) {
	    clearTimeout(babelHelpers.classPrivateFieldGet(this, _reloadGridTimeoutId));
	    babelHelpers.classPrivateFieldSet(this, _reloadGridTimeoutId, 0);
	  }
	  babelHelpers.classPrivateFieldSet(this, _reloadGridTimeoutId, setTimeout(function () {
	    babelHelpers.classPrivateFieldGet(_this2, _grid).reload();
	  }, 1000));
	}
	namespace$1.Grid = Grid;

	exports.ActiveField = ActiveField;
	exports.Grid = Grid;

}((this.window = this.window || {}),BX,BX.Event,BX.UI.Dialogs,BX));
//# sourceMappingURL=script.js.map
