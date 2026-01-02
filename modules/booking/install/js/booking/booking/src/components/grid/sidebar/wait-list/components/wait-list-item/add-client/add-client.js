import { AddClient } from 'booking.component.booking';
import { waitListService } from 'booking.provider.service.wait-list-service';
import type { ClientData } from 'booking.model.clients';
// eslint-disable-next-line no-unused-vars
import type { WaitListItemModel } from 'booking.model.wait-list';

import './add-client.css';

// @vue/component
export const WaitListItemAddClient = {
	name: 'WaitListItemAddClient',
	components: {
		AddClient,
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
	methods: {
		async addClient(clients: ClientData[]): Promise<void>
		{
			const id = this.waitListItem.id;
			await waitListService.update({
				id,
				clients,
			});
		},
	},
	template: `
		<AddClient
			:dataAttributes="{
				'data-id': waitListItem.id,
				'data-element': 'booking-wait-list-item-add-client-button',
			}"
			:popupOffsetLeft="-300"
			@add="addClient"
		/>
	`,
};
