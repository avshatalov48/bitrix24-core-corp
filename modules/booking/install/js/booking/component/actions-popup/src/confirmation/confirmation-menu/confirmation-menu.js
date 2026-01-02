import { Event } from 'main.core';
import { MenuManager, Menu } from 'main.popup';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';

import { Model } from 'booking.const';
import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { limit } from 'booking.lib.limit';

import './confirmation-menu.css';

type ConfirmationMenuData = {
	iconSet: { [string]: string },
	buttonSize: { [string]: string },
	buttonColor: { [string]: string },
	buttonIcon: { [string]: string },
	menuPopup: Menu | null,
}

export type UpdateConfirmationStatusPayload = {
	id: number | string;
	isConfirmed: boolean;
}

export const ConfirmationMenu = {
	name: 'ConfirmationMenu',
	emits: ['popupShown', 'popupClosed', 'updateConfirmationStatus'],
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		isConfirmed: {
			type: Boolean,
			required: true,
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
	setup(): ConfirmationMenuData
	{
		const iconSet = IconSet;
		const buttonSize = ButtonSize;
		const buttonColor = ButtonColor;
		const buttonIcon = ButtonIcon;
		const menuPopup: Menu | null = null;

		return {
			iconSet,
			buttonSize,
			buttonColor,
			buttonIcon,
			menuPopup,
		};
	},
	computed: {
		...mapGetters({
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		popupId(): string
		{
			return `booking-confirmation-menu-${this.id}`;
		},
	},
	unmounted(): void
	{
		if (this.menuPopup)
		{
			this.destroy();
		}
	},
	methods: {
		updateConfirmStatus(isConfirmed: boolean): void
		{
			this.$emit('updateConfirmationStatus', {
				id: this.id,
				isConfirmed,
			});
		},
		openMenu(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			if (this.disabled)
			{
				return;
			}

			if (this.menuPopup?.popupWindow?.isShown())
			{
				this.destroy();

				return;
			}

			const menuButton = this.$refs.button.$el;
			this.menuPopup = MenuManager.create(
				this.popupId,
				menuButton,
				this.getMenuItems(),
				{
					className: 'booking-confirmation-menu-popup',
					closeByEsc: true,
					autoHide: true,
					offsetTop: 0,
					offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
					angle: true,
					cacheable: true,
					events: {
						onClose: () => this.destroy(),
						onDestroy: () => this.unbindScrollEvent(),
					},
				},
			);
			this.menuPopup.show();
			this.bindScrollEvent();
			this.$emit('popupShown');
		},
		getMenuItems(): Array
		{
			const text = (
				this.isConfirmed
					? this.loc('BB_ACTIONS_POPUP_CONFIRMATION_MENU_NOT_CONFIRMED')
					: this.loc('BB_ACTIONS_POPUP_CONFIRMATION_MENU_CONFIRMED')
			);

			return [
				{
					text,
					onclick: () => {
						this.updateConfirmStatus(!this.isConfirmed);
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
		Button,
	},
	template: `
		<Button
			:data-booking-id="dataId"
			:data-element="dataElementPrefix + '-menu-confirmation-button'"
			class="booking-actions-popup-button-with-chevron"
			:class="{'--lock': !isFeatureEnabled || disabled}"
			buttonClass="ui-btn-shadow"
			:disabled="disabled || !isFeatureEnabled"
			:text="loc('BB_ACTIONS_POPUP_CONFIRMATION_BTN_LABEL')"
			:size="buttonSize.EXTRA_SMALL"
			:color="buttonColor.LIGHT"
			:round="true"
			ref="button"
			@click="openMenu"
		>
			<Icon v-if="isFeatureEnabled" :name="iconSet.CHEVRON_DOWN"/>
			<Icon v-else :name="iconSet.LOCK"/>
		</Button>
	`,
};
