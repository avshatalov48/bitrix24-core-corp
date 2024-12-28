/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
(function (exports,im_v2_application_core,imopenlines_v2_provider_service) {
	'use strict';

	const OpenLinesManager = {
	  async handleChatLoadResponse(sessionData) {
	    if (!sessionData) {
	      return Promise.resolve();
	    }
	    return im_v2_application_core.Core.getStore().dispatch('sessions/set', sessionData);
	  }
	};

	exports.OpenLinesManager = OpenLinesManager;

}((this.BX.OpenLines.v2.Lib = this.BX.OpenLines.v2.Lib || {}),BX.Messenger.v2.Application,BX.OpenLines.v2.Provider.Service));
//# sourceMappingURL=openlines.bundle.js.map
