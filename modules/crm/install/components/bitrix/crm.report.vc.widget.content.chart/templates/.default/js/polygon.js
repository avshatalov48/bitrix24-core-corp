;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');

	/**
	 * Polygon.
	 * @param options
	 * @constructor
	 */
	function Polygon(options)
	{
		this.init(options)
	}
	Polygon.prototype.init = function (options)
	{
		this.context = options.context;
		this.leftItem = options.leftItem;
		this.rightItem = options.rightItem;
		this.node = options.node;
		this.source = options.source;
		this.stage = options.stage;

		if (this.rightItem)
		{
			BX.bind(this.node, 'mouseenter', this.rightItem.onMouseEnter.bind(this.rightItem));
		}
	};
	Polygon.prototype.draw = function ()
	{
		var prev = BX.pos(this.leftItem.node);
		var next = BX.pos(this.rightItem.node);
		var cont = BX.pos(this.node.parentNode);

		var magic = 3; // border weight
		var coordinates = [
			[ // leftTop
				0,
				prev.top - cont.top + magic
			],
			[ // leftBottom
				0,
				prev.top - cont.top + prev.height
			],
			[ // rightBottom
				cont.width,
				next.top - cont.top + next.height
			],
			[ // rightTop
				cont.width,
				next.top - cont.top + magic
			]
		];

		this.node.setAttribute('points', coordinates.map(function (coordinate) {
			return coordinate.join(' ');
		}).join(', '));
		this.node.style.fill = this.node.style.stroke = this.source.data.color;
	};

	namespace.Polygon = Polygon;
})();