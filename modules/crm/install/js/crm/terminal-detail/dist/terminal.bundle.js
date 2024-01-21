/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_feedback_form,main_core) {
	'use strict';

	let _ = t => t,
	  _t;
	BX.namespace("BX.Crm");
	class Button {
	  static render(parentNode) {
	    const buttonTitle = main_core.Loc.getMessage('CRM_FEEDBACK_BUTTON_TITLE');
	    const button = main_core.Tag.render(_t || (_t = _`
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
	    const url = new main_core.Uri('/bitrix/components/bitrix/crm.feedback/slider.php');
	    url.setQueryParams({
	      sender_page: 'terminal'
	    });
	    return Slider.open(url.toString(), {
	      width: 735
	    });
	  }
	  static open(url, options) {
	    if (!main_core.Type.isPlainObject(options)) {
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
	      if (main_core.Type.isString(url) && url.length > 1) {
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

}((this.BX.Crm.Terminal = this.BX.Crm.Terminal || {}),BX,BX));
//# sourceMappingURL=terminal.bundle.js.map
