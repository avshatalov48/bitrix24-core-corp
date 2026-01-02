import { Outline } from 'ui.icon-set.api.vue';

import { ActionButton } from './action-button';

// @vue/component
export const Mention = {
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
			:iconName="Outline.MENTION"
			:title="loc('TASKS_V2_DESCRIPTION_ACTION_MENTION_TITLE')"
		/>
	`,
};
