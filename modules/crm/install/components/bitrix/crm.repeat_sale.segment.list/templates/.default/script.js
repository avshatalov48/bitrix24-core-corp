/* eslint-disable */
(function (exports,ui_dialogs_messagebox,ui_notification,main_core,ui_progressround) {
	'use strict';

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace = main_core.Reflection.namespace('BX.Crm.RepeatSale.SegmentList');
	var _id = /*#__PURE__*/new WeakMap();
	var _targetNode = /*#__PURE__*/new WeakMap();
	var _checked = /*#__PURE__*/new WeakMap();
	var _readOnly = /*#__PURE__*/new WeakMap();
	var _isFlowDisabled = /*#__PURE__*/new WeakMap();
	var _showMessageBox = /*#__PURE__*/new WeakSet();
	var _showAllFlowEnableMessageBox = /*#__PURE__*/new WeakSet();
	var _changeRepeatSaleSegmentActive = /*#__PURE__*/new WeakSet();
	var ActiveField = /*#__PURE__*/function () {
	  function ActiveField(_ref) {
	    var id = _ref.id,
	      targetNodeId = _ref.targetNodeId,
	      checked = _ref.checked,
	      readOnly = _ref.readOnly,
	      isFlowDisabled = _ref.isFlowDisabled;
	    babelHelpers.classCallCheck(this, ActiveField);
	    _classPrivateMethodInitSpec(this, _changeRepeatSaleSegmentActive);
	    _classPrivateMethodInitSpec(this, _showAllFlowEnableMessageBox);
	    _classPrivateMethodInitSpec(this, _showMessageBox);
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
	    _classPrivateFieldInitSpec(this, _isFlowDisabled, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _id, id);
	    babelHelpers.classPrivateFieldSet(this, _targetNode, document.getElementById(targetNodeId));
	    babelHelpers.classPrivateFieldSet(this, _checked, checked);
	    babelHelpers.classPrivateFieldSet(this, _readOnly, readOnly);
	    babelHelpers.classPrivateFieldSet(this, _isFlowDisabled, isFlowDisabled);
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
	              _classPrivateMethodGet(_this, _showMessageBox, _showMessageBox2).call(_this, function () {
	                _classPrivateMethodGet(_this, _changeRepeatSaleSegmentActive, _changeRepeatSaleSegmentActive2).call(_this, false);
	              }, function () {
	                switcher.check(true, false);
	              });
	            },
	            unchecked: function unchecked(event) {
	              event.stopPropagation();
	              if (babelHelpers.classPrivateFieldGet(_this, _isFlowDisabled)) {
	                _classPrivateMethodGet(_this, _showAllFlowEnableMessageBox, _showAllFlowEnableMessageBox2).call(_this, function () {
	                  _classPrivateMethodGet(_this, _changeRepeatSaleSegmentActive, _changeRepeatSaleSegmentActive2).call(_this, true);
	                  babelHelpers.classPrivateFieldSet(_this, _isFlowDisabled, false);
	                }, function () {
	                  switcher.check(false, false);
	                });
	              } else {
	                _classPrivateMethodGet(_this, _changeRepeatSaleSegmentActive, _changeRepeatSaleSegmentActive2).call(_this, true);
	              }
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
	function _showMessageBox2(_onOk, _onCancel) {
	  var popupContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-repeat-sale-segment-list-confirm-container\">\n\t\t\t\t<div class=\"crm-repeat-sale-segment-list-confirm-message\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_CONFIRM_DIALOG_MESSAGE'));
	  ui_dialogs_messagebox.MessageBox.show({
	    modal: true,
	    minHeight: 100,
	    minWidth: 400,
	    popupOptions: {
	      content: popupContainer,
	      closeIcon: false
	    },
	    buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	    okCaption: main_core.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_CONFIRM_DIALOG_OK_BTN'),
	    onOk: function onOk(messageBox) {
	      messageBox.close();
	      _onOk();
	    },
	    onCancel: function onCancel(messageBox) {
	      _onCancel();
	      messageBox.close();
	    }
	  });
	}
	function _showAllFlowEnableMessageBox2(_onOk2, _onCancel2) {
	  var popupContainer = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-repeat-sale-segment-list-confirm-container\">\n\t\t\t\t<div class=\"crm-repeat-sale-segment-list-confirm-message\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_RS_CONFIRM_DIALOG_MESSAGE'));
	  ui_dialogs_messagebox.MessageBox.show({
	    modal: true,
	    minHeight: 100,
	    minWidth: 400,
	    popupOptions: {
	      content: popupContainer,
	      closeIcon: false
	    },
	    buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	    okCaption: main_core.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_RS_CONFIRM_DIALOG_OK_BTN'),
	    onOk: function onOk(messageBox) {
	      messageBox.close();
	      _onOk2();
	    },
	    onCancel: function onCancel(messageBox) {
	      _onCancel2();
	      messageBox.close();
	    }
	  });
	}
	function _changeRepeatSaleSegmentActive2(isEnabled) {
	  var _this2 = this;
	  main_core.Runtime.throttle(function () {
	    main_core.ajax.runAction('crm.repeatsale.segment.active', {
	      json: {
	        id: babelHelpers.classPrivateFieldGet(_this2, _id),
	        isEnabled: isEnabled ? 'Y' : 'N'
	      },
	      analyticsLabel: {
	        tool: 'crm',
	        category: 'editor',
	        event: 'scenario_enable',
	        c_element: "".concat(isEnabled ? 'on' : 'off')
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

	var _templateObject$1, _templateObject2$1;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var namespace$1 = main_core.Reflection.namespace('BX.Crm.RepeatSale.SegmentList');
	var DEFAULT_BORDER = 'default';
	var LOW_BORDER = 'lowBorder';
	var HIGH_BORDER = 'highBorder';
	var _id$1 = /*#__PURE__*/new WeakMap();
	var _targetNode$1 = /*#__PURE__*/new WeakMap();
	var _borders = /*#__PURE__*/new WeakMap();
	var _value = /*#__PURE__*/new WeakMap();
	var _valueContainer = /*#__PURE__*/new WeakMap();
	var _getTrackColor = /*#__PURE__*/new WeakSet();
	var _getBorderById = /*#__PURE__*/new WeakSet();
	var RoundChartField = /*#__PURE__*/function () {
	  function RoundChartField(_ref) {
	    var _id2 = _ref.id,
	      targetNodeId = _ref.targetNodeId,
	      borders = _ref.borders,
	      value = _ref.value;
	    babelHelpers.classCallCheck(this, RoundChartField);
	    _classPrivateMethodInitSpec$1(this, _getBorderById);
	    _classPrivateMethodInitSpec$1(this, _getTrackColor);
	    _classPrivateFieldInitSpec$1(this, _id$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _targetNode$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _borders, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _value, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _valueContainer, {
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
	      babelHelpers.classPrivateFieldSet(this, _valueContainer, main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	      var content = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-repeat-sale-segment-list-client-percent\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"crm-repeat-sale-segment-list-client-percent-value\">\n\t\t\t\t\t", "\n\t\t\t\t\t<span class=\"crm-repeat-sale-segment-list-client-percent-percent\">%</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _valueContainer), babelHelpers.classPrivateFieldGet(this, _value));
	      main_core.Dom.append(content, babelHelpers.classPrivateFieldGet(this, _targetNode$1));
	      var loader = new ui_progressround.ProgressRound({
	        width: 28,
	        lineSize: 8,
	        colorBar: _classPrivateMethodGet$1(this, _getTrackColor, _getTrackColor2).call(this),
	        colorTrack: '#EBF1F6',
	        rotation: false,
	        value: babelHelpers.classPrivateFieldGet(this, _value),
	        color: ui_progressround.ProgressRound.Color.SUCCESS
	      });
	      loader.renderTo(babelHelpers.classPrivateFieldGet(this, _valueContainer));
	    }
	  }]);
	  return RoundChartField;
	}();
	function _getTrackColor2() {
	  var highBorder = _classPrivateMethodGet$1(this, _getBorderById, _getBorderById2).call(this, HIGH_BORDER);
	  if (highBorder && babelHelpers.classPrivateFieldGet(this, _value) >= (highBorder === null || highBorder === void 0 ? void 0 : highBorder.value)) {
	    return highBorder.color;
	  }
	  var lowBorder = _classPrivateMethodGet$1(this, _getBorderById, _getBorderById2).call(this, LOW_BORDER);
	  if (lowBorder && babelHelpers.classPrivateFieldGet(this, _value) <= (lowBorder === null || lowBorder === void 0 ? void 0 : lowBorder.value)) {
	    return lowBorder.color;
	  }
	  var defaultBorder = _classPrivateMethodGet$1(this, _getBorderById, _getBorderById2).call(this, DEFAULT_BORDER);
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
	namespace$1.RoundChartField = RoundChartField;

	exports.ActiveField = ActiveField;
	exports.RoundChartField = RoundChartField;

}((this.window = this.window || {}),BX.UI.Dialogs,BX,BX,BX.UI));
//# sourceMappingURL=script.js.map
