import { Outline } from 'ui.icon-set.api.core';

import { ActionButton } from './action-button';

// @vue/component
export const FullDescription = {
	name: 'TaskFullDescription',
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
			:icon-name="Outline.GO_TO_L"
			:title="loc('TASKS_V2_DESCRIPTION_BUTTON_EXPAND')"
		/>
	`,
};
