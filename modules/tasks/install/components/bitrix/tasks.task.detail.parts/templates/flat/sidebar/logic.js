BX.namespace("Tasks.Component");

(function() {

	if(typeof BX.Tasks.Component.TaskViewSidebar != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TaskViewSidebar = function(parameters)
	{
		this.layout = {
			stagesWrap: BX("tasksStagesWrap"),
			stages: BX("tasksStages"),
			epicTitle: BX("tasksEpicTitle"),
			epicContainer: BX("tasksEpicContainer")
		};
		this.parameters = parameters || {};
		this.taskId = this.parameters.taskId;
		this.parentId = this.parameters.parentId;
		this.groupId = parseInt(this.parameters.groupId, 10);
		this.messages = this.parameters.messages || {};
		this.workingTime = this.parameters.workingTime || { start : { hours: 0, minutes: 0 }, end : { hours: 0, minutes: 0 }};
		this.can = this.parameters.can || {};
		this.allowTimeTracking = this.parameters.allowTimeTracking === true;
		this.user = this.parameters.user || {};
		this.isAmAuditor = this.parameters.iAmAuditor;
		this.showIntranetControl = this.parameters.showIntranetControl;
		this.auditorCtrl = null;
		this.pathToTasks = this.parameters.pathToTasks;
		this.stageId = parseInt(this.parameters.stageId);
		this.stages = this.parameters.stages || {};
		this.taskLimitExceeded = this.parameters.taskLimitExceeded;
		this.calendarSettings = (this.parameters.calendarSettings ? this.parameters.calendarSettings : {});
		this.isScrumTask = this.parameters.isScrumTask === 'Y';

		this.initDeadline();
		this.initReminder();
		this.initMark();
		this.initTime();
		this.initAuditorThing();
		this.initStages();
		this.initIntranetControlButton();

		BX.addCustomEvent(window, "tasksTaskEvent", BX.delegate(this.onTaskEvent, this));
		BX.addCustomEvent(window, "onChangeProjectLink", BX.delegate(this.onChangeProjectLink, this));
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.initIntranetControlButton = function()
	{
		if (!this.showIntranetControl)
		{
			return;
		}

		BX.loadExt('intranet.control-button').then(function() {
			if (BX.Intranet.ControlButton)
			{
				new BX.Intranet.ControlButton({
					container: BX('task-detail-sidebar-item-videocall'),
					entityType: 'task',
					entityId: this.taskId
				});
			}
		}.bind(this));
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.initAuditorThing = function()
	{
		if(!this.can.EDIT)
		{
			BX.Tasks.Util.Dispatcher.find('auditor-selector').then(function(ctrl){
				this.auditorCtrl = ctrl;
				ctrl.bindControl('header-button', 'click', this.onToggleImAuditor.bind(this));
			}.bind(this));
		}
	};

	/**
	 * Draw stages block.
	 * @returns {void}
	 */
	BX.Tasks.Component.TaskViewSidebar.prototype.initStages = function()
	{
		if (this.layout.stages && this.stages)
		{
			var canChange = this.parameters.can.SORT;
			var stagesShowed = this.stages.length > 0;

			BX.cleanNode(this.layout.stages);

			for (var i=0, c=this.stages.length; i<c; i++)
			{
				this.layout.stages.appendChild(
					this.stages[i].TEXT_LAYOUT = BX.create("div", {
						attrs: {
							"data-stageId": this.stages[i].ID,
							title: this.stages[i].TITLE
						},
						props: {
							className: "task-section-status-step"
						},
						text: this.stages[i].TITLE,
						events:
							canChange
								? {
								click: BX.delegate(this.setStageHadnler, this),
							}
								: null,
						style:
							!canChange
								? {
								cursor: "default"
							}
								: null
					})
				);
			}

			if (stagesShowed)
			{
				BX.show(this.layout.stagesWrap);

				if (this.stageId > 0)
				{
					this.setStage(this.stageId);
				}
				else
				{
					this.setStage(this.stages[0].ID);
				}
			}
			else
			{
				BX.hide(this.layout.stagesWrap);
			}
		}
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.hideEpicSelector = function()
	{
		if (
			!this.layout.epicTitle
			|| !this.layout.epicContainer
		)
		{
			return;
		}

		BX.hide(this.layout.epicTitle);
		BX.hide(this.layout.epicContainer);
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.showEpicSelector = function()
	{
		if (
			!this.layout.epicTitle
			|| !this.layout.epicContainer
		)
		{
			return;
		}

		BX.show(this.layout.epicTitle);
		BX.show(this.layout.epicContainer);
	};

	/**
	 * Handler on change task group.
	 * @param {int} groupId
	 * @param {int} taskId
	 * @returns {void}
	 */
	BX.Tasks.Component.TaskViewSidebar.prototype.onChangeProjectLink = function(groupId, taskId)
	{
		groupId = parseInt(groupId);

		// stage id is nulled after group change
		this.stageId = 0;

		// get new stages and redraw block
		if (groupId === 0)
		{
			this.stages = [];
			this.initStages();
			this.hideEpicSelector();

			return;
		}

		BX.ajax.runComponentAction(
			'bitrix:tasks.task',
			'needShowEpicField',
			{
				mode: 'class',
				data: {
					groupId: groupId
				}
			}
		).then(
			function(response)
			{
				if (response.data && response.data === true)
				{
					this.showEpicSelector();
				}
			}.bind(this)
		);

		BX.ajax.runComponentAction('bitrix:tasks.task', 'canMoveStage', {
			mode: 'class',
			data: {
				entityId: groupId,
				entityType: "G"
			}
		}).then(
			function(response)
			{
				if (
					response.data
					&& response.data === true
				)
				{
					this.parameters.can.SORT = true;
				}
			}.bind(this)
		);

		BX.ajax.runComponentAction('bitrix:tasks.task', 'getStages', {
			mode: 'class',
			data: {
				entityId: groupId,
				isNumeric: 1
			}
		}).then(
			function(response)
			{
				if (response.data)
				{
					this.stages = response.data;
					this.initStages();
				}
			}.bind(this)
		);
	};

	/**
	 * Get data of the stage.
	 * @param {int} stageId
	 * @returns {Object|null}
	 */
	BX.Tasks.Component.TaskViewSidebar.prototype.getStageData = function(stageId)
	{
		stageId = parseInt(stageId);

		if (this.stages)
		{
			for (var id in this.stages)
			{
				if (parseInt(this.stages[id].ID) === stageId)
				{
					return this.stages[id];
				}
			}
		}

		return null;
	};

	/**
	 * Handler for click by task stage.
	 * @returns {void}
	 */
	BX.Tasks.Component.TaskViewSidebar.prototype.setStageHadnler = function()
	{
		var promise = new BX.Promise();

		var stageId = parseInt(BX.data(BX.proxy_context, 'stageId'), 10);
		if (stageId === this.stageId)
		{
			return;
		}

		var taskStatus = null;
		var isParentScrumTask = false;

		if (this.isScrumTask && BX.type.isArray(this.stages))
		{
			var previousStageId = this.stageId;

			var isFinishStage = false;
			var isPreviousFinishStage = false;
			for (var k in this.stages)
			{
				var stage = this.stages[k];
				if (
					parseInt(stage.ID) === stageId
					&& stage.SYSTEM_TYPE === 'FINISH'
				)
				{
					isFinishStage = true;
				}
				else if (
					parseInt(stage.ID) === previousStageId
					&& stage.SYSTEM_TYPE === 'FINISH'
				)
				{
					isPreviousFinishStage = true;
				}
			}

			if (isFinishStage || isPreviousFinishStage)
			{
				top.BX.loadExt('tasks.scrum.task-status').then(function() {
					if (
						!BX.type.isUndefined(top.BX.Tasks.Scrum)
						&& !BX.type.isUndefined(top.BX.Tasks.Scrum.TaskStatus)
					)
					{
						taskStatus = new top.BX.Tasks.Scrum.TaskStatus({
							groupId: this.groupId,
							parentTaskId: this.parentId,
							taskId: this.taskId,
							action: isFinishStage ? 'complete': 'renew',
							performActionOnParentTask: true
						});
						taskStatus.isParentScrumTask(this.parentId)
							.then(function(result) {
								isParentScrumTask = result;
								if (isParentScrumTask)
								{
									promise.fulfill();
								}
								else
								{
									if (isFinishStage)
									{
										taskStatus.showDod(this.taskId)
											.then(function() {
												promise.fulfill();
											}.bind(this))
											.catch(function() {
												promise.reject();
											}.bind(this))
										;
									}
									else
									{
										promise.fulfill();
									}
								}
							}.bind(this))
						;
					}
					else
					{
						promise.fulfill();
					}
				}.bind(this));
			}
			else
			{
				promise.fulfill();
			}
		}
		else
		{
			promise.fulfill();
		}

		promise.then(function() {
			this.setStage(stageId);
			this.saveStage(stageId)
				.then(function() {
					if (taskStatus && isParentScrumTask)
					{
						taskStatus.update();
					}
				}.bind(this))
			;
		}.bind(this));
	};

	/**
	 * Server-side set stage of task.
	 * @param {int} stageId
	 * @returns {BX.Promise}
	 */
	BX.Tasks.Component.TaskViewSidebar.prototype.saveStage = function(stageId)
	{
		this.stageId = stageId;

		return BX.ajax.runComponentAction('bitrix:tasks.task', 'moveStage', {
			mode: 'class',
			data: {
				taskId: this.taskId,
				stageId: stageId
			}
		}).then(
			function(response)
			{
				if (
					response
					&& response.errors
					&& response.errors.length === 0
				)
				{
					BX.Tasks.Util.fireGlobalTaskEvent(
						"UPDATE_STAGE",
						{ID: this.taskId, STAGE_ID: stageId},
						{STAY_AT_PAGE: true},
						{id: this.taskId}
					);

					return true;
				}

				return false;
			}.bind(this)
		);
	};

	/**
	 * Visual set stage of task.
	 * @param {int} stageId
	 * @returns {void}
	 */
	BX.Tasks.Component.TaskViewSidebar.prototype.setStage = function(stageId)
	{
		var stage = this.getStageData(stageId);
		stageId = parseInt(stageId);

		if (this.stages && stage)
		{
			var color = "#" + stage["COLOR"];
			var clearAll = true;
			var layout;
			for (var i=0, c=this.stages.length; i<c; i++)
			{
				layout = this.stages[i].TEXT_LAYOUT;
				if (clearAll)
				{
					layout.style.color = this.calculateTextColor(color);
					layout.style.backgroundColor = color;
					layout.style.borderBottomColor = color;
				}
				else
				{
					layout.style.backgroundColor = "";
					layout.style.borderBottomColor = "#" + this.stages[i].COLOR;
				}
				if (parseInt(this.stages[i].ID) === stageId)
				{
					clearAll = false;
				}
			}
		}
	};

	/**
	 * Calculate text color - black or white.
	 * @param {String} baseColor
	 * @returns {String}
	 */
	BX.Tasks.Component.TaskViewSidebar.prototype.calculateTextColor = function(baseColor)
	{
		var defaultColors = [
			"00c4fb",
			"47d1e2",
			"75d900",
			"ffab00",
			"ff5752",
			"468ee5",
			"1eae43"
		];
		var r, g, b;

		if (BX.util.in_array(baseColor.toLowerCase(), defaultColors))
		{
			return "#fff";
		}
		else
		{
			var c = baseColor.split("");
			if (c.length== 3){
				c= [c[0], c[0], c[1], c[1], c[2], c[2]];
			}
			c = "0x" + c.join("");
			r = ( c >> 16 ) & 255;
			g = ( c >> 8 ) & 255;
			b =  c & 255;
		}

		var y = 0.21 * r + 0.72 * g + 0.07 * b;
		return ( y < 145 ) ? "#fff" : "#333";
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.onToggleImAuditor = function()
	{
		if (this.isAmAuditor) // i am auditor now, it will be leaving
		{
			BX.Tasks.confirm(BX.message('TASKS_TTDP_TEMPLATE_USER_VIEW_LEAVE_AUDITOR_CONFIRM')).then(function(){
				this.syncAuditor();
			}.bind(this));
		}
		else if (this.taskLimitExceeded)
		{
			BX.UI.InfoHelper.show('limit_tasks_observers_participants', {
				isLimit: true,
				limitAnalyticsLabels: {
					module: 'tasks',
					source: 'sidebar',
					subject: 'auditor'
				}
			});
		}
		else
		{
			this.syncAuditor();
		}
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.syncAuditor = function()
	{
		var action = this.isAmAuditor ? 'leaveAuditor' : 'enterAuditor';
		BX.ajax.runComponentAction('bitrix:tasks.widget.member.selector', action, {
			mode: 'class',
			data: {
				taskId: this.taskId,
				context: ''
			}
		}).then(
			function(response)
			{
				this.user.entityType = 'U';

				// add\remove self
				if(this.isAmAuditor)
				{
					this.auditorCtrl.deleteItem(this.user);
				}
				else
				{
					this.auditorCtrl.addItem(this.user);
				}

				this.isAmAuditor = !this.isAmAuditor;
				this.auditorCtrl.setHeaderButtonLabelText(
					this.isAmAuditor ?
						BX.message('TASKS_TTDP_TEMPLATE_USER_VIEW_LEAVE_AUDITOR') :
						BX.message('TASKS_TTDP_TEMPLATE_USER_VIEW_ENTER_AUDITOR')
				);
			}.bind(this)
		).catch(
			function(response)
			{
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);
				}
			}.bind(this)
		);

		// rights check
		BX.ajax.runComponentAction('bitrix:tasks.task', 'checkCanRead', {
			mode: 'class',
			data: {
				taskId: this.taskId
			}
		}).then(
			function(response)
			{
				if(
					!response.data
					|| !response.data.READ
				)
				{
					if(this.pathToTasks)
					{
						window.document.location = this.pathToTasks;
					}
					else
					{
						BX.reload();
					}
				}
			}.bind(this)
		).catch(
			function(response)
			{
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);
				}
			}.bind(this)
		);
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.onTaskEvent = function(type, parameters)
	{
		parameters = parameters || {};
		var data = parameters.task || {};

		if(type == 'UPDATE' && data.ID == this.parameters.taskId)
		{
			//console.dir(data);

			if(BX.type.isNotEmptyString(data.REAL_STATUS) && BX.type.isNotEmptyString(data.STATUS_CHANGED_DATE))
			{
				this.setStatus(data.REAL_STATUS, data.STATUS_CHANGED_DATE);
			}
		}
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.setStatus = function(status, time)
	{
		var statusName = BX("task-detail-status-name");
		var statusDate = BX("task-detail-status-date");

		statusName.innerHTML = BX.message("TASKS_STATUS_" + status);
		statusDate.innerHTML = (status != 4 && status != 5 ?
			BX.message("TASKS_SIDEBAR_START_DATE") + " " : "") +
			BX.util.htmlspecialchars(time);
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.initDeadline = function()
	{
		this.deadline = BX.type.isNotEmptyString(this.parameters.deadline) ? this.parameters.deadline : "";
		this.layout.deadline = BX("task-detail-deadline");
		this.layout.deadlineClear = BX("task-detail-deadline-clear");

		if (!this.layout.deadline)
		{
			return;
		}

		BX.bind(this.layout.deadline, "click", BX.proxy(this.onDeadlineClick, this));
		BX.bind(this.layout.deadlineClear, "click", BX.proxy(this.clearDeadline, this));
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.onDeadlineClick = function(event)
	{
		var now = new Date();
		var today = new Date(Date.UTC(
			now.getFullYear(),
			now.getMonth(),
			now.getDate(),
			this.workingTime.end.hours,
			this.workingTime.end.minutes
		));

		BX.calendar({
			node: this.layout.deadline,
			field: "",
			form: "",
			bTime: true,
			value: this.deadline ? this.deadline : today,
			bHideTimebar: false,
			bCompatibility: true,
			bCategoryTimeVisibilityOption: 'tasks.bx.calendar.deadline',
			bTimeVisibility: (
				this.calendarSettings ? (this.calendarSettings.deadlineTimeVisibility === 'Y') : false
			),
			callback_after: BX.proxy(function(value, time) {
				this.setDeadline(value);
			}, this)
		});
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.setDeadline = function(deadline)
	{
		this.deadline = BX.calendar.ValueToString(deadline, true, false);

		this.layout.deadline.innerHTML = BX.date.format(
			BX.date.convertBitrixFormat(
				BX.message("FORMAT_DATETIME").replace(":SS", "").replace("/SS", "")),
			deadline,
			null,
			false
		);
		this.layout.deadlineClear.style.display = "";

		this.updateDeadline();
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.clearDeadline = function()
	{
		this.deadline = "";
		this.layout.deadline.innerHTML = this.messages.emptyDeadline;
		this.layout.deadlineClear.style.display = "none";

		this.updateDeadline();
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.updateDeadline = function()
	{
		BX.ajax.runComponentAction('bitrix:tasks.task', 'setDeadline', {
			mode: 'class',
			data: {
				taskId: this.taskId,
				date: this.deadline
			}
		}).then(
			function(response)
			{
				BX.onCustomEvent(window, "tasksTaskEventChangeDeadline", [this.taskId, this.deadline]);
				BX.Tasks.Util.fireGlobalTaskEvent('UPDATE', {ID: this.taskId}, {STAY_AT_PAGE: true}, {id: this.taskId, deadline: this.deadline});
			}.bind(this)
		).catch(
			function(response)
			{
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);
				}
			}.bind(this)
		);
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.addReminder = function()
	{
		BX.onCustomEvent(window, "tasksTaskEventAddReminder", [this.layout.reminderAdd]);
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.initReminder = function()
	{
		this.layout.reminderAdd = BX("task-detail-reminder-add");
		BX.bind(this.layout.reminderAdd, "click", BX.delegate(this.addReminder, this));
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.initMark = function()
	{
		if (!this.can["RATE"])
		{
			return;
		}

		this.mark = this.parameters.mark || "NULL";
		this.layout.mark = BX("task-detail-mark");
		if (this.layout.mark)
		{
			BX.bind(this.layout.mark, "click", BX.proxy(this.onMarkClick, this));
		}
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.onMarkClick = function()
	{
		if (this.taskLimitExceeded)
		{
			BX.UI.InfoHelper.show('limit_tasks_rate', {
				isLimit: true,
				limitAnalyticsLabels: {
					module: 'tasks',
					source: 'taskSidebar'
				}
			});
			return;
		}

		BX.TaskGradePopup.show(
			this.taskId,
			this.layout.mark,
			{
				listValue: this.mark
			},
			{
				events : {
					onPopupChange : BX.proxy(this.onMarkChange, this)
				}
			}
		);
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.onMarkChange = function()
	{
		var popup = BX.proxy_context;

		this.layout.mark.className = "task-detail-sidebar-item-mark-" + popup.listValue.toLowerCase();
		this.layout.mark.innerHTML = popup.listItem.name;

		BX.ajax.runComponentAction('bitrix:tasks.task', 'setMark', {
			mode: 'class',
			data: {
				taskId: this.taskId,
				mark: popup.listValue === "NULL" ? "" :  popup.listValue
			}
		}).then(
			function(response)
			{
				BX.Tasks.Util.fireGlobalTaskEvent('UPDATE', {ID: this.taskId}, {STAY_AT_PAGE: true}, {id: this.taskId});
			}.bind(this)
		).catch(
			function(response)
			{
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);
				}
			}.bind(this)
		);
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.initTime = function()
	{
		if (!this.allowTimeTracking)
		{
			return;
		}

		BX.Tasks.Util.Dispatcher.bindEvent('buttons-dayplan', 'task-timer-tick', BX.delegate(this.onTaskTimerTick, this));
	};

	BX.Tasks.Component.TaskViewSidebar.prototype.onTaskTimerTick = function(taskId, time)
	{
		if (taskId != this.taskId)
		{
			return;
		}

		var node = BX("task-detail-spent-time-" + this.taskId);
		if (node)
		{
			node.innerHTML = BX.Tasks.Util.formatTimeAmount(time);
		}
	};

}).call(this);