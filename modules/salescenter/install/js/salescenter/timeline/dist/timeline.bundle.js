this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var Base = /*#__PURE__*/function () {
	  function Base(props) {
	    babelHelpers.classCallCheck(this, Base);
	    this.icon = this.getIcon();
	    this.type = this.getType();
	    this.content = main_core.Type.isString(props.content) && props.content.length > 0 ? props.content : '';
	    this.disabled = main_core.Type.isBoolean(props.disabled) ? props.disabled : false;
	  }

	  babelHelpers.createClass(Base, [{
	    key: "getType",
	    value: function getType() {
	      return '';
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return '';
	    }
	  }]);
	  return Base;
	}();

	var Cash = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Cash, _Base);

	  function Cash() {
	    babelHelpers.classCallCheck(this, Cash);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Cash).apply(this, arguments));
	  }

	  babelHelpers.createClass(Cash, [{
	    key: "getType",
	    value: function getType() {
	      return Cash.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'cash';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'cash';
	    }
	  }]);
	  return Cash;
	}(Base);

	var Sent = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Sent, _Base);

	  function Sent(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Sent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sent).call(this, props));
	    _this.url = main_core.Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	    return _this;
	  }

	  babelHelpers.createClass(Sent, [{
	    key: "getType",
	    value: function getType() {
	      return Sent.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'sent';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'sent';
	    }
	  }]);
	  return Sent;
	}(Base);

	var Check = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Check, _Base);

	  function Check(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Check);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Check).call(this, props));
	    _this.url = main_core.Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	    return _this;
	  }

	  babelHelpers.createClass(Check, [{
	    key: "getType",
	    value: function getType() {
	      return Check.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'check';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'check';
	    }
	  }]);
	  return Check;
	}(Base);

	var Watch = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Watch, _Base);

	  function Watch() {
	    babelHelpers.classCallCheck(this, Watch);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Watch).apply(this, arguments));
	  }

	  babelHelpers.createClass(Watch, [{
	    key: "getType",
	    value: function getType() {
	      return Watch.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'watch';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'watch';
	    }
	  }]);
	  return Watch;
	}(Base);

	var CheckSent = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(CheckSent, _Base);

	  function CheckSent(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, CheckSent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CheckSent).call(this, props));
	    _this.url = main_core.Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	    return _this;
	  }

	  babelHelpers.createClass(CheckSent, [{
	    key: "getType",
	    value: function getType() {
	      return CheckSent.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'check-sent';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'check-sent';
	    }
	  }]);
	  return CheckSent;
	}(Base);

	var Payment = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Payment, _Base);

	  function Payment(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Payment);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Payment).call(this, props));
	    _this.sum = typeof props.sum != 'undefined' ? props.sum : '0.00'; //.toFixed(2)

	    _this.title = main_core.Type.isString(props.title) && props.title.length > 0 ? props.title : '';
	    _this.currency = main_core.Type.isString(props.currency) && props.currency.length > 0 ? props.currency : '';
	    return _this;
	  }

	  babelHelpers.createClass(Payment, [{
	    key: "getType",
	    value: function getType() {
	      return Payment.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'cash';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'payment';
	    }
	  }]);
	  return Payment;
	}(Base);

	var items = [Cash, Check, CheckSent, Payment, Sent, Watch];

	var Factory = /*#__PURE__*/function () {
	  function Factory() {
	    babelHelpers.classCallCheck(this, Factory);
	  }

	  babelHelpers.createClass(Factory, null, [{
	    key: "create",
	    value: function create(options) {
	      var item = items.filter(function (item) {
	        return options.type === item.type();
	      })[0];

	      if (!item) {
	        throw new Error("Unknown field type '".concat(options.type, "'"));
	      }

	      return new item(options);
	    }
	  }]);
	  return Factory;
	}();

	exports.Base = Base;
	exports.Cash = Cash;
	exports.Sent = Sent;
	exports.Check = Check;
	exports.Watch = Watch;
	exports.Factory = Factory;
	exports.Payment = Payment;
	exports.CheckSent = CheckSent;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX));
//# sourceMappingURL=timeline.bundle.js.map
