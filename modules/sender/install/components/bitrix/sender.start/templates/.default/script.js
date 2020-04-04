;(function ()
{
	BX.namespace('BX.Sender');
	if (BX.Sender.Start)
	{
		return;
	}

	var Page = BX.Sender.Page;

	/**
	 * Manager.
	 *
	 */
	function Manager()
	{
	}
	Manager.prototype.init = function (options)
	{
		this.context = BX(options.containerId);

		BX.UI.TileList.Manager.getById('sender-start-mailings').getTiles().forEach(this.initTile, this);
		BX.UI.TileList.Manager.getById('sender-start-ad').getTiles().forEach(this.initTile, this);
		BX.UI.TileList.Manager.getById('sender-start-rc').getTiles().forEach(this.initTile, this);
	};
	Manager.prototype.initTile = function (tile)
	{
		BX.bind(tile.node, 'click', this.onClick.bind(this, tile));
	};
	Manager.prototype.onClick = function (tile)
	{
		if (!tile.selected && BX.Sender.B24License)
		{
			BX.Sender.B24License.showPopup('Ad');
			return;
		}

		Page.open(tile.data.url);
	};

	BX.Sender.Start = new Manager();

})(window);