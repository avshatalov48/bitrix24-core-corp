;(function ()
{
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');
	/*
	if (namespace.Manager)
	{
		return;
	}
	*/

	{
		function getNode(role, context)
		{
			var nodes = getNodes(role, context);
			return nodes.length > 0 ? nodes[0] : null;
		}
		function getNodes(role, context)
		{
			if (!BX.type.isDomNode(context))
			{
				return [];
			}
			return BX.convert.nodeListToArray(context.querySelectorAll('[data-role="' + role + '"]'));
		}
	}

	/**
	 * View.
	 *
	 */
	function Manager(options)
	{
		namespace.Manager.Instance = this;
		this.init(options);
	}
	Manager.Instance = null;
	Manager.prototype.init = function (options)
	{
		this.context = BX(options.containerId);
		this.data = options.data;

		namespace.Helper.context = this.context;
		namespace.Stage.clear();
		namespace.Layer.clear();
		namespace.Column.clear();
		namespace.Popup.clear();
		namespace.Tooltip.clear();

		var graphNode = getNode('graph', this.context);
		namespace.Popup.instance({
			content: getNode('popup', this.context)
		});
		BX.bind(graphNode, 'mouseleave', function () {
			namespace.Popup.instance().hide();
			namespace.Layer.showTopTooltips();
		});

		this.initStructure();

		BX.bind(window, 'resize', this.draw.bind(this));

		BX.UI.Hint.initByClassName();
	};
	Manager.prototype.getLayers = function ()
	{
		return namespace.Layer.getList();
	};
	Manager.prototype.getColumns = function ()
	{
		return namespace.Column.getList();
	};
	Manager.prototype.draw = function ()
	{
		this.getColumns().forEach(function (column) {
			column.draw();
		}, this);
		this.getLayers().forEach(function (layer) {
			layer.draw();
		}, this);

		namespace.Layer.showTopTooltips();
	};
	Manager.prototype.initStructure = function ()
	{
		this.data.dict.stages.forEach(function (stageCode) {
			this.data.dict.sources.forEach(function (sourceCode) {
				this.initItem(this.data.items[stageCode][sourceCode]);
			}, this);
		}, this);

		this.getLayers().forEach(this.initPolygons, this);

		this.draw();
	};
	Manager.prototype.initItem = function (itemData)
	{
		var stage = namespace.Stage.create({
			data: this.data.stages[itemData.stage]
		});
		var source = namespace.Source.create({
			data: this.data.sources[itemData.source]
		});
		var item = new namespace.Item({
			data: itemData,
			source: source,
			stage: stage,
			node: getNode('items/' + itemData.source + '/' + itemData.stage, this.context)
		});

		namespace.Layer
			.create({
				source: source
			})
			.appendItem(item);

		namespace.Column
			.create({
				stage: stage
			})
			.appendItem(item);
	};
	Manager.prototype.initPolygons = function (layer)
	{
		for (var i = 0; i < layer.items.length; i++)
		{
			var leftItem = layer.items[i];
			var rightItem = layer.items[i+1];

			if (!leftItem || !rightItem)
			{
				return;
			}

			var polygon = new namespace.Polygon({
				node: getNode('polygons/' + leftItem.data.source + '/' + leftItem.data.stage, this.context),
				source: leftItem.source,
				stage: leftItem.stage,
				leftItem: leftItem,
				rightItem: rightItem
			});
			layer.appendPolygon(polygon);
		}
	};


	namespace.Manager = Manager;
})();