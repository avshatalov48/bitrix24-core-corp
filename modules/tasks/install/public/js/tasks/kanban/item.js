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

		BX.show(this.task_complete);
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
		if (code === "completed" || code === "completed_supposedly")
		{
			BX.hide(this.task_complete);
			BX.addClass(this.task_status_title, "tasks-kanban-item-gray");
			if (code === "completed_supposedly")
			{
				this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_COMPLETED_SUPPOSEDLY");
			}
			else
			{
				this.task_status_title.textContent = BX.message("TASKS_KANBAN_STATUS_COMPLETED");
			}
		}
		else
		{
			BX.hide(this.task_status_title);
		}

		if (data.in_progress && !BX.hasClass(this.task_start, "tasks-kanban-task-pause"))
		{
			BX.addClass(this.task_start, "tasks-kanban-task-pause");
			this.task_start.setAttribute("title", BX.message("TASKS_KANBAN_TITLE_PAUSE"));
		}
		else if (!data.in_progress)
		{
			BX.removeClass(this.task_start, "tasks-kanban-task-pause");
			this.task_start.setAttribute("title", BX.message("TASKS_KANBAN_TITLE_START"));
		}

		if (data.muted && !BX.hasClass(this.task_mute, "tasks-kanban-task-muted"))
		{
			BX.addClass(this.container, "tasks-kanban-task-muted");
			this.task_mute.setAttribute("title", BX.message("TASKS_KANBAN_TITLE_UNMUTE"));
			this.task_counter.setColor(BX.UI.Counter.Color.GRAY);
		}
		else if (!data.muted)
		{
			BX.removeClass(this.container, "tasks-kanban-task-muted");
			this.task_mute.setAttribute("title", BX.message("TASKS_KANBAN_TITLE_MUTE"));
			this.task_counter.setColor(data.is_expired ? BX.UI.Counter.Color.DANGER : BX.UI.Counter.Color.SUCCESS);
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

		BX.calendar({
			node: BX.proxy_context,
			value: value,
			currentTime: value,
			bTime: true,
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
			}.bind(this)
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

		var selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
			scope: BX.proxy_context,
			id: action + "-" + this.getId(),
			mode: "user",
			query: false,
			useSearch: true,
			useAdd: false,
			parent: this,
			popupOffsetTop: 5,
			popupOffsetLeft: 40
		});
		selector.bindEvent("item-selected", BX.delegate(function(data){
			//this._control.setData(BX.util.htmlspecialcharsback(data.nameFormatted), data.id);
			var gridData = this.getGridData();

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

			selector.close();
		}, this));
		selector.open();
	},

	/**
	 * Get URL to task.
	 * @param {Integer} id Task id.
	 * @returns {String}
	 */
	getTaskUrl: function(id)
	{
		return this.getGridData().pathToTask.replace("#task_id#", id);
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
	 * Set tag to the filter.
	 * @returns {void}
	 */
	setFilterTag: function()
	{
		var tagName = BX.proxy_context.textContent.substr(1),
			gridData = this.getGridData(),
			filterManager = BX.Main.filterManager.getById(gridData.gridId),
			filterApi = filterManager.getApi(),
			currValues = filterManager.getFilterFieldsValues();

		currValues.TAG = tagName;
		filterApi.setFields(currValues);
		filterApi.apply();
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
		var withoutControl = data.completed || (!data.allow_complete && !data.allow_start);
		var formatLang = BX.message("LANGUAGE_ID");

		if (formatLang !== "en" && formatLang !== "de")
		{
			formatLang = "ru";
		}

		// border color
		BX.style(this.container, "border-left", "3px solid " + rgba);
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
		// tags
		if (data.tags && data.tags.length > 0)
		{
			BX.cleanNode(this.tags);
			for (var i = 0, c = data.tags.length; i < c; i++)
			{
				this.tag = BX.create("span", {
					props: {
						className: "ui-label ui-label-tag-light ui-label-fill ui-label-sm ui-label-link"
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

		this.deadlineNotificationDate = (data.date_deadline ? data.deadline.value.replace('&minus;', '-') : '');

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
		if (data.author)
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
						? "url(\'" + data.author.photo.src + "\')"
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

			this.responsible = BX.create("div", {
				props: {
					className: "tasks-kanban-item-responsible",
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
										? "url(\'" + data.responsible.photo.src + "\')"
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
		// controls block
		this.switchClass(
			this.container,
			"tasks-kanban-item-without-control",
			withoutControl
		);
		this.switchVisible(
			this.task_start,
			!withoutControl
		);
		this.switchVisible(
			this.task_start,
			data.allow_start
		);
		this.switchVisible(
			this.task_complete,
			data.allow_complete
		);

		return this.container;
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

		//endregion

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

		//region  time
		this.time_logs = BX.create("div", {
			props: {
				className: "tasks-kanban-item-timelogs"
			}
		});
		this.actions_container.appendChild(this.time_logs);

		//endregion

		//region controls block
		this.track_control = BX.create("div", {
			props: {
				className: "tasks-kanban-item-control"
			}
		});
		this.container.appendChild(this.track_control);

		//endregion

		//region mute button
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

		//endregion

		//region start button
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
		//endregion

		//region checked button
		if(this.getGrid().isMultiSelect())
		{
			this.task_check = BX.create("div", {
				props: {
					className: "tasks-kanban-item-checkbox"
				},
				events: {
					click: function()
					{
						this.checked = !this.checked;
						this.checked
							? BX.addClass(this.checkedButton, "tasks-kanban-item-checkbox-checked")
							: BX.removeClass(this.checkedButton, "tasks-kanban-item-checkbox-checked");
					}.bind(this)
				}
			});

			this.container.appendChild(this.task_check);
		}

		//endregion

		//region complete button
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
				className: this.grid.firstRenderComplete ? "main-kanban-item main-kanban-item-new" : "main-kanban-item",
				"data-id": this.getId(),
				"data-type": "item"
			},
			children: [
				this.getDragTarget(),
				this.getBodyContainer()
			],
			events: {
				click: this.handleClick.bind(this)
			}
		});

		this.makeDraggable();
		this.makeDroppable();

		BX.addCustomEvent("Kanban.Grid:onItemDragStart", function() {
			if(this.getGrid().isRealtimeMode())
			{
				this.disableDropping();
			}
		}.bind(this));

		BX.addCustomEvent("Kanban.Grid:onItemDragStop", function() {
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
	}

};

})();
