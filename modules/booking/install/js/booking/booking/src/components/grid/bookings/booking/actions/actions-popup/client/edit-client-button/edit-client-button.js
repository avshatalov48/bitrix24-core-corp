import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { CrmEntity, Model } from 'booking.const';
import { ClientPopup, type CurrentClient } from 'booking.component.client-popup';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientData, ClientModel } from 'booking.model.clients';

export const EditClientButton = {
	name: 'EditClientButton',
	emits: ['visible', 'invisible'],
	props: {
		bookingId: {
			type: Number,
			required: true,
		},
	},
	data(): { isClientPopupShowed: boolean }
	{
		return {
			IconSet,
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			isClientPopupShowed: false,
		};
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		currentClient(): CurrentClient
		{
			const getByClientData = this.$store.getters[`${Model.Clients}/getByClientData`];
			const client: { contact: ?ClientModel, company: ?ClientModel } = {
				contact: null,
				company: null,
			};

			(this.booking.clients || []).map((clientData) => getByClientData(clientData))
				.forEach((clientModel) => {
					if (clientModel.type.code === CrmEntity.Contact)
					{
						client.contact = clientModel;
					}
					else if (clientModel.type.code === CrmEntity.Company)
					{
						client.company = clientModel;
					}
				});

			return client;
		},
	},
	methods: {
		async updateClient(clients: ClientData[]): Promise<void>
		{
			await bookingService.update({
				id: this.booking.id,
				clients,
			});
		},
		showPopup(): void
		{
			this.isClientPopupShowed = true;
			this.$emit('visible');
		},
		closePopup(): void
		{
			this.isClientPopupShowed = false;
			this.$emit('invisible');
		},
	},
	components: {
		ClientPopup,
		Button,
		Icon,
	},
	template: `
		<Button
			data-element="booking-menu-client-edit"
			:data-booking-id="bookingId"
			:size="ButtonSize.EXTRA_SMALL"
			:color="ButtonColor.LIGHT"
			:round="true"
			ref="editClientButton"
			@click="showPopup"
		>
			<Icon :name="IconSet.MORE"/>
		</Button>
		<ClientPopup
			v-if="isClientPopupShowed"
			:bind-element="$refs.editClientButton.$el"
			:current-client="currentClient"
			@create="updateClient"
			@close="closePopup"
		/>
	`,
};
