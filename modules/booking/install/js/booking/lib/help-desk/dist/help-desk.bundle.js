/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports) {
	'use strict';

	class HelpDesk {
	  show(code, anchor = null) {
	    if (top.BX.Helper) {
	      const params = {
	        redirect: 'detail',
	        code,
	        ...(anchor !== null && {
	          anchor
	        })
	      };
	      const queryString = Object.entries(params).map(([key, value]) => `${key}=${value}`).join('&');
	      top.BX.Helper.show(queryString);
	    }
	  }
	}
	const helpDesk = new HelpDesk();

	exports.helpDesk = helpDesk;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {})));
//# sourceMappingURL=help-desk.bundle.js.map
