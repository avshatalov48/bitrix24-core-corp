import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Model } from 'booking.const';
import { bookingService } from 'booking.provider.service.booking-service';
import './remove-btn.css';

const secondsToDelete = 5;

export const RemoveBtn = {
	name: 'BookingActionsPopupRemoveBtn',
	emits: ['close'],
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	data(): { secondsLeft: number }
	{
		return {
			secondsLeft: secondsToDelete,
		};
	},
	components: {
		Icon,
	},
	methods: {
		reset(intervalId: number, balloon: { close(): void }): void
		{
			clearInterval(intervalId);
			balloon.close();
			this.secondsLeft = secondsToDelete;
		},
		removeBooking(): void
		{
			this.$emit('close');

			const balloon = BX.UI.Notification.Center.notify({
				id: this.balloonId,
				content: this.balloonTitle,
				actions: [{
					title: this.balloonCancelText,
					events: {
						click: () => {
							this.reset(interval, balloon);
							void this.$store.dispatch(`${Model.Interface}/removeDeletingBooking`, this.bookingId);
						},
					},
				}],
			});

			void this.$store.dispatch(`${Model.Interface}/addDeletingBooking`, this.bookingId);

			const interval = setInterval(() => {
				this.secondsLeft--;

				if (this.secondsLeft >= 1)
				{
					balloon.update({ content: this.balloonTitle });
				}
				else
				{
					this.reset(interval, balloon);
					void bookingService.delete(this.bookingId);
				}
			}, 1000);
		},
	},
	computed: {
		balloonId(): string
		{
			return `booking-notify-${this.bookingId}`;
		},
		balloonCancelText(): string
		{
			return this.loc('BB_BOOKING_REMOVE_BALLOON_CANCEL');
		},
		balloonTitle(): string
		{
			return this.loc('BB_BOOKING_REMOVE_BALLOON_TEXT', { '#countdown#': this.secondsLeft });
		},
		removeIcon(): string
		{
			return IconSet.TRASH_BIN;
		},
		removeIconSize(): number
		{
			return 20;
		},
		removeIconColor(): string
		{
			return 'var(--ui-color-palette-gray-20)';
		},
	},
	template: `
		<div
			class="booking-actions-popup__item-remove-btn-icon --end"
			data-element="booking-menu-remove-button"
			:data-booking-id="bookingId"
			@click="removeBooking"
		>
			<div class="booking-actions-popup__item-overbooking-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_REMOVE') }}
			</div>
			<Icon :name="removeIcon" :size="removeIconSize" :color="removeIconColor"/>
		</div>
	`,
};
