import {Text, Event, Type, Reflection} from 'main.core';
import PullQueue from "./pullqueue";

const namespace = Reflection.namespace('BX.Crm.Kanban');

export default class PullManager
{
	grid;
	queue;

	constructor(grid)
	{
		this.grid = grid;
		this.queue = new PullQueue(this.grid);
		if (
			Type.isString(grid.getData().moduleId)
			&& grid.getData().userId > 0
		)
		{
			this.init();
		}
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
			item.setActivityExistInnerHtml();

			item.useAnimation = true;
			item.setChangedInPullRequest();
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

		this.grid.removeItem(params.item.id);

		const column = this.grid.getColumn(params.item.data.columnId);
		column.decPrice(params.item.data.price);
		column.renderSubTitle();
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
}

namespace.PullManager = PullManager;
