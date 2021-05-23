BX.namespace('BX.Tasks.Grid');

BX.Tasks.GridActions = {
    gridId: null,
	groupSelector: null,
	registeredTimerNodes: {},

	initPopupBaloon: function(mode, searchField, groupIdField) {
    	
        this.groupSelector = null;

		BX.bind(BX(searchField + '_control'), 'click', BX.delegate(function(){

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

    action: function (code, taskId, args) {
        switch (code) {
            default:
                this.doAction(code, taskId, args);
                break;
			case 'add2Timeman':
				if(BX.addTaskToPlanner)
					BX.addTaskToPlanner(taskId);
				else if(window.top.BX.addTaskToPlanner)
					window.top.BX.addTaskToPlanner(taskId);
				break;
            case 'delete':
                BX.Tasks.confirmDelete(BX.message('TASKS_COMMON_TASK_ALT_A')).then(function () {
                    this.doAction(code, taskId, args);
                }.bind(this));
                break;
        }
    },
    confirmGroupAction: function (gridId) {
        BX.Tasks.confirm(BX.message('TASKS_CONFIRM_GROUP_ACTION')).then(function () {
            BX.Main.gridManager.getById(gridId).instance.sendSelected()
        }.bind(this));
    },

    doAction: function (code, taskId, args) {
        args = args || {};
        args['id'] = taskId;

        // add action
        this.getQuery().add('task.' + code.toLowerCase(), args, {}, BX.delegate(function (errors, data) {

            if (!errors.checkHasErrors()) {
                if (data.OPERATION == 'task.delete') {
                    BX.Tasks.Util.fireGlobalTaskEvent('DELETE', {ID: taskId});
                }

                if (!this.gridId) {
                    window.location.href = window.location.href;
                    return;
                }

                this.reloadRow(taskId);
            }
        }, this));

    },
    reloadGrid: function()
    {
		if (BX.Bitrix24 && BX.Bitrix24.Slider && BX.Bitrix24.Slider.getLastOpenPage())
		{
			BX.Bitrix24.Slider.destroy(
				BX.Bitrix24.Slider.getLastOpenPage().getUrl()
			);
		}

		var reloadParams = { apply_filter: 'Y', clear_nav: 'Y' };
		var gridObject = BX.Main.gridManager.getById(this.gridId);
		if (gridObject.hasOwnProperty('instance'))
		{
			gridObject.instance.reloadTable('POST', reloadParams);
		}
	},
    reloadRow: function(taskId)
    {
        reloadParams = {apply_filter: 'Y', clear_nav: 'Y'};
        gridObject = BX.Main.gridManager.getById(this.gridId);
        if (gridObject.hasOwnProperty('instance'))
            gridObject.instance.updateRow(taskId.toString());
    },

    getQuery: function () {
        if (!this.query) {
            this.query = new BX.Tasks.Util.Query({
                autoExec: true
            });
        }

        return this.query;
    },
    onDeadlineChangeClick: function (taskId, node, curDeadline) {

        curDeadline = curDeadline || (new Date).getDate();

        BX.calendar({
            node: node,
            value: curDeadline,
            form: '',
            bTime: true,
            currentTime: Math.round((new Date()) / 1000) - (new Date()).getTimezoneOffset() * 60,
            bHideTimebar: true,
            callback_after: (function (node, taskId) {
                return function (value, bTimeIn) {
                    var bTime = true;

                    if (typeof bTimeIn !== 'undefined')
                        bTime = bTimeIn;

                    BX.CJSTask.batchOperations(
                        [
                            {
                                operation: 'CTaskItem::update()',
                                taskData: {
                                    ID: taskId,
                                    DEADLINE: BX.calendar.ValueToString(value, bTime)
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
                    BX.Tasks.GridActions.reloadRow(taskId);

                };
            })(node, taskId)
        });

    },

    onMarkChangeClick: function (taskId, bindElement, currentValues) {
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

    __onGradePopupClose: function () {
        BX.removeClass(this.bindElement, "task-grade-and-report-selected");
    },

    __onGradePopupChange: function () {
        this.bindElement.className = "task-grade-and-report" + (this.listValue !== "NULL" ? " task-grade-" + this.listItem.className : "") + (this.report ? " task-in-report" : "");
        this.bindElement.title = BX.message("TASKS_MARK") + ": " + this.listItem.name;

        BX.Tasks.GridActions.action('update', this.id, {data: {MARK: this.listValue === "NULL" ? "" : this.listValue}});
    },

	renderTimerItem : function (taskId, timeSpentInLogs, timeEstimate, isRunning, taskTimersTotalValue, canStartTimeTracking)
	{
		var className = 'task-timer-inner';
		var timeSpent = timeSpentInLogs + taskTimersTotalValue;
		var canStartTimeTracking = canStartTimeTracking || false;

		if (isRunning)
			className = className + ' task-timer-play';
		else if (canStartTimeTracking)
			className = className + ' task-timer-pause';
		else
			className = className + ' task-timer-clock';

		if ((timeEstimate > 0) && (timeSpent > timeEstimate))
			className = className + ' task-timer-overdue';

		return (
			BX.create("span", {
				props : {
					id : 'task-timer-block-' + taskId,
					className : "task-timer-block"
				},
				events : {
					click : (function(taskId, canStartTimeTracking){
						return function(){
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
				children : [
					BX.create("span", {
						props : {
							id : 'task-timer-block-inner-' + taskId,
							className : className
						},
						children : [
							BX.create("span", {
								props : {
									className : 'task-timer-icon'
								}
							}),
							BX.create("span", {
								props : {
									id : 'task-timer-block-value-' + taskId,
									className : 'task-timer-time'
								},
								text : BX.Tasks.GridActions.renderTimerTimes(timeSpent, timeEstimate, isRunning)
							})
						]
					})
				]
			})
		);
	},

	renderTimerTimes : function(timeSpent, timeEstimate, isRunning)
	{
		var str = '';
		var bShowSeconds = false;

		if (isRunning)
			bShowSeconds = true;

		str = BX.Tasks.GridActions.renderSecondsToHHMMSS(timeSpent, bShowSeconds);

		if (timeEstimate > 0)
			str = str + ' / ' + BX.Tasks.GridActions.renderSecondsToHHMMSS(timeEstimate, false);

		return (str);
	},

	renderSecondsToHHMMSS : function(totalSeconds, bShowSeconds)
	{
		var pad = '00';
		var hours = '' + Math.floor(totalSeconds / 3600);
		var minutes = '' + (Math.floor(totalSeconds / 60) % 60);
		var seconds = 0;
		var result = '';

		result = pad.substring(0, 2 - hours.length) + hours
			+ ':' + pad.substring(0, 2 - minutes.length) + minutes;

		if (bShowSeconds)
		{
			seconds = '' + totalSeconds % 60;
			result = result + ':' + pad.substring(0, 2 - seconds.length) + seconds;
		}

		return (result);
	},

	redrawTimerNode : function (taskId, timeSpentInLogs, timeEstimate, isRunning, taskTimersTotalValue, canStartTimeTracking)
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
			taskTimerBlock.parentNode.replaceChild(
				newTaskTimerBlock,
				taskTimerBlock
			);
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

	removeTimerNode : function (taskId)
	{
		var taskTimerBlock = BX('task-timer-block-' + taskId);

		if (this.registeredTimerNodes[taskId])
			BX.removeCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);

		if (taskTimerBlock)
			taskTimerBlock.parentNode.removeChild(taskTimerBlock);
	},

	__getTimerChangeCallback : function(selfTaskId)
	{
		var state = null;

		return function(params)
		{
			var switchStateTo   = null;
			var innerTimerBlock = null;

			if (params.action === 'refresh_daemon_event')
			{
				if (params.taskId !== selfTaskId)
				{
					if (state === 'paused')
						return;
					else
						switchStateTo = 'paused';
				}
				else
				{
					if (state !== 'playing')
						switchStateTo = 'playing';

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
					(selfTaskId == params.taskId)
					&& params.timerData
					&& (selfTaskId == params.timerData.TASK_ID)
				)
				{
					switchStateTo = 'playing';
				}
				else
					switchStateTo = 'paused';	// other task timer started, so we need to be paused
			}
			else if (params.action === 'stop_timer')
			{
				if (selfTaskId == params.taskId)
					switchStateTo = 'paused';
			}
			else if (params.action === 'init_timer_data')
			{
				if (params.data.TIMER)
				{
					if (params.data.TIMER.TASK_ID == selfTaskId)
					{
						if (params.data.TIMER.TIMER_STARTED_AT > 0)
							switchStateTo = 'playing';
						else
							switchStateTo = 'paused';
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

				if (
					innerTimerBlock
					&& ( ! BX.hasClass(innerTimerBlock, 'task-timer-clock') )
				)
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

(function() {
	"use strict";

	BX.addCustomEvent("tasksTaskEvent", BX.delegate(function(type, data)
	{
		// BX.Tasks.GridActions.reloadRow(data.task.ID);
		BX.Tasks.GridActions.reloadGrid();
	}, this));

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

})();