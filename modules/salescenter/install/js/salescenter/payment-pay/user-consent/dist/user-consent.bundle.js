/* eslint-disable */
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
	    this.eventName = this.option('eventName', false);
	    this.items = this.option('items', []);
	    this.preselectedConsentsIsSubmitted = false;
	    this.setCallback(null);
	    this.subscribeToEvents();
	  }
	  babelHelpers.createClass(UserConsent, [{
	    key: "setCallback",
	    value: function setCallback(callback) {
	      this.callback = callback;
	    }
	  }, {
	    key: "runCallback",
	    value: function runCallback() {
	      if (this.callback) {
	        this.callback();
	      }
	      this.setCallback(null);
	    }
	    /**
	     * @private
	     */
	  }, {
	    key: "subscribeToEvents",
	    value: function subscribeToEvents() {
	      var _this = this;
	      main_core_events.EventEmitter.subscribe(sale_paymentPay_const.EventType.consent.accepted, function (event) {
	        var currentId = event.getData()[0].config.id;
	        var currentItem = _this.getItemById(currentId);
	        if (currentItem) {
	          currentItem.checked = 'Y';
	        }
	        var firstRequiredUncheckedItem = _this.getFirstRequiredUncheckedItem();
	        if (firstRequiredUncheckedItem && _this.callback) {
	          main_core_events.EventEmitter.emit("".concat(_this.eventName, "-").concat(firstRequiredUncheckedItem.id));
	          return;
	        }
	        _this.runCallback();
	      });
	      main_core_events.EventEmitter.subscribe(sale_paymentPay_const.EventType.consent.refused, function (event) {
	        var currentId = event.getData()[0].config.id;
	        var currentItem = _this.getItemById(currentId);
	        if (currentItem) {
	          currentItem.checked = 'N';
	        }
	        _this.setCallback(null);
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
	      this.setCallback(callback);
	      if (!this.isAvailable()) {
	        this.runCallback();
	        return;
	      }
	      if (!this.preselectedConsentsIsSubmitted) {
	        this.sendEventsForCheckedConsents();
	        this.preselectedConsentsIsSubmitted = true;
	      }
	      var firstRequiredUncheckedItem = this.getFirstRequiredUncheckedItem();
	      if (firstRequiredUncheckedItem) {
	        main_core_events.EventEmitter.emit("".concat(this.eventName, "-").concat(firstRequiredUncheckedItem.id));
	        return;
	      }
	      this.runCallback();
	    }
	  }, {
	    key: "sendEventsForCheckedConsents",
	    value: function sendEventsForCheckedConsents() {
	      var _this2 = this;
	      this.items.forEach(function (item) {
	        if (item.checked === 'Y') {
	          main_core_events.EventEmitter.emit("".concat(_this2.eventName, "-").concat(item.id));
	        }
	      });
	    }
	  }, {
	    key: "getFirstRequiredUncheckedItem",
	    value: function getFirstRequiredUncheckedItem() {
	      var _this$items$find;
	      return (_this$items$find = this.items.find(function (item) {
	        return item.required === 'Y' && item.checked === 'N';
	      })) !== null && _this$items$find !== void 0 ? _this$items$find : null;
	    }
	  }, {
	    key: "getItemById",
	    value: function getItemById(currentId) {
	      var _this$items$find2;
	      return (_this$items$find2 = this.items.find(function (item) {
	        return parseInt(item.id, 10) === currentId;
	      })) !== null && _this$items$find2 !== void 0 ? _this$items$find2 : null;
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
	      return Object.hasOwn(this.options, name) ? this.options[name] : defaultValue;
	    }
	  }]);
	  return UserConsent;
	}();

	exports.UserConsent = UserConsent;

}((this.BX.Salescenter.PaymentPay = this.BX.Salescenter.PaymentPay || {}),BX.Event,BX.Sale.PaymentPay.Const));
//# sourceMappingURL=user-consent.bundle.js.map
