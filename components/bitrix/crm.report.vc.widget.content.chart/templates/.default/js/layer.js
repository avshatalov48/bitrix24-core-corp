;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');
	var list = [];

	/**
	 * Layer.
	 * @param options
	 * @constructor
	 */
	function Layer(options)
	{
		this.init(options);
		list.push(this);
	}
	Layer.getList = function ()
	{
		return list;
	};
	Layer.clear = function ()
	{
		list = [];
	};
	Layer.create = function (options)
	{
		var filtered = list.filter(function (instance) {
			return instance.source.data.code === options.source.data.code;
		});
		if (filtered.length === 0)
		{
			return new namespace.Layer(options);
		}

		return filtered[0];
	};
	Layer.showTopTooltips = function ()
	{
		var instance = list[list.length - 1];
		if (instance)
		{
			instance.items.forEach(instance.showTooltip.bind(instance, true));
		}
	};
	Layer.prototype.init = function (options)
	{
		this.source = options.source;
		this.items = [];
		this.polygons = [];
	};
	Layer.prototype.appendItem = function (item)
	{
		this.items.push(item);
		item.setLayer(this);
	};
	Layer.prototype.getPreviousItem = function (item)
	{
		for (var i = 1; i < this.items.length; i++)
		{
			if (this.items[i] === item)
			{
				return this.items[i-1];
			}
		}

		return null;
	};
	Layer.prototype.getNextItem = function (item)
	{
		for (var i = 0; i < this.items.length - 1; i++)
		{
			if (this.items[i] === item)
			{
				return this.items[i+1];
			}
		}

		return null;
	};
	Layer.prototype.appendPolygon = function (polygon)
	{
		this.polygons.push(polygon);
	};
	Layer.prototype.draw = function ()
	{
		this.polygons.forEach(function (polygon) {
			polygon.draw();
		}, this);
	};
	Layer.prototype.showTooltips = function ()
	{
		if (list.length <= 1)
		{
			return;
		}

		this.items.forEach(this.showTooltip.bind(this, false));
	};
	Layer.prototype.showTooltip = function (showBetweenItems, item)
	{
		var nextItem = this.getNextItem(item);
		if (!nextItem)
		{
			return;
		}

		var parentPos = BX.pos(item.node.parentNode);
		var top = item.pos.top - parentPos.top;
		var left = item.pos.left - parentPos.left;
		var value = 0;

		if (showBetweenItems)
		{
			top = ((nextItem.pos.top - parentPos.top) + top) / 2 - 20;
			left = ((nextItem.pos.left - parentPos.left) + left + item.pos.width) / 2;

			value = (
				(item.stage.data.quantity
						? nextItem.stage.data.quantity / item.stage.data.quantity
						: 0
				) * 100
			);
			value = value < 10 ? Math.round(value * 10) / 10 : parseInt(value);
		}
		else
		{
			top = top + item.pos.height / 2 - 10;
			left = left + item.pos.width - 10;

			value = (
				(item.data.quantity
						? nextItem.data.quantity / item.data.quantity
						: 0
				) * 100
			);
			value = value < 10 ? Math.round(value * 10) / 10 : parseInt(value);
		}

		item.stage.tooltip.show(top, left, value);
	};
	Layer.prototype.hideTooltips = function ()
	{
		this.items.forEach(function (item) {
			item.stage.tooltip.hide();
		}, this);
	};

	namespace.Layer = Layer;
})();