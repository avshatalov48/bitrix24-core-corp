import { Loc } from 'main.core';

export const EmptyScriptListBlock = {

	computed: {
		title(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_TITLE');
		},
		text(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_TEXT');
		},
	},

	template: `
		<div class="call-quality__explanation">
			<div class="call-quality__explanation__container">
				<div class="call-quality__explanation-title">
					{{ title }}
				</div>
				<div class="call-quality__explanation-text" v-html="text">
				</div>
			</div>
		</div>
	`,
};
