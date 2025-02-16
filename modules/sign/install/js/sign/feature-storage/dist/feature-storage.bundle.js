/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	const settings = main_core.Extension.getSettings('sign.feature-storage');
	class FeatureStorage {
	  static isSendDocumentByEmployeeEnabled() {
	    return settings.get('isSendDocumentByEmployeeEnabled', false);
	  }
	  static isMultiDocumentLoadingEnabled() {
	    return settings.get('isMultiDocumentLoadingEnabled', false);
	  }
	  static isGroupSendingEnabled() {
	    return settings.get('isGroupSendingEnabled', false);
	  }
	}

	exports.FeatureStorage = FeatureStorage;

}((this.BX.Sign = this.BX.Sign || {}),BX));
//# sourceMappingURL=feature-storage.bundle.js.map
