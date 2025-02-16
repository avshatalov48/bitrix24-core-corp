import type { CountersModel } from 'booking.model.counters';
import type { MainPageGetCountersResponse, MoneyStatisticsDto } from './types';

export class CountersExtractor
{
	#response: MainPageGetCountersResponse;

	constructor(response: MainPageGetCountersResponse)
	{
		this.#response = response;
	}

	getCounters(): CountersModel
	{
		return this.#response.counters;
	}

	getTotalClients(): number
	{
		return this.#response.clientStatistics.total;
	}

	getTotalNewClientsToday(): number
	{
		return this.#response.clientStatistics.totalToday;
	}

	getMoneyStatistics(): MoneyStatisticsDto
	{
		return this.#response.moneyStatistics;
	}
}
