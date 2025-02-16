import { limit } from 'booking.lib.limit';
import { Event } from 'main.core';
import { Menu, MenuManager } from 'main.popup';
import type { MenuItemOptions } from 'main.popup';

import { Notifier } from 'ui.notification-manager';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { Loader } from 'booking.component.loader';
import { bookingActionsService } from 'booking.provider.service.booking-actions-service';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientData, ClientModel } from 'booking.model.clients';
import type { MessageStatusModel } from 'booking.model.message-status';

import './message.css';

export const Message = {
	emits: ['freeze', 'unfreeze'],
	name: 'BookingActionsPopupMessage',
	props: {
		bookingId: {
			type: Number,
			required: true,
		},
	},
	components: {
		Button,
		Icon,
		Loader,
	},
	data(): Object
	{
		return {
			IconSet,
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			isLoading: true,
			isPrimaryClientIdUpdated: false,
		};
	},
	mounted(): void
	{
		void this.fetchMessageData();
	},
	watch: {
		clientId(): void
		{
			this.isPrimaryClientIdUpdated = true;
		},
		updatedAt(): void
		{
			if (this.isPrimaryClientIdUpdated && this.isCurrentSenderAvailable)
			{
				void this.fetchMessageData();

				this.isPrimaryClientIdUpdated = false;
			}
		},
	},
	methods: {
		openMenu(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			if (this.status.isDisabled && this.isCurrentSenderAvailable)
			{
				return;
			}

			if (this.getMenu()?.getPopupWindow()?.isShown())
			{
				this.destroyMenu();

				return;
			}

			const menuButton = this.$refs.button.$el;
			MenuManager.create(
				this.menuId,
				menuButton,
				this.getMenuItems(),
				{
					autoHide: true,
					offsetTop: 0,
					offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
					angle: true,
					events: {
						onClose: this.destroyMenu,
						onDestroy: this.destroyMenu,
					},
				},
			).show();

			this.$emit('freeze');
			Event.bind(document, 'scroll', this.adjustPosition, { capture: true });
		},
		getMenuItems(): MenuItemOptions[]
		{
			return Object.values(this.dictionary).map(({ name, value }) => ({
				text: name,
				onclick: () => this.sendMessage(value),
				disabled: value === this.dictionary.Feedback.value,
			}));
		},
		async sendMessage(notificationType: string): Promise<void>
		{
			this.destroyMenu();

			const result = await bookingActionsService.sendMessage(this.bookingId, notificationType);

			if (!result.isSuccess)
			{
				Notifier.notify({
					id: 'booking-message-send-error',
					text: result.errorText,
				});
			}

			void this.fetchMessageData();
		},
		destroyMenu(): void
		{
			MenuManager.destroy(this.menuId);
			this.$emit('unfreeze');
			Event.unbind(document, 'scroll', this.adjustPosition, { capture: true });
		},
		adjustPosition(): void
		{
			this.getMenu()?.getPopupWindow()?.adjustPosition();
		},
		getMenu(): Menu | null
		{
			return MenuManager.getMenuById(this.menuId);
		},
		async fetchMessageData(): Promise<void>
		{
			this.isLoading = true;

			await bookingActionsService.getMessageData(this.bookingId);

			this.isLoading = false;
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.BookingActionsMessage.code,
				HelpDesk.BookingActionsMessage.anchorCode,
			);
		},
	},
	computed: {
		...mapGetters({
			dictionary: `${Model.Dictionary}/getNotifications`,
			isCurrentSenderAvailable: `${Model.Interface}/isCurrentSenderAvailable`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		menuId(): string
		{
			return `booking-message-menu-${this.bookingId}`;
		},
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
		client(): ClientModel | null
		{
			const clientData: ClientData = this.booking.primaryClient;

			return clientData ? this.$store.getters['clients/getByClientData'](clientData) : null;
		},
		clientId(): number
		{
			return this.booking.primaryClient?.id;
		},
		updatedAt(): number
		{
			return this.booking.updatedAt;
		},
		status(): MessageStatusModel
		{
			return this.$store.getters[`${Model.MessageStatus}/getById`](this.bookingId);
		},
		iconColor(): string
		{
			const colorMap: Record<MessageStatusModel['semantic'], string> = {
				success: '#ffffff',
				primary: '#ffffff',
				failure: '#ffffff',
			};

			return colorMap[this.status.semantic] || '';
		},
		failure(): boolean
		{
			return this.status.semantic === 'failure';
		},
	},
	template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-message-content"
			:class="{'--disabled': !isCurrentSenderAvailable}"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-message-loader" />
			<template v-else>
				<div
					class="booking-actions-popup-item-icon"
					:class="'--' + status.semantic"
				>
					<Icon
						:name="IconSet.SMS"
						:color="iconColor"
					/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span :title="status.title">{{ status.title }}</span>
						<Icon :name="IconSet.HELP" @click="showHelpDesk"/>
					</div>
					<div
						class="booking-actions-popup-item-subtitle"
						:class="'--' + status.semantic"
					>
						{{ status.description }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<Button
						data-element="booking-menu-message-button"
						:data-booking-id="bookingId"
						class="booking-actions-popup-button-with-chevron"
						:class="{
							'--lock': !isFeatureEnabled,
							'--disabled': status.isDisabled && isCurrentSenderAvailable
						}"
						buttonClass="ui-btn-shadow"
						:text="loc('BB_ACTIONS_POPUP_MESSAGE_BUTTON_SEND')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT"
						:round="true"
						ref="button"
						@click="openMenu"
					>
						<Icon v-if="isFeatureEnabled" :name="IconSet.CHEVRON_DOWN"/>
						<Icon v-else :name="IconSet.LOCK"/>
					</Button>
					<div
						v-if="failure"
						class="booking-actions-popup-item-buttons-counter"
					></div>
				</div>
			</template>
			<div
				v-if="!isCurrentSenderAvailable"
				class="booking-booking-actions-popup-label"
			>
				{{ loc('BB_ACTIONS_POPUP_LABEL_SOON') }}
			</div>
		</div>
	`,
};
