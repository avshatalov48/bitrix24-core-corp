import { Outline } from 'ui.icon-set.api.vue';

import { ActionButton } from './action-button';

// @vue/component
export const Copilot = {
	name: 'TaskDescriptionCopilot',
	components: {
		ActionButton,
		Outline,
	},
	setup(): Object
	{
		return {
			Outline,
		};
	},
	computed: {
		buttonColor(): String
		{
			return 'var(--ui-color-copilot-primary)';
		},
	},
	methods: {
		handleClick()
		{
			alert('Not implemented yet');
		},
	},
	template: `
		<ActionButton
			:iconName="Outline.COPILOT"
			:title="loc('TASKS_V2_DESCRIPTION_ACTION_COPILOT_TITLE')"
			:iconColor="buttonColor"
			@click="handleClick"
		/>
	`,
};
