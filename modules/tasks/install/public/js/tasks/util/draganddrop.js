BX.namespace('Tasks.Util');

/*yes, another drag and drop*/

/*
todo: implement x-dimension
*/

BX.Tasks.Util.DragAndDrop = BX.Tasks.Util.Base.extend({
	options: {
		createFlying: BX.DoNothing,
		autoMarkItemBefore: false,
		autoMarkItemAfter: false,
		autoMarkZoneTopBottom: false
	},
	methods: {

		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Base);

			this.ctrls = {
				zones: [],
				items: [],
				flyingNode: false,
				currentZoneNodeCache: false,
				insertNodeScope: false,
				prevInserNodeScope: false
			};
			this.vars = {
				flyingOverZone: false,
				ghostItem: false,
				dragStartHandleCoords: {x: 0, y: 0}
			};

			// mouse track init
			BX.Tasks.Util.MouseTracker.getCoordinates();

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
					if(childNodes[k].nodeType == 1 && this.checkNodeBound(childNodes[k])) // only bound element nodes
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
				var mc = BX.Tasks.Util.MouseTracker.getCoordinates();
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

			var handle = [node];
			if(typeof parameters != 'undefined' && typeof parameters.handle != 'undefined')
			{
				if(BX.type.isElementNode(parameters.handle))
				{
					handle = [parameters.handle];
				}
				else if("length" in parameters.handle)
				{
					handle = [];
					for(var k = 0; k < parameters.handle.length; k++)
					{
						if(BX.type.isElementNode(parameters.handle[k]))
						{
							handle.push(parameters.handle[k]);
						}
					}
				}
			}

			for(var k = 0; k < handle.length; k++)
			{
				jsDD.registerObject(handle[k]);

				handle[k].onbxdragstart = this.passCtx(this.onDragStart, this);
				handle[k].onbxdrag = BX.throttle(this.onDrag, 300, this);
				handle[k].onbxdragstop = BX.delegate(this.onDragStop, this);
				handle[k].onbxdraghover = BX.delegate(this.onDragHover, this);
				handle[k].onbxdraghout = BX.delegate(this.onDragHout, this);
			}

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

			for(var j in this.ctrls.items)
			{
				if(this.ctrls.items[j].node === node)
				{
					var handle = this.ctrls.items[j].handle;

					for(var k = 0; k < handle.length; k++)
					{
						jsDD.unregisterObject(handle[k]);

						handle[k].onbxdragstart = null;
						handle[k].onbxdrag = null;
						handle[k].onbxdragstop = null;
						handle[k].onbxdraghover = null;
						handle[k].onbxdraghout = null;
					}

					this.ctrls.items[j] = null;
					this.ctrls.items.splice(j, 1);

					return;
				}
			}
		},

		checkNodeBound: function(node)
		{
			if(!BX.type.isElementNode(node))
			{
				return;
			}

			for(var j in this.ctrls.items)
			{
				if(this.ctrls.items[j].node === node)
				{
					return true;
				}
			}

			return false;
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
				var zoneNode = this.getCurrentZone().node;
				this.ctrls.insertNodeScope = this.getCurrentZoneNodeScope();

				this.toggleNodeScopeClass(this.ctrls.prevInserNodeScope, false);
				this.toggleNodeScopeClass(this.ctrls.insertNodeScope, true);

				this.toggleZoneClass(zoneNode, this.ctrls.insertNodeScope);

				this.ctrls.prevInserNodeScope = this.ctrls.insertNodeScope;

				this.fireEvent('item-flying', [zoneNode, this.ctrls.insertNodeScope]);
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
					var nodeScope = {
						before: this.ctrls.insertNodeScope.before,
						after: this.ctrls.insertNodeScope.after,
						zone: zoneNode
					};
					var relocated = nodeScope.before != ghostNode && nodeScope.after != ghostNode;

					var pBefore = new BX.Promise(null, this);
					pBefore.setAutoResolve(true);

					if(relocated)
					{
						this.fireEvent('item-relocation-before', [pBefore, ghostNode, nodeScope]);
					}

					pBefore.then(function(){

						if(relocated)
						{
							if(this.ctrls.insertNodeScope.before === null) // insert at the end
							{
								BX.append(ghostNode, zoneNode);
							}
							else // insert before node
							{
								zoneNode.insertBefore(ghostNode, nodeScope.before);
							}

							this.fireEvent('item-relocated', [ghostNode, zoneNode, nodeScope]);
						}

						this.clearNodeScopeClass();
						this.toggleZoneClass(zoneNode);
						this.makeCurrentZoneOut();

						return true;

					}).then(function(){

						var pAfter = new BX.Promise(null, this);
						pAfter.setAutoResolve(true);

						if(relocated)
						{
							this.fireEvent('item-relocation-after', [pAfter, ghostNode, nodeScope]);
						}

						return pAfter;
					});
				}
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
				this.clearNodeScopeClass();
				this.toggleZoneClass(zone);
			}
		},

		getCurrentZone: function()
		{
			return this.ctrls.zones[this.vars.flyingOverZone];
		},

		clearNodeScopeClass: function()
		{
			this.toggleNodeScopeClass(this.ctrls.insertNodeScope, false);
			this.ctrls.insertNodeScope = false;
		},

		toggleNodeScopeClass: function(scope, way)
		{
			if(scope)
			{
				var autoBefore = this.option('autoMarkItemBefore');
				var autoAfter = this.option('autoMarkItemAfter');

				if(way)
				{
					if(autoAfter && scope.after && !BX.hasClass(scope.after, 'after'))
					{
						BX.addClass(scope.after, 'after')
					}
					if(autoBefore && scope.before && !BX.hasClass(scope.before, 'before'))
					{
						BX.addClass(scope.before, 'before')
					}
				}
				else
				{
					if(autoAfter && scope.after && BX.hasClass(scope.after, 'after'))
					{
						BX.removeClass(scope.after, 'after')
					}
					if(autoBefore && scope.before && BX.hasClass(scope.before, 'before'))
					{
						BX.removeClass(scope.before, 'before')
					}
				}
			}
		},

		toggleZoneClass: function(zoneNode, scope)
		{
			if(!this.option('autoMarkZoneTopBottom'))
			{
				return;
			}

			if(!scope)
			{
				scope = {after: true, before: true};
			}

			if(zoneNode)
			{
				if(!scope.after)
				{
					if(!BX.hasClass(zoneNode, 'top'))
					{
						BX.addClass(zoneNode, 'top');
					}
				}
				else
				{
					if(BX.hasClass(zoneNode, 'top'))
					{
						BX.removeClass(zoneNode, 'top');
					}
				}

				if(!scope.before)
				{
					if(!BX.hasClass(zoneNode, 'bottom'))
					{
						BX.addClass(zoneNode, 'bottom');
					}
				}
				else
				{
					if(BX.hasClass(zoneNode, 'bottom'))
					{
						BX.removeClass(zoneNode, 'bottom');
					}
				}
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

		getItemIndexByHandle: function(search)
		{
			for(var j in this.ctrls.items)
			{
				var handle = this.ctrls.items[j].handle;

				for(var k = 0; k < handle.length; k++)
				{
					if(handle[k] === search)
					{
						return j;
					}
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
			var coords = BX.Tasks.Util.MouseTracker.getCoordinates();

			var x = coords.x - nodePos.left;
			var y = coords.y - nodePos.top;

			return {
				x: x, // > 0 ? x : 0,
				y: y// > 0 ? y : 0
			};
		},

		stickToMouse: function(node)
		{
			var coords = BX.Tasks.Util.MouseTracker.getCoordinates();
			var offs = this.vars.dragStartHandleCoords;

			BX.adjust(node, {style: {
				'top': (coords.y - offs.y)+'px',
				'left': (coords.x - offs.x)+'px'
			}});
		}
	}
});