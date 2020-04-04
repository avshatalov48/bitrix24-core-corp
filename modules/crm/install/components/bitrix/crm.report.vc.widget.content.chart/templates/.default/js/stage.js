;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');
	var list = [];

	/**
	 * Stage.
	 * @param options
	 * @constructor
	 */
	function Stage(options)
	{
		this.data = options.data;
		this.init(options);
		list.push(this);
	}
	Stage.clear = function ()
	{
		list = [];
	};
	Stage.create = function (options)
	{
		var data = options.data || {};
		var filtered = list.filter(function (instance) {
			return instance.data.code === data.code;
		});
		if (filtered.length === 0)
		{
			return new namespace.Stage(options);
		}

		return filtered[0];
	};
	Stage.prototype.init = function (options)
	{
		this.data = options.data || {};
		this.tooltip = new namespace.Tooltip({
			node: namespace.Helper.getNode('tooltips/' + this.data.code)
		});
	};

	Stage.prototype.showTooltip = function ()
	{
		this.tooltip.show();
	};
	Stage.prototype.hideTooltip = function ()
	{
		this.tooltip.hide();
	};

	namespace.Stage = Stage;
})();