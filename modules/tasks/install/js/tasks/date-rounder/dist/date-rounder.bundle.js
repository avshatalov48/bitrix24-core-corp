/* eslint-disable */
this.BX = this.BX || {};
(function (exports) {
	'use strict';

	const SECONDS_IN_DAY = 86400;
	class DateRounder {
	  static roundToDays(offsetInSeconds) {
	    const offsetInDays = Math.max(0, offsetInSeconds / SECONDS_IN_DAY);
	    return Math.ceil(offsetInDays);
	  }
	}

	exports.DateRounder = DateRounder;

}((this.BX.Tasks = this.BX.Tasks || {})));
