import { SidePanel as SidePanelMain } from 'main.sidepanel';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import { lazyload } from 'ui.vue3.directives.lazyload';
import { hint } from 'ui.vue3.directives.hint';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { Loader } from 'booking.component.loader';
import type { ClientData, ClientModel } from 'booking.model.clients';

import { Note } from './note/note';
import { Empty } from './empty/empty';
import { EditClientButton } from './edit-client-button/edit-client-button';
import './client.css';

export type { AddClientsPayload } from './empty/empty';
export type { UpdateClientsPayload } from './edit-client-button/edit-client-button';
export type { UpdateNotePayload } from './note/note';

const SidePanel = SidePanelMain || BX.SidePanel;

// @vue/component
export const Client = {
	name: 'ActionsPopupClient',
	directives: { lazyload, hint },
	components: {
		Button,
		Icon,
		Loader,
		Empty,
		Note,
		EditClientButton,
	},
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		/**
		 * @type ClientData
		 */
		primaryClientData: {
			type: Object,
			default: null,
		},
		/**
		 * @type ClientData
		 */
		clients: {
			type: Array,
			default: () => [],
		},
		note: {
			type: String,
			default: '',
		},
		dataId: {
			type: [Number, String],
			default: '',
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
	emits: [
		'freeze',
		'unfreeze',
		'addClients',
		'updateClients',
		'updateNote',
	],
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			isLoading: true,
		};
	},
	computed: {
		client(): ClientModel | null
		{
			const clientData: ClientData = this.primaryClientData;

			return clientData ? this.$store.getters['clients/getByClientData'](clientData) : null;
		},
		clientPhone(): string
		{
			const client: ClientModel = this.client;

			return (
				client.phones.length > 0
					? client.phones[0]
					: this.loc('BB_ACTIONS_POPUP_CLIENT_PHONE_LABEL')
			);
		},
		clientAvatar(): string
		{
			const client: ClientModel = this.client;

			return client.image;
		},
		clientStatus(): string
		{
			if (!this.client.isReturning)
			{
				return this.loc('BB_ACTIONS_POPUP_CLIENT_STATUS_FIRST');
			}

			return this.loc('BB_ACTIONS_POPUP_CLIENT_STATUS_RETURNING');
		},
		userIcon(): string
		{
			return IconSet.PERSON;
		},
		personSize(): number
		{
			return 26;
		},
		callIcon(): string
		{
			return IconSet.TELEPHONY_HANDSET_1;
		},
		messageIcon(): string
		{
			return IconSet.CHATS_1;
		},
		iconSize(): number
		{
			return 20;
		},
		iconColor(): string
		{
			return 'var(--ui-color-palette-gray-20)';
		},
		imageTypeClass(): string[] | string
		{
			return '--user';
		},
		soonHint(): Object
		{
			return {
				text: this.loc('BOOKING_BOOKING_SOON_HINT'),
				popupOptions: {
					offsetLeft: -60,
				},
			};
		},
	},
	async mounted()
	{
		this.isLoading = false;
	},
	methods: {
		openClient(): void
		{
			const entity = this.client.type.code.toLowerCase();

			SidePanel.Instance.open(`/crm/${entity}/details/${this.client.id}/`);
		},
	},
	template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-client">
			<div class="booking-actions-popup__item-client-client">
				<Loader v-if="isLoading" class="booking-actions-popup__item-client-loader"/>
				<template v-else-if="client">
					<div class="booking-actions-popup__item-client-icon-container">
						<div
							v-if="clientAvatar"
							class="booking-actions-popup-user__avatar"
							:class="imageTypeClass"
						>
							<img
								v-lazyload
								:data-lazyload-src="clientAvatar"
								class="booking-actions-popup-user__source"
								alt="user avatar"
							/>
						</div>
						<div v-else class="booking-actions-popup__item-client-icon">
							<Icon :name="userIcon" :size="personSize" :color="iconColor"/>
						</div>
					</div>
					<div class="booking-actions-popup__item-client-info">
						<div class="booking-actions-popup__item-client-info-label" :title="client.name">
							{{ client.name }}
						</div>
						<div class="booking-actions-popup-item-info">
							<div class="booking-actions-popup-item-subtitle">
								{{ clientStatus }}
							</div>
							<div class="booking-actions-popup-item-subtitle">
								{{ clientPhone }}
							</div>
						</div>
						<div class="booking-actions-popup-item-buttons booking-actions-popup__item-client-info-btn">
							<Button
								:data-element="dataElementPrefix + '-menu-client-open'"
								v-bind="dataAttributes"
								class="booking-actions-popup-item-client-open-button"
								:text="loc('BB_ACTIONS_POPUP_CLIENT_BTN_LABEL')"
								:size="ButtonSize.EXTRA_SMALL"
								:color="ButtonColor.LIGHT_BORDER"
								:round="true"
								@click="openClient"
							/>
							<EditClientButton
								:id
								:clients
								:dataElementPrefix
								:dataAttributes
								@visible="$emit('freeze')"
								@invisible="$emit('unfreeze')"
								@updateClients="$emit('updateClients', $event)"
							/>
						</div>
					</div>
					<div v-hint="soonHint" class="booking-actions-popup__item-client-action">
						<Icon :name="callIcon" :size="iconSize" :color="iconColor"/>
						<Icon :name="messageIcon" :size="iconSize" :color="iconColor"/>
					</div>
				</template>
				<template v-else>
					<Empty
						:id
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
						@addClients="$emit('addClients', $event)"
					/>
				</template>
			</div>
			<Note
				:id
				:dataId
				:dataElementPrefix
				:note
				:dataAttributes
				@popupShown="$emit('freeze')"
				@popupClosed="$emit('unfreeze')"
				@updateNote="$emit('updateNote', $event)"
			/>
		</div>
	`,
};
