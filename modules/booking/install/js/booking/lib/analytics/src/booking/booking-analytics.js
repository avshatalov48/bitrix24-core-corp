import { sendData } from 'ui.analytics';

import { Core } from 'booking.core';
import { AnalyticsTool, AnalyticsCategory, Model } from 'booking.const';

import { getCSection } from '../lib';
import type { AddBookingOptions } from './types';

export class BookingAnalytics
{
	static sendAddMultiBookings(bookingIds: number[]): void
	{
		const $store = Core.getStore();
		const cSection = getCSection();
		const overbookingMap = $store.getters[`${Model.Bookings}/overbookingMap`];
		for (const bookingId of bookingIds)
		{
			const isOverbooking = overbookingMap.has(bookingId);
			const options: AddBookingOptions = {
				tool: AnalyticsTool.booking,
				category: AnalyticsCategory.booking,
				event: 'add_booking',
				c_section: cSection,
				c_element: 'multi_button',
				p1: 'isMultiResource_Y',
				p2: isOverbooking ? 'isOverbooking_Y' : 'isOverbooking_N',
				p3: 'isWaitlist_N',
			};
			sendData(options);
		}
	}

	static sendAddBooking({ isOverbooking }: { isOverbooking: ?boolean } = {}): void
	{
		const options: AddBookingOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: 'add_booking',
			c_section: getCSection(),
			c_element: 'solo_button',
			p1: 'isMultiResource_N',
			p2: isOverbooking ? 'isOverbooking_Y' : 'isOverbooking_N',
			p3: 'isWaitlist_N',
		};
		sendData(options);
	}

	static sendAddWaitListItem(): void
	{
		const options: AddBookingOptions = {
			tool: AnalyticsTool.booking,
			category: AnalyticsCategory.booking,
			event: 'add_booking',
			c_section: getCSection(),
			c_element: 'solo_button',
			p1: 'isMultiResource_N',
			p2: 'isOverbooking_N',
			p3: 'isWaitlist_Y',
		};
		sendData(options);
	}
}
