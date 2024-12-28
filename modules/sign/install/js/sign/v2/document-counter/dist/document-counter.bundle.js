/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core_events,ui_counterpanel,main_core) {
	'use strict';

	var _filter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filter");
	class Filter {
	  constructor(options) {
	    Object.defineProperty(this, _filter, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter] = BX.Main.filterManager.getById(options.filterId);
	  }
	  toggleField(name, value, resetAllFields) {
	    const field = babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFieldByName(name);
	    if (!main_core.Type.isPlainObject(field) || field === null) {
	      return false;
	    }
	    const items = value.split('_');

	    // eslint-disable-next-line no-shadow

	    const filteredValues = field.ITEMS.filter(item => items.includes(item.VALUE)).map(item => item.VALUE);
	    // const fieldValue = field.ITEMS.find((item) => item.VALUE === value);
	    if (filteredValues.length === 0) {
	      return false;
	    }
	    if (this.isFieldValueAlreadyApplied(name, filteredValues, resetAllFields)) {
	      return false;
	    }
	    if (resetAllFields) {
	      babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getApi().setFields({});
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getApi().extendFilter({
	      [name]: {
	        ...filteredValues
	      }
	    });
	    return true;
	  }
	  getFilterRows() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getFilterFieldsValues();
	  }
	  deactivate() {
	    babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getApi().setFields({});
	    babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getApi().apply();
	  }
	  isFieldValueAlreadyApplied(name, setValues, withoutOtherFilters) {
	    const filterRows = this.getFilterRows();
	    const currentValuesObject = filterRows[name];
	    if (!main_core.Type.isObject(currentValuesObject)) {
	      return false;
	    }
	    if (withoutOtherFilters && this.isSomeOtherFilterPresent(name)) {
	      return false;
	    }
	    const currentValuesArray = Object.values(currentValuesObject);
	    for (const setValue of setValues) {
	      if (!currentValuesArray.includes(setValue)) {
	        return false;
	      }
	    }
	    if (withoutOtherFilters) {
	      for (const currentValue of currentValuesArray) {
	        if (!setValues.includes(currentValue)) {
	          return false;
	        }
	      }
	    }
	    return true;
	  }
	  isSomeOtherFilterPresent(name) {
	    for (const [key, value] of Object.entries(this.getFilterRows())) {
	      const isPresent = main_core.Type.isStringFilled(value) || main_core.Type.isArrayFilled(value);
	      if (key !== name && isPresent) {
	        return true;
	      }
	    }
	    return false;
	  }
	}

	var _filter$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filter");
	var _resetAllFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetAllFields");
	var _onActivateItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActivateItem");
	var _onDeactivateItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDeactivateItem");
	var _processItemSelection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("processItemSelection");
	var _getFieldData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldData");
	var _isAllDeactivated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAllDeactivated");
	var _onFilterApply = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFilterApply");
	var _onCounterUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCounterUpdate");
	class DocumentCounter extends ui_counterpanel.CounterPanel {
	  constructor(options) {
	    super({
	      target: options.target,
	      items: DocumentCounter.getCounterItems(options.items),
	      multiselect: false,
	      title: options.title
	    });
	    Object.defineProperty(this, _onCounterUpdate, {
	      value: _onCounterUpdate2
	    });
	    Object.defineProperty(this, _onFilterApply, {
	      value: _onFilterApply2
	    });
	    Object.defineProperty(this, _isAllDeactivated, {
	      value: _isAllDeactivated2
	    });
	    Object.defineProperty(this, _getFieldData, {
	      value: _getFieldData2
	    });
	    Object.defineProperty(this, _processItemSelection, {
	      value: _processItemSelection2
	    });
	    Object.defineProperty(this, _onDeactivateItem, {
	      value: _onDeactivateItem2
	    });
	    Object.defineProperty(this, _onActivateItem, {
	      value: _onActivateItem2
	    });
	    Object.defineProperty(this, _filter$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resetAllFields, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _filter$1)[_filter$1] = new Filter({
	      filterId: options.filterId
	    });
	    main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:activate', babelHelpers.classPrivateFieldLooseBase(this, _onActivateItem)[_onActivateItem].bind(this));
	    main_core_events.EventEmitter.subscribe('BX.UI.CounterPanel.Item:deactivate', babelHelpers.classPrivateFieldLooseBase(this, _onDeactivateItem)[_onDeactivateItem].bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', babelHelpers.classPrivateFieldLooseBase(this, _onFilterApply)[_onFilterApply].bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Sign.DocumentCounter.Item:updateCounter', babelHelpers.classPrivateFieldLooseBase(this, _onCounterUpdate)[_onCounterUpdate].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _resetAllFields)[_resetAllFields] = Boolean(options == null ? void 0 : options.resetAllFields);
	  }
	  static getCounterItems(items) {
	    return items.map(item => {
	      return {
	        id: item.id,
	        title: item.title,
	        value: Number.parseInt(item.value, 10),
	        isRestricted: item.isRestricted,
	        color: item.color === 'THEME' ? 'GRAY' : item.color,
	        hideValue: item.hideValue || false,
	        isActive: (item == null ? void 0 : item.isActive) === true
	      };
	    });
	  }
	}
	function _onActivateItem2(event) {
	  const {
	    name,
	    value
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getFieldData)[_getFieldData](event.getData());
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _processItemSelection)[_processItemSelection](name, value)) {
	    event.preventDefault();
	  }
	}
	function _onDeactivateItem2(event) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isAllDeactivated)[_isAllDeactivated]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _filter$1)[_filter$1].deactivate();
	  }
	}
	function _processItemSelection2(name, value) {
	  babelHelpers.classPrivateFieldLooseBase(this, _filter$1)[_filter$1].toggleField(name, value, babelHelpers.classPrivateFieldLooseBase(this, _resetAllFields)[_resetAllFields]);
	  return true;
	}
	function _getFieldData2(item) {
	  const fieldData = item.id.split('__');
	  return {
	    name: fieldData[0].toUpperCase(),
	    value: fieldData[1].toUpperCase()
	  };
	}
	function _isAllDeactivated2() {
	  return this.getItems().every(record => {
	    return !record.isActive;
	  });
	}
	function _onFilterApply2() {
	  let compoundId = '';
	  const filterRows = babelHelpers.classPrivateFieldLooseBase(this, _filter$1)[_filter$1].getFilterRows();
	  const counterItemIds = new Set(this.items.map(item => item.id.toLowerCase()));
	  const activeField = Object.entries(filterRows).find(row => {
	    if (!main_core.Type.isPlainObject(row[1])) {
	      return false;
	    }
	    const values = Object.values(row[1]);
	    const result = [row[0], values.join('_')];
	    compoundId = result.join('__').toLowerCase();
	    return counterItemIds.has(compoundId);
	  });
	  this.getItems().forEach(item => {
	    item.deactivate(false);
	    if (activeField && item.id.toLowerCase() === compoundId) {
	      // eslint-disable-next-line no-param-reassign
	      item.activate(false);
	    }
	  });
	}
	function _onCounterUpdate2(event) {
	  const {
	    id,
	    count
	  } = event.getData();
	  for (const item of this.getItems()) {
	    if (item.id === id) {
	      item.updateValue(count);
	      const color = count > 0 ? 'DANGER' : 'GRAY';
	      item.updateColor(color);
	      break;
	    }
	  }
	}

	exports.DocumentCounter = DocumentCounter;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX.Event,BX.UI,BX));
//# sourceMappingURL=document-counter.bundle.js.map
