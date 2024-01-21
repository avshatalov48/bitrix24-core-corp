import { Runtime } from 'main.core';

export class ToolAvailabilityManager
{
	static openSalescenterToolDisabledSlider()
	{
		ToolAvailabilityManager.openSliderByCode('limit_crm_sales_center_off');
	}

	static openSliderByCode(sliderCode)
	{
		Runtime.loadExtension('ui.info-helper').then(() => {
			top.BX.UI.InfoHelper.show(sliderCode);
		});
	}
}
