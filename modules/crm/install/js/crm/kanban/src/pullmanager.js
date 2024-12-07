import { Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import type { ActionItem } from 'pull.queuemanager';
import { QueueManager } from 'pull.queuemanager';
import PullOperation from './pulloperation';
import { ViewMode } from './viewmode';

const EventName = {
	itemUpdated: 'ITEMUPDATED',
	itemAdded: 'ITEMADDED',
	itemDeleted: 'ITEMDELETED',
	stageAdded: 'STAGEADDED',
	stageUpdated: 'STAGEUPDATED',
	stageDeleted: 'STAGEDELETED',
};

export default class PullManager
{
	queueManager: QueueManager;
	grid: BX.CRM.Kanban.Grid;

	constructor(grid: BX.CRM.Kanban.Grid)
	{
		if (!BX.PULL)
		{
			console.info('BX.PULL is not initialized');

			return;
		}

		this.grid = grid;

		const data = grid.getData();

		const options = {
			moduleId: data.moduleId,
			pullTag: data.pullTag,
			additionalPullTags: data.additionalPullTags ?? [],
			userId: data.userId,
			additionalData: {
				viewMode: data.viewMode,
			},
			events: {
				onBeforePull: (event) => {
					this.#onBeforePull(event);
				},
				onPull: (event) => {
					this.#onPull(event);
				},
			},
			callbacks: {
				onBeforeQueueExecute: (items) => {
					return this.#onBeforeQueueExecute(items);
				},
				onQueueExecute: (items) => {
					return this.#onQueueExecute(items);
				},
				onReload: () => {
					this.#onReload();
				},
			},
		};

		this.queueManager = new QueueManager(options);
	}

	#onBeforeQueueExecute(items: Object[]): Promise
	{
		items.forEach((item) => {
			const { data } = item;

			const operation = PullOperation.createInstance({
				grid: this.grid,
				itemId: data.id,
				action: data.action,
				actionParams: data.actionParams,
			});

			operation.execute(); // change to async and use Promise.all in return
		});

		return Promise.resolve();
	}

	#onQueueExecute(items: Object[]): Promise
	{
		const ids = [];
		items.forEach(({ id, data: { action } }) => {
			if (action === 'addItem' || action === 'updateItem')
			{
				ids.push(parseInt(id, 10));
			}
		});

		if (ids.length === 0)
		{
			return Promise.resolve();
		}

		return this.grid.loadNew(ids, false, true, true, true);
	}

	#onReload(): void
	{
		this.grid.reload();
	}

	#onBeforePull(event: BaseEvent): void
	{
		const { data: { options, pullData } } = event;
		if (
			!pullData.command.startsWith(options.pullTag)
			&& options.additionalData.viewMode !== ViewMode.MODE_ACTIVITIES
		)
		{
			event.preventDefault();
		}
	}

	#onPull(event: BaseEvent): void
	{
		const { pullData: { params } } = event.data;

		if (params.eventName === EventName.itemUpdated)
		{
			this.#onPullItemUpdated(event);

			return;
		}

		if (params.eventName === EventName.itemAdded)
		{
			this.#onPullItemAdded(event);

			return;
		}

		if (params.eventName === EventName.itemDeleted)
		{
			this.#onPullItemDeleted(event);

			return;
		}

		if (params.eventName === EventName.stageAdded)
		{
			this.#onPullStageChanged(event);

			return;
		}

		if (params.eventName === EventName.stageUpdated)
		{
			this.#onPullStageChanged(event);

			return;
		}

		if (params.eventName === EventName.stageDeleted)
		{
			this.#onPullStageDeleted(event);
		}
	}

	#onPullItemUpdated(event: BaseEvent): void
	{
		const { pullData: { params }, promises } = event.data;

		const item = this.grid.getItem(params.item.id);

		if (item)
		{
			promises.push(Promise.resolve({
				data: this.#getPullData('updateItem', params),
			}));

			return;
		}

		// eslint-disable-next-line no-param-reassign
		params.eventName = EventName.itemAdded;
		this.#onPullItemAdded(event);
	}

	#onPullItemAdded(event: BaseEvent): void
	{
		const { pullData: { params }, promises } = event.data;

		const itemId = params.item.id;
		const oldItem = this.grid.getItem(itemId);

		if (oldItem)
		{
			event.preventDefault();

			return;
		}

		promises.push(Promise.resolve({
			data: this.#getPullData('addItem', params),
		}));
	}

	#getPullData(action: string, actionParams: Object): ActionItem
	{
		const { id } = actionParams.item;

		return {
			id,
			action,
			actionParams,
		};
	}

	#onPullItemDeleted(event: BaseEvent): void
	{
		const { pullData: { params } } = event.data;

		if (!Type.isPlainObject(params.item))
		{
			return;
		}

		const { id, data: { columnId } } = params.item;

		/**
		 * Delay so that the element has time to be rendered before deletion,
		 * if an event for changing the element came before. Ticket #141983
		 */
		const delay = (
			this.queueManager.hasInQueue(id)
				? this.queueManager.getLoadItemsDelay()
				: 0
		);

		setTimeout(() => {
			this.queueManager.deleteFromQueue(id);

			const { grid } = this;
			const item = grid.getItem(id);
			if (!item)
			{
				return;
			}

			grid.removeItem(id);

			if (grid.getTypeInfoParam('showTotalPrice'))
			{
				const column = grid.getColumn(columnId);
				column.decPrice(item.data.price);
				column.renderSubTitle();
			}
		}, delay);

		event.preventDefault();
	}

	#onPullStageChanged(event: BaseEvent): void
	{
		event.preventDefault();

		this.grid.onApplyFilter();
	}

	#onPullStageDeleted(event: BaseEvent): void
	{
		event.preventDefault();

		const { pullData: { params } } = event.data;
		this.grid.removeColumn(params.stage.id);
	}
}
