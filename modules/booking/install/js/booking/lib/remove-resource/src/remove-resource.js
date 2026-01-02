import { Loc } from 'main.core';
import 'ui.notification';

import { Model } from 'booking.const';
import { Core } from 'booking.core';
import { resourceService } from 'booking.provider.service.resources-service';

import { RemoveConfirmation } from './remove-confirmation';

import './remove-resource.css';

const secondsToDelete = 5;

export class RemoveResource
{
	#resourceId: number;
	#shouldRemoveFutureBookings: boolean = false;
	#futureBookingIds: number[] = [];

	#isDeletionCancelled: boolean;
	#balloon: BX.UI.Notification.Balloon;
	#secondsLeft: number;
	#countdownInterval: number;

	constructor(resourceId: number)
	{
		this.#resourceId = resourceId;
	}

	async run(): Promise<void>
	{
		await this.#cancelRemovingResource();

		const hasFutureBookings = await resourceService.hasFutureBookings(this.#resourceId);

		if (hasFutureBookings)
		{
			const shouldMoveFutureBookings = await RemoveConfirmation.confirmMoveFutureBooking();
			if (shouldMoveFutureBookings)
			{
				await Core.getStore().dispatch(`${Model.Filter}/setDeletingResourceFilter`, {
					resourceId: this.#resourceId,
				});
			}
			else
			{
				this.#shouldRemoveFutureBookings = true;
				this.#runCancellableDeletion();
			}

			return;
		}

		const isDeletionConfirmed = await RemoveConfirmation.confirmDelete();
		if (isDeletionConfirmed)
		{
			this.#runCancellableDeletion();
		}
	}

	async runAfterMoveBookings(): Promise<void>
	{
		await this.#cancelRemovingResource();

		const hasFutureBookings = await resourceService.hasFutureBookings(this.#resourceId);
		if (hasFutureBookings)
		{
			return;
		}

		const resource = Core.getStore().getters[`${Model.Resources}/getById`](this.#resourceId);
		const isDeletionConfirmed = await RemoveConfirmation.confirmAfterMoveFutureBooking(resource);

		if (isDeletionConfirmed)
		{
			this.#runCancellableDeletion();
		}
	}

	#runCancellableDeletion(): void
	{
		this.#secondsLeft = secondsToDelete;

		this.#balloon = BX.UI.Notification.Center.notify({
			id: `booking-notify-remove-resource-${this.#resourceId}`,
			content: this.#getBalloonTitle(),
			actions: [
				{
					title: Loc.getMessage('BOOKING_RESOURCE_REMOVE_BALLOON_CANCEL'),
					events: {
						mouseup: this.#cancelDeletion,
					},
				},
			],
			events: {
				onClose: this.#onBalloonClose,
			},
		});

		this.#runCountdown();
	}

	#runCountdown(): void
	{
		void Core.getStore().dispatch(`${Model.Interface}/addDeletingResource`, this.#resourceId);

		if (this.#shouldRemoveFutureBookings)
		{
			const futureBookings = Core.getStore().getters[`${Model.Bookings}/getFutureByResourceId`](this.#resourceId);
			this.#futureBookingIds = futureBookings.map((booking) => booking.id);

			if (this.#futureBookingIds.length > 0)
			{
				void Core.getStore().dispatch(`${Model.Interface}/addDeletingBooking`, this.#futureBookingIds);
			}
		}

		this.#countdownInterval = setInterval(() => {
			this.#secondsLeft--;

			this.#balloon.update({ content: this.#getBalloonTitle() });

			if (this.#secondsLeft <= 0)
			{
				this.#balloon.close();
			}
		}, 1000);
	}

	#getBalloonTitle(): string
	{
		return Loc.getMessage('BOOKING_RESOURCE_REMOVE_BALLOON_TEXT', {
			'#COUNTDOWN#': this.#secondsLeft,
		});
	}

	#cancelDeletion = (): void => {
		this.#isDeletionCancelled = true;
		this.#balloon.close();

		void Core.getStore().dispatch(`${Model.Interface}/removeDeletingResource`, this.#resourceId);

		if (this.#futureBookingIds.length > 0)
		{
			void Core.getStore().dispatch(`${Model.Interface}/removeDeletingBooking`, this.#futureBookingIds);
		}
	};

	#onBalloonClose = (): void => {
		clearInterval(this.#countdownInterval);

		if (this.#isDeletionCancelled)
		{
			this.#isDeletionCancelled = false;

			return;
		}

		void resourceService.delete(this.#resourceId, this.#shouldRemoveFutureBookings);
	};

	async #cancelRemovingResource(): Promise<void>
	{
		const $store = Core.getStore();

		if ($store.getters[`${Model.Filter}/isDeletingResourceFilterMode`])
		{
			await Promise.all([
				$store.dispatch(`${Model.Filter}/clearFilter`),
				$store.dispatch(`${Model.Interface}/setPinnedResourceIds`, []),
			]);
		}
	}
}
