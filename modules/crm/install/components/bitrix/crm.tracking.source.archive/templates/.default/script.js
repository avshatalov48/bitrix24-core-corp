;(function ()
{
	var namespace = BX.namespace('BX.Crm.Tracking.Source');
	if (namespace.Archive)
	{
		return;
	}

	/**
	 * Archive.
	 *
	 */
	function Archive()
	{
	}
	Archive.prototype.init = function (params)
	{
		this.gridId = params.gridId;
		this.mess = params.mess;

		this.componentName = params.componentName;
		this.signedParameters = params.signedParameters;
	};
	Archive.prototype.unarchive = function (sourceId)
	{
		this.changeGridLoaderShowing(true);
		BX.ajax.runComponentAction(this.componentName, 'unarchive', {
			mode: 'class',
			signedParameters: this.signedParameters,
			data: {
				'sourceId': sourceId
			}
		}).then(function () {
			this.reloadGrid();
		}.bind(this));
	};
	Archive.prototype.getGridInstance = function ()
	{
		var grid = BX.Main.gridManager.getById(this.gridId);
		if (!grid || !grid.instance)
		{
			return null;
		}

		return grid.instance;
	};
	Archive.prototype.changeGridLoaderShowing = function (isShow)
	{
		var gridInstance = this.getGridInstance();
		if (!gridInstance)
		{
			return;
		}

		isShow ? gridInstance.tableFade() : gridInstance.tableUnfade();
	};
	Archive.prototype.reloadGrid = function ()
	{
		var gridInstance = this.getGridInstance();
		if (!gridInstance)
		{
			return;
		}

		gridInstance.reload();
	};

	namespace.Archive = new Archive();

})(window);