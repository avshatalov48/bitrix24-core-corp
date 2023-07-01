/*eslint-disable*/

(function() {

"use strict";

BX.namespace("BX.Tasks.Kanban");

/**
 *
 * @param options
 * @extends {BX.Kanban.Item}
 * @constructor
 */
BX.Tasks.Kanban.Item = function(options)
{
	BX.Kanban.Item.apply(this, arguments);

	/** @var {Element} **/
	this.container = null;
	this.timer = null;

	this.isSprintView = (options.isSprintView === 'Y');
	this.networkEnabled = options.networkEnabled || false;
	this.storyPoints = (this.data.storyPoints ? this.data.storyPoints : '');
	this.epic = (BX.type.isPlainObject(this.data.epic) ? this.data.epic : null);
	this.calendarSettings = (options.calendarSettings ? options.calendarSettings : {});
};

BX.Tasks.Kanban.Item.prototype = {
	__proto__: BX.Kanban.Item.prototype,
	constructor: BX.Tasks.Kanban.Item,
	dateFormats: {
		short: {
			en: "F j",
			de: "j. F",
			ru: "j F"
		},
		full: {
			en: "F j, Y",
			de: "j. F Y",
			ru: "j F Y"
		}
	},

	setOptions: function(options)
	{
		if (!BX.type.isPlainObject(options))
		{
			return;
		}

		BX.Kanban.Item.prototype.setOptions.apply(this, arguments);

		this.storyPoints = (options.data.storyPoints ? options.data.storyPoints : '');
		this.epic = (BX.type.isPlainObject(options.data.epic) ? options.data.epic : null);
	},

	/**
	 * Return formatted time.
	 * @param {String} string
	 * @param {Boolean} showSeconds
	 * @returns {String}
	 */
	renderTime: function (string, showSeconds)
	{
		var sec_num = parseInt(string, 10);
		var hours   = Math.floor(sec_num / 3600);
		var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
		var seconds = sec_num - (hours * 3600) - (minutes * 60);
		showSeconds = typeof(showSeconds) === "undefined" ? true : showSeconds;

		if (hours   < 10)
		{
			hours   = "0" + hours;
		}
		if (minutes < 10)
		{
			minutes = "0" + minutes;
		}
		if (seconds < 10)
		{
			seconds = "0" + seconds;
		}

		return hours + ":" + minutes + (showSeconds ? (":" + seconds) : "");
	},

	/**
	 * Add <span> for last word in title.
	 * @param {String} fullTitle
	 * @returns {String}
	 */
	clipTitle: function (fullTitle)
	{
		var title = fullTitle;
		var arrTitle = title.split(" ");
		var lastWord = "<span>" + arrTitle[arrTitle.length - 1] + "</span>";

		arrTitle.splice(arrTitle.length - 1);

		title = arrTitle.join(" ") + " " + lastWord;

		return title;
	},

	/**
	 * Store key in current data.
	 * @param {String} key
	 * @param {String} val
	 * @returns {void}
	 */
	setDataKey: function(key, val)
	{
		var data = this.getData();
		data[key] = val;
	},

	/**
	 * Get key value from current data.
	 * @param {String} key
	 * @returns {String}
	 */
	getDataKey: function(key)
	{
		var data = this.getData();
		return data[key];
	},

	/**
	 * Gets deadline of item.
	 * @return {String}
	 */
	getDeadline: function()
	{
		return this.deadlineNotificationDate;
	},

	/**
	 * Set status for current item.
	 * @param {String} code
	 * @returns {undefined}
	 */
	setStatus: function(code)
	{
		var data = this.getData();

		if (this.task_complete)
		{
			BX.show(this.task_complete);
		}
		BX.show(this.task_status_title);
		BX.style(this.task_status_title, "display", "inline-block");

		BX.removeClass(this.task_status_title, "tasks-kanban-item-blue");
		BX.removeClass(this.task_status_title, "tasks-kanban-item-gray");
		BX.removeClass(this.task_status_title, "tasks-kanban-item-red");
		BX.removeClass(this.task_status_title, "tasks-kanban-item-white-blue");

		this.setDataKey("status", code);

		// if (code === "deferred")
		// {
		// 	// BX.addClass(this.task_status_title, "tasks-kanban-item-blue");
		// 	// this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_DEFERRED");
		// }
		// else if (code === "completed" || code === "completed_supposedly")
		// {
		// 	// BX.hide(this.task_complete);
		// 	// BX.addClass(this.task_status_title, "tasks-kanban-item-gray");
		// 	// if (code === "completed_supposedly")
		// 	// {
		// 	// 	this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_COMPLETED_SUPPOSEDLY");
		// 	// }
		// 	// else
		// 	// {
		// 	// 	this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_COMPLETED");
		// 	// }
		// }
		// else if (code === "overdue")
		// {
		// 	// this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_OVERDUE");
		// }
		// else if (code === "in_progress")
		// {
		// 	// this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_PROGRESS");
		// }
		// else if (code === "pause")
		// {
		// 	// this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_PAUSE");
		// }
		// else if (code === "new")
		// {
		// 	// BX.addClass(this.task_status_title, "tasks-kanban-item-white-blue");
		// 	// this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_NEW");
		// }
		// else
		// {
		// 	BX.hide(this.task_status_title);
		// }
		if (code === "completed")
		{
			if (this.task_complete)
			{
				BX.hide(this.task_complete);
			}
			BX.addClass(this.task_status_title, "tasks-kanban-item-gray");
			this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_COMPLETED");
		}
		else
		{
			BX.hide(this.task_status_title);
		}

		if (this.task_start && data.in_progress && !BX.hasClass(this.task_start, "tasks-kanban-task-pause"))
		{
			BX.addClass(this.task_start, "tasks-kanban-task-pause");
			this.task_start.setAttribute("title", BX.message("TASKS_KANBAN_TITLE_PAUSE"));
		}
		else if (this.task_start && !data.in_progress)
		{
			BX.removeClass(this.task_start, "tasks-kanban-task-pause");
			this.task_start.setAttribute("title", BX.message("TASKS_KANBAN_TITLE_START"));
		}

		if (this.task_mute && data.muted && !BX.hasClass(this.task_mute, "tasks-kanban-task-muted"))
		{
			BX.addClass(this.container, "tasks-kanban-task-muted");
			this.task_mute.setAttribute("title", BX.message("TASKS_KANBAN_TITLE_UNMUTE"));
			this.task_counter.setColor(BX.UI.Counter.Color.GRAY);
		}
		else if (this.task_mute && !data.muted)
		{
			var isExpiredCounts = data.is_expired && !data.completed && !data.completed_supposedly;
			BX.removeClass(this.container, "tasks-kanban-task-muted");
			this.task_mute.setAttribute("title", BX.message("TASKS_KANBAN_TITLE_MUTE"));
			this.task_counter.setColor(isExpiredCounts ? BX.UI.Counter.Color.DANGER : BX.UI.Counter.Color.SUCCESS);
		}
	},

	/**
	 * Action for mute/unmute the task.
	 * @returns {void}
	 */
	muteTask: function()
	{
		var taskId = this.getId();
		var data = this.getData();
		var action = data.muted ? "unmuteTask" : "muteTask";

		this.getGrid().ajax({
				action: action,
				taskId: taskId
			},
			function(data)
			{
				if (data && !data.error)
				{
					this.getGrid().updateItem(this.getId(), data);
				}
				else if (data)
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
	 * Action for start the task.
	 * @returns {void}
	 */
	startTask: function()
	{
		var taskId = this.getId();
		var data = this.getData();
		var action = data.in_progress ? "pauseTask" : "startTask";

		if (data.allow_time_tracking && data.time_tracking)
		{
			if (action === "startTask")
			{
				BX.TasksTimerManager.start(taskId);
			}
			else
			{
				BX.TasksTimerManager.stop(taskId);
			}
		}

		if (
			!data.allow_time_tracking ||
			!data.time_tracking ||
			action === "pauseTask"
		)
		{
			this.getGrid().ajax({
					action: action,
					taskId: taskId
				},
				function(data)
				{
					if (data && !data.error)
					{
						this.getGrid().updateItem(this.getId(), data);
					}
					else if (data)
					{
						BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
					}
				}.bind(this),
				function(error)
				{
					BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				}.bind(this)
			);
		}
	},

	/**
	 * Action for complete the task.
	 * @returns {void}
	 */
	completeTask: function()
	{
		var currentStatus = this.getDataKey("status");

		this.setStatus("completed");

		this.getGrid().ajax({
				action: "completeTask",
				taskId: this.getId(),
				columnId: this.getColumnId()
			},
			function(data)
			{
				if (data && !data.error)
				{
					this.getGrid().updateItem(this.getId(), data);
				}
				else if (data)
				{
					this.setStatus(currentStatus);
					BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
				}
			}.bind(this),
			function(error)
			{
				this.setStatus(currentStatus);
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this)
		);
	},

	/**
	 * Action for set deadline for the task.
	 * @returns {void}
	 */
	deadlineTask: function()
	{
		var data = this.getData();
		var format = BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME"));
		var value = BX.date.format(format, data.date_deadline || data.date_day_end);

		var calendar = BX.calendar({
			node: BX.proxy_context,
			value: value,
			currentTime: value,
			bTime: true,
			bCompatibility: true,
			bCategoryTimeVisibilityOption: 'tasks.bx.calendar.deadline',
			bTimeVisibility: (
				this.calendarSettings ? (this.calendarSettings.deadlineTimeVisibility === 'Y') : false
			),
			callback: function(data)
			{
				this.getGrid().ajax({
						action: "deadlineTask",
						taskId: this.getId(),
						deadline: BX.date.format(format, data),
						columnId: this.getColumnId()
					},
					function(data)
					{
						if (data && !data.error)
						{
							this.getGrid().updateItem(data.id, data);
						}
						else if (data)
						{
							BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
						}
					}.bind(this),
					function(error)
					{
						BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
					}.bind(this)
				);
			}.bind(this),
			callback_after: function(value) {
				BX.onCustomEvent(this.getGrid(), 'Tasks.Kanban.Item:deadlineChanged', {value: value});
			}.bind(this)
		});

		BX.onCustomEvent(this.getGrid(), 'Tasks.Kanban.Item:deadlineChangeClick', {
			calendar: calendar,
			itemId: data.id
		});
	},

	/**
	 * Action for change author for the task.
	 * @returns {void}
	 */
	changeAuthorTask: function()
	{
		this.changeMemberTask("changeAuthorTask");
	},

	/**
	 * Action for delegate the task.
	 * @returns {void}
	 */
	delegateTask: function()
	{
		this.changeMemberTask("delegateTask");
	},

	/**
	 * Summary action for change member in the task.
	 * @param {String} action
	 * @returns {void}
	 */
	changeMemberTask: function(action)
	{
		var data = this.getData();

		var targetNode = this.responsible;
		var userId = data.responsible.id;
		var userRole = 'R';

		if (action === 'changeAuthorTask')
		{
			targetNode = this.author;
			userId = data.author.id;
			userRole = 'O';
		}

		var entities;
		if (this.isSprintView)
		{
			entities = [
				{
					id: 'scrum-user',
					options: {
						groupId: this.getGrid().getGroupId()
					},
					dynamicLoad: true
				},
				{
					id: 'department'
				}
			];
		}
		else
		{
			entities = [
				{
					id: 'user',
					options: {
						emailUsers: true,
						networkUsers: this.networkEnabled,
						extranetUsers: true,
						inviteGuestLink: true,
						myEmailUsers: true
					}
				},
				{
					id: 'department',
				}
			];
		}

		BX.loadExt('ui.entity-selector').then(function() {
			var userDialog = new BX.UI.EntitySelector.Dialog({
				targetNode: targetNode,
				enableSearch: true,
				multiple: false,
				dropdownMode: this.isSprintView,
				context: 'KANBAN_RESPONSIBLE_SELECTOR_' + action + data.id,
				preselectedItems: [
					['user', userId]
				],
				undeselectedItems: [
					['user', userId]
				],
				entities: entities,
				events: {
					'Item:onSelect': function(event) {
						var item = event.getData().item;
						var data = this.prepareUserData(item, userRole);

						this.getGrid().ajax({
								action: action,
								taskId: this.getId(),
								columnId: this.getColumnId(),
								userId: data.id
							},
							function(data)
							{
								if (data && !data.error)
								{
									this.getGrid().updateItem(data.id, data);
								}
								else if (data)
								{
									BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
								}
							}.bind(this),
							function(error)
							{
								BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
							}.bind(this)
						);
					}.bind(this)
				}
			});

			userDialog.show();
		}.bind(this));
	},

	prepareUserData: function(user, userRole)
	{
		var customData = user.getCustomData();
		var entityType = user.getEntityType();

		return {
			avatar: user.avatar,
			description: '',
			entityType: userRole,
			id: user.getId(),
			name: customData.get('name'),
			lastName: customData.get('lastName'),
			email: customData.get('email'),
			nameFormatted: BX.Text.encode(user.getTitle()),
			networkId: '',
			type: {
				crmemail: false,
				extranet: (entityType === 'extranet'),
				email: (entityType === 'email'),
				network: (entityType === 'network')
			}
		};
	},

	/**
	 * Get URL to task.
	 * @param {Integer} id Task id.
	 * @returns {String}
	 */
	getTaskUrl: function(id)
	{
		if (parseInt(this.getGridData().groupId, 10) > 0)
		{
			return this.getGridData().pathToGroupTask.replace("#task_id#", id);
		}
		return this.getGridData().pathToTask.replace("#task_id#", id);
	},

	isCompleted: function()
	{
		var data = this.getData();

		return data.completed;
	},

	/**
	 * Get scrum item storyPoints.
	 * @return {String}
	 */
	getStoryPoints: function()
	{
		return this.storyPoints;
	},

	/**
	 * Add or remove class for element.
	 * @param {DOMNode} el
	 * @param {String} className
	 * @param {Boolean} mode
	 * @returns {void}
	 */
	switchClass: function(el, className, mode)
	{
		if (mode)
		{
			BX.addClass(el, className);
		}
		else
		{
			BX.removeClass(el, className);
		}
	},

	/**
	 * Show or hide element.
	 * @param {DOMNode} el
	 * @param {Boolean} mode
	 * @returns {void}
	 */
	switchVisible: function(el, mode)
	{
		if (mode)
		{
			el.style.display = "";
		}
		else
		{
			BX.hide(el);
		}
	},

	/**
	 *
	 * @param {Element} itemNode
	 * @param {number} x
	 * @param {number} y
	 */
	onDragDrop: function(itemNode, x, y)
	{
		if(this.selectable && this.getGrid().getSelectedItems().size > 1)
		{
			return this.onDragDropMultiple();
		}

		this.hideDragTarget();
		var draggableItem = this.getGrid().getItemByElement(itemNode);

		var event = new BX.Kanban.DragEvent();
		event.setItem(draggableItem);
		event.setTargetColumn(this.getColumn());
		event.setTargetItem(this);

		BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onBeforeItemMoved", [event]);
		if (!event.isActionAllowed())
		{
			return;
		}

		var taskCompletePromise = new BX.Promise();

		if (
			this.isSprintView
			&& (this.getColumn().isFinishType())
			&& (!draggableItem.getColumn().isFinishType())
		)
		{
			top.BX.loadExt('tasks.scrum.dod').then(function() {
				if (typeof top.BX.Tasks.Scrum === 'undefined' || typeof top.BX.Tasks.Scrum.Dod === 'undefined')
				{
					taskCompletePromise.fulfill();
				}
				this.scrumDod = new top.BX.Tasks.Scrum.Dod({
					groupId: this.getData()['groupId'],
					taskId: draggableItem.getId()
				});
				this.scrumDod.subscribe('resolve', function() { taskCompletePromise.fulfill() });
				this.scrumDod.subscribe('reject', function() { taskCompletePromise.reject() });
				this.scrumDod.isNecessary()
					.then(function(isNecessary) {
						if (isNecessary)
						{
							this.scrumDod.showList();
						}
						else
						{
							taskCompletePromise.fulfill();
						}
					}.bind(this))
				;
			}.bind(this));
		}
		else
		{
			taskCompletePromise.fulfill();
		}

		taskCompletePromise.then(function() {
			var success = this.getGrid().moveItem(draggableItem, this.getColumn(), this);
			if (success)
			{
				BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onItemMoved", [draggableItem, this.getColumn(), this]);
			}
		}.bind(this));
	},

	/**
	 * Set tag to the filter.
	 * @returns {void}
	 */
	setFilterTag: function()
	{
		var tagName = BX.proxy_context.textContent.substring(1);
		var gridData = this.getGridData();
		var filterManager = BX.Main.filterManager.getById(gridData.gridId);

		filterManager.getApi().extendFilter({
			TAG: tagName,
			TAG_label: tagName
		});
	},

	/**
	 * Set epic to the filter.
	 * @returns {void}
	 */
	setFilterEpic: function(epicId)
	{
		var gridData = this.getGridData();
		var filterManager = BX.Main.filterManager.getById(gridData.gridId);

		filterManager.getApi().extendFilter({ EPIC: String(epicId) });
	},

	/**
	 * Return full node for item.
	 * @returns {DOMNode}
	 */
	render: function()
	{
		if (!this.container)
		{
			this.createLayout();
		}

		var data = this.getData();
		var color = this.getColumn().getColor();
		var rgb = BX.util.hex2rgb(color);
		var rgba = "rgba(" + rgb.r + "," + rgb.g + "," + rgb.b + "," + ".7)";
		var formatLang = BX.message("LANGUAGE_ID");

		if (formatLang !== "en" && formatLang !== "de")
		{
			formatLang = "ru";
		}

		// border color
		//BX.style(this.container, "border-left", "3px solid " + rgba);
		this.container.style.setProperty("--tasks-kanban-item-color", rgba);
		// background
		if (data.background)
		{
			BX.style(this.containerImg, "background-image", "url(\'" + data.background + "\')");
			this.containerImg.setAttribute("href", this.getTaskUrl(this.getId()));
		}
		this.switchVisible(this.containerImg, data.background);
		// border color / item link / hot
		this.link.innerHTML = this.clipTitle(data.name);
		this.link.setAttribute(
			"href",
			this.getTaskUrl(this.getId())
		);
		this.switchClass(
			this.link,
			"tasks-kanban-item-title-hot",
			data.high
		);
		this.switchVisible(this.link, data.name !== '');

		if (this.getGrid().isScrumGrid())
		{
			BX.cleanNode(this.epicLayout);
			if (this.epic)
			{
				this.epicLayout.appendChild(this.createEpicLayout());
			}
		}
		// tags
		if (data.tags && data.tags.length > 0)
		{
			BX.cleanNode(this.tags);
			for (var i = 0, c = data.tags.length; i < c; i++)
			{
				this.tag = BX.create("span", {
					props: {
						className: "ui-label ui-label-tag-light ui-label-fill ui-label-md ui-label-link"
					},
					children: [
						BX.create("span", {
							props: {
								className: "ui-label-inner"
							},
							text: "#" + data.tags[i],
							events: {
								click: BX.delegate(function(e) {
									this.setFilterTag();
									e.stopPropagation();
								}, this)
							}
						})
					]
				});
				this.tags.appendChild(this.tag);
			}
		}

		// deadline
		this.switchClass(
			this.date_deadline_container,
			"tasks-kanban-item-pointer",
			data.allow_change_deadline
		);
		if (data.allow_change_deadline)
		{
			BX.bind(this.date_deadline_container, "click", BX.delegate(function(e) {
				this.deadlineTask();
				e.stopPropagation();
			}, this));
		}
		else
		{
			BX.unbind(this.date_deadline_container, "click", BX.delegate(function(e) {
				this.deadlineTask();
				e.stopPropagation();
			}, this));
		}

		if (!this.isSprintView && data?.deadline_visibility !== 'hidden')
		{
			if (data.date_deadline || data.deferred || data.completed_supposedly)
			{
				this.deadlineNotificationDate = data.deadline.value.replace('&minus;', '-');
				this.date_deadline.setText(this.deadlineNotificationDate);
				this.date_deadline.setFill(data.deadline.fill);
				this.date_deadline.setColor(data.deadline.color);
			}
			else
			{
				this.deadlineNotificationDate = '';
				this.date_deadline.setText(BX.message("TASKS_KANBAN_NO_DATE"));
				this.date_deadline.setFill(false);
				this.date_deadline.setColor(BX.UI.Label.LIGHT);
			}
			this.date_deadline.setCustomClass("tasks-kanban-item-deadline");
		}

		// set status
		if (data.deferred)
		{
			this.setStatus("deferred");
		}
		else if (data.completed)
		{
			this.setStatus("completed");
		}
		else if (data.completed_supposedly)
		{
			this.setStatus("completed_supposedly");
		}
		else if (data.overdue)
		{
			this.setStatus("overdue");
		}
		else if (data.in_progress)
		{
			this.setStatus("in_progress");
		}
		else if (data.date_start)
		{
			this.setStatus("pause");
		}
		else if (data.new)
		{
			this.setStatus("new");
		}
		else
		{
			this.setStatus("");
		}

		// info block
		this.switchVisible(
			this.task_content,
			data.count_files > 0 || data.check_list.complete !== 0 || data.check_list.work !== 0
		);

		// new comments
		if (data.counter.value > 0)
		{
			this.task_counter.setColor(data.counter.color);
			if (Number(this.task_counter.getValue()) !== Number(data.counter.value))
			{
				this.task_counter.update(data.counter.value);
			}
		}
		this.switchVisible(this.task_counter_container, (data.counter.value > 0));

		//region checklist
		this.switchVisible(
			this.check_list,
			data.check_list.complete !== 0 || data.check_list.work !== 0
		);
		this.switchClass(
			this.check_list,
			"tasks-kanban-item-super-blue",
			data.log.checklist > 0
		);
		this.check_list.setAttribute("title",
			BX.message("TASKS_KANBAN_TITLE_CHECKLIST")
			.replace("#complete#", data.check_list.complete)
			.replace("#all#", (parseInt(data.check_list.complete) + parseInt(data.check_list.work)))
		);
		this.check_list.textContent = data.log.checklist > 0
										? "+" + data.log.checklist
										: data.check_list.complete + "/" +
										 (+data.check_list.complete + +data.check_list.work);

		// endregion

		// files
		this.switchVisible(
			this.count_files,
			data.count_files > 0
		);
		this.switchClass(
			this.count_files,
			"tasks-kanban-item-super-blue",
			data.log.file > 0
		);
		this.count_files.setAttribute(
			"title",
			BX.message("TASKS_KANBAN_TITLE_FILES").replace("#count#", data.count_files)
		);
		this.count_files.textContent = data.log.file > 0
										? "+" + data.log.file
										: data.count_files;
		// users
		if (!this.isSprintView && data.author)
		{
			if (this.author)
			{
				BX.remove(this.author);
			}

			this.author = BX.create("div", {
				props: {
					className: "tasks-kanban-item-author",
					style: "cursor: pointer"
				}
			});

			var authorType;

			if(data.author.extranet)
			{
				authorType = "tasks-kanban-item-author-avatar tasks-kanban-item-author-avatar-extranet";
			}
			else if (data.author.crm)
			{
				authorType = "tasks-kanban-item-author-avatar tasks-kanban-item-author-avatar-crm";
			}
			else if (data.author.mail)
			{
				authorType = "tasks-kanban-item-author-avatar tasks-kanban-item-author-avatar-mail";
			}
			else
			{
				authorType = "tasks-kanban-item-author-avatar";
			}

			var avatarClass;

			!data.author.photo ? avatarClass = ' tasks-kanban-item-author-avatar-empty' : avatarClass = '';

			this.author.appendChild(BX.create("div", {
				props: {
					title: data.author.name,
					className: authorType + avatarClass
				},
				style: {
					backgroundImage: data.author.photo
						? "url(\'" + encodeURI(data.author.photo.src) + "\')"
						: "",
					cursor: data.allow_edit
						? "pointer"
						: "default"
				},
				events: {
					click: data.allow_edit
							? BX.delegate(function(e) {
								this.changeAuthorTask();
								e.stopPropagation();
							}, this)
							: function() {}
				}
			}));
			this.user_container.appendChild(this.author);
		}
		if (data.responsible)
		{
			if (this.responsible)
			{
				BX.remove(this.responsible);
			}

			var responsibleClassName = (this.isSprintView ?
				'tasks-kanban-item-responsible-sprint' : 'tasks-kanban-item-responsible');

			this.responsible = BX.create("div", {
				props: {
					className: responsibleClassName,
					style: "cursor: pointer"
				}
			});

			var responsibleType;

			if(data.responsible.extranet)
			{
				responsibleType = "tasks-kanban-item-author-avatar tasks-kanban-item-author-avatar-extranet";
			}
			else if (data.responsible.crm)
			{
				responsibleType = "tasks-kanban-item-author-avatar tasks-kanban-item-author-avatar-crm";
			}
			else if (data.responsible.mail)
			{
				responsibleType = "tasks-kanban-item-author-avatar tasks-kanban-item-author-avatar-mail";
			}
			else
			{
				responsibleType = "tasks-kanban-item-author-avatar";
			}

			this.responsible.appendChild(BX.create("div", {
				props: {
					title: data.responsible.name,
					className: responsibleType
				},
				style: {
					backgroundImage: 	data.responsible.photo
										? "url(\'" + encodeURI(data.responsible.photo.src) + "\')"
										: "",
					cursor: data.allow_delegate
						? "pointer"
						: "default"
				},
				events: {
					click: data.allow_delegate
							? BX.delegate(function(e) {
								this.delegateTask();
								e.stopPropagation();
							}, this)
							: function() {}
				}
			}));
			this.user_container.appendChild(this.responsible);
		}
		// time
		if (this.time_logs)
		{
			this.switchVisible(
				this.time_logs,
				data.time_tracking
			);
			if (data.time_tracking)
			{
				this.time_logs.textContent = parseInt(data.time_estimate) > 0
					? this.renderTime(data.time_logs) + " / " + this.renderTime(data.time_estimate, false)
					: this.renderTime(data.time_logs);
			}
			else
			{
				this.time_logs.textContent = "";
			}
		}

		// controls block
		if (!this.getGrid().isScrumGrid())
		{
			this.switchClass(
				this.container,
				'tasks-kanban-item-without-control',
				(data.completed || (!data.allow_complete && !data.allow_start))
			);
			if (this.task_start)
			{
				this.switchVisible(this.task_start, data.allow_start);
			}
			if (this.task_complete)
			{
				this.switchVisible(this.task_complete, data.allow_complete);
			}
		}

		// item fields
		BX.cleanNode(this.itemFieldsNode);
		if (data?.item_fields?.length > 0)
		{
			data.item_fields.forEach((item) =>
			{
				if (item?.value || item?.collection?.length > 0)
				{
					this.itemFieldsNode.appendChild(this.getItemFieldDiv(item));
				}
			});
		}

		return this.container;
	},

	getItemFieldDiv(item)
	{
		return BX.create("div", {
			props: {
				className: "tasks-kanban-item-fields-item"
			},
			children: [
				BX.create("div", {
					props: {
						className: "tasks-kanban-item-fields-item-title"
					},
					children:[
						BX.create("div", {
							props: {
								className: "tasks-kanban-item-fields-item-title-text"
							},
							text: item?.label,
						})
					],
				}),
				BX.create("div", {
					props: {
						className: "tasks-kanban-item-fields-item-value"
					},
					text: item?.value,
					html: (item?.collection?.length > 0)
						? this.getItemFieldLinks(item.collection)
						: ''
				}),
			]
		});
	},

	getItemFieldLinks(collection)
	{
		return collection.map((item) => {
			return `<a href="${BX.util.htmlspecialchars(item.url)}">${BX.util.htmlspecialchars(item.name)}</a>`;
		});
	},

	animate: function(params)
	{
		var duration = params.duration;
		var draw = params.draw;

		// linear function by default, you can set non-linear animation function in timing key
		var timing = (params.timing || function(timeFraction){
			return timeFraction;
		});

		var useAnimation = ((params.useAnimation && !this.isAnimationInProgress) || false);

		var start = performance.now();

		return new Promise(
			function(resolve, reject)
			{
				if (!useAnimation)
				{
					this.isAnimationInProgress = false;
					return resolve();
				}

				var self = this;
				self.isAnimationInProgress = true;

				requestAnimationFrame(function animate(time)
				{
					var timeFraction = (time - start) / duration;
					if (timeFraction > 1)
					{
						timeFraction = 1;
					}

					var progress = timing(timeFraction);
					draw(progress);

					if (timeFraction < 1)
					{
						requestAnimationFrame(animate);
					}

					if (progress === 1)
					{
						self.isAnimationInProgress = false;
						resolve();
					}
				}.bind(this));
			}.bind(this)
		);
	},

	/**
	 * Create layout for one item.
	 * @returns {void}
	 */
	createLayout: function()
	{
		var data = this.getData();

		//region common container
		this.container = BX.create("div", {
			props: {
				className: "tasks-kanban-item"
			},
			events: {
				click: function()
				{
					if (
						typeof BX.Bitrix24 !== "undefined" &&
						typeof BX.Bitrix24.PageSlider !== "undefined"
					)
					{
						// BX.Bitrix24.PageSlider.open(this.getTaskUrl(this.getId()));
					}
				}.bind(this)
			}
		});

		//endregion

		//region title link
		this.link = BX.create("a", {
			props: {
				className: data.counter.value > 0 ? "tasks-kanban-item-title" : "tasks-kanban-item-title tasks-kanban-item-title--with-counter"
			}
		});
		this.container.appendChild(this.link);
		this.container.appendChild(BX.Tag.render`<div class="tasks-kanban-item-line"></div>`);

		//endregion

		// region item fields
		this.itemFieldsNode = BX.create("div", {
			props: { className: "tasks-kanban-item-fields" }
		});
		this.container.appendChild(this.itemFieldsNode);

		if (this.getGrid().isScrumGrid())
		{
			//region epic
			this.epicLayout = BX.create("span", {
				props: {
					className: "tasks-kanban-item-epic-container"
				}
			});
			if (this.epic)
			{
				this.epicLayout.appendChild(this.createEpicLayout());
			}
			this.container.appendChild(this.epicLayout);
		}

		//region tags
		this.tags = BX.create("span", {
			props: {
				className: "tasks-kanban-item-tags"
			}
		});
		this.container.appendChild(this.tags);

		//endregion

		//region status
		this.task_status = BX.create("div", {
			props: {
				className: "tasks-kanban-item-task-status"
			}
		});
		this.container.appendChild(this.task_status);

		//endregion

		//region status title
		this.task_status_title = BX.create("div", {
			props: {
				className: "tasks-kanban-item-status"
			}
		});
		this.task_status.appendChild(this.task_status_title);

		//endregion

		//region background
		this.containerImg = BX.create("a", {
			props: {
				className: "tasks-kanban-item-image"
			}
		});
		this.container.appendChild(this.containerImg);

		//endregion

		//region info block
		this.task_content = BX.create("div", {
			props: { className: "tasks-kanban-item-info" }
		});
		this.container.appendChild(this.task_content);

		//endregion

		//region deadline
		if (!this.isSprintView && data.deadline_visibility !== 'hidden')
		{
			this.date_deadline = new BX.UI.Label({
				text: data.deadline.value.replace('&minus;', '-'),
				color: data.deadline.color,
				fill: (data.date_deadline ? data.deadline.fill : false),
				size: BX.UI.Label.Size.SM,
			});
			this.date_deadline_container = BX.create("div", {
				props: {
					className: "tasks-kanban-item-deadline",
				},
				children: [
					this.date_deadline.getContainer()
				]
			});
			this.container.appendChild(this.date_deadline_container);
		}

		//endregion

		//region comments / checklist / files
	/*	this.count_comments = BX.create("a", {
			props: {
				className: "tasks-kanban-item-comments"
			},
			events: {
				click: function(e) {
					e.stopPropagation();
				}
			}
		});
		this.task_content.appendChild(this.count_comments);*/

		this.check_list = BX.create("div", {
			props: {
				className: "tasks-kanban-item-checklist"
			}
		});
		this.task_content.appendChild(this.check_list);
		this.count_files = BX.create("div", {
			props: {
				className: "tasks-kanban-item-files"
			}
		});
		this.task_content.appendChild(this.count_files);

		this.actions_container = BX.create("div", {
			props: {
				className: "tasks-kanban-actions-container"
			}
		});
		this.container.appendChild(this.actions_container);

		//endregion

		//region user block
		this.user_container = BX.create("div", {
			props: {
				className: "tasks-kanban-item-users"
			}
		});
		this.actions_container.appendChild(this.user_container);

		//endregion

		if (!this.getGrid().isScrumGrid())
		{
			//region  time
			this.time_logs = BX.create("div", {
				props: {
					className: "tasks-kanban-item-timelogs"
				}
			});
			this.actions_container.appendChild(this.time_logs);

			//endregion

			this.track_control = BX.create("div", {
				props: {
					className: "tasks-kanban-item-control"
				}
			});
			this.container.appendChild(this.track_control);

			this.task_mute = BX.create("div", {
				props: {
					className: "tasks-kanban-task-mute"
				},
				events: {
					click: function (e)
					{
						this.muteTask();
						e.stopPropagation();
					}.bind(this)
				}
			});
			// BX.hide(this.task_mute);
			this.track_control.appendChild(this.task_mute);

			this.task_start = BX.create("div", {
				props: {
					className: "tasks-kanban-task-start"
				},
				events: {
					click: function (e)
					{
						this.startTask();
						e.stopPropagation();
					}.bind(this)
				}
			});
			this.track_control.appendChild(this.task_start);

			this.task_complete = BX.create("div", {
				props: {
					className: "tasks-kanban-task-complete",
					title: BX.message("TASKS_KANBAN_TITLE_COMPLETE")
				},
				events: {
					click: function (e)
					{
						this.completeTask();
						e.stopPropagation();
					}.bind(this)
				}
			});
			this.track_control.appendChild(this.task_complete);
		}
		else
		{
			this.storyPointsNode = BX.create('div', {
				props: {
					className: 'tasks-kanban-item-story-points',
					title: BX.message('TASKS_KANBAN_ITEM_STORY_POINTS_TITLE')
				},
				text: this.getDataKey('storyPoints'),
			});

			this.container.appendChild(this.storyPointsNode);
		}

		//endregion

		//region Counters
		this.task_counter = new BX.UI.Counter({
			value: data.counter.value,
			color: data.counter.color,
			animate: true
		});
		this.task_counter_container = BX.create("div", {
			props: {
				className: "tasks-kanban-task-counter",
			},
			children: [
				this.task_counter.getContainer()
			]
		});
		this.container.appendChild(this.task_counter_container);

		//endregion

		//region hover / shadow
		this.container.appendChild(this.createShadow());
		this.container.addEventListener("mouseenter", function()
		{
			this.addHoverClass(this.container);
		}.bind(this));
		this.container.addEventListener("mouseleave", function ()
		{
			this.removeHoverClass(this.container);
		}.bind(this), false);

		//endregion
	},

	/**
	 * Add shadow to item.
	 * @returns {DOMNode}
	 */
	createShadow: function ()
	{
		return BX.create("div", {
			props: { className: "tasks-kanban-item-shadow" }
		});
	},

	/**
	 * @returns {Element}
	 */
	getContainer: function()
	{
		if (this.layout.container !== null)
		{
			return this.layout.container;
		}

		this.layout.container = BX.create("div", {
			attrs: {
				className: "main-kanban-item",
				"data-id": this.getId(),
				"data-type": "item"
			},
			children: [
				this.getDragTarget(),
				this.getBodyContainer()
			]
		});

		this.makeDraggable();
		this.makeDroppable();

		if(this.grid.firstRenderComplete && !this.draftContainer)
		{
			this.layout.container.classList.add("main-kanban-item-new");
			var cleanAnimate = function() {
				this.layout.container.classList.remove("main-kanban-item-new");
				this.getBodyContainer().removeEventListener("animationend", cleanAnimate);
			}.bind(this);
			this.getBodyContainer().addEventListener("animationend", cleanAnimate);
		}

		BX.addCustomEvent(this.getGrid(), "Kanban.Grid:onItemDragStart", function() {
			if(this.getGrid().isRealtimeMode())
			{
				this.disableDropping();
			}
		}.bind(this));

		BX.addCustomEvent(this.getGrid(), "Kanban.Grid:onItemDragStop", function() {
			if(this.getGrid().isRealtimeMode())
			{
				this.enableDropping();
			}
		}.bind(this));

		return this.layout.container;
	},

	/**
	 * Add hover to item.
	 * @param {DOMNode} itemBlock
	 * @returns {void}
	 */
	addHoverClass: function (itemBlock)
	{
		this.timer = setTimeout(function ()
		{
			itemBlock.classList.add("tasks-kanban-item-hover");
		}, 150);
	},

	/**
	 * Remove hover from item.
	 * @param {DOMNode} itemBlock
	 * @returns {void}
	 */
	removeHoverClass: function (itemBlock)
	{
		clearTimeout(this.timer);
		itemBlock.classList.remove("tasks-kanban-item-hover");
	},

	createEpicLayout: function ()
	{
		var colorBorder = this.convertHexToRGBA(this.epic.color, 0.7);
		var colorBackground = this.convertHexToRGBA(this.epic.color, 0.3);

		return BX.create("span", {
			props: {
				className: "tasks-kanban-item-epic"
			},
			style: {
				background: colorBackground,
				borderColor: colorBorder
			},
			text: this.epic.name,
			events: {
				click: BX.delegate(function(e) {
					this.setFilterEpic(this.epic.id);
					e.stopPropagation();
				}, this)
			}
		});
	},

	convertHexToRGBA: function (hexCode, opacity)
	{
		var hex = hexCode.replace('#', '');

		if (hex.length === 3)
		{
			hex = String(hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2]);
		}

		var r = parseInt(hex.substring(0, 2), 16);
		var g = parseInt(hex.substring(2, 4), 16);
		var b = parseInt(hex.substring(4, 6), 16);

		return 'rgba(' + r + ',' + g + ',' + b + ',' + opacity + ')';
	}

};

})();