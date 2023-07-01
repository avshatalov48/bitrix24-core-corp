this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_date,crm_datetime) {
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
	    /**
	     * @param timestamp Timestamp in server timezone
	     */
	    value: function createFromServerTimestamp(timestamp) {
	      const date = crm_datetime.Factory.createFromTimestampInServerTimezone(timestamp);
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
	    babelHelpers.classPrivateFieldSet(this, _timeFormat, main_core.Loc.getMessage('CRM_TIMELINE_TIME_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _shortDateFormat, main_core.Loc.getMessage('CRM_TIMELINE_SHORT_DATE_FORMAT'));
	    babelHelpers.classPrivateFieldSet(this, _fullDateFormat, main_core.Loc.getMessage('CRM_TIMELINE_FULL_DATE_FORMAT'));
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
	      const serverTimestamp = Math.floor(babelHelpers.classPrivateFieldGet(this, _datetime).getTime() / 1000);
	      babelHelpers.classPrivateFieldSet(this, _datetime, new Date(crm_datetime.TimestampConverter.serverToUser(serverTimestamp) * 1000));
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
	      return main_date.DateTimeFormat.format([['today', 'today'], ['tommorow', 'tommorow'], ['yesterday', 'yesterday'], ['', babelHelpers.classPrivateFieldGet(this, _datetime).getFullYear() === crm_datetime.Factory.getUserNow().getFullYear() ? babelHelpers.classPrivateFieldGet(this, _shortDateFormat) : babelHelpers.classPrivateFieldGet(this, _fullDateFormat)]], babelHelpers.classPrivateFieldGet(this, _datetime)).replaceAll('\\', '');
	    }
	  }, {
	    key: "toFormatString",
	    value: function toFormatString(format, now, utc) {
	      return main_date.DateTimeFormat.format(format, babelHelpers.classPrivateFieldGet(this, _datetime), now, utc).replaceAll('\\', '');
	    }
	  }], [{
	    key: "getSiteDateFormat",
	    value: function getSiteDateFormat() {
	      return main_date.DateTimeFormat.convertBitrixFormat(main_core.Loc.getMessage('FORMAT_DATE'));
	    }
	  }, {
	    key: "getSiteDateTimeFormat",
	    value: function getSiteDateTimeFormat() {
	      return main_date.DateTimeFormat.convertBitrixFormat(main_core.Loc.getMessage('FORMAT_DATETIME'));
	    }
	  }]);
	  return DatetimeConverter;
	}();

	exports.DatetimeConverter = DatetimeConverter;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX,BX.Main,BX.Crm.DateTime));
//# sourceMappingURL=tools.bundle.js.map
