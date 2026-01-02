/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core_events) {
	'use strict';

	class Clock {
	  init() {
	    main_core_events.EventEmitter.subscribe('onJCClockInit', config => {
	      window.JCClock.setOptions({
	        centerXInline: 83,
	        centerX: 83,
	        centerYInline: 67,
	        centerY: 79,
	        minuteLength: 31,
	        hourLength: 26,
	        popupHeight: 229,
	        inaccuracy: 15,
	        cancelCheckClick: true
	      });
	    });
	  }
	}

	exports.Clock = Clock;

}((this.BX.Intranet.Bitrix24 = this.BX.Intranet.Bitrix24 || {}),BX.Event));
//# sourceMappingURL=clock.bundle.js.map
