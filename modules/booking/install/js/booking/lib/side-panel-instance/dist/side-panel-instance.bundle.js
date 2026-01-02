/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_sidepanel) {
	'use strict';

	const sp = main_sidepanel.SidePanel != null ? main_sidepanel.SidePanel : BX.SidePanel;
	const ManagerInst = main_sidepanel.Manager != null ? main_sidepanel.Manager : BX.SidePanel.Manager;
	const SidePanelInstance = window === top ? sp.Instance : new ManagerInst();

	exports.SidePanelInstance = SidePanelInstance;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX.SidePanel));
//# sourceMappingURL=side-panel-instance.bundle.js.map
