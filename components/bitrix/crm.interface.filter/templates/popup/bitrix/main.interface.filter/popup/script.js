if(typeof(BX.InterfaceGridFilterPopup) === "undefined")
{
	BX.InterfaceGridFilterPopup = function()
	{
		this._id = "";
		this._settings = {};
		this._popup = null;
	};

	BX.InterfaceGridFilterPopup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
		},
		uninitialize: function()
		{
			if(this._popup)
			{
				this._popup.close();
				this._popup.destroy();
				this._popup = null;
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getId: function()
		{
			return this._id;
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		show : function(anchor)
		{
			if(!BX.type.isDomNode(anchor))
			{
				anchor = BX(this.getSetting("anchorId", ""));
			}

			if(!this._popup)
			{
				this._popup = new BX.PopupWindow(
					this._id,
					anchor,
					{
						content : BX(this.getSetting("filterContainerId", "")),
						offsetLeft : -263 + (anchor ? (anchor.offsetWidth - 10) : 0),
						offsetTop : 3,
						autoHide : false,
						closeByEsc: true,
						//closeIcon: { top: "10px", right: "15px" },
						zIndex: -2,
						events: { onPopupClose: BX.delegate(this._onPopupClose, this) }
					}
				);
			}

			this._popup.show();
			this._adjustWorkarea();
		},
		close: function()
		{
			if(this._popup)
			{
				this._popup.close();
			}
		},
		toggle: function(anchor)
		{
			if(this.isShown())
			{
				this.close();
			}
			else
			{
				this.show(anchor);
			}
		},
		_onPopupClose: function(popupWindow)
		{
			this._adjustWorkarea();
		},
		_adjustWorkarea : function()
		{
			var workarea = BX("workarea", true);
			var workareaHeight = workarea.offsetHeight;
			var filterHeight = this.popup ? this.popup.popupContainer.offsetHeight : 0;

			if (filterHeight > workareaHeight)
				BX("workarea", true).style.paddingBottom = filterHeight - workareaHeight + "px";
			else
				BX("workarea", true).style.cssText = "";
		}
	};
	BX.InterfaceGridFilterPopup.items = {};
	BX.InterfaceGridFilterPopup.create = function(id, settings)
	{
		var self = new BX.InterfaceGridFilterPopup();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
	BX.InterfaceGridFilterPopup.toggle = function(id, anchor)
	{
		var item = this.items[id];
		if(item)
		{

			item.toggle(anchor);
		}
	};
	BX.InterfaceGridFilterPopup.deleteItem = function(id)
	{
		if(this.items[id])
		{
			this.items[id].uninitialize();
			delete this.items[id];
		}
	};
	BX.InterfaceGridFilterPopup.initializeCalendarInterval = function(selector)
	{
		if(typeof(BX.InterfaceGridFilterPopup) === "undefined")
		{
			window.setTimeout(function(){ bxCalendarInterval.OnDateChange(selector); }, 1000);
		}
		else
		{
			bxCalendarInterval.OnDateChange(selector);
		}
	};
}
