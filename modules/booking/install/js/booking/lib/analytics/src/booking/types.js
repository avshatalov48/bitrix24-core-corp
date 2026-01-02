import type { AnalyticsOptions } from 'ui.analytics';
import {
	AnalyticsTool,
	AnalyticsCategory,
} from 'booking.const';

type BookingAnalyticsOptions = AnalyticsOptions & {
	tool: $Values<typeof AnalyticsTool>,
	category: $Values<typeof AnalyticsCategory>,
}

export type AddBookingOptions =
	& Omit<BookingAnalyticsOptions, 'type' | 'c_sub_section' | 'status' | 'p4' | 'p5'>
	& {
	event: 'add_booking',
	c_element: AddBookingCElement,
	p1: AddBookingP1,
	p2: AddBookingP2,
	p3: AddBookingP3,
}
export type AddBookingCElement = 'solo_button' | 'multi_button';
export type AddBookingP1 = 'isMultiResource_Y' | 'isMultiResource_N';
export type AddBookingP2 = 'isOverbooking_Y' | 'isOverbooking_N';
export type AddBookingP3 = 'isWaitlist_Y' | 'isWaitlist_N';
