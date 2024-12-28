BX.namespace("Crm.KanbanComponent");

BX.Crm.KanbanComponent.currentPopupItem = null;
BX.Crm.KanbanComponent.currentPopup = null;
BX.Crm.KanbanComponent.currentData = null;
BX.Crm.KanbanComponent.successClosePopup = false;
BX.Crm.KanbanComponent.dropConfirmed = false;
BX.Crm.KanbanComponent.activePopups = {};

/**
 * Return item to the last position and dec/inc price in column.
 * @param {BX.Kanban.Item} item
 * @returns {void}
 */
BX.Crm.KanbanComponent.returnItem = function(item)
{
	var lastPosition = item.getLastPosition();
	var data = item.getData();
	var grid = item.getGrid();
	var price = parseFloat(data.price);

	data.columnId = lastPosition.columnId;
	data.targetId = lastPosition.targetId;

	// dec in column and inc in last column
	this.successClosePopup = true;
	item.getColumn().decPrice(price);
	grid.getColumn(data.columnId).incPrice(price);
	// update item info
	grid.updateItem(item.getId(), data);
	grid.unhideItem(item);
};

/**
 * Clear popup form.
 * @param {String} containerId DOM id of popup content.
 * @returns {void}
 */
BX.Crm.KanbanComponent.clearPopup = function(containerId)
{
	var fields = BX(containerId).querySelectorAll('[data-field]');

	if (fields && fields.length > 0)
	{
		for (var i = 0, c = fields.length; i < c; i++)
		{
			var defaultVal = BX.data(fields[i], "default");
			fields[i].value = defaultVal ? defaultVal : "";
		}
	}
};

/**
 * Get all fields from popup for send to the backend.
 * @param {String} containerId DOM id of popup content.
 * @returns {array}
 */
BX.Crm.KanbanComponent.collectFieldsPopup = function(containerId)
{
	var fields = BX(containerId).querySelectorAll('[data-field]');
	var post = {};

	if (fields && fields.length > 0)
	{
		for (var i = 0, c = fields.length; i < c; i++)
		{
			var name = BX.data(fields[i], "field");
			if (name)
			{
				post[name] = fields[i].value;
			}
		}
	}

	return post;
};

/**
 * Show some popup.
 * @param {String} containerId DOM id of popup content.
 * @param {Object} handlerData Data from handler.
 * @param {String type handlerType Column or DropZone.
 * @returns {void}
 */
BX.Crm.KanbanComponent.showPopup = function(containerId, handlerData, handlerType)
{
	if (BX.Crm.KanbanComponent.activePopups[containerId])
	{
		return;
	}

	this.successClosePopup = false;

	if (containerId === "crm_kanban_lead_win")
	{
		var itemData = handlerData.item.getData();
		var items = BX.findChildren(
			BX(containerId),
			{
				className: "kanban-converttype"
			},
			true,
			true
		);
		for (var i = 0, c = items.length; i < c; i++)
		{
			if (itemData.return)
			{
				items[i].style.display = (BX.data(items[i], 'type') === "deal")
										? "block" : "none";
			}
			else
			{
				items[i].style.display = "block";
			}
		}
	}

	BX.Crm.KanbanComponent.currentPopupItem = handlerData.item;
	this.currentPopup = new BX.PopupWindow(
		"kanban_column_popup",
		window.body,
		{
			closeIcon : true,
			offsetLeft : 0,
			lightShadow : true,
			overlay : true,
			titleBar: {content: BX.create("span", {html: ""})},
			draggable: true,
			contentColor: "white",
			closeByEsc : true,
			events: {
				onPopupClose: function()
				{
					if (!this.successClosePopup)
					{
						this.returnItem(handlerData.item);
					}
					BX.Crm.KanbanComponent.activePopups[containerId] = false;
					this.dropConfirmed = false;
				}.bind(this)
			},
			buttons: [
				// if ok, set data to backend
				containerId !== "crm_kanban_lead_win"
				? new BX.PopupWindowButton(
					{
						text: BX.data(BX(containerId), "deletetitle")
								? BX.data(BX(containerId), "deletetitle")
								: BX.message("CRM_KANBAN_POPUP_PARAMS_SAVE"),
						className: "popup-window-button-accept",
						events:
						{
							click: function()
							{
								if (handlerType.toLowerCase() === "column")
								{
									var grid = handlerData.item.getGrid();

									grid.setAjaxParams(
										this.collectFieldsPopup(containerId)
									);
									grid.onItemMoved(
										handlerData.item,
										handlerData.targetColumn,
										handlerData.beforeItem,
										true
									);
									this.successClosePopup = true;
								}
								else if (handlerType.toLowerCase() === "dropzone")
								{
									var item = handlerData.getItem();
									var grid = item.getGrid();
									var dropZone = handlerData.getDropZone();

									grid.setAjaxParams(
										this.collectFieldsPopup(containerId)
									);
									grid.unhideItem(item);
									dropZone.captureItem(item);

									this.successClosePopup = true;
								}
								this.currentPopup.close();
							}.bind(this)
						}
					}
				)
				: null,
				// if decline, return item to the last position
				new BX.PopupWindowButton(
					{
						text: BX.message("CRM_KANBAN_POPUP_PARAMS_CANCEL"),
						className: "popup-window-button-decline",
						events:
						{
							click: function()
							{
								this.returnItem(handlerData.item);
								this.currentPopup.close();
							}.bind(this)
						}
					}
				)
			]
		}
	);
	BX.Crm.KanbanComponent.activePopups[containerId] = true;
	this.clearPopup(containerId);
	this.currentPopup.setContent(BX(containerId));
	this.currentPopup.setTitleBar(BX.data(BX(containerId), "title"));
	this.currentPopup.show();
};

/**
 * Hook on select schema of convert lead.
 * @param {String} schema Selected schema.
 * @returns {void}
 */
BX.Crm.KanbanComponent.leadConvert = function(schema)
{
	const data = this.currentData;

	if (!data || !data.item)
	{
		return;
	}

	if (!data.grid || !data.grid.data || !data.grid.data.converterId)
	{
		console.error('Converter id not found in data', data);

		return;
	}

	const id = data.item.getId();

	this.successClosePopup = true;
	this.currentPopup.close();

	const converter = BX.Crm.Conversion.Manager.Instance.getConverter(data.grid.data.converterId);
	if (!converter)
	{
		console.error('Converter with given id not found', data.grid.data.converterId, BX.Crm.Conversion.Manager.Instance);

		return;
	}

	converter.setAnalyticsElement(BX.Crm.Integration.Analytics.Dictionary.ELEMENT_DRAG_N_DROP);

	if (schema === 'SELECT')
	{
		const selector = BX.Crm.Conversion.Manager.Instance.createEntitySelector(
			converter.getId(),
			[BX.CrmEntityType.enumeration.contact, BX.CrmEntityType.enumeration.company],
			id,
		);
		void selector.show();
	}
	else
	{
		converter.convertBySchemeItemId(schema, id);
	}
};

/**
 * Handler on drop item to the column.
 * @param {Object} data Data from handler.
 * @returns {void}
 */
BX.Crm.KanbanComponent.columnPopup = function(data)
{
	var grid = data.grid;
	var gridData = grid.getData();
	var item = data.item;
	var itemData = item.getData();
	var targetColumn = data.targetColumn;
	var targetColumnId = targetColumn ? targetColumn.getId() : 0;

	BX.Crm.KanbanComponent.currentData = data;

	if (targetColumn && targetColumnId !== itemData.columnId)
	{
		var columnData = targetColumn.getData();

		// show popup on lead
		if (
			columnData.type === "WIN" &&
			gridData.entityType === "LEAD"
		)
		{
			BX.Crm.KanbanComponent.showPopup("crm_kanban_lead_win", data, "column");
			data.skip = true;
		}
		// on invoice
		else if (gridData.entityType === "INVOICE")
		{
			if (columnData.type === "WIN")
			{
				BX.Crm.KanbanComponent.showPopup("crm_kanban_invoice_win", data, "column");
				data.skip = true;
			}
			else if (columnData.type === "LOOSE")
			{
				BX.Crm.KanbanComponent.showPopup("crm_kanban_invoice_loose", data, "column");
				data.skip = true;
			}
		}
	}
};

/**
 * Handler on drop item to the dropZone.
 * @param {BX.CRM.Kanban.Grid} grid
 * @param {BX.Kanban.DropZoneEvent} dropEvent
 * @returns {void}
 */
BX.Crm.KanbanComponent.dropPopup = function(grid, dropEvent)
{
	var gridData = grid.getData();
	var dropZone = dropEvent.getDropZone();
	var dropZoneData = dropZone.getData();
	var item = dropEvent.getItem();

	// for second handler must return
	if (BX.Crm.KanbanComponent.dropConfirmed !== false)
	{
		return;
	}
	else
	{
		BX.Crm.KanbanComponent.dropConfirmed = true;
	}

	// show popup on lead
	if (gridData.entityType === "LEAD")
	{
		if (dropZoneData.type === "WIN")
		{
			grid.hideItem(item);
			BX.Crm.KanbanComponent.currentData = {
				grid: grid,
				item: item,
				targetColumn: null,
				beforeItem: null
			};
			BX.Crm.KanbanComponent.showPopup("crm_kanban_lead_win", dropEvent, "dropzone");
			dropEvent.denyAction();
		}
	}
	// show popup on invoice
	else if (gridData.entityType === "INVOICE")
	{
		grid.hideItem(item);
		BX.Crm.KanbanComponent.currentData = {
			grid: grid,
			item: item,
			targetColumn: null,
			beforeItem: null
		};
		if (dropZoneData.type === "WIN")
		{
			BX.Crm.KanbanComponent.showPopup("crm_kanban_invoice_win", dropEvent, "dropzone");
		}
		else
		{
			BX.Crm.KanbanComponent.showPopup("crm_kanban_invoice_loose", dropEvent, "dropzone");
		}
		dropEvent.denyAction();
	}
};

/**
 * On popup close.
 * @param {BX.PopupWindow} popupWindow Instance of popup.
 * @returns {void}
 */
BX.Crm.KanbanComponent.onPopupClose = function(popupWindow)
{
	BX.Crm.KanbanComponent.activePopups[popupWindow.uniquePopupId] = false;
	// detect lead converter cancel (second step)
	if (
		popupWindow &&
		typeof popupWindow.uniquePopupId !== "undefined" &&
		popupWindow.uniquePopupId === "CRM-lead_converter-popup" &&
		BX.Crm.KanbanComponent.currentPopupItem !== null
	)
	{
		setTimeout(function()
		{
			if (BX.Crm.KanbanComponent.currentPopupItem !== null)
			{
				BX.Crm.KanbanComponent.returnItem(BX.Crm.KanbanComponent.currentPopupItem);
			}
		}, 300);
	}
};

/**
 * Handler on one special item draw.
 * @param {BX.CRM.Kanban.Item} item
 * @param {DOMNode} layout
 * @returns {void}
 */
BX.Crm.KanbanComponent.onSpecialItemDraw = function(item, layout)
{
	[].slice.call(layout.querySelectorAll(".crm-kanban-sidepanel"))
		.forEach(function(link) {
			BX.bind(
				link,
				"click",
				BX.delegate(function(e)
				{
					var gridData = item.getGrid().getData();
					var urlMarker = BX.data(this, "url").toString().toLowerCase();

					if (typeof gridData.linksPath[urlMarker] !== "undefined")
					{
						if (['rest_demo', 'contact_center', 'marketplace'].includes(urlMarker))
						{
							item.getGrid().registerAnalyticsSpecialItemLinkClick(item, urlMarker);
						}

						var skipslider = BX.data(this, "skipslider") ||
										gridData.linksPath[urlMarker].skipslider;
						if (parseInt(skipslider) == 1)
						{
							window.location.href = gridData.linksPath[urlMarker].url;
						}
						else
						{
							BX.SidePanel.Instance.open(
								gridData.linksPath[urlMarker].url,
								gridData.linksPath[urlMarker].params || {}
							);
						}
					}
					e.preventDefault();
				}));
		});
};

/**
 * Intranet user selector.
 * Copy-paste from
 * crm/install/components/bitrix/crm.entity.editor/templates/.default/script.js
 */
if (
	typeof(BX.Crm.EntityEditorUserSelector) === "undefined" &&
	typeof(BX.SocNetLogDestination) !== "undefined"
)
{
	BX.Crm.EntityEditorUserSelector = function()
	{
		this._id = "";
		this._settings = {};
	};

	BX.Crm.EntityEditorUserSelector.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = id;
				this._settings = settings ? settings : {};
				this._isInitialized = false;
			},
			getId: function()
			{
				return this._id;
			},
			open: function(anchor)
			{
				if(this._mainWindow && this._mainWindow === BX.SocNetLogDestination.containerWindow)
				{
					return;
				}

				if(!this._isInitialized)
				{
					BX.SocNetLogDestination.init(
						{
							name: this._id,
							groupCode: "users",
							extranetUser:  false,
							bindMainPopup: { node: anchor, offsetTop: "5px", offsetLeft: "15px" },
							callback: { select : BX.delegate(this.onSelect, this) },
							showSearchInput: true,
							departmentSelectDisable: true,
							items:
								{
									users: BX.Crm.EntityEditorUserSelector.users,
									groups: {},
									sonetgroups: {},
									department: BX.Crm.EntityEditorUserSelector.department,
									departmentRelation : BX.SocNetLogDestination.buildDepartmentRelation(BX.Crm.EntityEditorUserSelector.department)
								},
							itemsLast: BX.Crm.EntityEditorUserSelector.last,
							itemsSelected: {},
							isCrmFeed: false,
							useClientDatabase: false,
							destSort: {},
							allowAddUser: false,
							allowSearchCrmEmailUsers: false,
							allowUserSearch: true
						}
					);
					this._isInitialized = true;
				}

				BX.SocNetLogDestination.openDialog(this._id, { bindNode: anchor });
				this._mainWindow = BX.SocNetLogDestination.containerWindow;
			},
			close: function()
			{
				if(this._mainWindow && this._mainWindow === BX.SocNetLogDestination.containerWindow)
				{
					BX.SocNetLogDestination.closeDialog();
					this._mainWindow = null;
					this._isInitialized = false;
				}

			},
			onSelect: function(item, type, search, bUndeleted)
			{
				if(type !== "users")
				{
					return;
				}

				var callback = BX.prop.getFunction(this._settings, "callback", null);
				if(callback)
				{
					callback(this, item);
				}
			}
		};

	BX.Crm.EntityEditorUserSelector.items = {};
	BX.Crm.EntityEditorUserSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUserSelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}
