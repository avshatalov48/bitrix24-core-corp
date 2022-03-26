BX.namespace('BX.Tasks.Grid');

BX.Tasks.GridActions = {
    gridId: null,
	groupSelector: null,
	registeredTimerNodes: {},

    reloadGrid: function()
    {
		if (BX.Bitrix24 && BX.Bitrix24.Slider && BX.Bitrix24.Slider.getLastOpenPage())
		{
			BX.Bitrix24.Slider.destroy(
				BX.Bitrix24.Slider.getLastOpenPage().getUrl()
			);
		}

		var reloadParams = { apply_filter: 'Y', clear_nav: 'Y' };
		var gridObject = BX.Main.gridManager.getById(this.gridId);
		if (gridObject.hasOwnProperty('instance'))
		{
			gridObject.instance.reloadTable('POST', reloadParams);
		}
	}

};

(function() {
	"use strict";

	BX.addCustomEvent("tasksTaskEvent", BX.delegate(function(type, data)
	{
		BX.Tasks.GridActions.reloadGrid();
	}, this));

})();