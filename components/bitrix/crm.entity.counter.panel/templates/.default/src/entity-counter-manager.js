import { ajax as Ajax, Event, Loc, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

import type { EntityCounterManagerOptions } from './entity-counter-manager-options';

export default class EntityCounterManager
{
	static lastInstance:?EntityCounterManager = null;

	#id: string;
	#entityTypeId: number;
	#entityTypeName: string;
	#serviceUrl: string;
	#codes: Array;
	#extras: Object;
	#withExcludeUsers: boolean = false;
	#counterData: Object;
	#isRequestRunning: boolean;
	#lastPullEventData: Object;
	#isTabActive: boolean;

	constructor(options: EntityCounterManagerOptions): void
	{
		if (!Type.isPlainObject(options))
		{
			throw 'BX.Crm.EntityCounterManager: The "options" argument must be object.';
		}

		this.#id = Type.isString(options.id) ? options.id : '';
		if (this.#id === '')
		{
			throw 'BX.Crm.EntityCounterManager: The "id" argument must be specified.';
		}

		this.#serviceUrl = Type.isString(options.serviceUrl) ? options.serviceUrl : '';
		if (this.#serviceUrl === '')
		{
			throw 'BX.Crm.EntityCounterManager: The "serviceUrl" argument must be specified.';
		}

		this.#entityTypeId = options.entityTypeId ? Text.toInteger(options.entityTypeId) : 0;
		this.#entityTypeName = BX.CrmEntityType.resolveName(this.#entityTypeId);
		this.#codes = Type.isArray(options.codes) ? options.codes : [];
		this.#extras = Type.isObject(options.extras) ? options.extras : {};
		this.#withExcludeUsers = Type.isBoolean(options.withExcludeUsers) ? options.withExcludeUsers : false;
		this.#counterData = {};
		this.#isTabActive = true;

		this.#bindEvents();

		this.constructor.lastInstance = this;
	}

	#bindEvents(): void
	{
		EventEmitter.subscribe('onPullEvent-main', this.#onPullEvent.bind(this));

		Event.ready(() => {
			Event.bind(document, 'visibilitychange', () => {
				this.#isTabActive = document.visibilityState === 'visible';
				if (this.#isTabActive && this.#isRecalculationRequired())
				{
					this.#tryRecalculate(this.#lastPullEventData);
				}
			});
		});
	}

	#onPullEvent(event: BaseEvent): void
	{
		const [ command, params ] = event.getData();
		if (command !== 'user_counter')
		{
			return;
		}

		this.#lastPullEventData = params;

		this.#tryRecalculate(params);
	}

	#tryRecalculate(params: Object): void
	{
		let enableRecalculation = false;
		let enableRecalculationWithRequest = false;

		const counterData = this.#fetchCounterData(params);
		for (let counterId in counterData)
		{
			if (
				!counterData.hasOwnProperty(counterId)
				|| this.#codes.indexOf(counterId) < 0
			)
			{
				continue;
			}

			const counterValue = BX.prop.getInteger(counterData, counterId, 0);
			if (counterValue < 0)
			{
				enableRecalculationWithRequest = true;

				break;
			}

			const currentCounterValue = BX.prop.getInteger(this.#counterData, counterId, 0);
			if (currentCounterValue !== counterValue)
			{
				enableRecalculation = true;

				// update counter data
				this.#counterData[counterId] = counterValue;
			}
		}

		if (enableRecalculationWithRequest)
		{
			this.#startRecalculationRequest();
		}

		if (enableRecalculation)
		{
			EventEmitter.emit(this, 'BX.Crm.EntityCounterManager:onRecalculate');
		}
	}

	#startRecalculationRequest(): void
	{
		if (this.#isRequestRunning )
		{
			return;
		}

		if (!this.#isTabActive)
		{
			return;
		}

		this.#isRequestRunning = true;

		Ajax.runAction('crm.counter.list', {
			data: {
				entityTypeId: this.#entityTypeId,
				extras: this.#extras,
				withExcludeUsers: this.#withExcludeUsers ? 1 : 0,
			}
		}).then(this.#onRecalculationSuccess.bind(this));
	}

	#onRecalculationSuccess(result: Object): void
	{
		this.#isRequestRunning = false;

		const data = Type.isPlainObject(result.data) ? result.data : null;
		if (data === null)
		{
			return;
		}
		this.setCounterData(data);

		EventEmitter.emit('BX.Crm.EntityCounterManager:onRecalculate', this);
	}

	#fetchCounterData(params: Object): Object
	{
		const currentSiteId = Loc.getMessage('SITE_ID');

		return  Type.isPlainObject(params[currentSiteId]) ? params[currentSiteId] : {};
	}

	#isRecalculationRequired(): Boolean
	{
		if (!this.#lastPullEventData)
		{
			return false;
		}

		const counterData = this.#fetchCounterData(this.#lastPullEventData);

		return Object.values(counterData).includes(-1);
	}

	getId(): string
	{
		return this.#id;
	}

	getCounterData(): Object
	{
		return this.#counterData;
	}

	setCounterData(data: Object): void
	{
		this.#counterData = data;
	}

	static getLastInstance(): ?EntityCounterManager
	{
		return this.lastInstance;
	}
}
