this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_date) {
	'use strict';

	/**
	 * @memberOf BX.Crm.DateTime.Dictionary
	 */

	const TimezoneOffset = {
	  SERVER_TO_UTC: main_core.Text.toInteger(main_core.Loc.getMessage('SERVER_TZ_OFFSET')),
	  USER_TO_SERVER: main_core.Text.toInteger(main_core.Loc.getMessage('USER_TZ_OFFSET')),
	  // Date returns timezone offset in minutes by default, change it to seconds
	  // Also offset is negative in UTC+ timezones and positive in UTC- timezones.
	  // By convention Bitrix uses the opposite approach, so change offset sign.
	  BROWSER_TO_UTC: -main_core.Text.toInteger(new Date().getTimezoneOffset() * 60)
	};
	Object.freeze(TimezoneOffset);

	/**
	 * @memberOf BX.Crm.DateTime
	 */

	var _browserToUtc = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("browserToUtc");

	var _normalizeTimestampFromArgs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("normalizeTimestampFromArgs");

	class TimestampConverter {
	  static serverToUser(serverTimestamp) {
	    serverTimestamp = babelHelpers.classPrivateFieldLooseBase(this, _normalizeTimestampFromArgs)[_normalizeTimestampFromArgs](serverTimestamp);
	    return serverTimestamp + TimezoneOffset.USER_TO_SERVER;
	  }

	  static userToServer(userTimestamp) {
	    userTimestamp = babelHelpers.classPrivateFieldLooseBase(this, _normalizeTimestampFromArgs)[_normalizeTimestampFromArgs](userTimestamp);
	    return userTimestamp - TimezoneOffset.USER_TO_SERVER;
	  }

	  static browserToUser(browserTimestamp) {
	    browserTimestamp = babelHelpers.classPrivateFieldLooseBase(this, _normalizeTimestampFromArgs)[_normalizeTimestampFromArgs](browserTimestamp);
	    return browserTimestamp + TimezoneOffset.USER_TO_SERVER;
	  }

	  static browserToServer(browserTimestamp) {
	    browserTimestamp = babelHelpers.classPrivateFieldLooseBase(this, _normalizeTimestampFromArgs)[_normalizeTimestampFromArgs](browserTimestamp);
	    return babelHelpers.classPrivateFieldLooseBase(this, _browserToUtc)[_browserToUtc](browserTimestamp) + TimezoneOffset.SERVER_TO_UTC;
	  }

	  static userToBrowser(userTimestamp) {
	    userTimestamp = babelHelpers.classPrivateFieldLooseBase(this, _normalizeTimestampFromArgs)[_normalizeTimestampFromArgs](userTimestamp);
	    return userTimestamp + TimezoneOffset.BROWSER_TO_UTC - TimezoneOffset.SERVER_TO_UTC - TimezoneOffset.USER_TO_SERVER;
	  }

	  static serverToBrowser(serverTimestamp) {
	    serverTimestamp = babelHelpers.classPrivateFieldLooseBase(this, _normalizeTimestampFromArgs)[_normalizeTimestampFromArgs](serverTimestamp);
	    return serverTimestamp + TimezoneOffset.BROWSER_TO_UTC - TimezoneOffset.SERVER_TO_UTC;
	  }

	}

	function _browserToUtc2(browserTimestamp) {
	  browserTimestamp = babelHelpers.classPrivateFieldLooseBase(this, _normalizeTimestampFromArgs)[_normalizeTimestampFromArgs](browserTimestamp);
	  return browserTimestamp - TimezoneOffset.BROWSER_TO_UTC;
	}

	function _normalizeTimestampFromArgs2(timestamp) {
	  const normalized = main_core.Text.toInteger(timestamp);

	  if (normalized < 0) {
	    throw new Error('BX.Crm.DateTime.TimestampConverter: input timestamp could not be negative');
	  }

	  return normalized;
	}

	Object.defineProperty(TimestampConverter, _normalizeTimestampFromArgs, {
	  value: _normalizeTimestampFromArgs2
	});
	Object.defineProperty(TimestampConverter, _browserToUtc, {
	  value: _browserToUtc2
	});

	/**
	 * @memberOf BX.Crm.DateTime
	 */

	var _getBrowserNowTimestamp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBrowserNowTimestamp");

	class Factory {
	  /**
	   * Returns Date object with current time in user timezone.
	   *
	   * WARNING! In Bitrix user timezone !== browser timezone. Users can change their timezone from profile settings and
	   * will be different from browser timezone.
	   *
	   * If you need to get 'now' in a user's perspective, use this method instead of 'new Date()'
	   *
	   * Note that 'getTimezoneOffset' will not return correct user timezone, its always returns browser offset
	   *
	   * @returns {Date}
	   */
	  static getUserNow() {
	    const userTimestamp = TimestampConverter.browserToUser(babelHelpers.classPrivateFieldLooseBase(this, _getBrowserNowTimestamp)[_getBrowserNowTimestamp]());
	    return new Date(userTimestamp * 1000);
	  }
	  /**
	   * Returns Date object with current time in server timezone
	   * Note that 'getTimezoneOffset' will not return correct server timezone, its always returns browser offset
	   *
	   * @returns {Date}
	   */


	  static getServerNow() {
	    const serverTimestamp = TimestampConverter.browserToServer(babelHelpers.classPrivateFieldLooseBase(this, _getBrowserNowTimestamp)[_getBrowserNowTimestamp]());
	    return new Date(serverTimestamp * 1000);
	  }

	  static createFromTimestampInUserTimezone(timestamp) {
	    const browserTimestamp = TimestampConverter.browserToUser(timestamp);
	    return new Date(browserTimestamp * 1000);
	  }

	  static createFromTimestampInServerTimezone(timestamp) {
	    const browserTimestamp = TimestampConverter.browserToServer(timestamp);
	    return new Date(browserTimestamp * 1000);
	  }

	}

	function _getBrowserNowTimestamp2() {
	  return Math.floor(Date.now() / 1000);
	}

	Object.defineProperty(Factory, _getBrowserNowTimestamp, {
	  value: _getBrowserNowTimestamp2
	});

	/**
	 * Contains datetime formats for current culture.
	 * See config.php of this extension for specific format details.
	 * All formats are in BX.Main.DateTimeFormat format (de-facto - php format), even FORMAT_DATE and FORMAT_DATETIME
	 *
	 * @memberOf BX.Crm.DateTime.Dictionary
	 */

	const Format = {};
	const formatsRaw = main_core.Extension.getSettings('crm.datetime').get('formats', {});

	for (const name in formatsRaw) {
	  if (formatsRaw.hasOwnProperty(name) && main_core.Type.isStringFilled(formatsRaw[name])) {
	    let value = formatsRaw[name];

	    if (name === 'FORMAT_DATE' || name === 'FORMAT_DATETIME') {
	      value = main_date.DateTimeFormat.convertBitrixFormat(value);
	    }

	    Format[name] = value;
	  }
	}

	Object.freeze(Format);

	const namespace = main_core.Reflection.namespace('BX.Crm.DateTime');
	namespace.Factory = Factory;
	namespace.TimestampConverter = TimestampConverter;
	namespace.Dictionary = {
	  TimezoneOffset,
	  Format
	};

	exports.Factory = Factory;
	exports.TimestampConverter = TimestampConverter;
	exports.TimezoneOffset = TimezoneOffset;
	exports.Format = Format;

}((this.BX.Crm.DateTime = this.BX.Crm.DateTime || {}),BX,BX.Main));
//# sourceMappingURL=datetime.bundle.js.map
