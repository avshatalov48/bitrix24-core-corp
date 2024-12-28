import { ajax as Ajax, Event, Loc, Runtime, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

import type { EntityCounterManagerOptions } from './entity-counter-manager-options';

export default class EntityCounterManager
{
	static lastInstance:?EntityCounterManager = null;

	#id: string;
	#entityTypeId: number;
	#codes: Array;
	#extras: Object;
	#withExcludeUsers: boolean = false;
	#counterData: Object;
	#isRequestRunning: boolean;
	#lastPullEventData: Object;
	#isTabActive: boolean;
	#openedSlidersCount: Number;

	constructor(options: EntityCounterManagerOptions): void
	{
		if (!Type.isPlainObject(options))
		{
			throw new TypeError('BX.Crm.EntityCounterManager: The "options" argument must be object.');
		}

		this.#id = Type.isString(options.id) ? options.id : '';
		if (this.#id === '')
		{
			throw new RangeError('BX.Crm.EntityCounterManager: The "id" argument must be specified.');
		}

		this.#entityTypeId = options.entityTypeId ? Text.toInteger(options.entityTypeId) : 0;
		this.#codes = Type.isArray(options.codes) ? options.codes : [];
		this.#extras = Type.isObject(options.extras) ? options.extras : {};
		this.#withExcludeUsers = Type.isBoolean(options.withExcludeUsers) ? options.withExcludeUsers : false;
		this.#counterData = {};
		this.#isTabActive = true;
		this.#openedSlidersCount = 0;

		this.#bindEvents();

		this.constructor.lastInstance = this;
	}

	#bindEvents(): void
	{
		EventEmitter.subscribe(
			'onPullEvent-main',
			Runtime.debounce(this.#onPullEvent, 3000, this),
		);

		Event.ready(() => {
			Event.bind(document, 'visibilitychange', () => {
				this.#isTabActive = document.visibilityState === 'visible';
				if (this.#isTabActive && this.#isRecalculationRequired())
				{
					this.#tryRecalculate(this.#lastPullEventData);
				}
			});
		});

		EventEmitter.subscribe('SidePanel.Slider:onOpen', () => {
			this.#openedSlidersCount++;
			this.#isTabActive = false;
		});

		EventEmitter.subscribe('SidePanel.Slider:onClose', () => {
			this.#openedSlidersCount--;
			if (this.#openedSlidersCount <= 0)
			{
				this.#openedSlidersCount = 0;
				this.#isTabActive = true;
				if (this.#isRecalculationRequired())
				{
					this.#tryRecalculate(this.#lastPullEventData);
				}
			}
		});
	}

	#onPullEvent(event: BaseEvent): void
	{
		const [command, params] = event.getData();
		if (command !== 'user_counter')
		{
			return;
		}

		this.#lastPullEventData = params;
		if (!this.#isTabActive)
		{
			return;
		}

		this.#tryRecalculate(params);
	}

	#tryRecalculate(params: Object): void
	{
		let enableRecalculation = false;
		let enableRecalculationWithRequest = false;

		const counterData = this.#fetchCounterData(params);

		// eslint-disable-next-line no-restricted-syntax
		for (const counterId in counterData)
		{
			if (
				!Object.hasOwn(counterData, counterId)
				|| !this.#codes.includes(counterId)
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
		if (this.#isRequestRunning)
		{
			return;
		}

		if (!this.#isTabActive)
		{
			return;
		}

		this.#isRequestRunning = true;

		const data = {
			entityTypeId: this.#entityTypeId,
			extras: this.#extras,
			withExcludeUsers: this.#withExcludeUsers ? 1 : 0,
		};

		void Ajax
			.runAction('crm.counter.list', { data })
			.then(this.#onRecalculationSuccess.bind(this))
		;
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

		return Type.isPlainObject(params[currentSiteId]) ? params[currentSiteId] : {};
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
