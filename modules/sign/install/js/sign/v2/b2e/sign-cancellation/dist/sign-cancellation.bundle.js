/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,ui_notification,main_core,ui_dialogs_messagebox) {
	'use strict';

	var _isCancellationInProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCancellationInProgress");
	class SignCancellation {
	  constructor() {
	    Object.defineProperty(this, _isCancellationInProgress, {
	      writable: true,
	      value: false
	    });
	  }
	  cancelWithConfirm(documentUid) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isCancellationInProgress)[_isCancellationInProgress]) {
	      return;
	    }
	    const signingCancellationDialog = new ui_dialogs_messagebox.MessageBox({
	      title: main_core.Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_DIALOG_TITLE'),
	      message: main_core.Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_DIALOG_TEXT'),
	      modal: true
	    });
	    const yesButton = new BX.UI.Button({
	      text: main_core.Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_DIALOG_YES_BUTTON_TEXT'),
	      color: BX.UI.Button.Color.DANGER,
	      onclick: button => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _isCancellationInProgress)[_isCancellationInProgress] === true) {
	          return;
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _isCancellationInProgress)[_isCancellationInProgress] = true;
	        button.setState(BX.UI.Button.State.WAITING);
	        void this.cancelSigningProcess(documentUid).finally(() => {
	          babelHelpers.classPrivateFieldLooseBase(this, _isCancellationInProgress)[_isCancellationInProgress] = false;
	          signingCancellationDialog.close();
	        });
	      }
	    });
	    const noButton = new BX.UI.Button({
	      text: main_core.Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_DIALOG_NO_BUTTON_TEXT'),
	      color: BX.UI.Button.Color.LIGHT_BORDER,
	      onclick: () => {
	        signingCancellationDialog.close();
	      }
	    });
	    signingCancellationDialog.setButtons([yesButton, noButton]);
	    signingCancellationDialog.show();
	  }
	  cancelSigningProcess(documentUid) {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('sign.api_v1.document.signing.stop', {
	        json: {
	          uid: documentUid
	        },
	        preparePost: false
	      }).then(response => {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_SUCCESS'),
	          autoHideDelay: 5000
	        });
	        resolve(response);
	      }, response => {
	        response.errors.forEach(error => {
	          ui_notification.UI.Notification.Center.notify({
	            content: error.message,
	            autoHideDelay: 5000
	          });
	        });
	        reject(response.errors);
	      }).catch(() => {
	        reject();
	      });
	    });
	  }
	}

	exports.SignCancellation = SignCancellation;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX,BX.UI.Dialogs));
//# sourceMappingURL=sign-cancellation.bundle.js.map
