/* eslint-disable */
this.BX = this.BX || {};
this.BX.Baas = this.BX.Baas || {};
(function (exports,ui_iconSet,main_core,ui_dialogs_messagebox) {
	'use strict';

	class Notifier {
	  constructor(message) {
	    this.message = message;
	  }
	  getTitle() {
	    return main_core.Loc.getMessage('BAAS_ERROR_REGISTRATION_TITLE');
	  }
	  getMessage() {
	    return this.message;
	  }
	  show() {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: this.getTitle(),
	      message: this.getMessage(),
	      modal: true,
	      buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
	      maxWidth: 1000
	    });
	  }
	}

	let _ = t => t,
	  _t,
	  _t2;
	class Notifier0 extends Notifier {
	  constructor(message, effectiveUrl, body) {
	    super(message);
	    this.effectiveUrl = effectiveUrl;
	    this.body = body;
	  }
	  getTitle() {
	    return main_core.Loc.getMessage('BAAS_ERROR_REGISTRATION_TITLE_0');
	  }
	  getMessage() {
	    return main_core.Loc.getMessage('BAAS_ERROR_REGISTRATION_MESSAGE_0', {
	      '#effectiveUrl#': this.effectiveUrl
	    });
	  }
	  getEffectiveUrl() {
	    return this.effectiveUrl;
	  }
	  getBody() {
	    if (main_core.Type.isStringFilled(this.body)) {
	      return `<textarea style="width: 90%;">${main_core.Text.encode(this.body)}</textarea>`;
	    }
	    return '';
	  }
	  show() {
	    const copyButton = main_core.Tag.render(_t || (_t = _`<div class="ui-icon-set --copy-plates"></div>`));
	    BX.clipboard.bindCopyClick(copyButton, {
	      text: () => {
	        return JSON.stringify({
	          effectiveUrl: this.getEffectiveUrl(),
	          body: this.getBody()
	        });
	      }
	    });
	    ui_dialogs_messagebox.MessageBox.show({
	      title: this.getTitle(),
	      message: main_core.Tag.render(_t2 || (_t2 = _`
				<div>${0}
					<dd>
						<dt>${0} ${0}</dt>
						<dl>${0}</dl>
					</dd>
				</div>
			`), this.getMessage(), this.getEffectiveUrl(), copyButton, this.getBody()),
	      modal: true,
	      buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
	      maxWidth: 1000
	    });
	  }
	}
	Notifier0.STATUS = 0;
	Notifier0.CODE = 9713;

	class Notifier200 extends Notifier0 {
	  getMessage() {
	    return main_core.Loc.getMessage('BAAS_ERROR_REGISTRATION_MESSAGE_200', {
	      '#effectiveUrl#': this.effectiveUrl
	    });
	  }
	}
	Notifier200.STATUS = 200;

	class Notifier403 extends Notifier0 {
	  getTitle() {
	    return main_core.Loc.getMessage('BAAS_ERROR_REGISTRATION_TITLE_403');
	  }
	  getMessage() {
	    return main_core.Loc.getMessage('BAAS_ERROR_REGISTRATION_MESSAGE_403', {
	      '#effectiveUrl#': this.effectiveUrl
	    });
	  }
	}
	Notifier403.STATUS = 403;

	class ErrorNotifierFactory {
	  static createFromResponse(response = {}) {
	    if (response && main_core.Type.isArrayLike(response.errors)) {
	      const error = [...response.errors].shift();
	      if (error.customData && error.customData.status) {
	        switch (error.customData.status) {
	          case Notifier0.STATUS:
	            return new Notifier0(error.message, error.customData.effective_url, error.customData.body);
	          case Notifier403.STATUS:
	            return new Notifier403(error.message, error.customData.effective_url, error.customData.body);
	          default:
	            return new Notifier200(error.message, error.customData.effective_url, error.customData.body);
	        }
	      }
	      switch (error.code) {
	        case Notifier0.CODE:
	          return new Notifier0(error.message, error.customData.effective_url, error.customData.body);
	        case Notifier403.CODE:
	          return new Notifier403(error.message, error.customData.effective_url, error.customData.body);
	        default:
	          return new Notifier(error.message);
	      }
	    }
	    return new Notifier();
	  }
	}

	class SuccessNotifier {
	  show() {
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('BAAS_SUCCESS_REGISTRATION_TITLE'),
	      message: main_core.Loc.getMessage('BAAS_SUCCESS_REGISTRATION_MESSAGE', {
	        '#LINK#': '/bitrix/admin/baas_marketplace.php'
	      }),
	      modal: true,
	      buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
	      maxWidth: 1000
	    });
	  }
	}

	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	class Registration {
	  constructor() {
	    this.ready = true;
	  }
	  bind(node) {
	    main_core.Event.bind(node, 'click', this.send.bind(this));
	    return this;
	  }
	  send() {
	    return new Promise((resolve, reject) => {
	      if (this.ready === false) {
	        reject();
	      } else {
	        this.ready = false;
	        main_core.ajax.runAction('baas.Host.register', {
	          data: {}
	        }).then(response => {
	          this.ready = true;
	          new SuccessNotifier().show();
	          resolve();
	        }).catch(response => {
	          this.ready = true;
	          ErrorNotifierFactory.createFromResponse(response).show();
	          reject();
	        });
	      }
	    });
	  }
	  static getInstance() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] = new this();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	}
	Object.defineProperty(Registration, _instance, {
	  writable: true,
	  value: null
	});

	exports.Registration = Registration;

}((this.BX.Baas.Utility = this.BX.Baas.Utility || {}),BX,BX,BX.UI.Dialogs));
//# sourceMappingURL=registration.bundle.js.map
