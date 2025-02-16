/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core_events,ui_entitySelector) {
	'use strict';

	const userEntityTypeId = 'user';
	const UserSelectorEvent = Object.freeze({
	  onShow: 'onShow',
	  onHide: 'onHide',
	  onItemSelect: 'onItemSelect',
	  onItemDeselect: 'onItemDeselect'
	});
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	class UserSelector extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$multiple, _options$context, _options$preselectedI;
	    super();
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: null
	    });
	    this.setEventNamespace('BX.Sign.V2.B2e.UserSelector');
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = options.container;
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = new ui_entitySelector.Dialog({
	      width: 425,
	      height: 363,
	      multiple: (_options$multiple = options.multiple) != null ? _options$multiple : true,
	      targetNode: babelHelpers.classPrivateFieldLooseBase(this, _container)[_container],
	      context: (_options$context = options.context) != null ? _options$context : 'sign_b2e_user_selector',
	      entities: [{
	        id: userEntityTypeId,
	        options: {
	          intranetUsersOnly: true
	        }
	      }],
	      dropdownMode: true,
	      enableSearch: true,
	      preselectedItems: (_options$preselectedI = options.preselectedIds) == null ? void 0 : _options$preselectedI.map(id => [userEntityTypeId, id]),
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
	  toggle() {
	    this.getDialog().show();
	  }
	  getDialog() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog];
	  }
	}

	exports.UserSelectorEvent = UserSelectorEvent;
	exports.UserSelector = UserSelector;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Event,BX.UI.EntitySelector));
//# sourceMappingURL=user-selector.bundle.js.map
