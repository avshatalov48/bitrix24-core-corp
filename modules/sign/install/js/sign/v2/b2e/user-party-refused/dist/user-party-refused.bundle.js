/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,ui_switcher,ui_designTokens,sign_v2_helper) {
	'use strict';

	let _ = t => t,
	  _t;
	const HelpdeskCodes = Object.freeze({
	  RejectedListDetails: '25375700'
	});
	var _switcher = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("switcher");
	class UserPartyRefused extends main_core.Event.EventEmitter {
	  constructor() {
	    super();
	    Object.defineProperty(this, _switcher, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.B2E.UserPartyRefused');
	    babelHelpers.classPrivateFieldLooseBase(this, _switcher)[_switcher] = new ui_switcher.Switcher({
	      size: ui_switcher.SwitcherSize.extraSmall,
	      color: ui_switcher.SwitcherColor.primary,
	      checked: true,
	      handlers: {
	        toggled: () => {
	          this.emit('onChange', {
	            checked: babelHelpers.classPrivateFieldLooseBase(this, _switcher)[_switcher].isChecked()
	          });
	        }
	      }
	    });
	  }
	  shouldRemoveRefused() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _switcher)[_switcher].isChecked();
	  }
	  render() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="sign-user-party-refused">
				${0}
				<span class="sign-user-party-refused-desc">
					${0}
				</span>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _switcher)[_switcher].getNode(), sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_B2E_USER_PARTY_REJECTED_DESC_MSGVER_1'), HelpdeskCodes.RejectedListDetails));
	  }
	}

	exports.UserPartyRefused = UserPartyRefused;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.UI,BX,BX.Sign.V2));
//# sourceMappingURL=user-party-refused.bundle.js.map
