import { limit } from 'booking.lib.limit';
import { Event } from 'main.core';
import { MenuManager, Menu } from 'main.popup';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';

import { Model } from 'booking.const';
import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';

import './confirmation-menu.css';
import { mapGetters } from 'ui.vue3.vuex';

export const ConfirmationMenu = {
	emits: ['popupShown', 'popupClosed'],
	name: 'ConfirmationMenu',
	props: {
		bookingId: {
			type: Number,
			required: true,
		},
	},
	data(): { menuPopup: Menu | null }
	{
		return {
			IconSet,
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			menuPopup: null,
		};
	},
	computed: {
		...mapGetters({
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		popupId(): string
		{
			return `booking-confirmation-menu-${this.bookingId}`;
		},
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
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
			void bookingService.update({
				id: this.booking.id,
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
				this.booking.isConfirmed
					? this.loc('BB_ACTIONS_POPUP_CONFIRMATION_MENU_NOT_CONFIRMED')
					: this.loc('BB_ACTIONS_POPUP_CONFIRMATION_MENU_CONFIRMED')
			);

			return [
				{
					text,
					onclick: () => {
						this.updateConfirmStatus(!this.booking.isConfirmed);
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
			data-element="booking-menu-confirmation-button"
			:data-booking-id="bookingId"
			class="booking-actions-popup-button-with-chevron"
			:class="{'--lock': !isFeatureEnabled}"
			buttonClass="ui-btn-shadow"
			:text="loc('BB_ACTIONS_POPUP_CONFIRMATION_BTN_LABEL')"
			:size="ButtonSize.EXTRA_SMALL"
			:color="ButtonColor.LIGHT"
			:round="true"
			ref="button"
			@click="openMenu"
		>
			<Icon v-if="isFeatureEnabled" :name="IconSet.CHEVRON_DOWN"/>
			<Icon v-else :name="IconSet.LOCK"/>
		</Button>
	`,
};
