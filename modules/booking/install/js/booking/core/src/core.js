import { Extension } from 'main.core';
import { Builder, BuilderModel, Store } from 'ui.vue3.vuex';

import { AhaMoment } from 'booking.const';
import { Bookings } from 'booking.model.bookings';
import { MessageStatus } from 'booking.model.message-status';
import { Clients } from 'booking.model.clients';
import { Counters } from 'booking.model.counters';
import { Interface } from 'booking.model.interface';
import { ResourceTypes } from 'booking.model.resource-types';
import { Resources } from 'booking.model.resources';
import { Favorites } from 'booking.model.favorites';
import { Dictionary } from 'booking.model.dictionary';
import { MainResources } from 'booking.model.main-resources';
import { BookingPullManager } from 'booking.provider.pull.booking-pull-manager';
import type { MoneyStatistics } from 'booking.model.interface';

export type BookingParams = {
	container: HTMLElement,
	afterTitleContainer: HTMLElement,
	counterPanelContainer: HTMLElement,
	isSlider: boolean,
	currentUserId: number,
	isFeatureEnabled: boolean,
	canTurnOnTrial: boolean,
	canTurnOnDemo: boolean,
	timezone: string,
	filterId: string,
	editingBookingId: number,
	ahaMoments: $Values<typeof AhaMoment>,
	totalClients: number,
	totalClientsToday: number,
	moneyStatistics: MoneyStatistics,
};

class CoreApplication
{
	#params: BookingParams;
	#store: Store;
	#builder: Builder;
	#initPromise: Promise;

	#pullManager: ?BookingPullManager = null;

	setParams(params: BookingParams): void
	{
		this.#params = params;
	}

	getParams(): BookingParams
	{
		return this.#params;
	}

	getStore(): Store
	{
		return this.#store;
	}

	async init(): Promise<void>
	{
		this.#initPromise ??= new Promise(async (resolve) => {
			this.#store = await this.#initStore();
			this.#initPull();
			resolve();
		});

		return this.#initPromise;
	}

	async #initStore(): Promise<Store>
	{
		const settings = Extension.getSettings('booking.core');

		this.#builder = Builder.init()
			.addModel(Bookings.create())
			.addModel(MessageStatus.create())
			.addModel(Clients.create())
			.addModel(Counters.create())
			.addModel(Interface.create().setVariables({
				schedule: settings.schedule,
				editingBookingId: this.#params.editingBookingId,
				timezone: this.#params.timezone,
				totalClients: this.#params.totalClients,
				totalNewClientsToday: this.#params.totalClientsToday,
				moneyStatistics: this.#params.moneyStatistics,
				isFeatureEnabled: this.#params.isFeatureEnabled,
				canTurnOnTrial: this.#params.canTurnOnTrial,
				canTurnOnDemo: this.#params.canTurnOnDemo,
			}))
			.addModel(ResourceTypes.create())
			.addModel(Resources.create())
			.addModel(Favorites.create())
			.addModel(Dictionary.create())
			.addModel(MainResources.create())
		;

		const builderResult = await this.#builder.build();

		return builderResult.store;
	}

	#initPull(): void
	{
		this.#pullManager = new BookingPullManager({
			currentUserId: this.#params.currentUserId,
		});

		this.#pullManager.initQueueManager();
	}

	async addDynamicModule(vuexBuilderModel: BuilderModel): Promise<void>
	{
		if (!(this.#builder instanceof Builder))
		{
			throw new TypeError('Builder has not been init');
		}

		if (this.#store.hasModule(vuexBuilderModel.getName()))
		{
			return;
		}

		await this.#builder.addDynamicModel(vuexBuilderModel);
	}

	removeDynamicModule(vuexModelName: string): void
	{
		if (this.#builder instanceof Builder && this.#store.hasModule(vuexModelName))
		{
			this.#builder.removeDynamicModel(vuexModelName);
		}
	}
}

export const Core = new CoreApplication();
