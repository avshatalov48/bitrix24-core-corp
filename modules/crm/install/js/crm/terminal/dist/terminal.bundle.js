/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,ui_qrauthorization) {
	'use strict';

	var _createQrAuthorization = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createQrAuthorization");
	class QrAuth {
	  constructor(options = {}) {
	    Object.defineProperty(this, _createQrAuthorization, {
	      value: _createQrAuthorization2
	    });
	    this.settingsCollection = main_core.Extension.getSettings('crm.terminal');
	    this.intent = options.intent || this.settingsCollection.get('intent');
	    this.title = options.title || main_core.Loc.getMessage('TERMINAL_QR_AUTH_TITLE');
	    this.content = options.content || main_core.Loc.getMessage('TERMINAL_QR_AUTH_CONTENT_MSGVER_1');
	    this.popup = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _createQrAuthorization)[_createQrAuthorization]();
	  }
	  show() {
	    this.popup.show();
	  }
	}
	function _createQrAuthorization2() {
	  if (!this.popup) {
	    this.popup = new ui_qrauthorization.QrAuthorization({
	      intent: this.intent,
	      title: this.title,
	      content: this.content,
	      popupParam: {
	        overlay: true
	      }
	    });
	  }
	}

	exports.QrAuth = QrAuth;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.UI));
//# sourceMappingURL=terminal.bundle.js.map
