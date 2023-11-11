;(function() {
	"use strict";

	BX.namespace("BX.Crm");

	BX.Crm.Catalog = function(options)
	{
		if (!BX.Type.isPlainObject(options))
		{
			options = {};
		}

		this.selfFolderUrl = '/crm/catalog/';
		this.gridId = options.gridId || null;

		this.init();
	};

	BX.Crm.Catalog.prototype.init = function()
	{
		if (BX.SidePanel.Instance)
		{
			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: [
							"/crm/catalog/(\\d+)/product/",
							"/crm/catalog/section/(\\d+)/"
						],
						handler: this.adjustSidePanelOpener.bind(this),
					}
				]
			});
		}

		if (!top.window["adminSidePanel"] || !BX.is_subclass_of(top.window["adminSidePanel"], top.BX.adminSidePanel))
		{
			top.window["adminSidePanel"] = new top.BX.adminSidePanel({
				publicMode: true
			});
		}
	};

	BX.Crm.Catalog.prototype.adjustSidePanelOpener = function(event, link)
	{
		if (BX.SidePanel.Instance)
		{
			var isSidePanelParams = (link.url.indexOf("IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER") >= 0);
			if (!isSidePanelParams || (isSidePanelParams && !BX.SidePanel.Instance.getTopSlider()))
			{
				event.preventDefault();

				BX.SidePanel.Instance.open(link.url, {
					allowChangeHistory: true,
					events: {
						onClose() {
							if (this.gridId)
							{
								const grid = BX.Main.gridManager.getInstanceById(this.gridId);
								if (grid)
								{
									grid.reload();
								}
							}
						},
					}
				});
			}
		}
	};

})();
