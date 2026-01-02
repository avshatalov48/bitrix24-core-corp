import { Event } from 'main.core';
import { Menu, MenuManager } from 'main.popup';
import type { MenuItemOptions } from 'main.popup';

import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import { limit } from 'booking.lib.limit';
import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { Loader } from 'booking.component.loader';
import type { ClientData, ClientModel } from 'booking.model.clients';
import type { MessageStatusModel } from 'booking.model.message-status';

import './message.css';

export type UpdateNotificationTypePayload = {
	id: number | string,
	notificationType: string;
}

type OptionsDictionary = { [string]: string };
type MessageData = {
	iconSet: OptionsDictionary,
	buttonSize: OptionsDictionary,
	buttonColor: OptionsDictionary,
	buttonIcon: OptionsDictionary,
}

export const Message = {
	name: 'ActionsPopupMessage',
	emits: ['open', 'close', 'updateNotificationType'],
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		/**
		 * @type ClientData
		 */
		clientData: {
			type: Object,
			default: null,
		},
		loading: {
			type: Boolean,
			default: false,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		dataId: {
			type: [Number, String],
			default: '',
		},
		dataElementPrefix: {
			type: String,
			default: '',
		},
	},
	components: {
		Button,
		Icon,
		Loader,
	},
	setup(): MessageData
	{
		const iconSet = IconSet;
		const buttonSize = ButtonSize;
		const buttonColor = ButtonColor;
		const buttonIcon = ButtonIcon;

		return {
			iconSet,
			buttonSize,
			buttonColor,
			buttonIcon,
		};
	},
	computed: {
		...mapGetters({
			dictionary: `${Model.Dictionary}/getNotifications`,
			isCurrentSenderAvailable: `${Model.Interface}/isCurrentSenderAvailable`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		menuId(): string
		{
			return `booking-message-menu-${this.id}`;
		},
		client(): ClientModel | null
		{
			const clientData: ClientData = this.clientData;

			return clientData ? this.$store.getters['clients/getByClientData'](clientData) : null;
		},
		status(): MessageStatusModel | undefined
		{
			return this.$store.getters[`${Model.MessageStatus}/getById`](this.id);
		},
		semantic(): string
		{
			return this.status?.semantic || '';
		},
		iconColor(): string
		{
			const colorMap: Record<MessageStatusModel['semantic'], string> = {
				success: '#ffffff',
				primary: '#ffffff',
				failure: '#ffffff',
			};

			return colorMap[this.semantic] || '';
		},
		failure(): boolean
		{
			return this.semantic === 'failure';
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

			if (this.disabled || (this.status.isDisabled && this.isCurrentSenderAvailable))
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
		sendMessage(notificationType: string): void
		{
			this.destroyMenu();
			this.$emit('updateNotificationType', {
				id: this.id,
				notificationType,
			});
		},
		destroyMenu(): void
		{
			MenuManager.destroy(this.menuId);
			this.$emit('close');
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
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.BookingActionsMessage.code,
				HelpDesk.BookingActionsMessage.anchorCode,
			);
		},
	},
	template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-message-content"
			:class="{'--disabled': disabled || !isCurrentSenderAvailable}"
		>
			<Loader v-if="loading" class="booking-actions-popup__item-message-loader"/>
			<template v-else>
				<div
					class="booking-actions-popup-item-icon"
					:class="'--' + semantic || 'none'"
				>
					<Icon
						:name="iconSet.SMS"
						:color="iconColor"
					/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span :title="status?.title">{{ status?.title || '' }}</span>
						<Icon :name="iconSet.HELP" @click="showHelpDesk"/>
					</div>
					<div
						class="booking-actions-popup-item-subtitle"
						:class="'--' + semantic || 'none'"
					>
						{{ status?.description || '' }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<Button
						:data-element="dataElementPrefix + '-menu-message-button'"
						:data-booking-id="dataId"
						:disabled="disabled || (status?.isDisabled && isCurrentSenderAvailable)"
						class="booking-actions-popup-button-with-chevron"
						:class="{
							'--lock': !isFeatureEnabled,
							'--disabled': disabled || (status?.isDisabled && isCurrentSenderAvailable)
						}"
						buttonClass="ui-btn-shadow"
						:text="loc('BB_ACTIONS_POPUP_MESSAGE_BUTTON_SEND')"
						:size="buttonSize.EXTRA_SMALL"
						:color="buttonColor.LIGHT"
						:round="true"
						ref="button"
						@click="openMenu"
					>
						<Icon v-if="isFeatureEnabled" :name="iconSet.CHEVRON_DOWN"/>
						<Icon v-else :name="iconSet.LOCK"/>
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
