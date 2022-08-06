import { ajax as Ajax, Loc, Text, Type } from 'main.core';
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
	#counterData: Object;
	#isRequestRunning: boolean;

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
		this.#counterData = {};

		this.#bindEvents();

		this.constructor.lastInstance = this;
	}

	#bindEvents(): void
	{
		EventEmitter.subscribe('onPullEvent-main', this.#onPullEvent.bind(this));
	}

	#onPullEvent(event: BaseEvent): void
	{
		const [ command, params ] = event.getData();
		if (command !== 'user_counter')
		{
			return;
		}

		let enableRecalculation = false;
		let enableRecalculationWithRequest = false;
		const currentSiteId = Loc.getMessage('SITE_ID');
		const counterData = Type.isPlainObject(params[currentSiteId]) ? params[currentSiteId] : {};
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
			EventEmitter.emit('BX.Crm.EntityCounterManager:onRecalculate', this);
		}
	}

	#startRecalculationRequest(): void
	{
		if (this.#isRequestRunning)
		{
			return;
		}

		this.#isRequestRunning = true;

		Ajax({
			url: this.#serviceUrl,
			method: 'POST',
			dataType: 'json',
			data: {
				'ACTION': 'RECALCULATE',
				'ENTITY_TYPES': [ this.#entityTypeName ],
				'EXTRAS': this.#extras
			},
			onsuccess: BX.delegate(this.#onRecalculationSuccess, this)
		});
	}

	#onRecalculationSuccess(result: Object): void
	{
		this.#isRequestRunning = false;

		const data = Type.isPlainObject(result['DATA']) ? result['DATA'] : null;
		if (data === null)
		{
			return;
		}

		this.setCounterData(
			Type.isPlainObject(data[this.#entityTypeName])
				? data[this.#entityTypeName]
				: {}
		);

		EventEmitter.emit('BX.Crm.EntityCounterManager:onRecalculate', this);
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
