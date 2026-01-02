import { Text } from 'main.core';
import type { BaseEvent } from 'main.core.events';

import { QueueManager } from 'pull.queuemanager';
import type { ActionItem } from 'pull.queuemanager';

import { Module } from 'tasks.v2.const';
import { TaskPullHandler } from './task-pull-handler/task-pull-handler';
import type { BasePullHandler } from './handler/base-pull-handler';

type Params = {
	currentUserId: number,
};

export class PullManager
{
	#params: Params;
	#loadItemsDelay: number = 500;

	#handlers: Set<BasePullHandler>;

	constructor(params: Params)
	{
		this.#params = params;

		this.#handlers = new Set([
			new TaskPullHandler(),
		]);
	}

	initQueueManager(): QueueManager
	{
		return new QueueManager({
			moduleId: Module.Tasks,
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
