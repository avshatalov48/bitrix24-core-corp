import { Confirmation } from 'booking.component.actions-popup';

// @vue/component
export const WaitListItemConfirmation = {
	name: 'WaitListItemConfirmation',
	components: {
		Confirmation,
	},
	props: {
		waitListItemId: {
			type: Number,
			required: true,
		},
	},
	emits: ['freeze', 'unfreeze'],
	template: `
		<Confirmation
			:id="waitListItemId"
			:isConfirmed="false"
			:counters="[]"
			disabled
			:dataId="waitListItemId"
			dataPre
		/>
	`,
};
