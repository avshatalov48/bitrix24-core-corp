import type { AnalyticsOptions } from 'ui.analytics';

type WaitListAnalyticsOptions =
	& AnalyticsOptions
	& {
	tool: 'booking',
	category: 'booking',
}

export type AddBookingWaitListAnalyticsOptions =
	& Omit<WaitListAnalyticsOptions, 'type' | 's_sub_section' | 'status' | 'p1' | 'p2' | 'p3' | 'p4' | 'p5'>
	& {
		event: 'add_booking',
		'c_element': 'add_button'
	}
