this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var DateFormatter = /*#__PURE__*/function () {
	  function DateFormatter() {
	    babelHelpers.classCallCheck(this, DateFormatter);
	  }

	  babelHelpers.createClass(DateFormatter, [{
	    key: "init",
	    value: function init(params) {
	      this.formatLong = params.long;
	      this.formatShort = params.short;
	      this.isInitialized = true;
	    }
	  }, {
	    key: "isInit",
	    value: function isInit() {
	      return this.isInitialized;
	    }
	  }, {
	    key: "toLong",
	    value: function toLong(date) {
	      if (!this.isInit()) {
	        throw new Error("DateFormatter has not been initialized.");
	      }

	      return this.format(this.formatLong, date);
	    }
	  }, {
	    key: "toShort",
	    value: function toShort(date) {
	      if (!this.isInit()) {
	        throw new Error("DateFormatter has not been initialized.");
	      }

	      return this.format(this.formatShort, date);
	    }
	  }, {
	    key: "format",
	    value: function format(_format, date) {
	      if (!main_core.Type.isDate(date)) {
	        date = new Date(date);
	      }

	      if (isNaN(date)) {
	        throw new Error("DateFormatter: Invalid date.");
	      }

	      return BX.date.format(_format, date);
	    }
	  }, {
	    key: "toString",
	    value: function toString(date) {
	      if (!main_core.Type.isDate(date)) {
	        date = new Date(date);
	      }

	      if (isNaN(date)) {
	        throw new Error("DateFormatter: Invalid date.");
	      }

	      var addZero = function addZero(num) {
	        return num >= 0 && num <= 9 ? '0' + num : num;
	      };

	      var year = date.getFullYear();
	      var month = addZero(date.getMonth() + 1);
	      var day = addZero(date.getDate());
	      return year + '-' + month + '-' + day;
	    }
	  }]);
	  return DateFormatter;
	}();

	var dateFormatter = new DateFormatter();

	exports.DateFormatter = dateFormatter;

}((this.BX.Timeman = this.BX.Timeman || {}),BX));
//# sourceMappingURL=dateformatter.bundle.js.map
