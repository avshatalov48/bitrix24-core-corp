this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_popup,main_core,crm_activity_todoNotificationSkip) {
	'use strict';

	var _selectedMenuItemId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedMenuItemId");
	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _skipProvider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("skipProvider");
	var _onSkippedPeriodChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkippedPeriodChange");
	var _getMenuItemText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemText");
	var _getSkipPeriodsMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSkipPeriodsMenuItems");
	var _isLoading = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLoading");
	var _onSkipMenuItemSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkipMenuItemSelect");
	class TodoNotificationSkipMenu {
	  constructor(params) {
	    Object.defineProperty(this, _onSkipMenuItemSelect, {
	      value: _onSkipMenuItemSelect2
	    });
	    Object.defineProperty(this, _isLoading, {
	      value: _isLoading2
	    });
	    Object.defineProperty(this, _getSkipPeriodsMenuItems, {
	      value: _getSkipPeriodsMenuItems2
	    });
	    Object.defineProperty(this, _getMenuItemText, {
	      value: _getMenuItemText2
	    });
	    Object.defineProperty(this, _onSkippedPeriodChange, {
	      value: _onSkippedPeriodChange2
	    });
	    Object.defineProperty(this, _selectedMenuItemId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _skipProvider, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = params.entityTypeId;
	    if (params.selectedValue) {
	      babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] = params.selectedValue;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _skipProvider)[_skipProvider] = new crm_activity_todoNotificationSkip.TodoNotificationSkip({
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	      onSkippedPeriodChange: babelHelpers.classPrivateFieldLooseBase(this, _onSkippedPeriodChange)[_onSkippedPeriodChange].bind(this)
	    });
	  }
	  setSelectedValue(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] = value;
	  }
	  getItems() {
	    const items = [];
	    items.push({
	      id: 'askForCreateTodo',
	      text: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemText)[_getMenuItemText](),
	      className: 'menu-popup-item-none',
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getSkipPeriodsMenuItems)[_getSkipPeriodsMenuItems]()
	    });
	    return items;
	  }
	}
	function _onSkippedPeriodChange2(period) {
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] = period;
	}
	function _getMenuItemText2() {
	  let messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_MENU_ITEM';
	  switch (babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]) {
	    case BX.CrmEntityType.enumeration.lead:
	      messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_MENU_ITEM_LEAD';
	      break;
	    case BX.CrmEntityType.enumeration.deal:
	      messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_MENU_ITEM_DEAL';
	      break;
	  }
	  return main_core.Loc.getMessage(messagePhrase);
	}
	function _getSkipPeriodsMenuItems2() {
	  const activeClass = 'menu-popup-item-accept';
	  const inactiveClass = 'menu-popup-item-none';
	  const items = [];
	  items.push({
	    id: 'activate',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_ACTIVATE'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] ? inactiveClass : activeClass,
	    disabled: babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading](),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, '')
	  });
	  items.push({
	    id: 'day',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_PERIOD_DAY'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] === 'day' ? activeClass : inactiveClass,
	    disabled: babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading](),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'day')
	  });
	  items.push({
	    id: 'week',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_PERIOD_WEEK'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] === 'week' ? activeClass : inactiveClass,
	    disabled: babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading](),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'week')
	  });
	  items.push({
	    id: 'month',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_PERIOD_MONTH'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] === 'month' ? activeClass : inactiveClass,
	    disabled: babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading](),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'month')
	  });
	  items.push({
	    id: 'forever',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_PERIOD_FOREVER'),
	    className: babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] === 'forever' ? activeClass : inactiveClass,
	    disabled: babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading](),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'forever')
	  });
	  return items;
	}
	function _isLoading2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] === 'loading';
	}
	function _onSkipMenuItemSelect2(period, event, item) {
	  var _item$getMenuWindow, _item$getMenuWindow$g;
	  (_item$getMenuWindow = item.getMenuWindow()) == null ? void 0 : (_item$getMenuWindow$g = _item$getMenuWindow.getRootMenuWindow()) == null ? void 0 : _item$getMenuWindow$g.close();
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] = 'loading';
	  babelHelpers.classPrivateFieldLooseBase(this, _skipProvider)[_skipProvider].saveSkippedPeriod(period).then(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedMenuItemId)[_selectedMenuItemId] = period;
	  });
	}

	exports.TodoNotificationSkipMenu = TodoNotificationSkipMenu;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX.Main,BX,BX.Crm.Activity));
//# sourceMappingURL=todo-notification-skip-menu.bundle.js.map
