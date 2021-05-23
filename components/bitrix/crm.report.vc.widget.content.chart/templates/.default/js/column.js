;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');
	var list = [];

	/**
	 * Column.
	 * @param options
	 * @constructor
	 */
	function Column(options)
	{
		this.init(options);
		list.push(this);
	}
	Column.getList = function ()
	{
		return list;
	};
	Column.clear = function ()
	{
		list = [];
	};
	Column.getPrevious = function (instance)
	{
		var prevInstance = null;
		for (var i = 0; i < list.length; i++)
		{
			if (i > 0 && list[i] === instance)
			{
				prevInstance = list[i-1];
			}
		}

		return prevInstance;
	};
	Column.getFirst = function ()
	{
		if (list.length === 0)
		{
			return null;
		}

		var filtered = list.filter(function (column) {
			return column.items.filter(function (item) {
				return item.data.quantity > 0;
			}).length > 0;
		});

		return filtered.length > 0 ? filtered[0] : list[0];
	};
	Column.create = function (options)
	{
		var filtered = list.filter(function (instance) {
			return instance.stage.data.code === options.stage.data.code;
		});
		if (filtered.length === 0)
		{
			return new namespace.Column(options);
		}

		return filtered[0];
	};
	Column.prototype.init = function (options)
	{
		this.stage = options.stage;
		this.items = [];
		this.height = 0;
		this.multiplier = 0;
	};
	Column.prototype.appendItem = function (item)
	{
		this.items.push(item);
		item.setColumn(this);
	};
	Column.prototype.getValue = function ()
	{
		return this.items.reduce(function (prev, item) {
			return prev + item.getValue();
		}, 0);
	};
	Column.prototype.draw = function ()
	{
		var value = this.getValue();
		if (!value)
		{
			return;
		}

		var previousInstance = Column.getPrevious(this);
		var previousValue = previousInstance ? previousInstance.getValue() : 0;

		var previousMultiplier = previousInstance ? previousInstance.multiplier || 1 : 1;
		var minMultiplier = previousMultiplier / 3;
		var defMultiplier = previousMultiplier / 2;
		var multiplier = previousValue ? value / previousValue : 1;
		multiplier *= previousMultiplier;

		multiplier = multiplier > minMultiplier ? multiplier : defMultiplier;
		this.multiplier = multiplier;

		this.items.forEach(function (item) {
			var height = parseInt(multiplier * item.getValue() * 100 / value);
			height = height || 2;
			item.node.style.height = height + '%';

			item.draw();
		}, 0);
	};

	namespace.Column = Column;
})();