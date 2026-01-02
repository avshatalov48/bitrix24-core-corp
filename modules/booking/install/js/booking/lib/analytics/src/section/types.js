import type { AnalyticsOptions } from 'ui.analytics';

type SectionAnalyticsOptions =
	& AnalyticsOptions
	& {
	tool: 'booking',
	category: 'booking',
}

export type OpenSectionAnalyticsOptions =
	& Omit<SectionAnalyticsOptions, 'type' | 'c_element' | 'status' | 'p1' | 'p2' | 'p3' | 'p4' | 'p5'>
	& {
	event: 'open_section',
	s_sub_section: OpenSectionCSubSection,
}
export type OpenSectionCSection = 'main_menu' | 'crm';
export type OpenSectionCSubSection =
	| 'deal'
	| 'lead'
	| 'smart'
	| 'contact'
	| 'company'
	| 'crm_business';
