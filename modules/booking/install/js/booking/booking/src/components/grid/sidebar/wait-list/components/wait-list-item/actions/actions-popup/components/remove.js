import { RemoveButton } from 'booking.component.actions-popup';
import { RemoveWaitListItem } from 'booking.lib.remove-wait-list-item';

// @vue/component
export const WaitListItemRemove = {
	name: 'WaitListItemRemove',
	components: {
		RemoveButton,
	},
	props: {
		waitListItemId: {
			type: Number,
			required: true,
		},
	},
	emits: ['close'],
	methods: {
		remove(): void
		{
			new RemoveWaitListItem(this.waitListItemId);
			this.$emit('close');
		},
	},
	template: `
		<RemoveButton
			showLabel
			:dataAttributes="{
				'data-id': waitListItemId,
				'data-element': 'booking-wait-list-item-menu-remove-button'
			}"
			@remove="remove"
		/>
	`,
};
