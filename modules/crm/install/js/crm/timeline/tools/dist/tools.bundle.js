/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_date) {
	'use strict';

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _timeFormat = /*#__PURE__*/new WeakMap();
	var _shortDateFormat = /*#__PURE__*/new WeakMap();
	var _fullDateFormat = /*#__PURE__*/new WeakMap();
	var _datetime = /*#__PURE__*/new WeakMap();
	let DatetimeConverter = /*#__PURE__*/function () {
	  babelHelpers.createClass(DatetimeConverter, null, [{
	    key: "createFromServerTimestamp",
	    // date object which absolute time will be the same as if it was in server timezone
	    /**
	     * @param timestamp Normal UTC timestamp, as it should be
	     */
	    value: function createFromServerTimestamp(timestamp) {
	      const offset = BX.Main.Timezone.Offset.SERVER_TO_UTC + BX.Main.Timezone.Offset.BROWSER_TO_UTC;

	      // make a date object which absolute time will match time of server (even though it has different timezone)
	      const date = new Date((timestamp + offset) * 1000);
	      return new DatetimeConverter(date);
	    }
	  }]);
	  function DatetimeConverter(datetime) {
	    babelHelpers.classCallCheck(this, DatetimeConverter);
	    _classPrivateFieldInitSpec(this, _timeFormat, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _shortDateFormat, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _fullDateFormat, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _datetime, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _timeFormat, main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _shortDateFormat, main_date.DateTimeFormat.getFormat('DAY_SHORT_MONTH_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _fullDateFormat, main_date.DateTimeFormat.getFormat('MEDIUM_DATE_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _datetime, datetime);
	  }
	  babelHelpers.createClass(DatetimeConverter, [{
	    key: "getValue",
	    value: function getValue() {
	      return babelHelpers.classPrivateFieldGet(this, _datetime);
	    }
	  }, {
	    key: "toUserTime",
	    value: function toUserTime() {
	      const timestampServer = Math.floor(babelHelpers.classPrivateFieldGet(this, _datetime).getTime() / 1000);

	      // make a date object which absolute time will match time of user (even though it has different timezone)
	      babelHelpers.classPrivateFieldSet(this, _datetime, new Date((timestampServer + main_date.Timezone.Offset.USER_TO_SERVER) * 1000));
	      return this;
	    }
	  }, {
	    key: "toDatetimeString",
	    value: function toDatetimeString(options) {
	      options = options || {};
	      const now = new Date();
	      const withDayOfWeek = !!options.withDayOfWeek;
	      const delimiter = options.delimiter || ' ';
	      return main_date.DateTimeFormat.format([['today', 'today' + delimiter + babelHelpers.classPrivateFieldGet(this, _timeFormat)], ['tommorow', 'tommorow' + delimiter + babelHelpers.classPrivateFieldGet(this, _timeFormat)], ['yesterday', 'yesterday' + delimiter + babelHelpers.classPrivateFieldGet(this, _timeFormat)], ['', (withDayOfWeek ? 'D' + delimiter : '') + (babelHelpers.classPrivateFieldGet(this, _datetime).getFullYear() === now.getFullYear() ? babelHelpers.classPrivateFieldGet(this, _shortDateFormat) : babelHelpers.classPrivateFieldGet(this, _fullDateFormat)) + delimiter + babelHelpers.classPrivateFieldGet(this, _timeFormat)]], babelHelpers.classPrivateFieldGet(this, _datetime), now).replaceAll('\\', '');
	    }
	  }, {
	    key: "toTimeString",
	    value: function toTimeString(now, utc) {
	      return main_date.DateTimeFormat.format(babelHelpers.classPrivateFieldGet(this, _timeFormat), babelHelpers.classPrivateFieldGet(this, _datetime), now, utc).replaceAll('\\', '');
	    }
	  }, {
	    key: "toDateString",
	    value: function toDateString() {
	      return main_date.DateTimeFormat.format([['today', 'today'], ['tommorow', 'tommorow'], ['yesterday', 'yesterday'], ['', babelHelpers.classPrivateFieldGet(this, _datetime).getFullYear() === main_date.Timezone.UserTime.getDate().getFullYear() ? babelHelpers.classPrivateFieldGet(this, _shortDateFormat) : babelHelpers.classPrivateFieldGet(this, _fullDateFormat)]], babelHelpers.classPrivateFieldGet(this, _datetime)).replaceAll('\\', '');
	    }
	  }, {
	    key: "toFormatString",
	    value: function toFormatString(format, now, utc) {
	      return main_date.DateTimeFormat.format(format, babelHelpers.classPrivateFieldGet(this, _datetime), now, utc).replaceAll('\\', '');
	    }
	  }], [{
	    key: "getSiteDateFormat",
	    value: function getSiteDateFormat() {
	      return main_date.DateTimeFormat.getFormat('FORMAT_DATE');
	    }
	  }, {
	    key: "getSiteDateTimeFormat",
	    value: function getSiteDateTimeFormat() {
	      return main_date.DateTimeFormat.getFormat('FORMAT_DATETIME');
	    }
	  }]);
	  return DatetimeConverter;
	}();

	exports.DatetimeConverter = DatetimeConverter;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX.Main));
//# sourceMappingURL=tools.bundle.js.map
