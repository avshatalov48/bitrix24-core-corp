/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_timeline_tools,main_core,main_core_events,main_date,main_popup,ui_notification) {
	'use strict';

	var _templateObject;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var MENU_ITEM_CLASS_ACTIVE = 'menu-popup-item-accept';
	var MENU_ITEM_CLASS_INACTIVE = 'menu-popup-item-none';
	var MENU_ITEM_CLASS_ARROW = 'crm-field-ping-selector-arrow';
	var MENU_ITEM_SHOW_CALENDAR_ID = 'item-selector-menu-id-custom-calendar';
	var CompactIcons = {
	  NONE: null,
	  BELL: 'bell'
	};
	var PingSelectorEvents = {
	  EVENT_PINGSELECTOR_OPEN: 'crm.field.pingselector:open',
	  EVENT_PINGSELECTOR_VALUE_CHANGE: 'crm.field.pingselector:change'
	};
	var _id = /*#__PURE__*/new WeakMap();
	var _target = /*#__PURE__*/new WeakMap();
	var _valuesList = /*#__PURE__*/new WeakMap();
	var _selectedValues = /*#__PURE__*/new WeakMap();
	var _readonlyMode = /*#__PURE__*/new WeakMap();
	var _icon = /*#__PURE__*/new WeakMap();
	var _deadline = /*#__PURE__*/new WeakMap();
	var _selectedValueWrapperEl = /*#__PURE__*/new WeakMap();
	var _valuesMenuPopup = /*#__PURE__*/new WeakMap();
	var _addButtonCompact = /*#__PURE__*/new WeakMap();
	var _addValue = /*#__PURE__*/new WeakSet();
	var _removeValue = /*#__PURE__*/new WeakSet();
	var _create = /*#__PURE__*/new WeakSet();
	var _adjustAddButtonCompact = /*#__PURE__*/new WeakSet();
	var _getAddButtonEl = /*#__PURE__*/new WeakSet();
	var _bindEvents = /*#__PURE__*/new WeakSet();
	var _onShowPopup = /*#__PURE__*/new WeakSet();
	var _getPreparedMenuItems = /*#__PURE__*/new WeakSet();
	var _getPreparedMenuItem = /*#__PURE__*/new WeakSet();
	var _getCalendarMenuItem = /*#__PURE__*/new WeakSet();
	var _showCalendar = /*#__PURE__*/new WeakSet();
	var _addCustomValue = /*#__PURE__*/new WeakSet();
	var _getOffsetTitle = /*#__PURE__*/new WeakSet();
	var _close = /*#__PURE__*/new WeakSet();
	var _onRemoveValue = /*#__PURE__*/new WeakSet();
	var _onMenuItemClick = /*#__PURE__*/new WeakSet();
	var _onWindowResize = /*#__PURE__*/new WeakSet();
	var _emitEvent = /*#__PURE__*/new WeakSet();
	var _assertValidParams = /*#__PURE__*/new WeakSet();
	var _applyCurrentValue = /*#__PURE__*/new WeakSet();
	var _isValueSelected = /*#__PURE__*/new WeakSet();
	var PingSelector = /*#__PURE__*/function () {
	  function PingSelector(_params) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, PingSelector);
	    _classPrivateMethodInitSpec(this, _isValueSelected);
	    _classPrivateMethodInitSpec(this, _applyCurrentValue);
	    _classPrivateMethodInitSpec(this, _assertValidParams);
	    _classPrivateMethodInitSpec(this, _emitEvent);
	    _classPrivateMethodInitSpec(this, _onWindowResize);
	    _classPrivateMethodInitSpec(this, _onMenuItemClick);
	    _classPrivateMethodInitSpec(this, _onRemoveValue);
	    _classPrivateMethodInitSpec(this, _close);
	    _classPrivateMethodInitSpec(this, _getOffsetTitle);
	    _classPrivateMethodInitSpec(this, _addCustomValue);
	    _classPrivateMethodInitSpec(this, _showCalendar);
	    _classPrivateMethodInitSpec(this, _getCalendarMenuItem);
	    _classPrivateMethodInitSpec(this, _getPreparedMenuItem);
	    _classPrivateMethodInitSpec(this, _getPreparedMenuItems);
	    _classPrivateMethodInitSpec(this, _onShowPopup);
	    _classPrivateMethodInitSpec(this, _bindEvents);
	    _classPrivateMethodInitSpec(this, _getAddButtonEl);
	    _classPrivateMethodInitSpec(this, _adjustAddButtonCompact);
	    _classPrivateMethodInitSpec(this, _create);
	    _classPrivateMethodInitSpec(this, _removeValue);
	    _classPrivateMethodInitSpec(this, _addValue);
	    _classPrivateFieldInitSpec(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _target, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _valuesList, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(this, _selectedValues, {
	      writable: true,
	      value: new Set()
	    });
	    _classPrivateFieldInitSpec(this, _readonlyMode, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _icon, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _deadline, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _selectedValueWrapperEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _valuesMenuPopup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _addButtonCompact, {
	      writable: true,
	      value: null
	    });
	    _classPrivateMethodGet(this, _assertValidParams, _assertValidParams2).call(this, _params);
	    babelHelpers.classPrivateFieldSet(this, _id, _params.id || "ping-selector-".concat(main_core.Text.getRandom()));
	    babelHelpers.classPrivateFieldSet(this, _target, main_core.Type.isDomNode(_params.target) ? _params.target : null);
	    babelHelpers.classPrivateFieldSet(this, _valuesList, main_core.Type.isArrayFilled(_params.valuesList) ? _params.valuesList.map(function (item) {
	      return _objectSpread(_objectSpread({}, item), {}, {
	        id: item.id.toString()
	      });
	    }) : []);
	    if (main_core.Type.isArrayFilled(_params.selectedValues)) {
	      _params.selectedValues.forEach(function (selectedValue) {
	        return babelHelpers.classPrivateFieldGet(_this, _selectedValues).add(selectedValue.toString());
	      });
	    }
	    babelHelpers.classPrivateFieldSet(this, _readonlyMode, _params.readonlyMode === true);
	    babelHelpers.classPrivateFieldSet(this, _deadline, main_core.Type.isDate(_params === null || _params === void 0 ? void 0 : _params.deadline) ? _params.deadline : new Date());
	    babelHelpers.classPrivateFieldGet(this, _deadline).setSeconds(0);
	    if (main_core.Type.isStringFilled(_params.icon) && Object.values(CompactIcons).includes(_params.icon)) {
	      babelHelpers.classPrivateFieldSet(this, _icon, _params.icon);
	    }
	    _classPrivateMethodGet(this, _create, _create2).call(this);
	    _classPrivateMethodGet(this, _bindEvents, _bindEvents2).call(this);
	    _classPrivateMethodGet(this, _applyCurrentValue, _applyCurrentValue2).call(this, 100);
	  }
	  babelHelpers.createClass(PingSelector, [{
	    key: "setDeadline",
	    value: function setDeadline(deadline) {
	      babelHelpers.classPrivateFieldSet(this, _deadline, deadline);
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return babelHelpers.toConsumableArray(babelHelpers.classPrivateFieldGet(this, _selectedValues).values());
	    }
	  }, {
	    key: "setValue",
	    value: function setValue(values) {
	      var _this2 = this;
	      var isEmitEvent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      this.clearAll();
	      values.forEach(function (value) {
	        _classPrivateMethodGet(_this2, _addValue, _addValue2).call(_this2, value, isEmitEvent);
	      });
	    }
	  }, {
	    key: "clearAll",
	    value: function clearAll() {
	      var _this3 = this;
	      if (babelHelpers.classPrivateFieldGet(this, _selectedValues).size === 0) {
	        return;
	      }
	      babelHelpers.classPrivateFieldGet(this, _selectedValues).forEach(function (value) {
	        return _classPrivateMethodGet(_this3, _removeValue, _removeValue2).call(_this3, value);
	      });
	      babelHelpers.classPrivateFieldSet(this, _selectedValues, new Set());
	      _classPrivateMethodGet(this, _adjustAddButtonCompact, _adjustAddButtonCompact2).call(this);
	    }
	  }]);
	  return PingSelector;
	}();
	function _addValue2(value) {
	  var isEmitEvent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	  var rawValue = babelHelpers.classPrivateFieldGet(this, _valuesList).find(function (element) {
	    var _element$id;
	    return (element === null || element === void 0 ? void 0 : (_element$id = element.id) === null || _element$id === void 0 ? void 0 : _element$id.toString()) === (value === null || value === void 0 ? void 0 : value.toString());
	  });
	  if (!rawValue) {
	    return;
	  }
	  babelHelpers.classPrivateFieldGet(this, _selectedValues).add(rawValue.id);
	  _classPrivateMethodGet(this, _adjustAddButtonCompact, _adjustAddButtonCompact2).call(this);
	  if (isEmitEvent) {
	    _classPrivateMethodGet(this, _emitEvent, _emitEvent2).call(this);
	  }
	}
	function _removeValue2(value) {
	  var isEmitEvent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	  babelHelpers.classPrivateFieldGet(this, _selectedValues)["delete"](value);
	  _classPrivateMethodGet(this, _adjustAddButtonCompact, _adjustAddButtonCompact2).call(this);
	  if (isEmitEvent) {
	    _classPrivateMethodGet(this, _emitEvent, _emitEvent2).call(this);
	  }
	}
	function _create2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _target)) {
	    return;
	  }
	  if (!babelHelpers.classPrivateFieldGet(this, _readonlyMode)) {
	    babelHelpers.classPrivateFieldSet(this, _addButtonCompact, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-field-ping-selector-compact-icon ", "\"\n\t\t\t\t></span>\n\t\t\t"])), main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _icon)) ? "--".concat(babelHelpers.classPrivateFieldGet(this, _icon)) : ''));
	    _classPrivateMethodGet(this, _adjustAddButtonCompact, _adjustAddButtonCompact2).call(this);
	  }
	  main_core.Dom.append(_classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this), babelHelpers.classPrivateFieldGet(this, _target));
	}
	function _adjustAddButtonCompact2() {
	  if (babelHelpers.classPrivateFieldGet(this, _selectedValues).size > 0) {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _addButtonCompact), '--empty');
	  } else {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _addButtonCompact), '--empty');
	  }
	}
	function _getAddButtonEl2() {
	  return babelHelpers.classPrivateFieldGet(this, _addButtonCompact);
	}
	function _bindEvents2() {
	  if (main_core.Type.isDomNode(_classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this))) {
	    main_core.Event.bind(_classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this), 'click', _classPrivateMethodGet(this, _onShowPopup, _onShowPopup2).bind(this));
	  }
	  if (main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _addButtonCompact))) {
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _addButtonCompact), 'click', _classPrivateMethodGet(this, _onShowPopup, _onShowPopup2).bind(this));
	  }
	  if (main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _selectedValueWrapperEl))) {
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _selectedValueWrapperEl), 'click', _classPrivateMethodGet(this, _onRemoveValue, _onRemoveValue2).bind(this));
	  }
	  main_core.Event.unbind(window, 'resize', _classPrivateMethodGet(this, _onWindowResize, _onWindowResize2));
	  main_core.Event.bind(window, 'resize', _classPrivateMethodGet(this, _onWindowResize, _onWindowResize2).bind(this));
	}
	function _onShowPopup2() {
	  var menuItems = _classPrivateMethodGet(this, _getPreparedMenuItems, _getPreparedMenuItems2).call(this);

	  // @todo temporary, need other fix
	  var angle = {
	    offset: 29,
	    position: 'top'
	  };
	  var menuParams = {
	    closeByEsc: true,
	    autoHide: true,
	    offsetLeft: _classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this).offsetWidth - 16,
	    angle: angle,
	    cacheable: false
	  };
	  babelHelpers.classPrivateFieldSet(this, _valuesMenuPopup, main_popup.MenuManager.create(babelHelpers.classPrivateFieldGet(this, _id), _classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this), menuItems, menuParams));
	  babelHelpers.classPrivateFieldGet(this, _valuesMenuPopup).show();
	  main_core_events.EventEmitter.emit(this, PingSelectorEvents.EVENT_PINGSELECTOR_OPEN);
	}
	function _getPreparedMenuItems2() {
	  var _this4 = this;
	  var items = babelHelpers.classPrivateFieldGet(this, _valuesList).map(function (item) {
	    return _classPrivateMethodGet(_this4, _getPreparedMenuItem, _getPreparedMenuItem2).call(_this4, item);
	  });
	  items.push(_classPrivateMethodGet(this, _getCalendarMenuItem, _getCalendarMenuItem2).call(this));
	  return items;
	}
	function _getPreparedMenuItem2(item) {
	  return {
	    id: "ping-selector-menu-id-".concat(item.id),
	    className: _classPrivateMethodGet(this, _isValueSelected, _isValueSelected2).call(this, item.id) ? MENU_ITEM_CLASS_ACTIVE : MENU_ITEM_CLASS_INACTIVE,
	    onclick: _classPrivateMethodGet(this, _onMenuItemClick, _onMenuItemClick2).bind(this, item.id),
	    html: main_core.Text.encode(item.title)
	  };
	}
	function _getCalendarMenuItem2() {
	  var _this5 = this;
	  return {
	    id: MENU_ITEM_SHOW_CALENDAR_ID,
	    className: MENU_ITEM_CLASS_ARROW,
	    onclick: function onclick(event) {
	      _classPrivateMethodGet(_this5, _showCalendar, _showCalendar2).call(_this5, event.target);
	    },
	    html: main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_OTHER_TIME')
	  };
	}
	function _showCalendar2(target) {
	  // eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
	  BX.calendar({
	    node: target,
	    bTime: true,
	    bHideTime: false,
	    bSetFocus: false,
	    value: main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(), babelHelpers.classPrivateFieldGet(this, _deadline)),
	    callback: _classPrivateMethodGet(this, _addCustomValue, _addCustomValue2).bind(this)
	  });
	}
	function _addCustomValue2(date) {
	  if (date.getTime() > babelHelpers.classPrivateFieldGet(this, _deadline).getTime()) {
	    _classPrivateMethodGet(this, _close, _close2).call(this);
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_WRONG_TIME'),
	      autoHideDelay: 3000
	    });
	    return;
	  }
	  var offset = Math.floor((babelHelpers.classPrivateFieldGet(this, _deadline).getTime() - date.getTime()) / 1000 / 60);
	  babelHelpers.classPrivateFieldGet(this, _selectedValues).add(offset.toString());
	  var customValue = {
	    id: offset.toString(),
	    title: _classPrivateMethodGet(this, _getOffsetTitle, _getOffsetTitle2).call(this, offset)
	  };
	  babelHelpers.classPrivateFieldGet(this, _valuesList).push(customValue);
	  babelHelpers.classPrivateFieldSet(this, _valuesList, babelHelpers.classPrivateFieldGet(this, _valuesList).sort(function (a, b) {
	    var offset1 = Number(a.id);
	    var offset2 = Number(b.id);
	    return offset1 < offset2 ? -1 : offset1 > offset2 ? 1 : 0;
	  }));
	  _classPrivateMethodGet(this, _close, _close2).call(this);
	  _classPrivateMethodGet(this, _adjustAddButtonCompact, _adjustAddButtonCompact2).call(this);
	  _classPrivateMethodGet(this, _emitEvent, _emitEvent2).call(this);
	}
	function _getOffsetTitle2(offset) {
	  var minutesInHour = 60;
	  var days = Math.floor(offset / (minutesInHour * 24));
	  var daysString = null;
	  if (days > 0) {
	    daysString = main_core.Loc.getMessagePlural('CRM_FIELD_PING_SELECTOR_DAY', days, {
	      '#COUNT#': days
	    });
	  }
	  var hours = Math.floor(offset % (minutesInHour * 24) / minutesInHour);
	  var hoursString = null;
	  if (hours > 0) {
	    hoursString = main_core.Loc.getMessagePlural('CRM_FIELD_PING_SELECTOR_HOUR', hours, {
	      '#COUNT#': hours
	    });
	  }
	  var minutes = Math.floor(offset % minutesInHour);
	  var minutesString = null;
	  if (minutes > 0) {
	    minutesString = main_core.Loc.getMessagePlural('CRM_FIELD_PING_SELECTOR_MINUTE', minutes, {
	      '#COUNT#': minutes
	    });
	  }
	  if (days > 0 && hours > 0 && minutes > 0) {
	    return main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_DAY_HOUR_MINUTE_TITLE', {
	      '#DAYS#': daysString,
	      '#HOURS#': hoursString,
	      '#MINUTES#': minutesString
	    });
	  }
	  if (days > 0 && hours > 0) {
	    return main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_DAY_HOUR_TITLE', {
	      '#DAYS#': daysString,
	      '#HOURS#': hoursString
	    });
	  }
	  if (days > 0 && minutes > 0) {
	    return main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_DAY_MINUTE_TITLE', {
	      '#DAYS#': daysString,
	      '#MINUTES#': minutesString
	    });
	  }
	  if (days > 0) {
	    return main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_DAY_TITLE', {
	      '#DAYS#': daysString
	    });
	  }
	  if (hours > 0 && minutes > 0) {
	    return main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_HOUR_MINUTE_TITLE', {
	      '#HOURS#': hoursString,
	      '#MINUTES#': minutesString
	    });
	  }
	  if (hours > 0) {
	    return main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_HOUR_TITLE', {
	      '#HOURS#': hoursString
	    });
	  }
	  return main_core.Loc.getMessage('CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_MINUTE_TITLE', {
	    '#MINUTES#': minutesString
	  });
	}
	function _close2() {
	  babelHelpers.classPrivateFieldGet(this, _valuesMenuPopup).close();
	  main_popup.MenuManager.destroy(babelHelpers.classPrivateFieldGet(this, _id));
	}
	function _onRemoveValue2(event) {
	  var target = event.target || event.srcElement;
	  var itemIdToRemove = target.getAttribute('data-ping-selector-id');
	  if (main_core.Type.isNull(itemIdToRemove)) {
	    return; // nothing to do
	  }

	  if (_classPrivateMethodGet(this, _isValueSelected, _isValueSelected2).call(this, itemIdToRemove)) {
	    _classPrivateMethodGet(this, _removeValue, _removeValue2).call(this, itemIdToRemove, true);
	  }
	}
	function _onMenuItemClick2(value, event, item) {
	  if (_classPrivateMethodGet(this, _isValueSelected, _isValueSelected2).call(this, value)) {
	    _classPrivateMethodGet(this, _removeValue, _removeValue2).call(this, value, true);
	    main_core.Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
	    main_core.Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
	  } else {
	    _classPrivateMethodGet(this, _addValue, _addValue2).call(this, value, true);
	    main_core.Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
	    main_core.Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
	  }
	}
	function _onWindowResize2() {
	  _classPrivateMethodGet(this, _applyCurrentValue, _applyCurrentValue2).call(this, 750);
	}
	function _emitEvent2() {
	  main_core_events.EventEmitter.emit(this, PingSelectorEvents.EVENT_PINGSELECTOR_VALUE_CHANGE, {
	    value: this.getValue()
	  });
	}
	function _assertValidParams2(params) {
	  if (!main_core.Type.isPlainObject(params)) {
	    throw new TypeError('BX.Crm.Field.PingSelector: The "params" argument must be object');
	  }
	  if (!main_core.Type.isDomNode(params.target)) {
	    throw new Error('BX.Crm.Field.PingSelector: The "target" argument must be DOM node');
	  }
	  if (!main_core.Type.isArrayFilled(params.valuesList)) {
	    throw new Error('BX.Crm.Field.PingSelector: The "valuesList" argument must be filled');
	  }
	}
	function _applyCurrentValue2(delay) {
	  var _this6 = this;
	  main_core.Runtime.debounce(function () {
	    _this6.setValue(babelHelpers.toConsumableArray(babelHelpers.classPrivateFieldGet(_this6, _selectedValues)) || []);
	  }, delay, this)();
	}
	function _isValueSelected2(value) {
	  return babelHelpers.classPrivateFieldGet(this, _selectedValues).has(value);
	}

	exports.CompactIcons = CompactIcons;
	exports.PingSelectorEvents = PingSelectorEvents;
	exports.PingSelector = PingSelector;

}((this.BX.Crm.Field = this.BX.Crm.Field || {}),BX.Crm.Timeline,BX,BX.Event,BX.Main,BX.Main,BX));
//# sourceMappingURL=ping-selector.bundle.js.map
