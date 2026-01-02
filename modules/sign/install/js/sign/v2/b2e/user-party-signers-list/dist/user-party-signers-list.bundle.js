/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_b2e_userParty) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	class UserPartySignersList extends sign_v2_b2e_userParty.UserParty {
	  constructor() {
	    super({
	      mode: 'edit'
	    });
	  }
	  getLayout(region) {
	    if (this.ui.container) {
	      return this.ui.container;
	    }
	    this.ui.itemContainer = main_core.Tag.render(_t || (_t = _`
			<div class="sign-document-b2e-user-party__item-list"></div>
		`));
	    return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-b2e-settings_signers-list">
				<div class="sign-b2e-settings__header-wrapper">
					<h1 class="sign-b2e-settings__header">${0}</h1>
					${0}
				</div>
				<div class="sign-b2e-settings__item">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('SIGN_USER_PARTY_HEADER'), this.userPartyCounters.getLayout(), this.ui.itemContainer);
	  }
	  isRejectExcludedEnabled() {
	    return false;
	  }
	}

	exports.UserPartySignersList = UserPartySignersList;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2.B2e));
//# sourceMappingURL=user-party-signers-list.bundle.js.map
