import { Core } from 'booking.core';
import { BusySlot, DateFormat, Model } from 'booking.const';
import { SlotRanges } from 'booking.lib.slot-ranges';
import { resourceDialogService } from 'booking.provider.service.resource-dialog-service';
import { resourcesDateCache } from 'booking.lib.resources-date-cache';
import type { BookingModel, OverbookingMap } from 'booking.model.bookings';
import type { ResourceModel, SlotRange } from 'booking.model.resources';
import type { Intersections } from 'booking.model.interface';

import { getIntersectionBusySlots } from './lib';
import type { BusySlotDto, Range } from './types';

export type { BusySlotDto };

const minBookingViewMs = 15 * 60 * 1000;

class BusySlots
{
	#busySlots: BusySlotDto[] = [];

	#getBookings(): BookingModel[]
	{
		return Core.getStore().getters[`${Model.Bookings}/getByDateAndResources`](
			this.#selectedDateTs,
			this.#resourcesIds,
		);
	}

	#getIntersectingBookings(resourcesIds: number[]): BookingModel[]
	{
		return Core.getStore().getters[`${Model.Bookings}/getByDateAndResources`](
			this.#selectedDateTs,
			resourcesIds,
		);
	}

	get #selectedWeekDay(): string
	{
		return DateFormat.WeekDays[new Date(this.#selectedDateTs + this.#offset).getDay()];
	}

	get #selectedDateTs(): number
	{
		return Core.getStore().getters[`${Model.Interface}/selectedDateTs`];
	}

	get #offset(): number
	{
		return Core.getStore().getters[`${Model.Interface}/offset`];
	}

	get #timezone(): number
	{
		return Core.getStore().getters[`${Model.Interface}/timezone`];
	}

	get #resourcesIds(): number[]
	{
		return Core.getStore().getters[`${Model.Interface}/resourcesIds`];
	}

	get #intersections(): Intersections
	{
		if (this.#draggedBooking)
		{
			const draggedIds = [...this.#draggedBooking.resourcesIds];
			const notDraggedIds = draggedIds.filter((id: number) => id !== this.#draggedBookingResourceId);

			return {
				...[...this.#resourcesIds].reduce((acc: Intersections, id: number) => ({
					...acc,
					[id]: notDraggedIds,
				}), {}),
				...notDraggedIds.reduce((acc: Intersections, id: number) => ({
					...acc,
					[id]: draggedIds,
				}), {}),
			};
		}

		return Core.getStore().getters[`${Model.Interface}/intersections`];
	}

	get #draggedBooking(): BookingModel | null
	{
		return Core.getStore().getters[`${Model.Bookings}/getById`](this.#draggedBookingId) ?? null;
	}

	get #draggedBookingId(): number
	{
		return (
			Core.getStore().getters[`${Model.Interface}/draggedBookingId`]
			|| Core.getStore().getters[`${Model.Interface}/resizedBookingId`]
		);
	}

	get #draggedBookingResourceId(): number
	{
		return Core.getStore().getters[`${Model.Interface}/draggedBookingResourceId`];
	}

	async loadBusySlots(): Promise<void>
	{
		await this.#loadIntersections();

		void Core.getStore().dispatch(`${Model.Interface}/clearDisabledBusySlots`);
		void Core.getStore().dispatch(`${Model.Interface}/clearBusySlots`);

		const resourcesWithIntersections = Object.keys(this.#intersections)
			.flatMap((key: string) => {
				const resourceId = Number(key);

				if (resourceId > 0)
				{
					return resourceId;
				}

				return this.#resourcesIds;
			})
		;

		this.#busySlots = [
			...this.#resourcesIds.flatMap((resourceId) => this.#calculateOffHoursBusySlots(resourceId)),
			...resourcesWithIntersections.flatMap((resourceId) => this.#calculateIntersectionBusySlots(resourceId)),
		];

		return Core.getStore().dispatch(`${Model.Interface}/upsertBusySlotMany`, this.#busySlots);
	}

	async #loadIntersections(): Promise<void>
	{
		const selectedResourceIds = [...new Set(Object.values(this.#intersections).flat())];

		const dateTs = this.#selectedDateTs / 1000;
		const loadedResourcesIds = new Set(resourcesDateCache.getIdsByDateTs(dateTs));
		const idsToLoad = selectedResourceIds.filter((id: number) => !loadedResourcesIds.has(id));

		await resourceDialogService.loadByIds(idsToLoad, dateTs);
	}

	#calculateOffHoursBusySlots(resourceId: number): BusySlotDto[]
	{
		const resource: ResourceModel = this.#getResource(resourceId);
		if (resource.slotRanges.length === 0)
		{
			return [];
		}

		const bookingRanges = this.#getBookings()
			.filter((booking: BookingModel) => booking.resourcesIds.includes(resourceId))
			.map((booking: BookingModel) => this.#calculateMinutesRange(booking))
		;

		const slotRanges = SlotRanges
			.applyTimezone(resource.slotRanges, this.#selectedDateTs, this.#timezone)
			.filter((slotRange: SlotRange) => slotRange.weekDays.includes(this.#selectedWeekDay))
		;

		const freeRanges = this.filterSlotRanges([...slotRanges, ...bookingRanges]);

		const busyRanges = [0, ...freeRanges.flatMap(({ from, to }) => [from, to]), 24 * 60]
			.reduce((acc, minutes, index) => {
				const chunkIndex = Math.floor(index / 2);

				acc[chunkIndex] ??= [];
				acc[chunkIndex].push(minutes);

				return acc;
			}, [])
		;

		return busyRanges.filter(([from, to]) => to - from > 0).map(([from, to]): BusySlotDto => {
			const fromTs = new Date(this.#selectedDateTs).setMinutes(from);
			const toTs = new Date(this.#selectedDateTs).setMinutes(to);
			const id = `${resourceId}-${fromTs}-${toTs}`;
			const type = BusySlot.OffHours;

			return { id, fromTs, toTs, resourceId, type };
		});
	}

	#calculateIntersectionBusySlots(resourceId: number): BusySlotDto[]
	{
		const resource: ResourceModel = this.#getResource(resourceId);
		if (resource.slotRanges.length === 0)
		{
			return [];
		}

		const intersectingResourcesIds = [
			...(this.#intersections[0] ?? []),
			...(this.#intersections[resourceId] ?? []),
		];

		const bookingRanges = this.#getBookings()
			.filter((booking: BookingModel) => booking.resourcesIds.includes(resourceId))
			.map((booking: BookingModel) => this.#calculateMinutesRange(booking));

		const intersectingBookings = this.#getIntersectingBookings(intersectingResourcesIds)
			.filter((booking: BookingModel) => {
				const notCurrentResource = !booking.resourcesIds.includes(resourceId);
				const isNotDragged = booking.id !== this.#draggedBookingId;

				return notCurrentResource && isNotDragged;
			});

		const intersectingBookingRanges = intersectingBookings
			.map((booking: BookingModel) => this.#calculateMinutesRange(booking))
			.filter((ir) => {
				return bookingRanges
					.filter((br) => br.from <= ir.from && ir.to <= br.to)
					.length < 2;
			});

		if (intersectingBookingRanges.length === 0)
		{
			return [];
		}

		const busyRanges = intersectingBookingRanges.flatMap((intersectingRange) => {
			return this.#subtractRanges(intersectingRange, bookingRanges);
		});

		return getIntersectionBusySlots({
			resourceId,
			busyRanges,
			overbookingMap: this.#getOverbookingMap(),
			selectedDateTs: this.#selectedDateTs,
			intersectingBookings,
			intersectingResourcesIds,
		});
	}

	#calculateMinutesRange(booking: BookingModel): Range
	{
		const date = new Date(this.#selectedDateTs);
		const dateFromTs = Math.max(date.getTime(), booking.dateFromTs) + this.#offset;
		const bookingViewToTs = Math.max(booking.dateToTs, booking.dateFromTs + minBookingViewMs);
		const dateToTs = Math.min(date.setDate(date.getDate() + 1), bookingViewToTs) + this.#offset;

		const dateFrom = new Date(dateFromTs);
		const dateTo = new Date(dateToTs);
		const to = dateTo.getHours() * 60 + dateTo.getMinutes();

		return {
			from: dateFrom.getHours() * 60 + dateFrom.getMinutes(),
			to: to === 0 ? 60 * 24 : to,
			id: booking.id,
		};
	}

	#subtractRanges(range, bookingRanges): Range[]
	{
		let remainingRanges = [{ ...range }];

		bookingRanges.forEach((bookingRange) => {
			remainingRanges = remainingRanges.flatMap((remainingRange) => {
				if (this.#rangesOverlap(remainingRange, bookingRange))
				{
					const parts = [];
					if (remainingRange.from < bookingRange.from)
					{
						parts.push({
							from: remainingRange.from,
							to: bookingRange.from,
							id: remainingRange.id,
						});
					}

					if (remainingRange.to > bookingRange.to)
					{
						parts.push({
							from: bookingRange.to,
							to: remainingRange.to,
							id: remainingRange.id,
						});
					}

					if (parts.length > 0)
					{
						return parts;
					}
				}

				return [remainingRange];
			});
		});

		return remainingRanges;
	}

	#rangesOverlap(range1, range2): boolean
	{
		return range1.from < range2.to && range2.from < range1.to;
	}

	filterSlotRanges(slotRanges: { from: number, to: number }[]): { from: number, to: number }[]
	{
		return slotRanges
			.map(({ from, to }) => ({ from, to }))
			.sort((a, b) => a.from - b.from)
			.reduce((acc, { from, to }) => {
				const last = acc.length - 1;
				if (acc[last] && acc[last].to >= from)
				{
					if (acc[last].to <= to)
					{
						acc[last].to = to;
					}
				}
				else
				{
					acc.push({ from, to });
				}

				return acc;
			}, [])
			.filter(({ from, to }) => to - from > 0);
	}

	#getResource(resourceId: number): ResourceModel
	{
		return Core.getStore().getters[`${Model.Resources}/getById`](resourceId);
	}

	#getOverbookingMap(): OverbookingMap
	{
		return Core.getStore().getters[`${Model.Bookings}/overbookingMap`];
	}
}

export const busySlots = new BusySlots();
