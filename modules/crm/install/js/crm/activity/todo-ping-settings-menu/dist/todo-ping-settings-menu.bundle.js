this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_popup) {
	'use strict';

	const MENU_ITEM_CLASS_ACTIVE = 'menu-popup-item-accept';
	const MENU_ITEM_CLASS_INACTIVE = 'menu-popup-item-none';
	const SAVE_OFFSETS_REQUEST_DELAY = 750;
	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _settings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("settings");
	var _selectedOffsets = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedOffsets");
	var _isLoadingMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLoadingMenuItem");
	var _getPintSettingsMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPintSettingsMenuItems");
	var _getMenuItemClass = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemClass");
	var _isLoading = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLoading");
	var _onMenuItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMenuItemClick");
	class TodoPingSettingsMenu {
	  constructor(params) {
	    Object.defineProperty(this, _onMenuItemClick, {
	      value: _onMenuItemClick2
	    });
	    Object.defineProperty(this, _isLoading, {
	      value: _isLoading2
	    });
	    Object.defineProperty(this, _getMenuItemClass, {
	      value: _getMenuItemClass2
	    });
	    Object.defineProperty(this, _getPintSettingsMenuItems, {
	      value: _getPintSettingsMenuItems2
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _settings, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _selectedOffsets, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isLoadingMenuItem, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = params.entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings] = params.settings;
	    if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].optionName)) {
	      throw new Error('Option name are not defined.');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets] = babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].currentOffsets || [];
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets] = babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets].map(element => parseInt(element, 10));
	    if (!main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].currentOffsets)) {
	      throw new Error('Offsets are not defined.');
	    }
	  }
	  setSelectedValues(values) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets] = values.map(element => parseInt(element, 10));
	  }
	  getItems() {
	    const items = [];
	    items.push({
	      id: 'askForSetupTodoPing',
	      text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_PING_SETTINGS_MENU_ITEM'),
	      className: MENU_ITEM_CLASS_INACTIVE,
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getPintSettingsMenuItems)[_getPintSettingsMenuItems]()
	    });
	    return items;
	  }
	}
	function _getPintSettingsMenuItems2() {
	  if (main_core.Type.isNull(babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].offsetList) || !main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].offsetList)) {
	    return [];
	  }
	  const items = [];
	  babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].offsetList.forEach(item => {
	    items.push({
	      id: item.id,
	      text: item.title,
	      className: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemClass)[_getMenuItemClass](parseInt(item.offset, 10)),
	      disabled: babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading](),
	      onclick: babelHelpers.classPrivateFieldLooseBase(this, _onMenuItemClick)[_onMenuItemClick].bind(this, parseInt(item.offset, 10))
	    });
	  });
	  return items;
	}
	function _getMenuItemClass2(offset) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets].includes(offset) ? MENU_ITEM_CLASS_ACTIVE : MENU_ITEM_CLASS_INACTIVE;
	}
	function _isLoading2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _isLoadingMenuItem)[_isLoadingMenuItem];
	}
	function _onMenuItemClick2(offset, event, item) {
	  babelHelpers.classPrivateFieldLooseBase(this, _isLoadingMenuItem)[_isLoadingMenuItem] = true;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets].includes(offset)) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets].length === 1) {
	      BX.UI.Hint.show(item.getContainer(), main_core.Loc.getMessage('CRM_ACTIVITY_TODO_PING_SETTINGS_MENU_ITEM_TOOLTIP'));
	      babelHelpers.classPrivateFieldLooseBase(this, _isLoadingMenuItem)[_isLoadingMenuItem] = false;
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets] = babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets].filter(value => value !== offset);
	    main_core.Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
	    main_core.Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets].push(offset);
	    main_core.Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
	    main_core.Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets].length === 0) {
	    throw new Error('Offsets are not defined.');
	  }
	  setTimeout(() => {
	    BX.userOptions.save('crm', babelHelpers.classPrivateFieldLooseBase(this, _settings)[_settings].optionName, 'offsets', babelHelpers.classPrivateFieldLooseBase(this, _selectedOffsets)[_selectedOffsets].join(','));
	    babelHelpers.classPrivateFieldLooseBase(this, _isLoadingMenuItem)[_isLoadingMenuItem] = false;
	  }, SAVE_OFFSETS_REQUEST_DELAY);
	}

	exports.TodoPingSettingsMenu = TodoPingSettingsMenu;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX,BX.Main));
//# sourceMappingURL=todo-ping-settings-menu.bundle.js.map
