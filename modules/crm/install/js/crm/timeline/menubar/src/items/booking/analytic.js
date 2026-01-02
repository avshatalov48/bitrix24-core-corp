import { sendData } from 'ui.analytics';

export default class BannerAnalytics
{
	static ANALYTICS_TOOL_BOOKING = 'booking';
	static ANALYTICS_CATEGORY_BOOKING = 'booking';
	static ANALYTICS_SECTION_CRM = 'crm';

	static sendShowPopup(): void
	{
		const options = {
			tool: BannerAnalytics.ANALYTICS_TOOL_BOOKING,
			category: BannerAnalytics.ANALYTICS_CATEGORY_BOOKING,
			event: 'show_popup',
			c_section: BannerAnalytics.ANALYTICS_SECTION_CRM,
		};
		sendData(options);
	}

	static sendClickEnable(): void
	{
		const options = {
			tool: BannerAnalytics.ANALYTICS_TOOL_BOOKING,
			category: BannerAnalytics.ANALYTICS_CATEGORY_BOOKING,
			event: 'click_enable',
			c_section: BannerAnalytics.ANALYTICS_SECTION_CRM,
		};
		sendData(options);
	}
}
