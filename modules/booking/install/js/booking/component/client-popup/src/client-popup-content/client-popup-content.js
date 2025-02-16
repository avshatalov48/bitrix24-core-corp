import { Popup } from 'main.popup';
import { Notifier } from 'ui.notification-manager';

import { clientService } from 'booking.provider.service.client-service';
import { CrmEntity } from 'booking.const';
import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import type { ClientModel } from 'booking.model.clients';

import { ClientInput } from './components/client-input';
import { PhoneInput } from './components/phone-input';
import { EmailInput } from './components/email-input';
import { deepToRawClientModel } from './lib';
import type { CurrentClient } from './types';
import './client-popup-content.css';

export const ClientPopupContent = {
	name: 'ClientPopupContent',
	emits: ['create', 'close'],
	props: {
		adjustPosition: {
			type: Function,
			required: true,
		},
		currentClient: {
			type: Object,
			default: null,
		},
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			CrmEntity,
			contact: null,
			company: null,
			isSaving: false,
		};
	},
	computed: {
		hasClient(): boolean
		{
			return this.hasContact || this.hasCompany;
		},
		hasContact(): boolean
		{
			return Boolean(this.contact);
		},
		hasCompany(): boolean
		{
			return Boolean(this.company);
		},
		cannotSave(): boolean
		{
			return this.clients.length > 0 && this.filledClients.length === 0;
		},
		filledClients(): ClientModel[]
		{
			return this.clients.filter((client) => client.name?.trim());
		},
		clients(): ClientModel[]
		{
			const clients = [];

			if (this.contact)
			{
				clients.push({
					...this.contact,
					name: this.$refs.contactInput?.getValue() ?? '',
				});
			}

			if (this.company)
			{
				clients.push({
					...this.company,
					name: this.$refs.companyInput?.getValue() ?? '',
				});
			}

			return clients;
		},
	},
	beforeMount(): void
	{
		const currentClient: CurrentClient | null = this.currentClient;

		if (!currentClient)
		{
			return;
		}

		if (currentClient.contact)
		{
			this.setContact(deepToRawClientModel(currentClient.contact));
		}

		if (currentClient.company)
		{
			this.company = deepToRawClientModel(currentClient.company);
		}
	},
	methods: {
		setContact(contact: ClientModel | null): void
		{
			this.contact = contact;
		},
		async setCompany(company: ClientModel | null): Promise<void>
		{
			const previousCompanyId = this.company?.id;
			const newCompanyId = company?.id;

			this.company = company;
			if (!newCompanyId || previousCompanyId === newCompanyId)
			{
				return;
			}

			const linkedContact = await clientService.getLinkedContactByCompany(this.company);
			if (linkedContact)
			{
				this.contact = linkedContact;
			}
		},
		async saveClients(): Promise<void>
		{
			this.isSaving = true;

			const { clients, error } = await clientService.saveMany(this.filledClients);

			this.isSaving = false;

			if (error)
			{
				Notifier.notify({
					id: 'booking-client-popup-save-error',
					text: error.message,
				});

				return;
			}

			this.$emit('create', clients);

			this.closePopup();
		},
		getClientsPopup(): ?Popup
		{
			const contactsPopup = this.$refs.contactInput.getPopup();
			const companiesPopup = this.$refs.companyInput.getPopup();

			if (contactsPopup?.isShown())
			{
				return contactsPopup;
			}

			if (companiesPopup?.isShown())
			{
				return companiesPopup;
			}

			return null;
		},
		closePopup(): void
		{
			this.$emit('close');
		},
	},
	watch: {
		isNew(): void
		{
			void this.$nextTick(() => this.adjustPosition());
		},
	},
	components: {
		Button,
		ClientInput,
		PhoneInput,
		EmailInput,
	},
	template: `
		<div class="booking-booking-client-popup-header">
			<div class="booking-booking-client-popup-header-text">
				{{ loc('BOOKING_BOOKING_ADD_CLIENT_POPUP_HEADER') }}
			</div>
			<div
				class="ui-icon-set --cross-45"
				data-element="booking-client-popup-close"
				@click="closePopup"
			></div>
		</div>
		<div class="booking-booking-client-popup-contact">
			<ClientInput
				:code="CrmEntity.Contact"
				:client="contact"
				:isWarning="contact && cannotSave"
				ref="contactInput"
				@setClient="setContact"
			/>
			<template v-if="hasContact">
				<PhoneInput v-model="contact.phones[0]" :clientId="contact.id || 0" :code="CrmEntity.Contact"/>
				<EmailInput v-model="contact.emails[0]" :clientId="contact.id || 0" :code="CrmEntity.Contact"/>
			</template>
			<ClientInput
				:code="CrmEntity.Company"
				:client="company"
				:isWarning="company && cannotSave"
				ref="companyInput"
				@setClient="setCompany"
			/>
			<template v-if="hasCompany">
				<PhoneInput v-model="company.phones[0]" :clientId="company.id || 0" :code="CrmEntity.Company"/>
				<EmailInput v-model="company.emails[0]" :clientId="company.id || 0" :code="CrmEntity.Company"/>
			</template>
		</div>
		<div v-if="hasClient" class="booking-booking-client-popup-buttons">
			<Button
				:dataset="{element: 'booking-client-popup-save'}"
				:text="loc('BOOKING_BOOKING_ADD_CLIENT_SAVE')"
				:size="ButtonSize.EXTRA_SMALL"
				:color="ButtonColor.PRIMARY"
				:round="true"
				:disabled="cannotSave"
				:waiting="isSaving"
				@click="saveClients"
			/>
			<Button
				:dataset="{element: 'booking-client-popup-cancel'}"
				:text="loc('BOOKING_BOOKING_ADD_CLIENT_CANCEL')"
				:size="ButtonSize.EXTRA_SMALL"
				:color="ButtonColor.LINK"
				:round="true"
				@click="closePopup"
			/>
		</div>
		<div v-else class="booking-booking-client-popup-hint">
			{{ loc('BOOKING_BOOKING_ADD_CLIENT_POPUP_HINT') }}
		</div>
	`,
};
