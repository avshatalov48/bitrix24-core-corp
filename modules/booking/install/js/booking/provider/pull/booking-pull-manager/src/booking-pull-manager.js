import { Text } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { QueueManager, ActionItem } from 'pull.queuemanager';
import { Module } from 'booking.const';

import { BookingPullHandler } from './handler/booking-pull-handler';
import { BasePullHandler } from './handler/base-pull-handler';
import { CountersPullHandler } from './handler/counters-pull-handler';
import { ResourcePullHandler } from './handler/resource-pull-handler';
import { ResourceTypePullHandler } from './handler/resource-type-pull-handler';

type Params = {
	currentUserId: number,
}

export class BookingPullManager
{
	#params: Params;
	#loadItemsDelay: number = 500;

	#handlers: Set<BasePullHandler>;

	constructor(params: Params)
	{
		this.#params = params;

		this.#handlers = new Set([
			new BookingPullHandler(),
			new ResourcePullHandler(),
			new ResourceTypePullHandler(),
			new CountersPullHandler(),
		]);
	}

	initQueueManager(): QueueManager
	{
		return new QueueManager({
			moduleId: Module.Booking,
			userId: this.#params.currentUserId,
			config: {
				loadItemsDelay: this.#loadItemsDelay,
			},
			additionalData: {},
			events: {
				onBeforePull: (baseEvent: BaseEvent) => {
					this.#onBeforePull(baseEvent);
				},
				onPull: (baseEvent: BaseEvent) => {
					this.#onPull(baseEvent);
				},
			},
			callbacks: {
				onBeforeQueueExecute: (items: Array<ActionItem>) => {
					return this.#onBeforeQueueExecute(items);
				},
				onQueueExecute: (items: Array<ActionItem>) => {
					return this.#onQueueExecute(items);
				},
				onReload: () => {
					this.#onReload();
				},
			},
		});
	}

	#onBeforePull(baseEvent: BaseEvent): void
	{
		const { pullData: { command, params } } = baseEvent.data;

		for (const handler of this.#handlers)
		{
			handler.getMap()[command]?.(params);
		}
	}

	#onPull(baseEvent: BaseEvent): void
	{
		const { pullData: { command, params }, promises } = baseEvent.data;

		for (const handler of this.#handlers)
		{
			if (handler.getDelayedMap()[command])
			{
				promises.push(
					Promise.resolve({
						data: {
							id: params.entityId ?? Text.getRandom(),
							command,
							params,
						},
					}),
				);
			}
		}
	}

	#onBeforeQueueExecute(items: Array<ActionItem>): Promise
	{
		return Promise.resolve();
	}

	async #onQueueExecute(items: Array<ActionItem>): Promise
	{
		await this.#executeQueue(items);
	}

	#onReload(event) {}

	#executeQueue(items: Array<ActionItem>): Promise
	{
		return new Promise((resolve) => {
			items.forEach((item: ActionItem) => {
				const { data: { command, params } } = item;

				for (const handler of this.#handlers)
				{
					handler.getDelayedMap()[command]?.(params);
				}
			});

			resolve();
		});
	}
}
