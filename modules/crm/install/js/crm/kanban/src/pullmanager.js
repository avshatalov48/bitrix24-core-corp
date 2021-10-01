import {Event, Type, Loc} from 'main.core';
import PullQueue from "./pullqueue";
import {EventEmitter} from "main.core.events";

export default class PullManager
{
	grid: BX.CRM.Kanban.Grid;
	queue: PullQueue;
	notifier: BX.UI.Notification.Balloon;

	constructor(grid)
	{
		this.grid = grid;
		this.queue = new PullQueue(this.grid);
		if (Type.isString(grid.getData().moduleId) && grid.getData().userId > 0)
		{
			this.init();
		}

		this.bindEvents();
	}

	init()
	{
		Event.ready(() =>
		{
			const Pull = BX.PULL;
			if (!Pull)
			{
				console.error('pull is not initialized');
				return;
			}

			Pull.subscribe({
				moduleId: this.grid.getData().moduleId,
				command: this.grid.getData().pullTag,
				callback: (params) =>
				{
					if (Type.isString(params.eventName))
					{
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
			this.queue.loadItem();
		}
	}

	updateItem(params)
	{
		const item = this.grid.getItem(params.item.id);
		const paramsItem = params.item;

		if (item)
		{
			const oldPrice = parseFloat(item.data.price);
			const oldColumnId = item.data.columnId;

			for (let key in paramsItem.data)
			{
				if (key in item.data)
				{
					item.data[key] = paramsItem.data[key];
				}
			}

			item.rawData = paramsItem.rawData;
			item.setActivityExistInnerHtml();
			item.useAnimation = true;
			item.setChangedInPullRequest();
			this.grid.resetMultiSelectMode();

			this.grid.insertItem(item);

			const newColumn = this.grid.getColumn(paramsItem.data.columnId);
			const newPrice = parseFloat(paramsItem.data.price);

			if (oldColumnId !== paramsItem.data.columnId)
			{
				const oldColumn = this.grid.getColumn(oldColumnId);
				oldColumn.decPrice(oldPrice);
				oldColumn.renderSubTitle();

				newColumn.incPrice(newPrice);
				newColumn.renderSubTitle();
			}
			else
			{
				if (oldPrice < newPrice)
				{
					newColumn.incPrice(newPrice - oldPrice);
					newColumn.renderSubTitle();
				}
				else if (oldPrice > newPrice)
				{
					newColumn.decPrice(oldPrice - newPrice);
					newColumn.renderSubTitle();
				}
			}

			item.columnId = paramsItem.data.columnId;
			this.queue.push(item.id);

			return true;
		}

		this.onPullItemAdded(params);
		return false
	}

	onPullItemAdded(params)
	{
		this.addItem(params);
		this.queue.loadItem();
	}

	addItem(params)
	{
		const oldItem = this.grid.getItem(params.item.id);
		if (oldItem)
		{
			return;
		}

		this.grid.addItemTop(params.item);
		this.queue.push(params.item.id);
	}

	onPullItemDeleted(params)
	{
		if (!Type.isPlainObject(params.item))
		{
			return;
		}

		/**
		 * Delay so that the element has time to be rendered before deletion,
		 * if an event for changing the element came before. Ticket #141983
		 */
		const delay = (this.queue.has(params.item.id) ? 5000 : 0);

		setTimeout(() => {
			this.queue.delete(params.item.id);
			this.grid.removeItem(params.item.id);

			const column = this.grid.getColumn(params.item.data.columnId);
			column.decPrice(params.item.data.price);
			column.renderSubTitle();
		}, delay);
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
			if (this.isEntitySlider(event.data[0].slider))
			{
				this.queue.freeze();
			}
		});
		EventEmitter.subscribe('SidePanel.Slider:onClose', (event) => {
			if (this.isEntitySlider(event.data[0].slider))
			{
				this.queue.unfreeze();
				this.onTabActivated();
			}
		});
	}

	isEntitySlider(slider): boolean
	{
		const sliderUrl = slider.getUrl();
		const entityPath = this.grid.getData().entityPath;
		const maskUrl = entityPath.replace(/\#([^\#]+)\#/, '([\\d]+)');

		return (new RegExp(maskUrl)).test(sliderUrl);
	}
}
