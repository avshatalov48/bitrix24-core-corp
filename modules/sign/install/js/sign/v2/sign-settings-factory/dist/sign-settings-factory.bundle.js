this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,sign_v2_b2b_signSettings,sign_v2_b2e_signSettings) {
	'use strict';

	const settings = {
	  b2b: sign_v2_b2b_signSettings.B2BSignSettings,
	  b2e: sign_v2_b2e_signSettings.B2ESignSettings
	};
	function createSignSettings(containerId, options) {
	  var _settings$type;
	  const {
	    type,
	    uid
	  } = options;
	  const SignSettingsConstructor = (_settings$type = settings[type]) != null ? _settings$type : sign_v2_b2b_signSettings.B2BSignSettings;
	  const signSettings = new SignSettingsConstructor(containerId, options);
	  signSettings.init(uid);
	}

	exports.createSignSettings = createSignSettings;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX.Sign.V2.B2b,BX.Sign.V2.B2e));
//# sourceMappingURL=sign-settings-factory.bundle.js.map
