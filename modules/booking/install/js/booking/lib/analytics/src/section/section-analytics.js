import { sendData } from 'ui.analytics';

import { Core } from 'booking.core';
import {
	AnalyticsTool,
	AnalyticsCategory,
	Model,
} from 'booking.const';
import type { DealData } from 'booking.model.bookings';

import { getOpenSectionCSection, getOpenSectionCSubSection } from './lib';
import type { OpenSectionAnalyticsOptions } from './types';

export class SectionAnalytics
{
	static sendOpenSection(): void
	{
		const $store = Core.getStore();
		const embedItems: DealData[] = $store.getters[`${Model.Interface}/embedItems`];
		const editingBookingId: number = $store.getters[`${Model.Interface}/editingBookingId`] || 0;

		const options: OpenSectionAnalyticsOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: 'open_section',
			c_section: getOpenSectionCSection(editingBookingId, embedItems),
			c_sub_section: getOpenSectionCSubSection(editingBookingId, embedItems),
		};
		sendData(options);
	}
}
