;(function ()
{
	'use strict';

	var namespace = BX.namespace('BX.Crm.Tracking.Channel');
	if (namespace.Pool)
	{
		return;
	}

	/**
	 * Editor.
	 *
	 */
	function Editor()
	{
		this.context = null;
		this.editor = null;

		this.colors = {
			used: '#e9e9e9'
		};
	}
	Editor.prototype.init = function (params)
	{
		this.context = BX(params.containerId);
		this.componentName = params.componentName;
		this.signedParameters = params.signedParameters;
		this.mess = params.mess || {};
		this.typeId = params.typeId;

		BX.bind(BX('item-allocate'), 'click', this.allocate.bind(this));

		this.poolSelector = BX.UI.TileSelector.getById('available-list');
		this.organicSelector = BX.UI.TileSelector.getById('organic-list');
		BX.addCustomEvent(this.poolSelector, this.poolSelector.events.tileRemove, this.onRemoveTile.bind(this));

		this.selectors = BX.UI.TileSelector.getList().filter(function (selector) {
			return selector !== this.poolSelector && selector !== this.organicSelector;
		}, this);
		this.selectors.forEach(function (selector) {
			BX.addCustomEvent(selector, selector.events.tileAdd, this.actualizePool.bind(this));
			BX.addCustomEvent(selector, selector.events.tileRemove, this.actualizePool.bind(this));
			BX.addCustomEvent(selector, selector.events.buttonSelect, this.showSelectorPopup.bind(this, selector));
		}, this);

		this.actualizePool();

		this.itemDialogBtn = BX('item-add');
		BX.bind(this.itemDialogBtn, 'click', this.showAddItemDialog.bind(this));
	};
	Editor.prototype.showSelectorPopup = function (selector)
	{
		var items = this.poolSelector.getTiles().filter(function (tile) {
			return !tile.data.used;
		});
		selector.showSearcher(this.mess.searcherTitle);
		selector.setSearcherData([{
			id: 'all',
			name: this.mess.searcherCategory,
			data: {},
			items: items
		}]);
	};
	Editor.prototype.onRemoveTile = function (tile)
	{
		if (!tile.id)
		{
			return;
		}

		this.request(
			'removeItem',
			{
				typeId: this.typeId,
				value: tile.id
			},
			function (response)
			{
			},
			function ()
			{
			}
		);
	};
	Editor.prototype.showAddItemDialog = function ()
	{
		if (!this.itemDialog)
		{
			this.itemDialog = new BX.PopupWindow(
				'crm-tracking-phone-add',
				this.itemDialogBtn,
				{
					content: BX('crm-tracking-dialog-add'),
					minWidth: 300
				}
			);
			this.itemDialogName = BX('item-add-name');
			var buttonAdd = BX('item-add-name-btn-add');
			BX.bind(buttonAdd, 'click', function () {

				var value = this.itemDialogName.value.trim();
				if (!value)
				{
					this.itemDialog.close();
					return;
				}

				BX.addClass(buttonAdd, 'ui-btn-wait');
				this.request(
					'addItem',
					{
						typeId: this.typeId,
						value: value
					},
					function (response)
					{
						BX.removeClass(buttonAdd, 'ui-btn-wait');
						this.itemDialog.close();

						if (!response.data || !response.data.value)
						{
							return;
						}

						if (response.data.value === true)
						{
							return;
						}

						this.poolSelector.addTile(
							response.data.value,
							{canRemove: true},
							response.data.value
						);
					},
					function ()
					{
						BX.removeClass(buttonAdd, 'ui-btn-wait');
						this.itemDialog.close();
					}
				);
			}.bind(this));
			BX.bind(BX('item-add-name-btn-close'), 'click', function () {
				this.itemDialog.close();
			}.bind(this));
		}

		if (this.itemDialog.isShown())
		{
			this.itemDialog.close();
		}
		else
		{
			this.itemDialogName.value = '';
			this.itemDialog.show();
			this.itemDialogName.focus();
		}
	};
	Editor.prototype.actualizePool = function ()
	{
		var tiles = this.selectors.reduce(function (accumulator, selector) {
			return accumulator.concat(selector.getTiles());
		}, []);

		this.organicSelector.removeTiles();
		this.poolSelector.getTiles().forEach(function (poolTile) {
			poolTile.data.used = tiles.some(function (tile) {
				return poolTile.id === tile.id;
			});
			poolTile.changeRemoving(!poolTile.data.used && poolTile.data.canRemove);
			this.poolSelector.updateTile(
				poolTile,
				null,
				poolTile.data,
				poolTile.data.used ? this.colors.used : null
			);

			if (!poolTile.data.used)
			{
				this.organicSelector.addTile(
					poolTile.name || poolTile.id,
					poolTile.data,
					poolTile.id
				).changeRemoving(false);
			}

		}, this);
	};
	Editor.prototype.allocate = function ()
	{
		var pool = this.poolSelector.getTiles().filter(function (tile) {
			return !tile.data.used;
		});
		if (pool.length === 0)
		{
			return;
		}

		this.selectors.filter(function (selector) {
			return selector.getTiles().length === 0;
		}).forEach(function (selector) {
			if (pool.length === 0)
			{
				return;
			}

			var tile = pool.shift();
			selector.addTile(tile.name || tile.id, tile.data, tile.id);
		});
	};
	Editor.prototype.request = function (action, data, callbackSuccess, callbackFailure)
	{
		BX.ajax.runComponentAction(this.componentName, action, {
			'mode': 'class',
			'signedParameters': this.signedParameters,
			'data': data
		}).then(
			function (response)
			{
				var data = response.data || {};
				if(data.error)
				{
					callbackFailure.apply(this, [data]);
				}
				else if(callbackSuccess)
				{
					callbackSuccess.apply(this, [data]);
				}
			}.bind(this),
			function()
			{
				var data = {'error': true, 'text': ''};
				callbackFailure.apply(this, [data]);
			}.bind(this)
		);
	};

	namespace.Pool = new Editor();
})();