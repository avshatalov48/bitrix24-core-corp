import { HtmlFormatter } from 'ui.bbcode.formatter.html-formatter';
import { EmptyState } from './empty-state';
import { mapGetters } from 'ui.vue3.vuex';
import { Dom } from 'main.core';

export const Prompt = {
	name: 'Prompt',

	components: {
		EmptyState,
	},

	props: {
		htmlFormatter: {
			type: HtmlFormatter,
			default: new HtmlFormatter(),
			required: false,
		},
	},

	computed: mapGetters(['callAssessmentPrompt', 'isScriptSelected']),

	watch: {
		callAssessmentPrompt(prompt: ?string): void
		{
			this.applyCallAssessmentPrompt(prompt);
		},
	},

	mounted(): void
	{
		if (this.isScriptSelected)
		{
			this.applyCallAssessmentPrompt(this.callAssessmentPrompt);
		}
	},

	methods: {
		applyCallAssessmentPrompt(prompt: ?string): void
		{
			if (prompt === null)
			{
				return;
			}

			const promptContainer = this.$refs.prompt;
			const formattedPromptContent = this.htmlFormatter.format({ source: prompt });

			Dom.clean(promptContainer);
			Dom.append(formattedPromptContent, promptContainer);

			this.$refs.container.scrollTop = 0;
		},
	},

	template: `
		<div ref="container" class="crm-copilot__call-card-replacement-main">
			<div class="crm-copilot__call-card-replacement-prompt-wrapper">
				<div v-show="isScriptSelected" ref="prompt" class="crm-copilot__call-card-replacement-prompt"></div>
				<EmptyState
					v-if="!isScriptSelected"
					icon="DocumentIcon"
					:title="$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_NOT_RESOLVED_SCRIPT_TITLE')"
					:description="$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_NOT_RESOLVED_SCRIPT_DESCRIPTION')"
				/>
			</div>
		</div>
	`,
};
