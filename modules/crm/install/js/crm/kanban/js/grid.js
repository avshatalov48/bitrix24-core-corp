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
	BX.Event.EventEmitter.setMaxListeners('Kanban.Grid:onFirstRender', 50);

	BX.Kanban.Grid.apply(this, arguments);

	BX.addCustomEvent(this, "Kanban.DropZone:onBeforeItemCaptured", BX.delegate(this.onBeforeItemCaptured, this));
	BX.addCustomEvent(this, "Kanban.DropZone:onBeforeItemRestored", BX.delegate(this.onBeforeItemRestored, this));

	BX.addCustomEvent(this, "Kanban.Grid:onBeforeItemMoved", BX.delegate(this.onBeforeItemMoved, this));
	//BX.addCustomEvent(this, "Kanban.Grid:onItemMoved", BX.delegate(this.onItemMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnAddedAsync", BX.delegate(this.onColumnAddedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnUpdated", BX.delegate(this.onColumnUpdated, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnMoved", BX.delegate(this.onColumnMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnRemovedAsync", BX.delegate(this.onColumnRemovedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnLoadAsync", BX.delegate(this.onColumnLoadAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStart", BX.delegate(this.onItemDragStartHandler, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStart", BX.delegate(this.setKanbanDragMode, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStop", BX.delegate(this.unSetKanbanDragMode, this));

	BX.addCustomEvent("BX.Main.Filter:apply", BX.delegate(this.onApplyFilter, this));
	BX.addCustomEvent("Crm.PartialEditorDialog.Close", BX.delegate(this.onPartialEditorClose, this));
	BX.addCustomEvent("onPullEvent-crm", BX.proxy(this.onPullEventHandlerCrm, this));
	BX.addCustomEvent("onPullEvent-im", BX.proxy(this.onPullEventHandlerCrm, this));
	BX.addCustomEvent("onCrmActivityTodoChecked", BX.proxy(this.onCrmActivityTodoChecked, this));
	BX.addCustomEvent("SidePanel.Slider:onClose", BX.proxy(this.onSliderClose, this));
	BX.addCustomEvent("BX.CRM.Kanban.Item.select", BX.proxy(this.startActionPanel, this));
	BX.addCustomEvent("BX.CRM.Kanban.Item.unSelect", BX.proxy(this.onItemUnselect, this));
	BX.addCustomEvent("BX.UI.ActionPanel:clickResetAllBlock", BX.proxy(this.resetMultiSelectMode, this));
	BX.addCustomEvent('BX.UI.ActionPanel:hidePanel', BX.proxy(this.showUiToolbarContainer, this));
	BX.addCustomEvent('BX.UI.ActionPanel:showPanel', BX.proxy(this.hideUiToolbarContainer, this));
	BX.addCustomEvent("BX.Crm.EntityEditorSection:onOpenChildMenu", BX.proxy(this.onOpenEditorMenu, this));
	BX.addCustomEvent("BX.Crm.EntityEditor:onConfigScopeChange", BX.proxy(this.onConfigEditorScopeChange, this));
	BX.addCustomEvent("BX.Crm.EntityEditor:onConfigReset", BX.proxy(this.onConfigEditorReset, this));
	BX.addCustomEvent("BX.Crm.EntityEditor:onForceCommonConfigScopeForAll", BX.proxy(this.onForceCommonEditorConfigScopeForAll, this));
	// BX.addCustomEvent("BX.CRM.Kanban.Item.select", BX.proxy(this.onMultiSelectMode, this));
	BX.addCustomEvent("onPopupShow", BX.proxy(this.onPopupShow, this));
	BX.addCustomEvent("onPopupClose", BX.proxy(this.onPopupClose, this));
	BX.addCustomEvent("CrmDragItemDragRelease", BX.proxy(this.onEditorDragItemRelease, this));
	BX.addCustomEvent(window, "onCrmEntityCreate", BX.delegate(this.onCrmEntityCreateDeadlinesView, this));
	BX.addCustomEvent(this, "Kanban.Grid:onRender", BX.Runtime.debounce(this.handleHintForNotVisibleColumns, 400, this));

	//setInterval(BX.proxy(this.loadNew, this), this.loadNewInterval * 1000);
	this.bindEvents();
	BX.CRM.Kanban.Grid.Instance = this;
};

BX.CRM.Kanban.Grid.Instance = null;

BX.CRM.Kanban.Grid.getInstance = function()
{
	return BX.CRM.Kanban.Grid.Instance;
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
	customActionPanel: null,
	currentNode: null,
	itemMoving: null,
	actionItems: [],
	checkedItems: [],
	progressBarEditor: null,
	ccItem: null,
	restItem: null,
	popupCancel: null,
	handleScrollWithOpenPopupInKanbanColumn: null,
	dropZonesShow: false,
	schemeInline: null,
	isBindEvents: false,
	fieldsSelectors: {},
	headersSections: {},
	animationDuration: 800,
	hintForNotVisibleItems: null,
	handleHideHintForNotVisibleItems: null,

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

			if (!this.isCheckedItem(itemToArray))
			{
				this.checkedItems.push(itemToArray);
			}

			BX.addClass(itemToArray.checkedButton, "crm-kanban-item-checkbox-checked");
			BX.addClass(itemToArray.container, "crm-kanban-item-selected");

			BX.onCustomEvent("BX.CRM.Kanban.Item.select", [itemToArray]);
		}
	},

	isCheckedItem: function(item)
	{
		var checkedItems = this.checkedItems;
		for (var i = 0, c = checkedItems.length; i < c; i++)
		{
			if (checkedItems[i]['id'] === item['id'])
			{
				return true;
			}
		}

		return false;
	},

	onCrmEntityCreateDeadlinesView: function(entityData)
	{
		var context = entityData.sender.getContext();
		if (context['VIEW_MODE'] === 'DEADLINES')
		{
			this.loadNew(
				entityData.entityId,
				true
			);
		}
	},

	onEditorDragItemRelease: function()
	{
		var columns = this.getColumns();
		for (var i = 0, c = columns.length; i < c; i++)
		{
			if(!columns[i].isEditorOpen())
			{
				columns[i].cleanEditorNode();
				columns[i].editor = null;
			}
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
			this.actionItems.push(parseInt(items[i].id, 10))
		}

		return this.actionItems;
	},

	/**
	 * Bind some events.
	 * @returns {void}
	 */
	bindEvents: function()
	{
		if (!this.isBindEvents)
		{
			// BX.addCustomEvent("BX.UI.ActionPanel:hidePanel", this.resetSelectItems.bind(this));
			BX.bind(window, "click", function(el)
			{

				if(this.dropZonesShow)
				{
					return;
				}

				this.isItKanban(el.target) ? this.currentNode = el.target : this.currentNode = null;

				if(
					!BX.findParent(el.target, {'className': 'main-kanban-item'})
					&& !BX.findParent(el.target, {'className': 'ui-action-panel'})
					&& !BX.findParent(el.target, {'className': 'ui-action-panel-item-popup-menu'})
				)
				{
					this.unSetKanbanDragMode();
					this.resetMultiSelectMode();
				}
			}.bind(this));

			BX.bind(window, "keydown", function(el)
			{

				if(this.dropZonesShow)
				{
					return;
				}

				if(el.code === "Escape")
				{
					this.resetMultiSelectMode();
					this.unSetKanbanDragMode();
				}
			}.bind(this));

			BX.addCustomEvent(
				window,
				'Crm.PartialEditorDialog.Close',
				function (editor, params)
				{
					if (params.isCancelled && this.itemMoving.item)
					{
						this.moveItem(
							this.itemMoving.item,
							this.itemMoving.oldColumn,
							this.itemMoving.oldNextSiblingId
						);
					}
				}.bind(this)
			);

			BX.Event.EventEmitter.subscribe(
				'Crm.Kanban.Column:onItemAdded',
				function(event){
					if (
						this.itemMoving
						&& this.itemMoving.item.id === event.data.item.id
						&& this.items[this.itemMoving.item.id] !== undefined
						&& this.itemMoving.item.columnId === this.items[this.itemMoving.item.id].columnId
					)
					{
						const column = this.getColumn(event.data.item.columnId);
						if (
							event.data.item.columnId === event.data.oldColumn.id
							&& column.data.type === 'PROGRESS'
						)
						{
							return;
						}

						// @todo check this for ticket 0143009
						//this.itemMoving.oldColumn = event.data.oldColumn;
						this.onItemMoved(
							event.data.item,
							event.data.targetColumn,
							event.data.beforeItem
						);
					}
				}.bind(this)
			);

			BX.Event.EventEmitter.subscribe('crm-kanban-settings-fields-view', function ()
			{
				this.showFieldsSelectPopup('view');
			}.bind(this));

			BX.Event.EventEmitter.subscribe('crm-kanban-settings-fields-edit', function ()
			{
				this.showFieldsSelectPopup('edit');
			}.bind(this));

			var toolbarComponent = BX.Reflection.getClass('BX.Crm.ToolbarComponent')
				? BX.Reflection.getClass('BX.Crm.ToolbarComponent').Instance
				: null;

			if (this.getData().isDynamicEntity && toolbarComponent)
			{
				toolbarComponent.subscribeTypeUpdatedEvent(function() {
					if (BX.Reflection.getClass('BX.Crm.Router.Instance.getKanbanUrl'))
					{
						var entityTypeId =
							this.getData().hasOwnProperty('entityTypeInt')
								? BX.Text.toInteger(this.getData().entityTypeInt)
								: 0
						;

						var categoryId =
							this.getData().params.hasOwnProperty('CATEGORY_ID')
								? BX.Text.toInteger(this.getData().params.CATEGORY_ID)
								: 0
						;

						var newUrl = BX.Crm.Router.Instance.getKanbanUrl(entityTypeId, categoryId);
						if (newUrl)
						{
							window.location.href = newUrl;
							return;
						}
					}

					window.location.reload();
				}.bind(this));
				toolbarComponent.subscribeCategoriesUpdatedEvent(function() {
					this.reload();
				}.bind(this));
			}

			this.isBindEvents = true;
		}
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
		this.stopActionPanel(true);
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
		if (this.restItem && !gridData.restDemoBlockShow)
		{
			this.hideItem(this.restItem);
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

		if (this.layout.gridContainer.firstChild !== null)
		{
			var styles = document.createTextNode(".main-kanban-dropzone:first-child, main-kanban-dropzone:last-child {" +
				"max-width: " + (this.layout.gridContainer.firstChild.offsetWidth + 3) + "px; " +
				"min-width: " + (this.layout.gridContainer.firstChild.offsetWidth + 3) + "px;}");
			styleNode.appendChild(styles);
			head.appendChild(styleNode);
		}
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
				: "/bitrix/components/bitrix/crm.kanban/ajax.old.php"
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
		data.viewMode = gridData.viewMode;
		data.version = 2;
		data.ajaxParams = this.ajaxParams;
		data.entityPath = gridData.entityPath;

		this.setAjaxParams({});

		if (data.action !== "undefined")
		{
			url += url.indexOf("?") === -1 ? "?" : "&";
			url += "action=" + data.action;
		}

		if (this.isMultiSelectMode())
		{
			url += "&group=yes"
		}

		if (this.isCicleRequest(data))
		{
			this.reload();
		}
		else
		{
			BX.ajax({
				method: "POST",
				dataType: dataType,
				url: url,
				data: data,
				onsuccess: onsuccess,
				onfailure: onfailure
			});
		}
	},

	/**
	 * This is a crutch that will serve as the final frontier against kanban loops
	 * until we find all the scenarios where customers have kanban loops.
	 * @param data
	 * @returns {boolean}
	 */
	isCicleRequest: function(data)
	{
		if (data.action !== 'page')
		{
			return false;
		}

		var ciclePeriod = 8 * 1000; // 8 seconds
		var maxRequestsInPeriod = 5;
		var setCicleRequestParams = function(cicleRequestParams)
		{
			BX.localStorage.set('crm-kanban-cicle-request-params', cicleRequestParams, ciclePeriod);
		}

		var params = BX.localStorage.get('crm-kanban-cicle-request-params');
		if (!params)
		{
			params = this.getEmptyCicleRequestParams();
			params.total += 1;
			setCicleRequestParams(params);
			return false;
		}

		var offset = Date.now() - params.startTime;
		if (offset < ciclePeriod && params.total >= maxRequestsInPeriod)
		{
			setCicleRequestParams(this.getEmptyCicleRequestParams());
			return true;
		}

		if (offset > ciclePeriod)
		{
			params = this.getEmptyCicleRequestParams();
		}

		params.total += 1;
		setCicleRequestParams(params);

		return false;
	},

	getEmptyCicleRequestParams: function()
	{
		return {
			total: 0,
			startTime: Date.now(),
		};
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
					listUserData: this.getData().admins,
					notificationHandlerUrl: this.getAjaxHandlerPath() +
											"?action=notifyAdmin&version=2&entity_type=" +
											gridData.entityType,
					popupTexts: {
						sendButton: BX.message("CRM_KANBAN_NOTIFY_BUTTON"),
						title: BX.message("CRM_KANBAN_NOTIFY_TITLE"),
						header: BX.message("CRM_KANBAN_NOTIFY_HEADER"),
						description: BX.message("CRM_KANBAN_NOTIFY_TEXT2")
					}
				});
			}
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
	 * @param {BX.CRM.Kanban.Column} column
	 * @returns {BX.Promise}
	 */
	getColumnItems: function(column)
	{
		var promise = new BX.Promise();

		this.data.params['total'] = column.getTotal();
		this.data.params['itemsCount'] = column.getItemsCount();

		var pagination = column.getPagination();

		/**
		 *  if there is a large number of changes in elements in real time, then the value of the total number
		 *  of elements in the column may become irrelevant and an erroneous request will be sent with a page number
		 *  that is outside the range of acceptable values, because of this, the request will return a selection
		 *  for the first page (see "$this->NavPageNomer = ..." in DBNavStart() function)
		 *  To eliminate this error, we check in advance if it is possible to get the items on the next page.
		 */
		if (column.getTotal() < pagination.getPage() * column.blockSize)
		{
			column.setTotal(column.getItemsCount());
			promise.fulfill([]);
			return promise;
		}

		column.loadingInProgress = true;

		var page = pagination.getPage() + 1
		this.ajax({
				action: "page",
				page: page,
				column: column.getId(),
				onlyItems: (page > 1 ? 'Y' : 'N')
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
	 * @param {bool} incColumnPrice
	 * @returns {void}
	 */
	addItemTop: function(data, incColumnPrice)
	{
		var column = this.getColumn(data.columnId);
		var columnItems = column ? column.getItems() : [];
		incColumnPrice = (typeof incColumnPrice !== 'undefined' ? incColumnPrice : true);

		// get first item in column
		if (columnItems.length > 0)
		{
			data.targetId = columnItems[0].getId();
		}

		// inc column price and add item
		(column && incColumnPrice) ? column.incPrice(data.data.price) : null;
		this.addItem(data);
	},

	/**
	 *
	 * @param {BX.CRM.Kanban.Item} item
	 * @param {BX.CRM.Kanban.Column} targetColumn
	 * @param {BX.CRM.Kanban.Item} beforeItem
	 * @param {bool} usePromise
	 * @returns {Promise<{status: boolean}>|boolean|Promise<void>|Promise<unknown>}
	 */
	moveItem: function(
		item,
		targetColumn,
		beforeItem,
		usePromise
	)
	{
		usePromise = (usePromise || false);
		return this.movePromisedItem(item, targetColumn, beforeItem, usePromise);
	},

	/**
	 *
	 * @param {BX.CRM.Kanban.Item} item
	 * @param {BX.CRM.Kanban.Column} targetColumn
	 * @param {BX.CRM.Kanban.Item} beforeItem
	 * @param {bool} usePromise
	 * @returns {Promise<unknown[]>|boolean|Promise<void>|Promise<{status: boolean}>}
	 */
	movePromisedItem: function(
		item,
		targetColumn,
		beforeItem,
		usePromise
	)
	{
		var notChangeTotal = (item.notChangeTotal || false);
		item = this.getItem(item);
		item.notChangeTotal = notChangeTotal;

		targetColumn = this.getColumn(targetColumn);

		if (!item || !targetColumn || item === beforeItem)
		{
			if (usePromise)
			{
				return Promise.resolve({
					status: false,
				});
			}
			return false;
		}

		beforeItem = (beforeItem ? this.getItem(beforeItem) : (targetColumn.items[0] || null));
		var currentColumn = item.getColumn();
		var targetColumnId = targetColumn.getId();
		var gridData = this.getData();
		var targetColumnData = targetColumn.getData();

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
				if (this.getTypeInfoParam('hasRestictionToMoveToWinColumn') && targetColumnData.type === "WIN")
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
				else if (itemData['updateRestrictionCallback'])
				{
					try
					{
						eval(itemData['updateRestrictionCallback']);
					}
					catch (e)
					{
						console.log('update action restricted');
					}
					this.resetMultiSelectMode();

					if (usePromise)
					{
						return Promise.resolve();
					}
					return;
				}
			}

			if (error)
			{
				this.showNotCompletedPopup(gridData);
			}

			var itemsChecked = this.getChecked();

			var removePromises = [];
			for (var i = 0; i < itemsChecked.length; i++)
			{
				if(itemsChecked[i] !== item && itemsChecked[i].getColumn() !== targetColumn)
				{
					itemsChecked[i].getColumn().layout.total.textContent = +itemsChecked[i].getColumn().layout.total.innerHTML - 1;
				}

				removePromises.push(currentColumn.removeItem(itemsChecked[i]));
			}

			var checked = this.getChecked();

			if (usePromise)
			{
				return Promise.all(removePromises).then(function ()
				{
					checked.forEach(function(item){
						item.useAnimation = false;
						item.layout.container.style.opacity = 1;
					});
					targetColumn.addItems(checked, beforeItem);

					this.resetMultiSelectMode();
					this.stopActionPanel();
				}.bind(this));
			}

			targetColumn.addItems(checked, beforeItem);
			this.resetMultiSelectMode();
			this.stopActionPanel();

			return;
		}

		item.beforeItem = beforeItem;
		currentColumn.removeItem(item).then(function (){
			targetColumn.addItem(item, item.beforeItem);
		});

		this.stopActionPanel();

		if (usePromise)
		{
			return Promise.resolve({
				status: true,
			});
		}

		return true;
	},

	/**
	 * Load new items by interval.
	 * @param {int|int[]} id Entity id or array of entity ids (optional).
	 * @param {boolean} force Force load without filter.
	 * @param {boolean} forceUpdate Force update entity.
	 * @param {boolean} onlyItems
	 * @param {boolean} useAnimation
	 * @returns {Promise}
	 */
	loadNew: function(id, force, forceUpdate, onlyItems, useAnimation)
	{
		var gridData = this.getData();
		var entityIds = (typeof id !== 'undefined' ? (Array.isArray(id) ? id : [id]) : 0);

		if (document.hidden)
		{
			return Promise.reject(new Error('Tab is not active'));
		}

		var loadItemsCount = 0;
		loadItemsCount = entityIds.reduce(function(count, current, index, arr){
			var item = this.getItem(current);
			if (item && item.getData().updateRestrictionCallback)
			{
				delete arr[index];
				return count;
			}
			return ++count;
		}.bind(this), 0);

		if (!loadItemsCount)
		{
			return Promise.resolve();
		}

		useAnimation = BX.Type.isBoolean(useAnimation) ? useAnimation : false;

		return new Promise(function(resolve, reject){
			this.ajax(
				entityIds[0]
					? {
						action: "get",
						entity_id: entityIds,
						force: force === true ? "Y" : "N",
						onlyItems: (onlyItems === true ? 'Y' : 'N')
					}
					: {
						action: "get",
						min_entity_id: gridData.lastId,
						force: force === true ? "Y" : "N",
						onlyItems: (onlyItems === true ? 'Y' : 'N')
					},
				function (data)
				{
					if (data && data.items)
					{
						var worked = false;
						if (data.items.length)
						{
							var titlesForRender = {};
							for (var i = data.items.length - 1; i >= 0; i--)
							{
								var item = data.items[i];
								item.useAnimation = useAnimation;
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
										existItem.data.price = item.data.price;

										const sorter = BX.CRM.Kanban.Sort.Sorter.createWithCurrentSortType(newColumn.getItems());
										const beforeItem = sorter.calcBeforeItemByParams(item.data.sort);

										if (newColumn !== existColumn || forceUpdate === true)
										{
											item.notChangeTotal = true;
											if (beforeItem)
											{
												item.targetId = beforeItem.getId();
											}
											this.updateItem(item.id, item);
											titlesForRender[newColumn.getId()] = newColumn;
										}
										else if (newColumn.getPreviousItemSibling(existItem) !== beforeItem)
										{
											this.moveItem(existItem, newColumn, beforeItem);
										}
									}
									else
									{
										this.removeItem(existItem);
									}
								}
								else if (item.id && this.getColumn(item.columnId) === null)
								{
									BX.onCustomEvent(this, "Kanban.Column:render");
								}
								else if (item.id)
								{
									this.addItemTop(item);
								}
								if (!entityIds[0])
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

						if (!worked && entityIds[0])
						{
							var item = this.getItem(entityIds[0]);
							if (item)
							{
								var itemData = item.getData();
								var column = item.getColumn();

								column.decPrice(itemData.price);
								this.removeItem(entityIds[0]);
							}
						}
					}
					resolve(data);
				}.bind(this),
				function (error)
				{
					reject();
				}.bind(this)
			);
		}.bind(this));
	},

	/**
	 *
	 * @param {Number} item
	 * @param {BX.CRM.Kanban.Item} options
	 * @returns {boolean}
	 */
	updateItem: function(item, options)
	{
		item = this.getItem(item);
		if (!item)
		{
			return false;
		}

		if (BX.Kanban.Utils.isValidId(options.columnId) && options.columnId !== item.getColumn().getId())
		{
			if (options.notChangeTotal)
			{
				item.notChangeTotal = options.notChangeTotal;
			}
			if (options.useAnimation)
			{
				item.useAnimation = options.useAnimation;
			}
			this.moveItem(item, this.getColumn(options.columnId), this.getItem(options.targetId));
		}

		var eventArgs = ['UPDATE', { task: item, options: options }];

		BX.onCustomEvent(window, 'tasksTaskEvent', eventArgs);

		item.setOptions(options);
		item.render();

		return true;
	},

	/**
	 * Hook on item drag start.
	 * @param {BX.CRM.Kanban.Item} item
	 * @returns {void}
	 */
	onItemDragStart: function(item)
	{
		this.setDragMode(BX.Kanban.DragMode.ITEM);

		if (parseInt(item.getId()) < 0)
		{
			return;
		}

		var items = this.getItems();
		var itemColumnId = item.getColumnId();

		// disable move for win lead
		if (item.isItemMoveDisabled())
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
	 * @returns {void}
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
				BX.CRM.Kanban.Actions.delete(
					this,
					ids.length ? ids : parseInt(item.getId(), 10),
					drop
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
		if (this.isBlockedIncomingMoving(event.targetColumn))
		{
			BX.UI.Notification.Center.notify(
				{
					content: BX.message('CRM_KANBAN_MOVE_ITEM_TO_COLUMN_BLOCKED_2'),
					autoHideDelay: 5000,
				}
			);

			event.denyAction();
			return;
		}

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
	 * @param {BX.CRM.Kanban.Item} item
	 * @param {BX.Kanban.Column|BX.Kanban.DropZone} targetColumn
	 * @param {BX.CRM.Kanban.Item} [beforeItem]
	 * @param {Boolean} [skipHandler]
	 * @returns {void}
	 */
	onItemMoved: function(item, targetColumn, beforeItem, skipHandler)
	{
		var itemData = item.getData();
		var columnId = targetColumn.getId();
		var gridData = this.getData();
		var isDropZone = targetColumn instanceof BX.Kanban.DropZone;

		if (
			targetColumn.getId() !== "DELETED"
			&& itemData['updateRestrictionCallback']
			&& BX.Type.isString(itemData['updateRestrictionCallback'])
			&& columnId !== this.itemMoving.oldColumn.getId()
		)
		{
			try
			{
				eval(itemData['updateRestrictionCallback']);
			}
			catch (e)
			{
				console.log('update action restricted');
			}
			if (isDropZone)
			{
				this.itemMoving.dropEvent.denyAction();
			}
			else
			{
				this.moveItem(
					item,
					this.itemMoving.oldColumn,
					this.itemMoving.oldNextSiblingId
				);
			}

			return;
		}

		var isItemDataHasRequiredColumn = (
			itemData.required
			&& itemData.required[columnId]
			&& itemData.required[columnId].length > 0
		);

		// first checking if targetColumn require some fields
		if (
			isItemDataHasRequiredColumn
			&& this.itemMoving.oldColumn.getId() !== targetColumn.getId()
			&& !item.isChangedInPullRequest()
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

			// if the item was loaded from a pull request, remove the required fields already set there
			if (item.rawData && typeof item.rawData === 'object')
			{
				var requiredFields = itemData.required[columnId];
				for (var i = 0, c = itemData.required[columnId].length; i < c; i++)
				{
					var key = itemData.required[columnId][i];
					if (
						!(
							typeof item.rawData[key] === 'undefined'
							|| item.rawData[key] === null
							|| item.rawData[key] === ''
							|| (Array.isArray(item.rawData[key]) && !item.rawData[key].length)
						)
					)
					{
						requiredFields.splice(i, 1);
					}
				}
				itemData.required[columnId] = requiredFields;
			}

			if (
				itemData.required[columnId].length > 0
				&& this.getTypeInfoParam('isQuickEditorEnabled')
			)
			{
				this.itemMoving.newColumn = targetColumn;
				this.itemMoving.newNextSibling = beforeItem;
				// back to the prev place
				if (isDropZone)
				{
					this.itemMoving.dropEvent.denyAction();
				}

				if (this.getChecked().length > 1)
				{
					this.showNotCompletedPopup(gridData);
					this.resetMultiSelectMode();
				}
				else
				{
					// show editor
					this.openPartialEditor(item.getId(), columnId, itemData.required[columnId]);
					BX.addClass(
						item.layout.container,
						"main-kanban-item-waiting"
					);
				}
				return;
			}
		}

		if (!item.isChangedInPullRequest())
		{
			// show popup for lead convert
			if (
				this.getTypeInfoParam('canShowPopupForLeadConvert')
				&& targetColumn.getId() === 'CONVERTED'
				&& this.itemMoving.dropEvent
			)
			{
				BX.Crm.KanbanComponent.dropPopup(
					this,
					this.itemMoving.dropEvent
				);
			}

			// change price in old/new columns
			if (!item.notChangeTotal)
			{
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
			}

			item.notChangeTotal = false;
		}

		this.itemMoving.item.setDataKey(
			"runtimePrice",
			false
		);

		// call handler
		if (
			skipHandler !== true
			&& !item.isChangedInPullRequest()
		)
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
		if (!item.isChangedInPullRequest())
		{
			this.ajax({
					action: "status",
					entity_id: (
						this.itemMoving.groupIds
							? this.itemMoving.groupIds
							: itemId
					),
					prev_entity_id: afterItemId,
					status: targetColumnId
				},
				function(data)
				{
					if (data && !data.error)
					{
						if (
							this.getData().viewMode === BX.Crm.Kanban.ViewMode.MODE_ACTIVITIES
							&& !this.itemMoving.groupIds
							&& BX.CRM.Kanban.Restriction.Instance.isTodoActivityCreateAvailable()
						)
						{
							setTimeout(() => {
								item.showPlannerMenu(
									item.getContainer(),
									BX.Crm.Activity.TodoEditorMode.UPDATE,
									true
								);
							}, 500);
						}

						if (data.items && data.items.length > 0)
						{
							this.updateItem(itemId, data.items[0]);
						}
						else
						{
							item.setDataKey("columnId", targetColumnId);
							if (data.IS_SHOULD_UPDATE_CARD)
							{
								this.loadNew(itemId, false, true, true, true);
							}
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
		}

		if (item.isChangedInPullRequest())
		{
			this.clearItemMoving();
			item.dropChangedInPullRequest();
		}
	},

	showNotCompletedPopup: function(gridData)
	{
		var message;
		if(gridData.isDynamicEntity)
		{
			message = BX.message("CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_DYNAMIC_MSGVER_1")
		}
		else
		{
			message = BX.message("CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_" + gridData.entityType + '_MSGVER_1');
		}

		this.getPopupCancel(message).show();
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
		this.clearItemMoving();
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
				// re-set some data
				var gridData = this.getData();
				if (typeof data.customFields !== "undefined")
				{
					gridData.customFields = data.customFields;
				}
				if (typeof data.customEditFields !== "undefined")
				{
					gridData.customEditFields = data.customEditFields;
				}
				// scheme for inline edit
				if (
					typeof BX.UI.EntityScheme !== "undefined" &&
					typeof data.scheme_inline !== "undefined"
				)
				{
					gridData.schemeInline = BX.UI.EntityScheme.create(
						"kanban_scheme",
						{
							current: data.scheme_inline
						}
					);
				}
				this.setData(gridData);
				this.destroyFieldsSelectPopup();

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

				this.resetMultiSelectMode();

				setTimeout(() => {
					if (this.hasOnlyNotVisibleColumnsWithItems())
					{
						this.showHintForNotVisibleItems();
					}
				}, 20);

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

	handleHintForNotVisibleColumns: function()
	{
		if (this.hintForNotVisibleItems || !this.hasOnlyNotVisibleColumnsWithItems())
		{
			return;
		}

		this.showHintForNotVisibleItems();
	},

	hasOnlyNotVisibleColumnsWithItems: function()
	{
		const columns = this.getColumns();
		let result = false;

		for (let columnIndex = 0; columnIndex < columns.length; columnIndex++)
		{
			const column = columns[columnIndex];
			const isColumnHaveItems = column.items.filter((item) => item.id > -1).length > 0;

			if (!isColumnHaveItems)
			{
				continue;
			}

			if (this.isVisibleColumn(column))
			{
				result = false;
				break;
			}
			else
			{
				result = true;
			}
		}

		return result;

	},

	isVisibleColumn: function(column)
	{
		if (!column || !this.layout.gridContainer)
		{
			return false;
		}

		const gridContainerPos = BX.Dom.getPosition(this.layout.gridContainer);
		const columnPos = BX.Dom.getPosition(column.layout.container);

		return columnPos.left < gridContainerPos.right;
	},

	showHintForNotVisibleItems: function()
	{
		if (this.hintForNotVisibleItems)
		{
			return;
		}

		const entityType = this.getData().entityType;

		const hintTitle = BX.Loc.getMessage(`CRM_GRID_HINT_FOR_NOT_VISIBLE_${entityType}_TITLE`)
			|| BX.Loc.getMessage(`CRM_GRID_HINT_FOR_NOT_VISIBLE_${entityType}_TITLE_MSGVER_1`)
			|| BX.Loc.getMessage('CRM_GRID_HINT_FOR_NOT_VISIBLE_ELEMENT_TITLE');
		const hintText = BX.Loc.getMessage(`CRM_GRID_HINT_FOR_NOT_VISIBLE_${entityType}_TEXT`)
			|| BX.Loc.getMessage(`CRM_GRID_HINT_FOR_NOT_VISIBLE_${entityType}_TEXT_MSGVER_1`)
			|| BX.Loc.getMessage('CRM_GRID_HINT_FOR_NOT_VISIBLE_ELEMENT_TEXT');

		this.hintForNotVisibleItems = new BX.UI.Tour.Guide({
			onEvents: true,
			simpleMode: true,
			steps: [
				{
					target: '.main-kanban-ear-right',
					title: hintTitle,
					text: hintText,
					position: 'left',
				},
			],
		});

		this.hintForNotVisibleItems.showNextStep()
		this.adjustHintForNotVisibleItems();

		this.handleHideHintForNotVisibleItems = this.hideHintForNotVisibleItems.bind(this);
		BX.Event.bind(window, 'scroll', this.handleHideHintForNotVisibleItems);
		BX.Event.bind(this.layout.gridContainer, 'scroll', this.handleHideHintForNotVisibleItems);
		BX.addCustomEvent('BX.Main.Filter:apply', this.handleHideHintForNotVisibleItems);

		this.addClassEar();
	},

	hideHintForNotVisibleItems: function()
	{
		if (this.hintForNotVisibleItems)
		{
			BX.Event.unbind(this.layout.gridContainer, 'scroll', this.handleHideHintForNotVisibleItems);
			BX.Event.unbind(window, 'scroll', this.handleHideHintForNotVisibleItems);
			this.hintForNotVisibleItems.close();
			this.hintForNotVisibleItems = null;
		}

	},

	adjustHintForNotVisibleItems: function()
	{
		if (!this.hintForNotVisibleItems)
		{
			return;
		}

		const popup = this.hintForNotVisibleItems.getPopup();
		const bindElementPos = BX.Dom.getPosition(popup.bindElement);

		const {width: popupWidth} = BX.Dom.getPosition(popup.getPopupContainer());
		const angleHeight = 18;
		const angleWidth = 13;
		const angleOffset = 16;

		const newTopPos = bindElementPos.top + (bindElementPos.height / 2) - (angleHeight / 2) - angleOffset + 'px';
		const newLeftPos = (bindElementPos.left - popupWidth - angleWidth) + 'px';

		BX.Dom.style(popup.getPopupContainer(), 'top', newTopPos);
		BX.Dom.style(popup.getPopupContainer(), 'left', newLeftPos);
	},

	adjustLayout: function()
	{
		BX.Kanban.Grid.prototype.adjustLayout.apply(this, arguments);
		this.adjustHintForNotVisibleItems();
	},

	clearItemMoving: function()
	{
		this.itemMoving = null
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
	 *
	 * @param {BX.Main.Menu} menu
	 *
	 * @return {void}
	 */
	toggleCC: function(menu)
	{
		if (menu === undefined)
		{
			menu = BX.PopupMenu.getCurrentMenu();
		}

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

			if (menu)
			{
				menu.removeMenuItem('crm_kanban_cc_delimiter');
				menu.removeMenuItem('crm_kanban_cc');
			}
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
	 * Hide REST demo block.
	 * @return {void}
	 */
	toggleRest: function()
	{
		if (this.restItem)
		{
			this.hideItem(this.restItem);
		}
		this.ajax({
				action: "toggleRest"
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
	 * Add menu item for show popup for select additional fields.
	 * @param {Strings} menuId
	 * @return {void}
	 */
	addMenuAdditionalFields: function(menuId)
	{
		var menu = BX.PopupMenu.getCurrentMenu(
			menuId
		);
		var menuItems = menu.getMenuItems();

		if (
			menu &&
			menuItems &&
			menu.bindElement &&
			BX(menu.bindElement) &&
			BX.hasClass(BX(menu.bindElement), "ui-btn-icon-setting")
		)
		{
			var itemId = (menuItems.length > 0) ? menuItems[0].getId() : 0;
			var newMenuItems = [
				{
					text: BX.message("CRM_KANBAN_SETTINGS_FIELDS_VIEW"),
					onclick: function(e, /*BX.PopupMenuItem*/item)
					{
						this.showFieldsSelectPopup("view");
					}.bind(this)
				}
			];
			if (this.getData().entityType !== 'ORDER')
			{
				newMenuItems.push({
					text: BX.message("CRM_KANBAN_SETTINGS_FIELDS_EDIT"),
					onclick: function(e, /*BX.PopupMenuItem*/item)
					{
						// @todo as needed, will need to add a promise to the showQuickEditor method
						var firstColumnId = (this.columnsOrder[0] ? this.columnsOrder[0].id : null);
						if (firstColumnId && this.columns[firstColumnId].canAddItem)
						{
							this.columns[firstColumnId].showQuickEditor(true)
							this.showFieldsSelectPopup("edit");
						}
					}.bind(this)
				});
			}
			menu.addMenuItem(
				{
					text: BX.message("CRM_KANBAN_SETTINGS_TITLE"),
					items: newMenuItems
				},
				itemId
			);
		}
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

	getQuickEditor: function()
	{
		var columns = this.getColumns();

		for (var i = 0, c = columns.length; i < c; i++)
		{
			var columnEditor = columns[i].getQuickEditor();
			if (columnEditor)
			{
				return columnEditor;
			}
		}

		return null;
	},

	/**
	 * Show popup for selecting fields which must show in view / edit.
	 * @param viewType
	 */
	showFieldsSelectPopup: function(viewType)
	{
		if (!this.fieldsSelectors.hasOwnProperty(viewType))
		{
			var gridData = this.getData();
			this.fieldsSelectors[viewType] = new BX.Crm.Kanban.FieldsSelector({
				entityTypeName: gridData.entityType,
				type: viewType,
				sections: gridData.customSectionsFields,
				headersSections: gridData.headersSections || {},
				defaultHeaderSectionId: gridData.defaultHeaderSectionId || null,
				selectedFields: (viewType === 'view')
					? gridData.customFields
					: gridData.customEditFields,
				ignoredFields: gridData.customDisabledFields,
				onSelect: function(selectedItems) {
					var oldValue = [];
					if (viewType === 'view')
					{
						oldValue = gridData.customFields;
						gridData.customFields = Object.keys(selectedItems);
					}
					else
					{
						oldValue = gridData.customEditFields;
						gridData.customEditFields = Object.keys(selectedItems);
					}
					this.ajax({
							action: "saveFields",
							fields: selectedItems,
							type: viewType
						},
						function()
						{
							// for view-form just refresh
							if (viewType === "view")
							{
								this.onApplyFilter();
							}
							else
							{
								this.applyCustomEditFields(gridData.customEditFields, oldValue);
							}
						}.bind(this)
					);
				}.bind(this)
			});

		}
		this.fieldsSelectors[viewType].show();
	},
	applyCustomEditFields: function(newFields, oldFields)
	{
		var sectionEditor = this.getQuickEditor().getControlById("main");
		var fieldsToAdd = newFields.filter(
			function(fieldName)
			{
				return oldFields.indexOf(fieldName) < 0 && sectionEditor.getChildById(fieldName) === null;
			}
		);
		var fieldsToRemove = oldFields.filter(
			function(fieldName)
			{
				return newFields.indexOf(fieldName) < 0 && sectionEditor.getChildById(fieldName) !== null;
			}
		);
		// gets editor from each column and add new fields
		var columns = this.getColumns();
		for (var i = 0; i < columns.length; i++)
		{
			var columnEditor = columns[i].getQuickEditor();
			if (!columnEditor)
			{
				continue;
			}
			var element;
			// add new fields
			for (var j = 0; j < fieldsToAdd.length; j++)
			{
				element = columnEditor.getAvailableSchemeElementByName(
					fieldsToAdd[j]
				);
				if (element)
				{
					var field = columnEditor.createControl(
						element.getType(),
						element.getName(),
						{
							schemeElement: element,
							model: columnEditor._model,
							mode: columnEditor._mode
						}
					);

					if (field)
					{
						columnEditor.getControlById("main").addChild(
							field,
							{
								layout: {forceDisplay: true},
								enableSaving: false
							}
						);
					}
				}
			}
			// remove old fields
			for (var k = 0; k < fieldsToRemove.length; k++)
			{
				element = columnEditor.getSchemeElementByName(
					fieldsToRemove[k]
				);
				if (element)
				{
					var section = columnEditor.getControlById("main");
					var control = section.getChildById(fieldsToRemove[k]);
					if (control)
					{
						section.removeChild(control, { enableSaving: false });
					}
				}
			}

			columnEditor.commitSchemeChanges();
		}

		this.getQuickEditor().saveSchemeChanges().then(
			function () {
				for (var i = 0; i < columns.length; i++)
				{
					var columnEditor = columns[i].getQuickEditor();
					if (columnEditor)
					{
						columnEditor.refreshLayout();
					}
				}
			}
		);
	},

	destroyFieldsSelectPopup: function()
	{
		var customFieldsPopup = BX.Main.PopupManager.getPopupById("kanban_custom_fields");
		if (customFieldsPopup)
		{
			customFieldsPopup.destroy();
		}
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
					else if (
						key === "FILES"
						&& (
							BX.Type.isArray(eventParams.entityData['STORAGE_ELEMENT_IDS'])
							&& eventParams.entityData['STORAGE_ELEMENT_IDS'].reduce(function(a, b) {
								return a + b;
							}, 0) > 0
						)
					)
					{
						itrError = false;
					}
					else if (
						key === "OBSERVER" &&
						eventParams.entityData["OBSERVER_IDS"].length
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

				// @todo #015661 it may be necessary to rollback commit after merging with mobile/crm
				if (eventParams.entityData["OPPORTUNITY_ACCOUNT"])
				{
					this.itemMoving.price = parseFloat(eventParams.entityData["OPPORTUNITY_ACCOUNT"]);
					this.itemMoving.item.setDataKey(
						"price",
						this.itemMoving.price
					);
					this.itemMoving.item.setDataKey(
						"price_formatted",
						eventParams.entityData["FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY"]
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
			// // move visual and save
			// this.onItemMoved(
			// 	this.itemMoving.item,
			// 	this.itemMoving.newColumn,
			// 	this.itemMoving.newNextSibling
			// );
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
		// if (command === "activity_add" && /*params.COMPLETED !== "Y" &&*/
		// 	params.OWNER_TYPE_NAME === gridData.entityType
		// )
		// {
		// 	var item = this.getItem(params.OWNER_ID);
		// 	if (item)
		// 	{
		// 		this.loadNew(item.getId());
		// 	}
		// 	else
		// 	{
		// 		this.loadNew();
		// 	}
		// }

		// new element by delegate
		if (command === "notify")
		{
			// lead / deal
			// var matches = params.originalTag.match(
			// 	new RegExp("CRM\\|" + gridData.entityType + "_RESPONSIBLE\\|([\\d]+)")
			// );
			// if (matches && matches[1])
			// {
			// 	this.loadNew(matches[1]);
			// }
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
			this.loadNew(match[1], false, true, true, true);
		}
	},

	/**
	 * On popup show.
	 * @param {BX.PopupWindow} popupWindow
	 * @returns {void}
	 */
	onPopupShow: function(popupWindow)
	{
		if (this.isPopupInKanbanColumn(popupWindow)) {

			if (this.handleScrollWithOpenPopupInKanbanColumn) {
				this.onPopupClose();
			}

			this.handleScrollWithOpenPopupInKanbanColumn = (e) => {
				popupWindow.close();
			}

			BX.Event.EventEmitter.subscribe(this, 'Kanban.Column:onScroll', this.handleScrollWithOpenPopupInKanbanColumn);
			BX.Event.bind(window, 'scroll', this.handleScrollWithOpenPopupInKanbanColumn);
			BX.Event.bind(this.layout.gridContainer, 'scroll', this.handleScrollWithOpenPopupInKanbanColumn);
		}

		var kanbanSettingsClasses = [
			'menu-popup-toolbar_lead_list_menu',
			'menu-popup-toolbar_deal_list_menu',
			'menu-popup-toolbar_order_kanban_menu',
			'menu-popup-toolbar_quote_list_menu'
		];
		var notCsClasses = [
			'menu-popup-toolbar_order_kanban_menu',
			'menu-popup-toolbar_quote_list_menu'
		];
		var newKanbanSettingsClasses = [
			'toolbar_lead_list_settings_menu',
			'toolbar_deal_list_settings_menu'
		];

		// add some menu item
		if (kanbanSettingsClasses.indexOf(popupWindow.uniquePopupId) !== -1)
		{
			var popupId = popupWindow.uniquePopupId.substr(11);
			this.addMenuAdditionalFields(popupId);
			if (notCsClasses.indexOf(popupWindow.uniquePopupId) === -1)
			{
				this.addMenuToggleCS(popupId);
			}
		}
		else if (newKanbanSettingsClasses.indexOf(popupWindow.uniquePopupId) !== -1)
		{
			var settingsButtonMenu = this.getSettingsButtonMenu();
			if (settingsButtonMenu !== null)
			{
				var gridData = this.getData();
				settingsButtonMenu.addMenuItem({
					id: 'crm_kanban_cc_delimiter',
					delimiter: true
				}, null);
				settingsButtonMenu.addMenuItem({
					id: 'crm_kanban_cc',
					text: gridData.contactCenterShow? BX.message("CRM_KANBAN_HIDE_CC") : BX.message("CRM_KANBAN_SHOW_CC"),
					onclick: function(event)
					{
						this.toggleCC(settingsButtonMenu);
					}.bind(this)
				}, null);
			}
		}
	},

	/**
	 * On popup close.
	 * @returns {void}
	 */
	onPopupClose: function() {
		if (this.handleScrollWithOpenPopupInKanbanColumn) {
			BX.Event.EventEmitter.unsubscribe(this, 'Kanban.Column:onScroll', this.handleScrollWithOpenPopupInKanbanColumn);
			BX.Event.unbind(window, 'scroll', this.handleScrollWithOpenPopupInKanbanColumn);
			BX.Event.unbind(this.layout.gridContainer, 'scroll', this.handleScrollWithOpenPopupInKanbanColumn);

			this.handleScrollWithOpenPopupInKanbanColumn = null;
		}
	},

	/**
	 * Is popup kanban column.
	 * @param {BX.PopupWindow} popupWindow
	 * @returns {boolean}
	 */
	isPopupInKanbanColumn(popupWindow)
	{
		const kanbanColumnClassname = 'main-kanban-column';
		let kanbanColumnElem = popupWindow.bindElement;

		while (kanbanColumnElem && !BX.Dom.hasClass(kanbanColumnElem, kanbanColumnClassname)) {
			kanbanColumnElem = kanbanColumnElem.parentNode;
		}

		return !!kanbanColumnElem;
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

		var renderToNode = document.querySelector(".page-navigation");

		if(!renderToNode)
		{
			renderToNode = document.getElementById('uiToolbarContainer');
		}

		if (this.customActionPanel)
		{
			this.customActionPanel.renderTo = renderToNode;
			this.actionPanel = this.customActionPanel;

			this.actionPanel.draw();

			return;
		}

		this.actionPanel = new BX.UI.ActionPanel({
			renderTo: renderToNode,
			removeLeftPosition: true,
			maxHeight: 58,
			parentPosition: "bottom",
			autoHide: false,
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
		if (this.getTypeInfoParam('canUseIgnoreItemInPanel'))
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

		/*region Change category*/
		var items = [],
			categories = [],
			columns = this.getColumns(),
			drops = this.getDropZoneArea().getDropZones();
		for (var i = 0, c = columns.length; i < c; i++)
		{
			categories.push({
				id: columns[i].id,
				name: columns[i].name,
				blockedIncomingMoving: this.isBlockedIncomingMoving(columns[i]),
			});
		}
		for (var i = 0, c = drops.length; i < c; i++)
		{
			var dropData = drops[i].getData();
			if (
				(
					gridData.entityType === "LEAD"
					&& dropData.type === "LOOSE"
				)
				||
				(
					gridData.entityType !== "LEAD"
					&& dropData.type
				)
			)
			{
				categories.push({
					id: drops[i].id,
					name: drops[i].name,
					blockedIncomingMoving: this.isBlockedIncomingMoving(columns[i]),
				});
			}
		}
		for (var i = 0, c = categories.length; i < c; i++)
		{
			if (categories[i].blockedIncomingMoving)
			{
				continue;
			}

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
			text: BX.message("CRM_KANBAN_PANEL_STAGE"),
			items: items,
			icon: (gridData.entityType === "DEAL" || gridData.isDynamicEntity)
				? "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-stage.svg"
				: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-status.svg"
		});
		/* endregion */

		// change category
		if (gridData.categories && gridData.categories.length)
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
				text: BX.message("CRM_KANBAN_PANEL_CATEGORY2"),
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

						var target = (
							item.layout.container === undefined
								? item.actionPanel.layout.more
								: item.layout.container
						);
						userSelector.open(target);
					}.bind(this), 100);
				}.bind(this)
			});
		}

		// create task
		if (this.getTypeInfoParam('canUseCreateTaskInPanel'))
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

		if (this.getTypeInfoParam('canUseCallListInPanel'))
		{
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
		}

		// merge
		if (this.getTypeInfoParam('canUseMergeInPanel'))
		{

			this.actionPanel.appendItem({
				id: "kanban_merge",
				text: BX.message("CRM_KANBAN_PANEL_MERGE"),
				icon: "/bitrix/js/crm/kanban/images/crm-kanban-actionpanel-merge.svg",
				onclick: function()
				{
					BX.CRM.Kanban.Actions.merge(
						this
					);
				}.bind(this)
			});
		}

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

	isBlockedIncomingMoving: function(column)
	{
		return ((column && column.data && column.data.blockedIncomingMoving) || false);
	},

	hideUiToolbarContainer()
	{
		var uiToolbarContainer = document.getElementById('uiToolbarContainer');
		BX.Dom.addClass(uiToolbarContainer, '--transparent');
	},

	showUiToolbarContainer()
	{
		var uiToolbarContainer = document.getElementById('uiToolbarContainer');
		BX.Dom.removeClass(uiToolbarContainer, '--transparent');
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
	stopActionPanel: function(force = false, resetMultiSelectMode = false)
	{
		if (!this.actionPanel)
		{
			return;
		}

		if (force || !this.getChecked().length)
		{
			this.actionPanel.hidePanel();
		}
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

		if (this.customActionPanel)
		{
			this.customActionPanel.removeItems();
			this.customActionPanel = null;
		}
	},

	onItemUnselect: function(itemInArray)
	{
		this.stopActionPanel();
	},

	/**
	 * Set Custom Action Panel
	 * @param {BX.UI.ActionPanel} actionPanel
	 */
	setCustomActionPanel: function (actionPanel)
	{
		this.customActionPanel = actionPanel;
	},

	reload: function ()
	{
		this.resetMultiSelectMode();
		this.unSetKanbanDragMode();
		this.onApplyFilter();
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
	},

	onOpenEditorMenu: function(editor, eventArgs)
	{
		var gridData = this.getData();

		// redefine editor custom field
		var columnEditor = this.getQuickEditor();
		if (columnEditor)
		{
			gridData.customEditFields = [];

			var section = columnEditor.getControlById("main");
			for (var i = 0, c = section._fields.length; i < c; i++)
			{
				gridData.customEditFields.push(
					section._fields[i].getId()
				);
			}
			this.setData(gridData);
		}

		// build new items for editor menu
		var menuItems = [], editorMenuPopup = null;
		menuItems.push({
			id: menuItems.length + 1,
			text: BX.message("CRM_KANBAN_CUSTOM_FIELDS_VIEW"),
			onclick: function() {
				this.showFieldsSelectPopup("view", editor);
			}.bind(this)
		});
		menuItems.push({
			id: menuItems.length + 1,
			text: BX.message("CRM_KANBAN_CUSTOM_FIELDS_EDIT"),
			onclick: function() {
				this.showFieldsSelectPopup("edit", editor);
			}.bind(this)
		});
		editorMenuPopup = new BX.PopupMenuWindow(
			"crm-kanban-qiuck-form-add-fields-popup",
			editor._addChildButton,
			menuItems,
			{
				autohide: true,
				bindOptions: { forceBindPosition: true },
				autoHide: true,
				cacheable: false,
				closeByEsc: true
			}
		);
		editorMenuPopup.show();

		// cancel system menu
		eventArgs["cancel"] = true;
	},

	onConfigEditorScopeChange: function()
	{
		this.onApplyFilter();
	},

	onConfigEditorReset: function()
	{
		this.setAjaxParams({
			editorReset: "Y"
		});
		this.onApplyFilter();
	},

	onForceCommonEditorConfigScopeForAll: function()
	{
		this.setAjaxParams({
			editorSetCommon: "Y"
		});
		this.onApplyFilter();
	},

	insertItem: function(item, params = {})
	{
		const columnId = (params.hasOwnProperty('newColumnId') ? params.newColumnId : item.columnId);
		const newColumn = this.getColumn(columnId);

		if(newColumn)
		{
			const sorter = BX.CRM.Kanban.Sort.Sorter.createWithCurrentSortType(newColumn.getItems());

			const beforeItem = sorter.calcBeforeItem(item);
			if (
				sorter.getSortType() === BX.CRM.Kanban.Sort.Type.BY_LAST_ACTIVITY_TIME
				&& params.canShowLastActivitySortTour
			)
			{
				BX.Event.EventEmitter.emit('Kanban.Grid::onShowSortByLastActivityTour', {
					target: ".main-kanban-item[data-id='"+item.id+"']",
					stepId: 'step-sort-by-last-activity-time',
					delay: 1000,
				});
			}

			this.moveItem(item, newColumn.getId(), beforeItem);
		}
		else
		{
			this.removeItem(item);
		}
	},

	removeItem: function(itemId)
	{
		var item = this.getItem(itemId);
		if (item)
		{
			item.useAnimation = true;
			var column = item.getColumn();
			delete this.items[item.getId()];
			column.removeItem(item);
			item.dispose();
		}

		return item;
	},

	openPartialEditor: function(itemId, columnId, fieldNames)
	{
		var gridData = this.getData();
		var context = {};
		var settings = {
			entityTypeId: gridData.entityTypeInt,
			entityId: itemId,
			fieldNames: fieldNames,
			context: context,
		};
		context[this.getTypeInfoParam('stageIdKey')] = columnId;
		context['NOT_CHANGE_STATUS'] = 'Y';
		if(this.getTypeInfoParam('useFactoryBasedApproach'))
		{
			settings.title = BX.message('CRM_TYPE_ITEM_PARTIAL_EDITOR_TITLE');
			settings.isController = true;
			settings.entityTypeName = gridData.entityType;
			settings.stageId = columnId;
		}
		else
		{
			settings.title = BX.message('CRM_TYPE_ITEM_PARTIAL_EDITOR_TITLE');
		}

		this.progressBarEditor = BX.Crm.PartialEditorDialog.create(
			"progressbar-entity-editor",
			settings
		);

		window.setTimeout(
			function(){
				this.progressBarEditor.open();
			}.bind(this),
			150
		);
	},

	/**
	 * @param {string} param
	 */
	getTypeInfoParam: function(param)
	{
		var typeInfo = this.getTypeInfo();

		return (typeInfo[param] ? typeInfo[param] : false);
	},

	getTypeInfo: function()
	{
		return this.getData().typeInfo;
	},

	/**
	 * @returns {BX.Main.Menu|null}
	 */
	getSettingsButtonMenu: function()
	{
		const button = BX.Crm.ToolbarComponent.Instance.getSettingsButton();

		return button ? button.getMenuWindow() : null;
	},

	setCurrentSortType(sortType)
	{
		return new Promise((resolve, reject) => {
			this.ajax(
				{
					action: 'setCurrentSortType',
					sortType,
				},
				resolve,
				reject,
			);
		});
	}
};

})();
