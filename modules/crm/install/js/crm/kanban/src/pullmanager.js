import {Event, Type, Loc, Text} from 'main.core';
import PullQueue from "./pullqueue";
import {EventEmitter} from "main.core.events";
import {ViewMode} from "./viewmode";

export default class PullManager
{
	grid: BX.CRM.Kanban.Grid;
	queue: PullQueue;
	notifier: BX.UI.Notification.Balloon;
	openedSlidersCount: Number;

	static eventIds = new Set();

	constructor(grid)
	{
		this.grid = grid;
		this.queue = new PullQueue(this.grid);
		this.openedSlidersCount = 0;
		if (Type.isString(grid.getData().moduleId) && grid.getData().userId > 0)
		{
			this.init();
		}

		this.bindEvents();
	}

	static registerRandomEventId(): string
	{
		const eventId = Text.getRandom(12);
		this.registerEventId(eventId);
		return eventId;
	}

	static registerEventId(eventId: string)
	{
		this.eventIds.add(eventId);
	}

	init()
	{
		Event.ready(() => {
			const Pull = BX.PULL;
			if (!Pull)
			{
				console.error('pull is not initialized');
				return;
			}

			const gridData = this.grid.getData();
			const { pullTag, eventKanbanUpdatedTag, viewMode } = gridData;

			Pull.subscribe({
				moduleId: this.grid.getData().moduleId,
				//command: this.grid.getData().pullTag,
				callback: (data) => {
					if (
						data.command !== pullTag
						&& !(data.command.indexOf(eventKanbanUpdatedTag) === 0 && viewMode === ViewMode.MODE_ACTIVITIES)
					)
					{
						return;
					}

					const { params } = data;

					if (Type.isString(params.eventName))
					{
						if(PullManager.eventIds.has(params.eventId))
						{
							return;
						}

						if(this.queue.isOverflow())
						{
							return;
						}

						if (params.eventName === 'ITEMUPDATED')
						{
							this.onPullItemUpdated(params);
						}
						else if (params.eventName === 'ITEMADDED')
						{
							this.onPullItemAdded(params);
						}
						else if (params.eventName === 'ITEMDELETED')
						{
							this.onPullItemDeleted(params);
						}
						else if (params.eventName === 'STAGEADDED')
						{
							this.onPullStageAdded(params);
						}
						else if (params.eventName === 'STAGEDELETED')
						{
							this.onPullStageDeleted(params);
						}
						else if (params.eventName === 'STAGEUPDATED')
						{
							this.onPullStageUpdated(params);
						}
					}
				},
			});
			Pull.extendWatch(this.grid.getData().pullTag);

			Event.bind(document, 'visibilitychange', () => {
				if (!document.hidden)
				{
					this.onTabActivated();
				}
			});
		});
	}

	onPullItemUpdated(params)
	{
		if (this.updateItem(params))
		{
			this.queue.loadItem(false, params.ignoreDelay || false);
		}
	}

	updateItem(params)
	{
		const item = this.grid.getItem(params.item.id);

		if (item)
		{
			this.queue.push(item.id, {
				id: item.id,
				action: 'updateItem',
				actionParams: params,
			});

			return true;
		}

		this.onPullItemAdded(params);
		return false
	}

	onPullItemAdded(params)
	{
		if (this.addItem(params))
		{
			this.queue.loadItem(false, params.ignoreDelay || false);
		}
	}

	addItem(params)
	{
		const itemId = params.item.id;
		const oldItem = this.grid.getItem(itemId);
		if (oldItem)
		{
			return false;
		}

		this.queue.push(itemId, {
			id: itemId,
			action: 'addItem',
			actionParams: params,
		});

		return true;
	}

	onPullItemDeleted(params)
	{
		if (!Type.isPlainObject(params.item))
		{
			return;
		}

		const { id, data: { columnId } } = params.item;

		/**
		 * Delay so that the element has time to be rendered before deletion,
		 * if an event for changing the element came before. Ticket #141983
		 */
		const delay = (this.queue.has(id) ? 5000 : 0);

		setTimeout(function() {
			this.queue.delete(id);

			const item = this.grid.getItem(id);
			if (!item)
			{
				return;
			}

			this.grid.removeItem(id);

			const column = this.grid.getColumn(columnId);
			column.decPrice(item.price);
			column.renderSubTitle();
		}.bind(this), delay);
	}

	onPullStageAdded(params)
	{
		this.grid.onApplyFilter();
	}

	onPullStageDeleted(params)
	{
		this.grid.removeColumn(params.stage.id);
	}

	onPullStageUpdated(params)
	{
		this.grid.onApplyFilter();
	}

	onTabActivated()
	{
		if (this.queue.isOverflow())
		{
			this.showOutdatedDataDialog();
		}
		else if (this.queue.peek())
		{
			this.queue.loadItem();
		}
	}

	showOutdatedDataDialog()
	{
		if (!this.notifier)
		{
			this.notifier = BX.UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_KANBAN_NOTIFY_OUTDATED_DATA'),
				closeButton: false,
				autoHide: false,
				actions: [{
					title: Loc.getMessage('CRM_KANBAN_GRID_RELOAD'),
					events: {
						click: (event, balloon, action) => {
							balloon.close();
							this.grid.reload();
							this.queue.clear();
						}
					}
				}]
			});
		}
		else
		{
			this.notifier.show();
		}
	}

	bindEvents(): void
	{
		EventEmitter.subscribe('SidePanel.Slider:onOpen', (event) => {
			this.openedSlidersCount++;
			this.queue.freeze();
		});
		EventEmitter.subscribe('SidePanel.Slider:onClose', (event) => {
			this.openedSlidersCount--;
			if (this.openedSlidersCount <= 0)
			{
				this.openedSlidersCount = 0;
				this.queue.unfreeze();
				this.onTabActivated();
			}
		});
	}
}
