BX.namespace('Tasks.Component');

(function() {

	if(typeof BX.Tasks.Component.TaskDetailPartsButtons != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TaskDetailPartsButtons = BX.Tasks.Util.Widget.extend({
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
					this.getTaskMenu()
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

			getQuery: function()
			{
				if(!this.instances.query)
				{
					this.instances.query = new BX.Tasks.Util.Query({
						autoExec: true
					});
				}

				return this.instances.query;
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
						query: this.getQuery(),
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

			getUserSelector: function()
			{
				if(!this.instances.selector)
				{
					this.instances.selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
						scope: this.control('open-menu'),
						mode: 'user',
						query: this.getQuery(),
						useSearch: true,
						parent: this
					});
					this.instances.selector.bindEvent('item-selected', BX.delegate(this.onSelectorItemSelected, this));
				}

				return this.instances.selector;
			},

			onSelectorItemSelected: function(item)
			{
				this.instances.selector.close();
				if(item.id)
				{
					this.doDynamicAction('DELEGATE', {userId: item.id});
				}
			},

			doMenuAction: function(menu, e, item)
			{
				var code = item.code;
				if(code)
				{
					if(code == 'COPY')
					{
						window.location = this.option('copyUrl');
					}
					else if(code == 'CREATE_SUBTASK')
					{
						window.location = this.option('createSubtaskUrl');
					}
					else
					{
						// do one of the dynamic actions
						if(code == 'DELETE')
						{
							if(confirm(BX.message('TASKS_DELETE_CONFIRM')))
							{
								this.doDynamicAction(code);
							}
						}
						else if(code == 'DELEGATE')
						{
							this.getUserSelector().open();
						}
						else
						{
							this.doDynamicAction(code);
						}
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
				}
				else
				{
					this.doDynamicTaskAction(code, args);
				}

				this.reFetchTaskData(code);
			},

			doDynamicTaskAction: function(code, args)
			{
				var taskId = this.option('taskId');

				args = args || {};
				args['id'] = taskId;

				// add action
				this.getQuery().add('task.'+code.toLowerCase(), args, {}, BX.delegate(function(errors, data){

					if(!errors.checkHasErrors())
					{
						if(data.OPERATION == 'task.delete')
						{
							BX.Tasks.Util.fireGlobalTaskEvent('DELETE', {ID: taskId});

							if(this.option('goToListOnDelete'))
							{
								window.location = this.option('listUrl');
							}
						}
					}
				}, this));
			},

			reFetchTaskData: function(code)
			{
				if(code != 'DELETE' && code != 'DAYPLAN.ADD') // no need to re-query task data on task delete and add to plan
				{
					var taskId = this.option('taskId');

					this.getQuery().add('task.get', {id: taskId, parameters: {ENTITY_SELECT: ['DAYPLAN']}}, {code: 'task_data'}, BX.delegate(function(errors, data){

						if(!errors.checkHasErrors())
						{
							if(code == 'DELEGATE')
							{
								// on DELEGATE we usually loose significant part of rights
								// it forces us to redraw large parts of user interface
								// todo: get rid of page reload here, do js-based redraw

								window.location.reload();
							}
							else if(code == 'DEFER')
							{
								this.getDayPlan().stopTimer(taskId, false); // tell timeman to update widget, no sync
							}

							this.updateTaskData(data.RESULT.DATA);
							this.updatePlanner(); // currently planner has no ability to catch task change by itself, so we have to TELL him manually
							this.togglePanelActivity(true);
						}

					}, this));
				}
				else
				{
					if(code == 'DAYPLAN.ADD')
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

				if(can['DAYPLAN.ADD'])
				{
					menu.push({
						code: 'DAYPLAN.ADD',
						text: BX.message('TASKS_ADD_TASK_TO_TIMEMAN'),
						title: BX.message('TASKS_ADD_TASK_TO_TIMEMAN_EX'),
						className: 'menu-popup-item-add-to-tm',
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				if(can.DELEGATE)
				{
					menu.push({
						code: 'DELEGATE',
						text: BX.message('TASKS_DELEGATE_TASK'),
						title: BX.message('TASKS_DELEGATE_TASK'),
						className: 'menu-popup-item-delegate',
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				if(can.DEFER)
				{
					menu.push({
						code: 'DEFER',
						text: BX.message('TASKS_DEFER_TASK'),
						title: BX.message('TASKS_DEFER_TASK'),
						className: 'menu-popup-item-hold',
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				if(can.RENEW)
				{
					menu.push({
						code: 'RENEW',
						text: BX.message('TASKS_RENEW_TASK'),
						title: BX.message('TASKS_RENEW_TASK'),
						className: 'menu-popup-item-reopen',
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				if(can.REMOVE)
				{
					menu.push({
						code: 'DELETE',
						text: BX.message('TASKS_DELETE_TASK'),
						title: BX.message('TASKS_DELETE_TASK'),
						className: 'menu-popup-item-delete',
						onclick: this.passCtx(this.doMenuAction)
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

