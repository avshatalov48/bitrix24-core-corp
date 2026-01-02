import { HelpDeskRule } from './helpdesk-rule';
import { NoWrapRule } from './nowrap-rule';

export const ruleRegistry = {
	helpdesk: {
		name: 'helpdesk',
		component: HelpDeskRule,
	},
	nowrap: {
		name: 'nowrap',
		component: NoWrapRule,
	},
};
