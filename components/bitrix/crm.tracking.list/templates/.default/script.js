;(function ()
{
	'use strict';

	var namespace = BX.namespace('BX.Crm.Tracking');
	if (namespace.Grid)
	{
		return;
	}

	/**
	 * Grid.
	 *
	 */
	function Grid()
	{
	}
	Grid.prototype.init = function (params)
	{
		this.gridId = params.gridId;
		this.actionUri = params.actionUri;
		this.pathToEdit = params.pathToEdit;
		this.pathToAdd = params.pathToAdd;
		this.mess = params.mess;

		this.sourceTileManagerId = params.sourceTileManagerId;
		this.channelTileManagerId = params.channelTileManagerId;

		this.sourceTileManager = BX.UI.TileList.Manager.getById(this.sourceTileManagerId);
		this.channelTileManager = BX.UI.TileList.Manager.getById(this.channelTileManagerId);

		BX.addCustomEvent(
			this.sourceTileManager,
			this.sourceTileManager.events.buttonAdd,
			function () {
				BX.SidePanel.Instance.open(this.pathToAdd, {width: 735, cacheable: false});
				this.closeItemPopup();
			}.bind(this)
		);
		BX.addCustomEvent(
			this.sourceTileManager,
			this.sourceTileManager.events.tileClick,
			function (tile) {
				if (tile.data.items.list && tile.data.items.list.length > 0)
				{
					this.showItemPopup(tile.node, tile.data.items, 'source-' + tile.id);
				}
				else
				{
					BX.SidePanel.Instance.open(tile.data.url, {width: 735, cacheable: false});
					this.closeItemPopup();
				}
			}.bind(this)
		);
		BX.addCustomEvent(
			this.channelTileManager,
			this.channelTileManager.events.tileClick,
			function (tile) {
				if (tile.data.items.list && tile.data.items.list.length > 0)
				{
					this.showItemPopup(tile.node, tile.data.items, 'channel-' + tile.id);
				}
				else
				{
					BX.SidePanel.Instance.open(tile.data.url, {width: 735, cacheable: false});
					this.closeItemPopup();
				}
			}.bind(this)
		);
	};
	Grid.prototype.closeItemPopup = function ()
	{
		if (this.itemPopup)
		{
			this.itemPopup.close();
		}
	};
	Grid.prototype.showItemPopup = function (node, items, code)
	{
		if (this.itemPopup)
		{
			this.itemPopup.close();
		}

		var popupItems = [
			{text: items.addText, href: items.addUrl},
			{delimiter: true}
		].concat(items.list);

		popupItems = popupItems.map(function (item) {
			var className = '';
			if (BX.type.isBoolean(item.active))
			{
				className = 'crm-tracking-list-popup-item crm-tracking-list-popup-item-';
				className += (item.active ? 'green' : 'gray');
				item.html = '<span class="crm-tracking-list-popup-item ' + className + '"></span>' + BX.util.htmlspecialchars(item.text);
			}
			item.onclick = function (e, item) {
				BX.SidePanel.Instance.open(item.href, {width: 735, cacheable: false});
				item.getMenuWindow().close();
				e.preventDefault();
			};
			return item;
		});

		this.itemPopup = BX.PopupMenu.create(
			"crm-tracking-list-items-" + code,
			node, //bindElement
			popupItems,
			{
				maxHeight: 250,
				minWidth: 200,
				offsetLeft: 40,
				offsetTop: -10,
				bindOptions: {
					position: 'top'
				},
				angle: true,
				autoHide: true,
				closeByEsc : true
			}
		);

		this.itemPopup.show();
	};

	namespace.Grid = new Grid();
})();