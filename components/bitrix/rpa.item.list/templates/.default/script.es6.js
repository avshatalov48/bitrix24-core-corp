import {Event, Reflection, Text, Type} from 'main.core';

const namespace = Reflection.namespace('BX.Rpa');

class ItemsListComponent
{
	typeId = 0;
	gridId = null;
	eventIds;
	kanbanPullTag = null;
	grid = null;

	constructor(params: {
		typeId: number,
		kanbanPullTag: ?string,
		gridId: ?string,
	})
	{
		if(Type.isPlainObject(params))
		{
			this.typeId = Text.toInteger(params.typeId);
			this.gridId = params.gridId;
			this.kanbanPullTag = params.kanbanPullTag;
		}

		this.eventIds = new Set();

		this.bindEvents();
	}

	getGrid(): ?BX.Main.grid
	{
		if(this.grid)
		{
			return this.grid;
		}

		if(this.gridId && BX.Main.grid && BX.Main.gridManager)
		{
			this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
		}

		return this.grid;
	}

	bindEvents()
	{
		Event.ready(() =>
		{
			const Pull = BX.PULL;
			if(!Pull)
			{
				console.error('pull is not initialized');
				return;
			}
			if(Type.isString(this.kanbanPullTag) && this.typeId > 0)
			{
				Pull.subscribe({
					moduleId: 'rpa',
					command: this.kanbanPullTag,
					callback: (params) =>
					{
						if(Type.isString(params.eventName))
						{
							if(Type.isString(params.eventId))
							{
								if(this.eventIds.has(params.eventId))
								{
									return;
								}
							}
							if(params.eventName.indexOf('ITEMUPDATED' + this.typeId) === 0 && Type.isPlainObject(params.item))
							{
								this.onPullItemUpdated(params.item);
							}
						}
					}
				});
			}
		});
	}

	onPullItemUpdated(item: {
			id: number,
	})
	{
		const grid = this.getGrid();
		if(!grid)
		{
			return;
		}
		const row = grid.getRows().getById(item.id);
		if(!row)
		{
			return;
		}

		row.update()
	}
}

namespace.ItemsListComponent = ItemsListComponent;