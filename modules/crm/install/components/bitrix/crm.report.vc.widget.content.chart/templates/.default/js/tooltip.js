;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');
	var list = [];

	/**
	 * Tooltip.
	 * @constructor
	 */
	function Tooltip(options)
	{
		this.node = options.node;

		list.push(this);

	}
	Tooltip.getList = function ()
	{
		return list;
	};
	Tooltip.clear = function ()
	{
		list = [];
	};
	Tooltip.hideAll = function ()
	{
		list.forEach(function (instance) {
			instance.hide();
		});
	};
	Tooltip.prototype.show = function (top, left, value)
	{
		this.node.style.top = top + 'px';
		this.node.style.left = left + 'px';
		if (left)
		{
			this.node.style.right = 'initial';
		}

		namespace.Helper
			.getNode('tooltip-value', this.node)
			.textContent = value;

		this.node.style.display = '';
	};
	Tooltip.prototype.hide = function ()
	{
		this.node.style.display = 'none';
	};

	namespace.Tooltip = Tooltip;
})();