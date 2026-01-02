import type { BookingDto } from 'booking.provider.service.booking-service';
import type { CountersModel } from 'booking.model.counters';
import type { ClientModel } from 'booking.model.clients';
import type { ResourceDto } from 'booking.provider.service.resources-service';
import type { ResourceTypeDto } from 'booking.provider.service.resources-type-service';
import type { WaitListItemDto } from 'booking.provider.service.wait-list-service';
import type { FormsMenuModel } from 'booking.model.forms-menu';

export type MainPageGetResponse = {
	favorites: {
		managerId: number,
		resources: ResourceDto[],
	},
	bookings: BookingDto[],
	resourceTypes: ResourceTypeDto[],
	waitListItems: WaitListItemDto[],
	clients: {
		providerModuleId: string,
		recent: ClientModel[],
	},
	counters: CountersModel,
	isIntersectionForAll: boolean,
	isCurrentSenderAvailable: boolean,
	formsMenu: FormsMenuModel,
};

export type MainPageGetCountersResponse = {
	clientStatistics: {
		total: number,
		totalToday: number,
	},
	moneyStatistics: MoneyStatisticsDto,
	counters: CountersModel,
};

export type MoneyStatisticsDto = {
	today: {
		currencyId: string,
		opportunity: number,
	}[],
	month: {
		currencyId: string,
		opportunity: number,
	}[],
};
