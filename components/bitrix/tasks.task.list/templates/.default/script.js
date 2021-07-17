BX.namespace('BX.Tasks.Grid');

BX.Tasks.GridActions = {
    gridId: null,
	groupSelector: null,
	registeredTimerNodes: {},
	defaultPresetId: '',
	taskPath: '',
	getTotalCountProceed: false,
	currentGroupAction: null,
	groupId: 0,

	checkCanMove: function()
	{
		return !BX.Tasks.GridInstance || BX.Tasks.GridInstance.checkCanMove();
	},

	initPopupBalloon: function(mode, searchField, groupIdField)
	{
        this.groupSelector = null;

		BX.bind(BX(searchField + '_control'), 'click', BX.delegate(function() {
			if (!this.groupSelector)
			{
				this.groupSelector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
					scope: BX(searchField + '_control'),
					id: 'group-selector-' + this.gridId,
					mode: mode,
					query: false,
					useSearch: true,
					useAdd: false,
					parent: this,
					popupOffsetTop: 5,
					popupOffsetLeft: 40
				});

				this.groupSelector.bindEvent('item-selected', BX.delegate(function(data){
					BX(searchField + '_control').value = BX.util.htmlspecialcharsback(data.nameFormatted) || '';
					BX(groupIdField + '_control').value = data.id || '';
					this.groupSelector.close();
				}, this));
			}
			this.groupSelector.open();
		}, this));
	},

	toggleFilter: function (options, selected)
	{
		var filterManager = BX.Main.filterManager.getById(this.gridId);
		if (!filterManager)
		{
			console.log('BX.Main.filterManager not initialised');
			return;
		}

		if (!selected)
		{
			filterManager.getApi().extendFilter(options);
			return;
		}

		var filterFields = filterManager.getFilterFields();

		Object.keys(options).forEach(function(optionKey) {
			filterFields.forEach(function(field) {
				if (field.getAttribute('data-name') === optionKey)
				{
					filterManager.getFields().deleteField(field);
				}
			});
		});

		filterManager.getSearch().apply();
	},

	filter: function(options)
	{
		var filterManager = BX.Main.filterManager.getById(this.gridId);
		if (!filterManager)
		{
			console.log('BX.Main.filterManager not initialised');
			return;
		}
		var fields = filterManager.getFilterFieldsValues();
		var filterApi = filterManager.getApi();

		Object.keys(options).forEach(function(key) {
			fields[key] = options[key];
		});

		filterApi.setFields(fields);
		filterApi.apply();
	},

	changePin: function(event)
	{
		var eventData = event.getData();
		var button = eventData.button;
		var row = eventData.row;
		var groupId = BX.Tasks.GridActions.groupId;

		if (BX.Dom.hasClass(button, BX.Grid.CellActionState.ACTIVE))
		{
			BX.Tasks.GridActions.action('unpin', row.getId(), {groupId: groupId});

			BX.Dom.removeClass(button, BX.Grid.CellActionState.ACTIVE);
			BX.Dom.addClass(button, BX.Grid.CellActionState.SHOW_BY_HOVER);
		}
		else
		{
			BX.Tasks.GridActions.action('pin', row.getId(), {groupId: groupId});

			BX.Dom.addClass(button, BX.Grid.CellActionState.ACTIVE);
			BX.Dom.removeClass(button, BX.Grid.CellActionState.SHOW_BY_HOVER);
		}
	},

	changeMute: function(event)
	{
		var eventData = event.getData();
		var button = eventData.button;
		var row = eventData.row;

		if (BX.Dom.hasClass(button, BX.Grid.CellActionState.ACTIVE))
		{
			BX.Tasks.GridActions.action('unmute', row.getId());

			BX.Dom.removeClass(button, BX.Grid.CellActionState.ACTIVE);
			BX.Dom.addClass(button, BX.Grid.CellActionState.SHOW_BY_HOVER);
		}
		else
		{
			BX.Tasks.GridActions.action('mute', row.getId());

			BX.Dom.addClass(button, BX.Grid.CellActionState.ACTIVE);
			BX.Dom.removeClass(button, BX.Grid.CellActionState.SHOW_BY_HOVER);
		}
	},

	onCounterClick: function(event)
	{
		var eventData = event.getData();
		var row = eventData.row;

		if (BX.Tasks.GridActions.taskPath)
		{
			BX.SidePanel.Instance.open(BX.Tasks.GridActions.taskPath.replace('#task_id#', row.getId()));
		}
	},

    action: function(code, taskId, args)
	{
		if (code === 'add2Timeman')
		{
			if (BX.addTaskToPlanner)
			{
				BX.addTaskToPlanner(taskId);
			}
			else if (window.top.BX.addTaskToPlanner)
			{
				window.top.BX.addTaskToPlanner(taskId);
			}
		}
		else
		{
			this.doAction(code, taskId, args);
		}
    },

	doAction: function(code, taskId, args)
	{
		args = args || {};
		args['id'] = taskId;

		this.getQuery(code).add('task.' + code.toLowerCase(), args, {}, BX.delegate(function (errors, data) {
			if (!errors.checkHasErrors())
			{
				if (data.OPERATION == 'task.delete')
				{
					BX.Tasks.Util.fireGlobalTaskEvent('DELETE', {ID: taskId});
					BX.UI.Notification.Center.notify({content: BX.message('TASKS_DELETE_SUCCESS')});
				}
				if (!this.gridId)
				{
					window.location.href = window.location.href;
					return;
				}
				if (!this.checkCanMove())
				{
					this.reloadRow(taskId);
				}
			}
		}, this));
	},

	getQuery: function(code)
	{
		var viewType = BX.message('_VIEW_TYPE');
		var url = '/bitrix/components/bitrix/tasks.base/ajax.php?_CODE=' + (code || '') + '&viewType=' + viewType;

		if (!this.query)
		{
			this.query = new BX.Tasks.Util.Query({url: url, autoExec: true});
		}

		return this.query;
	},

	getTotalCount: function(prefix, userId, groupId, parameters)
	{
		if (this.getTotalCountProceed)
		{
			return;
		}
		this.getTotalCountProceed = true;

		var container = document.getElementById(prefix+'_row_count_wrapper');
		this.showCountLoader(container);

		var query = new BX.Tasks.Util.Query({url: '/bitrix/components/bitrix/tasks.task.list/ajax.php'});
		query
			.run(
				'this.getTotalCount',
				{
					userId: userId,
					groupId: groupId,
					parameters: JSON.stringify(parameters)
				}
			)
			.then(function(result) {
				this.hideCountLoader(container);
				if (result.data)
				{
					result.data = (typeof result.data == "number") ? result.data : 0;
					var button = container.querySelector('a');
					if (button)
					{
						button.remove();
					}
					container.append(result.data);
				}
				this.getTotalCountProceed = false;
			}.bind(this))
		query.execute();
	},

	showCountLoader: function(container)
	{
		var button = container.querySelector('a');
		if (button)
		{
			button.style.display = 'none';
		}

		var loader = container.querySelector('.tasks-circle-loader-circular');
		if (loader)
		{
			loader.style.display = 'inline';
		}
	},

	hideCountLoader: function(container)
	{
		var loader = container.querySelector('.tasks-circle-loader-circular');
		if (loader)
		{
			loader.style.display = 'none';
		}
	},

	reloadRow: function(taskId)
	{
		var grid = BX.Main.gridManager.getById(this.gridId);
		if (grid && grid.hasOwnProperty('instance'))
		{
			grid.instance.updateRow(taskId.toString());
		}
	},

	reloadGrid: function()
	{
		if (BX.Bitrix24 && BX.Bitrix24.Slider && BX.Bitrix24.Slider.getLastOpenPage())
		{
			BX.Bitrix24.Slider.destroy(BX.Bitrix24.Slider.getLastOpenPage().getUrl());
		}

		var grid = BX.Main.gridManager.getById(this.gridId);
		if (grid && grid.hasOwnProperty('instance'))
		{
			grid.instance.reloadTable('POST', {apply_filter: 'Y', clear_nav: 'Y'});
		}
	},

	setCurrentGroupAction: function(groupAction)
	{
		this.currentGroupAction = groupAction;
	},

	processBeforeGroupActionSent: function()
	{
		if (this.currentGroupAction === 'ping')
		{
			BX.UI.Notification.Center.notify({content: BX.message('TASKS_LIST_GROUP_ACTION_PING_NOTIFICATION')});
		}
		this.currentGroupAction = null;
	},

    confirmGroupAction: function(gridId)
	{
        BX.Tasks.confirm(BX.message('TASKS_CONFIRM_GROUP_ACTION')).then(function () {
			this.processBeforeGroupActionSent();
			BX.Main.gridManager.getById(gridId).instance.sendSelected();
        }.bind(this));
    },

    onDeadlineChangeClick: function(taskId, node, curDeadline)
	{
        curDeadline = curDeadline || (new Date).getDate();

        var calendar = BX.calendar({
            node: node,
            value: curDeadline,
            form: '',
            bTime: true,
            currentTime: Math.round((new Date()) / 1000) - (new Date()).getTimezoneOffset() * 60,
            bHideTimebar: true,
			bCompatibility: true,
			bCategoryTimeVisibilityOption: 'tasks.bx.calendar.deadline',
			bTimeVisibility: (BX.Tasks.GridInstance ?
				(BX.Tasks.GridInstance.calendarSettings.deadlineTimeVisibility === 'Y') : false),
            callback_after: (function (node, taskId) {
                return function (value) {
                	var currentTime = new Date();
                	if (value.getTime() > currentTime.getTime())
					{
						BX.onCustomEvent('Tasks.Tour.ExpiredTasksDeadlineChange:saveDeadline', []);
					}

                    var path = BX.CJSTask.ajaxUrl;
					BX.CJSTask.ajaxUrl = BX.CJSTask.ajaxUrl + '&_CODE=CHANGE_DEADLINE&viewType=VIEW_MODE_LIST';
                    BX.CJSTask.batchOperations(
                        [
                            {
                                operation: 'CTaskItem::update()',
                                taskData: {
                                    ID: taskId,
                                    DEADLINE: BX.calendar.ValueToString(value, true)
                                }
                            }
                        ],
                        {
                            callbackOnSuccess: (function (node, taskId, value) {
                                return function (reply) {
                                    // if (node.parentNode.parentNode.tagName === 'TD')
                                    //     node.parentNode.parentNode.innerHTML = tasksListNS.renderDeadline(taskId, value, true);
                                    // else
                                    //     node.parentNode.innerHTML = tasksListNS.renderDeadline(taskId, value, true);

                                };
                            })(node, taskId, value)
                        }
                    );
					BX.CJSTask.ajaxUrl = path;
					if (!BX.Tasks.GridActions.checkCanMove())
					{
						BX.Tasks.GridActions.reloadRow(taskId);
					}
                };
            })(node, taskId)
        });

        var guide = BX.Tasks.TourGuideController.getGuide();
        if (guide && guide.setCalendarPopup && guide.isCorrectRow(taskId) && !guide.getIsStopped())
		{
			guide.setCalendarPopup(calendar.popup);
		}
    },

    onMarkChangeClick: function(taskId, bindElement, currentValues)
	{
        BX.TaskGradePopup.show(
            taskId,
            bindElement,
            currentValues,
            {
                events: {
                    onPopupClose: this.__onGradePopupClose,
                    onPopupChange: this.__onGradePopupChange
                }
            }
        );
        BX.addClass(bindElement, "task-grade-and-report-selected");

        return false;
    },

    __onGradePopupClose: function()
	{
        BX.removeClass(this.bindElement, "task-grade-and-report-selected");
    },

    __onGradePopupChange: function()
	{
        this.bindElement.className = "task-grade-and-report"
			+ (this.listValue !== "NULL" ? " task-grade-" + this.listItem.className : "")
			+ (this.report ? " task-in-report" : "");
        this.bindElement.title = BX.message("TASKS_MARK") + ": " + this.listItem.name;

        BX.Tasks.GridActions.action('update', this.id, {data: {
        	MARK: (this.listValue === "NULL" ? "" : this.listValue)
        }});
    },

	renderTimerItem: function(taskId, timeSpentInLogs, timeEstimate, isRunning, taskTimersTotalValue, canStartTimeTracking)
	{
		canStartTimeTracking = canStartTimeTracking || false;

		var className = 'task-timer-inner';
		var timeSpent = timeSpentInLogs + taskTimersTotalValue;

		if (isRunning)
		{
			className = className + ' task-timer-play';
		}
		else if (canStartTimeTracking)
		{
			className = className + ' task-timer-pause';
		}
		else
		{
			className = className + ' task-timer-clock';
		}

		if (timeEstimate > 0 && timeSpent > timeEstimate)
		{
			className = className + ' task-timer-overdue';
		}

		return (
			BX.create("span", {
				props: {
					id: 'task-timer-block-' + taskId,
					className: "task-timer-block"
				},
				events: {
					click: (function(taskId, canStartTimeTracking) {
						return function() {
							if (BX.hasClass(BX('task-timer-block-inner-' + taskId), 'task-timer-play'))
							{
								BX.TasksTimerManager.stop(taskId);
							}
							else if (canStartTimeTracking)
							{
								BX.TasksTimerManager.start(taskId);
							}
						}
					})(taskId, canStartTimeTracking)
				},
				children: [
					BX.create("span", {
						props: {
							id: 'task-timer-block-inner-' + taskId,
							className: className
						},
						children: [
							BX.create("span", {
								props: {
									className: 'task-timer-icon'
								}
							}),
							BX.create("span", {
								props: {
									id: 'task-timer-block-value-' + taskId,
									className: 'task-timer-time'
								},
								text: BX.Tasks.GridActions.renderTimerTimes(timeSpent, timeEstimate, isRunning)
							})
						]
					})
				]
			})
		);
	},

	renderTimerTimes: function(timeSpent, timeEstimate, isRunning)
	{
		var str = '';
		var showSeconds = !!isRunning;

		str = BX.Tasks.GridActions.renderSecondsToHHMMSS(timeSpent, showSeconds);

		if (timeEstimate > 0)
		{
			str = str + ' / ' + BX.Tasks.GridActions.renderSecondsToHHMMSS(timeEstimate, false);
		}

		return str;
	},

	renderSecondsToHHMMSS: function(totalSeconds, showSeconds)
	{
		var pad = '00';
		var hours = '';
		var minutes = '';
		var seconds = 0;

		if (totalSeconds > 0)
		{
			hours += Math.floor(totalSeconds / 3600);
			minutes += Math.floor(totalSeconds / 60) % 60;
		}
		else
		{
			hours += Math.ceil(totalSeconds / 3600);
			minutes += Math.ceil(totalSeconds / 60) % 60;
		}

		var result = pad.substring(0, 2 - hours.length) + hours + ':' + pad.substring(0, 2 - minutes.length) + minutes;

		if (showSeconds)
		{
			seconds = '' + totalSeconds % 60;
			result = result + ':' + pad.substring(0, 2 - seconds.length) + seconds;
		}

		return result;
	},

	redrawTimerNode: function(taskId, timeSpentInLogs, timeEstimate, isRunning, taskTimersTotalValue, canStartTimeTracking)
	{
		var taskTimerBlock = BX('task-timer-block-' + taskId);
		var newTaskTimerBlock = BX.Tasks.GridActions.renderTimerItem(
			taskId,
			timeSpentInLogs,
			timeEstimate,
			isRunning,
			taskTimersTotalValue,
			canStartTimeTracking
		);

		if (taskTimerBlock)
		{
			taskTimerBlock.parentNode.replaceChild(newTaskTimerBlock, taskTimerBlock);
		}
		else
		{
			var container = BX("task-timer-block-container-" + taskId);
			if (container)
			{
				// Unregister callback function for this item (if it exists)
				if (this.registeredTimerNodes[taskId])
				{
					BX.removeCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);
				}

				container.appendChild(newTaskTimerBlock);

				// If row inserted into DOM -> register callback function
				if (BX('task-timer-block-' + taskId))
				{
					this.registeredTimerNodes[taskId] = this.__getTimerChangeCallback(taskId);
					BX.addCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);
				}
			}
		}
	},

	removeTimerNode: function(taskId)
	{
		if (this.registeredTimerNodes[taskId])
		{
			BX.removeCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);
		}

		var taskTimerBlock = BX('task-timer-block-' + taskId);
		if (taskTimerBlock)
		{
			taskTimerBlock.parentNode.removeChild(taskTimerBlock);
		}
	},

	__getTimerChangeCallback: function(selfTaskId)
	{
		var state = null;

		return function(params) {
			var switchStateTo   = null;
			var innerTimerBlock = null;

			if (params.action === 'refresh_daemon_event')
			{
				if (Number(params.taskId) !== Number(selfTaskId))
				{
					if (state === 'paused')
					{
						return;
					}
					switchStateTo = 'paused';
				}
				else
				{
					if (state !== 'playing')
					{
						switchStateTo = 'playing';
					}

					BX.Tasks.GridActions.redrawTimerNode(
						params.taskId,
						params.data.TASK.TIME_SPENT_IN_LOGS,
						params.data.TASK.TIME_ESTIMATE,
						true,	// IS_TASK_TRACKING_NOW
						params.data.TIMER.RUN_TIME,
						true
					);
				}
			}
			else if (params.action === 'start_timer')
			{
				if (
					Number(selfTaskId) === Number(params.taskId)
					&& params.timerData
					&& Number(selfTaskId) === Number(params.timerData.TASK_ID)
				)
				{
					switchStateTo = 'playing';
				}
				else
				{
					switchStateTo = 'paused'; // other task timer started, so we need to be paused
				}
			}
			else if (params.action === 'stop_timer')
			{
				if (Number(selfTaskId) == Number(params.taskId))
				{
					switchStateTo = 'paused';
				}
			}
			else if (params.action === 'init_timer_data')
			{
				if (params.data.TIMER)
				{
					if (Number(params.data.TIMER.TASK_ID) === Number(selfTaskId))
					{
						switchStateTo = (params.data.TIMER.TIMER_STARTED_AT > 0 ? 'playing' : 'paused');
					}
					else if (params.data.TIMER.TASK_ID > 0)
					{
						// our task is not playing now
						switchStateTo = 'paused';
					}
				}
			}

			if (switchStateTo !== null)
			{
				innerTimerBlock = BX('task-timer-block-inner-' + selfTaskId);
				if (innerTimerBlock && !BX.hasClass(innerTimerBlock, 'task-timer-clock'))
				{
					if (switchStateTo === 'paused')
					{
						BX.removeClass(innerTimerBlock, 'task-timer-play');
						BX.addClass(innerTimerBlock, 'task-timer-pause');
					}
					else if (switchStateTo === 'playing')
					{
						BX.removeClass(innerTimerBlock, 'task-timer-pause');
						BX.addClass(innerTimerBlock, 'task-timer-play');
					}
				}

				state = switchStateTo;
			}
		}
	}
};

BX(function() {
	"use strict";

	BX.Tasks.Grid = function(options)
	{
		this.grid = BX.Main.gridManager.getInstanceById(options.gridId);

		this.userId = Number(options.userId);
		this.ownerId = Number(options.ownerId);
		this.groupId = Number(options.groupId);

		this.sorting = options.sorting;
		this.groupByGroups = (options.groupByGroups === 'true');
		this.groupBySubTasks = (options.groupBySubTasks === 'true');
		this.arParams = options.arParams;

		this.calendarSettings = (options.calendarSettings ? options.calendarSettings : {});

		this.taskList = new Map();
		this.comments = new Map();

		this.query = new BX.Tasks.Util.Query({url: '/bitrix/components/bitrix/tasks.task.list/ajax.php'});
		this.actions = {
			taskAdd: 'taskAdd',
			taskUpdate: 'taskUpdate',
			taskRemove: 'taskRemove',
			commentAdd: 'commentAdd',
			pinChanged: 'pinChanged'
		};
		this.classes = {
			highlighted: 'task-list-item-highlighted',
			pinned: 'tasks-list-item-pinned'
		};

		this.isMyList = this.userId === this.ownerId;
		this.canPin = this.isMyList;

		this.updateCanMove();
		this.init(options);
	};

	BX.Tasks.Grid.prototype = {
		init: function(options)
		{
			this.bindEvents();
			this.fillTaskListItems(options.taskList);
			this.colorPinnedRows();

			if (options.arParams && options.arParams['LAZY_LOAD'])
			{
				this.grid.reload();
			}
		},

		bindEvents: function()
		{
			var eventHandlers = {
				comment_add: this.onPullCommentAdd,
				comment_read_all: this.onPullCommentReadAll,
				project_read_all: this.onPullProjectReadAll,
				task_add: this.onPullTaskAdd,
				task_update: this.onPullTaskUpdate,
				task_view: this.onPullTaskView,
				task_remove: this.onPullTaskRemove,
				user_option_changed: this.onUserOptionChanged
			};

			BX.addCustomEvent('onPullEvent-tasks', function(command, params) {
				if (eventHandlers[command])
				{
					eventHandlers[command].apply(this, [params]);
				}
			}.bind(this));

			BX.addCustomEvent('BX.Main.grid:sort', function(column, grid) {
				if (grid === this.grid)
				{
					this.sorting.sort = {};
					this.sorting.sort[column.sort_by] = column.sort_order;
				}
			}.bind(this));

			BX.addCustomEvent('BX.Main.grid:paramsUpdated', function() {
				this.updateCanMove();
				this.colorPinnedRows();
				this.clearTaskListItems();
				this.getRows()
					.map(function(row) {
						return row.getId();
					})
					.filter(function(id) {
						return id !== 'template_0';
					})
					.forEach(function(id) {
						this.addTaskListItem(id);
					}.bind(this))
				;
			}.bind(this));

			BX.addCustomEvent('BX.Tasks.Filter.group', function(grid, groupType, value) {
				if (this.getGrid() === grid)
				{
					this[groupType] = value;
				}
			}.bind(this));
		},

		updateCanMove: function()
		{
			this.canMove = (
				this.isMyList
				&& this.sorting.sort.ACTIVITY_DATE
				&& this.sorting.sort.ACTIVITY_DATE === 'desc'
				&& !this.groupByGroups
				&& !this.groupBySubTasks
			);
		},

		checkCanMove: function()
		{
			return this.canMove;
		},

		colorPinnedRows: function()
		{
			this.getRows().forEach(function(row) {
				var node = row.getNode();
				this.getIsPinned(row.getId())
					? BX.addClass(node, this.classes.pinned)
					: BX.removeClass(node, this.classes.pinned)
				;
			}.bind(this));
		},

		getIsPinned: function(rowId)
		{
			return this.isRowExist(rowId)
				&& this.getRowNodeById(rowId).querySelector('.main-grid-cell-content-action-pin.main-grid-cell-content-action-active')instanceof HTMLElement;
		},

		onPullTaskView: function(data)
		{
			if (this.userId !== Number(data.USER_ID) || !this.isRowExist(data.TASK_ID.toString()))
			{
				return;
			}
			this.updateActivityDateCellForTasks([data.TASK_ID]);
		},

		onPullCommentReadAll: function(data)
		{
			this.onReadAll(data);
		},

		onPullProjectReadAll: function(data)
		{
			this.onReadAll(data);
		},

		onReadAll: function(data)
		{
			if (this.userId !== Number(data.USER_ID))
			{
				return;
			}
			this.updateActivityDateCellForTasks(this.getTaskListItems());
		},

		updateActivityDateCellForTasks: function(taskIds, rowData, parameters)
		{
			rowData = rowData || {};
			parameters = parameters || {};

			var params = {
				taskIds: taskIds,
				data: {},
				arParams: this.arParams
			};
			if (rowData)
			{
				Object.keys(rowData).forEach(function(rowId) {
					params.data[rowId] = rowData[rowId];
				});
			}

			this.query.run('this.prepareGridRowsForTasks', params).then(function(result) {
				if (result.data)
				{
					Object.keys(result.data).forEach(function(taskId) {
						if (this.isRowExist(taskId))
						{
							var row = this.getRowById(taskId);
							var rowData = result.data[taskId];

							if (row.getCellById('ACTIVITY_DATE'))
							{
								row.setCellsContent({ACTIVITY_DATE: rowData.content.ACTIVITY_DATE});
							}
							row.setCellActions(rowData.cellActions);
							row.setCounters(rowData.counters);

							if (parameters.highlightRow === true)
							{
								this.highlightGridRow(taskId);
							}
						}
					}.bind(this));
				}
			}.bind(this));
			this.query.execute();
		},

		onPullCommentAdd: function(data)
		{
			if (this.checkComment(data))
			{
				var xmlId = data.entityXmlId.split('_');
				if (xmlId)
				{
					this.checkTask(xmlId[1], {
						action: this.actions.commentAdd,
						userId: Number(data.ownerId),
						isCompleteComment: data.isCompleteComment
					});
				}
			}
		},

		onPullTaskAdd: function(data)
		{
			if (data.params.addCommentExists === false)
			{
				this.checkTask(data.TASK_ID.toString(), {
					action: this.actions.taskAdd,
					userId: this.ownerId
				});
			}
		},

		onPullTaskUpdate: function(data)
		{
			if (data.params.updateCommentExists === false)
			{
				this.checkTask(data.TASK_ID.toString(), {
					action: this.actions.taskUpdate,
					userId: this.ownerId
				});
			}
			else if (
				data.params.removedParticipants.includes(this.ownerId.toString())
				&& (!this.groupId || this.groupId !== Number(data.AFTER.GROUP_ID))
			)
			{
				this.removeItem(data.TASK_ID.toString());
			}
		},

		onPullTaskRemove: function(data)
		{
			if (this.checkCanMove())
			{
				this.removeItem(data.TASK_ID.toString());
			}
		},

		onUserOptionChanged: function(data)
		{
			if (!this.checkCanMove() || this.userId !== Number(data.USER_ID))
			{
				return;
			}

			var taskId = data.TASK_ID.toString();

			switch (Number(data.OPTION))
			{
				case 1:
					this.updateActivityDateCellForTasks([taskId]);
					break;

				case 2:
				case 3:
					if (this.canPin)
					{
						this.placeToNearTasks(taskId, null, {action: this.actions.pinChanged});
					}
					break;

				default:
					break;
			}
		},

		placeToNearTasks: function(taskId, taskData, parameters)
		{
			var queryParams = {
				taskId: taskId,
				navigation: {
					pageNumber: this.getPageNumber(),
					pageSize: this.getPageSize()
				},
				arParams: this.arParams
			};

			this.query.run('this.getNearTasks', queryParams).then(function(result) {
				if (result.data)
				{
					var before = result.data.before;
					var after = result.data.after;

					if ((before && this.isRowExist(before)) || (after && this.isRowExist(after)))
					{
						var params = {
							before: before,
							after: after
						};
						Object.keys(parameters).forEach(function(key) {
							params[key] = parameters[key];
						});
						this.updateItem(taskId, taskData, params);
					}
					else
					{
						this.removeItem(taskId);
					}
				}
			}.bind(this));
			this.query.execute();
		},

		checkComment: function(data)
		{
			var xmlId = data.entityXmlId.split('_');
			if (!xmlId)
			{
				return false;
			}

			var entityType = xmlId[0];
			var taskId = xmlId[1];

			if (entityType !== 'TASK')
			{
				return false;
			}

			if (!this.comments.has(taskId))
			{
				this.comments.set(taskId, new Set());
			}

			var taskComments = this.comments.get(taskId);
			var messageId = data.messageId;
			var participants = data.participants.map(function(id) {
				return id.toString();
			})

			if (taskComments.has(messageId))
			{
				return false;
			}

			taskComments.add(messageId);

			return (participants.includes(this.userId.toString()) || this.groupId === data.groupId);
		},

		checkTask: function(taskId, parameters)
		{
			parameters = parameters || {};

			var ajaxActionParams = {RETURN_ACCESS: 'Y'};
			if (parameters.isCompleteComment === false || parameters.userId === this.userId)
			{
				ajaxActionParams.SIFT_THROUGH_FILTER = {
					userId: this.ownerId,
					groupId: this.groupId
				};
			}

			BX.ajax.runAction('tasks.task.list', {data: {
				filter: {ID: taskId},
				params: ajaxActionParams
			}}).then(function(response) {
				this.onCheckTaskSuccess(response, taskId, parameters);
			}.bind(this));
		},

		onCheckTaskSuccess: function(response, taskId, parameters)
		{
			if (!response.data.tasks.length)
			{
				this.removeItem(taskId);
				return;
			}

			var taskData = response.data.tasks[0];

			if (this.isRowExist(taskId))
			{
				if (this.checkCanMove())
				{
					parameters.canMoveRow = (parameters.action !== this.actions.taskUpdate);
					this.updateGridRow(taskId, taskData, parameters);
				}
				else
				{
					if (parameters.action === this.actions.commentAdd)
					{
						var rowData = {};
						rowData[taskId] = taskData;
						this.updateActivityDateCellForTasks([taskId], rowData, {highlightRow: true});
					}
					else if (parameters.action === this.actions.taskUpdate)
					{
						this.getGrid().updateRow(taskId);
					}
				}
			}
			else if (this.checkCanMove())
			{
				if (parameters.action === this.actions.taskUpdate)
				{
					this.placeToNearTasks(taskId, taskData, parameters);
				}
				else
				{
					this.updateItem(taskId, taskData, parameters);
				}
			}
		},

		updateItem: function(taskId, rowData, parameters)
		{
			rowData = rowData || null;
			parameters = parameters || {};

			if (!this.hasTaskListItem(taskId))
			{
				this.addTaskListItem(taskId);
				this.addGridRow(taskId, rowData, parameters);
			}
			else
			{
				this.updateGridRow(taskId, rowData, parameters);
			}
		},

		addGridRow: function(rowId, rowData, parameters)
		{
			var params = {
				taskIds: [rowId],
				data: {},
				arParams: this.arParams
			};
			if (rowData)
			{
				params.data[rowId] = rowData;
			}

			this.query.run('this.prepareGridRowsForTasks', params).then(function(result) {
				if (result.data && result.data[rowId])
				{
					var rowData = result.data[rowId];
					var options = {
						id: rowId,
						columns: rowData.content,
						actions: rowData.actions,
						cellActions: rowData.cellActions,
						counters: rowData.counters
					};
					var moveRows = this.getGridMoveRows(rowId, parameters);

					if (moveRows.rowAfter)
					{
						options.insertAfter = moveRows.rowAfter;
					}
					else if (moveRows.rowBefore)
					{
						options.insertBefore = moveRows.rowBefore;
					}
					else
					{
						options.append = true;
					}

					if (this.taskList.size > this.getPageNumber() * this.getPageSize())
					{
						var lastRowId = this.getLastRowId();

						this.removeTaskListItem(lastRowId);
						BX.remove(this.getRowNodeById(lastRowId));
						this.showMoreButton();
					}

					this.hideStub();
					this.getRealtime().addRow(options);
					this.colorPinnedRows();
				}
			}.bind(this));
			this.query.execute();
		},

		updateGridRow: function(rowId, rowData, parameters)
		{
			if (!this.isRowExist(rowId))
			{
				return;
			}

			var params = {
				taskIds: [rowId],
				data: {},
				arParams: Object.assign(this.arParams, parameters.arParams || {})
			};
			if (rowData)
			{
				params.data[rowId] = rowData;
			}

			this.query.run('this.prepareGridRowsForTasks', params).then(function(result) {
				if (result.data && result.data[rowId])
				{
					var rowData = result.data[rowId];
					var row = this.getRowById(rowId);
					row.setCellsContent(rowData.content);
					row.setActions(rowData.actions);
					row.setCellActions(rowData.cellActions);
					row.setCounters(rowData.counters);

					if (parameters.canMoveRow !== false)
					{
						this.getGrid().getRows().reset();

						var moveRows = this.getGridMoveRows(rowId, parameters);
						this.moveRow(rowId, moveRows.rowAfter, moveRows.rowBefore);
					}
					this.highlightGridRow(rowId).then(function() {
						this.colorPinnedRows();
					}.bind(this));

					this.getGrid().bindOnRowEvents();
				}
			}.bind(this));
			this.query.execute();
		},

		removeItem: function(id)
		{
			if (!this.isRowExist(id))
			{
				return;
			}

			this.removeTaskListItem(id);
			this.getGrid().removeRow(id);
		},

		getGridMoveRows: function(rowId, parameters)
		{
			var rowBefore;
			var rowAfter;

			switch (parameters.action)
			{
				case this.actions.pinChanged:
				case this.actions.taskUpdate:
					rowBefore = parameters.before;
					rowAfter = parameters.after;
					break;

				default:
					rowBefore = this.getFirstRowId();
					rowAfter = (this.getIsPinned(rowId) ? 0 : this.getLastPinnedRowId());
					break;
			}

			return {
				rowBefore: rowBefore,
				rowAfter: rowAfter
			};
		},

		moveRow: function(rowId, after, before)
		{
			if (after)
			{
				this.getGrid().getRows().insertAfter(rowId, after);
			}
			else if (before)
			{
				this.getGrid().getRows().insertBefore(rowId, before);
			}
		},

		getLastPinnedRowId: function()
		{
			var pinnedRows = Object.values(this.getRows()).filter(function(row) {
				return this.getIsPinned(row.getId());
			}.bind(this));
			var keys = Object.keys(pinnedRows);

			if (keys.length > 0)
			{
				return pinnedRows[keys[keys.length - 1]].getId();
			}

			return 0;
		},

		getFirstRowId: function()
		{
			var rows = this.getRows();
			return (rows.length ? this.getRowProp(rows[0], 'id') : 0);
		},

		getLastRowId: function()
		{
			var lastRow = this.getGrid().getRows().getBodyLastChild();
			return (lastRow ? this.getRowProp(lastRow, 'id') : 0);
		},

		highlightGridRow: function(rowId)
		{
			var promise = new BX.Promise();

			if (!this.isRowExist(rowId))
			{
				promise.reject();
				return promise;
			}

			var node = this.getRowNodeById(rowId);
			var isPinned = BX.hasClass(node, this.classes.pinned);

			if (isPinned)
			{
				BX.removeClass(node, this.classes.pinned);
			}

			BX.addClass(node, this.classes.highlighted);
			setTimeout(function() {
				BX.removeClass(node, this.classes.highlighted);
				if (isPinned)
				{
					BX.addClass(node, this.classes.pinned);
				}
				promise.fulfill();
			}.bind(this), 900);

			return promise;
		},

		getGrid: function()
		{
			return this.grid;
		},

		getRows: function()
		{
			return this.getGrid().getRows().getBodyChild();
		},

		isRowExist: function(id)
		{
			return this.getRowById(id) !== null;
		},

		getRowById: function(id)
		{
			return this.getGrid().getRows().getById(id);
		},

		getRowNodeById: function(id)
		{
			return this.getRowById(id).getNode();
		},

		getRowProp: function(row, propName)
		{
			return BX.data(row.getNode(), propName);
		},

		setRowProp: function(row, propName, propValue)
		{
			row.getNode().setAttribute('data-' + propName, propValue);
		},

		getPageNumber: function()
		{
			var pageNumber = 1;
			var navPanel = this.getGrid().getContainer().querySelector('.main-grid-nav-panel');
			if (navPanel)
			{
				var pagination = navPanel.querySelector('.main-ui-pagination');
				if (pagination)
				{
					var activePagination = pagination.querySelector('.main-ui-pagination-active');
					if (activePagination)
					{
						pageNumber = activePagination.innerText;
					}
				}
			}

			return pageNumber;
		},

		getPageSize: function()
		{
			var pageSize = 50;
			var selector = BX(this.getGrid().getContainerId() + '_' + this.getGrid().settings.get('pageSizeId'));

			if (selector)
			{
				pageSize = BX.data(selector, 'value');
			}

			return pageSize;
		},

		getRealtime: function()
		{
			return this.getGrid().getRealtime();
		},

		showStub: function()
		{
			if (this.stub)
			{
				this.getRealtime().showStub({content: this.stub});
			}
		},

		hideStub: function()
		{
			this.getGrid().hideEmptyStub();
		},

		showMoreButton: function()
		{
			this.getGrid().getMoreButton().getNode().style.display = 'inline-block';
		},

		hideMoreButton: function()
		{
			this.getGrid().getMoreButton().getNode().style.display = 'none';
		},

		getTaskListItems: function()
		{
			return Array.from(this.taskList.keys());
		},

		hasTaskListItem: function(id)
		{
			return this.taskList.has(parseInt(id, 10));
		},

		addTaskListItem: function(id)
		{
			this.taskList.set(parseInt(id, 10));
		},

		removeTaskListItem: function(id)
		{
			this.taskList.delete(parseInt(id, 10));
		},

		fillTaskListItems: function(items)
		{
			Object.keys(items).forEach(function(id) {
				this.addTaskListItem(id);
			}.bind(this));
		},

		clearTaskListItems: function()
		{
			this.taskList.clear();
		}
	};

	BX.addCustomEvent('tasksTaskEvent', BX.delegate(function(type, data) {
		if (!BX.Tasks.GridActions.checkCanMove())
		{
			BX.Tasks.GridActions.reloadGrid();
		}
	}, this));

	BX.addCustomEvent("SidePanel.Slider:onCloseByEsc", function(event) {
		var reg = /tasks\/task\/edit/;
		var str = event.getSlider().getUrl();
		if (reg.test(str) && !confirm(BX.message('TASKS_CLOSE_PAGE_CONFIRM')))
		{
			event.denyAction();
		}
	});

	BX.addCustomEvent('BX.Main.Filter:apply', function(filterId, data, ctx) {
		var stringUrl = window.location.href;
		var url = new URL(stringUrl);
		var state = url.searchParams.get('F_STATE');
		var newUrl = (state === 'sR' ? stringUrl.replace('&F_STATE=sR', '') : stringUrl);

		window.history.replaceState(null, null, newUrl);
	}.bind(this));

	BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId, url) {
		var filterManager = BX.Main.filterManager.getById(BX.Tasks.GridActions.gridId);
		if (!filterManager)
		{
			console.log('BX.Main.filterManager not initialised');
			return;
		}

		var fields = {
			preset_id: BX.Tasks.GridActions.defaultPresetId,
			additional: {ROLEID: (roleId === 'view_all' ? 0 : roleId)}
		};
		var filterApi = filterManager.getApi();
		filterApi.setFilter(fields, {ROLE_TYPE: 'TASKS_ROLE_TYPE_' + (roleId === '' ? 'view_all' : roleId)});

		window.history.pushState(null, null, url);
	});

	BX.addCustomEvent('Tasks.Toolbar:onItem', function(event) {
		var data = event.getData();
		if (data.counter && data.counter.filter)
		{
			data.counter.filter.toggleByField({PROBLEM: data.counter.filterValue});
		}
	});

	BX.Tasks.Grid.Sorting = function(options)
	{
		this.grid = BX.Main.gridManager.getInstanceById(options.gridId);
		this.currentGroupId = options.currentGroupId;
		this.treeMode = options.treeMode;

		BX.message(options.messages);

		this.init();

		BX.addCustomEvent("BX.Main.grid:rowDragStart", this.handleRowDragStart.bind(this));
		BX.addCustomEvent("BX.Main.grid:rowDragMove", this.handleRowDragMove.bind(this));
		BX.addCustomEvent("BX.Main.grid:rowDragEnd", this.handleRowDragEnd.bind(this));
	};

	BX.Tasks.Grid.Sorting.prototype = {

		init: function()
		{
			this.dragRow = null;
			this.targetRow = null;
			this.error = false;

			this.targetTask = null;
			this.before = true;
			this.newGroup = null;
			this.newParentId = null;
		},

		/**
		 *
		 * @returns {BX.Main.grid}
		 */
		getGrid: function()
		{
			return this.grid;
		},

		/**
		 *
		 * @param {Element} node
		 * @return {BX.Grid.Row}
		 */
		getRow: function(node)
		{
			return this.getGrid().getRows().get(node);
		},

		/**
		 *
		 * @param id
		 * @return {BX.Grid.Row}
		 */
		getRowById: function(id)
		{
			return this.getGrid().getRows().getById(id);
		},

		/**
		 *
		 * @returns {BX.Grid.Row[]}
		 */
		getRows: function()
		{
			return this.getGrid().getRows().getBodyChild();
		},

		/**
		 *
		 * @param {BX.Grid.Row} row
		 * @param {string} propName
		 * @return {string}
		 */
		getRowProp: function(row, propName)
		{
			return row.getNode().dataset[propName];
		},

		/**
		 *
		 * @param {BX.Grid.Row} row
		 * @returns {string}
		 */
		getRowType: function(row)
		{
			return this.getRowProp(row, "type");
		},

		/**
		 *
		 * @param {BX.Grid.Row} row
		 * @returns {string}
		 */
		getRowGroupId: function(row)
		{
			return this.getRowProp(row, "groupId");
		},

		/**
		 *
		 * @param {BX.Grid.RowDragEvent} dragEvent
		 * @param {BX.Main.grid} grid
		 */
		handleRowDragStart: function(dragEvent, grid)
		{
			this.dragRow = this.getRow(dragEvent.getDragItem());
		},

		/**
		 *
		 * @param {BX.Grid.RowDragEvent} dragEvent
		 * @param {BX.Main.grid} grid
		 */
		handleRowDragMove: function(dragEvent, grid)
		{
			this.targetRow = this.getRow(dragEvent.getTargetItem());
			var targetType = this.targetRow ? this.getRowType(this.targetRow) : null;

			this.newParentId = null;
			this.error = false;
			var newGroup = null;

			if (targetType === "task")
			{
				var targetParentId = this.targetRow.getParentId();
				if (targetParentId !== this.dragRow.getParentId())
				{
					this.newParentId = targetParentId;
				}

				newGroup = this.getGroupByRow(this.targetRow);

				this.targetTask = this.targetRow;
				this.before = true;
			}
			else
			{
				if (targetType === "group")
				{
					newGroup = this.getPreviousGroup(this.targetRow);
				}
				else
				{
					newGroup = this.getLastGroup();
				}

				var target = this.getClosestTask(this.targetRow);
				this.targetTask = target.task;
				this.before = target.before;
			}

			this.newGroup = newGroup.id !== this.getGroupByRow(this.dragRow).id ? newGroup : null;

			if (targetType === "task" && this.isChildOf(this.targetTask, this.dragRow))
			{
				this.error = true;
			}
			else if (
				this.newGroup &&
				(
					this.getRowProp(this.dragRow, "canEdit") === "false" ||
					!this.newGroup.canCreateTasks
				)
			)
			{
				this.error = true;
			}
			else if (
				this.newParentId !== null &&
				this.getRowProp(this.dragRow, "canEdit") === "false"
			)
			{
				this.error = true;
			}

			this.error ? dragEvent.disallowMove(BX.message("TASKS_ACCESS_DENIED")) : dragEvent.allowMove();
		},

		handleRowDragEnd: function(dragEvent, grid)
		{
			if (!this.error)
			{
				this.save();
			}

			this.init();
		},

		save: function()
		{
			var sourceId = this.dragRow.getId();
			var targetId = this.targetTask ? this.targetTask.getId() : null;

			if (sourceId === targetId)
			{
				return;
			}

			var data = {
				sourceId: sourceId,
				targetId: targetId,
				before: this.before,
				currentGroupId : this.currentGroupId
			};

			if (this.newGroup !== null)
			{
				data.newGroupId = this.newGroup.id;
				this.setGroupId(this.dragRow, data.newGroupId);
			}

			if (this.newParentId !== null && this.treeMode)
			{
				data.newParentId = this.newParentId;
			}

			var query = new BX.Tasks.Util.Query();
			query.run("task.sorting.move", { data: data }).then(function(/*BX.Tasks.Util.Query.Result*/ result)
			{
				if (!result.getErrors().isEmpty())
				{
					BX.Tasks.confirm(
						result.getErrors().getMessages().join(" "),
						function() {
							BX.reload()
						},
						{
							buttonSet: []
						}
					);
				}
			});

			query.execute();
		},

		getParentRow: function(row)
		{
			return this.getRowById(row.getParentId());
		},

		/**
		 *
		 * @param {BX.Grid.Row} child
		 * @param {BX.Grid.Row} parent
		 * @return {Boolean}
		 */
		isChildOf: function(child, parent)
		{
			var parentTask = this.getParentRow(child);
			while (parentTask !== null)
			{
				if (parentTask === parent)
				{
					return true;
				}

				parentTask = this.getParentRow(parentTask);
			}

			return false;
		},

		getGroupById: function(groupId)
		{
			var rows = this.getRows();

			for (var i = 0; i < rows.length; i++)
			{
				var row = rows[i];
				if (this.getRowType(row) === "group" && this.getRowGroupId(row) === String(groupId))
				{
					return this.getGroupByRow(row);
				}
			}

			return this.getDefaultProject();
		},

		setGroupId: function(row, groupId)
		{
			row.getDataset().groupId = groupId;

			var children = row.getChildren();
			for (var i = 0; i < children.length; i++)
			{
				this.setGroupId(children[i], groupId);

			}
		},

		getDefaultProject: function()
		{
			return {
				id: "0",
				canCreateTasks: true
			}
		},

		/**
		 *
		 * @param {BX.Grid.Row} row
		 */
		getGroupByRow: function(row)
		{
			if (this.getRowType(row) === "group")
			{
				return {
					id: this.getRowGroupId(row),
					canCreateTasks: this.getRowProp(row, "canCreateTasks") === "true"
				};
			}
			else
			{
				return this.getGroupById(this.getRowGroupId(row));
			}
		},

		getLastGroup: function()
		{
			var group = null;
			var rows = this.getRows();

			for (var i = rows.length - 1; i >= 0; i--)
			{
				var row = rows[i];
				if (this.getRowType(row) === "group")
				{
					return this.getGroupByRow(row);
				}
			}

			return this.getDefaultProject();
		},

		getPreviousGroup: function(currentGroup)
		{
			var group = null;
			var rows = this.getRows();
			var found = false;

			for (var i = rows.length - 1; i >= 0; i--)
			{
				var row = rows[i];
				if (currentGroup === row)
				{
					found = true;
					continue;
				}

				if (found && this.getRowType(row) === "group")
				{
					return this.getGroupByRow(row);
				}
			}

			return this.getDefaultProject();
		},

		getClosestTask: function(currentRow)
		{
			var rows = this.getRows();
			var index = currentRow ? currentRow.getIndex() - 1 : rows.length;

			for (var i = index - 1; i >= 0; i--)
			{
				if (this.getRowType(rows[i]) === "task" && rows[i].getDepth() === "0")
				{
					return {
						task: rows[i],
						before: false
					};
				}
			}

			for (i = index + 1; i < rows.length; i++)
			{
				if (this.getRowType(rows[i]) === "task" && rows[i].getDepth() === "0")
				{
					return {
						task: rows[i],
						before: true
					};
				}
			}

			return {
				task: null,
				before: true
			};
		}
	};

	BX.Tasks.TourGuideController = function(options)
	{
		this.tours = options.tours;
		this.guide = null;

		this.initGuides(options);
	}

	BX.Tasks.TourGuideController.prototype = {
		initGuides: function(options)
		{
			var firstGridTaskCreation = this.tours.firstGridTaskCreation;
			var expiredTasksDeadlineChange = this.tours.expiredTasksDeadlineChange;

			if (firstGridTaskCreation.show)
			{
				this.guide = new BX.Tasks.TourGuideController.FirstGridTaskCreationTourGuide(options);
			}
			else if (expiredTasksDeadlineChange.show || expiredTasksDeadlineChange.backgroundCheck)
			{
				this.guide = new BX.Tasks.TourGuideController.ExpiredTasksDeadlineChangeTourGuide(options);
			}
		},

		getGuide: function()
		{
			return this.guide;
		}
	};

	BX.Tasks.TourGuideController.FirstGridTaskCreationTourGuide = function(options)
	{
		this.tour = options.tours.firstGridTaskCreation;
		this.popupData = this.tour.popupData;

		this.start();
	};

	BX.Tasks.TourGuideController.FirstGridTaskCreationTourGuide.prototype = {
		start: function()
		{
			var addButton = BX('tasks-buttonAdd');
			if (!addButton)
			{
				return;
			}
			addButton.href = BX.Uri.addParam(addButton.href, {FIRST_GRID_TASK_CREATION_TOUR_GUIDE: 'Y'});

			this.guide = new BX.UI.Tour.Guide({
				steps: [
					{
						target: addButton,
						title: this.popupData[0].title,
						text: this.popupData[0].text
					}
				],
				onEvents: true
			});

			BX.addCustomEvent('UI.Tour.Guide:onFinish', function(event) {
				if (event.getData().guide === this.guide)
				{
					addButton.href = BX.Uri.removeParam(addButton.href, ['FIRST_GRID_TASK_CREATION_TOUR_GUIDE']);
				}
			}.bind(this));

			this.showNextStep();
		},

		showNextStep: function()
		{
			setTimeout(function() {
				this.guide.showNextStep();
			}.bind(this), 500);
		},
	};

	BX.Tasks.TourGuideController.ExpiredTasksDeadlineChangeTourGuide = function(options)
	{
		this.userId = options.userId;
		this.tour = options.tours.expiredTasksDeadlineChange;
		this.popupData = this.tour.popupData
		this.counterToCheck = this.tour.counterToCheck;

		this.rowId = 0;
		this.calendarPopup = 0;
		this.isStopped = false;
		this.viewMode = 'grid';
		this.ajaxActionPrefix = 'tasks.tourguide.expiredtasksdeadlinechange.';

		this.grid = BX.Main.gridManager.getInstanceById(options.gridId);

		if (this.tour.show)
		{
			this.start();
		}
		else if (this.tour.backgroundCheck)
		{
			this.isPullListening = true;
		}

		this.bindEvents();
	}

	BX.Tasks.TourGuideController.ExpiredTasksDeadlineChangeTourGuide.prototype = {
		bindEvents: function()
		{
			var eventHandlers = {
				user_counter: this.onUserCounter.bind(this),
			};
			BX.addCustomEvent('onPullEvent-tasks', function(command, params) {
				if (eventHandlers[command])
				{
					eventHandlers[command].apply(this, [params]);
				}
			}.bind(this));

			BX.addCustomEvent('UI.Tour.Guide:onPopupClose', function() {
				this.stop();
			}.bind(this));

			BX.addCustomEvent('UI.Tour.Guide:onFinish', function(event) {
				if (event.getData().guide === this.guide && this.getCurrentStepIndex() === 0)
				{
					BX.addCustomEvent('BX.Main.grid:paramsUpdated', BX.proxy(this.onExpiredCounterGridReloaded, this));
				}
			}.bind(this));
		},

		onUserCounter: function(data)
		{
			if (!this.isPullListening || this.userId !== Number(data.userId))
			{
				return;
			}

			var newCounter = Number(data[0].view_role_originator.expired) + Number(data[0].view_role_responsible.expired);
			if (newCounter >= Number(this.counterToCheck))
			{
				this.isPullListening = false;

				BX.ajax.runAction(this.ajaxActionPrefix + 'proceed', {
					analyticsLabel: {
						viewMode: this.viewMode
					}
				}).then(function(result) {
					if (result.data)
					{
						this.start();
					}
				}.bind(this));
			}
		},

		start: function()
		{
			this.guide = new BX.UI.Tour.Guide({
				steps: [
					{
						target: document.querySelector('.tasks-counters--item-counter'),
						title: this.popupData[0].title,
						text: this.popupData[0].text
					}
				],
				onEvents: true
			});

			this.showNextStep();
		},

		markShowedStep: function(step)
		{
			BX.ajax.runAction(this.ajaxActionPrefix + 'markShowedStep', {
				analyticsLabel: {
					viewMode: this.viewMode,
					step: step
				}
			});
		},

		onExpiredCounterGridReloaded: function()
		{
			BX.removeCustomEvent('BX.Main.grid:paramsUpdated', BX.proxy(this.onExpiredCounterGridReloaded, this));

			var target = null;
			Object.values(this.grid.getRows().getBodyChild()).forEach(function(row) {
				if (!this.rowId && row.getNode().querySelector('.ui-label.ui-label-danger.ui-label-fill.ui-label-link'))
				{
					this.rowId = row.getId();
					target = row.getNode().querySelector('.ui-label.ui-label-danger.ui-label-fill.ui-label-link');
				}
			}.bind(this));

			if (this.rowId > 0 && target)
			{
				this.guide.steps.push(
					new BX.UI.Tour.Step({
						target: target,
						title: this.popupData[1].title,
						text: this.popupData[1].text
					})
				);
				this.showNextStep();
			}
		},

		setCalendarPopup: function(popup)
		{
			if (!this.calendarPopup && this.getCurrentStepIndex() === 1)
			{
				this.calendarPopup = popup;

				BX.addCustomEvent(this.calendarPopup, 'onPopupAfterClose', BX.proxy(this.onCalendarPopupClose, this));
			}
		},

		onCalendarPopupClose: function()
		{
			BX.removeCustomEvent(this.calendarPopup, 'onPopupAfterClose', BX.proxy(this.onCalendarPopupClose, this));

			setTimeout(function() {
				BX.removeCustomEvent('Tasks.Tour.ExpiredTasksDeadlineChange:saveDeadline', BX.proxy(this.onDeadlineSave, this));
			}.bind(this), 500);

			BX.addCustomEvent('Tasks.Tour.ExpiredTasksDeadlineChange:saveDeadline', BX.proxy(this.onDeadlineSave, this));
		},

		onDeadlineSave: function()
		{
			BX.addCustomEvent('BX.Main.grid:paramsUpdated', BX.proxy(this.onGridReloaded, this));
		},

		onGridReloaded: function()
		{
			BX.removeCustomEvent('BX.Main.grid:paramsUpdated', BX.proxy(this.onGridReloaded, this));

			if (this.grid.getRows().getById(this.rowId) === null)
			{
				this.guide.steps.push(
					new BX.UI.Tour.Step({
						title: this.popupData[2].title,
						text: this.popupData[2].text,
						buttons: [
							{
								text: this.popupData[2].buttons[0],
								event: function() {
									this.stop();
								}.bind(this)
							}
						]
					})
				);
				this.showNextStep();
			}
		},

		showNextStep: function()
		{
			if (this.isStopped)
			{
				return;
			}

			setTimeout(function() {
				this.guide.showNextStep();
				this.markShowedStep(this.getCurrentStepIndex());
			}.bind(this), 500);
		},

		getCurrentStepIndex: function()
		{
			return this.guide.currentStepIndex;
		},

		isCorrectRow: function(rowId)
		{
			return Number(this.rowId) === Number(rowId);
		},

		getIsStopped: function()
		{
			return this.isStopped;
		},

		stop: function()
		{
			this.isStopped = true;
			this.guide.close();
		}
	};
});