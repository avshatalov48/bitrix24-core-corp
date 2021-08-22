import {Dom, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

export class Grid
{
	static get classes()
	{
		return {
			highlighted: 'task-projects-item-highlighted',
			pinned: 'tasks-projects-row-pinned',
		};
	}

	constructor(options)
	{
		this.pageSize = 10;

		this.grid = BX.Main.gridManager.getInstanceById(options.gridId);
		this.stub = options.gridStub;

		this.sort = options.sort;
		this.actionsPanel = options.actionsPanel;

		this.items = new Map();
		this.fillItems(options.items);

		this.bindEvents();
		this.colorPinnedRows();
	}

	bindEvents()
	{
		EventEmitter.subscribe('BX.Main.grid:sort', this.onColumnSort.bind(this));
		EventEmitter.subscribe('BX.Main.grid:paramsUpdated', this.onParamsUpdated.bind(this));
	}

	onColumnSort(event: BaseEvent)
	{
		const data = event.getData();
		const grid = data[1];
		const column = data[0];

		if (grid === this.grid)
		{
			this.sort = {};
			this.sort[column.sort_by] = column.sort_order;
		}
	}

	onParamsUpdated()
	{
		const newItems = this.getRows().map(row => row.getId()).filter(id => id !== 'template_0');
		const flipped = newItems.reduce((obj, value) => ({...obj, [value]: null}), {});

		this.clearItems();
		this.fillItems(flipped);
		this.colorPinnedRows();
	}

	addRow(id, data, params)
	{
		const options = {
			id: id,
			columns: data.content,
			actions: data.actions,
			cellActions: data.cellActions,
			counters: data.counters,
		};
		const moveParams = params.moveParams || {};

		if (moveParams.rowBefore)
		{
			options.insertAfter = moveParams.rowBefore;
		}
		else if (moveParams.rowAfter)
		{
			options.insertBefore = moveParams.rowAfter;
		}
		else
		{
			options.append = true;
		}

		if (this.items.size > this.getCurrentPage() * this.pageSize)
		{
			const lastRowId = this.getLastRowId();

			this.removeItem(lastRowId);
			Dom.remove(this.getRowNodeById(lastRowId));
			this.showMoreButton();
		}

		this.hideStub();
		this.getRealtime().addRow(options);
		this.colorPinnedRows();

		EventEmitter.emit('Tasks.Projects.Grid:RowAdd', {id});
	}

	updateRow(id, data, params)
	{
		const row = this.getRowById(id);
		row.setCellsContent(data.content);
		row.setActions(data.actions);
		row.setCellActions(data.cellActions);
		row.setCounters(data.counters);

		this.resetRows();
		this.moveRow(id, (params.moveParams || {}));
		this.highlightRow(id, (params.highlightParams || {})).then(() => this.colorPinnedRows(), () => {});

		this.grid.bindOnRowEvents();

		EventEmitter.emit('Tasks.Projects.Grid:RowUpdate', {id});
	}

	removeRow(id)
	{
		if (!this.isRowExist(id))
		{
			return;
		}

		this.grid.removeRow(id);

		EventEmitter.emit('Tasks.Projects.Grid:RowRemove', {id});
	}

	moveRow(rowId, params): void
	{
		if (params.skip)
		{
			return;
		}

		const rowBefore = params.rowBefore || 0;
		const rowAfter = params.rowAfter || 0;

		if (rowBefore)
		{
			this.grid.getRows().insertAfter(rowId, rowBefore);
		}
		else if (rowAfter)
		{
			this.grid.getRows().insertBefore(rowId, rowAfter);
		}
	}

	highlightRow(rowId, params): Promise
	{
		params = params || {};

		return new Promise((resolve, reject) => {
			if (!this.isRowExist(rowId))
			{
				reject();
				return;
			}

			if (params.skip)
			{
				resolve();
				return;
			}

			const node = this.getRowNodeById(rowId);
			const isPinned = Dom.hasClass(node, Grid.classes.pinned);

			if (isPinned)
			{
				Dom.removeClass(node, Grid.classes.pinned);
			}

			Dom.addClass(node, Grid.classes.highlighted);
			setTimeout(() => {
				Dom.removeClass(node, Grid.classes.highlighted);
				if (isPinned)
				{
					Dom.addClass(node, Grid.classes.pinned);
				}
				resolve();
			}, 900);
		});
	}

	colorPinnedRows()
	{
		this.getRows().forEach((row) => {
			const node = row.getNode();
			this.getIsPinned(row.getId())
				? Dom.addClass(node, Grid.classes.pinned)
				: Dom.removeClass(node, Grid.classes.pinned)
			;
		});
	}

	resetRows()
	{
		this.grid.getRows().reset();
	}

	getRows()
	{
		return this.grid.getRows().getBodyChild();
	}

	getFirstRowId()
	{
		const firstRow = this.grid.getRows().getBodyFirstChild();
		return (firstRow ? this.getRowProperty(firstRow, 'id') : 0);
	}

	getLastRowId()
	{
		const lastRow = this.grid.getRows().getBodyLastChild();
		return (lastRow ? this.getRowProperty(lastRow, 'id') : 0);
	}

	getLastPinnedRowId()
	{
		const pinnedRows = Object.values(this.getRows()).filter(row => this.getIsPinned(row.getId()));
		const keys = Object.keys(pinnedRows);

		if (keys.length > 0)
		{
			return pinnedRows[keys[keys.length - 1]].getId();
		}

		return 0;
	}

	getIsPinned(rowId)
	{
		return this.isRowExist(rowId)
			&& Type.isDomNode(this.getRowNodeById(rowId).querySelector('.main-grid-cell-content-action-pin.main-grid-cell-content-action-active'));
	}

	getRowProperty(row, propertyName)
	{
		return BX.data(row.getNode(), propertyName);
	}

	getRowById(id)
	{
		return this.grid.getRows().getById(id);
	}

	getRowNodeById(id)
	{
		return this.getRowById(id).getNode();
	}

	isRowExist(id)
	{
		return this.getRowById(id) !== null;
	}

	getCurrentPage()
	{
		const currentPage = this.grid.getContainer().querySelector('.modern-page-current');
		return (currentPage ? currentPage.innerText : 1);
	}

	isActivityRealtimeMode()
	{
		return this.sort.ACTIVITY_DATE
			&& this.sort.ACTIVITY_DATE === 'desc';
	}

	getItems()
	{
		return Array.from(this.items.keys());
	}

	hasItem(id)
	{
		return this.items.has(parseInt(id, 10));
	}

	addItem(id)
	{
		this.items.set(parseInt(id, 10));
	}

	removeItem(id)
	{
		this.items.delete(parseInt(id, 10));
	}

	fillItems(items)
	{
		Object.keys(items).forEach(id => this.addItem(id));
	}

	clearItems()
	{
		this.items.clear();
	}

	getRealtime()
	{
		return this.grid.getRealtime();
	}

	showStub()
	{
		if (this.stub)
		{
			this.getRealtime().showStub({content: this.stub});
		}
	}

	hideStub()
	{
		this.grid.hideEmptyStub();
	}

	showMoreButton()
	{
		this.grid.getMoreButton().getNode().style.display = 'inline-block';
	}

	hideMoreButton()
	{
		this.grid.getMoreButton().getNode().style.display = 'none';
	}
}