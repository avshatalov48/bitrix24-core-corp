import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import './remove-button.css';

type RemoveButtonData = {
	iconSet: { [string]: string },
}

// @vue/component
export const RemoveButton = {
	name: 'RemoveButton',
	components: {
		Icon,
	},
	props: {
		showLabel: {
			type: Boolean,
			default: false,
		},
		dataAttributes: {
			type: Object,
			default: () => ({}),
		},
	},
	emits: ['remove'],
	setup(): RemoveButtonData
	{
		const iconSet = IconSet;

		return {
			iconSet,
		};
	},
	template: `
		<div
			class="booking-actions-popup__item-remove-button"
			:title="loc('BB_ACTIONS_POPUP_OVERBOOKING_REMOVE')"
			v-bind="dataAttributes"
			@click="$emit('remove')"
		>
			<Icon :name="iconSet.TRASH_BIN"/>
			<div v-if="showLabel" class="booking-actions-popup__item-overbooking-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_REMOVE') }}
			</div>
		</div>
	`,
};
