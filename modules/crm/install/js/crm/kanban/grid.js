(function() {

"use strict";

BX.namespace("BX.CRM.Kanban");

/**
 * @param options
 * @extends {BX.Kanban.Grid}
 * @constructor
 */
BX.CRM.Kanban.Grid = function(options)
{
	
	BX.Kanban.Grid.apply(this, arguments);

	BX.addCustomEvent(this, "Kanban.DropZone:onBeforeItemCaptured", BX.delegate(this.onBeforeItemCaptured, this));
	BX.addCustomEvent(this, "Kanban.DropZone:onBeforeItemRestored", BX.delegate(this.onBeforeItemRestored, this));

	BX.addCustomEvent(this, "Kanban.Grid:onBeforeItemMoved", BX.delegate(this.onBeforeItemMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemMoved", BX.delegate(this.onItemMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnAddedAsync", BX.delegate(this.onColumnAddedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnUpdated", BX.delegate(this.onColumnUpdated, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnMoved", BX.delegate(this.onColumnMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnRemovedAsync", BX.delegate(this.onColumnRemovedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnLoadAsync", BX.delegate(this.onColumnLoadAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStart", BX.delegate(this.onItemDragStartHandler, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStart", BX.delegate(this.setKanbanDragMode, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStop", BX.delegate(this.unSetKanbanDragMode, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStop", BX.delegate(this.stopActionPanel, this));

	BX.addCustomEvent("BX.Main.Filter:apply", BX.delegate(this.onApplyFilter, this));
	BX.addCustomEvent("BX.CrmEntityCounterPanel:applyFilter", BX.delegate(this.onApplyFilterCounter, this));
	BX.addCustomEvent("Crm.PartialEditorDialog.Close", BX.delegate(this.onPartialEditorClose, this));
	BX.addCustomEvent("onPullEvent-crm", BX.proxy(this.onPullEventHandlerCrm, this));
	BX.addCustomEvent("onPullEvent-im", BX.proxy(this.onPullEventHandlerCrm, this));
	BX.addCustomEvent("onCrmActivityTodoChecked", BX.proxy(this.onCrmActivityTodoChecked, this));
	BX.addCustomEvent("SidePanel.Slider:onClose", BX.proxy(this.onSliderClose, this));
	BX.addCustomEvent("BX.CRM.Kanban.Item.select", BX.proxy(this.startActionPanel, this));
	BX.addCustomEvent("BX.UI.ActionPanel:clickResetAllBlock", BX.proxy(this.resetMultiSelectMode, this));
	// BX.addCustomEvent("BX.CRM.Kanban.Item.select", BX.proxy(this.onMultiSelectMode, this));
	BX.addCustomEvent("onPopupShow", BX.proxy(this.onPopupShow, this));

	//setInterval(BX.proxy(this.loadNew, this), this.loadNewInterval * 1000);
	this.bindEvents();
};

BX.CRM.Kanban.Grid.prototype = {
	__proto__: BX.Kanban.Grid.prototype,
	constructor: BX.CRM.Kanban.Grid,
	accessNotifyDialog: null,
	loadNewInterval: 25,
	ajaxParams: {},
	customFieldsPopup: null,
	customFieldsContainer: null,
	actionPanel: null,
	currentNode: null,
	itemMoving: null,
	actionItems: [],
	checkedItems: [],
	progressBarEditor: null,
	ccItem: null,
	popupCancel: null,
	dropZonesShow: false,

	/**
	 * Get current checkeds items.
	 * @returns {Array}
	 */
	getChecked: function()
	{
		return this.checkedItems;
	},

	getCheckedId: function()
	{
		var checkedItems = this.getChecked();
		var checkedItemsId = [];

		for (var i = 0; i < checkedItems.length; i++)
		{
			checkedItemsId.push(checkedItems[i].id)
		}

		return checkedItemsId;
	},

	checkItem: function(item)
	{
		var itemToArray = this.getItem(item);

		if(!BX.util.in_array(itemToArray, this.checkedItems))
		{
			itemToArray.checked = true;

			this.checkedItems.push(itemToArray);

			BX.addClass(itemToArray.checkedButton, "crm-kanban-item-checkbox-checked");
			BX.addClass(itemToArray.container, "crm-kanban-item-selected");

			BX.onCustomEvent("BX.CRM.Kanban.Item.select", [itemToArray]);
		}
	},

	unCheckItem: function(item)
	{
		var itemInArray = this.getItem(item);

		if(BX.util.in_array(itemInArray, this.checkedItems))
		{
			this.checkedItems.splice(this.checkedItems.indexOf(itemInArray), 1);

			itemInArray.checked = false;

			BX.removeClass(itemInArray.checkedButton, "crm-kanban-item-checkbox-checked");
			BX.removeClass(itemInArray.container, "crm-kanban-item-selected");
			BX.onCustomEvent("BX.CRM.Kanban.Item.unSelect", [itemInArray]);
		}
	},

	getPopupCancel: function(content)
	{
		if(!this.popupCancel)
		{
			this.popupCancel = new BX.PopupWindow("crm-kanban-popup-cancel", window, {
					className: "crm-kanban-popup-cancel",
					autoHide: false,
					overlay: true,
					maxWidth: 350,
					buttons: [
						new BX.PopupWindowButton({
							text: "OK",
							className: "ui-btn ui-btn-primary",
							events: {
								click: function()
								{
									this.popupCancel.close();
								}.bind(this)
							}
						})
					],
					closeByEsc: true,
					events: {
						onPopupClose: function()
						{

						}.bind(this)
					},
					closeIcon: true
				}
			);
		}

		this.popupCancel.setContent(content);

		return this.popupCancel;
	},

	getItemsForAction: function(removeItem)
	{
		var items = this.getChecked();
		this.actionItems = [];

		if(removeItem)
		{
			items.splice(this.actionItems.indexOf(removeItem), 1);
		}

		for (var i = 0; i < items.length; i++)
		{
			this.actionItems.push(items[i].id)
		}

		return this.actionItems;
	},

	/**
	 * Bind some events.
	 * @returns {void}
	 */
	bindEvents: function()
	{
		// BX.addCustomEvent("BX.UI.ActionPanel:hidePanel", this.resetSelectItems.bind(this));
		BX.bind(window, "click", function(el) {

			if(this.dropZonesShow)
			{
				return;
			}

			this.isItKanban(el.target) ? this.currentNode = el.target : this.currentNode = null;

			if(
				!BX.findParent(el.target, {"className": "main-kanban-item"}) && 
				!BX.findParent(el.target, {"className": "ui-action-panel"})
			)
			{
				this.resetMultiSelectMode();
				this.stopActionPanel();
				this.unSetKanbanDragMode();
			}
		}.bind(this));

		BX.bind(window, "keydown", function(el) {

			if(this.dropZonesShow)
			{
				return;
			}

			if(el.code === "Escape")
			{
				this.resetMultiSelectMode();
				this.stopActionPanel();
				this.unSetKanbanDragMode();
			}
		}.bind(this));
	},

	/**
	 * Target in the kanban?
	 * @param target
	 * @return {boolean}
	 */
	isItKanban: function(target)
	{
		if (BX.findParent(target, {className: "main-kanban"}))
		{
			return true;
		}
	},

	/**
	 * Set Kanban drag mode on.
	 * @returns {void}
	 */
	setKanbanDragMode: function()
	{
		BX.addClass(document.body, "crm-kanban-drag-mode");
	},

	/**
	 * Set Kanban drag mode off.
	 * @returns {void}
	 */
	unSetKanbanDragMode: function()
	{
		BX.removeClass(document.body, "crm-kanban-drag-mode");
	},

	/**
	 * Render Kanban (override for add multiple actions).
	 * @returns {void}
	 */
	renderLayout: function()
	{
		var gridData = this.getData();

		BX.Kanban.Grid.prototype.renderLayout.apply(this, arguments);
		this.setDropareaFirstItemWidth();
		if (this.ccItem && !gridData.contactCenterShow)
		{
			this.hideItem(this.ccItem);
		}
	},

	/**
	 * Set width for first item.
	 */
	setDropareaFirstItemWidth: function()
	{
		var head = document.head;
		var styleNode = BX.create("style", {
			attrs: {
				type: "text/css"
			}
		});

		var styles = document.createTextNode(".main-kanban-dropzone:first-child, main-kanban-dropzone:last-child {" +
			"max-width: " + (this.layout.gridContainer.firstChild.offsetWidth + 3) +  "px; " +
			"min-width: " + (this.layout.gridContainer.firstChild.offsetWidth + 3) +  "px;}");
		styleNode.appendChild(styles);
		head.appendChild(styleNode);
	},

	/**
	 * Get path for ajax query.
	 * @returns {string}
	 */
	getAjaxHandlerPath: function()
	{
		var data = this.getData();

		return (
			BX.type.isNotEmptyString(data.ajaxHandlerPath)
				? data.ajaxHandlerPath
				: "/bitrix/components/bitrix/crm.kanban/ajax.php"
		);

	},

	/**
	 * Set additional params for ajax.
	 * @param {Object} data
	 * @returns {void}
	 */
	setAjaxParams: function(data)
	{
		this.ajaxParams = data;
	},

	/**
	 * Perform ajax query.
	 * @param {Object} data
	 * @param {Function} onsuccess
	 * @param {Function} onfailure
	 * @param {String} dataType html/json/script
	 * @returns {Void}
	 */
	ajax: function(data, onsuccess, onfailure, dataType)
	{
		var gridData = this.getData();
		var url = this.getAjaxHandlerPath();

		if (typeof dataType === "undefined")
		{
			dataType = "json";
		}

		data.sessid = BX.bitrix_sessid();
		data.extra = gridData.params;
		data.entity_type = gridData.entityType;
		data.version = 2;
		data.ajaxParams = this.ajaxParams;

		if (data.action !== "undefined")
		{
			url += url.indexOf("?") === -1 ? "?" : "&";
			url += "action=" + data.action;
		}

		if (this.isMultiSelectMode())
		{
			url += "&group=yes"
		}

		BX.ajax({
			method: "POST",
			dataType: dataType,
			url: url,
			data: data,
			onsuccess: onsuccess,
			onfailure: onfailure
		});
	},

	/**
	 * Show popup for request access.
	 * @returns {void}
	 */
	accessNotify: function()
	{
		if (
			typeof BX.Intranet !== "undefined" &&
			typeof BX.Intranet.NotifyDialog !== "undefined"
		)
		{
			if (this.accessNotifyDialog === null)
			{
				var gridData = this.getData();
				this.accessNotifyDialog = new BX.Intranet.NotifyDialog({
					listUserData: [],
					notificationHandlerUrl: this.getAjaxHandlerPath() +
											"?action=notifyAdmin&version=2&entity_type=" +
											gridData.entityType,
					popupTexts: {
						sendButton: BX.message("CRM_KANBAN_NOTIFY_BUTTON"),
						title: BX.message("CRM_KANBAN_NOTIFY_TITLE"),
						header: BX.message("CRM_KANBAN_NOTIFY_HEADER"),
						description: BX.message("CRM_KANBAN_NOTIFY_TEXT")
					}
				});
			}
			this.accessNotifyDialog.setUsersForNotify(this.getData().admins);
			this.accessNotifyDialog.show();
		}
	},

	/**
	 * Add new stage.
	 * @param {BX.Kanban.Column} column
	 * @returns {BX.Promise}
	 */
	addStage: function(column)
	{
		var promise = new BX.Promise();
		var targetColumn = this.getPreviousColumnSibling(column);
		var targetColumnId = targetColumn ? targetColumn.getId() : 0;

		this.ajax({
				action: "modifyStage",
				columnName: column.getName(),
				columnColor: column.getColor(),
				afterColumnId: targetColumnId
			},
			function(data)
			{
				if (data && !data.error)
				{
					this.resetActionPanel();
					promise.fulfill(data);
				}
				else if (data)
				{
					BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
					promise.reject(data.error);
				}
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				promise.reject("Error: " + error);
			}.bind(this)
		);

		return promise;
	},

	/**
	 * Remove one column (stage).
	 * @param {BX.Kanban.Column} column
	 * @returns {BX.Promise}
	 */
	removeStage: function(column)
	{
		var promise = new BX.Promise();

		this.ajax({
				action: "modifyStage",
				columnId: column.getId(),
				delete: 1
			},
			function(data)
			{
				if (data && !data.error)
				{
					this.resetActionPanel();
					promise.fulfill();
				}
				else if (data)
				{
					promise.reject(data.error);
				}
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				promise.reject("Error: " + error);
			}.bind(this)
		);

		return promise;
	},

	/**
	 * Get items from one columns.
	 * @param {BX.Kanban.Column} column
	 * @returns {BX.Promise}
	 */
	getColumnItems: function(column)
	{
		var promise = new BX.Promise();

		this.ajax({
				action: "page",
				page: column.getPagination().getPage() + 1,
				column: column.getId()
			},
			function(data)
			{
				if (data && (BX.type.isArray(data) || BX.type.isArray(data.items)) && !data.error)
				{
					promise.fulfill(BX.type.isArray(data) ? data : data.items);
				}
				else if (data)
				{
					BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
					promise.reject(data.error);
				}
				if (this.ccItem)
				{
					var gridData = this.getData();
					if (!gridData.contactCenterShow)
					{
						this.hideItem(this.ccItem);
					}
				}
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				promise.reject("Error: " + error);
			}.bind(this)
		);

		return promise;
	},

	/**
	 * Add item to the column in top.
	 * @param {Object} data
	 * @returns {void}
	 */
	addItemTop: function(data)
	{
		var column = this.getColumn(data.columnId);
		var columnItems = column ? column.getItems() : [];

		// get first item in column
		if (columnItems.length > 0)
		{
			data.targetId = columnItems[0].getId();
		}

		// inc column price and add item
		column.incPrice(data.data.price);
		this.addItem(data);
	},

	moveItem: function(item, targetColumn, beforeItem)
	{
		item = this.getItem(item);
		targetColumn = this.getColumn(targetColumn);
		beforeItem = this.getItem(beforeItem);
		var currentColumn = item.getColumn();
		var targetColumnId = targetColumn.getId();
		var gridData = this.getData();
		var targetColumnData = targetColumn.getData();

		if (!item || !targetColumn || item === beforeItem)
		{
			return false;
		}

		if (this.getChecked().length > 1)
		{
			// check required fields
			var error = false;
			var checked = this.getChecked();

			for (var i = 0, c = checked.length; i < c; i++)
			{
				var itemData = checked[i].getData();
				var currentColumnId = checked[i].getColumn().getId();

				// some final columns
				if (
					gridData.entityType === "LEAD" && targetColumnData.type === "WIN" ||
					gridData.entityType === "INVOICE"
				)
				{
					error = true;
					// this.unCheckItem(checked[i]);
					if(checked.length === i + 1)
					{
						this.resetMultiSelectMode()
					}
				}
				// first checking if targetColumn require some fields
				else if (
					itemData.required &&
					itemData.required[targetColumnId] &&
					itemData.required[targetColumnId].length > 0 &&
					targetColumnId !== currentColumnId
				)
				{
					// check required fm fields
					if (itemData.required_fm)
					{
						var newRequired = [];
						for (var j = 0, cc = itemData.required[targetColumnId].length; j < cc; j++)
						{
							var key = itemData.required[targetColumnId][j];
							if (
								typeof itemData.required_fm[key] === "undefined" ||
								itemData.required_fm[key] === true
							)
							{
								newRequired.push(itemData.required[targetColumnId][j]);
							}
						}
						itemData.required[targetColumnId] = newRequired;
					}
					if (itemData.required[targetColumnId].length > 0)
					{
						error = true;
						// this.unCheckItem(checked[i]);

						if(checked.length === i + 1)
						{
							this.resetMultiSelectMode()
						}
					}
				}
			}

			if (error)
			{
				this.getPopupCancel(
					BX.message("CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_" + gridData.entityType)
				).show();
			}

			var itemsChecked = this.getChecked();

			for (var i = 0; i < itemsChecked.length; i++)
			{
				if(itemsChecked[i] !== item && itemsChecked[i].getColumn() !== targetColumn)
				{
					itemsChecked[i].getColumn().layout.total.textContent = +itemsChecked[i].getColumn().layout.total.innerHTML - 1;
				}

				currentColumn.removeItem(itemsChecked[i]);
			}

			targetColumn.addItems(this.getChecked(), beforeItem);

			this.resetMultiSelectMode();

			return;
		}

		currentColumn.removeItem(item);
		targetColumn.addItem(item, beforeItem);

		return true;
	},

	/**
	 * Load new items by interval.
	 * @param {int} id Entity id (optional).
	 * @param {boolean} force Force load without filter.
	 * @returns {void}
	 */
	loadNew: function(id, force)
	{
		var gridData = this.getData();
		var entityId = typeof id !== "undefined" ? id : 0;

		if (document.hidden)
		{
			return;
		}

		this.ajax(
			entityId
			? {
				action: "get",
				entity_id: entityId,
				force: force === true ? "Y" : "N"
			}
			: {
				action: "get",
				min_entity_id: gridData.lastId,
				force: force === true ? "Y" : "N"
			},
			function(data)
			{
				if (data && data.items)
				{
					var worked = false;
					if (data.items.length > 0)
					{
						var titlesForRender = {};
						for (var i = data.items.length-1; i >= 0; i--)
						{
							var item = data.items[i];
							var existItem = this.getItem(item.id);
							if (item.id <= 0)
							{
								continue;
							}
							worked = true;
							if (existItem)
							{
								var existData = existItem.getData();
								var existColumn = existItem.getColumn();
								var newColumn = this.getColumn(item.columnId);

								existColumn.decPrice(parseFloat(existData.price));
								titlesForRender[existColumn.getId()] = existColumn;

								if (newColumn)
								{
									newColumn.incPrice(parseFloat(item.data.price));
									this.updateItem(item.id, item);
									titlesForRender[newColumn.getId()] = newColumn;
								}
								else
								{
									this.removeItem(item.id);
								}
							}
							else if (item.id)
							{
								this.addItemTop(item);
							}
							if (!entityId)
							{
								gridData.lastId = item.id;
								this.setData(gridData);
							}
						}

						for (var key in titlesForRender)
						{
							titlesForRender[key].renderSubTitle();
						}
					}

					if (!worked && entityId)
					{
						var item = this.getItem(entityId);
						if (item)
						{
							var itemData = item.getData();
							var column = item.getColumn();

							column.decPrice(itemData.price);
							this.removeItem(entityId);
						}
					}
				}
			}.bind(this),
			function(error)
			{
			}.bind(this)
		);
	},

	/**
	 * Hook on item drag start.
	 * @param {BX.Kanban.Item} item
	 * @returns {void}
	 */
	onItemDragStart: function(item)
	{
		this.setDragMode(BX.Kanban.DragMode.ITEM);

		var gridData = this.getData();
		var items = this.getItems();
		var itemColumnData = item.getColumn().getData();
		var itemColumnId = item.getColumnId();

		if (parseInt(item.getId()) < 0)
		{
			return;
		}

		// disable move for win lead
		if (
			gridData.entityType === "LEAD" &&
			itemColumnData.type === "WIN"
		)
		{
			for (var itemId in items)
			{
				var columnId = items[itemId].getColumnId();

				if (columnId === itemColumnId)
				{
					items[itemId].enableDropping();
				}
			}

			return;
		}

		BX.Kanban.Grid.prototype.onItemDragStart.apply(this, arguments);

		if (this.progressBarEditor)
		{
			this.progressBarEditor.close();
		}
	},

	/**
	 * Hook on item drag start.
	 * @param {BX.Kanban.Item} item
	 * @returns {voiid}
	 */
	onItemDragStartHandler: function(item)
	{
		item.setLastPosition();
	},

	/**
	 * Event Handler must add a promise to the 'promises' collection.
	 * @param {Array} promises
	 * @returns {void}
	 */
	onColumnLoadAsync: function(promises)
	{
		promises.push(BX.delegate(this.getColumnItems, this));
	},

	/**
	 * Event Handler must add a promise to the 'promises' collection.
	 * @param {Array} promises
	 * @returns {void}
	 */
	onColumnRemovedAsync: function(promises)
	{
		promises.push(BX.delegate(this.removeStage, this));
	},

	/**
	 * Event Handler must add a promise to the 'promises' collection.
	 * @param {Array} promises
	 * @returns {void}
	 */
	onColumnAddedAsync: function(promises)
	{
		promises.push(BX.delegate(this.addStage, this));
	},

	/**
	 * Hook on item drop to junk's.
	 * @param {BX.Kanban.DropZoneEvent} dropEvent
	 * @returns {void}
	 */
	onBeforeItemCaptured: function(dropEvent)
	{
		BX.onCustomEvent("Crm.Kanban.Grid:onBeforeItemCapturedStart", [this, dropEvent]);
		// move item and decprice in column
		if (dropEvent.isActionAllowed())
		{
			var item = dropEvent.getItem();
			var column = item.getColumn();
			var drop = dropEvent.getDropZone();

			this.itemMoving = {
				item: item,
				price: parseFloat(item.getData().price),
				oldColumn: column,
				oldNextSiblingId: column.getNextItemSibling(item),
				newColumn: null,
				newNextSibling: null,
				dropEvent: dropEvent,
				groupIds: this.getItemsForAction()
			};

			this.onItemMoved(item, drop, null, true);

			if (drop.getId() === "DELETED")
			{
				var ids = this.getItemsForAction();
				setTimeout(function()
					{
						BX.CRM.Kanban.Actions.delete(
							this,
							ids.length ? ids : item.getId()
						);
					}.bind(this),
					drop.getDropZoneArea().getDropZoneTimeout()
				);
			}
		}
	},

	/**
	 * Hook on item return from junk's.
	 * @param {BX.Kanban.DropZoneEvent} event
	 * @returns {void}
	 */
	onBeforeItemRestored: function(event)
	{
		var item = event.getItem();
		var column = item.getColumn();
		var price = parseFloat(item.getData().price);

		// change price in column and move item
		column.incPrice(price);
		this.onItemMoved(item, column);
	},

	/**
	 * Hook on item moved start.
	 * @param {BX.Kanban.DragEvent} event
	 * @returns {void}
	 */
	onBeforeItemMoved: function(event)
	{
		var item = event.getItem();
		var column = item.getColumn();

		this.itemMoving = {
			item: item,
			price: parseFloat(item.getData().price),
			oldColumn: column,
			oldNextSiblingId: column.getNextItemSibling(item),
			newColumn: null,
			newNextSibling: null
		};
	},

	/**
	 * Hook on item moved.
	 * @param {BX.Kanban.Item} item
	 * @param {BX.Kanban.Column|BX.Kanban.DropZone} targetColumn
	 * @param {BX.Kanban.Item} [beforeItem]
	 * @param {Boolean} [skipHandler]
	 * @returns {void}
	 */
	onItemMoved: function(item, targetColumn, beforeItem, skipHandler)
	{
		var itemData = item.getData();
		var columnId = targetColumn.getId();
		var gridData = this.getData();
		var isDropZone = targetColumn instanceof BX.Kanban.DropZone;

		// first checking if targetColumn require some fields
		if (
			itemData.required &&
			itemData.required[columnId] &&
			itemData.required[columnId].length > 0 &&
			this.itemMoving.oldColumn.getId() !== targetColumn.getId()
		)
		{
			// check required fm fields
			if (itemData.required_fm)
			{
				var newRequired = [];
				for (var i = 0, c = itemData.required[columnId].length; i < c; i++)
				{
					var key = itemData.required[columnId][i];
					if (
						typeof itemData.required_fm[key] === "undefined" ||
						itemData.required_fm[key] === true
					)
					{
						newRequired.push(itemData.required[columnId][i]);
					}
				}
				itemData.required[columnId] = newRequired;
			}

			if (itemData.required[columnId].length > 0)
			{
				this.itemMoving.newColumn = targetColumn;
				this.itemMoving.newNextSibling = beforeItem;
				// back to the prev place
				if (!isDropZone)
				{
					this.moveItem(
						this.itemMoving.item,
						this.itemMoving.oldColumn,
						this.itemMoving.oldNextSiblingId
					);
				}
				else
				{
					this.itemMoving.dropEvent.denyAction();
				}
				// show editor
				this.progressBarEditor = BX.Crm.PartialEditorDialog.create(
					"progressbar-entity-editor",
					{
						entityTypeId: gridData.entityTypeInt,
						entityId: item.getId(),
						fieldNames: itemData.required[columnId],
						title: BX.message(
							"CRM_KANBAN_REQUIRED_FIELDS_TITLE_" + gridData.entityType
						)
					}
				);
				this.progressBarEditor.open();
				BX.addClass(
					item.layout.container,
					"main-kanban-item-waiting"
				);
				return;
			}
		}

		// show popup for lead convert
		if (
			gridData.entityType === "LEAD" &&
			targetColumn.getId() === "CONVERTED"
		)
		{
			BX.Crm.KanbanComponent.dropPopup(
				this,
				this.itemMoving.dropEvent
			);
		}

		// change price in old/new columns
		if (this.itemMoving.item.getData().runtimePrice !== true)
		{
			this.itemMoving.oldColumn.decPrice(this.itemMoving.price);
		}
		if (!isDropZone)
		{
			targetColumn.incPrice(this.itemMoving.price);
			targetColumn.renderSubTitle();
			this.itemMoving.oldColumn.renderSubTitle();
		}

		this.itemMoving.item.setDataKey(
			"runtimePrice",
			false
		);

		// call handler
		if (skipHandler !== true)
		{
			var handlerData = {
				grid: this,
				item: item,
				targetColumn: targetColumn,
				beforeItem: beforeItem,
				skip: false
			};
			BX.onCustomEvent("Crm.Kanban.Grid:onItemMovedFinal", [handlerData]);
			if (handlerData.skip === true)
			{
				return;
			}
		}

		// some vars
		var afterItemId = 0;
		var itemId = item.getId();
		var targetColumnId = targetColumn ? targetColumn.getId() : 0;

		// set sort
		if (targetColumn instanceof BX.Kanban.DropZone)
		{
			afterItemId = 0;
		}
		else
		{
			var afterItem = targetColumn.getPreviousItemSibling(item);
			if (afterItem)
			{
				afterItemId = afterItem.getId();
			}
		}

		BX.removeClass(
			item.layout.container,
			"main-kanban-item-waiting"
		);

		if(this.itemMoving.groupIds && this.itemMoving.groupIds.length === 0)
		{
			this.itemMoving.groupIds.push(itemId)
		}

		// ajax
		this.ajax({
				action: "status",
				entity_id: itemId
							? itemId
							: this.itemMoving.groupIds,
				prev_entity_id: afterItemId,
				status: targetColumnId
			},
			function(data)
			{
				if (data && !data.error)
				{
					if (data.items && data.items.length > 0)
					{
						this.updateItem(itemId, data.items[0]);
					}
					else
					{
						item.setDataKey("columnId", targetColumnId);
					}
				}
				else if (data)
				{
					BX.Kanban.Utils.showErrorDialog(data.error, true);
				}
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this)
		);
	},

	/**
	 * Hook on column update.
	 * @param {BX.Kanban.Column} column
	 * @returns {void}
	 */
	onColumnUpdated: function(column)
	{
		var columnId = column.getId();
		var title = column.getName();
		var color = column.getColor();

		this.ajax({
				action: "modifyStage",
				columnId: columnId,
				columnName: title,
				columnColor: color
			},
			function(data)
			{
				if (data && data.error)
				{
					BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
				}
				else
				{
					this.resetActionPanel();
				}
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this)
		);
	},

	/**
	 * Hook on column move.
	 * @param {BX.Kanban.Column} column
	 * @param {BX.Kanban.Column} [targetColumn]
	 * @returns {void}
	 */
	onColumnMoved: function(column, targetColumn)
	{
		var columnId = column.getId();
		var afterColumn = this.getPreviousColumnSibling(column);
		var afterColumnId = afterColumn ? afterColumn.getId() : 0;

		this.ajax({
				action: "modifyStage",
				columnId: columnId,
				afterColumnId: afterColumnId
			},
			function(data)
			{
				if (data && data.error)
				{
					BX.Kanban.Utils.showErrorDialog(data.error, true);
				}
				else
				{
					this.resetActionPanel();
				}
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this)
		);
	},

	/**
	 * Hook on main filter applied.
	 * @param {String} filterId
	 * @param {Object} values
	 * @param {Object} filterInstance
	 * @param {BX.Promise} promise
	 * @param {Object} params
	 * @returns {void}
	 */
	onApplyFilter: function(filterId, values, filterInstance, promise, params)
	{
		this.fadeOut();
		if (typeof params !== "undefined")
		{
			params.autoResolve = false;
		}
		this.ajax({
				action: "get"
			},
			function(data)
			{
				// remove all columns
				var exist = [], id = null;
				var columns = this.getColumns();
				for (var i = 0, c = columns.length; i < c; i++)
				{
					id = columns[i].getId();
					exist.push(id);
					this.removeColumn(id);
				}
				// remove items
				this.removeItems();
				// and load new
				this.loadData(data);

				// redraw drop zones
				var dropZone = this.getDropZoneArea();
				var dropZones = this.getDropZoneArea().getDropZones();
				for (var i = 0, c = dropZones.length; i < c; i++)
				{
					dropZone.removeDropZone(dropZones[i]);
				}
				if (data.dropzones)
				{
					for (var i = 0, c = data.dropzones.length; i < c; i++)
					{
						dropZone.addDropZone(data.dropzones[i]);
					}
				}
				// check for new columns and scroll to it
				var newColumn = null;
				columns = this.getColumns();
				for (var i = 0, c = columns.length; i < c; i++)
				{
					id = columns[i].getId();
					if (!BX.util.in_array(id, exist))
					{
						newColumn = columns[i];
					}
				}
				if (newColumn !== null)
				{
					this.addClassEar()
				}
				this.fadeIn();

				if (typeof promise !== "undefined")
				{
					promise.fulfill();
				}

			}.bind(this),
			function(error)
			{
				if (typeof promise !== "undefined")
				{
					promise.reject();
				}
				this.fadeIn();
			}.bind(this)
		);
	},

	/**
	 * Add ears.
	 * @returns {void}
	 */
	addClassEar: function()
	{
		var ear = document.querySelector(".main-kanban-ear-right");
		ear.classList.contains("crm-kanban-ear-animate") ? BX.removeClass("crm-kanban-ear-animate") : null
		BX.addClass(ear, "crm-kanban-ear-animate")
	},

	/**
	 * Show or hide contact center.
	 * @return {void}
	 */
	toggleCC: function()
	{
		var menu = BX.PopupMenu.getCurrentMenu();
		if (menu)
		{
			menu.close();
		}

		if (this.ccItem)
		{
			var gridData = this.getData();

			if (gridData.contactCenterShow)
			{
				this.hideItem(this.ccItem);
			}
			else
			{
				this.unhideItem(this.ccItem);
			}
			gridData.contactCenterShow = !gridData.contactCenterShow;
		}

		this.ajax({
				action: "toggleCC"
			},
			function()
			{
			}.bind(this),
			function(error)
			{
			}.bind(this)
		);
	},

	/**
	 *
	 * @param {BX.Kanban.Item|string|number} item
	 * @returns {boolean}
	 */
	unhideItem: function(item)
	{
		item = this.getItem(item);
		if (!item || item.isVisible())
		{
			return false;
		}

		item.setOptions({ visible: true });

		if(item.layout.container && item.layout.container.classList.contains("main-kanban-item-disabled"))
		{
			BX.removeClass(item.layout.container, "main-kanban-item-disabled");
		}

		if (item.isCountable())
		{
			item.getColumn().incrementTotal();
		}

		item.getColumn().render();

		return true;
	},

	/**
	 * Add menu item for show or hide contact center block.
	 * @param {Strings} menuId
	 * @return {void}
	 */
	addMenuToggleCS: function(menuId)
	{
		var gridData = this.getData();
		var menu = BX.PopupMenu.getCurrentMenu(
			menuId
		);
		if (
			menu &&
			menu.bindElement &&
			BX(menu.bindElement) &&
			BX.hasClass(BX(menu.bindElement), "ui-btn-icon-setting")
		)
		{
			menu.addMenuItem(
				{
					text: "",
					delimiter: true
				},
				null
			);
			menu.addMenuItem(
				{
					text: gridData.contactCenterShow
							? BX.message("CRM_KANBAN_HIDE_CC")
							: BX.message("CRM_KANBAN_SHOW_CC"),
					onclick: function(e, /*BX.PopupMenuItem*/item)
					{
						//item.layout.text.textContent for change text if need
						this.toggleCC();
					}.bind(this)
				},
				null
			);
		}
	},

	/**
	 * Show popup for custom fields.
	 * @param {String} Menu id.
	 * @returns {void}
	 */
	addMenuCustomFieldsPopup: function(menuId)
	{
		var gridData = this.getData();
		var entityType = gridData.entityType.toLowerCase();
		var menu = BX.PopupMenu.getCurrentMenu(
			menuId
		);
		if (menu)
		{
			var items = menu.getMenuItems();
			menu.addMenuItem({
					text: BX.message("CRM_KANBAN_CUSTOM_FIELDS"),
					onclick: function()
					{
						if (!this.customFieldsPopup)
						{
							this.customFieldsPopup = new BX.PopupWindow(
								"kanban_custom_fields",
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
									buttons: [
										// save fields
										new BX.PopupWindowButton(
											{
												text: BX.message("CRM_KANBAN_POPUP_SAVE"),
												className: "popup-window-button-accept",
												events:
													{
														click: function()
														{
															// collect checked fields
															var saveData = {};
															var checkboxes = BX.findChild(
																this.customFieldsContainer,
																{
																	tagName: "input"
																},
																false,
																true
															);
															for (var i = 0, c = checkboxes.length; i < c; i++)
															{
																if (checkboxes[i].checked)
																{
																	saveData[checkboxes[i].getAttribute("name")] = BX.data(checkboxes[i], "label");
																}
															}
															// save
															this.ajax({
																	action: "saveFields",
																	fields: saveData
																},
																function(data)
																{
																	this.onApplyFilter();
																}.bind(this),
																function(error)
																{
																}.bind(this)
															);
															this.customFieldsPopup.close();
														}.bind(this)
													}
											}
										),
										new BX.PopupWindowButton(
											{
												text: BX.message("CRM_KANBAN_POPUP_CANCEL"),
												className: "popup-window-button-decline",
												events:
													{
														click: function()
														{
															this.customFieldsPopup.close();
														}.bind(this)
													}
											}
										)
									]
								}
							);
							this.customFieldsPopup.setContent(
								"wait..."
							);
							this.customFieldsPopup.setTitleBar(
								BX.message("CRM_KANBAN_CUSTOM_FIELDS")
							);
							// get fields by ajax
							BX.ajax({
								method: "GET",
								dataType: "json",
								url: "/bitrix/components/bitrix/crm.lead.list/filter.ajax.php" +
								"?filter_id=" + gridData.gridId + "&siteID=" + BX.message("SITE_ID") +
								"&sessid=" + BX.bitrix_sessid(),
								onsuccess: function(data)
								{
									var formContent = [];
									for (var i = 0, c = data.length; i < c; i++)
									{
										formContent.push(BX.create(
											"input", {
												props: {
													id: "cf_" + data[i].ID,
													type: "checkbox",
													name: data[i].NAME,
													checked: BX.util.in_array(data[i].NAME, gridData.customFields)
												},
												dataset: {
													label: data[i].LABEL
												}
											}
										));
										formContent.push(BX.create(
											"label", {
												attrs: {
													"for": "cf_" + data[i].ID,
												},
												text: data[i].LABEL
											}
										));
										formContent.push(BX.create("br"));
									}
									this.customFieldsContainer = BX.create(
										"div", {
											children: formContent
										}
									);
									this.customFieldsPopup.setContent(
										this.customFieldsContainer
									);
									this.customFieldsPopup.adjustPosition();
								}.bind(this)
							});
						}
						this.customFieldsPopup.show();
					}.bind(this)
				},
				items.length > 0 ? items[0].id : null
			);
		}
	},

	/**
	 * Hook on filter counters click.
	 * @param {BX.CrmEntityCounterPanel} sender
	 * @param {Object} eventArgs
	 * @returns {void}
	 */
	onApplyFilterCounter: function(sender, eventArgs)
	{
		setTimeout(
			BX.delegate(
				function() {
					var gridData = this.getData();
					var fields = {
						"ASSIGNED_BY_ID": { 0: eventArgs["userId"] },
						"ASSIGNED_BY_ID_label": [ eventArgs["userName"] ],
						"ACTIVITY_COUNTER": { 0: eventArgs["counterTypeId"] }
					};
					var filter = BX.Main.filterManager.getById(gridData.gridId);
					var api = filter.getApi();
					api.setFields(fields);
					api.apply();
				},
				this
			), 0
		);
		eventArgs["cancel"] = true;
	},

	/**
	 * Handler partial editor close.
	 * @param {BX.Crm.PartialEditorDialog} sender
	 * @param {Object} eventParams
	 * @return void
	 */
	onPartialEditorClose: function(sender, eventParams)
	{
		BX.removeClass(
			this.itemMoving.item.layout.container,
			"main-kanban-item-waiting"
		);

		if (eventParams.isCancelled)
		{
			return;
		}

		var stilError = false;
		// update required fields
		if (eventParams.entityData)
		{
			var itemData = this.itemMoving.item.getData();
			var newColumnId = this.itemMoving.newColumn.getId();

			if (itemData.required && itemData.required[newColumnId])
			{
				var requiredKeys = itemData.required[newColumnId];
				var itrError = false;
				var deletedFM = {};

				for (var fmKey in itemData.required_fm)
				{
					if (eventParams.entityData[fmKey])
					{
						itemData.required_fm[fmKey] = false;
						deletedFM[fmKey] = true;
					}
				}

				var newRequired = [];
				for (var i = 0, c = requiredKeys.length; i < c; i++)
				{
					var key = requiredKeys[i];

					if (deletedFM[key])
					{
						itrError = false;
					}
					else if (
						eventParams.entityData[key] &&
						(
							typeof eventParams.entityData[key] === "object" &&
							eventParams.entityData[key].IS_EMPTY === false
							||
							typeof eventParams.entityData[key] !== "object" &&
							eventParams.entityData[key] !== ""
						)
					)
					{
						itrError = false;
					}
					else if (
						key === "OPPORTUNITY_WITH_CURRENCY" &&
						parseFloat(eventParams.entityData["OPPORTUNITY"]) > 0
					)
					{
						this.itemMoving.item.setDataKey(
							"runtimePrice",
							true
						);
						itrError = false;
					}
					else if (
						key === "CLIENT" &&
						(
							parseInt(eventParams.entityData["CONTACT_ID"]) > 0 ||
							parseInt(eventParams.entityData["COMPANY_ID"]) > 0
						)
					)
					{
						itrError = false;
					}
					else
					{
						itrError = true;
					}
					if (!itrError)
					{
						for (var kStatus in itemData.required)
						{
							var stRequired = itemData.required[kStatus];
							for (var ii = 0, cc = stRequired.length; ii < cc; ii++)
							{
								if (stRequired[ii] === key)
								{
									stRequired = BX.util.deleteFromArray(stRequired, ii);
									break;
								}
							}
							itemData.required[kStatus] = stRequired;
						}
					}
					else
					{
						stilError = true;
					}
				}
				// save new data
				this.itemMoving.item.setDataKey(
					"required",
					itemData.required
				);
				this.itemMoving.item.setDataKey(
					"required_fm",
					itemData.required_fm
				);
				if (eventParams.entityData["OPPORTUNITY"])
				{
					this.itemMoving.price = parseFloat(eventParams.entityData["OPPORTUNITY"]);
					this.itemMoving.item.setDataKey(
						"price",
						this.itemMoving.price
					);
					this.itemMoving.item.setDataKey(
						"price_formatted",
						eventParams.entityData["FORMATTED_OPPORTUNITY_WITH_CURRENCY"]
					);
				}
			}
		}
		// if drop area
		if (this.itemMoving.newColumn instanceof BX.Kanban.DropZone)
		{
			this.itemMoving.newColumn.captureItem(
				this.itemMoving.item
			);
		}
		else
		{
			// move visual and save
			this.onItemMoved(
				this.itemMoving.item,
				this.itemMoving.newColumn,
				this.itemMoving.newNextSibling
			);
			if (!stilError)
			{
				this.moveItem(
					this.itemMoving.item,
					this.itemMoving.newColumn,
					this.itemMoving.newNextSibling
				);
			}
		}
	},

	/**
	 * Hook on pull event.
	 * @param {String} command
	 * @param {Object} params
	 * @returns {void}
	 */
	onPullEventHandlerCrm: function(command, params)
	{
		var gridData = this.getData();

		// new activity
		if (command === "activity_add" && /*params.COMPLETED !== "Y" &&*/
			params.OWNER_TYPE_NAME === gridData.entityType
		)
		{
			var item = this.getItem(params.OWNER_ID);
			if (item)
			{
				this.loadNew(item.getId());
			}
			else
			{
				this.loadNew();
			}
		}

		// new element by delegate
		if (command === "notify")
		{
			// lead / deal
			var matches = params.originalTag.match(
				new RegExp("CRM\\|" + gridData.entityType + "_RESPONSIBLE\\|([\\d]+)")
			);
			if (matches && matches[1])
			{
				this.loadNew(matches[1]);
			}
			// invoice
			if (
				gridData.entityType === "INVOICE" &&
				params.settingName === "crm|invoice_responsible_changed"
			)
			{
				var matches = params.originalTag.match(
					new RegExp("CRM\\|" + gridData.entityType + "\\|([\\d]+)")
				);
				if (matches && matches[1])
				{
					this.loadNew(matches[1]);
				}
			}
		}
	},

	/**
	 * Check on one activity.
	 * @param {Integer} activityId
	 * @param {Integer} ownerId
	 * @param {Integer} ownerTypeId
	 * @param {Boolean} deadlined
	 * @returns {void}
	 */
	onCrmActivityTodoChecked: function(activityId, ownerId, ownerTypeId, deadlined)
	{
		var item = this.getItem(ownerId);
		if (item)
		{
			// deadlined counters
			if (deadlined)
			{
				var activityErrorTotal = item.getDataKey("activityErrorTotal");
				activityErrorTotal--;
				item.setDataKey("activityErrorTotal", activityErrorTotal);
			}
			// common counters
			var activityProgress = item.getDataKey("activityProgress");
			activityProgress--;
			item.setDataKey("activityProgress", activityProgress);
			// render
			item.switchPlanner();
		}
	},

	/**
	 * On slider close.
	 * @param {BX.SidePanel.Event} SliderEvent
	 * @returns {void}
	 */
	onSliderClose: function(SliderEvent)
	{
		var gridData = this.getData();
		var maskUrl = gridData.entityPath;
		var sliderUrl = SliderEvent.slider.getUrl();
		maskUrl = maskUrl.replace(/\#([^\#]+)\#/, '([\\d]+)');

		var match = sliderUrl.match(new RegExp(maskUrl));
		if (match && match[1])
		{
			this.loadNew(match[1]);
		}
	},

	/**
	 * On popup show.
	 * @param {BX.PopupWindow} popupWindow
	 * @returns {void}
	 */
	onPopupShow: function(popupWindow)
	{
		// add some menu item
		if (
			popupWindow.uniquePopupId == "menu-popup-toolbar_lead_list_menu" ||
			popupWindow.uniquePopupId == "menu-popup-toolbar_deal_list_menu"
		)
		{
			this.addMenuToggleCS(
				popupWindow.uniquePopupId.substr(11)
			);
			/*this.addMenuCustomFieldsPopup(
				popupWindow.uniquePopupId.substr(11)
			);*/
		}
	},

	/**
	 * Set multi select mode.
	 * @returns {void}
	 */
	setMultiSelectMode: function()
	{
		this.multiSelectMode = true;
		this.setKanbanDragMode();
	},

	/**
	 * Build the action panel.
	 */
	initActionPanel: function()
	{
		var gridData = this.getData();

		this.actionPanel = new BX.UI.ActionPanel({
			renderTo: document.querySelector(".pagetitle-wrap"),
			removeLeftPosition: true,
			maxHeight: 56,
			parentPosition: "bottom"
		});

		this.actionPanel.draw();

		// delete
		this.actionPanel.appendItem({
			id: "kanban_delete",
			text: BX.message("CRM_KANBAN_PANEL_DELETE"),
			icon: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-delete.svg",
			onclick: function()
			{
				BX.CRM.Kanban.Actions.deleteAll(
					this
				);
			}.bind(this)
		});

		// ignore
		if (
			gridData.entityType === "LEAD" ||
			gridData.entityType === "DEAL"
		)
		{
			this.actionPanel.appendItem({
				id: "kanban_ignore",
				text: BX.message("CRM_KANBAN_PANEL_IGNORE"),
				icon: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-ignore.svg",
				onclick: function()
				{
					BX.CRM.Kanban.Actions.ignore(
						this
					);
				}.bind(this)
			});
		}

		// change category
		if (
			gridData.entityType === "LEAD" ||
			gridData.entityType === "DEAL" ||
			gridData.entityType === "QUOTE"
		)
		{
			var items = [],
				categories = [],
				columns = this.getColumns(),
				drops = this.getDropZoneArea().getDropZones();
			for (var i = 0, c = columns.length; i < c; i++)
			{
				categories.push({
					id: columns[i].id,
					name: columns[i].name
				});
			}
			for (var i = 0, c = drops.length; i < c; i++)
			{
				var dropData = drops[i].getData();
				if (
					(
						gridData.entityType === "LEAD" &&
						dropData.type === "LOOSE"
					)
					||
					(
						gridData.entityType === "DEAL" &&
						dropData.type
					)
					||
					(
						gridData.entityType === "QUOTE" &&
						dropData.type
					)
				)
				{
					categories.push({
						id: drops[i].id,
						name: drops[i].name
					});
				}
			}
			for (var i = 0, c = categories.length; i < c; i++)
			{
				items.push({
					id: "kanban_column_" + categories[i].id,
					column: categories[i],
					text: BX.util.htmlspecialchars(categories[i].name),
					onclick: function(i, item)
					{
						item.menuWindow.close();
						BX.CRM.Kanban.Actions.changeColumn(
							this,
							item.column
						);
					}.bind(this)
				});
			}
			this.actionPanel.appendItem({
				id: "kanban_column",
				text: (gridData.entityType === "DEAL")
					? BX.message("CRM_KANBAN_PANEL_STAGE")
					: BX.message("CRM_KANBAN_PANEL_STATUS"),
				items: items,
				icon: (gridData.entityType === "DEAL")
					? "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-stage.svg"
					: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-status.svg"
			});
		}

		// change category
		if (gridData.entityType === "DEAL")
		{
			var items = [], categories = gridData.categories;
			for (var i = 0, c = categories.length; i < c; i++)
			{
				items.push({
					id: "kanban_category_" + categories[i].ID,
					category: categories[i],
					text: BX.util.htmlspecialchars(categories[i].NAME),
					onclick: function(i, item)
					{
						item.menuWindow.close();
						BX.CRM.Kanban.Actions.changeCategory(
							this,
							item.category
						);
					}.bind(this)
				});
			}
			this.actionPanel.appendItem({
				id: "kanban_category",
				text: BX.message("CRM_KANBAN_PANEL_CATEGORY"),
				icon: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-fulling.svg",
				items: items
			});
		}

		// assigned to
		if (typeof(BX.Crm.EntityEditorUserSelector) !== "undefined")
		{
			this.actionPanel.appendItem({
				id: "kanban_assigned",
				text: BX.message("CRM_KANBAN_PANEL_ASSIGNED"),
				icon: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-responsible.svg",
				onclick: function(e, item)
				{
					setTimeout(function()
					{
						var userSelector = BX.Crm.EntityEditorUserSelector.create(
							"selector_assigned",
							{
								callback: function(selector, item)
								{
									BX.CRM.Kanban.Actions.setAssigned(
										this,
										item
									);
									userSelector.close();
								}.bind(this)
							}
						);
						userSelector.open(item.layout.container);
					}.bind(this), 100);
				}.bind(this)
			});
		}

		// create task
		if (
			gridData.entityType === "LEAD" ||
			gridData.entityType === "DEAL"
		)
		{
			this.actionPanel.appendItem({
				id: "kanban_task",
				text: BX.message("CRM_KANBAN_PANEL_TASK"),
				icon: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-create.svg",
				onclick: function()
				{
					BX.CRM.Kanban.Actions.task(
						this
					);
				}.bind(this)
			});
		}

		// call list
		this.actionPanel.appendItem({
			id: "kanban_calllist",
			text: BX.message("CRM_KANBAN_PANEL_CALLLIST"),
			icon: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-call.svg",
			onclick: function()
			{
				BX.CRM.Kanban.Actions.startCallList(
					this,
					false
				);
			}.bind(this)
		});

		// call
		/*this.actionPanel.appendItem({
			id: "kanban_call",
			text: BX.message("CRM_KANBAN_PANEL_CALL"),
			onclick: function()
			{
				BX.CRM.Kanban.Actions.startCallList(
					this
				);
			}.bind(this)
		});*/

		/*// send email
		if (gridData.entityType === "LEAD")
		{
			this.actionPanel.appendItem({
				id: "kanban_email",
				text: BX.message("CRM_KANBAN_PANEL_EMAIL"),
				onclick: function()
				{
					BX.CRM.Kanban.Actions.email(
						this
					);
				}.bind(this)
			});
		}

		// accounting
		if (gridData.entityType === "DEAL")
		{
			this.actionPanel.appendItem({
				id: "kanban_account",
				text: BX.message("CRM_KANBAN_PANEL_ACCOUNTING"),
				onclick: function()
				{
					BX.CRM.Kanban.Actions.refreshaccount(
						this
					);
				}.bind(this)
			});
		}

		// open / close for all
		if (gridData.entityType !== "INVOICE")
		{
			this.actionPanel.appendItem({
				id: "kanban_open",
				text: BX.message("CRM_KANBAN_PANEL_OPEN"),
				onclick: function()
				{
					BX.CRM.Kanban.Actions.open(
						this,
						true
					);
				}.bind(this)
			});
			this.actionPanel.appendItem({
				id: "kanban_close",
				text: BX.message("CRM_KANBAN_PANEL_CLOSE"),
				onclick: function()
				{
					BX.CRM.Kanban.Actions.open(
						this
					);
				}.bind(this)
			});
		}*/
	},

	/**
	 * Show action panel.
	 * @returns {void}
	 */
	startActionPanel: function()
	{
		if (!this.actionPanel)
		{
			this.initActionPanel();
		}

		this.actionPanel.showPanel();
	},

	/**
	 * Hide action panel.
	 * @returns {void}
	 */
	stopActionPanel: function()
	{
		if (!this.actionPanel)
		{
			return;
		}

		this.actionPanel.hidePanel();
	},

	/**
	 * Reset action panel.
	 * @returns {void}
	 */
	resetActionPanel: function()
	{
		if (this.actionPanel)
		{
			this.actionPanel.removeItems();
			this.actionPanel = null;
		}
	},

	calculateTotalCheckItems: function()
	{
		if(!this.actionPanel)
		{
			this.initActionPanel();
		}

		this.actionPanel.setTotalSelectedItems(this.getChecked().length);
	},

	isMultiSelectMode: function()
	{
		return this.multiSelectMode;
	},

	onMultiSelectMode: function()
	{
		if(this.multiSelectMode)
			return;

		this.multiSelectMode = true;
		BX.addClass(this.layout.gridContainer, "crm-kanban-multi-select-mode");
	},

	resetMultiSelectMode: function()
	{
		for (var i = 0; i < this.getChecked().length; i++)
		{
			this.getChecked()[i].unSelectItem();
			if(this.getChecked()[i].layout.container && this.getChecked()[i].layout.container.classList.contains("main-kanban-item-disabled"))
			{
				BX.removeClass(this.getChecked()[i].layout.container, "main-kanban-item-disabled");
			}
		}

		this.checkedItems = [];
		this.actionItems = [];
		this.multiSelectMode = false;
		BX.removeClass(this.layout.gridContainer, "crm-kanban-multi-select-mode");
	}
};

})();