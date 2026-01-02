/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core) {
	'use strict';

	const settings = main_core.Extension.getSettings('tasks.v2.lib.timezone');
	class Timezone {
	  getOffset(dateTs, timeZone = this.getTimezone()) {
	    return this.getTimezoneOffset(dateTs, timeZone) + new Date(dateTs).getTimezoneOffset() * 60 * 1000;
	  }
	  getTimezoneOffset(dateTs, timeZone = this.getTimezone()) {
	    const date = new Date(dateTs);
	    const dateInTimezone = new Date(date.toLocaleString('en-US', {
	      timeZone
	    }));
	    const dateInUTC = new Date(date.toLocaleString('en-US', {
	      timeZone: 'UTC'
	    }));
	    return dateInTimezone.getTime() - dateInUTC.getTime();
	  }
	  getTimezone() {
	    return settings.timeZone || Intl.DateTimeFormat().resolvedOptions().timeZone;
	  }
	}
	const timezone = new Timezone();

	exports.timezone = timezone;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX));
//# sourceMappingURL=timezone.bundle.js.map
