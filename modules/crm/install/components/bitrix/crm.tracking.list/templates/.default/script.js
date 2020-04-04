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
			}.bind(this)
		);
		BX.addCustomEvent(
			this.sourceTileManager,
			this.sourceTileManager.events.tileClick,
			function (tile) {
				BX.SidePanel.Instance.open(tile.data.url, {width: 735, cacheable: false});
			}
		);
		BX.addCustomEvent(
			this.channelTileManager,
			this.channelTileManager.events.tileClick,
			function (tile) {
				BX.SidePanel.Instance.open(tile.data.url, {width: 735, cacheable: false});
			}
		);
	};

	namespace.Grid = new Grid();
})();