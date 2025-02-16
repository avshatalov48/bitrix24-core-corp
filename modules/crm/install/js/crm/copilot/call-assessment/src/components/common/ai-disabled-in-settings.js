import { Loc } from 'main.core';
import 'ui.icon-set.main';

export const AiDisabledInSettings = {
	methods: {
		showLimitCopilotOffSlider(): void
		{
			top.BX.UI?.InfoHelper?.show('limit_v2_crm_copilot_call_assessment_off');
		},
	},

	computed: {
		message(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AI_DISABLED');
		},
	},

	template: `
		<div class="crm-copilot__call-assessment-ai-disabled">
			<span v-html="message"></span>
			<span
				@click="showLimitCopilotOffSlider"
				class="ui-icon-set --help"
			></span>
		</div>
	`,
};
