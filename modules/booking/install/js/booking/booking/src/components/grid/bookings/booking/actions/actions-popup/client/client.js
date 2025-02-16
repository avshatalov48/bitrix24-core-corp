import { SidePanel } from 'main.sidepanel';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import { lazyload } from 'ui.vue3.directives.lazyload';
import { hint } from 'ui.vue3.directives.hint';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { Loader } from 'booking.component.loader';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientData, ClientModel } from 'booking.model.clients';

import { Note } from './note/note';
import { Empty } from './empty';
import { EditClientButton } from './edit-client-button/edit-client-button';
import './client.css';

export const Client = {
	emits: ['freeze', 'unfreeze'],
	name: 'BookingActionsPopupClient',
	directives: { lazyload, hint },
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	components: {
		Button,
		Icon,
		Loader,
		Empty,
		Note,
		EditClientButton,
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			isLoading: true,
		};
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
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
		client(): ClientModel | null
		{
			const clientData: ClientData = this.booking.primaryClient;

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
	template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-client">
			<div class="booking-actions-popup__item-client-client">
				<Loader v-if="isLoading" class="booking-actions-popup__item-client-loader" />
				<template v-else-if="client">
					<div class="booking-actions-popup__item-client-icon-container">
						<div
							v-if="clientAvatar"
							class="booking-actions-popup-user__avatar"
							:class="imageTypeClass"
						>
							<img
								v-lazyload :data-lazyload-src="clientAvatar"
								class="booking-actions-popup-user__source"
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
								data-element="booking-menu-client-open"
								:data-booking-id="bookingId"
								class="booking-actions-popup-item-client-open-button"
								:text="loc('BB_ACTIONS_POPUP_CLIENT_BTN_LABEL')"
								:size="ButtonSize.EXTRA_SMALL"
								:color="ButtonColor.LIGHT_BORDER"
								:round="true"
								@click="openClient"
							/>
							<EditClientButton
								:bookingId="bookingId"
								@visible="$emit('freeze')"
								@invisible="$emit('unfreeze')"
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
						:bookingId="bookingId"
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
					/>
				</template>
			</div>
			<Note
				:bookingId="bookingId"
				@popupShown="$emit('freeze')"
				@popupClosed="$emit('unfreeze')"
			/>
		</div>
	`,
};
