/* eslint-disable */
this.BX = this.BX || {};
(function (exports,crm_activity_todoNotificationSkipMenu,crm_activity_todoPingSettingsMenu,crm_kanban_restriction,crm_kanban_sort,main_core_events,main_popup,ui_entitySelector,main_core) {
	'use strict';

	function requireClassOrNull(param, constructor, paramName) {
	  if (main_core.Type.isNil(param)) {
	    return param;
	  }
	  return requireClass(param, constructor, paramName);
	}
	function requireClass(param, constructor, paramName) {
	  if (param instanceof constructor) {
	    return param;
	  }
	  throw new Error(`Expected ${paramName} be an instance of ${constructor.name}, got ${getType(param)} instead`);
	}
	function requireArrayOfString(param, paramName) {
	  if (!main_core.Type.isArray(param)) {
	    throw new TypeError(`Expected ${paramName} should be an array of strings, got ${getType(param)} instead`);
	  }
	  param.forEach((value, index) => {
	    if (!main_core.Type.isString(value)) {
	      throw new TypeError(`Expected ${paramName} should be an array of strings, instead the element at index ${index} is ${getType(value)}`);
	    }
	  });
	  return param;
	}
	function requireStringOrNull(param, paramName) {
	  if (main_core.Type.isStringFilled(param) || main_core.Type.isNil(param)) {
	    return param;
	  }
	  throw new Error(`Expected ${paramName} be either non-empty string or null, got ${getType(param)} instead`);
	}
	function getType(value) {
	  if (main_core.Type.isObject(value) && !main_core.Type.isPlainObject(value)) {
	    var _value$constructor;
	    return (value == null ? void 0 : (_value$constructor = value.constructor) == null ? void 0 : _value$constructor.name) || 'unknown';
	  }

	  // eslint-disable-next-line @bitrix24/bitrix24-rules/no-typeof
	  return typeof value;
	}

	const aliases = main_core.Extension.getSettings('crm.settings-button-extender').get('createTimeAliases', {});
	const DefaultSort = {};
	for (const entityTypeId in aliases) {
	  DefaultSort[entityTypeId] = {
	    column: aliases[entityTypeId],
	    order: 'desc'
	  };
	}
	Object.freeze(DefaultSort);

	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _grid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("grid");
	var _disableLastActivitySort = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableLastActivitySort");
	var _enableLastActivitySort = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableLastActivitySort");
	var _isColumnExists = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isColumnExists");
	var _isColumnShowed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isColumnShowed");
	var _isColumnSortable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isColumnSortable");
	var _getShowedColumnList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getShowedColumnList");
	var _setSortOrder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSortOrder");
	var _showColumn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showColumn");
	class SortController {
	  constructor(entityTypeId, grid) {
	    Object.defineProperty(this, _showColumn, {
	      value: _showColumn2
	    });
	    Object.defineProperty(this, _setSortOrder, {
	      value: _setSortOrder2
	    });
	    Object.defineProperty(this, _getShowedColumnList, {
	      value: _getShowedColumnList2
	    });
	    Object.defineProperty(this, _isColumnSortable, {
	      value: _isColumnSortable2
	    });
	    Object.defineProperty(this, _isColumnShowed, {
	      value: _isColumnShowed2
	    });
	    Object.defineProperty(this, _isColumnExists, {
	      value: _isColumnExists2
	    });
	    Object.defineProperty(this, _enableLastActivitySort, {
	      value: _enableLastActivitySort2
	    });
	    Object.defineProperty(this, _disableLastActivitySort, {
	      value: _disableLastActivitySort2
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _grid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = main_core.Text.toInteger(entityTypeId);
	    babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid] = requireClass(grid, BX.Main.grid, 'grid');
	  }
	  isLastActivitySortSupported() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isColumnExists)[_isColumnExists]('LAST_ACTIVITY_TIME');
	  }
	  isLastActivitySortEnabled() {
	    const options = babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getUserOptions().getCurrentOptions();
	    const column = options.last_sort_by;
	    const order = options.last_sort_order;
	    return (column == null ? void 0 : column.toLowerCase()) === 'last_activity_time' && (order == null ? void 0 : order.toLowerCase()) === 'desc';
	  }
	  toggleLastActivitySort() {
	    if (this.isLastActivitySortEnabled()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _disableLastActivitySort)[_disableLastActivitySort]();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _enableLastActivitySort)[_enableLastActivitySort]();
	    }
	  }
	}
	async function _disableLastActivitySort2() {
	  const sort = DefaultSort[babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]];
	  let column;
	  if (main_core.Type.isPlainObject(sort) && babelHelpers.classPrivateFieldLooseBase(this, _isColumnExists)[_isColumnExists](sort.column) && babelHelpers.classPrivateFieldLooseBase(this, _isColumnSortable)[_isColumnSortable](sort.column)) {
	    column = sort.column;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isColumnShowed)[_isColumnShowed](column)) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _showColumn)[_showColumn](column);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _setSortOrder)[_setSortOrder](column, sort.order);
	  } else {
	    // fist showed different sortable
	    column = babelHelpers.classPrivateFieldLooseBase(this, _getShowedColumnList)[_getShowedColumnList]().find(columnName => {
	      return columnName !== 'LAST_ACTIVITY_TIME' && babelHelpers.classPrivateFieldLooseBase(this, _isColumnSortable)[_isColumnSortable](columnName);
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].sortByColumn(column);
	}
	async function _enableLastActivitySort2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isColumnShowed)[_isColumnShowed]('LAST_ACTIVITY_TIME')) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _showColumn)[_showColumn]('LAST_ACTIVITY_TIME');
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setSortOrder)[_setSortOrder]('LAST_ACTIVITY_TIME', 'desc');
	  babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].sortByColumn('LAST_ACTIVITY_TIME');
	}
	function _isColumnExists2(column) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getParam('COLUMNS_ALL', {}).hasOwnProperty(column);
	}
	function _isColumnShowed2(column) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getShowedColumnList)[_getShowedColumnList]().includes(column);
	}
	function _isColumnSortable2(column) {
	  const columnParams = babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getColumnByName(column);
	  return !!(columnParams && columnParams.sort !== false);
	}
	function _getShowedColumnList2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getSettingsWindow().getShowedColumns();
	}
	function _setSortOrder2(column, order) {
	  babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getColumnByName(column).sort_order = order;
	}
	function _showColumn2(column) {
	  return new Promise((resolve, reject) => {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isColumnExists)[_isColumnExists](column)) {
	      reject(new Error(`Column ${column} does not exists`));
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isColumnShowed)[_isColumnShowed](column)) {
	      reject(new Error(`Column ${column} is showed already`));
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getSettingsWindow().select(column);
	    const showedColumns = babelHelpers.classPrivateFieldLooseBase(this, _getShowedColumnList)[_getShowedColumnList]();
	    showedColumns.push(column);
	    babelHelpers.classPrivateFieldLooseBase(this, _grid)[_grid].getSettingsWindow().saveColumns(showedColumns, resolve);
	  });
	}

	const EntityType = main_core.Reflection.getClass('BX.CrmEntityType');
	const CHECKED_CLASS = 'menu-popup-item-accept';
	const NOT_CHECKED_CLASS = 'menu-popup-item-none';
	const COPILOT_LANGUAGE_ID_SAVE_REQUEST_DELAY = 750;
	const COPILOT_LANGUAGE_SELECTOR_POPUP_WIDTH = 300;
	const AUTOSTART_CALL_DIRECTION_INCOMING = 1;
	const AUTOSTART_CALL_DIRECTION_OUTGOING = 2;
	var _entityTypeId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _categoryId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("categoryId");
	var _pingSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pingSettings");
	var _rootMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rootMenu");
	var _targetItemId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetItemId");
	var _expandsBehindThan = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("expandsBehindThan");
	var _kanbanController = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("kanbanController");
	var _restriction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("restriction");
	var _gridController = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("gridController");
	var _todoSkipMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("todoSkipMenu");
	var _todoPingSettingsMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("todoPingSettingsMenu");
	var _isSetSortRequestRunning = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSetSortRequestRunning");
	var _smartActivityNotificationSupported = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("smartActivityNotificationSupported");
	var _aiAutostartSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("aiAutostartSettings");
	var _aiCopilotLanguageId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("aiCopilotLanguageId");
	var _isSetAiSettingsRequestRunning = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSetAiSettingsRequestRunning");
	var _extensionSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extensionSettings");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _getItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getItems");
	var _resolveEarlyTargetId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resolveEarlyTargetId");
	var _getPushCrmSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPushCrmSettings");
	var _shouldShowLastActivitySortToggle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowLastActivitySortToggle");
	var _getLastActivitySortToggle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLastActivitySortToggle");
	var _isLastActivitySortEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLastActivitySortEnabled");
	var _handleLastActivitySortToggleClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleLastActivitySortToggleClick");
	var _closeMenuWindow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeMenuWindow");
	var _shouldShowTodoSkipMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowTodoSkipMenu");
	var _shouldShowTodoPingSettingsMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldShowTodoPingSettingsMenu");
	var _getCoPilotSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCoPilotSettings");
	var _handleCoPilotMenuItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCoPilotMenuItemClick");
	var _handleCoPilotLanguageSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCoPilotLanguageSelect");
	var _getAllOperationTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAllOperationTypes");
	var _getTranscribeAIOperationType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTranscribeAIOperationType");
	var _getInfoHelper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInfoHelper");
	/**
	 * @memberOf BX.Crm
	 */
	class SettingsButtonExtender {
	  constructor(params) {
	    var _params$expandsBehind;
	    Object.defineProperty(this, _getInfoHelper, {
	      value: _getInfoHelper2
	    });
	    Object.defineProperty(this, _getTranscribeAIOperationType, {
	      value: _getTranscribeAIOperationType2
	    });
	    Object.defineProperty(this, _getAllOperationTypes, {
	      value: _getAllOperationTypes2
	    });
	    Object.defineProperty(this, _handleCoPilotLanguageSelect, {
	      value: _handleCoPilotLanguageSelect2
	    });
	    Object.defineProperty(this, _handleCoPilotMenuItemClick, {
	      value: _handleCoPilotMenuItemClick2
	    });
	    Object.defineProperty(this, _getCoPilotSettings, {
	      value: _getCoPilotSettings2
	    });
	    Object.defineProperty(this, _shouldShowTodoPingSettingsMenu, {
	      value: _shouldShowTodoPingSettingsMenu2
	    });
	    Object.defineProperty(this, _shouldShowTodoSkipMenu, {
	      value: _shouldShowTodoSkipMenu2
	    });
	    Object.defineProperty(this, _closeMenuWindow, {
	      value: _closeMenuWindow2
	    });
	    Object.defineProperty(this, _handleLastActivitySortToggleClick, {
	      value: _handleLastActivitySortToggleClick2
	    });
	    Object.defineProperty(this, _isLastActivitySortEnabled, {
	      value: _isLastActivitySortEnabled2
	    });
	    Object.defineProperty(this, _getLastActivitySortToggle, {
	      value: _getLastActivitySortToggle2
	    });
	    Object.defineProperty(this, _shouldShowLastActivitySortToggle, {
	      value: _shouldShowLastActivitySortToggle2
	    });
	    Object.defineProperty(this, _getPushCrmSettings, {
	      value: _getPushCrmSettings2
	    });
	    Object.defineProperty(this, _resolveEarlyTargetId, {
	      value: _resolveEarlyTargetId2
	    });
	    Object.defineProperty(this, _getItems, {
	      value: _getItems2
	    });
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _entityTypeId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _categoryId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pingSettings, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _rootMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _targetItemId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _expandsBehindThan, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _kanbanController, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _restriction, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _gridController, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _todoSkipMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _todoPingSettingsMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isSetSortRequestRunning, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _smartActivityNotificationSupported, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _aiAutostartSettings, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _aiCopilotLanguageId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isSetAiSettingsRequestRunning, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _extensionSettings, {
	      writable: true,
	      value: main_core.Extension.getSettings('crm.settings-button-extender')
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1] = main_core.Text.toInteger(params.entityTypeId);
	    babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId] = main_core.Type.isInteger(params.categoryId) ? params.categoryId : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _pingSettings)[_pingSettings] = main_core.Type.isPlainObject(params.pingSettings) ? params.pingSettings : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _expandsBehindThan)[_expandsBehindThan] = requireArrayOfString((_params$expandsBehind = params.expandsBehindThan) != null ? _params$expandsBehind : [], 'params.expandsBehindThan');
	    babelHelpers.classPrivateFieldLooseBase(this, _smartActivityNotificationSupported)[_smartActivityNotificationSupported] = main_core.Text.toBoolean(params.smartActivityNotificationSupported);
	    if (EntityType && !EntityType.isDefined(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1])) {
	      throw new Error(`Provided entityTypeId is invalid: ${babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1]}`);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _rootMenu)[_rootMenu] = requireClass(params.rootMenu, main_popup.Menu, 'params.rootMenu');
	    babelHelpers.classPrivateFieldLooseBase(this, _targetItemId)[_targetItemId] = requireStringOrNull(params.targetItemId, 'params.targetItemId');
	    babelHelpers.classPrivateFieldLooseBase(this, _kanbanController)[_kanbanController] = requireClassOrNull(params.controller, crm_kanban_sort.SettingsController, 'params.controller');
	    babelHelpers.classPrivateFieldLooseBase(this, _restriction)[_restriction] = requireClassOrNull(params.restriction, crm_kanban_restriction.Restriction, 'params.restriction');
	    if (main_core.Reflection.getClass('BX.Main.grid') && params.grid) {
	      babelHelpers.classPrivateFieldLooseBase(this, _gridController)[_gridController] = new SortController(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1], params.grid);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _todoSkipMenu)[_todoSkipMenu] = new crm_activity_todoNotificationSkipMenu.TodoNotificationSkipMenu({
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1],
	      selectedValue: requireStringOrNull(params.todoCreateNotificationSkipPeriod, 'params.todoCreateNotificationSkipPeriod')
	    });
	    if (Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _pingSettings)[_pingSettings]).length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _todoPingSettingsMenu)[_todoPingSettingsMenu] = new crm_activity_todoPingSettingsMenu.TodoPingSettingsMenu({
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1],
	        settings: babelHelpers.classPrivateFieldLooseBase(this, _pingSettings)[_pingSettings]
	      });
	    }
	    const aiSettingsJson = requireStringOrNull(params.aiAutostartSettings, 'params.aiAutostartSettings');
	    if (main_core.Type.isStringFilled(aiSettingsJson)) {
	      const candidate = JSON.parse(aiSettingsJson);
	      if (main_core.Type.isPlainObject(candidate)) {
	        babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings] = candidate;
	      }
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _aiCopilotLanguageId)[_aiCopilotLanguageId] = params.aiCopilotLanguageId;
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	  }
	}
	function _bindEvents2() {
	  const createdMenuItemIds = [];
	  main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'onPopupShow', event => {
	    const popup = event.getTarget();
	    if (popup.getId() !== babelHelpers.classPrivateFieldLooseBase(this, _rootMenu)[_rootMenu].getId()) {
	      return;
	    }
	    const items = babelHelpers.classPrivateFieldLooseBase(this, _getItems)[_getItems]();
	    if (items.length <= 0) {
	      return;
	    }
	    while (createdMenuItemIds.length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _rootMenu)[_rootMenu].removeMenuItem(createdMenuItemIds.pop());
	    }
	    let targetItemId = babelHelpers.classPrivateFieldLooseBase(this, _resolveEarlyTargetId)[_resolveEarlyTargetId]();
	    for (const item of items.reverse())
	    // new item is *prepended* on top of target item, therefore reverse
	    {
	      const newItem = babelHelpers.classPrivateFieldLooseBase(this, _rootMenu)[_rootMenu].addMenuItem(item, targetItemId);
	      if (newItem) {
	        targetItemId = newItem.getId();
	        createdMenuItemIds.push(newItem.getId());
	      }
	    }
	  });
	}
	function _getItems2() {
	  const items = [];
	  const pushCrmSettings = babelHelpers.classPrivateFieldLooseBase(this, _getPushCrmSettings)[_getPushCrmSettings]();
	  if (pushCrmSettings) {
	    items.push(pushCrmSettings);
	  }
	  const coPilotSettings = babelHelpers.classPrivateFieldLooseBase(this, _getCoPilotSettings)[_getCoPilotSettings]();
	  if (coPilotSettings) {
	    items.push(coPilotSettings);
	  }
	  return items;
	}
	function _resolveEarlyTargetId2() {
	  var _earlyItem$getId;
	  const items = babelHelpers.classPrivateFieldLooseBase(this, _rootMenu)[_rootMenu].getMenuItems();
	  const earlyItem = items.find(item => babelHelpers.classPrivateFieldLooseBase(this, _expandsBehindThan)[_expandsBehindThan].includes(item.getId()));
	  return (_earlyItem$getId = earlyItem == null ? void 0 : earlyItem.getId()) != null ? _earlyItem$getId : babelHelpers.classPrivateFieldLooseBase(this, _targetItemId)[_targetItemId];
	}
	function _getPushCrmSettings2() {
	  const pushCrmItems = [];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _shouldShowLastActivitySortToggle)[_shouldShowLastActivitySortToggle]()) {
	    pushCrmItems.push(babelHelpers.classPrivateFieldLooseBase(this, _getLastActivitySortToggle)[_getLastActivitySortToggle]());
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _shouldShowTodoSkipMenu)[_shouldShowTodoSkipMenu]()) {
	    pushCrmItems.push(...babelHelpers.classPrivateFieldLooseBase(this, _todoSkipMenu)[_todoSkipMenu].getItems());
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _shouldShowTodoPingSettingsMenu)[_shouldShowTodoPingSettingsMenu]()) {
	    pushCrmItems.push(...babelHelpers.classPrivateFieldLooseBase(this, _todoPingSettingsMenu)[_todoPingSettingsMenu].getItems());
	  }
	  if (pushCrmItems.length <= 0) {
	    return null;
	  }
	  return {
	    text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_PUSH_CRM'),
	    items: pushCrmItems
	  };
	}
	function _shouldShowLastActivitySortToggle2() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3;
	  const shouldShowInKanban = ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _kanbanController)[_kanbanController]) == null ? void 0 : _babelHelpers$classPr.getCurrentSettings().isTypeSupported(crm_kanban_sort.Type.BY_LAST_ACTIVITY_TIME)) && ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _restriction)[_restriction]) == null ? void 0 : _babelHelpers$classPr2.isSortTypeChangeAvailable());
	  return !!(shouldShowInKanban || (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _gridController)[_gridController]) != null && _babelHelpers$classPr3.isLastActivitySortSupported());
	}
	function _getLastActivitySortToggle2() {
	  return {
	    text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_PUSH_CRM_TOGGLE_SORT'),
	    disabled: babelHelpers.classPrivateFieldLooseBase(this, _isSetSortRequestRunning)[_isSetSortRequestRunning],
	    className: babelHelpers.classPrivateFieldLooseBase(this, _isLastActivitySortEnabled)[_isLastActivitySortEnabled]() ? CHECKED_CLASS : NOT_CHECKED_CLASS,
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _handleLastActivitySortToggleClick)[_handleLastActivitySortToggleClick].bind(this)
	  };
	}
	function _isLastActivitySortEnabled2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _kanbanController)[_kanbanController]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _kanbanController)[_kanbanController].getCurrentSettings().getCurrentType() === crm_kanban_sort.Type.BY_LAST_ACTIVITY_TIME;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _gridController)[_gridController]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _gridController)[_gridController].isLastActivitySortEnabled();
	  }
	  return false;
	}
	function _handleLastActivitySortToggleClick2(event, item) {
	  var _item$getMenuWindow, _item$getMenuWindow$g;
	  (_item$getMenuWindow = item.getMenuWindow()) == null ? void 0 : (_item$getMenuWindow$g = _item$getMenuWindow.getRootMenuWindow()) == null ? void 0 : _item$getMenuWindow$g.close();
	  item.disable();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _kanbanController)[_kanbanController]) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isSetSortRequestRunning)[_isSetSortRequestRunning]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _isSetSortRequestRunning)[_isSetSortRequestRunning] = true;
	    const settings = babelHelpers.classPrivateFieldLooseBase(this, _kanbanController)[_kanbanController].getCurrentSettings();

	    // eslint-disable-next-line init-declarations
	    let newSortType;
	    if (settings.getCurrentType() === crm_kanban_sort.Type.BY_LAST_ACTIVITY_TIME) {
	      // first different type
	      newSortType = settings.getSupportedTypes().find(sortType => sortType !== crm_kanban_sort.Type.BY_LAST_ACTIVITY_TIME);
	    } else {
	      newSortType = crm_kanban_sort.Type.BY_LAST_ACTIVITY_TIME;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _kanbanController)[_kanbanController].setCurrentSortType(newSortType).then(() => {}).catch(() => {}).finally(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _isSetSortRequestRunning)[_isSetSortRequestRunning] = false;
	      item.enable();
	    });
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _gridController)[_gridController]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _gridController)[_gridController].toggleLastActivitySort();
	    item.enable();
	  } else {
	    console.error('Can not handle last activity toggle click');
	  }
	}
	function _closeMenuWindow2(event, item) {
	  var _item$getMenuWindow2;
	  (_item$getMenuWindow2 = item.getMenuWindow()) == null ? void 0 : _item$getMenuWindow2.close();
	}
	function _shouldShowTodoSkipMenu2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _smartActivityNotificationSupported)[_smartActivityNotificationSupported];
	}
	function _shouldShowTodoPingSettingsMenu2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _todoPingSettingsMenu)[_todoPingSettingsMenu] && babelHelpers.classPrivateFieldLooseBase(this, _shouldShowLastActivitySortToggle)[_shouldShowLastActivitySortToggle]();
	}
	function _getCoPilotSettings2() {
	  const showInfoHelper = babelHelpers.classPrivateFieldLooseBase(this, _getInfoHelper)[_getInfoHelper]();
	  const menuItems = [];
	  if (main_core.Type.isPlainObject(babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings])) {
	    var _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10, _babelHelpers$classPr11, _babelHelpers$classPr12, _babelHelpers$classPr13, _babelHelpers$classPr14;
	    const autoCallItems = [];
	    const isTranscriptionAutoStarted = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings]) == null ? void 0 : (_babelHelpers$classPr5 = _babelHelpers$classPr4.autostartOperationTypes) == null ? void 0 : _babelHelpers$classPr5.includes(babelHelpers.classPrivateFieldLooseBase(this, _getTranscribeAIOperationType)[_getTranscribeAIOperationType]());
	    const isOnlyFirst = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings]) == null ? void 0 : _babelHelpers$classPr6.autostartTranscriptionOnlyOnFirstCallWithRecording;
	    const isOnlyIncoming = ((_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings]) == null ? void 0 : (_babelHelpers$classPr8 = _babelHelpers$classPr7.autostartCallDirections) == null ? void 0 : _babelHelpers$classPr8.length) === 1 && ((_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings]) == null ? void 0 : (_babelHelpers$classPr10 = _babelHelpers$classPr9.autostartCallDirections) == null ? void 0 : _babelHelpers$classPr10.includes(AUTOSTART_CALL_DIRECTION_INCOMING));
	    const isOnlyOutgoing = ((_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings]) == null ? void 0 : (_babelHelpers$classPr12 = _babelHelpers$classPr11.autostartCallDirections) == null ? void 0 : _babelHelpers$classPr12.length) === 1 && ((_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings]) == null ? void 0 : (_babelHelpers$classPr14 = _babelHelpers$classPr13.autostartCallDirections) == null ? void 0 : _babelHelpers$classPr14.includes(AUTOSTART_CALL_DIRECTION_OUTGOING));
	    const isAIHasPackages = babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('isAIHasPackages');
	    autoCallItems.push({
	      text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_FIRST_INCOMING_MSGVER_1'),
	      className: isTranscriptionAutoStarted && isAIHasPackages && isOnlyFirst && isOnlyIncoming ? CHECKED_CLASS : NOT_CHECKED_CLASS,
	      onclick: showInfoHelper != null ? showInfoHelper : babelHelpers.classPrivateFieldLooseBase(this, _handleCoPilotMenuItemClick)[_handleCoPilotMenuItemClick].bind(this, 'firstCall')
	    }, {
	      text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_INCOMING'),
	      className: isTranscriptionAutoStarted && isAIHasPackages && isOnlyIncoming && !isOnlyFirst ? CHECKED_CLASS : NOT_CHECKED_CLASS,
	      onclick: showInfoHelper != null ? showInfoHelper : babelHelpers.classPrivateFieldLooseBase(this, _handleCoPilotMenuItemClick)[_handleCoPilotMenuItemClick].bind(this, 'allCalls') // all incoming
	    }, {
	      text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_OUTGOING'),
	      className: isTranscriptionAutoStarted && isAIHasPackages && isOnlyOutgoing && !isOnlyFirst ? CHECKED_CLASS : NOT_CHECKED_CLASS,
	      onclick: showInfoHelper != null ? showInfoHelper : babelHelpers.classPrivateFieldLooseBase(this, _handleCoPilotMenuItemClick)[_handleCoPilotMenuItemClick].bind(this, 'outgoingCalls')
	    }, {
	      text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_ALL_MSGVER_1'),
	      className: isTranscriptionAutoStarted && isAIHasPackages && !isOnlyIncoming && !isOnlyOutgoing && !isOnlyFirst ? CHECKED_CLASS : NOT_CHECKED_CLASS,
	      onclick: showInfoHelper != null ? showInfoHelper : babelHelpers.classPrivateFieldLooseBase(this, _handleCoPilotMenuItemClick)[_handleCoPilotMenuItemClick].bind(this, 'allIncomingOutgoingCalls')
	    }, {
	      text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_MANUAL_CALLS_PROCESSING_MSGVER_1'),
	      className: isTranscriptionAutoStarted && isAIHasPackages ? NOT_CHECKED_CLASS : CHECKED_CLASS,
	      onclick: showInfoHelper != null ? showInfoHelper : babelHelpers.classPrivateFieldLooseBase(this, _handleCoPilotMenuItemClick)[_handleCoPilotMenuItemClick].bind(this, 'manual')
	    });
	    menuItems.push({
	      text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS'),
	      disabled: babelHelpers.classPrivateFieldLooseBase(this, _isSetAiSettingsRequestRunning)[_isSetAiSettingsRequestRunning],
	      items: autoCallItems
	    });
	  }
	  if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _aiCopilotLanguageId)[_aiCopilotLanguageId])) {
	    var _babelHelpers$classPr15;
	    menuItems.push({
	      text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_LANGUAGE_MSGVER_1'),
	      onclick: (_babelHelpers$classPr15 = babelHelpers.classPrivateFieldLooseBase(this, _getInfoHelper)[_getInfoHelper](true)) != null ? _babelHelpers$classPr15 : babelHelpers.classPrivateFieldLooseBase(this, _handleCoPilotLanguageSelect)[_handleCoPilotLanguageSelect].bind(this)
	    });
	  }
	  if (menuItems.length === 0) {
	    return null;
	  }
	  return {
	    text: main_core.Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_IN_CALLS'),
	    disabled: babelHelpers.classPrivateFieldLooseBase(this, _isSetAiSettingsRequestRunning)[_isSetAiSettingsRequestRunning],
	    items: menuItems
	  };
	}
	function _handleCoPilotMenuItemClick2(action, event, menuItem) {
	  var _menuItem$getMenuWind, _menuItem$getMenuWind2, _menuItem$getMenuWind3, _menuItem$getMenuWind4;
	  (_menuItem$getMenuWind = menuItem.getMenuWindow()) == null ? void 0 : (_menuItem$getMenuWind2 = _menuItem$getMenuWind.getRootMenuWindow()) == null ? void 0 : _menuItem$getMenuWind2.close();
	  (_menuItem$getMenuWind3 = menuItem.getMenuWindow()) == null ? void 0 : (_menuItem$getMenuWind4 = _menuItem$getMenuWind3.getParentMenuItem()) == null ? void 0 : _menuItem$getMenuWind4.disable();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isSetAiSettingsRequestRunning)[_isSetAiSettingsRequestRunning]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _isSetAiSettingsRequestRunning)[_isSetAiSettingsRequestRunning] = true;

	  // eslint-disable-next-line default-case
	  switch (action) {
	    case 'manual':
	      // autostart all except first step
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartOperationTypes = babelHelpers.classPrivateFieldLooseBase(this, _getAllOperationTypes)[_getAllOperationTypes]().filter(typeId => typeId !== babelHelpers.classPrivateFieldLooseBase(this, _getTranscribeAIOperationType)[_getTranscribeAIOperationType]());
	      break;
	    case 'firstCall':
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartOperationTypes = babelHelpers.classPrivateFieldLooseBase(this, _getAllOperationTypes)[_getAllOperationTypes]();
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartTranscriptionOnlyOnFirstCallWithRecording = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartCallDirections = [AUTOSTART_CALL_DIRECTION_INCOMING];
	      break;
	    case 'allCalls':
	      // all incoming
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartOperationTypes = babelHelpers.classPrivateFieldLooseBase(this, _getAllOperationTypes)[_getAllOperationTypes]();
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartTranscriptionOnlyOnFirstCallWithRecording = false;
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartCallDirections = [AUTOSTART_CALL_DIRECTION_INCOMING];
	      break;
	    case 'outgoingCalls':
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartOperationTypes = babelHelpers.classPrivateFieldLooseBase(this, _getAllOperationTypes)[_getAllOperationTypes]();
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartTranscriptionOnlyOnFirstCallWithRecording = false;
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartCallDirections = [AUTOSTART_CALL_DIRECTION_OUTGOING];
	      break;
	    case 'allIncomingOutgoingCalls':
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartOperationTypes = babelHelpers.classPrivateFieldLooseBase(this, _getAllOperationTypes)[_getAllOperationTypes]();
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartTranscriptionOnlyOnFirstCallWithRecording = false;
	      babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings].autostartCallDirections = [AUTOSTART_CALL_DIRECTION_INCOMING, AUTOSTART_CALL_DIRECTION_OUTGOING];
	      break;
	  }
	  main_core.ajax.runAction('crm.settings.ai.saveAutostartSettings', {
	    json: {
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1],
	      categoryId: babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId],
	      settings: babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings]
	    }
	  }).then(({
	    data
	  }) => {
	    var _menuItem$getMenuWind5, _menuItem$getMenuWind6;
	    babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings] = data.settings;
	    (_menuItem$getMenuWind5 = menuItem.getMenuWindow()) == null ? void 0 : (_menuItem$getMenuWind6 = _menuItem$getMenuWind5.getParentMenuItem()) == null ? void 0 : _menuItem$getMenuWind6.enable();
	    babelHelpers.classPrivateFieldLooseBase(this, _isSetAiSettingsRequestRunning)[_isSetAiSettingsRequestRunning] = false;
	  }).catch(({
	    errors
	  }) => {
	    console.error('Could not save ai settings', errors);

	    // refresh settings, we need to know relevant state
	    return main_core.ajax.runAction('crm.settings.ai.getAutostartSettings', {
	      json: {
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1],
	        categoryId: babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId]
	      }
	    });
	  }).then(({
	    data
	  }) => {
	    var _menuItem$getMenuWind7, _menuItem$getMenuWind8;
	    babelHelpers.classPrivateFieldLooseBase(this, _aiAutostartSettings)[_aiAutostartSettings] = data.settings;
	    (_menuItem$getMenuWind7 = menuItem.getMenuWindow()) == null ? void 0 : (_menuItem$getMenuWind8 = _menuItem$getMenuWind7.getParentMenuItem()) == null ? void 0 : _menuItem$getMenuWind8.enable();
	    babelHelpers.classPrivateFieldLooseBase(this, _isSetAiSettingsRequestRunning)[_isSetAiSettingsRequestRunning] = false;
	  }).catch(({
	    errors
	  }) => {
	    var _menuItem$getMenuWind9, _menuItem$getMenuWind10;
	    console.error('Could not fetch ai settings after error in save', errors);
	    (_menuItem$getMenuWind9 = menuItem.getMenuWindow()) == null ? void 0 : (_menuItem$getMenuWind10 = _menuItem$getMenuWind9.getParentMenuItem()) == null ? void 0 : _menuItem$getMenuWind10.enable();
	    babelHelpers.classPrivateFieldLooseBase(this, _isSetAiSettingsRequestRunning)[_isSetAiSettingsRequestRunning] = false;
	  });
	}
	function _handleCoPilotLanguageSelect2(event) {
	  const languageSelector = new ui_entitySelector.Dialog({
	    targetNode: event.target,
	    multiple: false,
	    showAvatars: false,
	    dropdownMode: true,
	    compactView: true,
	    enableSearch: true,
	    context: `COPILOT-LANGUAGE-SELECTOR-${babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1]}-${babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId]}`,
	    width: COPILOT_LANGUAGE_SELECTOR_POPUP_WIDTH,
	    tagSelectorOptions: {
	      textBoxWidth: '100%'
	    },
	    preselectedItems: [['copilot_language', babelHelpers.classPrivateFieldLooseBase(this, _aiCopilotLanguageId)[_aiCopilotLanguageId]]],
	    entities: [{
	      id: 'copilot_language',
	      options: {
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1],
	        categoryId: babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId]
	      }
	    }],
	    events: {
	      'Item:onSelect': selectEvent => {
	        const item = selectEvent.getData().item;
	        const languageId = item.id.toLowerCase();
	        if (!main_core.Type.isStringFilled(languageId)) {
	          throw new Error('Language ID is not defined');
	        }
	        setTimeout(() => {
	          let optionName = `ai_config_${babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1]}`;
	          if (main_core.Type.isInteger(babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId])) {
	            optionName += `_${babelHelpers.classPrivateFieldLooseBase(this, _categoryId)[_categoryId]}`;
	          }
	          main_core.userOptions.save('crm', optionName, 'languageId', languageId);
	          babelHelpers.classPrivateFieldLooseBase(this, _aiCopilotLanguageId)[_aiCopilotLanguageId] = languageId;
	        }, COPILOT_LANGUAGE_ID_SAVE_REQUEST_DELAY);
	      }
	    }
	  });
	  languageSelector.show();
	}
	function _getAllOperationTypes2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('allAIOperationTypes').map(id => main_core.Text.toInteger(id));
	}
	function _getTranscribeAIOperationType2() {
	  return main_core.Text.toInteger(babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('transcribeAIOperationType'));
	}
	function _getInfoHelper2(skipPackagesCheck = false) {
	  if (skipPackagesCheck) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('isAIEnabledInGlobalSettings')) {
	      return null;
	    }
	    return () => {
	      if (main_core.Reflection.getClass('BX.UI.InfoHelper.show')) {
	        BX.UI.InfoHelper.show(babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('aiDisabledSliderCode'));
	      }
	    };
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('isAIEnabledInGlobalSettings') && babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('isAIHasPackages')) {
	    return null;
	  }
	  return () => {
	    if (main_core.Reflection.getClass('BX.UI.InfoHelper.show')) {
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('isAIEnabledInGlobalSettings')) {
	        BX.UI.InfoHelper.show(babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('aiDisabledSliderCode'));
	      } else if (!babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('isAIHasPackages')) {
	        BX.UI.InfoHelper.show(babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get('aiPackagesEmptySliderCode'));
	      }
	    }
	  };
	}

	exports.SettingsButtonExtender = SettingsButtonExtender;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm.Activity,BX.Crm.Activity,BX.CRM.Kanban,BX.CRM.Kanban,BX.Event,BX.Main,BX.UI.EntitySelector,BX));
//# sourceMappingURL=settings-button-extender.bundle.js.map
