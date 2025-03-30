import { Event } from 'main.core';
import { MenuManager, Menu } from 'main.popup';
import { BIcon as Icon } from 'ui.icon-set.api.vue';
import { NotificationChannel } from 'booking.const';
import { ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';

import './channel-menu.css';

export const ChannelMenu = {
	name: 'ChannelMenu',
	emits: ['popupShown', 'popupClosed', 'updateChannel'],
	props: {
		currentChannel: {
			type: String,
			default: null,
		},
	},
	data(): { menuPopup: Menu | null }
	{
		return {
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			menuPopup: null,
			channel: this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA'),
		};
	},
	computed: {
		popupId(): string
		{
			return 'booking-choose-channel-menu';
		},
		notificationChannelOptions(): { label: string, value: Messenger }[]
		{
			return [
				{
					label: this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA'),
					value: NotificationChannel.WhatsApp,
				},
				{
					label: this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS'),
					value: NotificationChannel.Sms,
				},
			];
		},
	},
	mounted(): void
	{
		this.channel = this.notificationChannelOptions
			.find(({ value }) => value === this.currentChannel)?.label
			|| this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA');
	},
	unmounted(): void
	{
		if (this.menuPopup)
		{
			this.destroy();
		}
	},
	methods: {
		openMenu(): void
		{
			if (this.menuPopup?.popupWindow?.isShown())
			{
				this.destroy();

				return;
			}

			const menuButton = this.$refs['menu-button'];
			this.menuPopup = MenuManager.create(
				this.popupId,
				menuButton,
				this.getMenuItems(),
				{
					className: 'booking-choose-channel-menu',
					closeByEsc: true,
					autoHide: true,
					offsetTop: 0,
					offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
					angle: true,
					cacheable: true,
					targetContainer: document.querySelector('div.resource-creation-wizard__wrapper'),
					events: {
						onClose: () => this.destroy(),
						onDestroy: () => this.destroy(),
					},
				},
			);
			this.menuPopup.show();
			this.bindScrollEvent();
			this.$emit('popupShown');
		},
		getMenuItems(): Array
		{
			return [
				{
					text: this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA'),
					onclick: () => {
						this.channel = this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA');
						this.$emit('updateChannel', NotificationChannel.WhatsApp);
						this.destroy();
					},
				},
				{
					text: this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS'),
					onclick: () => {
						this.channel = this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS');
						this.$emit('updateChannel', NotificationChannel.Sms);
						this.destroy();
					},
				},
			];
		},
		destroy(): void
		{
			MenuManager.destroy(this.popupId);
			this.unbindScrollEvent();
			this.$emit('popupClosed');
		},
		bindScrollEvent(): void
		{
			Event.bind(document, 'scroll', this.adjustPosition, { capture: true });
		},
		unbindScrollEvent(): void
		{
			Event.unbind(document, 'scroll', this.adjustPosition, { capture: true });
		},
		adjustPosition(): void
		{
			this.menuPopup?.popupWindow?.adjustPosition();
		},
	},
	components: {
		Icon,
	},
	template: `
		<span
			class="booking-resource-creation-wizard-channel-menu-button"
			ref="menu-button"
			@click="openMenu"
		>
			{{ channel }}
		</span>
	`,
};
