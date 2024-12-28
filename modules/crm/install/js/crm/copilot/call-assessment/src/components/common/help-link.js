import { Loc } from 'main.core';

export const HelpLink = {
	props: {
		articleCode: {
			type: String,
			required: true,
		},
	},

	methods: {
		onClick(): void
		{
			window.top.BX?.Helper?.show(`redirect=detail&code=${this.articleCode}`);
		},
	},

	template: `
		<span class="crm-copilot__call-assessment-help-link" ref="container" @click="onClick">
			${Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_HELP')}
		</span>
	`,
};
