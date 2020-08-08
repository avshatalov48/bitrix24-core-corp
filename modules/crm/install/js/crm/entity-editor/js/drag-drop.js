BX.namespace("BX.Crm");

//region D&D
if(typeof BX.Crm.EditorDragScope === "undefined")
{
	BX.Crm.EditorDragScope =
		{
			intermediate: 0,
			parent: 1,
			form: 2,
			getDefault: function()
			{
				return this.form;
			}
		};
}

if(typeof BX.Crm.EditorDragObjectType === "undefined")
{
	BX.Crm.EditorDragObjectType =
		{
			intermediate: "",
			field: "F",
			section: "S"
		};
}

if(typeof(BX.Crm.EditorDragItem) === "undefined")
{
	BX.Crm.EditorDragItem = function()
	{
	};
	BX.Crm.EditorDragItem.prototype =
		{
			getType: function()
			{
				return BX.Crm.EditorDragObjectType.intermediate;
			},
			getContextId: function()
			{
				return "";
			},
			createGhostNode: function()
			{
				return null;
			},
			processDragStart: function()
			{
			},
			processDragPositionChange: function(pos, ghostRect)
			{
			},
			processDragStop: function()
			{
			}
		};
}

if(typeof(BX.Crm.EditorFieldDragItem) === "undefined")
{
	BX.Crm.EditorFieldDragItem = function()
	{
		BX.Crm.EditorFieldDragItem.superclass.constructor.apply(this);
		this._scope = BX.Crm.EditorDragScope.undefined;
		this._control = null;
		this._contextId = "";
	};
	BX.extend(BX.Crm.EditorFieldDragItem, BX.Crm.EditorDragItem);
	BX.Crm.EditorFieldDragItem.prototype.initialize = function(settings)
	{
		this._control = BX.prop.get(settings, "control");
		if(!this._control)
		{
			throw "Crm.EditorFieldDragItem: The 'control' parameter is not defined in settings or empty.";
		}
		this._scope = BX.prop.getInteger(settings, "scope", BX.Crm.EditorDragScope.getDefault());
		this._contextId = BX.prop.getString(settings, "contextId", "");
	};
	BX.Crm.EditorFieldDragItem.prototype.getType = function()
	{
		return BX.Crm.EditorDragObjectType.field;
	};
	BX.Crm.EditorFieldDragItem.prototype.getControl = function()
	{
		return this._control;
	};
	BX.Crm.EditorFieldDragItem.prototype.getContextId = function()
	{
		return this._contextId !== "" ? this._contextId : BX.Crm.EditorFieldDragItem.contextId;
	};
	BX.Crm.EditorFieldDragItem.prototype.createGhostNode = function()
	{
		return this._control.createGhostNode();
	};
	BX.Crm.EditorFieldDragItem.prototype.processDragStart = function()
	{
		window.setTimeout(
			function()
			{
				//Ensure Field drag controllers are enabled.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorFieldDragItem.contextId, true);
				//Disable Section drag controllers for the avoidance of collisions.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorSectionDragItem.contextId, false);
				//Refresh all drag&drop destination areas.
				BX.Crm.EditorDragContainerController.refreshAll();
			}
		);
		this._control.getWrapper().style.opacity = "0.2";
	};
	BX.Crm.EditorFieldDragItem.prototype.processDragPositionChange = function(pos, ghostRect)
	{
		//var startY = pos.y;

		var parentPos = this._scope === BX.Crm.EditorDragScope.parent
			? this._control.getParentPosition()
			: this._control.getRootContainerPosition();

		if(pos.y < parentPos.top)
		{
			pos.y = parentPos.top;
		}
		if((pos.y + ghostRect.height) > parentPos.bottom)
		{
			pos.y = parentPos.bottom - ghostRect.height;
		}
		if(pos.x < parentPos.left)
		{
			pos.x = parentPos.left;
		}
		if((pos.x + ghostRect.width) > parentPos.right)
		{
			pos.x = parentPos.right - ghostRect.width;
		}

		//var finishY = pos.y;
		//console.log("parent: %d start: %d final: %d", parentPos.top, startY, finishY);
	};
	BX.Crm.EditorFieldDragItem.prototype.processDragStop = function()
	{
		window.setTimeout(
			function()
			{
				//Returning Section drag controllers to work.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorSectionDragItem.contextId, true);
				//Refresh all drag&drop destination areas.
				BX.Crm.EditorDragContainerController.refreshAll();
			}
		);
		this._control.getWrapper().style.opacity = "1";
	};
	BX.Crm.EditorFieldDragItem.contextId = "editor_field";
	BX.Crm.EditorFieldDragItem.create = function(settings)
	{
		var self = new BX.Crm.EditorFieldDragItem();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorSectionDragItem) === "undefined")
{
	BX.Crm.EditorSectionDragItem = function()
	{
		BX.Crm.EditorSectionDragItem.superclass.constructor.apply(this);
		this._control = null;
	};
	BX.extend(BX.Crm.EditorSectionDragItem, BX.Crm.EditorDragItem);
	BX.Crm.EditorSectionDragItem.prototype.initialize = function(settings)
	{
		this._control = BX.prop.get(settings, "control");
		if(!this._control)
		{
			throw "Crm.EditorSectionDragItem: The 'control' parameter is not defined in settings or empty.";
		}
	};
	BX.Crm.EditorSectionDragItem.prototype.getType = function()
	{
		return BX.Crm.EditorDragObjectType.section;
	};
	BX.Crm.EditorSectionDragItem.prototype.getControl = function()
	{
		return this._control;
	};
	BX.Crm.EditorSectionDragItem.prototype.getContextId = function()
	{
		return BX.Crm.EditorSectionDragItem.contextId;
	};
	BX.Crm.EditorSectionDragItem.prototype.createGhostNode = function()
	{
		return this._control.createGhostNode();
	};
	BX.Crm.EditorSectionDragItem.prototype.processDragStart = function()
	{
		BX.addClass(document.body, "crm-entity-widgets-drag");

		var control = this._control;
		control.getWrapper().style.opacity = "0.2";
		window.setTimeout(
			function()
			{
				//Ensure Section drag controllers are enabled.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorSectionDragItem.contextId, true);
				//Disable Field drag controllers for the avoidance of collisions.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorFieldDragItem.contextId, false);
				//Refresh all drag&drop destination areas.
				BX.Crm.EditorDragContainerController.refreshAll();

				window.setTimeout(
					function()
					{
						var firstControl = control.getSiblingByIndex(0);
						if(firstControl !== null && firstControl !== control)
						{
							firstControl.getWrapper().scrollIntoView();
						}
					},
					200
				);
			}
		);
	};
	BX.Crm.EditorSectionDragItem.prototype.processDragStop = function()
	{
		BX.removeClass(document.body, "crm-entity-widgets-drag");
		window.setTimeout(
			function()
			{
				//Returning Field drag controllers to work.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorFieldDragItem.contextId, true);
				//Refresh all drag&drop destination areas.
				BX.Crm.EditorDragContainerController.refreshAll();
			}
		);

		var control = this._control;
		control.getWrapper().style.opacity = "1";
		window.setTimeout(
			function()
			{
				control.getWrapper().scrollIntoView();
			},
			150
		);
	};
	BX.Crm.EditorSectionDragItem.contextId = "editor_section";
	BX.Crm.EditorSectionDragItem.create = function(settings)
	{
		var self = new BX.Crm.EditorSectionDragItem();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorDragItemController) === "undefined")
{
	BX.Crm.EditorDragItemController = function()
	{
		BX.Crm.EditorDragItemController.superclass.constructor.apply(this);
		this._charge = null;
		this._preserveDocument = true;
	};
	BX.extend(BX.Crm.EditorDragItemController, BX.CrmCustomDragItem);
	BX.Crm.EditorDragItemController.prototype.doInitialize = function()
	{
		this._charge = this.getSetting("charge");
		if(!this._charge)
		{
			throw "Crm.EditorDragItemController: The 'charge' parameter is not defined in settings or empty.";
		}

		this._startNotifier = BX.CrmNotifier.create(this);
		this._stopNotifier = BX.CrmNotifier.create(this);

		this._ghostOffset = { x: 0, y: -40 };
	};
	BX.Crm.EditorDragItemController.prototype.addStartListener = function(listener)
	{
		this._startNotifier.addListener(listener);
	};
	BX.Crm.EditorDragItemController.prototype.removeStartListener = function(listener)
	{
		this._startNotifier.removeListener(listener);
	};
	BX.Crm.EditorDragItemController.prototype.addStopListener = function(listener)
	{
		this._stopNotifier.addListener(listener);
	};
	BX.Crm.EditorDragItemController.prototype.removeStopListener = function(listener)
	{
		this._stopNotifier.removeListener(listener);
	};
	BX.Crm.EditorDragItemController.prototype.getCharge = function()
	{
		return this._charge;
	};
	BX.Crm.EditorDragItemController.prototype.createGhostNode = function()
	{
		if(this._ghostNode)
		{
			return this._ghostNode;
		}

		this._ghostNode = this._charge.createGhostNode();
		document.body.appendChild(this._ghostNode);
	};
	BX.Crm.EditorDragItemController.prototype.getGhostNode = function()
	{
		return this._ghostNode;
	};
	BX.Crm.EditorDragItemController.prototype.removeGhostNode = function()
	{
		if(this._ghostNode)
		{
			document.body.removeChild(this._ghostNode);
			this._ghostNode = null;
		}
	};
	BX.Crm.EditorDragItemController.prototype.getContextId = function()
	{
		return this._charge.getContextId();
	};
	BX.Crm.EditorDragItemController.prototype.getContextData = function()
	{
		return ({ contextId: this._charge.getContextId(), charge: this._charge });
	};
	BX.Crm.EditorDragItemController.prototype.processDragStart = function()
	{
		BX.Crm.EditorDragItemController.current = this;
		this._charge.processDragStart();
		BX.Crm.EditorDragContainerController.refresh(this._charge.getContextId());

		this._startNotifier.notify([]);
	};
	BX.Crm.EditorDragItemController.prototype.processDrag = function(x, y)
	{
	};
	BX.Crm.EditorDragItemController.prototype.processDragPositionChange = function(pos)
	{
		this._charge.processDragPositionChange(pos, BX.pos(this.getGhostNode()));
	};
	BX.Crm.EditorDragItemController.prototype.processDragStop = function()
	{
		BX.Crm.EditorDragItemController.current = null;
		this._charge.processDragStop();
		BX.Crm.EditorDragContainerController.refreshAfter(this._charge.getContextId(), 300);

		this._stopNotifier.notify([]);
	};
	BX.Crm.EditorDragItemController.current = null;
	BX.Crm.EditorDragItemController.create = function(id, settings)
	{
		var self = new BX.Crm.EditorDragItemController();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorDragContainer) === "undefined")
{
	BX.Crm.EditorDragContainer = function()
	{
	};
	BX.Crm.EditorDragContainer.prototype =
		{
			getContextId: function()
			{
				return "";
			},
			getPriority: function()
			{
				return 100;
			},
			hasPlaceHolder: function()
			{
				return false;
			},
			createPlaceHolder: function(index)
			{
				return null;
			},
			getPlaceHolder: function()
			{
				return null;
			},
			removePlaceHolder: function()
			{
			},
			getChildNodes: function()
			{
				return [];
			},
			getChildNodeCount: function()
			{
				return 0;
			}
		}
}

if(typeof(BX.Crm.EditorFieldDragContainer) === "undefined")
{
	BX.Crm.EditorFieldDragContainer = function()
	{
		BX.Crm.EditorFieldDragContainer.superclass.constructor.apply(this);
		this._section = null;
		this._context = "";
	};
	BX.extend(BX.Crm.EditorFieldDragContainer, BX.Crm.EditorDragContainer);
	BX.Crm.EditorFieldDragContainer.prototype.initialize = function(settings)
	{
		this._section = BX.prop.get(settings, "section");
		if(!this._section)
		{
			throw "Crm.EditorSectionDragContainer: The 'section' parameter is not defined in settings or empty.";
		}

		this._context = BX.prop.getString(settings, "context", "");
	};
	BX.Crm.EditorFieldDragContainer.prototype.getSection = function()
	{
		return this._section;
	};
	BX.Crm.EditorFieldDragContainer.prototype.getContextId = function()
	{
		return this._context !== "" ? this._context : BX.Crm.EditorFieldDragItem.contextId;
	};
	BX.Crm.EditorFieldDragContainer.prototype.getPriority = function()
	{
		return 10;
	};
	BX.Crm.EditorFieldDragContainer.prototype.hasPlaceHolder = function()
	{
		return this._section.hasPlaceHolder();
	};
	BX.Crm.EditorFieldDragContainer.prototype.createPlaceHolder = function(index)
	{
		return this._section.createPlaceHolder(index);
	};
	BX.Crm.EditorFieldDragContainer.prototype.getPlaceHolder = function()
	{
		return this._section.getPlaceHolder();
	};
	BX.Crm.EditorFieldDragContainer.prototype.removePlaceHolder = function()
	{
		this._section.removePlaceHolder();
	};
	BX.Crm.EditorFieldDragContainer.prototype.getChildNodes = function()
	{
		var nodes = [];
		var items = this._section.getChildren();
		for(var i = 0, length = items.length; i < length; i++)
		{
			nodes.push(items[i].getWrapper());
		}
		return nodes;
	};
	BX.Crm.EditorFieldDragContainer.prototype.getChildNodeCount = function()
	{
		return this._section.getChildCount();
	};
	BX.Crm.EditorFieldDragContainer.create = function(settings)
	{
		var self = new BX.Crm.EditorFieldDragContainer();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorSectionDragContainer) === "undefined")
{
	BX.Crm.EditorSectionDragContainer = function()
	{
		BX.Crm.EditorSectionDragContainer.superclass.constructor.apply(this);
		this._editor = null;
	};
	BX.extend(BX.Crm.EditorSectionDragContainer, BX.Crm.EditorDragContainer);
	BX.Crm.EditorSectionDragContainer.prototype.initialize = function(settings)
	{
		this._editor = BX.prop.get(settings, "editor");
		if(!this._editor)
		{
			throw "Crm.EditorSectionDragContainer: The 'editor' parameter is not defined in settings or empty.";
		}
	};
	BX.Crm.EditorSectionDragContainer.prototype.getEditor = function()
	{
		return this._editor;
	};
	BX.Crm.EditorSectionDragContainer.prototype.getContextId = function()
	{
		return BX.Crm.EditorSectionDragItem.contextId;
	};
	BX.Crm.EditorSectionDragContainer.prototype.getPriority = function()
	{
		return 20;
	};
	BX.Crm.EditorSectionDragContainer.prototype.hasPlaceHolder = function()
	{
		return this._editor.hasPlaceHolder();
	};
	BX.Crm.EditorSectionDragContainer.prototype.createPlaceHolder = function(index)
	{
		return this._editor.createPlaceHolder(index);
	};
	BX.Crm.EditorSectionDragContainer.prototype.getPlaceHolder = function()
	{
		return this._editor.getPlaceHolder();
	};
	BX.Crm.EditorSectionDragContainer.prototype.removePlaceHolder = function()
	{
		this._editor.removePlaceHolder();
	};
	BX.Crm.EditorSectionDragContainer.prototype.getChildNodes = function()
	{
		var nodes = [];
		var items = this._editor.getControls();
		for(var i = 0, length = items.length; i < length; i++)
		{
			nodes.push(items[i].getWrapper());
		}
		return nodes;
	};
	BX.Crm.EditorSectionDragContainer.prototype.getChildNodeCount = function()
	{
		return this._editor.getControlCount();
	};
	BX.Crm.EditorSectionDragContainer.create = function(settings)
	{
		var self = new BX.Crm.EditorSectionDragContainer();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorDragContainerController) === "undefined")
{
	BX.Crm.EditorDragContainerController = function()
	{
		BX.Crm.EditorDragContainerController.superclass.constructor.apply(this);
		this._charge = null;
	};
	BX.extend(BX.Crm.EditorDragContainerController, BX.CrmCustomDragContainer);
	BX.Crm.EditorDragContainerController.prototype.doInitialize = function()
	{
		this._charge = this.getSetting("charge");
		if(!this._charge)
		{
			throw "Crm.EditorDragContainerController: The 'charge' parameter is not defined in settings or empty.";
		}
	};
	BX.Crm.EditorDragContainerController.prototype.getCharge = function()
	{
		return this._charge;
	};
	BX.Crm.EditorDragContainerController.prototype.createPlaceHolder = function(pos)
	{
		var ghostRect = BX.pos(BX.Crm.EditorDragItemController.current.getGhostNode());
		var ghostTop = ghostRect.top, ghostBottom = ghostRect.top + 40;
		var ghostMean = Math.floor((ghostTop + ghostBottom) / 2);

		var rect, mean;
		var placeholder = this._charge.getPlaceHolder();
		if(placeholder)
		{
			rect = placeholder.getPosition();
			mean = Math.floor((rect.top + rect.bottom) / 2);
			if(
				(ghostTop <= rect.bottom && ghostTop >= rect.top) ||
				(ghostBottom >= rect.top && ghostBottom <= rect.bottom) ||
				Math.abs(ghostMean - mean) <= 8
			)
			{
				if(!placeholder.isActive())
				{
					placeholder.setActive(true);
				}
				return;
			}
		}

		var nodes = this._charge.getChildNodes();
		for(var i = 0; i < nodes.length; i++)
		{
			rect = BX.pos(nodes[i]);
			mean = Math.floor((rect.top + rect.bottom) / 2);
			if(
				(ghostTop <= rect.bottom && ghostTop >= rect.top) ||
				(ghostBottom >= rect.top && ghostBottom <= rect.bottom) ||
				Math.abs(ghostMean - mean) <= 8
			)
			{
				this._charge.createPlaceHolder((ghostMean - mean) <= 0 ? i : (i + 1)).setActive(true);
				return;
			}
		}

		this._charge.createPlaceHolder(-1).setActive(true);
		this.refresh();
	};
	BX.Crm.EditorDragContainerController.prototype.removePlaceHolder = function()
	{
		if(!this._charge.hasPlaceHolder())
		{
			return;
		}

		if(this._charge.getChildNodeCount() > 0)
		{
			this._charge.removePlaceHolder();
		}
		else
		{
			this._charge.getPlaceHolder().setActive(false);
		}
		this.refresh();
	};
	BX.Crm.EditorDragContainerController.prototype.getContextId = function()
	{
		return this._charge.getContextId();
	};
	BX.Crm.EditorDragContainerController.prototype.getPriority = function()
	{
		return this._charge.getPriority();
	};
	BX.Crm.EditorDragContainerController.prototype.isAllowedContext = function(contextId)
	{
		return contextId === this._charge.getContextId();
	};
	BX.Crm.EditorDragContainerController.refresh = function(contextId)
	{
		for(var k in this.items)
		{
			if(!this.items.hasOwnProperty(k))
			{
				continue;
			}
			var item = this.items[k];
			if(item.getContextId() === contextId)
			{
				item.refresh();
			}
		}
	};
	BX.Crm.EditorDragContainerController.refreshAfter = function(contextId, interval)
	{
		interval = parseInt(interval);
		if(interval > 0)
		{
			window.setTimeout(function() { BX.Crm.EditorDragContainerController.refresh(contextId); }, interval);
		}
		else
		{
			this.refresh(contextId);
		}
	};
	BX.Crm.EditorDragContainerController.refreshAll = function()
	{
		for(var k in this.items)
		{
			if(!this.items.hasOwnProperty(k))
			{
				continue;
			}
			this.items[k].refresh();
		}
	};
	BX.Crm.EditorDragContainerController.enable = function(contextId, enable)
	{
		for(var k in this.items)
		{
			if(!this.items.hasOwnProperty(k))
			{
				continue;
			}
			var item = this.items[k];
			if(item.getContextId() === contextId)
			{
				item.enable(enable);
			}
		}
	};
	BX.Crm.EditorDragContainerController.items = {};
	BX.Crm.EditorDragContainerController.create = function(id, settings)
	{
		var self = new BX.Crm.EditorDragContainerController();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.Crm.EditorDragPlaceholder) === "undefined")
{
	BX.Crm.EditorDragPlaceholder = function()
	{
		this._settings = null;
		this._container = null;
		this._node = null;
		this._isDragOver = false;
		this._isActive = false;
		this._index = -1;
		this._timeoutId = null;
	};
	BX.Crm.EditorDragPlaceholder.prototype =
		{
			initialize: function(settings)
			{
				this._settings = settings ? settings : {};
				this._container = this.getSetting("container", null);

				this._isActive = this.getSetting("isActive", false);
				this._index = parseInt(this.getSetting("index", -1));
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
			},
			getContainer: function()
			{
				return this._container;
			},
			setContainer: function(container)
			{
				this._container = container;
			},
			isDragOver: function()
			{
				return this._isDragOver;
			},
			isActive: function()
			{
				return this._isActive;
			},
			setActive: function(active, interval)
			{
				if(this._timeoutId !== null)
				{
					window.clearTimeout(this._timeoutId);
					this._timeoutId = null;
				}

				interval = parseInt(interval);
				if(interval > 0)
				{
					var self = this;
					window.setTimeout(function(){ if(self._timeoutId === null) return; self._timeoutId = null; self.setActive(active, 0); }, interval);
					return;
				}

				active = !!active;
				if(this._isActive === active)
				{
					return;
				}

				this._isActive = active;
				if(this._node)
				{
					//this._node.className = active ? "crm-lead-header-drag-zone-bd" : "crm-lead-header-drag-zone-bd-inactive";
				}
			},
			getIndex: function()
			{
				return this._index;
			},
			prepareNode: function()
			{
				return null;
			},
			layout: function()
			{
				this._node = this.prepareNode();
				var anchor = this.getSetting("anchor", null);
				if(anchor)
				{
					this._container.insertBefore(this._node, anchor);
				}
				else
				{
					this._container.appendChild(this._node);
				}

				BX.bind(this._node, "dragover", BX.delegate(this._onDragOver, this));
				BX.bind(this._node, "dragleave", BX.delegate(this._onDragLeave, this));
			},
			clearLayout: function()
			{
				if(this._node)
				{
					// this._node = BX.remove(this._node);
					this._node.style.height = 0;
					setTimeout(BX.proxy(function (){this._node = BX.remove(this._node);}, this), 100);
				}
			},
			getPosition: function()
			{
				return BX.pos(this._node);
			},
			_onDragOver: function(e)
			{
				e = e || window.event;
				this._isDragOver = true;
				return BX.eventReturnFalse(e);
			},
			_onDragLeave: function(e)
			{
				e = e || window.event;
				this._isDragOver = false;
				return BX.eventReturnFalse(e);
			}
		}
}

if(typeof(BX.Crm.EditorDragFieldPlaceholder) === "undefined")
{
	BX.Crm.EditorDragFieldPlaceholder = function()
	{
	};

	BX.extend(BX.Crm.EditorDragFieldPlaceholder, BX.Crm.EditorDragPlaceholder);
	BX.Crm.EditorDragFieldPlaceholder.prototype.prepareNode = function()
	{
		return BX.create("div", { attrs: { className: "crm-entity-widget-content-block-place" } });
	};
	BX.Crm.EditorDragFieldPlaceholder.create = function(settings)
	{
		var self = new BX.Crm.EditorDragFieldPlaceholder();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorDragSectionPlaceholder) === "undefined")
{
	BX.Crm.EditorDragSectionPlaceholder = function()
	{
	};

	BX.extend(BX.Crm.EditorDragSectionPlaceholder, BX.Crm.EditorDragPlaceholder);
	BX.Crm.EditorDragSectionPlaceholder.prototype.prepareNode = function()
	{
		return BX.create("div", { attrs: { className: "crm-entity-card-widget crm-entity-card-widget-place" } });
	};
	BX.Crm.EditorDragSectionPlaceholder.create = function(settings)
	{
		var self = new BX.Crm.EditorDragSectionPlaceholder();
		self.initialize(settings);
		return self;
	};
}

//endregion
