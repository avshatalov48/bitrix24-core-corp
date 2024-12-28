import { UpdateScript as UpdateScriptButton } from './button/update-script';
import { AllScripts as AllScriptsButton, ALL_SCRIPTS_URL } from './button/all-scripts';
import { Prompt } from './prompt';
import { ScriptSelector } from './script-selector';
import { mapGetters } from 'ui.vue3.vuex';
import { EmptyState } from './empty-state';

import '../styles/main.css';

export const Main = {
	name: 'Main',

	components: {
		ScriptSelector,
		Prompt,
		UpdateScriptButton,
		AllScriptsButton,
		EmptyState,
	},

	methods: {
		emptyStateTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_NO_AVAILABLE_SCRIPTS_TITLE');
		},

		emptyStateDescription(): string
		{
			let message = this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_NO_AVAILABLE_SCRIPTS_DESCRIPTION');
			message = message.replace('[link]', `<a href="${ALL_SCRIPTS_URL}" target="_blank" class="crm-copilot__call-card-replacement-empty-state-link">`);
			message = message.replace('[/link]', '</a>');

			return message;
		},
	},

	computed: mapGetters(['hasAvailableSelectorItems']),

	template: `
		<div class="crm-copilot__call-card-replacement">
			<div v-if="hasAvailableSelectorItems" class="crm-copilot__call-card-replacement-content">
				<ScriptSelector />
				<Prompt />
			</div>
			<EmptyState
				v-else
				icon="SearchIcon"
				:title="emptyStateTitle()"
				:description="emptyStateDescription()"
			/>
			<div class="crm-copilot__call-card-replacement-footer">
				<UpdateScriptButton />
				<AllScriptsButton />
			</div>
		</div>
	`,
};
