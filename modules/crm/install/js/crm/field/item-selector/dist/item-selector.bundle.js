/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_popup,main_core_events,main_core,ui_buttons) {
	'use strict';

	var DEFAULT_CLASS = 'crm-field-item-selector__add-btn';
	var ItemSelectorButtonState = Object.freeze({
	  ADD: 'add',
	  MORE_ADD: 'more-add',
	  COUNTER_ADD: 'counter-add'
	});
	var ItemSelectorButton = /*#__PURE__*/function (_Button) {
	  babelHelpers.inherits(ItemSelectorButton, _Button);
	  function ItemSelectorButton(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, ItemSelectorButton);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemSelectorButton).call(this, options));
	    main_core.Dom.addClass(_this.getContainer(), DEFAULT_CLASS);
	    return _this;
	  }
	  babelHelpers.createClass(ItemSelectorButton, [{
	    key: "getDefaultOptions",
	    value: function getDefaultOptions() {
	      return {
	        id: "item-selector-button-".concat(main_core.Text.getRandom()),
	        text: main_core.Loc.getMessage('CRM_FIELD_ITEM_SELECTOR_DEFAULT_ADD_BUTTON_LABEL'),
	        tag: ui_buttons.Button.Tag.SPAN,
	        size: ui_buttons.Button.Size.EXTRA_SMALL,
	        color: ui_buttons.Button.Color.LIGHT,
	        round: true,
	        dropdown: true
	      };
	    }
	  }, {
	    key: "applyState",
	    value: function applyState(state) {
	      var _Loc$getMessage;
	      var counter = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      switch (state) {
	        case ItemSelectorButtonState.MORE_ADD:
	          this.setText(main_core.Loc.getMessage('CRM_FIELD_ITEM_SELECTOR_DEFAULT_MORE_BUTTON_LABEL'));
	          break;
	        case ItemSelectorButtonState.COUNTER_ADD:
	          this.setText((_Loc$getMessage = main_core.Loc.getMessage('CRM_FIELD_ITEM_SELECTOR_DEFAULT_MORE_COUNTER_BUTTON_LABEL')) === null || _Loc$getMessage === void 0 ? void 0 : _Loc$getMessage.replace('#COUNTER#', counter));
	          break;
	        case ItemSelectorButtonState.ADD:
	        default:
	          this.setText(main_core.Loc.getMessage('CRM_FIELD_ITEM_SELECTOR_DEFAULT_ADD_BUTTON_LABEL'));
	          break;
	      }
	    }
	  }]);
	  return ItemSelectorButton;
	}(ui_buttons.Button);

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var MENU_ITEM_CLASS_ACTIVE = 'menu-popup-item-accept';
	var MENU_ITEM_CLASS_INACTIVE = 'menu-popup-item-none';
	var Events = {
	  EVENT_ITEMSELECTOR_VALUE_CHANGE: 'crm.field.itemselector:change'
	};
	var _id = /*#__PURE__*/new WeakMap();
	var _target = /*#__PURE__*/new WeakMap();
	var _valuesList = /*#__PURE__*/new WeakMap();
	var _selectedValues = /*#__PURE__*/new WeakMap();
	var _readonlyMode = /*#__PURE__*/new WeakMap();
	var _containerEl = /*#__PURE__*/new WeakMap();
	var _selectedElementList = /*#__PURE__*/new WeakMap();
	var _selectedHiddenElementList = /*#__PURE__*/new WeakMap();
	var _selectedValueWrapperEl = /*#__PURE__*/new WeakMap();
	var _valuesMenuPopup = /*#__PURE__*/new WeakMap();
	var _addButton = /*#__PURE__*/new WeakMap();
	var _create = /*#__PURE__*/new WeakSet();
	var _getAddButtonEl = /*#__PURE__*/new WeakSet();
	var _animateAdd = /*#__PURE__*/new WeakSet();
	var _animateRemove = /*#__PURE__*/new WeakSet();
	var _applyAddButtonState = /*#__PURE__*/new WeakSet();
	var _bindEvents = /*#__PURE__*/new WeakSet();
	var _onShowPopup = /*#__PURE__*/new WeakSet();
	var _onRemoveValue = /*#__PURE__*/new WeakSet();
	var _onMenuItemClick = /*#__PURE__*/new WeakSet();
	var _onWindowResize = /*#__PURE__*/new WeakSet();
	var _emitEvent = /*#__PURE__*/new WeakSet();
	var _assertValidParams = /*#__PURE__*/new WeakSet();
	var _applyCurrentValue = /*#__PURE__*/new WeakSet();
	var _isValueSelected = /*#__PURE__*/new WeakSet();
	var _isTargetOverflown = /*#__PURE__*/new WeakSet();
	var ItemSelector = /*#__PURE__*/function () {
	  // options

	  // local

	  function ItemSelector(_params) {
	    babelHelpers.classCallCheck(this, ItemSelector);
	    _classPrivateMethodInitSpec(this, _isTargetOverflown);
	    _classPrivateMethodInitSpec(this, _isValueSelected);
	    _classPrivateMethodInitSpec(this, _applyCurrentValue);
	    _classPrivateMethodInitSpec(this, _assertValidParams);
	    _classPrivateMethodInitSpec(this, _emitEvent);
	    _classPrivateMethodInitSpec(this, _onWindowResize);
	    _classPrivateMethodInitSpec(this, _onMenuItemClick);
	    _classPrivateMethodInitSpec(this, _onRemoveValue);
	    _classPrivateMethodInitSpec(this, _onShowPopup);
	    _classPrivateMethodInitSpec(this, _bindEvents);
	    _classPrivateMethodInitSpec(this, _applyAddButtonState);
	    _classPrivateMethodInitSpec(this, _animateRemove);
	    _classPrivateMethodInitSpec(this, _animateAdd);
	    _classPrivateMethodInitSpec(this, _getAddButtonEl);
	    _classPrivateMethodInitSpec(this, _create);
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
	      value: []
	    });
	    _classPrivateFieldInitSpec(this, _readonlyMode, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _containerEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _selectedElementList, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(this, _selectedHiddenElementList, {
	      writable: true,
	      value: {}
	    });
	    _classPrivateFieldInitSpec(this, _selectedValueWrapperEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _valuesMenuPopup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _addButton, {
	      writable: true,
	      value: null
	    });
	    _classPrivateMethodGet(this, _assertValidParams, _assertValidParams2).call(this, _params);
	    babelHelpers.classPrivateFieldSet(this, _id, _params.id || "item-selector-".concat(main_core.Text.getRandom()));
	    babelHelpers.classPrivateFieldSet(this, _target, main_core.Type.isDomNode(_params.target) ? _params.target : null);
	    babelHelpers.classPrivateFieldSet(this, _valuesList, main_core.Type.isArrayFilled(_params.valuesList) ? _params.valuesList : []);
	    babelHelpers.classPrivateFieldSet(this, _selectedValues, main_core.Type.isArrayFilled(_params.selectedValues) ? _params.selectedValues : []);
	    babelHelpers.classPrivateFieldSet(this, _readonlyMode, _params.readonlyMode === true);
	    _classPrivateMethodGet(this, _create, _create2).call(this);
	    _classPrivateMethodGet(this, _bindEvents, _bindEvents2).call(this);
	    _classPrivateMethodGet(this, _applyCurrentValue, _applyCurrentValue2).call(this, 100);
	  }

	  // region Data management
	  babelHelpers.createClass(ItemSelector, [{
	    key: "getValue",
	    value: function getValue() {
	      return babelHelpers.classPrivateFieldGet(this, _selectedValues);
	    }
	  }, {
	    key: "setValue",
	    value: function setValue(values) {
	      var _this = this;
	      var isEmitEvent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      this.clearAll();
	      values.forEach(function (value) {
	        _this.addValue(value, isEmitEvent);
	      });
	    }
	  }, {
	    key: "addValue",
	    value: function addValue(value) {
	      var isEmitEvent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      var rawValue = babelHelpers.classPrivateFieldGet(this, _valuesList).find(function (element) {
	        return element.id.toString() === value.toString();
	      });
	      if (!rawValue) {
	        return;
	      }
	      var itemEl = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"crm-field-item-selector__value\">\n\t\t\t\t<span class=\"crm-field-item-selector__value-title\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</span>\n\t\t"])), main_core.Text.encode(rawValue.title));
	      if (!babelHelpers.classPrivateFieldGet(this, _readonlyMode)) {
	        main_core.Dom.append(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"crm-field-item-selector__value-clear-icon\" data-item-selector-id=\"", "\"/>\n\t\t\t\t\t</span>\n\t\t\t\t"])), rawValue.id), itemEl);
	      }
	      main_core.Dom.append(itemEl, babelHelpers.classPrivateFieldGet(this, _selectedValueWrapperEl));
	      var itemElWidth = itemEl.offsetWidth;
	      main_core.Dom.addClass(itemEl, '--hidden');
	      if (_classPrivateMethodGet(this, _isTargetOverflown, _isTargetOverflown2).call(this, itemElWidth)) {
	        babelHelpers.classPrivateFieldGet(this, _selectedHiddenElementList)[rawValue.id] = itemEl;
	      } else {
	        _classPrivateMethodGet(this, _animateAdd, _animateAdd2).call(this, itemEl); // add animation
	      }

	      babelHelpers.classPrivateFieldGet(this, _selectedValues).push(rawValue.id);
	      babelHelpers.classPrivateFieldGet(this, _selectedElementList)[rawValue.id] = itemEl;
	      _classPrivateMethodGet(this, _applyAddButtonState, _applyAddButtonState2).call(this, itemElWidth);
	      if (isEmitEvent) {
	        _classPrivateMethodGet(this, _emitEvent, _emitEvent2).call(this);
	      }
	    }
	  }, {
	    key: "removeValue",
	    value: function removeValue(value) {
	      var isEmitEvent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      if (babelHelpers.classPrivateFieldGet(this, _selectedElementList)[value] && main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _selectedElementList)[value])) {
	        _classPrivateMethodGet(this, _animateRemove, _animateRemove2).call(this, babelHelpers.classPrivateFieldGet(this, _selectedElementList)[value]);
	        main_core.Dom.remove(babelHelpers.classPrivateFieldGet(this, _selectedElementList)[value]);
	        delete babelHelpers.classPrivateFieldGet(this, _selectedElementList)[value];
	      }
	      babelHelpers.classPrivateFieldSet(this, _selectedValues, babelHelpers.classPrivateFieldGet(this, _selectedValues).filter(function (item) {
	        return item.toString() !== value.toString();
	      }));
	      var isHiddenElementNeedApply = babelHelpers.classPrivateFieldGet(this, _selectedHiddenElementList)[value] && main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _selectedHiddenElementList)[value]);
	      if (isHiddenElementNeedApply) {
	        delete babelHelpers.classPrivateFieldGet(this, _selectedHiddenElementList)[value];
	      }
	      if (!_classPrivateMethodGet(this, _isTargetOverflown, _isTargetOverflown2).call(this) || isHiddenElementNeedApply) {
	        var itemEl = Object.values(babelHelpers.classPrivateFieldGet(this, _selectedHiddenElementList))[0];
	        if (main_core.Type.isDomNode(itemEl) && !_classPrivateMethodGet(this, _isTargetOverflown, _isTargetOverflown2).call(this, itemEl.offsetWidth)) {
	          _classPrivateMethodGet(this, _animateAdd, _animateAdd2).call(this, itemEl);
	          delete babelHelpers.classPrivateFieldGet(this, _selectedHiddenElementList)[Object.keys(babelHelpers.classPrivateFieldGet(this, _selectedHiddenElementList))[0]];
	        }
	      }
	      _classPrivateMethodGet(this, _applyAddButtonState, _applyAddButtonState2).call(this);
	      if (isEmitEvent) {
	        _classPrivateMethodGet(this, _emitEvent, _emitEvent2).call(this);
	      }
	    }
	  }, {
	    key: "clearAll",
	    value: function clearAll() {
	      var _this2 = this;
	      if (!main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldGet(this, _selectedValues))) {
	        return;
	      }
	      babelHelpers.classPrivateFieldGet(this, _selectedValues).forEach(function (value) {
	        return _this2.removeValue(value);
	      });
	      babelHelpers.classPrivateFieldSet(this, _selectedValues, []);
	      babelHelpers.classPrivateFieldSet(this, _selectedElementList, {});
	      babelHelpers.classPrivateFieldSet(this, _selectedHiddenElementList, {});
	    } // endregion
	    // region DOM management
	    // endregion
	  }]);
	  return ItemSelector;
	}();
	function _create2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _target)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _containerEl, main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-field-item-selector crm-field-item-selector__scope\"></div>"]))));
	  babelHelpers.classPrivateFieldSet(this, _selectedValueWrapperEl, main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-field-item-selector__values\"></span>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _selectedValueWrapperEl), babelHelpers.classPrivateFieldGet(this, _containerEl));
	  if (!babelHelpers.classPrivateFieldGet(this, _readonlyMode)) {
	    babelHelpers.classPrivateFieldSet(this, _addButton, new ItemSelectorButton());
	    main_core.Dom.append(_classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this), babelHelpers.classPrivateFieldGet(this, _containerEl));
	  }
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _containerEl), babelHelpers.classPrivateFieldGet(this, _target));
	}
	function _getAddButtonEl2() {
	  var _babelHelpers$classPr;
	  return (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _addButton)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.getContainer();
	}
	function _animateAdd2(element) {
	  main_core.Dom.removeClass(element, ['--hidden', '--removing']);
	  main_core.Dom.addClass(element, '--adding');
	}
	function _animateRemove2(element) {
	  main_core.Dom.removeClass(element, '--adding');
	  main_core.Dom.addClass(element, '--removing');
	}
	function _applyAddButtonState2() {
	  var portion = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	  if (!main_core.Type.isDomNode(_classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this))) {
	    return;
	  }
	  var hiddenElementsCnt = Object.keys(babelHelpers.classPrivateFieldGet(this, _selectedHiddenElementList)).length;
	  if (babelHelpers.classPrivateFieldGet(this, _selectedValues).length === 0) {
	    babelHelpers.classPrivateFieldGet(this, _addButton).applyState(ItemSelectorButtonState.ADD);
	  } else if (_classPrivateMethodGet(this, _isTargetOverflown, _isTargetOverflown2).call(this, portion) && hiddenElementsCnt > 0) {
	    babelHelpers.classPrivateFieldGet(this, _addButton).applyState(ItemSelectorButtonState.COUNTER_ADD, hiddenElementsCnt);
	  } else {
	    babelHelpers.classPrivateFieldGet(this, _addButton).applyState(ItemSelectorButtonState.MORE_ADD);
	  }
	}
	function _bindEvents2() {
	  if (main_core.Type.isDomNode(_classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this))) {
	    main_core.Event.bind(_classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this), 'click', _classPrivateMethodGet(this, _onShowPopup, _onShowPopup2).bind(this));
	  }
	  if (main_core.Type.isDomNode(babelHelpers.classPrivateFieldGet(this, _selectedValueWrapperEl))) {
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _selectedValueWrapperEl), 'click', _classPrivateMethodGet(this, _onRemoveValue, _onRemoveValue2).bind(this));
	  }
	  main_core.Event.unbind(window, 'resize', _classPrivateMethodGet(this, _onWindowResize, _onWindowResize2));
	  main_core.Event.bind(window, 'resize', _classPrivateMethodGet(this, _onWindowResize, _onWindowResize2).bind(this));
	}
	function _onShowPopup2(event) {
	  var _this3 = this;
	  var menuItems = babelHelpers.classPrivateFieldGet(this, _valuesList).map(function (item) {
	    return {
	      id: "item-selector-menu-id-".concat(item.id),
	      text: main_core.Text.encode(item.title),
	      className: _classPrivateMethodGet(_this3, _isValueSelected, _isValueSelected2).call(_this3, item.id) ? MENU_ITEM_CLASS_ACTIVE : MENU_ITEM_CLASS_INACTIVE,
	      onclick: _classPrivateMethodGet(_this3, _onMenuItemClick, _onMenuItemClick2).bind(_this3, item.id)
	    };
	  });
	  var menuParams = {
	    closeByEsc: true,
	    autoHide: true,
	    offsetTop: 0,
	    offsetLeft: _classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this).offsetWidth - 16,
	    angle: true,
	    cacheable: false
	  };
	  babelHelpers.classPrivateFieldSet(this, _valuesMenuPopup, main_popup.MenuManager.create(babelHelpers.classPrivateFieldGet(this, _id), _classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this), menuItems, menuParams));
	  babelHelpers.classPrivateFieldGet(this, _valuesMenuPopup).show();
	}
	function _onRemoveValue2(event) {
	  var target = event.target || event.srcElement;
	  var itemIdToRemove = target.getAttribute('data-item-selector-id');
	  if (main_core.Type.isNull(itemIdToRemove)) {
	    return; // nothing to do
	  }

	  if (_classPrivateMethodGet(this, _isValueSelected, _isValueSelected2).call(this, itemIdToRemove)) {
	    this.removeValue(itemIdToRemove, true);
	  }
	}
	function _onMenuItemClick2(value, event, item) {
	  if (_classPrivateMethodGet(this, _isValueSelected, _isValueSelected2).call(this, value)) {
	    this.removeValue(value, true);
	    main_core.Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
	    main_core.Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
	  } else {
	    this.addValue(value, true);
	    main_core.Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
	    main_core.Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
	  }
	}
	function _onWindowResize2() {
	  _classPrivateMethodGet(this, _applyCurrentValue, _applyCurrentValue2).call(this, 750);
	}
	function _emitEvent2() {
	  main_core_events.EventEmitter.emit(this, Events.EVENT_ITEMSELECTOR_VALUE_CHANGE, {
	    value: this.getValue()
	  });
	}
	function _assertValidParams2(params) {
	  if (!main_core.Type.isPlainObject(params)) {
	    throw new TypeError('BX.Crm.Field.ItemSelector: The "params" argument must be object');
	  }
	  if (!main_core.Type.isDomNode(params.target)) {
	    throw new Error('BX.Crm.Field.ItemSelector: The "target" argument must be DOM node');
	  }
	  if (!main_core.Type.isArrayFilled(params.valuesList)) {
	    throw new Error('BX.Crm.Field.ItemSelector: The "valuesList" argument must be filled');
	  }
	}
	function _applyCurrentValue2(delay) {
	  var _this4 = this;
	  main_core.Runtime.debounce(function () {
	    _this4.setValue(babelHelpers.classPrivateFieldGet(_this4, _selectedValues) || []);
	  }, delay, this)();
	}
	function _isValueSelected2(value) {
	  return !main_core.Type.isUndefined(babelHelpers.classPrivateFieldGet(this, _selectedValues).find(function (item) {
	    return item.toString() === value.toString();
	  }));
	}
	function _isTargetOverflown2() {
	  var portion = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	  if (babelHelpers.classPrivateFieldGet(this, _readonlyMode)) {
	    return false;
	  }
	  var targetWidth = babelHelpers.classPrivateFieldGet(this, _target).offsetWidth;
	  var selectedValuesWidth = babelHelpers.classPrivateFieldGet(this, _selectedValueWrapperEl).offsetWidth;
	  var addBtnWidth = _classPrivateMethodGet(this, _getAddButtonEl, _getAddButtonEl2).call(this).offsetWidth;
	  var result = targetWidth - (selectedValuesWidth + addBtnWidth + portion);
	  return result <= 20;
	}

	exports.Events = Events;
	exports.ItemSelector = ItemSelector;

}((this.BX.Crm.Field = this.BX.Crm.Field || {}),BX.Main,BX.Event,BX,BX.UI));
//# sourceMappingURL=item-selector.bundle.js.map
