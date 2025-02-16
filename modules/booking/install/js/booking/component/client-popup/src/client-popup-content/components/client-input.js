import { EventEmitter, BaseEvent } from 'main.core.events';
import { Popup } from 'main.popup';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import 'ui.icon-set.crm';
import 'ui.dropdown';

import type { ClientModel } from 'booking.model.clients';
import { CrmEntity, Model } from 'booking.const';

import { InputField } from './input-field';
import { clientToItem, itemToClient, getEmptyClient } from '../lib';
import type { Item } from '../types';

export const ClientInput = {
	emits: ['setClient'],
	props: {
		client: {
			type: Object,
			default: null,
		},
		code: {
			type: String,
			required: true,
		},
		isWarning: {
			type: Boolean,
			default: false,
		},
	},
	mounted(): void
	{
		this.dropdown = new BX.UI.Dropdown({
			targetElement: this.$refs.clientInput,
			searchAction: 'crm.api.entity.search',
			searchOptions: {
				types: [this.code],
				scope: 'index',
			},
			enableCreation: true,
			autocompleteDelay: 200,
			items: this.clientsItems,
			messages: {
				creationLegend: this.creationLegend,
			},
			events: {
				onSelect: (dropdown, item) => this.setClient(this.itemToClient(item)),
				onAdd: (dropdown, item) => this.setClient(this.itemToClient(item)),
			},
		});

		this.updateValue();
		this.onInput();

		EventEmitter.subscribe(this.dropdown, 'BX.UI.Dropdown:onSearchComplete', this.onDropdownContactsLoaded);
	},
	beforeUnmount(): void
	{
		this.dropdown.destroyPopupWindow();
		EventEmitter.unsubscribe(this.dropdown, 'BX.UI.Dropdown:onSearchComplete', this.onDropdownContactsLoaded);
	},
	computed: {
		clientId(): number | null
		{
			return this.client?.id;
		},
		clientsItems(): Item[]
		{
			switch (this.code)
			{
				case CrmEntity.Contact:
					return this.$store.getters[`${Model.Clients}/getContacts`].map((it) => clientToItem(it));
				case CrmEntity.Company:
					return this.$store.getters[`${Model.Clients}/getCompanies`].map((it) => clientToItem(it));
				default:
					return [];
			}
		},
		fieldName(): string
		{
			switch (this.code)
			{
				case CrmEntity.Contact:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_CONTACT');
				case CrmEntity.Company:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_COMPANY');
				default:
					return '';
			}
		},
		inputPlaceholder(): string
		{
			switch (this.code)
			{
				case CrmEntity.Contact:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_CONTACT_PLACEHOLDER');
				case CrmEntity.Company:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_COMPANY_PLACEHOLDER');
				default:
					return '';
			}
		},
		creationLegend(): string
		{
			switch (this.code)
			{
				case CrmEntity.Contact:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_DROPDOWN_CREATE_NEW_CONTACT');
				case CrmEntity.Company:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_DROPDOWN_CREATE_NEW_COMPANY');
				default:
					return '';
			}
		},
		inputLabel(): string
		{
			if (this.isWarning)
			{
				return this.loc('BOOKING_BOOKING_ADD_CLIENT_CLIENT_REQUIRED');
			}

			if (this.isNew)
			{
				switch (this.code)
				{
					case CrmEntity.Contact:
						return this.loc('BOOKING_BOOKING_ADD_CLIENT_CONTACT_NEW');
					case CrmEntity.Company:
						return this.loc('BOOKING_BOOKING_ADD_CLIENT_COMPANY_NEW');
					default:
						return '';
				}
			}

			switch (this.code)
			{
				case CrmEntity.Contact:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_CLIENT_EDIT');
				case CrmEntity.Company:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_CLIENT_EDIT');
				default:
					return '';
			}
		},
		isNew(): boolean
		{
			return this.hasClient && !this.isEditing;
		},
		isEditing(): boolean
		{
			return this.hasClient && Boolean(this.client?.id);
		},
		hasClient(): boolean
		{
			return Boolean(this.client);
		},
		clearHint(): string
		{
			switch (this.code)
			{
				case CrmEntity.Contact:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_CHOOSE_ANOTHER_CONTACT');
				case CrmEntity.Company:
					return this.loc('BOOKING_BOOKING_ADD_CLIENT_CHOOSE_ANOTHER_COMPANY');
				default:
					return '';
			}
		},
		leftIcon(): string
		{
			switch (this.code)
			{
				case CrmEntity.Contact:
					return IconSet.PERSON;
				case CrmEntity.Company:
					return IconSet.COMPANY;
				default:
					return '';
			}
		},
		searchIcon(): string
		{
			return IconSet.SEARCH_2;
		},
		arrowsIcon(): string
		{
			return IconSet.SWAP;
		},
	},
	methods: {
		onInput(): void
		{
			if (this.client)
			{
				this.setClient({
					...this.client,
					name: this.getValue(),
				});
			}
		},
		getValue(): string
		{
			return this.$refs.clientInput.value;
		},
		getPopup(): ?Popup
		{
			return this.dropdown.popupWindow;
		},
		onDropdownContactsLoaded(event: BaseEvent): void
		{
			const [, results] = event.getData();

			this.$store.dispatch(`${Model.Clients}/upsertMany`, results.map((it) => this.itemToClient(it)));
		},
		getClient(item: Item): ClientModel
		{
			const client = this.$store.getters[`${Model.Clients}/getByClientData`]({
				id: item.id,
				type: {
					module: item.module,
					code: item.type,
				},
			});

			return client ?? this.itemToClient(item);
		},
		itemToClient(item: Item): ClientModel
		{
			if (item.id)
			{
				return itemToClient(item);
			}

			return this.getEmptyClient(item);
		},
		getEmptyClient(item: Item): ClientModel
		{
			return getEmptyClient(item, this.code);
		},
		clear(): void
		{
			this.setClient(null);
		},
		setClient(client: ClientModel): void
		{
			this.$emit('setClient', client);
		},
		updateValue(): void
		{
			if (this.client)
			{
				this.$refs.clientInput.value = this.client.name;
				this.dropdown.isDisabled = true;
				this.dropdown.destroyPopupWindow();
				this.dropdown.popupAlertContainer = null;
			}
			else
			{
				this.$refs.clientInput.value = '';
				this.dropdown.isDisabled = false;
			}
		},
	},
	watch: {
		client(): void
		{
			this.updateValue();
		},
		clientId(): void
		{
			this.onInput();
		},
		clientsItems(clientsItems): void
		{
			this.dropdown.setDefaultItems(clientsItems);
		},
	},
	components: {
		InputField,
		Icon,
	},
	template: `
		<InputField
			:name="fieldName"
			:data-element="'booking-client-field-' + code"
		>
			<input
				class="booking-booking-client-popup-field-input --left-icon --right-icon"
				:class="{'--warning': isWarning}"
				:placeholder="inputPlaceholder"
				data-element="booking-client-input"
				:data-id="client?.id || 0"
				:data-code="code"
				:data-new="isNew"
				:data-editing="isEditing"
				ref="clientInput"
				@input="onInput"
			/>
			<div class="booking-booking-client-popup-field-input-icon">
				<div class="booking-booking-client-popup-field-input-avatar-icon">
					<Icon :name="leftIcon"/>
				</div>
			</div>
			<div
				v-if="hasClient"
				class="booking-booking-client-popup-field-input-label"
				:class="{'--warning': isWarning}"
				data-element="booking-client-input-label"
			>
				{{ inputLabel }}
			</div>
			<div
				v-else
				class="booking-booking-client-popup-field-input-icon-right"
				data-element="booking-client-search-icon"
			>
				<Icon :name="searchIcon"/>
			</div>
			<div
				v-if="hasClient"
				class="booking-booking-client-popup-field-input-icon-right --clickable"
				:title="clearHint"
				data-element="booking-client-clear-icon"
				@click="clear"
			>
				<Icon :name="arrowsIcon"/>
			</div>
		</InputField>
	`,
};
