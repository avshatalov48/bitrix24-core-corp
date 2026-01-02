import type { AnalyticsOptions } from 'ui.analytics';
import {
	AnalyticsTool,
	AnalyticsCategory,
	AnalyticsEvent,
	AnalyticsCSection,
	AnalyticsType,
	AnalyticsElement,
	AnalyticsCSubSection,
} from 'booking.const';

type RcwAnalyticsOptions = AnalyticsOptions & {
	tool: $Values<typeof AnalyticsTool>,
	category: $Values<typeof AnalyticsCategory>,
	event: $Values<typeof AnalyticsEvent>,
	c_section: $Values<typeof AnalyticsCSection>,
	c_sub_section?: $Values<typeof AnalyticsCSubSection>,
	c_element?: $Values<typeof AnalyticsElement>,
	type?: $Values<typeof AnalyticsType>,
}

export type ClickAddResourceOptions = Omit<RcwAnalyticsOptions, 'c_sub_section' | 'type' | 'status' | 'p1' | 'p2' | 'p3' | 'p4' | 'p5'>
export type AddResourceStep1Options = Omit<RcwAnalyticsOptions, 'c_sub_section' | 'c_element' | 'status' | 'p1' | 'p2' | 'p3' | 'p4' | 'p5'>
export type AddResourceStep2Options =
	& Omit<RcwAnalyticsOptions, 'c_sub_section' | 'c_element' | 'type' | 'status' | 'p4' | 'p5'>
	& {
	p1: P1RenderType,
	p2: P2SetSchedule,
	p3: P3SlotLength
}
export type P1RenderType = 'renderType_main' | 'renderType_additional';
export type P2SetSchedule = 'setSchedule_Y' | 'setSchedule_N';
export type P3SlotLength =
	| 'slotLength_1h'
	| 'slotLength_2h'
	| 'slotLength_24h'
	| 'slotLength_7d'
	| 'slotLength_custom';

export type AddResourceFinishOptions = Omit<RcwAnalyticsOptions, 'type' | 'c_sub_section' | 'c_element' | 'status'>
	& {
	p1: P1InfoNotification,
	p2: P2ConfirmationNotification,
	p3: P3ReminderNotification,
	p4: P4DelayedNotification,
	p5: P5FeedbackNotification,
}
export type P1InfoNotification = 'infoNotification_Y' | 'infoNotification_N';
export type P2ConfirmationNotification = 'confirmationNotification_Y' | 'confirmationNotification_N';
export type P3ReminderNotification = 'reminderNotification_Y' | 'reminderNotification_N';
export type P4DelayedNotification = 'delayedNotification_Y' | 'delayedNotification_N';
export type P5FeedbackNotification = 'feedbackNotification_Y' | 'feedbackNotification_N';

export type AcceptAgreementOptions =
	& Omit<RcwAnalyticsOptions, 'type' | 'c_element' | 'status' | 'p1' | 'p2' | 'p3' | 'p4' | 'p5'>
	& {
	event: 'accept_agreement',
	c_sub_section: AcceptAgreementCSubSection,
};
export type AcceptAgreementCSubSection = 'accept' | 'deny';
