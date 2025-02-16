import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { countersService } from 'booking.provider.service.counters-service';
import { bookingService } from 'booking.provider.service.booking-service';
import { calendarService } from 'booking.provider.service.calendar-service';

import { BasePullHandler } from './base-pull-handler';

export class CountersPullHandler extends BasePullHandler
{
	getDelayedMap(): { [command: string]: Function }
	{
		return {
			countersUpdated: this.#handleCountersUpdated.bind(this),
		};
	}

	async #handleCountersUpdated(params: { entityId: number }): void
	{
		await countersService.fetchData();
		await bookingService.getById(params.entityId);

		const isFilterMode = this.#isFilterMode();

		if (!isFilterMode)
		{
			const viewDateTs = this.#getViewDateTs();
			const forcePull = true;
			await calendarService.loadCounterMarks(viewDateTs, forcePull);
		}
	}

	#isFilterMode(): boolean
	{
		return Core.getStore().getters[`${Model.Interface}/isFilterMode`];
	}

	#getViewDateTs(): number
	{
		return Core.getStore().getters[`${Model.Interface}/viewDateTs`];
	}
}
