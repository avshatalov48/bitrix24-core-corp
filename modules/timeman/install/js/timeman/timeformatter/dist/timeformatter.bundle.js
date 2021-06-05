this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var TimeFormatter = /*#__PURE__*/function () {
	  function TimeFormatter() {
	    babelHelpers.classCallCheck(this, TimeFormatter);
	  }

	  babelHelpers.createClass(TimeFormatter, [{
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
	    value: function toLong(time) {
	      if (!this.isInit()) {
	        throw new Error("TimeFormatter has not been initialized.");
	      }

	      return this.format(this.formatLong, time);
	    }
	  }, {
	    key: "toShort",
	    value: function toShort(time) {
	      if (!this.isInit()) {
	        throw new Error("TimeFormatter has not been initialized.");
	      }

	      return this.format(this.formatShort, time);
	    }
	  }, {
	    key: "format",
	    value: function format(_format, time) {
	      if (!main_core.Type.isDate(time)) {
	        time = new Date(time);
	      }

	      if (isNaN(time)) {
	        throw new Error("TimeFormatter: Invalid time. An object of type date was expected.");
	      }

	      return BX.date.format(_format, time);
	    }
	  }]);
	  return TimeFormatter;
	}();

	var timeFormatter = new TimeFormatter();

	exports.TimeFormatter = timeFormatter;

}((this.BX.Timeman = this.BX.Timeman || {}),BX));
//# sourceMappingURL=timeformatter.bundle.js.map
