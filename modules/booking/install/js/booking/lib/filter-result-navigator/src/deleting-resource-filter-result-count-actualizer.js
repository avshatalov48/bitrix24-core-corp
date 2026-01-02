import { Event, Type } from 'main.core';

import { Core } from 'booking.core';
import { EventName, Model } from 'booking.const';
import { RemoveResource } from 'booking.lib.remove-resource';
import { calendarService } from 'booking.provider.service.calendar-service';
import type { BookingModel } from 'booking.model.bookings';

class DeletingResourceFilterResultCountActualizer
{
	subscribe(): void
	{
		Event.EventEmitter.subscribe(EventName.CreateBookings, this.afterCreateBooking);
		Event.EventEmitter.subscribe(EventName.DeleteBooking, this.afterDeleteBooking);
		Event.EventEmitter.subscribe(EventName.UpdateBooking, this.afterUpdateBooking);
	}

	unsubscribe(): void
	{
		Event.EventEmitter.unsubscribe(EventName.CreateBookings, this.afterCreateBooking);
		Event.EventEmitter.unsubscribe(EventName.DeleteBooking, this.afterDeleteBooking);
		Event.EventEmitter.unsubscribe(EventName.UpdateBooking, this.afterUpdateBooking);
	}

	afterCreateBooking({ data }): void
	{
		const id = Core.getStore().getters[`${Model.Filter}/deletingResource`]?.id;
		const bookings = data.bookings || [];

		if (!Type.isArray(bookings) || bookings.every(({ resourcesIds }) => !resourcesIds.includes(id)))
		{
			return;
		}

		void updateFilterResultCounter();

		const selectedDateTs = Core.getStore().getters[`${Model.Interface}/selectedDateTs`];
		const resourceBookings = Core.getStore().getters[`${Model.Bookings}/getByDateAndResources`](
			selectedDateTs,
			[id],
		);
		if (resourceBookings.length === 1)
		{
			void updateFilterMarks();
		}
	}

	async afterDeleteBooking({ data }): void
	{
		const id = Core.getStore().getters[`${Model.Filter}/deletingResource`]?.id;
		const resourcesIds: number[] = data.booking.resourcesIds || [];

		if (!resourcesIds.includes(id))
		{
			return;
		}

		await updateFilterResultCounter();

		if (getResourceBookings(new Date(data.booking.dateFromTs)).length === 0)
		{
			void updateFilterMarks();
		}

		void tryRemoveResourceAgain(id);
	}

	async afterUpdateBooking({ data }): void
	{
		const oldResourcesIds = data.oldBooking.resourcesIds;
		const newResourcesIds = data.newBooking.resourcesIds;
		const id = Core.getStore().getters[`${Model.Filter}/deletingResource`]?.id;

		if (
			(oldResourcesIds.includes(id) && newResourcesIds.includes(id))
			|| (!oldResourcesIds.includes(id) && !newResourcesIds.includes(id))
		)
		{
			return;
		}

		await updateFilterResultCounter();

		if (getResourceBookings().length < 2)
		{
			void updateFilterMarks();
		}

		void tryRemoveResourceAgain(id);
	}
}

function getResourceBookings(dateTs: ?number): BookingModel[]
{
	const selectedDateTs = dateTs || Core.getStore().getters[`${Model.Interface}/selectedDateTs`];
	const resourceId = Core.getStore().getters[`${Model.Filter}/deletingResource`]?.id;

	return Core.getStore().getters[`${Model.Bookings}/getByDateAndResources`](selectedDateTs, [resourceId]);
}

async function updateFilterResultCounter(): Promise<void>
{
	const $store = Core.getStore();
	const fields = $store.getters[`${Model.Filter}/fields`];

	calendarService.clearDataCountCache();
	await calendarService.loadBookingsDateCount(fields, true);
}

async function updateFilterMarks(): void
{
	const $store = Core.getStore();
	const fields = $store.getters[`${Model.Filter}/fields`];

	await $store.dispatch(`${Model.Filter}/setFilteredMarks`, []);

	calendarService.clearFilterCache();
	await calendarService.loadFilterMarks(fields);
}

async function tryRemoveResourceAgain(resourceId: number): Promise<void>
{
	if (Core.getStore().getters[`${Model.Filter}/datesCount`]?.count > 0)
	{
		return;
	}

	await (new RemoveResource(resourceId)).runAfterMoveBookings();
}

export const deletingResourceFilterResultCountActualizer = new DeletingResourceFilterResultCountActualizer();
