import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { calendarService } from 'booking.provider.service.calendar-service';

const DAY_MS = 1000 * 60 * 60 * 24;

class FilterResultNavigator
{
	getOptimalFilterDateTs(inFuture = false): Promise<number>
	{
		const $store = Core.getStore();

		const count = $store.getters[`${Model.Filter}/datesCount`]?.count ?? 0;
		const maxDate = $store.getters[`${Model.Filter}/datesCount`]?.maxDate;
		const minDate = $store.getters[`${Model.Filter}/datesCount`]?.minDate;
		const selectedDateTs: number = $store.getters[`${Model.Interface}/selectedDateTs`];

		if (count === 0)
		{
			return selectedDateTs;
		}

		if (maxDate !== null && (this.#getDateTs(maxDate) > selectedDateTs || inFuture))
		{
			return this.getNextFilterDateTs((inFuture ? this.#getTodayTs() : selectedDateTs) - DAY_MS, inFuture);
		}

		if (minDate !== null && this.#getDateTs(minDate) < selectedDateTs)
		{
			return this.getPreviousFilterDateTs(selectedDateTs + DAY_MS);
		}

		return selectedDateTs;
	}

	async getPreviousFilterDateTs(startingDateTs: ?number): Promise<number | null>
	{
		const $store = Core.getStore();

		const minDate = $store.getters[`${Model.Filter}/datesCount`]?.minDate;
		if (minDate === null)
		{
			return null;
		}

		const minDateTs = this.#getDateTs(minDate);
		const selectedDateTs = startingDateTs || $store.getters[`${Model.Interface}/selectedDateTs`];

		if (selectedDateTs <= minDateTs)
		{
			return minDateTs;
		}

		const maxDateTs = this.#getDateTs($store.getters[`${Model.Filter}/datesCount`]?.maxDate);
		if (selectedDateTs > maxDateTs)
		{
			return maxDateTs;
		}

		const previousFilterDate = this.#findPreviousDate(selectedDateTs);
		if (previousFilterDate)
		{
			return previousFilterDate;
		}

		const previousDate = new Date(selectedDateTs - DAY_MS);

		await this.#loadNextFilterDates(previousDate.getTime(), minDateTs);

		return this.#findPreviousDate(selectedDateTs);
	}

	async getNextFilterDateTs(startingDateTs: ?number, inFuture = false): Promise<number | null>
	{
		const $store = Core.getStore();

		const maxDate = $store.getters[`${Model.Filter}/datesCount`]?.maxDate;
		if (maxDate === null)
		{
			return null;
		}

		const selectedDateTs: number = startingDateTs || $store.getters[`${Model.Interface}/selectedDateTs`];

		const maxDateTs = this.#getDateTs(maxDate);
		if (selectedDateTs >= maxDateTs)
		{
			return maxDateTs;
		}

		const minDateTs = this.#getDateTs($store.getters[`${Model.Filter}/datesCount`]?.minDate);
		if (selectedDateTs < minDateTs)
		{
			return minDateTs;
		}

		const nextFilterDate = this.#findNextDate(selectedDateTs, inFuture);
		if (nextFilterDate)
		{
			return nextFilterDate;
		}

		const nextDate = new Date(selectedDateTs + DAY_MS); // + (inFuture ? 0 : DAY_MS));

		await this.#loadNextFilterDates(nextDate.getTime(), maxDateTs);

		return this.#findNextDate(selectedDateTs);
	}

	#getTodayTs(): number
	{
		const today = new Date();

		return Math.trunc(today.getTime() / 1000) * 1000;
	}

	#getDateTs(date: Date | string | number): number
	{
		const d = new Date(date);
		d.setHours(0, 0, 0, 0);

		return d.getTime();
	}

	async #loadNextFilterDates(selectedDateTs: number, limitDateTs: number): Promise<void>
	{
		const filterFields = Core.getStore().getters[`${Model.Filter}/fields`];

		await calendarService.loadNextFilterMarks(filterFields, selectedDateTs, limitDateTs);
	}

	#findPreviousDate(selectedDateTs: number): number | undefined
	{
		return Core.getStore().state[Model.Filter].filterDates.findLast((dateTs) => dateTs < selectedDateTs);
	}

	#findNextDate(viewDateTs: number, inFuture = false): number | undefined
	{
		const filterDates = [...Core.getStore().state[Model.Filter].filterDates].sort();

		if (inFuture)
		{
			return filterDates.find((dateTs) => dateTs >= viewDateTs);
		}

		return filterDates.find((dateTs) => dateTs > viewDateTs);
	}
}

export const filterResultNavigator = new FilterResultNavigator();
