import { Model } from 'booking.const';
import { Visit } from 'booking.component.actions-popup';
import type { WaitListItemModel } from 'booking.model.wait-list';

// @vue/component
export const WaitListItemVisit = {
	name: 'WaitListItemVisit',
	components: {
		Visit,
	},
	props: {
		waitListItemId: {
			type: Number,
			required: true,
		},
	},
	computed: {
		waitListItem(): WaitListItemModel
		{
			return this.$store.getters[`${Model.WaitList}/getById`](this.waitListItemId);
		},
	},
	template: `
		<Visit
			:id="waitListItem.id"
			:hasClients="waitListItem.clients.length > 0"
			visitStatus="0"
			disabled
			:dataId="waitListItem.id"
			dataElementPrefix="wait-list"
		/>
	`,
};
