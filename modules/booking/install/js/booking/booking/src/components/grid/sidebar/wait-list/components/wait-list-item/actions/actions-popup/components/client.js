import { Client } from 'booking.component.actions-popup';
import { Model } from 'booking.const';
import { waitListService } from 'booking.provider.service.wait-list-service';
import type { WaitListItemModel } from 'booking.model.wait-list';
import type {
	AddClientsPayload,
	UpdateClientsPayload,
	UpdateNotePayload,
} from 'booking.component.actions-popup';

// @vue/component
export const WaitListItemClient = {
	name: 'WaitListItemClient',
	components: {
		Client,
	},
	props: {
		waitListItemId: {
			type: Number,
			required: true,
		},
	},
	emits: ['freeze', 'unfreeze'],
	computed: {
		waitListItem(): WaitListItemModel
		{
			return this.$store.getters[`${Model.WaitList}/getById`](this.waitListItemId);
		},
	},
	methods: {
		async addClients({ clients }: AddClientsPayload): Promise<void>
		{
			await waitListService.update({
				id: this.waitListItem.id,
				clients,
			});
		},
		async updateClients({ clients }: UpdateClientsPayload): Promise<void>
		{
			await waitListService.update({
				id: this.waitListItem.id,
				clients,
			});
		},
		async updateNote({ note }: UpdateNotePayload): Promise<void>
		{
			await waitListService.update({
				id: this.waitListItem.id,
				note,
			});
		},
	},
	template: `
		<Client
			:id="waitListItemId"
			:primaryClientData="waitListItem.clients?.[0] || null"
			:clients="waitListItem.clients"
			:note="waitListItem.note"
			:dataId="waitListItemId"
			dataElementPrefix="wait-list-item"
			:dataAttributes="{
				'data-wait-list-item-id': waitListItemId,
			}"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
			@addClients="addClients"
			@updateClients="updateClients"
			@updateNote="updateNote"
		/>
	`,
};
