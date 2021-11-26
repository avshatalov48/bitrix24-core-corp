this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
this.BX.Salescenter.PaymentPay = this.BX.Salescenter.PaymentPay || {};
(function (exports) {
	'use strict';

	var option = {
	  methods: {
	    option: function option(name, defaultValue) {
	      var parts = name.split('.');
	      var currentOption = this.options;
	      var found = false;
	      parts.map(function (part) {
	        if (currentOption && currentOption.hasOwnProperty(part)) {
	          currentOption = currentOption[part];
	          found = true;
	        } else {
	          currentOption = null;
	          found = false;
	        }
	      });
	      return found ? currentOption : defaultValue;
	    }
	  }
	};

	exports.OptionMixin = option;

}((this.BX.Salescenter.PaymentPay.Mixins = this.BX.Salescenter.PaymentPay.Mixins || {})));
//# sourceMappingURL=mixins.bundle.js.map
