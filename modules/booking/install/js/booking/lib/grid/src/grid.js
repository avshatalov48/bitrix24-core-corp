import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { Duration } from 'booking.lib.duration';

class Grid
{
	calculateLeft(resourceId: number): number
	{
		const cellWidth = 280 * this.#zoom;
		const indexOfResource = this.#resourcesIds.indexOf(resourceId);

		return indexOfResource * cellWidth;
	}

	calculateTop(fromTs: number): number
	{
		const hourHeight = 50 * this.#zoom;

		const from = new Date(Math.max(this.#selectedDateTs, fromTs + this.#offset));
		const bookingMinutes = from.getHours() * 60 + from.getMinutes();
		const fromMinutes = this.#fromHour * 60;

		return (bookingMinutes - fromMinutes) * (hourHeight / 60);
	}

	calculateHeight(fromTs: number, toTs: number): number
	{
		const hourHeight = 50 * this.#zoom;
		const minHeight = hourHeight / 4;

		const from = Math.max(this.#selectedDateTs, fromTs + this.#offset);
		const to = Math.min(new Date(this.#selectedDateTs).setHours(24), toTs + this.#offset);

		return Math.max((to - from) / Duration.getUnitDurations().H * hourHeight, minHeight);
	}

	calculateRealHeight(fromTs: number, toTs: number): number
	{
		const hourHeight = 50 * this.#zoom;
		const minHeight = hourHeight / 4;

		const minTs = new Date(this.#selectedDateTs).setHours(this.#offHoursExpanded ? 0 : this.#fromHour);
		const maxTs = new Date(this.#selectedDateTs).setHours(this.#offHoursExpanded ? 24 : this.#toHour);
		const from = Math.max(minTs, fromTs + this.#offset);
		const to = Math.min(maxTs, toTs + this.#offset);

		return Math.max((to - from) / Duration.getUnitDurations().H * hourHeight, minHeight);
	}

	get #selectedDateTs(): number
	{
		return Core.getStore().getters[`${Model.Interface}/selectedDateTs`] + this.#offset;
	}

	get #offset(): number
	{
		return Core.getStore().getters[`${Model.Interface}/offset`];
	}

	get #zoom(): number
	{
		return Core.getStore().getters[`${Model.Interface}/zoom`];
	}

	get #resourcesIds(): number[]
	{
		return Core.getStore().getters[`${Model.Interface}/resourcesIds`];
	}

	get #fromHour(): number
	{
		return Core.getStore().getters[`${Model.Interface}/fromHour`];
	}

	get #toHour(): number
	{
		return Core.getStore().getters[`${Model.Interface}/toHour`];
	}

	get #offHoursExpanded(): boolean
	{
		return Core.getStore().getters[`${Model.Interface}/offHoursExpanded`];
	}
}

export const grid = new Grid();
