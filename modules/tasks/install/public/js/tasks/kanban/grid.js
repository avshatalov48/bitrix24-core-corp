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
	BX.addCustomEvent("onTasksGroupSelectorChange", BX.delegate(this.onTasksGroupSelectorChange, this));
	BX.addCustomEvent("onTaskSortChanged", BX.delegate(this.onTaskSortChanged, this));
	BX.addCustomEvent("onPullEvent-im", BX.delegate(this.tasksTaskPull, this));
	BX.addCustomEvent("onPullEvent-tasks", BX.delegate(this.tasksTaskPull, this));

	BX.addCustomEvent("Kanban.Grid:multiSelectModeOn", BX.delegate(this.startActionPanel, this));
	BX.addCustomEvent("Kanban.Grid:multiSelectModeOff", BX.delegate(this.stopActionPanel, this));
	BX.addCustomEvent("Kanban.Grid:selectItem", BX.delegate(this.setTotalSelectedItems, this));
	BX.addCustomEvent("Kanban.Grid:unSelectItem", BX.delegate(this.setTotalSelectedItems, this));
	BX.addCustomEvent("Kanban.Grid:onItemDragStart", BX.delegate(this.setKanbanDragMode, this));
	BX.addCustomEvent("Kanban.Grid:onItemDragStop", BX.delegate(this.unSetKanbanDragMode, this));
	BX.addCustomEvent("Kanban.Grid:onItemDragStart", BX.delegate(this.setKanbanRealtimeMode, this));
	BX.addCustomEvent("Kanban.Grid:onItemDragStop", BX.delegate(this.unSetKanbanRealtimeMode, this));

	if(this.isMultiSelect())
	{
		BX.addCustomEvent("Kanban.Grid:onItemDragStartMultiple", BX.delegate(this.setKanbanDragMode, this));
		BX.addCustomEvent("Kanban.Grid:onItemDragStopMultiple", BX.delegate(this.unSetKanbanDragMode, this));
	}

	this.ownerId = Number(options.ownerId);
	this.groupId = Number(options.groupId);
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
	 * Hook on group selector.
	 * @param {Object} currentGroup
	 * @returns {void}
	 */
	onTasksGroupSelectorChange: function(currentGroup)
	{
		// replace groupId var
		var gridData = this.getData();
		gridData.params.GROUP_ID = currentGroup.id;
		if (currentGroup.sprintId)
		{
			gridData.params.SPRINT_ID = currentGroup.sprintId;
		}
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
		var taskId = this.recognizeTaskId(data);

		switch (command)
		{
			case "comment_add":
			case "stage_change":
			case "task_view":
				if (taskId)
				{
					BX.ajax.runAction('tasks.task.list', {data: {
						filter: {ID: taskId},
						params: {
							RETURN_ACCESS: 'Y',
							SIFT_THROUGH_FILTER: {
								userId: this.ownerId,
								groupId: this.groupId
							}
						}
					}}).then(function(response) {
						if (response.data.tasks.length > 0)
						{
							this.ajax({
									action: "refreshTask",
									taskId: taskId
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
					var counter = item.task_counter;
					var counterValue = counter.getValue();
					if (counterValue > 0 && (!data.is_expired || counterValue > 1))
					{
						data.counter.value = (data.is_expired ? 1 : 0);
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
	}
};


})();
