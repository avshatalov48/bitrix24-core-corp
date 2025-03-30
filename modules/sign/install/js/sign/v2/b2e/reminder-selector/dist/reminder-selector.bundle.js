/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,ui_buttons,sign_v2_api,sign_type) {
	'use strict';

	let _ = t => t,
	  _t;
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _button = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("button");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _chosenTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chosenTypeId");
	var _getItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getItems");
	var _getButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButton");
	var _chooseTypeById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chooseTypeById");
	var _getOptionById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptionById");
	var _getAvailableOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAvailableOptions");
	class ReminderSelector {
	  constructor(options = {}) {
	    Object.defineProperty(this, _getAvailableOptions, {
	      value: _getAvailableOptions2
	    });
	    Object.defineProperty(this, _getOptionById, {
	      value: _getOptionById2
	    });
	    Object.defineProperty(this, _chooseTypeById, {
	      value: _chooseTypeById2
	    });
	    Object.defineProperty(this, _getButton, {
	      value: _getButton2
	    });
	    Object.defineProperty(this, _getItems, {
	      value: _getItems2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _button, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _chosenTypeId, {
	      writable: true,
	      value: sign_type.Reminder.none
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _button)[_button] = babelHelpers.classPrivateFieldLooseBase(this, _getButton)[_getButton]();
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    if (!main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].preSelectedType)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _chooseTypeById)[_chooseTypeById](babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].preSelectedType);
	    }
	  }
	  getLayout() {
	    return main_core.Tag.render(_t || (_t = _`
				<div class="sign-reminder-selector">
				<span class="sign-reminder-selector__label">
					${0}
				</span>
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _button)[_button].getContainer());
	  }
	  save(documentUid, memberRole) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].modifyReminderTypeForMemberRole(documentUid, memberRole, babelHelpers.classPrivateFieldLooseBase(this, _chosenTypeId)[_chosenTypeId]);
	  }
	}
	function _getItems2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getAvailableOptions)[_getAvailableOptions]().map(reminderType => {
	    return {
	      text: reminderType.name,
	      onclick: () => babelHelpers.classPrivateFieldLooseBase(this, _chooseTypeById)[_chooseTypeById](reminderType.id)
	    };
	  });
	}
	function _getButton2() {
	  return new ui_buttons.Button({
	    text: babelHelpers.classPrivateFieldLooseBase(this, _getOptionById)[_getOptionById](sign_type.Reminder.none).name,
	    dropdown: true,
	    closeByEsc: true,
	    autoHide: true,
	    autoClose: true,
	    color: BX.UI.Button.Color.LIGHT,
	    size: BX.UI.Button.Size.SMALL,
	    menu: {
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getItems)[_getItems]()
	    },
	    className: 'sign-reminder-selector__button'
	  });
	}
	function _chooseTypeById2(reminderTypeId) {
	  babelHelpers.classPrivateFieldLooseBase(this, _button)[_button].menuWindow.close();
	  const option = babelHelpers.classPrivateFieldLooseBase(this, _getOptionById)[_getOptionById](reminderTypeId);
	  if (!option) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _button)[_button].setText(option.name);
	  babelHelpers.classPrivateFieldLooseBase(this, _chosenTypeId)[_chosenTypeId] = option.id;
	}
	function _getOptionById2(reminderTypeId) {
	  var _babelHelpers$classPr;
	  return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getAvailableOptions)[_getAvailableOptions]().find(option => option.id === reminderTypeId)) != null ? _babelHelpers$classPr : null;
	}
	function _getAvailableOptions2() {
	  return [{
	    id: sign_type.Reminder.none,
	    name: main_core.Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_NONE')
	  }, {
	    id: sign_type.Reminder.oncePerDay,
	    name: main_core.Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_ONCE_PER_DAY')
	  }, {
	    id: sign_type.Reminder.twicePerDay,
	    name: main_core.Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_TWICE_PER_DAY')
	  }, {
	    id: sign_type.Reminder.threeTimesPerDay,
	    name: main_core.Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_THREE_TIMES_PER_DAY')
	  }];
	}

	exports.ReminderSelector = ReminderSelector;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.UI,BX.Sign.V2,BX.Sign));
//# sourceMappingURL=reminder-selector.bundle.js.map
