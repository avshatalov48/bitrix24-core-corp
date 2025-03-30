import { Loc } from 'main.core';
import 'ui.notification';

import { Model } from 'booking.const';
import { Core } from 'booking.core';
import { bookingService } from 'booking.provider.service.booking-service';

const secondsToDelete = 5;

export class RemoveBooking
{
	#balloon: BX.UI.Notification.Balloon;
	#bookingId: number;
	#secondsLeft: number;
	#cancelingTheDeletion: boolean;
	#interval: number;

	constructor(bookingId: number)
	{
		this.#bookingId = bookingId;

		this.#removeBooking();
	}

	#removeBooking(): void
	{
		this.#secondsLeft = secondsToDelete;

		this.#balloon = BX.UI.Notification.Center.notify({
			id: `booking-notify-remove-${this.#bookingId}`,
			content: this.#getBalloonTitle(),
			actions: [{
				title: Loc.getMessage('BB_BOOKING_REMOVE_BALLOON_CANCEL'),
				events: {
					mouseup: this.#cancelDeletion,
				},
			}],
			events: {
				onClose: this.#onBalloonClose,
			},
		});

		this.#startDeletion();
	}

	#startDeletion(): void
	{
		this.#interval = setInterval(() => {
			this.#secondsLeft--;

			this.#balloon.update({ content: this.#getBalloonTitle() });

			if (this.#secondsLeft <= 0)
			{
				this.#balloon.close();
			}
		}, 1000);

		void Core.getStore().dispatch(`${Model.Interface}/addDeletingBooking`, this.#bookingId);
	}

	#getBalloonTitle(): string
	{
		return Loc.getMessage('BB_BOOKING_REMOVE_BALLOON_TEXT', {
			'#countdown#': this.#secondsLeft,
		});
	}

	#cancelDeletion = (): void => {
		this.#cancelingTheDeletion = true;
		this.#balloon.close();

		void Core.getStore().dispatch(`${Model.Interface}/removeDeletingBooking`, this.#bookingId);
	};

	#onBalloonClose = (): void => {
		clearInterval(this.#interval);

		if (this.#cancelingTheDeletion)
		{
			this.#cancelingTheDeletion = false;

			return;
		}

		void bookingService.delete(this.#bookingId);
	};
}
