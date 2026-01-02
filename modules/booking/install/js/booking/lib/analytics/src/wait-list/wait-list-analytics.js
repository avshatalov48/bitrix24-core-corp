import { sendData } from 'ui.analytics';

import { AnalyticsTool, AnalyticsCategory } from 'booking.const';

import { getCSection } from '../lib';
import { AddBookingWaitListAnalyticsOptions } from './types';

export class WaitListAnalytics
{
	static sendAddBooking(): void
	{
		const options: AddBookingWaitListAnalyticsOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.waitlist,
			c_section: getCSection(),
			event: 'add_booking',
			c_element: 'add_button',
		};
		sendData(options);
	}
}
