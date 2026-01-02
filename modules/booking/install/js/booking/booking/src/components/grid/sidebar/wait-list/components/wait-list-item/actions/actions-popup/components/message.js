import { Message } from 'booking.component.actions-popup';

// @vue/component
export const WaitListItemMessage = {
	name: 'WaitListItemMessage',
	components: {
		Message,
	},
	props: {
		waitListItemId: {
			type: Number,
			required: true,
		},
	},
	template: `
		<Message
			:id="waitListItemId"
			:clientData="null"
			:loading="false"
			disabled
			:dataId="waitListItemId"
			dataElementPrefix="wait-list"
		/>
	`,
};
