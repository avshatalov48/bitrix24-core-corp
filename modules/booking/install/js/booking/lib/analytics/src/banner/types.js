import type { AnalyticsOptions } from 'ui.analytics';

import { AnalyticsTool, AnalyticsCategory } from 'booking.const';

type BannerAnalyticsOptions = AnalyticsOptions & {
	tool: $Values<typeof AnalyticsTool>,
	category: $Values<typeof AnalyticsCategory>,
}

export type ShowPopupBannerAnalyticsOptions =
	& Omit<BannerAnalyticsOptions, 'type' | 'c_sub_section' | 'c_element' | 'status' | 'p1' | 'p2' | 'p3' | 'p4' | 'p5'>
	& {
	event: 'show_popup',
	c_section: 'booking',
};

export type ClickEnableBannerAnalyticsOptions =
	& Omit<BannerAnalyticsOptions, 'type' | 'c_sub_section' | 'c_element' | 'status' | 'p1' | 'p2' | 'p3' | 'p4' | 'p5'>
	& {
	event: 'click_enable',
	c_section: 'booking',
};
