/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_date) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _timeFormat = /*#__PURE__*/new WeakMap();
	var _dateFormat = /*#__PURE__*/new WeakMap();
	var _shortDateFormat = /*#__PURE__*/new WeakMap();
	var _longDateFormat = /*#__PURE__*/new WeakMap();
	var _mediumDateFormat = /*#__PURE__*/new WeakMap();
	var _datetime = /*#__PURE__*/new WeakMap();
	var _getDateFormat = /*#__PURE__*/new WeakSet();
	var _isShowYear = /*#__PURE__*/new WeakSet();
	let DatetimeConverter = /*#__PURE__*/function () {
	  babelHelpers.createClass(DatetimeConverter, null, [{
	    key: "createFromServerTimestamp",
	    // date object which absolute time will be the same as if it was in server timezone
	    /**
	     * @param timestamp Normal UTC timestamp, as it should be
	     */
	    value: function createFromServerTimestamp(timestamp) {
	      return new DatetimeConverter(main_date.Timezone.ServerTime.getDate(timestamp));
	    }
	  }]);
	  function DatetimeConverter(datetime) {
	    babelHelpers.classCallCheck(this, DatetimeConverter);
	    _classPrivateMethodInitSpec(this, _isShowYear);
	    _classPrivateMethodInitSpec(this, _getDateFormat);
	    _classPrivateFieldInitSpec(this, _timeFormat, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _dateFormat, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _shortDateFormat, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _longDateFormat, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _mediumDateFormat, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _datetime, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _timeFormat, main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _dateFormat, main_date.DateTimeFormat.getFormat('DAY_MONTH_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _shortDateFormat, main_date.DateTimeFormat.getFormat('DAY_SHORT_MONTH_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _longDateFormat, main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _mediumDateFormat, main_date.DateTimeFormat.getFormat('MEDIUM_DATE_FORMAT'));
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
	      babelHelpers.classPrivateFieldSet(this, _datetime, main_date.Timezone.ServerTime.toUserDate(babelHelpers.classPrivateFieldGet(this, _datetime)));
	      return this;
	    }
	  }, {
	    key: "toDatetimeString",
	    value: function toDatetimeString(options = {}) {
	      var _options$withFullMont;
	      // eslint-disable-next-line no-param-reassign
	      options = options || {};
	      const now = new Date();
	      const withDayOfWeek = Boolean(options.withDayOfWeek);
	      const withFullMonth = Boolean((_options$withFullMont = options.withFullMonth) !== null && _options$withFullMont !== void 0 ? _options$withFullMont : true);
	      const delimiter = options.delimiter || ' ';
	      const showYear = _classPrivateMethodGet(this, _isShowYear, _isShowYear2).call(this);
	      return main_date.DateTimeFormat.format([['today', `today${delimiter}${babelHelpers.classPrivateFieldGet(this, _timeFormat)}`], ['tommorow', `tommorow${delimiter}${babelHelpers.classPrivateFieldGet(this, _timeFormat)}`], ['yesterday', `yesterday${delimiter}${babelHelpers.classPrivateFieldGet(this, _timeFormat)}`], ['', (withDayOfWeek ? `D${delimiter}` : '') + _classPrivateMethodGet(this, _getDateFormat, _getDateFormat2).call(this, withFullMonth, showYear) + delimiter + babelHelpers.classPrivateFieldGet(this, _timeFormat)]], babelHelpers.classPrivateFieldGet(this, _datetime), now).replaceAll('\\', '');
	    }
	  }, {
	    key: "toTimeString",
	    value: function toTimeString(now, utc) {
	      return main_date.DateTimeFormat.format(babelHelpers.classPrivateFieldGet(this, _timeFormat), babelHelpers.classPrivateFieldGet(this, _datetime), now, utc).replaceAll('\\', '');
	    }
	  }, {
	    key: "toDateString",
	    value: function toDateString(options = {}) {
	      var _options$withFullMont2;
	      const withFullMonth = Boolean((_options$withFullMont2 = options.withFullMonth) !== null && _options$withFullMont2 !== void 0 ? _options$withFullMont2 : true);
	      const showYear = _classPrivateMethodGet(this, _isShowYear, _isShowYear2).call(this);
	      return main_date.DateTimeFormat.format([['today', 'today'], ['tommorow', 'tommorow'], ['yesterday', 'yesterday'], ['', _classPrivateMethodGet(this, _getDateFormat, _getDateFormat2).call(this, withFullMonth, showYear)]], babelHelpers.classPrivateFieldGet(this, _datetime)).replaceAll('\\', '');
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
	function _getDateFormat2(withFullMonth = false, withYear = false) {
	  if (withYear) {
	    return withFullMonth ? babelHelpers.classPrivateFieldGet(this, _longDateFormat) : babelHelpers.classPrivateFieldGet(this, _mediumDateFormat);
	  }
	  return withFullMonth ? babelHelpers.classPrivateFieldGet(this, _dateFormat) : babelHelpers.classPrivateFieldGet(this, _shortDateFormat);
	}
	function _isShowYear2() {
	  return babelHelpers.classPrivateFieldGet(this, _datetime).getFullYear() !== main_date.Timezone.UserTime.getDate().getFullYear();
	}

	exports.DatetimeConverter = DatetimeConverter;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX.Main));
//# sourceMappingURL=tools.bundle.js.map
