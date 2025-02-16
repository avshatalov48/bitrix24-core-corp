import { mapGetters } from 'ui.vue3.vuex';
import { ButtonSize, ButtonColor } from 'booking.component.button';
import { ClientPopup } from 'booking.component.client-popup';
import type { CurrentClient } from 'booking.component.client-popup';
import { CrmEntity, Model } from 'booking.const';
import type { ClientModel } from 'booking.model.clients';

export const AddClientButton = {
	name: 'AddClientButton',
	emits: ['update:model-value'],
	props: {
		modelValue: {
			type: Array,
			required: true,
		},
	},
	data(): { isPopupShown: boolean }
	{
		return {
			isPopupShown: false,
		};
	},
	computed:
		{
			...mapGetters({
				getByClientData: `${Model.Clients}/getByClientData`,
			}),
			color(): string
			{
				return ButtonColor.LINK;
			},
			size(): string
			{
				return ButtonSize.EXTRA_SMALL;
			},
			label(): string
			{
				if (this.modelValue.length === 0)
				{
					return this.loc('BOOKING_MULTI_CLIENT');
				}

				return this.loc('BOOKING_MULTI_CLIENT_WHIT_NAME', {
					'#NAME#': this.modelValue.find((client) => client.name)?.name || '',
				});
			},
			currentClient(): CurrentClient | null
			{
				if (this.modelValue.length === 0)
				{
					return null;
				}

				return {
					contact: this.findClientByType(CrmEntity.Contact),
					company: this.findClientByType(CrmEntity.Company),
				};
			},
		},
	methods: {
		createClients(clients: Object[])
		{
			const clientsData = clients.map((client) => this.getByClientData(client));
			this.$emit('update:model-value', clientsData);
		},
		findClientByType(clientTypeCode: string): ClientModel
		{
			return this.modelValue.find(({ type }) => type.code === clientTypeCode);
		},
	},
	components: {
		ClientPopup,
	},
	template: `
		<button
			:class="['ui-btn', 'booking--multi-booking--client-button', color, size]"
			type="button"
			ref="button"
			@click="isPopupShown = !isPopupShown"
		>
			<i class="ui-icon-set --customer-card"></i>
			<span>{{ label }}</span>
		</button>
		<ClientPopup
			v-if="isPopupShown"
			:bind-element="$refs.button"
			:currentClient
			@create="createClients"
			@close="isPopupShown = false"/>
	`,
};
