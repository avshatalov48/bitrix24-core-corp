this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports,main_core_events,sale_paymentPay_const) {
	'use strict';

	var UserConsent = /*#__PURE__*/function () {
	  /**
	   * @public
	   * @param {object} options
	   */
	  function UserConsent(options) {
	    babelHelpers.classCallCheck(this, UserConsent);
	    this.options = options || {};
	    this.accepted = this.option('accepted', false);
	    this.container = document.getElementById(this.option('containerId'), '');
	    this.eventName = this.option('eventName', false);
	    this.callback = null;
	    this.subscribeToEvents();
	  }
	  /**
	   * @private
	   */


	  babelHelpers.createClass(UserConsent, [{
	    key: "subscribeToEvents",
	    value: function subscribeToEvents() {
	      var _this = this;

	      main_core_events.EventEmitter.subscribe(sale_paymentPay_const.EventType.consent.accepted, function (event) {
	        _this.accepted = true;

	        if (_this.callback) {
	          _this.callback();
	        }
	      });
	      main_core_events.EventEmitter.subscribe(sale_paymentPay_const.EventType.consent.refused, function (event) {
	        _this.accepted = false;
	        _this.callback = null;
	      });
	    }
	    /**
	     * @public
	     * @returns {boolean}
	     */

	  }, {
	    key: "isAvailable",
	    value: function isAvailable() {
	      return BX.UserConsent && this.eventName;
	    }
	    /**
	     * @public
	     * @param callback
	     */

	  }, {
	    key: "askUserToPerform",
	    value: function askUserToPerform(callback) {
	      if (!this.isAvailable() || this.accepted) {
	        callback();
	        return;
	      }

	      this.callback = callback;
	      main_core_events.EventEmitter.emit(this.eventName);

	      if (this.checkCurrentConsent()) {
	        callback();
	      }
	    }
	  }, {
	    key: "checkCurrentConsent",
	    value: function checkCurrentConsent() {
	      if (!BX.UserConsent || !BX.UserConsent.current) {
	        return false;
	      }

	      return BX.UserConsent.check(BX.UserConsent.current);
	    }
	    /**
	     * @private
	     * @param {string} name
	     * @param defaultValue
	     * @returns {*}
	     */

	  }, {
	    key: "option",
	    value: function option(name, defaultValue) {
	      return this.options.hasOwnProperty(name) ? this.options[name] : defaultValue;
	    }
	  }]);
	  return UserConsent;
	}();

	exports.UserConsent = UserConsent;

}((this.BX.Salescenter.PaymentPay = this.BX.Salescenter.PaymentPay || {}),BX.Event,BX.Sale.PaymentPay.Const));
//# sourceMappingURL=user-consent.bundle.js.map
