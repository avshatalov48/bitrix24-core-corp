/* eslint-disable */
(function (exports,main_sidepanel,main_core_events,ui_dialogs_messagebox,ui_notification,main_core,ui_progressround) {
	'use strict';

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var namespace = main_core.Reflection.namespace('BX.Crm.Copilot.CallAssessmentList');
	var _isActive = /*#__PURE__*/new WeakMap();
	var ActionButton = /*#__PURE__*/function () {
	  function ActionButton() {
	    var isActiveCopilot = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	    babelHelpers.classCallCheck(this, ActionButton);
	    _classPrivateFieldInitSpec(this, _isActive, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _isActive, isActiveCopilot);
	  }
	  babelHelpers.createClass(ActionButton, [{
	    key: "execute",
	    value: function execute() {
	      var _top$BX$UI, _top$BX$UI$InfoHelper;
	      if (babelHelpers.classPrivateFieldGet(this, _isActive)) {
	        if (!main_sidepanel.SidePanel.Instance) {
	          console.error('SidePanel.Instance not found');
	          return;
	        }
	        main_sidepanel.SidePanel.Instance.open("/crm/copilot-call-assessment/details/0/", {
	          cacheable: false,
	          width: 700,
	          allowChangeHistory: false
	        });
	        return;
	      }
	      (_top$BX$UI = top.BX.UI) === null || _top$BX$UI === void 0 ? void 0 : (_top$BX$UI$InfoHelper = _top$BX$UI.InfoHelper) === null || _top$BX$UI$InfoHelper === void 0 ? void 0 : _top$BX$UI$InfoHelper.show('limit_v2_crm_copilot_call_assessment_off');
	    }
	  }]);
	  return ActionButton;
	}();
	namespace.ActionButton = ActionButton;

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace$1 = main_core.Reflection.namespace('BX.Crm.Copilot.CallAssessmentList');
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
	    _classPrivateFieldInitSpec$1(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _targetNode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _checked, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _readOnly, {
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
	        main_core.Dom.clean(babelHelpers.classPrivateFieldGet(_this, _targetNode));
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
	namespace$1.ActiveField = ActiveField;

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace$2 = main_core.Reflection.namespace('BX.Crm.Copilot.CallAssessmentList');
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
	    _classPrivateFieldInitSpec$2(this, _grid, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _reloadGridTimeoutId, {
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
	    _classPrivateMethodGet$1(this, _showError, _showError2).call(this, main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_NOT_FOUND_MSGVER_1'));
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
	namespace$2.Grid = Grid;

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace$3 = main_core.Reflection.namespace('BX.Crm.Copilot.CallAssessmentList');
	var DEFAULT_BORDER = 'default';
	var LOW_BORDER = 'lowBorder';
	var HIGH_BORDER = 'highBorder';
	var _id$1 = /*#__PURE__*/new WeakMap();
	var _targetNode$1 = /*#__PURE__*/new WeakMap();
	var _borders = /*#__PURE__*/new WeakMap();
	var _value = /*#__PURE__*/new WeakMap();
	var _valueContainer = /*#__PURE__*/new WeakMap();
	var _bindEvents$1 = /*#__PURE__*/new WeakSet();
	var _getTrackColor = /*#__PURE__*/new WeakSet();
	var _getBorderById = /*#__PURE__*/new WeakSet();
	var _showTooltip = /*#__PURE__*/new WeakSet();
	var _hideTooltip = /*#__PURE__*/new WeakSet();
	var RoundChartField = /*#__PURE__*/function () {
	  function RoundChartField(_ref) {
	    var _id2 = _ref.id,
	      targetNodeId = _ref.targetNodeId,
	      borders = _ref.borders,
	      value = _ref.value;
	    babelHelpers.classCallCheck(this, RoundChartField);
	    _classPrivateMethodInitSpec$2(this, _hideTooltip);
	    _classPrivateMethodInitSpec$2(this, _showTooltip);
	    _classPrivateMethodInitSpec$2(this, _getBorderById);
	    _classPrivateMethodInitSpec$2(this, _getTrackColor);
	    _classPrivateMethodInitSpec$2(this, _bindEvents$1);
	    _classPrivateFieldInitSpec$3(this, _id$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(this, _targetNode$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(this, _borders, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(this, _value, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(this, _valueContainer, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _id$1, _id2);
	    babelHelpers.classPrivateFieldSet(this, _targetNode$1, document.getElementById(targetNodeId));
	    babelHelpers.classPrivateFieldSet(this, _borders, borders !== null && borders !== void 0 ? borders : null);
	    babelHelpers.classPrivateFieldSet(this, _value, value);
	  }
	  babelHelpers.createClass(RoundChartField, [{
	    key: "init",
	    value: function init() {
	      if (babelHelpers.classPrivateFieldGet(this, _value) === null) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _valueContainer, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	      var content = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-copilot-call-assessment-list-assessment-avg\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"crm-copilot-call-assessment-list-assessment-avg-value\">\n\t\t\t\t\t", "\n\t\t\t\t\t<span class=\"crm-copilot-call-assessment-list-assessment-avg-percent\">%</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _valueContainer), babelHelpers.classPrivateFieldGet(this, _value));
	      main_core.Dom.append(content, babelHelpers.classPrivateFieldGet(this, _targetNode$1));
	      var loader = new ui_progressround.ProgressRound({
	        width: 28,
	        lineSize: 8,
	        colorBar: _classPrivateMethodGet$2(this, _getTrackColor, _getTrackColor2).call(this),
	        colorTrack: '#EBF1F6',
	        rotation: false,
	        value: babelHelpers.classPrivateFieldGet(this, _value),
	        color: ui_progressround.ProgressRound.Color.SUCCESS
	      });
	      loader.renderTo(babelHelpers.classPrivateFieldGet(this, _valueContainer));
	      _classPrivateMethodGet$2(this, _bindEvents$1, _bindEvents2$1).call(this, content);
	    }
	  }]);
	  return RoundChartField;
	}();
	function _bindEvents2$1(target) {
	  main_core.Event.bind(target, 'mouseenter', _classPrivateMethodGet$2(this, _showTooltip, _showTooltip2).bind(this));
	  main_core.Event.bind(target, 'mouseleave', _classPrivateMethodGet$2(this, _hideTooltip, _hideTooltip2).bind(this));
	}
	function _getTrackColor2() {
	  var highBorder = _classPrivateMethodGet$2(this, _getBorderById, _getBorderById2).call(this, HIGH_BORDER);
	  if (highBorder && babelHelpers.classPrivateFieldGet(this, _value) >= (highBorder === null || highBorder === void 0 ? void 0 : highBorder.value)) {
	    return highBorder.color;
	  }
	  var lowBorder = _classPrivateMethodGet$2(this, _getBorderById, _getBorderById2).call(this, LOW_BORDER);
	  if (lowBorder && babelHelpers.classPrivateFieldGet(this, _value) <= (lowBorder === null || lowBorder === void 0 ? void 0 : lowBorder.value)) {
	    return lowBorder.color;
	  }
	  var defaultBorder = _classPrivateMethodGet$2(this, _getBorderById, _getBorderById2).call(this, DEFAULT_BORDER);
	  if (defaultBorder) {
	    return defaultBorder.color;
	  }
	  throw new RangeError('unknown track color');
	}
	function _getBorderById2(id) {
	  var _babelHelpers$classPr;
	  return (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _borders).find(function (border) {
	    return border.id === id;
	  })) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : null;
	}
	function _showTooltip2(event) {
	  var lowBorder = _classPrivateMethodGet$2(this, _getBorderById, _getBorderById2).call(this, LOW_BORDER);
	  var highBorder = _classPrivateMethodGet$2(this, _getBorderById, _getBorderById2).call(this, HIGH_BORDER);
	  main_core.Runtime.debounce(function () {
	    BX.UI.Hint.show(event.target, main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_ASSESSMENT_AVG_TOOLTIP', {
	      '#LOW_BORDER#': lowBorder.value,
	      '#HIGH_BORDER#': highBorder.value
	    }), true);
	  }, 50, this)();
	}
	function _hideTooltip2(event) {
	  BX.UI.Hint.hide(event.target);
	}
	namespace$3.RoundChartField = RoundChartField;

	exports.ActiveField = ActiveField;
	exports.ActionButton = ActionButton;
	exports.Grid = Grid;
	exports.RoundChartField = RoundChartField;

}((this.window = this.window || {}),BX,BX.Event,BX.UI.Dialogs,BX,BX,BX.UI));
//# sourceMappingURL=script.js.map
