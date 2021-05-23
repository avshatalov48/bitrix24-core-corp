;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');

	/**
	 * Polygon.
	 * @param options
	 * @constructor
	 */
	function Item(options)
	{
		this.data = options.data;
		this.init(options)
	}
	Item.prototype.init = function (options)
	{
		this.context = BX(options.containerId);
		this.data = options.data;
		this.node = options.node;
		this.source = options.source;
		this.stage = options.stage;
		this.layer = options.layer || null;
		this.column = options.column || null;
		this.pos = [];

		BX.bind(this.node, 'mouseenter', this.onMouseEnter.bind(this));
	};
	Item.prototype.setLayer = function (layer)
	{
		this.layer = layer;
		return this;
	};
	Item.prototype.setColumn = function (column)
	{
		this.column = column;
		return this;
	};
	Item.prototype.getValue = function ()
	{
		var value = parseInt(this.data.quantity);
		return isNaN(value) ? 0 : value;
	};
	Item.prototype.onMouseEnter = function ()
	{
		namespace.Popup.instance().show(this);
		this.layer.showTooltips();
	};
	Item.prototype.onMouseLeave = function ()
	{
		this.layer.hideTooltips();
		namespace.Popup.instance().hide();
	};
	Item.prototype.draw = function ()
	{
		this.pos = BX.pos(this.node);
	};

	namespace.Item = Item;
})();