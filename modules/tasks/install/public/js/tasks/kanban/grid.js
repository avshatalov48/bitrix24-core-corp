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
	this.isCollab = (options.isCollab === 'Y');
	this.networkEnabled = options.networkEnabled || false;

	this.gridHeader = Boolean(options.gridHeader);
	this.parentTaskId = parseInt(options.parentTaskId, 10);
	this.parentTaskName = options.parentTaskName;
	this.parentTaskCompleted = Boolean(options.parentTaskCompleted);

	this.neighborGrids = [];
	this.delayedQueueForChildGrid = {};
	this.isBindEvents = false;

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

	this.bindEvents();

	const isCompletedSprint = (
		this.isScrumGrid()
			? this.getData().params.IS_COMPLETED_SPRINT === 'Y'
			: false
	);
	if (!isCompletedSprint)
	{
		this.subscribeToPull();
	}
};

BX.Tasks.Kanban.Grid.prototype = {
	__proto__: BX.Kanban.Grid.prototype,
	constructor: BX.Tasks.Kanban.Grid,
	accessNotifyDialog: null,

	/**
	 * Perform ajax query.
	 * @param {String} action
	 * @param {Object} data
	 * @returns {Promise}
	 */
	ajax: function(action, data)
	{
		const gridData = this.getData();

		data.params = gridData.params;

		if (gridData.kanbanType === "TL")
		{
			data.timeline = 'Y';
		}
		else
		{
			data.personal = data.params.PERSONAL;
		}
		if (data.groupAction === "Y")
		{
			data.groupMode = 'Y';
		}

		return BX.ajax.runComponentAction('bitrix:tasks.kanban', action, {
			mode: 'class',
			data: data,
		});
	},

	/**
	 * Bind some events.
	 * @returns {void}
	 */
	bindEvents: function() {
		if (!this.isBindEvents) {
			BX.Event.EventEmitter.subscribe('tasks-kanban-settings-fields-view', function ()
			{
				this.showFieldsSelectPopup();
			}.bind(this));
			this.isBindEvents = true;
		}
	},

	subscribeToPull()
	{
		new BX.Pull.QueueManager({
			moduleId: 'tasks',
			userId: this.ownerId,
			config: {
				loadItemsDelay: 1000,
			},
			additionalData: {},
			events: {
				onBeforePull: (event) => {
					if (this.isScrumGridHeader())
					{
						event.preventDefault();
					}

					const { pullData: { command, params } } = event.data;

					const pullHandlers = {
						comment_read_all: this.onPullCommentReadAll,
						tag_changed: this.onPullTagChanged,
					};
					if (pullHandlers[command])
					{
						pullHandlers[command].apply(this, [params]);
					}
				},
				onPull: (event) => {
					const { pullData: { command, params }, promises } = event.data;

					const taskId = this.resolveIdByEventParams(command, params);
					if (taskId > 0)
					{
						promises.push(
							Promise.resolve({
								data: {
									id: taskId,
									action: command,
									actionParams: params,
								},
							}),
						);
					}
				},
			},
			callbacks: {
				onBeforeQueueExecute: (items) => {
					return Promise.resolve();
				},
				onQueueExecute: (items) => {
					return new Promise((resolve, reject) => {
						this.getPullTasksData(this.getPullTaskIds(items))
							.then((tasksData) => {
								this.executePullQueue(this.getPullItems(items, tasksData))
									.then(() => {
										resolve();
									})
									.catch((error) => {
										reject(error);
									})
								;
							})
							.catch((error) => {
								reject(error);
							})
						;
					});
				},
				onReload: () => {},
			},
		});
	},

	resolveIdByEventParams(command, params)
	{
		const actions = [
			'comment_add',
			'stage_change',
			'task_add',
			'task_update',
			'task_view',
			'task_remove',
		];

		if (actions.includes(command))
		{
			const taskId = this.recognizeTaskId(params);
			if (taskId > 0)
			{
				return taskId;
			}

			throw new Error(`taskId is not resolved for command: ${command}`);

		}

		return 0;
	},

	getPullTaskIds(items)
	{
		const taskIds = [];

		items.forEach((item) => {
			taskIds.push(item.id);
		});

		return taskIds;
	},

	getPullTasksData(taskIds)
	{
		return new Promise((resolve, reject) => {
			// eslint-disable-next-line promise/catch-or-return
			BX.ajax.runAction('tasks.task.list', {
				data: {
					filter: { ID: taskIds },
					params: {
						RETURN_ACCESS: 'Y',
						SIFT_THROUGH_FILTER: {
							sprintKanban: (this.isScrumGrid() ? 'Y' : 'N'),
							isCompletedSprint: (this.isScrumGrid()
								? requestParams.IS_COMPLETED_SPRINT
								: 'N'
							),
							userId: this.ownerId,
							groupId: this.groupId,
						},
					},
					start: -1,
				},
			})
				.then((response) => {
					const taskIdsToRequest = [];
					if (response.data.tasks.length > 0)
					{
						response.data.tasks.forEach((taskData) => {
							taskIdsToRequest.push(parseInt(taskData.id, 10));
						});
					}

					// eslint-disable-next-line promise/catch-or-return
					this.ajax('refreshListTasks', {
						taskIds: taskIdsToRequest,
						isScrum: this.isScrumGrid() ? 'Y' : 'N',
						parentId: this.getParentTaskId(),
					}).then(
						(response) => {
							const data = response.data;

							const tasksData = new Map();

							if (BX.type.isArray(data))
							{
								data.forEach((taskData) => {
									tasksData.set(parseInt(taskData.id, 10), taskData);
								});
							}

							taskIds.forEach((taskId) => {
								if (!tasksData.has(taskId))
								{
									tasksData.set(taskId, null);
								}
							});

							resolve(tasksData);
						},
						(error) => {
							reject(error);
						},
					);
				})
				.catch((error) => {
					reject(error);
				})
			;
		});
	},

	executePullQueue(pullItems)
	{
		const pullHandlers = {
			comment_add: this.onPullCommentAdd,
			stage_change: this.onPullStageChange,
			task_add: this.onPullTaskAdd,
			task_update: this.onPullTaskUpdate,
			task_view: this.onPullTaskView,
			task_remove: this.onPullTaskRemove,
		};

		return new Promise((resolve, reject) => {
			pullItems.forEach((pullItem) => {
				const { data: { action, actionParams, id }, taskData } = pullItem;

				if (pullHandlers[action])
				{
					pullHandlers[action].apply(
						this,
						[actionParams, id, taskData],
					);
				}
			});

			resolve();
		});
	},

	getPullItems(items, tasksData)
	{
		const pullItems = [];

		items.forEach((item) => {
			pullItems.push({
				...item,
				...{
					taskData: tasksData.get(item.data.id),
				},
			});
		});

		return pullItems;
	},

	/**
	 * Show popup for selecting fields which must show in view.
	 */
	showFieldsSelectPopup: function()
	{
		var gridData = this.getData();
		const checkboxListPopup = new BX.UI.CheckboxList({
			columnCount: 3,
			lang: {
				title: gridData.customSectionsFields.title,
			},
			sections: gridData.customSectionsFields.sections,
			categories: gridData.customSectionsFields.categories,
			options: gridData.customSectionsFields.options,
			events: {
				onApply: (event) => {
					this.ajax('saveUserSelectedFields', {
						fields: event.data.fields
					}).then(
						(response) => {
							this.onApplyFilter();
						}
					);
				},
			},
		});
		checkboxListPopup.show();
	},

	/**
	 * Returns true, if Kanban in realtime work mode.
	 * @return {boolean}
	 */
	isRealtimeMode: function()
	{
		return this.data.newTaskOrder === "actual";
	},

	getGroupId: function()
	{
		return this.groupId;
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
			if (this.isCollab)
			{
				const popup = new BX.Main.Popup({
					closeIcon: true,
					titleBar: BX.message("TASKS_KANBAN_COLLAB_NOTIFY_HEADER"),
					content: BX.message("TASKS_KANBAN_COLLAB_NOTIFY_TEXT"),
					cacheable: true,
					buttons: [
						new BX.UI.Button({
							text: BX.message("TASKS_KANBAN_COLLAB_NOTIFY_BUTTON"),
							color: BX.UI.Button.Color.LIGHT_BORDER,
							onclick: function(button, event) {
								popup.close();
							},
						}),
					],
				});
				popup.show();
			}
			else
			{
				if (this.accessNotifyDialog === null)
				{
					this.accessNotifyDialog = new BX.Intranet.NotifyDialog({
						listUserData: this.getData().admins,
						notificationHandlerUrl: BX.Uri.addParam(this.getAjaxHandlerPath(), {
							action: 'notifyAdmin',
						}),
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

	updateItemState(taskId, taskData)
	{
		if (taskData)
		{
			this.addItemOrder(taskData);
		}
		else
		{
			if (this.hasItem(taskId))
			{
				this.removeItem(taskId);
			}
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

			this.ajax('setClientDate', {
				clientDate: gridData["clientDate"],
				clientTime: gridData["clientTime"]
			}).then(
				(response) => {
					this.removeItems();
					this.loadData(response.data);
					promise.fulfill();
					this.fadeIn();
				}
			);
		}
	},

	/**
	 * Get items from one column.
	 * @param {BX.Kanban.Column} column
	 * @returns {BX.Promise}
	 */
	getColumnItems: function(column)
	{
		const promise = new BX.Promise();

		if (this.isChildScrumGrid())
		{
			promise.fulfill([]);

			return promise;
		}

		this.ajax('getColumnItems', {
			pageId: column.getPagination().getPage() + 1,
			columnId: column.getId()
		}).then(
			(response) => {
				const data = response.data;
				const error = response.errors.pop();

				if (data && (BX.type.isArray(data) || BX.type.isArray(data.items)) && !error)
				{
					promise.fulfill(BX.type.isArray(data) ? data : data.items);
				}
				else if (data)
				{
					BX.Kanban.Utils.showErrorDialog(error.message, true);
					promise.reject(error);
				}
			},
			(response) => {
				const error = response.errors.pop().message;
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				promise.reject("Error: " + error);
			},
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
		const promise = new BX.Promise();

		this.ajax('modifyColumn', {
			fields: {
				id: column.getId(),
				delete: 1
			}
		}).then(
			(response) => {
				const data = response.data;
				const error = response.errors.pop();
				if (!error)
				{
					this.actionPanel = null;
					promise.fulfill(data);
				}
				else
				{
					promise.reject(error);
				}
			},
			(response) => {
				const error = response.errors.pop().message;
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				promise.reject("Error: " + error);
			},
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

		this.ajax('modifyColumn', {
			fields: {
				columnName: column.getName(),
				columnColor: column.getColor(),
				afterColumnId: targetColumnId
			}
		}).then(
			(response) => {
				const data = response.data;
				const error = response.errors.pop();
				if (!error)
				{
					this.actionPanel = null;
					promise.fulfill(data);
				}
				else
				{
					BX.Kanban.Utils.showErrorDialog(error.message, true);
					promise.reject(error);
				}
			},
			(response) => {
				const error = response.errors.pop().message;
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				promise.reject("Error: " + error);
			},
		);

		return promise;
	},

	/**
	 *
	 * @param {object} options
	 * @param {string|number} options.id
	 * @param {string|number} options.columnId
	 * @param {string} [options.type]
	 * @param {string|number} [options.targetId]
	 * @returns {BX.Kanban.Item|null}
	 */
	addItem: function(options)
	{
		if (this.isScrumGrid())
		{
			var itemType = this.getItemType(options.type);
			var item = new itemType(options);
			if (!(item instanceof BX.Kanban.Item))
			{
				throw new Error("Item type must be an instance of BX.Kanban.Item");
			}

			if (this.isChildScrumGrid())
			{
				if ((item instanceof BX.Tasks.Kanban.Item) && item.isCompleted() === false)
				{
					BX.onCustomEvent(this, 'Kanban.Grid:onAddItemInProgress');
				}

				return BX.Kanban.Grid.prototype.addItem.apply(this, arguments);
			}

			options = options || {};
			var column = this.getColumn(options.columnId), existGrid;
			if (!column)
			{
				return null;
			}

			if (options.hasOwnProperty('isSubTask') && options.isSubTask)
			{
				existGrid = false;
				this.getNeighborGrids()
					.forEach(function(neighborGrid) {
						if (!neighborGrid.isScrumGridHeader())
						{
							if (neighborGrid.isChildTask(options['parentId']))
							{
								neighborGrid.addItem(options);
								delete this.delayedQueueForChildGrid[options['id']];
								existGrid = true;
							}
						}
					}.bind(this))
				;
				if (!existGrid)
				{
					this.delayedQueueForChildGrid[options['id']] = options;
				}
			}
			else if (options.hasOwnProperty('isParentTask') && options.isParentTask)
			{
				if (options.hasOwnProperty('isVisibilitySubtasks') && options.isVisibilitySubtasks === 'N')
				{
					return BX.Kanban.Grid.prototype.addItem.apply(this, arguments);
				}

				existGrid = false;
				this.getNeighborGrids()
					.forEach(function(neighborGrid) {
						if (!neighborGrid.isScrumGridHeader())
						{
							if (neighborGrid.isParentTask(options['id']))
							{
								existGrid = true;
							}
						}
					}.bind(this))
				;
				if (!existGrid)
				{
					var columns = [];
					this.getColumns()
						.forEach(function(column) {
							columns.push({
								id: column.getId(),
								name: column.getName(),
								color: column.getColor(),
								total: column.getTotal()
							});
						}.bind(this))
					;

					BX.onCustomEvent(this, 'Kanban.Grid:onAddParentTask', {
						id: options.id,
						name: options.data.name,
						completed: options.data.completed ? 'Y' : 'N',
						storyPoints: options.data.storyPoints,
						columnId: options.columnId,
						columns: columns,
						items: []
					});
				}
			}
			else
			{
				return BX.Kanban.Grid.prototype.addItem.apply(this, arguments);
			}
		}
		else
		{
			return BX.Kanban.Grid.prototype.addItem.apply(this, arguments);
		}
	},

	addItemsFromQueue: function()
	{
		Object.values(this.delayedQueueForChildGrid)
			.forEach(function (options) {
				this.addItem(options);
			}.bind(this))
		;
	},

	/**
	 * Add new task.
	 * @param {BX.Kanban.Item} item
	 * @returns {BX.Promise}
	 */
	addTask: function(item)
	{
		const promise = new BX.Promise();
		const nextItem = item.getColumn().getNextItemSibling(item);
		const gridData = this.getData();

		this.ajax('addTask', {
			taskName: item.getData().title,
			columnId: item.getColumn().getId(),
			parentTaskId: this.parentTaskId,
			beforeItemId: (gridData.newTaskOrder === "desc" && nextItem) ? nextItem.getId() : 0
		}).then(
			(response) => {
				const data = response.data;
				const error = response.errors.pop();
				if (data && !error)
				{
					this.analytics('task_create', 'quick_button', 'success');
					promise.fulfill(data);
				}
				else if (error)
				{
					BX.Kanban.Utils.showErrorDialog(error.message, true);
					this.analytics('task_create', 'quick_button', 'error');
					promise.reject(error.message);
				}
			},
			(response) => {
				const error = response.errors.pop().message;

				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				this.analytics('task_create', 'quick_button', 'error');
				promise.reject("Error: " + error);
			},
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
		const itemId = item.getId();
		let afterItemId = 0;
		const beforeItemId = beforeItem ? beforeItem.getId() : 0;
		const targetColumnId = targetColumn ? targetColumn.getId() : 0;
		const gridData = this.getData();

		if (beforeItemId === 0)
		{
			afterItemId = targetColumn.getPreviousItemSibling(item);
			if (afterItemId)
			{
				afterItemId = afterItemId.getId();
			}
		}

		this.ajax('moveTask',{
			itemId: itemId,
			columnId: targetColumnId,
			beforeItemId: beforeItemId,
			afterItemId: afterItemId
		}).then(
			(response) => {
				const data = response.data;
				const error = response.errors.pop();
				if (data && !error)
				{
					this.updateItem(itemId, data);
					if (gridData.kanbanType === "TL")
					{
						const deadlineText = data.data.deadline.rawValue ?? item.getDeadline();
						BX.UI.Notification.Center.notify({
							content: deadlineText
								? BX.message("MAIN_KANBAN_NOTIFY_CHANGE_DEADLINE").replace("#date#", deadlineText)
								: BX.message("MAIN_KANBAN_NOTIFY_REMOVE_DEADLINE")
						});
					}
					if (
						typeof data.data !== "undefined"
						&& data.data.hiddenByFilter === true
					)
					{
						this.removeItem(item);
					}

					if (this.isChildScrumGrid())
					{
						top.BX.loadExt('tasks.scrum.task-status').then(() => {
							if (
								!BX.type.isUndefined(top.BX.Tasks.Scrum)
								&& !BX.type.isUndefined(top.BX.Tasks.Scrum.TaskStatus)
							)
							{
								this.taskStatus = new top.BX.Tasks.Scrum.TaskStatus({
									groupId: this.groupId,
									parentTaskId: this.parentTaskId,
									taskId: itemId,
									action: (
										this.getFinishColumn().getId() === targetColumnId
											? 'complete'
											: 'renew'
									)
								});
								this.taskStatus.update()
									.then((action) => {
										const actions = top.BX.Tasks.Scrum.TaskStatus.actions;
										switch (action)
										{
											case actions.complete:
											case actions.renew:
											case actions.proceed:
												this.updateParentTaskStatus(action);
												break;
											case actions.skip:
												break;
										}
									})
								;
							}
						});
					}
				}
				else if (error)
				{
					BX.Kanban.Utils.showErrorDialog(error.message, true);
				}
			},
			(response) => {
				BX.Kanban.Utils.showErrorDialog("Error: " + response.errors[0].message, true);
			},
		);
	},

	updateParentTaskStatus: function(inputAction)
	{
		if (inputAction === 'proceed')
		{
			this.ajax('proceedParentTask',{
				taskId: this.parentTaskId,
			}).then(
				(response) => {
					const data = response.data;
					this.addItemToParentGrid(data);
					const parentItem = this.getItemFromParentGrid(this.parentTaskId);
					if (parentItem)
					{
						this.scrollToItem(parentItem);
					}
					setTimeout(() => {
						BX.onCustomEvent(this, 'Kanban.Grid:onProceedParentTask', [this]);
					}, 300);
				},
				(response) => {
					BX.Kanban.Utils.showErrorDialog("Error: " + response.errors[0].message, true);
				},
			);
		}
		else
		{
			const isCompleteAction = inputAction === 'complete';
			const action = isCompleteAction ? 'completeParentTask' : 'renewParentTask';
			this.ajax(action, {
				taskId: this.parentTaskId,
				finishColumnId: this.getFinishColumn().getId(),
				newColumnId: this.getNewColumn().getId()
			}).then(
				(response) => {
					const error = response.errors.pop();
					this.actionPanel = null;
					if (!error)
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
						}					}
					else
					{
						BX.Kanban.Utils.showErrorDialog(error.message, true);
					}
				},
				(response) => {
					BX.Kanban.Utils.showErrorDialog("Error: " + response.errors[0].message, true);
				},
			);
		}
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

	getColumnCount: function()
	{
		return Object.keys(this.getColumns()).length;
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

		this.ajax('moveColumn', {
			columnId: columnId,
			afterColumnId: afterColumnId
		}).then(
			(response) => {
				const error = response.errors.pop();
				if (error)
				{
					BX.Kanban.Utils.showErrorDialog(error.message, true);
				}
			},
			(response) => {
				BX.Kanban.Utils.showErrorDialog("Error: " + response.errors[0].message, true);
			},
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

		this.ajax('modifyColumn', {
			fields: {
				id: columnId,
				columnName: title,
				columnColor: color
			}
		}).then(
			(response) => {
				const error = response.errors.pop();
				this.actionPanel = null;
				if (error)
				{
					BX.Kanban.Utils.showErrorDialog(error.message, true);
				}
			},
			(response) => {
				BX.Kanban.Utils.showErrorDialog("Error: " + response.errors[0].message, true);
			},
		);
	},

	/**
	 * Get path for ajax query.
	 * @returns {string}
	 */
	getAjaxHandlerPath: function()
	{
		return `/bitrix/services/main/ajax.php?mode=class&c=${encodeURIComponent('bitrix:tasks.kanban')}`;
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

		const data = {};
		data.params = this.getData().params;
		if (this.getData().kanbanType === "TL")
		{
			data.timeline = 'Y';
		}

		this.ajax('applyFilter', {}).then(
			(response) => {
				// re-set some data
				const data = response.data;
				const gridData = this.getData();
				if (typeof data.customFields !== "undefined")
				{
					gridData.customFields = data.customFields;
				}
				if (this.differentColumnCount(data))
				{
					this.getColumns().forEach((column) => this.removeColumn(column));
				}
				this.removeItems();
				this.loadData(data);
				if (promise)
				{
					promise.fulfill();
				}
				this.fadeIn();
			},
			(response) => {
				if (promise)
				{
					promise.reject();
				}
				this.fadeIn();
			}
		);
	},

	differentColumnCount: function(data)
	{
		if (BX.Type.isArray(data.columns))
		{
			const columnCount = data.columns.length;
			const kanbanColumnCount = this.getColumnCount();

			return columnCount !== kanbanColumnCount;
		}

		return false;
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

	onPullCommentReadAll(eventParams)
	{
		Object.values(this.getItems()).forEach((item) => {
			const data = item.data;
			const isExpiredCounts = (
				data.is_expired
				&& !data.completed
				&& !data.completed_supposedly
			);
			const counter = item.task_counter;
			const counterValue = counter.getValue();
			if (
				counterValue > 0
				&& (!isExpiredCounts || counterValue > 1)
			)
			{
				data.counter.value = (isExpiredCounts ? 1 : 0);
				counter.update(data.counter.value);
				item.render();
			}
		});
	},

	onPullTagChanged(eventParams)
	{
		if (this.isScrumGrid())
		{
			BX.Tasks.Scrum.Kanban.onApplyFilter();
		}
		else
		{
			this.onApplyFilter();
		}
	},

	onPullCommentAdd(eventParams, taskId, taskData)
	{
		this.updateItemState(taskId, taskData);
	},

	onPullStageChange(eventParams, taskId, taskData)
	{
		this.updateItemState(taskId, taskData);
	},

	onPullTaskAdd(eventParams, taskId, taskData)
	{
		this.updateItemState(taskId, taskData);
	},

	onPullTaskUpdate(eventParams, taskId, taskData)
	{
		this.updateItemState(taskId, taskData);
	},

	onPullTaskView(eventParams, taskId, taskData)
	{
		this.updateItemState(taskId, taskData);
	},

	onPullTaskRemove(eventParams, taskId)
	{
		this.removeItem(taskId);
	},

	refreshTask: function(taskId)
	{
		this.ajax('refreshTask',{
			taskId: taskId,
			isScrum: this.isScrumGrid() ? 'Y' : 'N',
			parentId: this.getParentTaskId()
		}).then(
			(response) => {
				const data = response.data;

				if (data && !BX.type.isArray(data) && response.errors.length === 0)
				{
					this.addItemOrder(data);
				}
			},
			(response) => {

			},
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
		this.ajax('changeDemoView',{
			viewId: viewId
		}).then(
			(response) => {
				const data = response.data;
				this.loadData(data);
			},
			(response) => {
				const error = response.errors[0].message;
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}
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
	addNeighborGrid: function(inputNeighborGrid)
	{
		if (this !== inputNeighborGrid)
		{
			var existGrid = false;
			this.neighborGrids.forEach(
				function(neighborGrid) {
					if (neighborGrid === inputNeighborGrid)
					{
						existGrid = true;
					}
				})
			;
			if (!existGrid)
			{
				this.neighborGrids.push(inputNeighborGrid);
			}
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

	addItemToParentGrid: function(data)
	{
		this.neighborGrids.forEach(
			function(neighborGrid) {
				if (!neighborGrid.isScrumGridHeader() && !neighborGrid.isChildScrumGrid())
				{
					neighborGrid.addItemOrder(data);
				}
			})
		;
	},

	getItemFromParentGrid: function(itemId)
	{
		var item = null;

		this.neighborGrids.forEach(
			function(neighborGrid) {
				if (!neighborGrid.isScrumGridHeader() && !neighborGrid.isChildScrumGrid())
				{
					item = neighborGrid.getItem(itemId);
				}
			})
		;

		return item;
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

	isCollabMode: function()
	{
		return this.isCollab;
	},

	isGroupingMode: function()
	{
		return this.groupingMode;
	},

	isChildScrumGrid: function()
	{
		return (this.parentTaskId > 0);
	},

	isParentTask: function(id)
	{
		return (parseInt(id, 10) === this.parentTaskId);
	},

	getParentTaskId: function()
	{
		return this.parentTaskId;
	},

	resetPaginationPage: function()
	{
		this.getColumns()
			.forEach(function(column) {
				column.getPagination().loadingInProgress = false;
				column.getPagination().page = 1;
			})
		;
	},

	isChildTask: function(parentId)
	{
		return (parseInt(parentId, 10) === this.parentTaskId);
	},

	updateTotals: function()
	{
		this.getColumns().forEach(function(column) {
			let total = 0;
			this.getNeighborGrids()
				.forEach(function(neighborGrid) {
					const currentColumn = neighborGrid.getColumn(column.getId());
					if (currentColumn)
					{
						total += currentColumn.getItemsCount();
					}
				})
			;
			column.setTotal(total);
			column.render();
		}.bind(this));
	},

	getColumnsWidth: function()
	{
		let columnsWidth = 0;

		this.getColumns().forEach(function(column) {
			columnsWidth += column.getContainer().offsetWidth;
		});

		return columnsWidth + 'px';
	},

	hasItem: function(itemId)
	{
		itemId = parseInt(itemId, 10);

		var hasItem = false;
		Object.values(this.getItems())
			.forEach(function(item) {
				if (parseInt(item.getId(), 10) === itemId)
				{
					hasItem = true;
				}
			})
		;

		return hasItem;
	},

	hasItemInProgress: function()
	{
		var hasItemInProgress = false;

		Object.values(this.getItems())
			.forEach(function(item) {
				if (!item.isCompleted())
				{
					hasItemInProgress = true;
				}
			})
		;

		return hasItemInProgress;
	},

	scrollToItem: function(item)
	{
		var scrollTimeout;
		var scrollHandler = function () {
			clearTimeout(scrollTimeout);
			scrollTimeout = setTimeout(function() {
				item.animate({
					duration: 1000,
					draw: function(progress) {
						item.layout.container.style.opacity = progress * 100 + '%';
					},
					useAnimation: true
				}).then(function(){
					item.layout.container.style.opacity = '100%';
				}.bind(this));
			}, 100);
			BX.unbind(window, 'scroll', scrollHandler);
		};
		BX.bind(window, 'scroll', scrollHandler);

		item.getContainer().scrollIntoView({
			behavior: 'smooth',
			block: 'center',
			inline: 'nearest'
		});
	},

	analytics: function (event, element, status = null)
	{
		const analyticsData = {
			tool: 'tasks',
			category: 'task_operations',
			event: event,
			type: 'task',
			c_section: this.getSectionTypeForAnalytics(),
			c_element: element,
			c_sub_section: this.getKanbanTypeForAnalytics(),
		};
		if (status)
		{
			analyticsData.status = status;
		}

		if (BX.UI.Analytics)
		{
			BX.UI.Analytics.sendData(analyticsData);
		}
		else
		{
			BX.Runtime.loadExtension('ui.analytics').then(() => {
				BX.UI.Analytics.sendData(analyticsData);
			});
		}
	},

	getSectionTypeForAnalytics: function ()
	{
		if (this.isScrumGrid())
		{
			return 'scrum';
		}

		if (this.getGroupId())
		{
			if (this.isCollabMode())
			{
				return 'collab';
			}

			return 'project';
		}

		return 'tasks';
	},

	getKanbanTypeForAnalytics: function ()
	{
		const type = this.getData().kanbanType;

		switch (type)
		{
			case 'TL':
				return 'deadline';
			case 'P':
				return 'planner';
			default:
				return 'kanban';
		}
	},
};


})();
