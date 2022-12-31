import { Sorter } from "crm.kanban.sort";

export default class PullOperation
{
	grid: BX.CRM.Kanban.Grid;
	itemId: Number;
	action: String;
	actionParams: Object;

	static createInstance(data: Object): PullOperation
	{
		const instance = new PullOperation(data.grid);

		instance.setItemId(data.itemId);
		instance.setAction(data.action);
		instance.setActionParams(data.actionParams);

		return instance;
	}

	constructor(grid: BX.CRM.Kanban.Grid): void
	{
		this.grid = grid;
	}

	setItemId(itemId: Number): PullOperation
	{
		this.itemId = itemId;
		return this;
	}

	getItemId(): Number
	{
		return this.itemId;
	}

	setAction(action: String): PullOperation
	{
		this.action = action;
		return this;
	}

	getAction(): String
	{
		return this.action;
	}

	setActionParams(actionParams: Object): PullOperation
	{
		this.actionParams = actionParams;
		return this;
	}

	getActionParams(): Object
	{
		return this.actionParams;
	}

	execute(): void
	{
		if (this.getAction() === 'updateItem')
		{
			return this.updateItem();
		}

		if (this.getAction() === 'addItem')
		{
			return this.addItem();
		}
	}

	updateItem(): void
	{
		const params = this.getActionParams();
		const item = this.grid.getItem(params.item.id);
		const paramsItem = params.item;

		if (!item)
		{
			return;
		}

		const insertItemParams = {};
		if (paramsItem.data.lastActivity && paramsItem.data.lastActivity.timestamp !== item.data.lastActivity.timestamp)
		{
			insertItemParams.canShowLastActivitySortTour = true;
		}

		const oldPrice = parseFloat(item.data.price);
		const oldColumnId = item.columnId;

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

		const newColumnId = paramsItem.data.columnId;
		const newColumn = this.grid.getColumn(newColumnId);
		const newPrice = parseFloat(paramsItem.data.price);

		insertItemParams.newColumnId = newColumnId;
		this.grid.insertItem(item, insertItemParams);

		item.columnId = newColumnId;

		if (!this.grid.getTypeInfoParam('showTotalPrice'))
		{
			return;
		}

		if (oldColumnId !== newColumnId)
		{
			const oldColumn = this.grid.getColumn(oldColumnId);
			oldColumn.decPrice(oldPrice);
			oldColumn.renderSubTitle();
			if (newColumn)
			{
				newColumn.incPrice(newPrice);
				newColumn.renderSubTitle();
			}
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
	}

	addItem(): void
	{
		const params = this.getActionParams();
		const oldItem = this.grid.getItem(params.item.id);
		if (oldItem)
		{
			return;
		}

		const column = this.grid.getColumn(params.item.data.columnId);
		if (!column)
		{
			return;
		}

		const sorter = Sorter.createWithCurrentSortType(column.getItems());

		const beforeItem = sorter.calcBeforeItemByParams(params.item.data.sort);
		if (beforeItem)
		{
			params.item.targetId = beforeItem.getId();
		}

		this.grid.addItem(params.item);
	}
}
