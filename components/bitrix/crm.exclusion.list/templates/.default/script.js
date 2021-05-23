;(function ()
{
	BX.namespace('BX.Crm.Exclusion');
	if (BX.Crm.Exclusion.Grid)
	{
		return;
	}

	/**
	 * ExclusionGrid.
	 *
	 */
	function ExclusionGrid()
	{
	}
	ExclusionGrid.prototype.init = function (params)
	{
		this.gridId = params.gridId;
		this.mess = params.mess;

		this.componentName = params.componentName;
		this.signedParameters = params.signedParameters;

		this.initSliderHandlers();
	};
	ExclusionGrid.prototype.openHref = function (href, e)
	{
		e.preventDefault();
		e.stopPropagation();
		BX.SidePanel.Instance.open(href, {cacheable: false});
	};
	ExclusionGrid.prototype.initSliderHandlers = function ()
	{
		var buttonAdd = BX('CRM_EXCLUSION_BUTTON_ADD');
		BX.bind(buttonAdd, 'click', this.openHref.bind(this, buttonAdd.getAttribute('href')));

		top.BX.addCustomEvent(top,'BX.Crm.Exclusion.Import::loaded', this.reloadGrid.bind(this));
	};
	ExclusionGrid.prototype.reloadGrid = function ()
	{
		if (!BX.Main || !BX.Main.gridManager)
		{
			return;
		}

		var grid = BX.Main.gridManager.getById(this.gridId);
		if (!grid)
		{
			return;
		}
		grid.instance.reload();
	};
	ExclusionGrid.prototype.removeFromExclusions = function (exclusionId)
	{
		var self = this;
		this.changeGridLoaderShowing(true);
		BX.ajax.runComponentAction(this.componentName, 'removeExclusion', {
			mode: 'class',
			signedParameters: this.signedParameters,
			data: {
				'exclusionId': exclusionId
			}
		}).then(function () {
			self.reloadGrid();
		});
	};
	ExclusionGrid.prototype.changeGridLoaderShowing = function (isShow)
	{
		var grid = BX.Main.gridManager.getById(this.gridId);
		if (!grid || !grid.instance)
		{
			return;
		}

		isShow ? grid.instance.tableFade() : grid.instance.tableUnfade();
	};

	BX.Crm.Exclusion.Grid = new ExclusionGrid();

})(window);