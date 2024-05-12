this.BX = this.BX || {};
this.BX.CRM = this.BX.CRM || {};
(function (exports,main_core) {
	'use strict';

	/**
	 * @memberOf BX.Crm.Kanban.Sort
	 */
	const Type = {
	  BY_ID: 'BY_ID',
	  BY_LAST_ACTIVITY_TIME: 'BY_LAST_ACTIVITY_TIME',
	  isDefined(type) {
	    return type === this.BY_ID || type === this.BY_LAST_ACTIVITY_TIME;
	  },
	  getAll() {
	    return [this.BY_ID, this.BY_LAST_ACTIVITY_TIME];
	  }
	};
	Object.freeze(Type);

	/**
	 * @memberOf BX.CRM.Kanban.Sort.Settings
	 */
	var _supportedTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("supportedTypes");
	var _currentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentType");
	class Settings {
	  constructor(supportedTypes, currentType) {
	    Object.defineProperty(this, _supportedTypes, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentType, {
	      writable: true,
	      value: void 0
	    });
	    supportedTypes = main_core.Type.isArray(supportedTypes) ? supportedTypes : [];
	    babelHelpers.classPrivateFieldLooseBase(this, _supportedTypes)[_supportedTypes] = supportedTypes.filter(type => Type.isDefined(type));
	    if (babelHelpers.classPrivateFieldLooseBase(this, _supportedTypes)[_supportedTypes].length <= 0) {
	      throw new Error('No valid supported types provided');
	    }
	    if (!main_core.Type.isString(currentType) || !Type.isDefined(currentType)) {
	      throw new Error('currentType is not a valid sort type');
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _supportedTypes)[_supportedTypes].includes(currentType)) {
	      throw new Error('currentType is not supported');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _currentType)[_currentType] = currentType;
	  }
	  getSupportedTypes() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _supportedTypes)[_supportedTypes];
	  }
	  isTypeSupported(sortType) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _supportedTypes)[_supportedTypes].includes(sortType);
	  }
	  getCurrentType() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _currentType)[_currentType];
	  }
	  static createFromJson(json) {
	    const {
	      supportedTypes,
	      currentType
	    } = JSON.parse(json);
	    return new Settings(supportedTypes, currentType);
	  }
	}

	let instance = null;

	/**
	 * @memberOf BX.CRM.Kanban.Sort
	 */
	var _grid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("grid");
	var _settings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settings");
	var _sortChangePromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sortChangePromise");
	class SettingsController {
	  static get Instance() {
	    if (window.top !== window && main_core.Reflection.getClass('top.BX.CRM.Kanban.Sort.SettingsController')) {
	      return window.top.BX.CRM.Kanban.Sort.SettingsController;
	    }
	    if (!instance) {
	      throw new Error('SettingsController must be inited before use');
	    }
	    return instance;
	  }
	  static init(grid, settings) {
	    if (instance) {
	      console.warn('Attempt to re-init SettingsController');
	      return;
	    }
	    instance = new SettingsController(grid, settings);
	  }
	  constructor(grid, settings) {
	    Object.defineProperty(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _settings, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _sortChangePromise, {
	      writable: true,
	      value: null
	    });
	    if (instance) {
	      throw new Error('SettingsController is a singleton, another instance exists already. Use Instance to access it');
	    }
	    if (!(grid instanceof BX.CRM.Kanban.Grid)) {
	      console.error(grid);
	      throw new Error('grid should be an instance of BX.CRM.Kanban.Grid');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid] = grid;
	    if (!(settings instanceof Settings)) {
	      console.error(settings);
	      throw new Error('settings should be an instance of Settings');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings] = settings;
	  }
	  setCurrentSortType(sortType) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _sortChangePromise)[_sortChangePromise]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _sortChangePromise)[_sortChangePromise] = babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].setCurrentSortType(sortType).then(() => {
	        //save new current sort type
	        babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings] = new Settings(babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].getSupportedTypes(), sortType);
	        babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].reload();
	      }).catch(error => {
	        console.error(error);
	        throw error;
	      }).finally(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _sortChangePromise)[_sortChangePromise] = null;
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _sortChangePromise)[_sortChangePromise];
	  }
	  getCurrentSettings() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings];
	  }
	}

	/**
	 * @memberOf BX.CRM.Kanban.Sort
	 */
	var _sortType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sortType");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _extractId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractId");
	var _extractTimestamp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractTimestamp");
	var _calcById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calcById");
	var _calcByLastActivityTime = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calcByLastActivityTime");
	var _findFirstDifferentItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("findFirstDifferentItem");
	class Sorter {
	  static createWithCurrentSortType(items) {
	    return new Sorter(SettingsController.Instance.getCurrentSettings().getCurrentType(), items);
	  }
	  constructor(sortType, _items2) {
	    Object.defineProperty(this, _findFirstDifferentItem, {
	      value: _findFirstDifferentItem2
	    });
	    Object.defineProperty(this, _calcByLastActivityTime, {
	      value: _calcByLastActivityTime2
	    });
	    Object.defineProperty(this, _calcById, {
	      value: _calcById2
	    });
	    Object.defineProperty(this, _extractTimestamp, {
	      value: _extractTimestamp2
	    });
	    Object.defineProperty(this, _extractId, {
	      value: _extractId2
	    });
	    Object.defineProperty(this, _sortType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: void 0
	    });
	    if (!Type.isDefined(sortType)) {
	      throw new Error('Undefined sort type');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _sortType)[_sortType] = sortType;
	    babelHelpers.classPrivateFieldLooseBase(this, _items)[_items] = main_core.Type.isArray(_items2) ? _items2 : [];
	  }
	  getSortType() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _sortType)[_sortType];
	  }

	  /**
	   * Returns items sorted in descending order. Beginning of array - is column top, end - column bottom.
	   *
	   * @returns {BX.CRM.Kanban.Item[]}
	   */
	  getSortedItems() {
	    let extractValue;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _sortType)[_sortType] === Type.BY_ID) {
	      extractValue = babelHelpers.classPrivateFieldLooseBase(this, _extractId)[_extractId];
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _sortType)[_sortType] === Type.BY_LAST_ACTIVITY_TIME) {
	      extractValue = babelHelpers.classPrivateFieldLooseBase(this, _extractTimestamp)[_extractTimestamp];
	    } else {
	      throw new Error('Unknown sort type');
	    }
	    const sortedItems = Array.from(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]);
	    sortedItems.sort((left, right) => {
	      return extractValue(right) - extractValue(left);
	    });
	    return sortedItems;
	  }
	  calcBeforeItem(item) {
	    const sortParams = item.getData().sort;
	    return main_core.Type.isPlainObject(sortParams) ? this.calcBeforeItemByParams(sortParams) : null;
	  }
	  calcBeforeItemByParams(sort) {
	    const id = main_core.Text.toInteger(sort == null ? void 0 : sort.id);
	    if (id <= 0) {
	      return null;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _sortType)[_sortType] === Type.BY_ID) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _calcById)[_calcById](id);
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _sortType)[_sortType] === Type.BY_LAST_ACTIVITY_TIME) {
	      const lastActivityTimestamp = main_core.Text.toInteger(sort == null ? void 0 : sort.lastActivityTimestamp);
	      if (lastActivityTimestamp <= 0) {
	        return null;
	      }
	      return babelHelpers.classPrivateFieldLooseBase(this, _calcByLastActivityTime)[_calcByLastActivityTime](id, lastActivityTimestamp);
	    } else {
	      throw new Error('Unknown sort type');
	    }
	  }
	}
	function _extractId2(item) {
	  var _item$getData, _item$getData$sort;
	  return main_core.Text.toInteger((_item$getData = item.getData()) == null ? void 0 : (_item$getData$sort = _item$getData.sort) == null ? void 0 : _item$getData$sort.id);
	}
	function _extractTimestamp2(item) {
	  var _item$getData2, _item$getData2$sort;
	  return main_core.Text.toInteger((_item$getData2 = item.getData()) == null ? void 0 : (_item$getData2$sort = _item$getData2.sort) == null ? void 0 : _item$getData2$sort.lastActivityTimestamp);
	}
	function _calcById2(id) {
	  const notSortedItems = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items];
	  for (let index = 0; index < notSortedItems.length; index++) {
	    const item = notSortedItems[index];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _extractId)[_extractId](item) === id) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _findFirstDifferentItem)[_findFirstDifferentItem](id, notSortedItems, index);
	    }
	  }
	  return null;
	}
	function _calcByLastActivityTime2(id, lastActivityTimestamp) {
	  const sortedItems = this.getSortedItems();
	  for (let index = 0; index < sortedItems.length; index++) {
	    const item = sortedItems[index];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _extractTimestamp)[_extractTimestamp](item) <= lastActivityTimestamp) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _findFirstDifferentItem)[_findFirstDifferentItem](id, sortedItems, index);
	    }
	  }
	  if (sortedItems.length > 0) {
	    // item should be placed at bottom
	    return sortedItems[sortedItems.length - 1];
	  }

	  // no items, place item on top
	  return null;
	}
	function _findFirstDifferentItem2(itemId, items, startIndex) {
	  for (let index = startIndex; index < items.length; index++) {
	    const item = items[index];
	    if (itemId !== babelHelpers.classPrivateFieldLooseBase(this, _extractId)[_extractId](item)) {
	      return item;
	    }
	  }
	  return null;
	}

	const namespace = main_core.Reflection.namespace('BX.CRM.Kanban.Sort');
	namespace.Sorter = Sorter;
	namespace.Settings = Settings;
	namespace.SettingsController = SettingsController;
	namespace.Type = Type;

	exports.Sorter = Sorter;
	exports.Settings = Settings;
	exports.SettingsController = SettingsController;
	exports.Type = Type;

}((this.BX.CRM.Kanban = this.BX.CRM.Kanban || {}),BX));
//# sourceMappingURL=sort.bundle.js.map
