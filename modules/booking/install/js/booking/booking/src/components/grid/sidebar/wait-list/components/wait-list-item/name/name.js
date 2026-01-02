import { Model } from 'booking.const';
import { Name } from 'booking.component.booking';
import type { ClientData, ClientModel } from 'booking.model.clients';
import type { WaitListItemModel } from 'booking.model.wait-list';

// @vue/component
export const WaitListItemName = {
	name: 'WaitListItemName',
	components: {
		Name,
	},
	props: {
		/**
		 * @type {WaitListItemModel}
		 */
		waitListItem: {
			type: Object,
			required: true,
		},
	},
	computed: {
		client(): ClientModel
		{
			const clientData: ClientData = this.waitListItem.clients?.[0] || null;

			return clientData ? this.$store.getters[`${Model.Clients}/getByClientData`](clientData) : null;
		},
		itemName(): string
		{
			return this.client?.name || this.waitListItem.name || this.loc('BOOKING_BOOKING_DEFAULT_BOOKING_NAME');
		},
	},
	template: `
		<Name
			:name="itemName"
			:dataAttributes="{
				'data-element': 'wait-list-item-name',
				'data-id': waitListItem.id,
			}"
		/>
	`,
};
