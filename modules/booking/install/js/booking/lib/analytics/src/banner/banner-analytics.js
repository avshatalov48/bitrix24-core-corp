import { sendData } from 'ui.analytics';
import { AnalyticsTool, AnalyticsCategory } from 'booking.const';
import type { ShowPopupBannerAnalyticsOptions, ClickEnableBannerAnalyticsOptions } from './types';

export class BannerAnalytics
{
	static sendShowPopup(): void
	{
		const options: ShowPopupBannerAnalyticsOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: 'show_popup',
			c_section: 'booking',
		};
		sendData(options);
	}

	static sendClickEnable(): void
	{
		const options: ClickEnableBannerAnalyticsOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: 'click_enable',
			c_section: 'booking',
		};
		sendData(options);
	}
}
