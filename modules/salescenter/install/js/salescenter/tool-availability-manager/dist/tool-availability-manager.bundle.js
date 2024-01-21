/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	class ToolAvailabilityManager {
	  static openSalescenterToolDisabledSlider() {
	    ToolAvailabilityManager.openSliderByCode('limit_crm_sales_center_off');
	  }
	  static openSliderByCode(sliderCode) {
	    main_core.Runtime.loadExtension('ui.info-helper').then(() => {
	      top.BX.UI.InfoHelper.show(sliderCode);
	    });
	  }
	}

	exports.ToolAvailabilityManager = ToolAvailabilityManager;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX));
//# sourceMappingURL=tool-availability-manager.bundle.js.map
