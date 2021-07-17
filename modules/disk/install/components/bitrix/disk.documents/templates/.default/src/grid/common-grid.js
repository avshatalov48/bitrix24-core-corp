
export default class CommonGrid
{
	/**
	 * @type {BX.TileGrid.Grid|BX.Main.grid}
	 */
	gridInstance: any = null;

	constructor(options)
	{
		this.gridInstance = options.gridInstance;
	}

	getId(): string
	{
		return this.gridInstance.getId();
	}

	isGrid(): boolean
	{
		return !this.isTile();
	}

	isTile(): boolean
	{
		return BX.TileGrid.Grid && (this.gridInstance instanceof BX.TileGrid.Grid);
	}

	getContainer(): HTMLElement
	{
		return this.gridInstance.getContainer();
	}

	fade(): void
	{
		if (this.isGrid())
		{
			this.gridInstance.tableFade();
		}
		else
		{
			this.gridInstance.setFadeContainer();
			this.gridInstance.getLoader();
			this.gridInstance.showLoader();
		}
	}

	unFade(): void
	{
		if (this.isGrid())
		{
			this.gridInstance.tableUnfade();
		}
		else
		{
			this.gridInstance.getLoader().hide();
			this.gridInstance.unSetFadeContainer();
		}
	}

	getActionKey(): string
	{
		return ('action_button_' + this.gridInstance.getId());
	}

	getSelectedIds(): Array<string>
	{
		if (this.isGrid())
		{
			return this.gridInstance.getRows().getSelectedIds();
		}
		else
		{
			return this.gridInstance.getSelectedItems().map(function (item) {
				return item.getId();
			});
		}
	}

	getIds(): Array<string>
	{
		if (this.isGrid())
		{
			return this.gridInstance.getRows().getBodyChild().map(function (row) {
				return row.getId();
			});
		}
		else
		{
			return this.gridInstance.items.map(function (item) {
				return item.id;
			});
		}
	}

	countItems(): number
	{
		if (this.isGrid())
		{
			return this.gridInstance.getRows().getBodyChild().length;
		}
		else
		{
			return this.gridInstance.countItems();
		}
	}

	reload(url, data)
	{
		data = data || {};

		if (this.isGrid())
		{
			var promise = new BX.Promise();
			this.gridInstance.reloadTable(
				"POST",
				data,
				function () {
					promise.fulfill();
				},
				url
			);

			return promise;
		}
		else
		{
			return this.gridInstance.reload(url, data);
		}
	}

	getActionsMenu(itemId): BX.Main.Menu
	{
		if (this.isGrid())
		{
			return this.gridInstance.getRows().getById(itemId).getActionsMenu();
		}
		else
		{
			var item = this.gridInstance.getItem(itemId);
			if (item)
			{
				return item.getActionsMenu();
			}
		}
	}

	getItemById(id)
	{
		if (this.isGrid())
		{
			return this.gridInstance.getRows().getById(id);
		}
		else
		{
			return this.gridInstance.getItem(id);
		}
	}

	scrollTo(id): void
	{
		var contentNode;
		if (this.isGrid())
		{
			var row = this.gridInstance.getRows().getById(id);
			if (row && row.node)
			{
				contentNode = row.node;
			}
		}
		else
		{
			var item = this.gridInstance.getItem(id);
			if (row && row.node)
			{
				contentNode = row.getContainer();
			}
		}

		if (contentNode)
		{
			(new BX.easing({
				duration: 500,
				start: {scroll: window.pageYOffset || document.documentElement.scrollTop},
				finish: {scroll: BX.pos(contentNode).top},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: function (state) {
					window.scrollTo(0, state.scroll);
				}
			})).animate();
		}
	}

	getActionById(id, menuItemId)
	{
		var item = this.getItemById(id);
		if (item)
		{
			var actions = item.getActions();
			for (var i = 0; i < actions.length; i++)
			{
				if (actions[i].id && actions[i].id === menuItemId)
				{
					return actions[i];
				}
			}
		}

		return null;
	}

	removeItemById(itemId): void
	{
		BX.fireEvent(document, 'click');

		if (this.isGrid())
		{
			this.gridInstance.removeRow(itemId);
		}
		else
		{
			var item = this.gridInstance.getItem(itemId);
			if (item)
			{
				//todo here we have to remove item from server
				this.gridInstance.removeItem(item);
			}
		}
	}

	selectItemById(itemId): void
	{
		var item;
		if (this.isGrid())
		{
			item = this.gridInstance.getRows().getById(itemId);
			if (item)
			{
				item.select();
			}
		}
		else
		{
			item = this.gridInstance.getItem(itemId);
			if (item)
			{
				this.gridInstance.selectItem(item);
			}
		}
	}

	removeSelected(): void
	{
		if (this.isGrid())
		{
			this.gridInstance.removeSelected();
		}
		else
		{
			//todo here we have to remove items from server
		}
	}

	sortByColumn(column): void
	{
		this.gridInstance.sortByColumn(column);
	}
}