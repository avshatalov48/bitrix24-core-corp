/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_feedback_form,main_core$1) {
	'use strict';

	let _ = t => t,
	  _t;
	BX.namespace("BX.Crm");
	class Button {
	  static render(parentNode) {
	    const buttonTitle = main_core.Loc.getMessage('CRM_FEEDBACK_BUTTON_TITLE');
	    const button = main_core$1.Tag.render(_t || (_t = _`
			<button class="ui-btn ui-btn-light-border ui-btn-themes" title="${0}">
				<span class="ui-btn-text">
					${0}
				</span>
			</button>
		`), buttonTitle, buttonTitle);
	    button.addEventListener('click', () => {
	      BX.Crm.Terminal.Slider.openFeedbackForm();
	    });
	    if (!parentNode) {
	      return;
	    }
	    parentNode.appendChild(button);
	    parentNode.style.justifyContent = 'space-between';
	    return button;
	  }
	}

	BX.namespace("BX.Crm");
	class Slider {
	  static openFeedbackForm() {
	    void main_core$1.Runtime.loadExtension(['ui.feedback.form']).then(() => {
	      const settings = main_core$1.Runtime.getSettings('crm.terminal-detail');
	      const hasPayment = settings.get('hasPaymentSystemConfigured');
	      const hasCashbox = settings.get('hasCashboxConfigured');
	      const formIdNumber = Math.round(Math.random() * 1000);
	      BX.UI.Feedback.Form.open({
	        id: `crm.feedback-${formIdNumber}`,
	        forms: [{
	          zones: ['en'],
	          id: 630,
	          lang: 'en',
	          sec: 'ypq6nz'
	        }, {
	          zones: ['com.br'],
	          id: 632,
	          lang: 'com.br',
	          sec: 'ama2ql'
	        }, {
	          zones: ['ru', 'by', 'kz'],
	          id: 628,
	          lang: 'ru',
	          sec: 'rgyboj'
	        }],
	        presets: {
	          sender_page: 'terminal',
	          is_payment_system: hasPayment ? 'yes' : 'no',
	          is_cashbox: hasCashbox ? 'yes' : 'no'
	        }
	      });
	    });
	  }
	  static open(url, options) {
	    if (!main_core$1.Type.isPlainObject(options)) {
	      options = {};
	    }
	    options = {
	      ...{
	        cacheable: false,
	        allowChangeHistory: false,
	        events: {}
	      },
	      ...options
	    };
	    return new Promise(resolve => {
	      if (main_core$1.Type.isString(url) && url.length > 1) {
	        options.events.onClose = function (event) {
	          resolve(event.getSlider());
	        };
	        BX.SidePanel.Instance.open(url, options);
	      } else {
	        resolve();
	      }
	    });
	  }
	}

	exports.FeedbackButton = Button;
	exports.Slider = Slider;

}((this.BX.Crm.Terminal = this.BX.Crm.Terminal || {}),BX.UI.Feedback,BX));
//# sourceMappingURL=terminal.bundle.js.map
