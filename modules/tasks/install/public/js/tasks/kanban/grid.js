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
	BX.Kanban.Grid.apply(this, arguments);

	BX.addCustomEvent(this, "Kanban.Grid:onItemMoved", BX.delegate(this.onItemMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onItemAddedAsync", BX.delegate(this.onItemAddedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnMoved", BX.delegate(this.onColumnMoved, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnUpdated", BX.delegate(this.onColumnUpdated, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnLoadAsync", BX.delegate(this.onColumnLoadAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnRemovedAsync", BX.delegate(this.onColumnRemovedAsync, this));
	BX.addCustomEvent(this, "Kanban.Grid:onColumnAddedAsync", BX.delegate(this.onColumnAddedAsync, this));

	BX.addCustomEvent(this, "Kanban.Grid:onRender", BX.delegate(this.onGridRender, this));

	BX.addCustomEvent("BX.Main.Filter:apply", BX.delegate(this.onApplyFilter, this));
	BX.addCustomEvent("onTaskTimerChange", BX.delegate(this.onTaskTimerChange, this));
	BX.addCustomEvent("onTasksGroupSelectorChange", BX.delegate(this.onTasksGroupSelectorChange, this));
	BX.addCustomEvent("onTaskSortChanged", BX.delegate(this.onTaskSortChanged, this));
	BX.addCustomEvent("tasksTaskEvent", BX.delegate(this.tasksTaskEvent, this));
	BX.addCustomEvent("onPullEvent-im", BX.delegate(this.tasksTaskPull, this));
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
		
		data.sessid = BX.bitrix_sessid();
		data.params = this.getData().params;
		
		if (data.action !== "undefined")
		{
			url += url.indexOf("?") === -1 ? "?" : "&";
			url += "action=" + data.action;
			url += "&personal=" + data.params.PERSONAL;
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
					listUserData: [],
					notificationHandlerUrl: this.getAjaxHandlerPath() + "?action=notifyAdmin",
					popupTexts: {
						sendButton: BX.message("TASKS_KANBAN_NOTIFY_BUTTON"),
						title: BX.message("TASKS_KANBAN_NOTIFY_TITLE"),
						header: BX.message("TASKS_KANBAN_NOTIFY_HEADER"),
						description: BX.message("TASKS_KANBAN_NOTIFY_TEXT")
					}
				});
			}
			this.accessNotifyDialog.setUsersForNotify(this.getData().admins);
			this.accessNotifyDialog.show();
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

		if (!gridData.rights.canSortItem)
		{
			return;
		}

		BX.Kanban.Grid.prototype.onItemDragStart.apply(this, arguments);
	},
	
	/**
	 * Add item to the column in order.
	 * @param {Object} data
	 * @returns {void}
	 */
	addItemOrder: function(data)
	{
		var gridData = this.getData();
		var column = this.getColumn(data.columnId);
		var columnItems = column ? column.getItems() : [];

		// new task - in top
		if (gridData.newTaskOrder === "desc")
		{
			// get first item in column
			if (columnItems.length > 0)
			{
				data.targetId = columnItems[0].getId();
			}
			this.addItem(data);
		}
		// new task - in bottom (only if not exist next page)
		else if (columnItems.length >= column.total)
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
			notDestroy !== true &&
			BX.Bitrix24 &&
			BX.Bitrix24.Slider && 
			BX.Bitrix24.Slider.destroy
		)
		{
			var url = this.getItem(item).getTaskUrl(item);
			BX.Bitrix24.Slider.destroy(url);
		}
		
		BX.Kanban.Grid.prototype.updateItem.apply(this, arguments);
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
	 * Get items from one columns.
	 * @param {BX.Kanban.Column} column
	 * @returns {BX.Promise}
	 */
	getColumnItems: function(column)
	{
		var promise = new BX.Promise();

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

		this.ajax({
				action: "addTask",
				taskName: item.getData().title,
				columnId: item.getColumn().getId(),
				beforeItemId: nextItem ? nextItem.getId() : 0
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
		this.fadeOut();
		params.autoResolve = false;
		this.ajax({
				action: "applyFilter"
			},
			function(data)
			{
				this.removeItems();
				this.loadData(data);
				promise.fulfill();
				this.fadeIn();
			}.bind(this),
			function(error)
			{
				promise.reject();
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
	 * Hook on group selector.
	 * @param {Object} currentGroup
	 * @returns {void}
	 */
	onTasksGroupSelectorChange: function(currentGroup)
	{
		// replace groupId var
		var gridData = this.getData();
		gridData.params.GROUP_ID = currentGroup.id;
		this.setData(gridData);
		// remove all columns
		var columns = this.getColumns();
		for (var i = 0, c = columns.length; i < c; i++)
		{
			this.removeColumn(columns[i]);
		}
		// and make query
		this.ajax({
				action: "changeGroup"
			},
			function(data)
			{
				// refill some settings
				gridData.admins = data.admins;
				gridData.newTaskOrder = data.newTaskOrder;
				gridData.rights.canAddColumn = data.canAddColumn;
				gridData.rights.canEditColumn = data.canEditColumn;
				gridData.rights.canRemoveColumn = data.canRemoveColumn;
				gridData.rights.canAddItem = data.canAddItem;
				gridData.rights.canSortItem = data.canSortItem;
				this.setData(gridData);
				// for demo
				data.canAddColumn = true;
				data.canEditColumn = true;
				// reload
				this.removeItems();
				this.loadData(data);
				BX.onCustomEvent(this, "onKanbanChanged", [data]);
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this)
		);
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
	 * Hook on update task in slider.
	 * @param {String} type
	 * @param {Object} data
	 * @returns {undefined}
	 */
	tasksTaskEvent: function(type, data)
	{
		if (!(data && data.task && data.task.ID))
		{
			return;
		}
		
		var taskId = data.task.ID;
		
		if (type === "DELETE")
		{
			this.removeItem(taskId);
		}
		else if (
			type === "UPDATE" || type === "ADD" 
			|| type === "UPDATE_STAGE"
		)
		{
			var task = this.getItem(taskId);
			var columns = this.getColumns();
			var columnId = task ? task.getColumnId() : columns[0].getId();
			
			if (typeof(data.task.STAGE_ID) !== "undefined")
			{
				columnId = data.task.STAGE_ID;
			}
			
			if (task || type === "ADD")
			{
				this.ajax({
						action: "refreshTask",
						taskId: taskId,
						columnId: columnId
					},
					function(data)
					{
						if (data && !data.error)
						{
							// if move stage - delete first
							if (type === "UPDATE_STAGE")
							{
								this.removeItem(taskId);
							}
							if (type === "ADD" || type === "UPDATE_STAGE")
							{
								this.addItemOrder(data);
							}
							else
							{
								this.updateItem(data.id, data, true);
							}
						}
					}.bind(this),
					function(error)
					{
					}.bind(this)
				);
			}
		}
	},

	/**
	 * Hook on pull event.
	 * @param {String} command
	 * @param {Object} data
	 * @returns {void}
	 */
	tasksTaskPull: function(command, data)
	{
		if (command === "notify")
		{
			var params = data.params ? data.params : {};

			if (params.operation === "ADD" && params.taskId)
			{
				this.ajax({
						action: "newTask",
						taskId: params.taskId
					},
					function(data)
					{
						if (data && !data.error)
						{
							this.addItemOrder(data);
						}
					}.bind(this),
					function(error)
					{
					}.bind(this)
				);
			}
		}
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
	}
};


})();