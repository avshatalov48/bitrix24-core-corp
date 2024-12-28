import { Loc } from 'main.core';

export const OtherScriptBlock = {
	methods: {
		showAssessment(): void
		{
			this.$emit('showAssessment');
		},
		doAssessment(): void
		{
			this.$emit('doAssessment');
		},
	},

	computed: {
		title(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_OLD_EXPLANATION_TITLE');
		},
		text(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_OLD_EXPLANATION_TEXT');
		},
		buttonShowText(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_OLD_EXPLANATION_SHOW_ASSESSMENT');
		},
		buttonDoText(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_OLD_EXPLANATION_ASSESSMENT');
		},
	},

	template: `
		<div class="call-quality__explanation">
			<div class="call-quality__explanation__container ">
				<div class="call-quality__explanation-title" v-html="title">
				</div>
				<div class="call-quality__explanation-text" v-html="text">
				</div>
			</div>
			<div class="call-quality__explanation__buttons-container">
				<button
					class="ui-btn ui-btn-md ui-btn-no-caps ui-btn-color-ai ui-btn-round ui-btn-active"
					@click="doAssessment"
				>
					{{ buttonDoText }}
				</button>
				<button
					class="ui-btn ui-btn-md ui-btn-no-caps ui-btn-light-border ui-btn-round"
					@click="showAssessment"
				>
					{{ buttonShowText }}
				</button>
			</div>
		</div>
	`,
};
