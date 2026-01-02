import { Outline } from 'ui.icon-set.api.vue';

import { ActionButton } from './action-button';

// @vue/component
export const Attach = {
	name: 'TaskDescriptionMention',
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
	template: `
		<ActionButton
			:iconName="Outline.ATTACH"
			:title="loc('TASKS_V2_DESCRIPTION_ACTION_ATTACH_TITLE')"
		/>
	`,
};
