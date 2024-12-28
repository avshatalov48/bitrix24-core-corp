import { Loc } from 'main.core';

export const NotAssessmentScriptBlock = {
	methods: {
		doAssessment(): void
		{
			this.$emit('doAssessment');
		},
	},

	computed: {
		title(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_NO_EXPLANATION_TITLE');
		},
		text(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_NO_EXPLANATION_TEXT');
		},
		buttonText(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_NO_EXPLANATION_ASSESSMENT');
		},
	},

	template: `
		<div class="call-quality__explanation">
			<div class="call-quality__explanation__container ">
				<div class="call-quality__explanation-title">
					{{ title }}
				</div>
				<div class="call-quality__explanation-text" v-html="text">
				</div>
			</div>
			<div class="call-quality__explanation__buttons-container">
				<button
					class="ui-btn ui-btn-md ui-btn-no-caps ui-btn-color-ai ui-btn-round ui-btn-active"
					@click="doAssessment"
				>
					{{ buttonText }}
				</button>
			</div>
		</div>
	`,
};
