(function() {

'use strict';

BX.namespace('BX.Intranet.Automation');

BX.Intranet.Automation.Center = function(options)
{
	options = BX.Type.isPlainObject(options) ? options : {};

	this.tileManagerId = options.tileManagerId;
	this.tileManager = BX.UI.TileList.Manager.getById(this.tileManagerId);

	BX.Event.EventEmitter.subscribe(
		this.tileManager,
		this.tileManager.events.tileClick,
		this.handleTileClick.bind(this)
	);
};

BX.Intranet.Automation.Center.prototype =
{
	handleTileClick: function(event)
	{
		var tile = event.getData()[0];
		if (tile && tile.data && BX.Type.isStringFilled(tile.data.url))
		{
			window.location = tile.data.url;
		}
	},
};

})();