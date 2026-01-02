import { ref } from 'ui.vue3';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { CrmEntity, Model } from 'booking.const';
import { ClientPopup } from 'booking.component.client-popup';
import type { CurrentClient } from 'booking.component.client-popup';
import type { ClientData, ClientModel } from 'booking.model.clients';

export type UpdateClientsPayload = {
	id: number | string,
	clients: ClientData[],
}

type OptionsDictionary = { [string]: string };
type EditClientButtonData = {
	iconSet: OptionsDictionary,
	buttonSize: OptionsDictionary,
	buttonColor: OptionsDictionary,
	buttonIcon: OptionsDictionary,
	isClientPopupShowed: boolean,
}

// @vue/component
export const EditClientButton = {
	name: 'EditClientButton',
	components: {
		ClientPopup,
		Button,
		Icon,
	},
	props: {
		id: {
			type: [Number, Array],
			required: true,
		},
		/**
		 * @type ClientData
		 */
		clients: {
			type: Array,
			default: () => [],
		},
		dataElementPrefix: {
			type: String,
			default: '',
		},
		dataAttributes: {
			type: Object,
			default: null,
		},
	},
	emits: ['visible', 'invisible', 'updateClients'],
	setup(): EditClientButtonData
	{
		const iconSet = IconSet;
		const buttonSize = ButtonSize;
		const buttonColor = ButtonColor;
		const buttonIcon = ButtonIcon;
		const isClientPopupShowed = ref(false);

		return {
			iconSet,
			buttonSize,
			buttonColor,
			buttonIcon,
			isClientPopupShowed,
		};
	},
	computed: {
		getClientByClientData(): (clientData: ClientData) => ClientModel
		{
			return this.$store.getters[`${Model.Clients}/getByClientData`];
		},
		currentClient(): CurrentClient
		{
			const getByClientData = this.getClientByClientData;
			const client: { contact: ?ClientModel, company: ?ClientModel } = {
				contact: null,
				company: null,
			};

			(this.clients || []).map((clientData) => getByClientData(clientData))
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
		updateClient(clients: ClientData[]): void
		{
			this.$emit('updateClients', {
				id: this.id,
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
	template: `
		<Button
			:data-element="dataElementPrefix + '-menu-client-edit'"
			v-bind="dataAttributes"
			:size="buttonSize.EXTRA_SMALL"
			:color="buttonColor.LIGHT"
			:round="true"
			ref="editClientButton"
			@click="showPopup"
		>
			<Icon :name="iconSet.MORE"/>
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
