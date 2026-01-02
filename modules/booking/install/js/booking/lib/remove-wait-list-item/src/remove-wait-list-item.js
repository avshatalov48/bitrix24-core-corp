import { Loc } from 'main.core';
import 'ui.notification';

import { Model } from 'booking.const';
import { Core } from 'booking.core';
import { waitListService } from 'booking.provider.service.wait-list-service';

const secondsToDelete = 5;

export class RemoveWaitListItem
{
	#balloon: BX.UI.Notification.Balloon;
	#waitListItemId: number;
	#secondsLeft: number;
	#cancelingTheDeletion: boolean;
	#interval: number;

	constructor(waitListItemId: number)
	{
		this.#waitListItemId = waitListItemId;
		this.#removeWaitListItem();
	}

	#removeWaitListItem(): void
	{
		this.#secondsLeft = secondsToDelete;

		this.#balloon = BX.UI.Notification.Center.notify({
			id: `booking-notify-remove-waitlist-item-${this.#waitListItemId}`,
			content: this.#getBalloonTitle(),
			actions: [
				{
					title: Loc.getMessage('BB_BOOKING_REMOVE_BALLOON_CANCEL'),
					events: {
						mouseup: this.#cancelDeletion,
					},
				},
			],
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

		void Core.getStore().dispatch(`${Model.Interface}/addDeletingWaitListItemId`, this.#waitListItemId);
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

		void Core.getStore().dispatch(`${Model.Interface}/removeDeletingWaitListItemId`, this.#waitListItemId);
	};

	#onBalloonClose = (): void => {
		clearInterval(this.#interval);

		if (this.#cancelingTheDeletion)
		{
			this.#cancelingTheDeletion = false;

			return;
		}

		void waitListService.delete(this.#waitListItemId);
	};
}
