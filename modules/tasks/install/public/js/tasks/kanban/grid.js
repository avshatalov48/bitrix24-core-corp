/* eslint-disable */
(function() {

"use strict";

BX.namespace("BX.Tasks.Kanban");

/**
 * @param options
 * @extends {BX.Kanban.Grid}
 * @constructor
 */
BX.Tasks.Kanban.Grid = function(options)
{
	this.ownerId = Number(options.ownerId);
	this.groupId = Number(options.groupId);
	this.groupingMode = Boolean(options.isGroupingMode);
	this.isSprintView = (options.isSprintView === 'Y');
	this.networkEnabled = options.networkEnabled || false;

	this.gridHeader = Boolean(options.gridHeader);
	this.parentTaskId = parseInt(options.parentTaskId, 10);
	this.parentTaskName = options.parentTaskName;
	this.parentTaskCompleted = Boolean(options.parentTaskCompleted);

	this.neighborGrids = [];

	BX.Kanban.Grid.apply(this, arguments);

	BX.addCustomEvent(this, "Kanban.Grid:onItemMoved", BX.delegate(this.onItemMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemAddedAsync", BX.delegate(this.onItemAddedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnMoved", BX.delegate(this.onColumnMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnUpdated", BX.delegate(this.onColumnUpdated, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnLoadAsync", BX.delegate(this.onColumnLoadAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnRemovedAsync", BX.delegate(this.onColumnRemovedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnAddedAsync", BX.delegate(this.onColumnAddedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onRender", BX.delegate(this.onRender, this));

	BX.addCustomEvent(this, "Kanban.Grid:onRender", BX.delegate(this.onGridRender, this));

	BX.addCustomEvent("BX.Main.Filter:apply", BX.delegate(this.onApplyFilter, this));
	BX.addCustomEvent("onTaskTimerChange", BX.delegate(this.onTaskTimerChange, this));
	BX.addCustomEvent("onTaskSortChanged", BX.delegate(this.onTaskSortChanged, this));
	BX.addCustomEvent("onPullEvent-im", BX.delegate(this.tasksTaskPull, this));
	BX.addCustomEvent("onPullEvent-tasks", BX.delegate(this.tasksTaskPull, this));

	BX.addCustomEvent(this, "Kanban.Grid:multiSelectModeOn", BX.delegate(this.startActionPanel, this));
	BX.addCustomEvent(this, "Kanban.Grid:multiSelectModeOff", BX.delegate(this.stopActionPanel, this));
	BX.addCustomEvent(this, "Kanban.Grid:selectItem", BX.delegate(this.setTotalSelectedItems, this));
	BX.addCustomEvent(this, "Kanban.Grid:unSelectItem", BX.delegate(this.setTotalSelectedItems, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStart", BX.delegate(this.setKanbanDragMode, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStop", BX.delegate(this.unSetKanbanDragMode, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStart", BX.delegate(this.setKanbanRealtimeMode, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemDragStop", BX.delegate(this.unSetKanbanRealtimeMode, this));

	if (this.isScrumGrid())
	{
		BX.bind(this.getGridContainer(), 'scroll', BX.delegate(this.onGridScroll, this));
	}
};

BX.Tasks.Kanban.Grid.prototype = {
	__proto__: BX.Kanban.Grid.prototype,
	constructor: BX.Tasks.Kanban.Grid,
	accessNotifyDialog: null,

	/**
	 * Perform ajax query.
	 * @param {Object} data
	 * @param {Function} onsuccess
	 * @param {Function} onfailure
	 * @returns {Void}
	 */
	ajax: function(data, onsuccess, onfailure)
	{
		var url = this.getAjaxHandlerPath();
		var gridData = this.getData();

		data.sessid = BX.bitrix_sessid();
		data.params = this.getData().params;

		if (data.action !== "undefined")
		{
			url += url.indexOf("?") === -1 ? "?" : "&";
			url += "action=" + data.action;
			if (gridData.kanbanType === "TL")
			{
				url += "&timeline=Y";
			}
			else
			{
				url += "&personal=" + data.params.PERSONAL;
			}
			if (data.groupAction === "Y")
			{
				url += "&groupMode=Y";
			}
		}

		BX.ajax({
			method: "POST",
			dataType: "json",
			url: url,
			data: data,
			onsuccess: onsuccess,
			onfailure: onfailure
		});
	},

	/**
	 * Returns true, if Kanban in realtime work mode.
	 * @return {boolean}
	 */
	isRealtimeMode: function()
	{
		return this.data.newTaskOrder === "actual";
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
				this.accessNotifyDialog = new BX.Intranet.NotifyDialog({
					listUserData: this.getData().admins,
					notificationHandlerUrl: this.getAjaxHandlerPath() + "?action=notifyAdmin",
					popupTexts: {
						sendButton: BX.message("TASKS_KANBAN_NOTIFY_BUTTON"),
						title: BX.message("TASKS_KANBAN_NOTIFY_TITLE"),
						header: BX.message("TASKS_KANBAN_NOTIFY_HEADER"),
						description: BX.message("TASKS_KANBAN_NOTIFY_TEXT")
					}
				});
			}
			this.accessNotifyDialog.show();
		}
	},

	renderLayout: function()
	{
		BX.Kanban.Grid.prototype.renderLayout.apply(this, arguments);

		if (this.isScrumGridHeader())
		{
			this.observeScrumGridHeader();
		}
	},

	/**
	 * Hook on item drag start.
	 * @param {Object} item
	 * @returns {void}
	 */
	onItemDragStart: function(item)
	{
		this.setDragMode(BX.Kanban.DragMode.ITEM);

		var gridData = this.getData();
		var itemData = item.getData();

		if (gridData.kanbanType === "TL")
		{
			BX.Kanban.Grid.prototype.onItemDragStart.apply(this, arguments);
			// disable another columns
			this.getColumns().forEach(function(/*BX.Kanban.Column*/column) {
				if (!column.canAddItems())
				{
					column.disableDropping();
				}
				else if (
					!itemData.allow_change_deadline &&
					column.getId() !== item.getColumn().getId()
				)
				{
					column.disableDropping();
				}
			});
			// disable another items
			var items = this.getItems();
			for (var itemId in items)
			{
				if (items[itemId].getColumn().getId() !== item.getColumn().getId())
				{
					if (
						!itemData.allow_change_deadline ||
						!items[itemId].getColumn().canAddItems()
					)
					{
						items[itemId].disableDropping();
					}
				}
			}
			if (!itemData.allow_change_deadline)
			{
				item.getDragElement().appendChild(this.createAlertBlock(
					BX.message("TASKS_KANBAN_ME_DISABLE_DEADLINE_PART2")
				));
			}
			return;
		}

		if (!gridData.rights.canSortItem)
		{
			return;
		}

		BX.Kanban.Grid.prototype.onItemDragStart.apply(this, arguments);
	},

	createAlertBlock: function (message)
	{

		return BX.create("div", {
			props: {
				className: "tasks-kanban-item-alert"
			},
			text: message
		});

	},

	/**
	 * Add item to the column in order.
	 * @param {Object} data
	 * @returns {void}
	 */
	addItemOrder: function(data)
	{
		var gridData = this.getData();
		var columnOne = null;
		var columnItems = [];

		// get columnId
		if (!data.columnId && data.columns)
		{
			for (var i = 0, c = data.columns.length; i < c; i++)
			{
				columnOne = this.getColumn(data.columns[i]);
				if (columnOne)
				{
					data.columnId = columnOne.getId();
				}
			}
		}
		if (!data.columnId)
		{
			columnOne = this.getColumns()[0];
			data.columnId = columnOne.getId();
		}
		if (data.columnId && !columnOne)
		{
			columnOne = this.getColumn(data.columnId);
			if (!columnOne)
			{
				columnOne = this.getColumns()[0];
				data.columnId = columnOne.getId();
			}
		}
		if (columnOne)
		{
			columnItems = columnOne.getItems();
		}

		if (
			gridData.newTaskOrder === "desc" || // new task - in top
			this.isRealtimeMode()// realtime kanban
		)
		{
			// for realtime mode we try to find place by actual date
			if (this.isRealtimeMode() && columnItems.length > 0)
			{
				if (typeof data.data["date_activity_ts"] !== "undefined")
				{
					var activityTS = data.data["date_activity_ts"];
					if (activityTS > 0)
					{
						for (var i = 0, c = columnItems.length; i < c; i++)
						{
							if (
								data.id !== columnItems[i].getId() &&
								columnItems[i].data["date_activity_ts"] < activityTS
							)
							{
								data.targetId = columnItems[i].getId();
								break;
							}
						}
					}
				}
			}
			// get first item in column
			else if (columnItems.length > 0)
			{
				data.targetId = columnItems[0].getId();
				if (data.targetId === data.id && columnItems[1])
				{
					data.targetId = columnItems[1].getId();
				}
			}

			if (
				this.isRealtimeMode()
				&& this.getItem(data.id)
				&& columnOne
				&& !columnOne.getDraftItem()
			)
			{
				this.updateItem(data.id, data, true);
				if (data.targetId)
				{
					this.moveItem(data.id, data.columnId, data.targetId);
				}
			}
			else
			{
				this.addItem(data);
			}
		}
		// new task - in bottom (only if not exist next page)
		else if (columnOne && columnItems.length >= columnOne.total)
		{
			this.addItem(data);
		}
	},

	/**
	 * System update item.
	 * @param {Object} item
	 * @param {Object} options
	 * @param {Boolean} notDestroy
	 * @returns {void}
	 */
	updateItem: function(item, options, notDestroy)
	{
		if (
			notDestroy !== true
			&& BX.Bitrix24
			&& BX.Bitrix24.Slider
			&& BX.Bitrix24.Slider.destroy
			&& this.getItem(item)
		)
		{
			var url = this.getItem(item).getTaskUrl(item);
			BX.Bitrix24.Slider.destroy(url);
		}

		BX.Kanban.Grid.prototype.updateItem.apply(this, arguments);
	},

	removeItem: function(itemId)
	{
		var item = BX.Kanban.Grid.prototype.removeItem.apply(this, arguments);

		BX.onCustomEvent(this, 'Kanban.Grid:onItemRemoved', {itemId: itemId});

		return item;
	},

	/**
	 * Event Handler must add a promise to the 'promises' collection.
	 * @param {Array} promises
	 * @returns {void}
	 */
	onItemAddedAsync: function(promises)
	{
		promises.push(BX.delegate(this.addTask, this));
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
	 * Event handler on first render.
	 * @param {BX.Kanban.Grid} grid Current grid.
	 * @returns {void}
	 */
	onRender: function(grid)
	{
		var gridData = grid.getData();

		if (
			grid.firstRenderComplete ||
			gridData["kanbanType"] !== "TL"
		)
		{
			return;
		}

		if (gridData["setClientDate"] === true)
		{
			var promise = new BX.Promise();
			this.fadeOut();

			this.ajax({
					action: "setClientDate",
					clientDate: gridData["clientDate"],
					clientTime: gridData["clientTime"]
				},
				function(data)
				{
					this.removeItems();
					this.loadData(data);
					promise.fulfill();
					this.fadeIn();
				}.bind(this)
			);
		}
	},

	/**
	 * Get items from one columns.
	 * @param {BX.Kanban.Column} column
	 * @returns {BX.Promise}
	 */
	getColumnItems: function(column)
	{
		var promise = new BX.Promise();

		if (this.isGroupingMode())
		{
			promise.fulfill([]);
			return promise;
		}

		this.ajax({
				action: "getColumnItems",
				pageId: column.getPagination().getPage() + 1,
				columnId: column.getId()
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
				action: "modifyColumn",
				fields: {
					id: column.getId(),
					delete: 1
				}
			},
			function(data)
			{
				if (data && !data.error)
				{
					this.actionPanel = null;
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
	 * Add new column (stage).
	 * @param {BX.Kanban.Column} column
	 * @returns {BX.Promise}
	 */
	addStage: function(column)
	{
		var promise = new BX.Promise();
		var targetColumn = this.getPreviousColumnSibling(column);
		var targetColumnId = targetColumn ? targetColumn.getId() : 0;

		this.ajax({
				action: "modifyColumn",
				fields: {
					columnName: column.getName(),
					columnColor: column.getColor(),
					afterColumnId: targetColumnId
				}
			},
			function(data)
			{
				if (data && !data.error)
				{
					this.actionPanel = null;
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
	 * Add new task.
	 * @param {BX.Kanban.Item} item
	 * @returns {BX.Promise}
	 */
	addTask: function(item)
	{
		var promise = new BX.Promise();
		var nextItem = item.getColumn().getNextItemSibling(item);
		var gridData = this.getData();

		this.ajax({
				action: "addTask",
				taskName: item.getData().title,
				columnId: item.getColumn().getId(),
				parentTaskId: this.parentTaskId,
				beforeItemId: (gridData.newTaskOrder === "desc" && nextItem)
								? nextItem.getId() : 0
			},
			function(data)
			{
				if (data && !data.error)
				{
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
	 * Hook on item moved.
	 * @param {BX.Kanban.Item} item
	 * @param {BX.Kanban.Column} targetColumn
	 * @param {BX.Kanban.Item} [beforeItem]
	 * @returns {void}
	 */
	onItemMoved: function(item, targetColumn, beforeItem)
	{
		var itemId = item.getId();
		var afterItemId = 0;
		var beforeItemId = beforeItem ? beforeItem.getId() : 0;
		var targetColumnId = targetColumn ? targetColumn.getId() : 0;
		var gridData = this.getData();

		if (beforeItemId === 0)
		{
			afterItemId = targetColumn.getPreviousItemSibling(item);
			if (afterItemId)
			{
				afterItemId = afterItemId.getId();
			}
		}

		this.ajax({
				action: "moveTask",
				itemId: itemId,
				columnId: targetColumnId,
				beforeItemId: beforeItemId,
				afterItemId: afterItemId
			},
			function(data)
			{
				if (data && !data.error)
				{
					this.updateItem(itemId, data);
					if (gridData.kanbanType === "TL")
					{
						var deadlineText = item.getDeadline();
						BX.UI.Notification.Center.notify({
							content: deadlineText
									? BX.message("MAIN_KANBAN_NOTIFY_CHANGE_DEADLINE").replace("#date#", deadlineText)
									: BX.message("MAIN_KANBAN_NOTIFY_REMOVE_DEADLINE")
						});
					}
					if (
						typeof data.data !== "undefined" &&
						data.data.hiddenByFilter === true
					)
					{
						this.removeItem(item);
					}

					if (this.isChildScrumGrid())
					{
						var actionToCheck = (this.getFinishColumn().getId() === targetColumnId)
							? 'complete'
							: 'renew'
						;

						if (
							this.isParentTaskCompleted() && actionToCheck === 'renew'
							|| !this.isParentTaskCompleted() && actionToCheck === 'complete'
						)
						{
							this.ajax({
								action: 'needUpdateParentTaskStatus',
								parentTaskId: this.parentTaskId,
								actionToCheck: actionToCheck
							}, function(response) {
								if (!response.error && response.can)
								{
									this.updateParentTaskStatus(item, actionToCheck);
								}
								else if (response.error)
								{
									BX.Kanban.Utils.showErrorDialog(response.error, true);
								}
							}.bind(this), function(error) {
								BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
							}.bind(this));
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
	},

	updateParentTaskStatus: function(item, actionToUpdate)
	{
		var isCompleteAction = actionToUpdate === 'complete';
		var parentTaskName = BX.util.htmlspecialchars(this.parentTaskName);

		(new BX.UI.Dialogs.MessageBox({
			message: isCompleteAction
				? BX.message('TASKS_SCRUM_KANBAN_PARENT_COMPLETE_MESSAGE').replace(/#name#/g, parentTaskName)
				: BX.message('TASKS_SCRUM_KANBAN_PARENT_RENEW_MESSAGE')
					.replace("#name#", parentTaskName)
					.replace("#sub-name#", BX.util.htmlspecialchars(item.data.name))
			,
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
			okCaption: isCompleteAction
				? BX.message('TASKS_SCRUM_KANBAN_PARENT_COMPLETE_OK_CAPTION')
				: BX.message('TASKS_SCRUM_KANBAN_PARENT_RENEW_OK_CAPTION')
			,
			cancelCaption: isCompleteAction
				? BX.message('TASKS_SCRUM_KANBAN_PARENT_COMPLETE_CANCEL_CAPTION')
				: BX.message('TASKS_SCRUM_KANBAN_PARENT_RENEW_CANCEL_CAPTION')
			,
			minWidth: 300,
			onOk: function (messageBox) {
				messageBox.close();
				this.ajax({
					action: (isCompleteAction ? 'completeParentTask' : 'renewParentTask'),
					taskId: this.parentTaskId,
					finishColumnId: this.getFinishColumn().getId(),
					newColumnId: this.getNewColumn().getId()
				}, function(data) {
					if (data && !data.error)
					{
						if (isCompleteAction)
						{
							this.parentTaskCompleted = true;
							BX.onCustomEvent(this, 'Kanban.Grid:onCompleteParentTask', [this]);
						}
						else
						{
							this.parentTaskCompleted = false;
							BX.onCustomEvent(this, 'Kanban.Grid:onRenewParentTask', [this]);
						}
					}
					else if (data)
					{
						BX.Kanban.Utils.showErrorDialog(data.error, true);
					}
				}.bind(this), function(error) {
					BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				}.bind(this));
			}.bind(this),
		})).show();
	},

	/**
	 *
	 * @returns {BX.Tasks.Kanban.Column[]}
	 */
	getColumns: function()
	{
		return BX.Kanban.Grid.prototype.getColumns.call(this);
	},

	getFinishColumn: function()
	{
		return this.getColumns().find(function(column) {
			return column.isFinishType();
		});
	},

	getNewColumn: function()
	{
		return this.getColumns().find(function(column) {
			return column.isNewType();
		});
	},

	isParentTaskCompleted: function()
	{
		return this.parentTaskCompleted;
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

		if (this.isScrumGridHeader())
		{
			this.moveColumnsInNeighborGrids(column, targetColumn);
		}

		this.ajax({
				action: "moveColumn",
				columnId: columnId,
				afterColumnId: afterColumnId
			},
			function(data)
			{
				if (data && data.error)
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
				action: "modifyColumn",
				fields: {
					id: columnId,
					columnName: title,
					columnColor: color
				}
			},
			function(data)
			{
				this.actionPanel = null;
				if (data && data.error)
				{
					BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
				}
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this)
		);
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
				: "/bitrix/components/bitrix/tasks.kanban/ajax.php"
		);

	},

	/**
	 * Hook on main filter applied.
	 * @param {String} filterId
	 * @param {Object} values
	 * @param {Object} filterInstance
	 * @param {Object} params
	 * @returns {void}
	 */
	onApplyFilter: function(filterId, values, filterInstance, promise, params)
	{
		if (this.isGroupingMode())
		{
			return;
		}

		this.fadeOut();
		if (params)
		{
			params.autoResolve = false;
		}
		this.ajax({
				action: "applyFilter"
			},
			function(data)
			{
				this.removeItems();
				this.loadData(data);
				if (promise)
				{
					promise.fulfill();
				}
				this.fadeIn();
			}.bind(this),
			function(error)
			{
				if (promise)
				{
					promise.reject();
				}
				this.fadeIn();
			}.bind(this)
		);
	},

	/**
	 * Hook on timer changed.
	 * @param {Object} data
	 * @returns {void}
	 */
	onTaskTimerChange: function(data)
	{
		if (
			data.taskId && data.action === "refresh_daemon_event" &&
			data.data && data.data.TIMER && data.data.TIMER.TIMER_STARTED_AT
		)
		{
			var task = this.getItem(data.taskId);
			var timestamp = Math.floor(Date.now() / 1000);
			var delta = parseInt(timestamp - data.data.TIMER.TIMER_STARTED_AT);
			if (task)
			{
				task.setDataKey("time_logs", task.getData().time_logs_start + delta);
				task.setDataKey("in_progress", true);
				task.render();
			}
		}
	},

	/**
	 * Hook on sort selector.
	 * @param {Object} data
	 * @returns {void}
	 */
	onTaskSortChanged: function(data)
	{
		var gridData = this.getData();
		gridData.newTaskOrder = data.newTaskOrder;
		this.setData(gridData);
	},

	/**
	 * @param {Object} data
	 * @return {int}
	 */
	recognizeTaskId: function(data)
	{
		var taskId = 0;

		if (data.TASK_ID)
		{
			taskId = parseInt(data.TASK_ID);
		}
		else if (data.taskId)
		{
			taskId = parseInt(data.taskId);
		}
		else if (data["entityXmlId"])
		{
			if (data["entityXmlId"].indexOf("TASK_") === 0)
			{
				taskId = parseInt(data["entityXmlId"].substr(5));
			}
		}

		return taskId;
	},

	/**
	 * Hook on pull event.
	 * @param {String} command
	 * @param {Object} data
	 * @returns {void}
	 */
	tasksTaskPull: function(command, data)
	{
		if (this.isScrumGridHeader())
		{
			return;
		}

		var taskId = this.recognizeTaskId(data);

		switch (command)
		{
			case 'task_add':
				if (this.isScrumGrid())
				{
					var taskData = data.AFTER;
					if (this.isChildScrumGrid())
					{
						if (this.isChildTask(taskData))
						{
							this.refreshTask(taskId);
						}
					}
					else
					{
						if (this.isParentTask(taskData))
						{
							this.refreshTask(taskId);
						}
					}
				}
				break;
			case "comment_add":
			case "stage_change":
			case "task_view":
				if (taskId)
				{
					if (this.isScrumGrid())
					{
						if (!this.getItems()[taskId])
						{
							return;
						}
					}

					var requestParams = this.getData().params;

					BX.ajax.runAction('tasks.task.list', {data: {
						filter: {ID: taskId},
						params: {
							RETURN_ACCESS: 'Y',
							SIFT_THROUGH_FILTER: {
								sprintKanban: (this.isScrumGrid() ? 'Y' : 'N'),
								isCompletedSprint: (this.isScrumGrid() ? requestParams.IS_COMPLETED_SPRINT : 'N'),
								userId: this.ownerId,
								groupId: this.groupId
							}
						}
					}}).then(function(response) {
						if (response.data.tasks.length > 0)
						{
							this.refreshTask(taskId);
						}
						else
						{
							this.removeItem(taskId);
						}
					}.bind(this));
				}
				break;

			case "comment_read_all":
				Object.values(this.getItems()).forEach(function(item) {
					var data = item.data;
					var isExpiredCounts = data.is_expired && !data.completed && !data.completed_supposedly;
					var counter = item.task_counter;
					var counterValue = counter.getValue();
					if (counterValue > 0 && (!isExpiredCounts || counterValue > 1))
					{
						data.counter.value = (isExpiredCounts ? 1 : 0);
						counter.update(data.counter.value);
						item.render();
					}
				});
				break;

			case "task_remove":
				if (taskId)
				{
					this.removeItem(taskId);
				}
				break;

			default:
				break;
		}
	},

	refreshTask: function(taskId)
	{
		this.ajax({
				action: 'refreshTask',
				taskId: taskId
			},
			function(data) {
				if (data && !data.error)
				{
					this.addItemOrder(data);
				}
			}.bind(this),
			function(error) {}.bind(this)
		);
	},

	/**
	 * Handler on grid render.
	 * @returns {void}
	 */
	onGridRender: function()
	{
		var grid = this.getGridContainer();

		var columnsWidth = this.getColumns().reduce(function(width, /*BX.Kanban.Column*/column) {
			return width + column.getContainer().offsetWidth;
		}, 0);

		var showBorder = (columnsWidth + 80) < grid.offsetWidth;

		this.getRenderToContainer().classList[showBorder ? "add" : "remove"]("tasks-kanban-border");
	},

	/**
	 * Change view demo.
	 * @param {Int} viewId
	 * @returns {void}
	 */
	changeDemoView: function(viewId)
	{
		// remove all columns
		var columns = this.getColumns();
		for (var i = 0, c = columns.length; i < c; i++)
		{
			this.removeColumn(columns[i]);
		}
		this.ajax({
				action: "changeDemoView",
				viewId: viewId
			},
			function(data)
			{
				this.loadData(data);
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this)
		);
	},

	/**
	 * Build the action panel.
	 */
	initActionPanel: function()
	{
		this.actionPanel = new BX.UI.ActionPanel({
			renderTo: document.querySelector(".pagetitle-wrap"),
			removeLeftPosition: true,
			maxHeight: 56,
			parentPosition: "bottom"
		});

		this.actionPanel.draw();

		var grid = this;
		var stages = [];
		var gridData = this.getData();

		// move to stage item
		if (
			gridData.kanbanType !== "TL" &&
			gridData.rights.canSortItem
		)
		{
			var changeStageHandler = function(columnId, columnName)
			{
				return function()
				{
					BX.Tasks.Kanban.Actions.simpleAction(grid, {
							action: "moveTask",
							columnId: columnId,
							columnName: BX.util.htmlspecialchars(columnName)
						}
					);
				};
			};
			// gets stages
			this.getColumns().forEach(function(/*BX.Kanban.Column*/column) {
				stages.push({
					text: column.getName(),
					onclick: changeStageHandler(column.getId(), column.getName())
				});
			});
			this.actionPanel.appendItem({
				id: "stage",
				text: BX.message("TASKS_KANBAN_PANEL_STAGE"),
				items: stages
			});
		}

		// building panel below

		this.actionPanel.appendItem({
			id: "complete",
			text: BX.message("TASKS_KANBAN_PANEL_COMPLETE"),
			onclick: function()
			{
				BX.UI.Dialogs.MessageBox.confirm(
					BX.message("TASKS_KANBAN_PANEL_CONFIRM_MESS_COMPLETE"),
					BX.message("TASKS_KANBAN_PANEL_CONFIRM_TITLE"),
					function (messageBox)
					{
						BX.Tasks.Kanban.Actions.simpleAction(grid, {
								action: "completeTask"
							}
						);
						messageBox.close();
					}
				);
			}
		});

		this.actionPanel.appendItem({
			id: "deadline",
			text: BX.message("TASKS_KANBAN_PANEL_DEADLINE"),
			onclick: function()
			{
				BX.Tasks.Kanban.Actions.deadline(
					grid,
					this.layout.container
				);
				BX.PreventDefault();
			}
		});

		this.actionPanel.appendItem({
			id: "members",
			text: BX.message("TASKS_KANBAN_PANEL_MEMBERS"),
			items: [
				{
					text: BX.message("TASKS_KANBAN_PANEL_MEMBERS_RESPONSE"),
					onclick: function()
					{
						BX.Tasks.Kanban.Actions.member(
							grid,
							this.layout.container,
							"delegateTask"
						);
						BX.PreventDefault();
					}
				},
				{
					text: BX.message("TASKS_KANBAN_PANEL_MEMBERS_CREATED"),
					onclick: function()
					{
						BX.Tasks.Kanban.Actions.member(
							grid,
							this.layout.container,
							"changeAuthorTask"
						);
						BX.PreventDefault();
					}
				},
				{
					text: BX.message("TASKS_KANBAN_PANEL_MEMBERS_CORESPONSE"),
					onclick: function()
					{
						BX.Tasks.Kanban.Actions.member(
							grid,
							this.layout.container,
							"addAccompliceTask"
						);
						BX.PreventDefault();
					}
				},
				{
					text: BX.message("TASKS_KANBAN_PANEL_MEMBERS_AUDITOR"),
					onclick: function()
					{
						BX.Tasks.Kanban.Actions.member(
							grid,
							this.layout.container,
							"addAuditorTask"
						);
						BX.PreventDefault();
					}
				}
			]
		});

		this.actionPanel.appendItem({
			id: "group",
			text: BX.message("TASKS_KANBAN_PANEL_GROUP"),
			onclick: function()
			{
				BX.Tasks.Kanban.Actions.member(
					grid,
					this.layout.container,
					"changeGroupTask",
					"group"
				);
				BX.PreventDefault();
			}
		});

		this.actionPanel.appendItem({
			id: "favorite",
			text: BX.message("TASKS_KANBAN_PANEL_FAVORITE"),
			items: [
				{
					text: BX.message("TASKS_KANBAN_PANEL_FAVORITE_ADD"),
					onclick: function()
					{
						BX.Tasks.Kanban.Actions.simpleAction(grid, {
								action: "addFavoriteTask"
							}
						);
					}
				},
				{
					text: BX.message("TASKS_KANBAN_PANEL_FAVORITE_REMOVE"),
					onclick: function()
					{
						BX.Tasks.Kanban.Actions.simpleAction(grid, {
								action: "deleteFavoriteTask"
							}
						);
					}
				}
			]
		});

		this.actionPanel.appendItem({
			id: "delete",
			text: BX.message("TASKS_KANBAN_PANEL_DELETE"),
			onclick: function()
			{
				BX.UI.Dialogs.MessageBox.confirm(
					BX.message("TASKS_KANBAN_PANEL_CONFIRM_MESS_DELETE"),
					BX.message("TASKS_KANBAN_PANEL_CONFIRM_TITLE"),
					function (messageBox)
					{
						BX.Tasks.Kanban.Actions.simpleAction(grid, {
								action: "deleteTask"
							}
						);
						messageBox.close();
					}
				);
			}
		});
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

	setTotalSelectedItems: function()
	{
		if(!this.actionPanel)
		{
			this.initActionPanel();
		}

		this.actionPanel.setTotalSelectedItems(this.getSelectedItems().size);
	},

	/**
	 * Set Kanban drag mode on.
	 * @returns {void}
	 */
	setKanbanDragMode: function()
	{
		BX.addClass(document.body, "task-kanban-drag-mode");
	},

	/**
	 * Set Kanban drag mode off.
	 * @returns {void}
	 */
	unSetKanbanDragMode: function()
	{
		BX.removeClass(document.body, "task-kanban-drag-mode");
	},

	setKanbanRealtimeMode: function()
	{
		if(this.isRealtimeMode())
		{
			this.getOuterContainer().classList.add("tasks-kanban-realtime-mode")
		}
	},

	unSetKanbanRealtimeMode: function()
	{
		if(this.isRealtimeMode())
		{
			this.getOuterContainer().classList.remove("tasks-kanban-realtime-mode")
		}
	},

	getGridContainer: function()
	{
		var gridContainer = BX.Kanban.Grid.prototype.getGridContainer.call(this);

		if (this.isScrumGridHeader())
		{
			gridContainer.style.overflow = 'hidden';
		}

		return gridContainer;
	},

	adjustHeight: function()
	{
		if (!this.isGroupingMode())
		{
			BX.Kanban.Grid.prototype.adjustHeight.call(this);
		}
	},

	observeScrumGridHeader: function()
	{
		if (typeof IntersectionObserver === 'undefined')
		{
			return;
		}

		var outerContainer = this.getOuterContainer();
		var scrumKanbanContainer = outerContainer.parentElement;
		var targetObserver = scrumKanbanContainer.querySelector('.tasks-scrum-kanban-header-target-observer');

		if (!targetObserver)
		{
			return;
		}

		var scrumGridHeaderObserver = new IntersectionObserver(function(entries) {
				if (entries[0].isIntersecting === true)
				{
					if (outerContainer.classList.contains('tasks-scrum-kanban-header'))
					{
						outerContainer.classList.remove('tasks-scrum-kanban-header');
					}
					targetObserver.classList.remove('--with-margin');
				}
				else
				{
					if (!outerContainer.classList.contains('tasks-scrum-kanban-header'))
					{
						outerContainer.classList.add('tasks-scrum-kanban-header');
					}
					targetObserver.classList.add('--with-margin');
				}
			}.bind(this),
			{
				threshold: [0]
			}
		);

		scrumGridHeaderObserver.observe(targetObserver);
	},

	onGridScroll: function(event)
	{
		this.neighborGrids.forEach(function(neighborGrid) {
			neighborGrid.getGridContainer().scrollLeft = event.target.scrollLeft;
		});
	},

	getEmptyStub: function()
	{
		if (this.isScrumGrid())
		{
			this.layout.emptyStub = document.createElement('div');

			return this.layout.emptyStub;
		}
		else
		{
			return BX.Kanban.Grid.prototype.getEmptyStub.call(this);
		}
	},

	getLeftEar: function()
	{
		if (this.isScrumGridHeader())
		{
			this.layout.earLeft = document.createElement('div');

			return this.layout.earLeft;
		}
		else
		{
			return BX.Kanban.Grid.prototype.getLeftEar.call(this);
		}
	},

	getRightEar: function()
	{
		if (this.isScrumGridHeader())
		{
			this.layout.earRight = document.createElement('div');

			return this.layout.earRight;
		}
		else
		{
			return BX.Kanban.Grid.prototype.getRightEar.call(this);
		}
	},

	/**
	 * @neighborGrid {BX.Tasks.Kanban.Grid}
	 */
	addNeighborGrid: function(neighborGrid)
	{
		if (this !== neighborGrid)
		{
			this.neighborGrids.push(neighborGrid);
		}
	},

	cleanNeighborGrids: function()
	{
		this.neighborGrids = [];
	},

	getNeighborGrids: function()
	{
		return this.neighborGrids;
	},

	removeColumnsByIdFromNeighborGrids: function(columnId)
	{
		this.neighborGrids.forEach(function(neighborGrid) {
			neighborGrid.removeColumn(neighborGrid.getColumn(columnId));
		});
	},

	moveColumnsInNeighborGrids: function(column, targetColumn)
	{
		this.neighborGrids.forEach(function(neighborGrid) {
			var neighborColumn = neighborGrid.getColumn(column.getId());
			var neighborTargetColumn = neighborGrid.getColumn(targetColumn.getId());
			if (neighborColumn && neighborTargetColumn)
			{
				neighborGrid.moveColumn(neighborColumn, neighborTargetColumn);
			}
		}.bind(this));
	},

	isScrumGridHeader: function()
	{
		return this.gridHeader;
	},

	isScrumGrid: function()
	{
		return this.isSprintView;
	},

	isGroupingMode: function()
	{
		return this.groupingMode;
	},

	isChildScrumGrid: function()
	{
		return (this.parentTaskId > 0);
	},

	isParentTask: function(taskData)
	{
		return !taskData['PARENT_ID'];
	},

	isChildTask: function(taskData)
	{
		return (taskData['PARENT_ID'] === this.parentTaskId);
	},

	updateTotals: function()
	{
		this.getColumns().forEach(function(column) {
			var total = 0;
			this.getNeighborGrids()
				.forEach(function(neighborGrid) {
					total += neighborGrid.getColumn(column.getId()).getTotal();
				})
			;
			column.setTotal(total);
			column.render();
		}.bind(this));
	},

	getColumnsWidth: function()
	{
		var columnsWidth = 0;

		this.getColumns().forEach(function(column) {
			columnsWidth += column.getContainer().offsetWidth;
		});

		return columnsWidth + 'px';
	}
};


})();
