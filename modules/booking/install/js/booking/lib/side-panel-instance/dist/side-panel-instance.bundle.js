/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_sidepanel) {
	'use strict';

	const SidePanelInstance = window === top ? main_sidepanel.SidePanel.Instance : new main_sidepanel.SidePanel.Manager({});

	exports.SidePanelInstance = SidePanelInstance;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX));
//# sourceMappingURL=side-panel-instance.bundle.js.map
