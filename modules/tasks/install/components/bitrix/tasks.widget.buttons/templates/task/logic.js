BX.namespace('Tasks.Component');

(function() {

	if(typeof BX.Tasks.Component.TasksWidgetButtonsTask != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TasksWidgetButtonsTask = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'task-view-b'
		},
		options: {
			goToListOnDelete: true
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.vars.data = this.option('data') || {};
				this.vars.can = this.option('can') || {};
				this.vars.publicMode = this.option('publicMode') || false;

				this.vars.overtime = null;
				this.vars.buttonActionEnabled = true;
				this.vars.delegateReload = true;

				this.vars.time = 0;
				if(this.vars.data.TIME_ELAPSED)
				{
					this.vars.time = parseInt(this.vars.data.TIME_ELAPSED);
				}

				this.bindEvents();
				this.getDayPlan(); // pre-init dayplan as it should process all events come from planner
			},

			bindEvents: function()
			{
				this.bindDelegateControl('open-menu', 'click', this.passCtx(this.onMenuOpen));
				this.bindDelegateControl('button', 'click', this.passCtx(this.onButtonPressed));
			},

			onMenuOpen: function(node)
			{
				if(!this.vars.buttonActionEnabled)
				{
					return;
				}

				BX.PopupMenu.show(
					this.code(),
					node,
					this.getTaskMenu(),
					{
						closeByEsc: true
					}
				);
			},

			onButtonPressed: function(node)
			{
				if(!this.vars.buttonActionEnabled)
				{
					return;
				}

				var action = BX.data(node, 'action');
				if(BX.type.isNotEmptyString(action))
				{
					this.doDynamicAction(action);
				}
			},

			getDayPlan: function()
			{
				if(!this.instances.dayplan)
				{
					var taskData = BX.clone(this.option('data'));
					var taskId = this.option('taskId');
					taskData.ID = taskId;

					var data = {};
					data[taskId] = taskData;

					this.instances.dayplan = new BX.Tasks.DayPlan({
						registerDispatcher: true,
						id: 'buttons-dayplan',
						data: data // task data forward to make tick-tack emulation work. see inside for details
					});
					this.instances.dayplan.bindEvent('other-task-on-timer', BX.delegate(this.showTimerConfirm, this)); // show confirm when other task is on timer
					this.instances.dayplan.bindEvent('task-timer-toggle', BX.delegate(this.onTaskTimerToggled, this)); // when task was toggled in planner
					this.instances.dayplan.bindEvent('task-plan-toggle', BX.delegate(this.onTaskPlanToggled, this)); // when task was added to dayplan in planner
					this.instances.dayplan.bindEvent('task-timer-tick', BX.delegate(this.onTaskTimerTick, this)); // when task is running in planner
				}

				return this.instances.dayplan;
			},

			showUserSelector: function()
			{
				if (!this.userSelector)
				{
					this.userSelector = new BX.UI.EntitySelector.Dialog({
						targetNode: this.control('open-menu'),
						enableSearch: true,
						multiple: false,
						context: 'TASKS_MEMBER_SELECTOR_EDIT_responsible',
						entities: [
							{
								id: 'user',
								options: {
									emailUsers: true,
								}
							},
						],
						events: {
							'Item:onSelect': function(event) {
								var item = event.getData().item;
								this.doDynamicAction('DELEGATE', {userId: item.getId()});
							}.bind(this),
						}
					});
				}
				this.userSelector.show();
			},

			doMenuAction: function(menu, e, item)
			{
				var code = item.code;

				if (code)
				{
					if (code === 'COPY')
					{
						window.location = this.option('copyUrl');
					}
					else if (code === 'CREATE_SUBTASK')
					{
						BX.SidePanel.Instance.open(this.option('createSubtaskUrl'));
					}
					else if (code === 'DELEGATE')
					{
						if (this.option('taskLimitExceeded'))
						{
							BX.UI.InfoHelper.show('limit_tasks_delegating', {
								isLimit: true,
								limitAnalyticsLabels: {
									module: 'tasks',
									source: 'taskView'
								}
							});
							return;
						}
						this.showUserSelector();
					}
					else if (code === 'REST')
					{
						BX.SidePanel.Instance.open(item.callbackData.serviceUrl, item.callbackData.opt || {});
					}
					else
					{
						this.doDynamicAction(code);
					}
				}

				menu.popupWindow.close();
			},

			doDynamicAction: function(code, args)
			{
				args = args || {};

				this.togglePanelActivity(false);

				if(code == 'START_TIMER' || code == 'PAUSE_TIMER')
				{
					// do timer actions
					this.getDayPlan()[code == 'START_TIMER' ? 'startTimer' : 'stopTimer'](
						this.option('taskId'),
						true,
						args.force || false
					);
				}
				else if(code == 'DAYPLAN.ADD')
				{
					this.getDayPlan().addToPlan(this.option('taskId'));
					this.reFetchTaskData(code);
				}
				else if(code == 'DELETE')
				{
					window.top.BX.UI.Notification.Center.notify({
						content: BX.message('TASKS_DELETE_SUCCESS')
					});
					this.doDynamicTaskAction(code, args);
				}
				else if(code === 'COMPLETE' || code === 'RENEW')
				{
					var taskCompletePromise = new BX.Promise();
					var taskStatus = null;
					var isParentScrumTask = false;

					if (this.option('isScrumTask'))
					{
						top.BX.loadExt('tasks.scrum.task-status').then(function() {
							if (
								!BX.type.isUndefined(top.BX.Tasks.Scrum)
								&& !BX.type.isUndefined(top.BX.Tasks.Scrum.TaskStatus)
							)
							{
								taskStatus = new top.BX.Tasks.Scrum.TaskStatus({
									groupId: this.option('groupId'),
									parentTaskId: this.option('parentId'),
									taskId: this.option('taskId'),
									action: code === 'COMPLETE' ? 'complete': 'renew',
									performActionOnParentTask: true
								});
								taskStatus.isParentScrumTask(this.option('parentId'))
									.then(function(result) {
										isParentScrumTask = result;
										if (isParentScrumTask)
										{
											taskCompletePromise.fulfill();
										}
										else
										{
											if (code === 'COMPLETE')
											{
												taskStatus.showDod(this.option('taskId'))
													.then(function() {
														taskCompletePromise.fulfill();
													}.bind(this))
													.catch(function() {
														taskCompletePromise.reject();
													}.bind(this))
												;
											}
											else
											{
												taskCompletePromise.fulfill();
											}
										}
									}.bind(this))
								;
							}
							else
							{
								taskCompletePromise.fulfill();
							}
						}.bind(this));
					}
					else
					{
						taskCompletePromise.fulfill();
					}

					taskCompletePromise.then(function() {
						this.doDynamicTaskAction(code, args)
							.then(function() {
								if (taskStatus && isParentScrumTask)
								{
									taskStatus.update();
								}
							}.bind(this))
						;
					}.bind(this));
				}
				else
				{
					this.doDynamicTaskAction(code, args);
				}

				this.togglePanelActivity(true);
			},

			doDynamicTaskAction: function(code, args)
			{
				var taskId = this.option('taskId');
				var self = this;

				var action = code.toLowerCase();

				args = args || {};
				args['id'] = taskId;

				return BX.ajax.runComponentAction('bitrix:tasks.task', action, {
					mode: 'class',
					data: {
						taskId: taskId,
						parameters: args
					}
				}).then(
					function(response)
					{
						if (action === 'delete')
						{
							BX.Tasks.Util.fireGlobalTaskEvent('DELETE', {ID: taskId});

							if(this.option('goToListOnDelete'))
							{
								window.location = this.option('listUrl');
							}
						}
						else
						{
							this.reFetchTaskData(code);
						}

						return true;
					}.bind(this)
				).catch(
					function(response)
					{
						if (response.errors)
						{
							if (action === 'delegate')
							{
								self.vars.delegateReload = false;
							}

							BX.Tasks.alert(response.errors);
							this.togglePanelActivity(true);
						}
					}.bind(this)
				);
			},

			reFetchTaskData: function(code)
			{
				if (code !== 'DELETE' && code !== 'DAYPLAN.ADD') // no need to re-query task data on task delete and add to plan
				{
					var taskId = this.option('taskId');
					var self = this;

					BX.ajax.runComponentAction('bitrix:tasks.task', 'get', {
						mode: 'class',
						data: {
							taskId: taskId,
							parameters: {ENTITY_SELECT: ['DAYPLAN']}
						}
					}).then(
						function(response)
						{
							if (code === 'DELEGATE' && self.vars.delegateReload)
							{
								window.location.reload();
							}
							else if(code === 'DEFER')
							{
								this.getDayPlan().stopTimer(taskId, false); // tell timeman to update widget, no sync
							}

							this.updateTaskData(response.data.DATA);
							this.updatePlanner(); // currently planner has no ability to catch task change by itself, so we have to TELL him manually
							this.togglePanelActivity(true);
						}.bind(this)
					).catch(
						function(response)
						{
							if (response.errors)
							{
								BX.Tasks.alert(response.errors);
								this.togglePanelActivity(true);
							}
						}.bind(this)
					);
				}
				else
				{
					if (code === 'DAYPLAN.ADD')
					{
						this.updateTaskData({ACTION: {'DAYPLAN.ADD': false}});
					}

					this.togglePanelActivity(true);
				}
			},

			updateTaskData: function(data)
			{
				BX.PopupMenu.destroy(this.code());

				if(data.ACTION)
				{
					BX.mergeEx(this.vars.can, data.ACTION);
				}
				BX.mergeEx(this.vars.data, data);

				this.updateButtons();

				var eventTaskData = BX.clone(this.vars.data);
				eventTaskData.ID = this.option('taskId');

				BX.Tasks.Util.fireGlobalTaskEvent('UPDATE', eventTaskData, {STAY_AT_PAGE: true});
			},

			updateButtons: function()
			{
				var can = this.vars.can;
				var data = this.vars.data;

				if (data.STATUS == 4)
				{
					can.COMPLETE = false;
				}

				var map = {
					'timer-start': can['DAYPLAN.TIMER.TOGGLE'] && !data.TIMER_IS_RUNNING_FOR_CURRENT_USER,
					'timer-pause': can['DAYPLAN.TIMER.TOGGLE'] && data.TIMER_IS_RUNNING_FOR_CURRENT_USER,
					'timer-running': data.TIMER_IS_RUNNING_FOR_CURRENT_USER,
					'timer-visible': can['DAYPLAN.TIMER.TOGGLE'],
					'pause': !can['DAYPLAN.TIMER.TOGGLE'] && can.PAUSE,
					'start': !can['DAYPLAN.TIMER.TOGGLE'] && can.START,
					'complete': can.COMPLETE,
					'approve': can.APPROVE,
					'disapprove': can.DISAPPROVE,
					'edit': can.EDIT && !this.vars.publicMode,
					'more-button': !this.vars.publicMode || can.RENEW
				};

				this.toggleCSSMap(map);
			},

			onTaskTimerTick: function(taskId, time, partialData)
			{
				if(taskId != this.option('taskId'))
				{
					return;
				}

				if(this.vars.overtime == null)
				{
					var estimate = parseInt(this.vars.data.TIME_ESTIMATE);
					if(!isNaN(estimate) && estimate && time > estimate)
					{
						this.vars.overtime = true;
						this.changeCSSFlag('timer-overtime', this.vars.overtime);
					}
				}

				this.control('time-elapsed').innerHTML = BX.Tasks.Util.formatTimeAmount(time);
			},

			onTaskTimerToggled: function(taskId, way, partialData)
			{
				if(taskId != this.option('taskId'))
				{
					return;
				}

				this.addToData(partialData);
				this.reFetchTaskData('START_TIMER');
				this.updateButtons();
			},

			onTaskPlanToggled: function(taskId, way)
			{
				if(taskId != this.option('taskId'))
				{
					return;
				}

				this.updateTaskData({ACTION: {'DAYPLAN.ADD': true}});
			},

			showTimerConfirm: function(taskId, previousTask)
			{
				if(taskId != this.option('taskId'))
				{
					return;
				}

				previousTask = previousTask || {};

				var body = BX.message('TASKS_TASK_CONFIRM_START_TIMER');
				body = body.replace('{{TITLE}}', BX.type.isNotEmptyString(previousTask.title) ? BX.util.htmlspecialchars(previousTask.title) : BX.message('TASKS_UNKNOWN'));

				BX.Tasks.confirm(body, BX.delegate(function(result){
					if(result)
					{
						this.doDynamicAction('START_TIMER', {force: true});
					}
				}, this), {title: BX.message('TASKS_TASK_CONFIRM_START_TIMER_TITLE')});
			},

			togglePanelActivity: function(way)
			{
				this.changeCSSFlag('inactive', !way);
				this.vars.buttonActionEnabled = way;
			},

			// todo: move this function to "task view" later, use as a project-wide client-side implementation of php tasksRenderJSON()
			getTaskMenu: function()
			{
				var menu = [];
				var can = this.vars.can;
				if (this.vars.publicMode)
				{
					if (can.RENEW)
					{
						menu.push({
							code: 'RENEW',
							text: BX.message('TASKS_RENEW_TASK'),
							title: BX.message('TASKS_RENEW_TASK'),
							className: 'menu-popup-item-reopen',
							onclick: this.passCtx(this.doMenuAction)
						});
					}

					return menu;
				}

				menu.push(
					{
						code: 'COPY',
						text: BX.message('TASKS_COPY_TASK'),
						title: BX.message('TASKS_COPY_TASK_EX'),
						className: 'menu-popup-item-copy',
						onclick: this.passCtx(this.doMenuAction)
					},
					{
						code: 'CREATE_SUBTASK',
						text: BX.message('TASKS_ADD_SUBTASK'),
						title: BX.message('TASKS_ADD_SUBTASK'),
						className: 'menu-popup-item-create',
						onclick: this.passCtx(this.doMenuAction)
					}
				);

				if (can['DAYPLAN.ADD'])
				{
					menu.push({
						code: 'DAYPLAN.ADD',
						text: BX.message('TASKS_ADD_TASK_TO_TIMEMAN'),
						title: BX.message('TASKS_ADD_TASK_TO_TIMEMAN_EX'),
						className: 'menu-popup-item-add-to-tm',
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				if (can.DELEGATE)
				{
					menu.push({
						code: 'DELEGATE',
						text: BX.message('TASKS_DELEGATE_TASK'),
						title: BX.message('TASKS_DELEGATE_TASK'),
						className: 'menu-popup-item-delegate' + (this.option('taskLimitExceeded') ? ' tasks-tariff-lock' : ''),
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				if (can.DEFER)
				{
					menu.push({
						code: 'DEFER',
						text: BX.message('TASKS_DEFER_TASK'),
						title: BX.message('TASKS_DEFER_TASK'),
						className: 'menu-popup-item-hold',
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				if (can.RENEW)
				{
					menu.push({
						code: 'RENEW',
						text: BX.message('TASKS_RENEW_TASK'),
						title: BX.message('TASKS_RENEW_TASK'),
						className: 'menu-popup-item-reopen',
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				if (can.REMOVE)
				{
					menu.push({
						code: 'DELETE',
						text: BX.message('TASKS_DELETE_TASK'),
						title: BX.message('TASKS_DELETE_TASK'),
						className: 'menu-popup-item-delete',
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				var addTabs = this.option('additional_tabs');
				if (addTabs.length > 0)
				{
					var items = [];

					for (var i = 0; i < addTabs.length; i++)
					{
						var item = addTabs[i];

						var options = {};

						if (item.LOADER && item.LOADER.componentData)
						{
							options = {
								options: {
									requestMethod: 'POST',
									requestParams: item.LOADER.componentData.params
								}
							};
						}

						items.push({
							code: 'REST',
							text: item.NAME,
							title: item.NAME,
							callbackData: item.ONCLICK,

							onclick: (item.ONCLICK)
						});
					}

					menu.push({
						code: '',
						text: BX.message('TASKS_REST_BUTTON_TITLE_2'),
						title: BX.message('TASKS_REST_BUTTON_TITLE_2'),
						className: 'menu-popup-item-',
						items: items
					});
				}

				return menu;
			},

			addToData: function(delta)
			{
				this.vars.overtime = null;

				BX.mergeEx(this.vars.data, delta || {});
			},

			updatePlanner: function()
			{
				this.getDayPlan().updatePlanner();
			}
		}
	});

}).call(this);