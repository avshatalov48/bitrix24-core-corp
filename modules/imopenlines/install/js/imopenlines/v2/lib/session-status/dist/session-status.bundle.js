/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
(function (exports,im_v2_application_core) {
	'use strict';

	const SessionManager = {
	  findGroupByStatus(sessionStatusName) {
	    const {
	      sessionStatusMap
	    } = im_v2_application_core.Core.getApplicationData();
	    const groupByStatusName = Object.entries(sessionStatusMap).find(([groupName, groupStatuses]) => {
	      return sessionStatusName in groupStatuses;
	    });
	    return groupByStatusName ? groupByStatusName[0] : null;
	  }
	};

	exports.SessionManager = SessionManager;

}((this.BX.OpenLines.v2.Lib = this.BX.OpenLines.v2.Lib || {}),BX.Messenger.v2.Application));
//# sourceMappingURL=session-status.bundle.js.map
