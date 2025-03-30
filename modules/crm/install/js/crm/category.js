BX.namespace("BX.Crm");

if(typeof(BX.Crm.DealCategoryChanger) === "undefined")
{
	BX.Crm.DealCategoryChanger = function()
	{
		this._id = "";
		this._settings = null;
		this._selector = null;
		this._selectListener = BX.delegate(this.onSelect, this);
		this._serviceUrl = "";
		this._entityId = 0;

		this._confirmationDialog = null;
		this._errorDialog = null;
	};

	BX.Crm.DealCategoryChanger.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
			this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);
		},
		getId: function()
		{
			return this._id;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.DealCategoryChanger.messages, name, name);
		},
		getFilteredCategories: function()
		{
			var categoryIds = BX.prop.getArray(this._settings, "categoryIds", []);
			if(categoryIds.length === 0)
			{
				return BX.CrmDealCategory.infos;
			}

			return BX.CrmDealCategory.infos.filter(
				function(info)
				{
					for(var i = 0, length = categoryIds.length; i < length; i++)
					{
						if(info["id"] == categoryIds[i])
						{
							return true;
						}
					}

					return false;
				}
			);
		},
		process: function(options)
		{
			if(!BX.type.isPlainObject(options))
			{
				options = {};
			}

			if(BX.prop.getBoolean(options, "usePopupMenu", false))
			{
				this.openPopupMenu(BX.prop.getElementNode(options, "anchor", null));
			}
			else
			{
				this.openSelector();
			}

			/*
			//Confirmation is disabled
			this.openConfirmationDialog(
				BX.delegate(function(){ this.closeConfirmationDialog(); this.openSelector(); }, this),
				BX.delegate(function(){ this.closeConfirmationDialog(); }, this)
			);
			*/
		},
		openConfirmationDialog: function(onConfirm, onCancel)
		{
			this._confirmationDialog = new BX.PopupWindow(
				this._id + "_confirm",
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: this.getMessage("dialogTitle"),
					content: this.getMessage("dialogSummary"),
					className : "crm-text-popup",
					lightShadow : true,
					buttons:
					[
						new BX.PopupWindowButton(
							{
								text : BX.message("JS_CORE_WINDOW_CONTINUE"),
								className : "ui-btn ui-btn-success ui-btn-lg",
								events: { click: onConfirm }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : BX.message("JS_CORE_WINDOW_CANCEL"),
								className : "ui-btn ui-btn-link ui-btn ui-btn-lg",
								events: { click: onCancel }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onOpenConfirmationDialog, this)
					}
				}
			);
			this._confirmationDialog.show();
		},
		onOpenConfirmationDialog: function()
		{
			this._confirmationDialog.contentContainer.className = "ui-alert ui-alert-icon-warning";
		},
		closeConfirmationDialog: function()
		{
			if(this._confirmationDialog)
			{
				this._confirmationDialog.close();
				this._confirmationDialog.destroy();
				this._confirmationDialog = null;
			}
		},
		openErrorDialog: function(message)
		{
			this._errorDialog = new BX.PopupWindow(
				this._id + "_error",
				null,
				{
					autoHide: true,
					draggable: false,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					zIndex: 0,
					content: message,
					className : "crm-text-popup",
					lightShadow : true,
					buttons:
					[
						new BX.PopupWindowCustomButton(
							{
								text : BX.message("JS_CORE_WINDOW_CLOSE"),
								className : "ui-btn ui-btn-lg",
								events: { click: BX.delegate(this.closeErrorDialog, this) }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onOpenErrorDialog, this)
					}
				}
			);
			this._errorDialog.show();
		},
		onOpenErrorDialog: function()
		{
			this._errorDialog.contentContainer.className = "ui-alert ui-alert-warning";
		},
		closeErrorDialog: function()
		{
			if(this._errorDialog)
			{
				this._errorDialog.close();
				this._errorDialog.destroy();
				this._errorDialog = null;
			}
		},
		openSelector: function()
		{
			if(!this._selector)
			{
				this._selector = BX.CrmDealCategorySelectDialog.create(
					this._id,
					{
						value: -1,
						categoryIds: BX.prop.getArray(this._settings, "categoryIds", [])
					}
				);
				this._selector.addCloseListener(this._selectListener);
			}
			this._selector.open();
		},
		onSelect: function(sender, args)
		{
			if(!(BX.type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false))
			{
				return;
			}

			this.startRequest(sender.getValue());
		},
		//region Popup menu
		openPopupMenu: function(anchor)
		{
			BX.PopupMenu.show(
				this._id + "_menu",
				anchor,
				this.prepareMenuItems(),
				{ angle: false, autoHide: true, closeByEsc: true }
			);
		},
		prepareMenuItems: function()
		{
			const categoryIds = BX.prop.getArray(this._settings, 'categoryIds', []);
			if (categoryIds.length === 0)
			{
				return [];
			}

			const results = [];
			const callback = BX.delegate(this.onMenuItemClick, this);
			const itemInfos = this.getFilteredCategories();
			for (var i = 0, length = itemInfos.length; i < length; i++)
			{
				results.push({ id: itemInfos[i]["id"], text: BX.Text.encode(itemInfos[i]["name"]), onclick: callback });
			}

			return results;
		},

		onMenuItemClick(event, menuItem)
		{
			if (menuItem.menuWindow)
			{
				menuItem.menuWindow.close();
			}

			this.startRequest(BX.prop.getInteger(menuItem, 'id', 0));
		},
		// endregion

		startRequest(categoryId)
		{
			if (this.getEntityId() <= 0)
			{
				BX.UI.Dialogs.MessageBox.show({
					modal: true,
					title: this.getMessage('changeFunnelConfirmDialogTitle'),
					message: this.getMessage('changeFunnelConfirmDialogMessage'),
					minHeight: 100,
					buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
					okCaption: this.getMessage('changeFunnelConfirmDialogOkBtn'),
					onOk: (messageBox) => {
						messageBox.close();
						this.reloadPageWhenCategoryChanged(categoryId);
					},
					onCancel: (messageBox) => messageBox.close(),
				});

				return;
			}

			BX.ajax({
				url: this._serviceUrl,
				method: 'POST',
				dataType: 'json',
				data: {
					ACTION: BX.prop.getString(this._settings, 'action', 'MOVE_TO_CATEGORY'),
					ACTION_ENTITY_ID: this._entityId,
					CATEGORY_ID: categoryId,
				},
				onsuccess: BX.delegate(this.onRequestSuccess, this),
			});
		},

		reloadPageWhenCategoryChanged(categoryId)
		{
			const url = new BX.Uri(window.location.href);
			url.setQueryParam('category_id', categoryId);
			window.location.href = url.toString();
		},

		onRequestSuccess(data)
		{
			const error = BX.prop.getString(data, 'ERROR', '');
			if (error === '')
			{
				window.location.reload();
			}
			else
			{
				this.openErrorDialog(error);
			}
		},
	};

	if(typeof(BX.Crm.DealCategoryChanger.messages) === "undefined")
	{
		BX.Crm.DealCategoryChanger.messages = {};
	}

	BX.Crm.DealCategoryChanger.items = {};
	BX.Crm.DealCategoryChanger.getByEntityId = function(entityId)
	{
		for(var key in this.items)
		{
			if(!this.items.hasOwnProperty(key))
			{
				continue;
			}

			var item = this.items[key];
			if(item.getEntityId() === entityId)
			{
				return item;
			}
		}
		return null;
	};

	BX.Crm.DealCategoryChanger.processEntity = function(entityId, options)
	{
		const item = this.getByEntityId(entityId);
		if (item)
		{
			item.process(options);
		}
	};

	BX.Crm.DealCategoryChanger.create = function(id, settings)
	{
		const self = new BX.Crm.DealCategoryChanger();
		self.initialize(id, settings);
		this.items[self.getId()] = self;

		return self;
	};
}
