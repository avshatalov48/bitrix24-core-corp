if(typeof(BX.CrmActivityCustomTypeList) === "undefined")
{
	BX.CrmActivityCustomTypeList = function()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._dlg = null;
		this._dlgCloseHandler = BX.delegate(this.onDialogClose, this);
	};
	BX.CrmActivityCustomTypeList.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmActivityCustomTypeList: Could not find parameter 'serviceUrl'.";
			}

			BX.addCustomEvent(window, "CrmActitityCustomTypeCreate", this.onCreateButtonClick.bind(this));
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmActivityCustomTypeList.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getGrid: function()
		{
			var name = "bxGrid_" + this._id;
			return typeof(window[name]) !== "undefined" ? window[name] : null;
		},
		add: function()
		{
			if(!this._dlg)
			{
				this._dlg = BX.CrmActivityCustomTypeEditDialog.create(
					this._id,
					{ entityId: 0, entityData: {}, isNewEntity: true }
				);
			}
			else
			{
				this._dlg.setEntityId(0);
				this._dlg.setEntityData({});
				this._dlg.markAsNewEntity(true);
			}

			this._dlg.open();
			this._dlg.addCloseListener(this._dlgCloseHandler);
		},
		edit: function(id)
		{
			var grid = this.getGrid();
			if(!grid)
			{
				return;
			}

			var data = grid.oEditData[id];
			if(!BX.type.isPlainObject(data))
			{
				return;
			}

			if(!this._dlg)
			{
				this._dlg = BX.CrmActivityCustomTypeEditDialog.create(
					this._id,
					{ entityId: id, entityData: data, isNewEntity: false }
				);
			}
			else
			{
				this._dlg.setEntityId(id);
				this._dlg.setEntityData(data);
				this._dlg.markAsNewEntity(false);
			}

			this._dlg.open();
			this._dlg.addCloseListener(this._dlgCloseHandler);
		},
		save: function(id, fields)
		{
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data: { "ACTION" : "SAVE", "ITEM_ID": id, "FIELDS": fields },
					onsuccess: BX.delegate(this.onSaveRequestSuccess, this),
					onfailure: BX.delegate(this.onSaveRequestFailure, this)
				}
			);
		},
		delete: function(name, path)
		{
			BX.CrmDeletionConfirmDialog.create(
				this._id,
				{
					name: name,
					path: path,
					messages:
					{
						title: this.getMessage("deleteTitle"),
						confirm: this.getMessage("deleteConfirm"),
						deleteButton: this.getMessage("deleteButton")
					}
				}
			).open();
		},
		reload: function()
		{
			var grid = this.getGrid();
			if(grid)
			{
				grid.Reload();
			}
		},
		hasAction: function()
		{
			return window.location.href.search(/open_edit=/i) >= 0;
		},
		clearAction: function()
		{
			var url = window.location.href;
			return (url.search(/open_edit=/i) >= 0 ? BX.util.remove_url_param(url, "open_edit") : url);
		},
		onCreateButtonClick: function(e)
		{
			this.add();
		},
		onDialogClose: function(sender, eventArgs)
		{
			this._dlg.removeCloseListener(this._dlgCloseHandler);
			if(!eventArgs["isCanceled"])
			{
				this.save(this._dlg.getEntityId(), this._dlg.getEntityData());
			}
			else if(this.hasAction())
			{
				window.location = this.clearAction();
			}
		},
		onSaveRequestSuccess: function(data)
		{
			if(this.hasAction())
			{
				window.location = this.clearAction();
			}
			else
			{
				this.reload();
			}
		},
		onSaveRequestFailure: function(data)
		{
		}
	};
	if(typeof(BX.CrmActivityCustomTypeList.messages) === "undefined")
	{
		BX.CrmActivityCustomTypeList.messages = {};
	}
	BX.CrmActivityCustomTypeList.current = null;
	BX.CrmActivityCustomTypeList.items = {};
	BX.CrmActivityCustomTypeList.create = function(id, settings)
	{
		var self = new BX.CrmActivityCustomTypeList();
		self.initialize(id, settings);
		this.items[self.getId()] = this.current = self;
		return self;
	};
}

if(typeof(BX.CrmActivityCustomTypeEditDialog) === "undefined")
{
	BX.CrmActivityCustomTypeEditDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._entityId = 0;
		this._entityData = {};
		this._isNewEntity = true;
		this._popup = null;
		this._isOpened = false;
		this._elements = {};
		this._closeNotifier = null;
	};
	BX.CrmActivityCustomTypeEditDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._entityId = parseInt(this.getSetting("entityId", 0));
			this._entityData = this.getSetting("entityData");
			if(!BX.type.isPlainObject(this._entityData))
			{
				this._entityData = {};
			}

			this._isNewEntity = this.getSetting("isNewEntity", null);
			if(!BX.type.isBoolean(this._isNewEntity))
			{
				this._isNewEntity = this._entityId <= 0;
			}

			this._closeNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmActivityCustomTypeEditDialog.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		setEntityId: function(entityId)
		{
			this._entityId = entityId;
		},
		isNewEntity: function()
		{
			return this._isNewEntity;
		},
		markAsNewEntity: function(isNewEntity)
		{
			this._isNewEntity = !!isNewEntity;
		},
		getEntityData: function()
		{
			return this._entityData;
		},
		setEntityData: function(data)
		{
			this._entityData = BX.type.isPlainObject(data) ? data : {};
		},
		getEntityDataParam: function(name, defaultval)
		{
			return this._entityData.hasOwnProperty(name) ? this._entityData[name] : defaultval;
		},
		setEntityDataParam: function(name, value)
		{
			this._entityData[name] = value;
		},
		getElementTextValue: function(name, defaultval)
		{
			return this._elements.hasOwnProperty(name) ? this._elements[name].value : defaultval;
		},
		getElementIntegerValue: function(name, minval, defaultval)
		{
			var v = parseInt(this._elements.hasOwnProperty(name) ? this._elements[name].value : defaultval);
			return (!isNaN(v) && v > minval) ? v : minval;
		},
		check: function()
		{
			var messages = [];
			if(this.getElementTextValue("NAME", "").trim() === "")
			{
				messages.push(this.getMessage("fieldNameNotAssignedError"));
			}

			if(messages.length == 0)
			{
				return true;
			}

			var dlg = new BX.CDialog(
				{
					title: this.getMessage("errorTitle"),
					head: "",
					content: messages.join("<br/>") ,
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

			dlg.SetButtons([BX.CDialog.btnClose]);
			dlg.Show();

			return false;
		},
		save: function()
		{
			this.setEntityDataParam(
				"NAME",
				this.getElementTextValue("NAME", this.getEntityDataParam("NAME", "")).trim()
			);

			this.setEntityDataParam(
				"SORT",
				this.getElementIntegerValue("SORT", this.getEntityDataParam("SORT", 0, 10))
			);
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function()
		{
			if(this._isOpened)
			{
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				null,
				{
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: true },
					closeByEsc: true,
					closeIcon: { top: "10px", right: "15px" },
					titleBar: this.getMessage(this._isNewEntity ? "createTitle" : "editTitle"),
					content: this.prepareContent(),
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					buttons: this.prepareButtons()
				}
			);
			this._popup.show();
		},
		close: function()
		{
			if (this._popup)
			{
				this._popup.close();
			}
		},
		addCloseListener: function(listener)
		{
			this._closeNotifier.addListener(listener);
		},
		removeCloseListener: function(listener)
		{
			this._closeNotifier.removeListener(listener);
		},
		prepareContent: function()
		{
			var table = BX.create("TABLE", { attrs: { cellspacing: "7" } });

			var r, c;

			r = table.insertRow(-1);

			c = r.insertCell(-1);
			c.appendChild(BX.create("LABEL", { text: this.getMessage("fieldName") + ":" }));

			c = r.insertCell(-1);
			this._elements["NAME"] = BX.create("INPUT",
				{
					attrs: { className: "bx-crm-edit-input" },
					props: { type: "text", value: this.getEntityDataParam("NAME", this.getMessage("defaultName")) }
				}
			);
			c.appendChild(this._elements["NAME"]);

			r = table.insertRow(-1);

			c = r.insertCell(-1);
			c.appendChild(BX.create("LABEL", { text: this.getMessage("fieldSort") + ":" }));

			c = r.insertCell(-1);
			this._elements["SORT"] = BX.create("INPUT",
				{
					attrs: { className: "bx-crm-edit-input" },
					props: { type: "text", value: this.getEntityDataParam("SORT", 10) }
				}
			);
			c.appendChild(this._elements["SORT"]);

			return table;
		},
		prepareButtons: function()
		{
			return(
			[
				new BX.PopupWindowButton(
					{
						text: this.getMessage("saveButton"),
						className: "popup-window-button-accept",
						events: { click: BX.delegate(this.processSave, this) }
					}
				),
				new BX.PopupWindowButtonLink(
					{
						text: this.getMessage("cancelButton"),
						className: "popup-window-button-link-cancel",
						events: { click: BX.delegate(this.processCancel, this) }
					}
				)
			]);
		},
		processSave: function()
		{
			if(!this.check())
			{
				return;
			}
			this.save();
			this._closeNotifier.notify([{ isCanceled: false }]);
			this.close();
		},
		processCancel: function()
		{
			this._closeNotifier.notify([{ isCanceled: true }]);
			this.close();
		},
		onPopupShow: function()
		{
			this._isOpened = true;
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;
			this._popup = null;
		}
	};

	if(typeof(BX.CrmActivityCustomTypeEditDialog.messages) === "undefined")
	{
		BX.CrmActivityCustomTypeEditDialog.messages = {};
	}
	BX.CrmActivityCustomTypeEditDialog.create = function(id, settings)
	{
		var self = new BX.CrmActivityCustomTypeEditDialog();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmActivityCustomTypeDeleteDialog) === "undefined")
{
	BX.CrmActivityCustomTypeDeleteDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._name = "";
		this._path = "";
		this._message = "";
		this._dlg = null;
		this._closeNotifier = null;
	};

	BX.CrmActivityCustomTypeDeleteDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._name = this.getSetting("name", "");
			this._path = this.getSetting("path", "");
			if(!BX.type.isNotEmptyString(this._path))
			{
				throw "BX.CrmActivityCustomTypeDeleteDialog: Could not find parameter 'path'.";
			}

			this._message = this.getSetting("message", "");
			this._closeNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmActivityCustomTypeDeleteDialog.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		open: function()
		{
			this._dlg = new BX.CDialog(
				{
					title: this.getMessage("title"),
					head: "",
					content: this.getMessage("confirm").replace(/#NAME#/gi, this._name),
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

			this._dlg.SetButtons(
				[
					{
						title: this.getMessage("deleteButton"),
						id: "delete",
						action: BX.delegate(this.onAction, this)
					},
					BX.CDialog.btnClose
				]
			);
			this._dlg.Show();
		},
		close: function()
		{
			if(this._dlg)
			{
				this._dlg.Close();
			}
		},
		onAction: function()
		{
			this.close();
			window.location.href = this._path;
		}
	};

	if(typeof(BX.CrmActivityCustomTypeDeleteDialog.messages) === "undefined")
	{
		BX.CrmActivityCustomTypeDeleteDialog.messages = {};
	}
	BX.CrmActivityCustomTypeDeleteDialog.create = function(id, settings)
	{
		var self = new BX.CrmActivityCustomTypeDeleteDialog();
		self.initialize(id, settings);
		return self;
	};
}
