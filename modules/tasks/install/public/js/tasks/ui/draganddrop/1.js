BX.namespace('Tasks.UI');

/*yes, another drag and drop*/

/*
events:
item-relocated: fired when item is released after drag
*/

/*
todo: 
1) implement an ability to display where the ghost item will insert to while being dragged around
2) while calculating destination over a zone (.getCurrentZoneNodeBefore()), ignore child nodes that were not bound with .bindNode()
3) implement x-dimension
*/

BX.Tasks.UI.DragAndDrop = BX.Tasks.Base.extend({
	options: {
		createFlying: BX.DoNothing
	},
	methods: {

		construct: function()
		{
			this.ctrls = {
				zones: [],
				items: [],
				flyingNode: false,
				currentZoneNodeCache: false,
				insertNodeScope: false
			};
			this.vars = {
				flyingOverZone: false,
				ghostItem: false,
				dragStartHandleCoords: {x: 0, y: 0}
			};

			// mouse track init
			BX.Tasks.UI.MouseTracker.getCoordinates();

			BX.bind(document, 'mousemove', BX.throttle(this.trackMouse, 10, this));
		},

		trackMouse: function()
		{
			if(this.ctrls.flyingNode !== false)
			{
				this.stickToMouse(this.ctrls.flyingNode);
			}
		},

		makeFlyingNode: function(node)
		{
			var baseOnNode = this.option('createFlying').apply(this, [node]);
			if(typeof baseOnNode == 'undefined' || baseOnNode == null)
			{
				baseOnNode = node;
			}

			var flying = BX.clone(baseOnNode);

			BX.addClass(flying, 'flying');
			this.makeAbsolute(flying, baseOnNode);

			this.vars.dragStartHandleCoords = this.getNodeMouseOffset(node);

			BX.append(flying, document.getElementsByTagName('body')[0]);
			this.stickToMouse(flying);

			return flying;
		},

		makeItemGhost: function(k)
		{
			BX.addClass(this.ctrls.items[k].node, 'ghost');
			this.vars.ghostItem = k;
		},

		embodyCurrentItem: function()
		{
			if(this.vars.ghostItem !== false)
			{
				BX.removeClass(this.ctrls.items[this.vars.ghostItem].node, 'ghost');
			}
			this.vars.ghostItem = false;
		},

		makeZoneIn: function(k)
		{
			this.vars.flyingOverZone = k;

			this.makeCurrentZoneCache();

			var zoneNode = this.ctrls.zones[this.vars.flyingOverZone].node;
			BX.addClass(zoneNode, 'over');
		},

		makeCurrentZoneOut: function()
		{
			if(this.vars.flyingOverZone === false)
			{
				return;
			}

			var zoneNode = this.ctrls.zones[this.vars.flyingOverZone].node;

			this.vars.flyingOverZone = false;
			BX.removeClass(zoneNode, 'over');
			this.ctrls.currentZoneNodeCache = false;
		},

		makeCurrentZoneCache: function()
		{
			if(this.ctrls.currentZoneNodeCache === false && this.vars.flyingOverZone !== false)
			{
				var zoneNode = this.ctrls.zones[this.vars.flyingOverZone].node;

				var nodes = [];
				var childNodes = zoneNode.childNodes;
				for(var k in childNodes)
				{
					if(childNodes[k].nodeType == 1) // only element nodes
					{
						var pos = BX.pos(childNodes[k]);
						if(pos.width && pos.height) // with a non-zero size
						{
							nodes.push(childNodes[k]);
						}
					}
				}

				this.ctrls.currentZoneNodeCache = nodes;
			}
		},

		// only by Y currently
		getCurrentZoneNodeScope: function()
		{
			if(this.ctrls.currentZoneNodeCache !== false && this.vars.flyingOverZone !== false && this.vars.ghostItem !== false)
			{
				var mc = BX.Tasks.UI.MouseTracker.getCoordinates();
				var zonePos = BX.pos(this.ctrls.zones[this.vars.flyingOverZone].node);
				var mOffs = {
					x: mc.x - zonePos.left,
					y: mc.y - zonePos.top
				};

				//console.dir('moffs: x:'+mOffs.x+' y:'+mOffs.y);
				//console.dir(zonePos.top+' + '+zonePos.height);

				var prevNode = null;

				for(var k in this.ctrls.currentZoneNodeCache)
				{
					var node = this.ctrls.currentZoneNodeCache[k];
					var nodePos = BX.pos(node);

					var nodeOffs = {
						x: nodePos.left - zonePos.left,
						y: nodePos.top - zonePos.top
					};
					var yHalf = Math.floor(nodePos.height / 2);

					//console.dir('node '+k+' Offs: x:'+nodeOffs.x+' y:'+nodeOffs.y);

					if(mOffs.y < (nodeOffs.y + yHalf))
					{
						//console.dir('insert '+this.vars.ghostItem+' before '+k);
						return {after: prevNode, before: node};
					}

					prevNode = node;
				}

				// insert at the end
				//console.dir('insert '+this.vars.ghostItem+' AT THE END');
				return {after: prevNode, before: null};
			}

			// do not insert node anywhere
			return {after: null, before: null};
		},

		bindDropZone: function(node)
		{
			if(!BX.type.isElementNode(node))
			{
				throw new TypeError('Bad zone to drop to');
			}

			jsDD.registerDest(node);

			this.ctrls.zones.push({
				node: node
			});
		},

		unBindDropZone: function(zone)
		{
			if(!BX.type.isElementNode(zone))
			{
				return;
			}

			jsDD.unregisterDest(zone);

			for(var k in this.ctrls.zones)
			{
				if(this.ctrls.zones[k].node === node)
				{
					this.ctrls.zones[k] = null;
					this.ctrls.zones.splice(k, 1);
				}
			}
		},

		bindNode: function(node, parameters)
		{
			if(!BX.type.isElementNode(node))
			{
				throw new TypeError('Bad item to drag');
			}

			var handle = node;
			if(typeof parameters != 'undefined' && typeof parameters.handle != 'undefined' && BX.type.isElementNode(parameters.handle))
			{
				handle = parameters.handle;
			}

			jsDD.registerObject(handle);

			handle.onbxdragstart = this.passCtx(this.onDragStart, this);
			handle.onbxdrag = BX.throttle(this.onDrag, 300, this);
			handle.onbxdragstop = BX.delegate(this.onDragStop, this);
			handle.onbxdraghover = BX.delegate(this.onDragHover, this);
			handle.onbxdraghout = BX.delegate(this.onDragHout, this);

			this.ctrls.items.push({
				node: node,
				handle: handle
			});
		},

		unBindNode: function(node)
		{
			if(!BX.type.isElementNode(node))
			{
				return;
			}

			for(var k in this.ctrls.items)
			{
				if(this.ctrls.items[k].node === node)
				{
					var handle = this.ctrls.items[k].handle;
					jsDD.unregisterObject(handle);

					handle.onbxdragstart = null;
					handle.onbxdrag = null;
					handle.onbxdragstop = null;
					handle.onbxdraghover = null;
					handle.onbxdraghout = null;

					this.ctrls.items[k] = null;
					this.ctrls.items.splice(k, 1);

					return;
				}
			}
		},

		onDragStart: function(handle)
		{
			var k = this.getItemIndexByHandle(handle);
			if(typeof this.ctrls.items[k])
			{
				this.ctrls.flyingNode = this.makeFlyingNode(this.ctrls.items[k].node);
				this.makeItemGhost(k);
			}
		},

		onDrag: function()
		{
			if(this.vars.flyingOverZone !== false)
			{
				this.ctrls.insertNodeScope = this.getCurrentZoneNodeScope();
			}
		},

		onDragStop: function()
		{
			if(this.vars.flyingOverZone !== false)
			{
				var zoneNode = this.ctrls.zones[this.vars.flyingOverZone].node;
				var ghostNode = this.ctrls.items[this.vars.ghostItem].node;

				if(this.ctrls.insertNodeScope !== false)
				{
					if(this.ctrls.insertNodeScope.before === null) // insert at the end
					{
						BX.append(ghostNode, zoneNode);
					}
					else // insert before node
					{
						zoneNode.insertBefore(ghostNode, this.ctrls.insertNodeScope.before);
					}

					this.fireEvent('item-relocated', [ghostNode, zoneNode, this.ctrls.insertNodeScope]);

					this.ctrls.insertNodeScope = false;
				}

				this.makeCurrentZoneOut();
			}

			this.embodyCurrentItem();
			if(this.ctrls.flying !== false)
			{
				BX.remove(this.ctrls.flyingNode);
			}
		},

		onDragHover: function(zone, x, y)
		{
			if(BX.type.isElementNode(zone))
			{
				var k = this.checkZoneIsLegal(zone);

				if(k !== false)
				{
					this.makeZoneIn(k);
				}
			}
		},

		onDragHout: function(zone, x, y)
		{
			if(BX.type.isElementNode(zone) && (this.checkZoneIsLegal(zone) !== false))
			{
				this.makeCurrentZoneOut();
			}
		},

		checkZoneIsLegal: function(node)
		{
			// zone is legal if it is among registered zones
			for(var k in this.ctrls.zones)
			{
				if(this.ctrls.zones[k].node === node)
				{
					return k;
				}
			}

			return false;
		},

		getItemIndexByHandle: function(handle)
		{
			for(var k in this.ctrls.items)
			{
				if(this.ctrls.items[k].handle === handle)
				{
					return k;
				}
			}

			return false;
		},

		makeAbsolute: function(node, source)
		{
			var style = {
				'position': 'absolute',
				'top': '100px',
				'left': '100px',
				'margin': 0,
				'box-sizing': 'border-box',
				'z-index': 999
			};

			var w = source.offsetWidth; // node.offsetWidth will be equal to zero
			if(w > 0)
			{
				style['width'] = w+'px';
			}

			var h = source.offsetHeight; // node.offsetHeight will be equal to zero
			if(h > 0)
			{
				style['height'] = h+'px';
			}

			BX.adjust(node, {style: style});
		},

		getNodeMouseOffset: function(node)
		{
			var nodePos = BX.pos(node);
			var coords = BX.Tasks.UI.MouseTracker.getCoordinates();

			var x = coords.x - nodePos.left;
			var y = coords.y - nodePos.top;

			return {
				x: x, // > 0 ? x : 0,
				y: y// > 0 ? y : 0
			};
		},

		stickToMouse: function(node)
		{
			var coords = BX.Tasks.UI.MouseTracker.getCoordinates();
			var offs = this.vars.dragStartHandleCoords;

			BX.adjust(node, {style: {
				'top': (coords.y - offs.y)+'px',
				'left': (coords.x - offs.x)+'px'
			}});
		}
	}
});



BX.Tasks.UI.MouseTracker = function(){

	this.coords = {x: 0, y: 0};

	BX.bind(document, 'mousemove', BX.delegate(function(e){
		this.coords = {
			x: e.pageX ? e.pageX :(e.clientX ? e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft : 0),
			y: e.pageY ? e.pageY :(e.clientY ? e.clientY + (document.documentElement.scrollTop || document.body.scrollTop) - document.documentElement.clientTop : 0)
		}
	}, this));
};

BX.Tasks.UI.MouseTracker.getCoordinates = function()
{
	BX.Tasks.UI.MouseTracker.makeInstance();

	return BX.clone(BX.Tasks.Instances.mouseTracker.coords);
}
BX.Tasks.UI.MouseTracker.makeInstance = function()
{
	if(typeof BX.Tasks.Instances == 'undefined')
	{
		BX.Tasks.Instances = {};
	}
	if(typeof BX.Tasks.Instances.mouseTracker == 'undefined')
	{
		BX.Tasks.Instances.mouseTracker = new BX.Tasks.UI.MouseTracker();
	}
}