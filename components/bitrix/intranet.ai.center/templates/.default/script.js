(function() {

"use strict";

BX.namespace("BX.Intranet.AI");

BX.Intranet.AI.Center = function(options)
{
	options = BX.type.isPlainObject(options) ? options : {};

	this.assistantAppId = options.assistantAppId;
	this.tileManagerId = options.tileManagerId;
	this.mlInstalled  = options.mlInstalled;
	this.tileManager = BX.UI.TileList.Manager.getById(this.tileManagerId);

	BX.addCustomEvent(
		this.tileManager,
		this.tileManager.events.tileClick,
		this.handleTileClick.bind(this)
	);

	BX.addCustomEvent(
		"Rest:AppLayout:ApplicationInstall",
		this.handleApplicationInstall.bind(this)
	);
};

BX.Intranet.AI.Center.prototype =
{
	/**
	 *
	 * @param {BX.UI.TileList.Tile} tile
	 */
	handleTileClick: function(tile)
	{
		if (tile.id === "alice" || tile.id === "google")
		{
			this.openAssistantApp(tile);
		}
		else if (tile.id === "facecard")
		{
			this.openFaceCard(tile);
		}
		else if (tile.id === "crm-scoring")
		{
			this.openScoring(tile);
		}
		else if (tile.id === "face-tracker")
		{
			this.openFaceTracker(tile);
		}
	},

	openAssistantApp: function(tile)
	{
		if (tile.data['featureEnabled'] === false)
		{
			BX.UI.InfoHelper.show('limit_ai_assistant');
		}
		else if (this.assistantAppId > 0)
		{
			BX.rest.AppLayout.openApplication(this.assistantAppId, { assistantId: tile.id });
		}
		else
		{
			BX.SidePanel.Instance.open("/marketplace/detail/bitrix.assistant/", { cacheable: false });
		}
	},

	openFaceCard: function(tile)
	{
		BX.SidePanel.Instance.open(tile.data.url);
	},

	openFaceTracker: function(tile)
	{
		BX.SidePanel.Instance.open(tile.data.url, { allowChangeHistory: false });
	},

	openScoring: function(tile)
	{
		if (this.mlInstalled)
		{
			BX.SidePanel.Instance.open(tile.data.url, {cacheable: false, width: 840});
		}
		else
		{
			BX.UI.Dialogs.MessageBox.alert(BX.message('INTRANET_AI_CENTER_ML_REQUIRED'));
		}
	},

	handleApplicationInstall: function(installed, eventResult)
	{
		if (installed)
		{
			//eventResult doesn't have an app id
			//this.assistantAppId = eventResult.appId
		}
		else
		{
			this.assistantAppId = 0;
		}
	}
};

})();