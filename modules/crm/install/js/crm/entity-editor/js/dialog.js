BX.namespace("BX.Crm");

//region DIALOG
if(typeof BX.Crm.EditorDialogButton === "undefined")
{
	BX.Crm.EditorDialogButton = function()
	{
		this._id = "";
		this._type = BX.Crm.DialogButtonType.undefined;
		this._settings = {};
		this._dialog = null;
		this._keyPressHandler = BX.delegate(this.onKeyPress, this);
	};
	BX.Crm.EditorDialogButton.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._type = BX.prop.getInteger(this._settings, "type", BX.Crm.DialogButtonType.undefined);
				this._dialog = BX.prop.get(this._settings, "dialog", null);
			},
			bind: function()
			{
				if(this._type === BX.Crm.DialogButtonType.accept)
				{
					BX.bind(document, "keydown", this._keyPressHandler);
				}
			},
			unbind: function()
			{
				if(this._type === BX.Crm.DialogButtonType.accept)
				{
					BX.unbind(document, "keydown", this._keyPressHandler);
				}
			},
			onKeyPress: function(e)
			{
				if(this._type !== BX.Crm.DialogButtonType.accept)
				{
					return;
				}

				e = e || window.event;
				if (e.keyCode === 13)
				{
					//Enter key
					this.onClick(e);
				}
			},
			getId: function()
			{
				return this._id;
			},
			getDialog: function()
			{
				return this._dialog;
			},
			prepareContent: function()
			{
				if(this._type === BX.Crm.DialogButtonType.accept)
				{
					return (
						new BX.UI.SaveButton(
							{
								text : BX.prop.getString(this._settings, "text", this._id),
								events: { click: BX.delegate(this.onClick, this) }
							}
						)
					);
				}
				else if(this._type === BX.Crm.DialogButtonType.cancel)
				{
					return (
						new BX.UI.CancelButton(
							{
								text : BX.prop.getString(this._settings, "text", this._id),
								events: { click: BX.delegate(this.onClick, this) }
							}
						)
					);
				}
				else
				{
					return (
						new BX.UI.Button(
							{
								text : BX.prop.getString(this._settings, "text", this._id),
								events: { click: BX.delegate(this.onClick, this) }
							}
						)
					);
				}
			},
			onClick: function(e)
			{
				var callback = BX.prop.getFunction(this._settings, "callback", null);
				if(callback)
				{
					callback(this);
				}
			}
		};
	BX.Crm.EditorDialogButton.create = function(id, settings)
	{
		var self = new BX.Crm.EditorDialogButton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EditorAuxiliaryDialog === "undefined")
{
	BX.Crm.EditorAuxiliaryDialog = function()
	{
		this._id = "";
		this._settings = {};

		this._popup = null;
		this._buttons = null;
	};
	BX.Crm.EditorAuxiliaryDialog.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
			},
			getSetting: function(name, defaultValue)
			{
				return BX.prop.get(this._settings, name, defaultValue);
			},
			getId: function()
			{
				return this._id;
			},
			open: function()
			{
				this._popup = new BX.PopupWindow(
					this._id,
					BX.prop.getElementNode(this._settings, "anchor", null),
					{
						autoHide: false,
						draggable: false,
						closeByEsc: true,
						offsetLeft: 0,
						offsetTop: 0,
						zIndex: BX.prop.getInteger(this._settings, "zIndex", 0),
						overlay: BX.prop.getBoolean(this._settings, "overlay", false),
						bindOptions: { forceBindPosition: true },
						titleBar: BX.prop.getString(this._settings, "title", "No title"),
						content: BX.prop.getString(this._settings, "content", ""),
						buttons: this.prepareButtons(),
						events:
							{
								onPopupShow: BX.delegate(this.onPopupShow, this),
								onPopupClose: BX.delegate(this.onPopupClose, this),
								onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
							}
					}
				);
				this._popup.show();
			},
			close: function()
			{
				if(this._popup)
				{
					this._popup.close();
				}
			},
			isOpen: function()
			{
				return this._popup && this._popup.isShown();
			},
			prepareButtons: function()
			{
				var results = [];

				this._buttons = [];
				var data = BX.prop.getArray(this._settings, "buttons", []);
				for(var i = 0, length = data.length; i < length; i++)
				{
					var buttonData = data[i];
					buttonData["dialog"] = this;
					var button = BX.Crm.EditorDialogButton.create(
						BX.prop.getString(buttonData, "id", ""),
						buttonData
					);
					this._buttons.push(button);
					results.push(button.prepareContent());
				}

				return results;
			},
			bind: function()
			{
				for(var i = 0, length = this._buttons.length; i < length; i++)
				{
					this._buttons[i].bind();
				}
			},
			unbind: function()
			{
				for(var i = 0, length = this._buttons.length; i < length; i++)
				{
					this._buttons[i].unbind();
				}
			},
			onPopupShow: function()
			{
				this.bind();
			},
			onPopupClose: function()
			{
				this.unbind();

				if(this._popup)
				{
					this._popup.destroy();
				}
			},
			onPopupDestroy: function()
			{
				if(this._popup)
				{
					this._popup = null;
				}
				delete BX.Crm.EditorAuxiliaryDialog.items[this.getId()];
			}
		};
	BX.Crm.EditorAuxiliaryDialog.items = {};

	BX.Crm.EditorAuxiliaryDialog.isItemOpened = function(id)
	{
		return this.items.hasOwnProperty(id) && this.items[id].isOpen();
	};
	BX.Crm.EditorAuxiliaryDialog.hasOpenItems = function()
	{
		for(var key in this.items)
		{
			if(!this.items.hasOwnProperty(key))
			{
				continue;
			}

			if(this.items[key].isOpen())
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.EditorAuxiliaryDialog.getById = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};
	BX.Crm.EditorAuxiliaryDialog.create = function(id, settings)
	{
		var self = new BX.Crm.EditorAuxiliaryDialog();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion