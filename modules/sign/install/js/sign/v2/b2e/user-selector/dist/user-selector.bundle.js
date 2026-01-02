/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core_events,ui_entitySelector,sign_type) {
	'use strict';

	const UserSelectorEvent = Object.freeze({
	  onShow: 'onShow',
	  onHide: 'onHide',
	  onItemSelect: 'onItemSelect',
	  onItemDeselect: 'onItemDeselect'
	});
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _isRoleEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRoleEnabled");
	var _cacheable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cacheable");
	var _excludedEntityList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("excludedEntityList");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _preselectedEntityList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preselectedEntityList");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	var _getEntities = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEntities");
	var _getExcludedIdListByEntityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getExcludedIdListByEntityType");
	class UserSelector extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$roleEnabled, _options$excludedEnti, _options$cacheable;
	    super();
	    Object.defineProperty(this, _getExcludedIdListByEntityType, {
	      value: _getExcludedIdListByEntityType2
	    });
	    Object.defineProperty(this, _getEntities, {
	      value: _getEntities2
	    });
	    Object.defineProperty(this, _createDialog, {
	      value: _createDialog2
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isRoleEnabled, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _cacheable, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _excludedEntityList, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _preselectedEntityList, {
	      writable: true,
	      value: []
	    });
	    this.setEventNamespace('BX.Sign.V2.B2e.UserSelector');
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _isRoleEnabled)[_isRoleEnabled] = (_options$roleEnabled = options.roleEnabled) != null ? _options$roleEnabled : false;
	    babelHelpers.classPrivateFieldLooseBase(this, _excludedEntityList)[_excludedEntityList] = (_options$excludedEnti = options.excludedEntityList) != null ? _options$excludedEnti : [];
	    babelHelpers.classPrivateFieldLooseBase(this, _cacheable)[_cacheable] = (_options$cacheable = options.cacheable) != null ? _options$cacheable : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	  }
	  setExcludedEntityList(excludedEntityList) {
	    babelHelpers.classPrivateFieldLooseBase(this, _excludedEntityList)[_excludedEntityList] = excludedEntityList;
	  }
	  setPreselectedEntityList(preselectedEntityList) {
	    babelHelpers.classPrivateFieldLooseBase(this, _preselectedEntityList)[_preselectedEntityList] = preselectedEntityList;
	  }
	  toggle() {
	    this.getDialog().show();
	  }
	  getDialog() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _cacheable)[_cacheable] === false) {
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog];
	  }
	}
	function _createDialog2() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3;
	  const preselectedEntityList = babelHelpers.classPrivateFieldLooseBase(this, _preselectedEntityList)[_preselectedEntityList].length > 0 ? babelHelpers.classPrivateFieldLooseBase(this, _preselectedEntityList)[_preselectedEntityList].map(entity => [entity.type, entity.id]) : (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].preselectedIds) == null ? void 0 : _babelHelpers$classPr.map(id => [sign_type.EntityType.USER, id]);
	  return new ui_entitySelector.Dialog({
	    cacheable: babelHelpers.classPrivateFieldLooseBase(this, _cacheable)[_cacheable],
	    width: 425,
	    height: 363,
	    multiple: (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].multiple) != null ? _babelHelpers$classPr2 : true,
	    targetNode: babelHelpers.classPrivateFieldLooseBase(this, _container)[_container],
	    context: (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].context) != null ? _babelHelpers$classPr3 : 'sign_b2e_user_selector',
	    entities: babelHelpers.classPrivateFieldLooseBase(this, _getEntities)[_getEntities](),
	    dropdownMode: false,
	    enableSearch: true,
	    preselectedItems: preselectedEntityList,
	    hideOnDeselect: true,
	    events: {
	      onHide: event => this.emit(UserSelectorEvent.onHide, {
	        items: babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems()
	      }),
	      'Item:onSelect': event => this.emit(UserSelectorEvent.onItemSelect, {
	        items: babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems()
	      }),
	      'Item:onDeselect': event => this.emit(UserSelectorEvent.onItemSelect, {
	        items: babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems()
	      })
	    }
	  });
	}
	function _getEntities2() {
	  const entities = [{
	    id: sign_type.EntityType.USER,
	    options: {
	      intranetUsersOnly: true,
	      '!userId': babelHelpers.classPrivateFieldLooseBase(this, _getExcludedIdListByEntityType)[_getExcludedIdListByEntityType](sign_type.EntityType.USER)
	    },
	    dynamicLoad: true
	  }];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isRoleEnabled)[_isRoleEnabled]) {
	    entities.push({
	      id: sign_type.EntityType.STRUCTURE_NODE_ROLE,
	      options: {
	        excludedRoleIdList: babelHelpers.classPrivateFieldLooseBase(this, _getExcludedIdListByEntityType)[_getExcludedIdListByEntityType](sign_type.EntityType.STRUCTURE_NODE_ROLE)
	      },
	      dynamicLoad: true,
	      dynamicSearch: true
	    });
	  }
	  return entities;
	}
	function _getExcludedIdListByEntityType2(entityType) {
	  const excludedEntityList = babelHelpers.classPrivateFieldLooseBase(this, _excludedEntityList)[_excludedEntityList].filter(entity => entity.entityType === entityType);
	  return excludedEntityList.map(entity => entity.entityId);
	}

	exports.UserSelectorEvent = UserSelectorEvent;
	exports.UserSelector = UserSelector;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Event,BX.UI.EntitySelector,BX.Sign));
//# sourceMappingURL=user-selector.bundle.js.map
