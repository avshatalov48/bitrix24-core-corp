var tasksListTemplateDefaultTableViewInit = function() {

	if (BX("task-new-item-responsible"))
	{
		BX.bind(BX("task-new-item-link-group"), "click", function(e) {
			if(!e) e = window.event;

			groupsPopup.show();

			BX.PreventDefault(e);
		});
	}
};

var tasksListNS = {
	isReady : false,
	table : null,
	columnsContextId : null,
	registeredTimerNodes : {},
	columnsMetaData      : [],
	initialColumnsMetaDataHash : null,
	groupActionCheckboxes : null,
	lastCheckboxIndex : null,
	objResizer : null,
	arFilter : null,
	groupActionCheckboxesName : null,
	groupActionSelectAll : null,
	userSelector : null,
	groupSelector : null,
	curUserName : '...',
	menu : null,

	approveTask : function(taskId)
	{
		var row = BX("task-" + taskId);
		if (row)
		{
			SetCSSStatus(row, "completed", "task-status-");
			var cells = row.getElementsByTagName("TD");
			var title = BX.findChild(cells[0], {tagName : "a"}, true);

			BX.style(title, "text-decoration", "line-through");

			cells[2].innerHTML = "&nbsp;";
			var link = BX.findChild(row, {tagName : "a", className : "task-complete-action"}, true);
			if (link)
			{
				link.onclick = null;
				link.title = BX.message("TASKS_FINISHED");
			}
		}
		SetServerStatus(taskId, "approve");
	},

	disapproveTask : function(taskId)
	{
		var row = BX("task-" + taskId);
		if (row)
		{
			SetCSSStatus(row, "accepted", "task-status-");
			var cells = row.getElementsByTagName("TD");
			cells[2].innerHTML = "&nbsp;";
		}
		SetServerStatus(taskId, "disapprove");
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
								BX.TasksTimerManager.stop(taskId);
							else if (canStartTimeTracking)
								BX.TasksTimerManager.start(taskId);
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
								text : tasksListNS.renderTimerTimes(timeSpent, timeEstimate, isRunning)
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

		str = tasksListNS.renderSecondsToHHMMSS(timeSpent, bShowSeconds);

		if (timeEstimate > 0)
			str = str + ' / ' + tasksListNS.renderSecondsToHHMMSS(timeEstimate, false);

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

		var newTaskTimerBlock = tasksListNS.renderTimerItem(
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
			var row = BX("task-" + taskId);
			if (row)
			{
				var a = BX.findChild(row,  {tagName: "a", className: "task-title-link"}, true);

				if (a)
				{
					// Unregister callback function for this item (if it exists)
					if (this.registeredTimerNodes[taskId])
						BX.removeCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);

					a.parentNode.insertBefore(newTaskTimerBlock, null);

					// If row inserted into DOM -> register callback function
					if (BX('task-timer-block-' + taskId))
					{
						this.registeredTimerNodes[taskId] = this.__getTimerChangeCallback(taskId);
						BX.addCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);
					}
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

					tasksListNS.redrawTimerNode(
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
	},

	onDeadlineChangeClick : function(taskId, node, curDeadline)
	{
		BX.calendar({
			node: node, 
			value : curDeadline,
			form: '', 
			bTime: true, 
			currentTime: Math.round((new Date()) / 1000) - (new Date()).getTimezoneOffset()*60, 
			bHideTimebar: false,
			callback_after: (function(node, taskId){
				return function(value, bTimeIn){
					var bTime = true;

					if (typeof bTimeIn !== 'undefined')
						bTime = bTimeIn;

					BX.CJSTask.batchOperations(
						[
							{
								operation : 'CTaskItem::update()',
								taskData  :  {
									ID       : taskId,
									DEADLINE : BX.calendar.ValueToString(value, bTime)
								}
							}
						],
						{
							callbackOnSuccess : (function(node, taskId, value){
								return function(reply){
									if (node.parentNode.parentNode.tagName === 'TD')
										node.parentNode.parentNode.innerHTML = tasksListNS.renderDeadline(taskId, value, true);
									else
										node.parentNode.innerHTML = tasksListNS.renderDeadline(taskId, value, true);
								};
							})(node, taskId, value)
						}
					);
				};
			})(node, taskId)
		});
	},

	renderDeadline : function(taskId, deadlineIn, allowEdit)
	{
		if (deadlineIn)
		{
			var deadlineIn = BX.calendar.ValueToString(deadlineIn, true);

			if (BX.isAmPmMode())
			{
				deadline = deadlineIn.substr(11, 11) == "12:00:00 am" ? deadlineIn.substr(0, 10) : deadlineIn.substr(0, 22);
			}
			else
			{
				deadline = deadlineIn.substr(11, 8) == "00:00:00" ? deadlineIn.substr(0, 10) : deadlineIn.substr(0, 19);
			}
			deadline = deadline.split(' ');
			if (deadline.length > 1)
			{
				var date = deadline[0];
				delete(deadline[0]);
				var time = deadline.join(' ');
				deadlineHtml = '<span class="task-deadline-datetime"><span class="task-deadline-date webform-field-action-link" onclick="tasksListNS.onDeadlineChangeClick(' + taskId + ', this, \'' + deadlineIn + '\');">' + date + '</span></span> <span class="task-deadline-time webform-field-action-link" onclick="tasksListNS.onDeadlineChangeClick(' + taskId + ', this, \'' + deadlineIn + '\');">' + time + '</span>';
			}
			else
			{
				if (allowEdit)
					deadlineHtml = '<span class="task-deadline-date webform-field-action-link" onclick="tasksListNS.onDeadlineChangeClick(' + taskId + ', this, \'' + deadlineIn + '\');">' + deadline[0] + '</span>';
				else
					deadlineHtml = '<span class="task-deadline-date">' + deadline[0] + '</span>';
			}
		}
		else
		{
			var deadlineHtml = "&nbsp;"
		}

		return deadlineHtml;
	},

	onColumnResize : function(columnId, columnWidth, columnsContextId)
	{
		var data = {
			'sessid'          :  BX.message('bitrix_sessid'),
			'mode'            : 'resizeColumn',
			'columnId'        :  columnId,
			'columnWidth'     :  columnWidth,
			'columnContextId' :  columnsContextId
		};

		BX.ajax({
			'method'      : 'POST',
			'dataType'    : 'json',
			'url'         :  tasksListAjaxUrl,
			'data'        :  data,
			'async'       :  true,
			'processData' :  false,
			'onsuccess'   : function(){}
		});
	},

	onColumnMoved : function(prevCellIndex, newCellIndex)
	{
		var i, iMin, iMax, row, tmp1, tmp2, movedColumnId, 
			movedColumnHiddenInput, movedAfterColumnId, leftColumnHiddenInput;

		if (newCellIndex == prevCellIndex)
			return;

		movedColumnHiddenInput = BX.findChild(
			this.table.rows[0].cells[newCellIndex],
			{tagName: "input"},
			true,		// recursive
			false		// get_all
		);

		movedColumnId = parseInt(movedColumnHiddenInput.value);

		if (newCellIndex == 1)
		{
			movedAfterColumnId = 0;
		}
		else
		{
			leftColumnHiddenInput = BX.findChild(
				this.table.rows[0].cells[newCellIndex - 1],
				{tagName: "input"},
				true,		// recursive
				false		// get_all
			);

			movedAfterColumnId = parseInt(leftColumnHiddenInput.value);
		}

		// restore table TH's content just before column moving
		iMin = Math.min(prevCellIndex, newCellIndex);
		iMax = Math.max(prevCellIndex, newCellIndex);
		row = this.table.rows[0];
		if (newCellIndex > prevCellIndex)
		{
			tmp1 = BX.findChild(row.cells[iMax], {tagName: 'DIV'}, false, false);
			for (i = iMin; i < iMax; ++i)
			{
				tmp2 = BX.findChild(row.cells[i], {tagName: 'DIV'}, false, false);
				tmp1.style.width = row.cells[i].offsetWidth + 'px';
				row.cells[i].insertBefore(tmp1, tmp2);
				tmp1 = tmp2;
			}
			tmp1.style.width = row.cells[iMax].offsetWidth + 'px';
			row.cells[iMax].appendChild(tmp1);
		}
		else
		{
			tmp1 = BX.findChild(row.cells[iMin], {tagName: 'DIV'}, false, false);
			for (i = iMax; i > iMin; --i)
			{
				tmp2 = BX.findChild(row.cells[i], {tagName: 'DIV'}, false, false);
				tmp1.style.width = row.cells[i].offsetWidth + 'px';
				row.cells[i].insertBefore(tmp1, tmp2);
				tmp1 = tmp2;
			}
			tmp1.style.width = row.cells[iMin].offsetWidth + 'px';
			row.cells[iMin].appendChild(tmp1);
		}

		// move column
		iMax = this.table.rows.length - 1;
		for (i = 0; i <= iMax; ++i)
		{
			row = this.table.rows[i];

			// skip rows with merged columns
			if (row.cells.length == 1)
				continue;

			if (newCellIndex < prevCellIndex)
				row.insertBefore(row.cells[prevCellIndex], row.cells[newCellIndex]);
			else
				row.insertBefore(row.cells[prevCellIndex], row.cells[newCellIndex + 1]);
		}

		var data = {
			'sessid'             :  BX.message('bitrix_sessid'),
			'mode'               : 'moveColumnAfter',
			'movedColumnId'      :  movedColumnId,
			'movedAfterColumnId' :  movedAfterColumnId,
			'columnContextId'    :  this.columnsContextId
		};

		BX.ajax({
			'method'      : 'POST',
			'dataType'    : 'json',
			'url'         :  tasksListAjaxUrl,
			'data'        :  data,
			'async'       :  false,
			'processData' :  false,
			'onsuccess'   : (function(self){
				return function(){
					window.setTimeout(
						function(){
							self.objResizer.reinit();
						},
						100
					);
				};
			})(this)
		});
	},

	onColumnAddRemove : function(columnsContextId)
	{
		var i;
		var selectedColumnsIds;

		if (this.initialColumnsMetaDataHash === JSON.stringify(this.columnsMetaData))
			return;		// nothing to do, columns was not changed

		selectedColumnsIds = [];

		for (i = 0; i < this.columnsMetaData.length; ++i)
		{
			if (this.columnsMetaData[i].isSelected)
				selectedColumnsIds.push(this.columnsMetaData[i].id);
		}

		if (selectedColumnsIds.length == 0)
			selectedColumnsIds.push(2);		// column 'TITLE'

		var data = {
			'sessid'          :  BX.message('bitrix_sessid'),
			'mode'            : 'addRemoveColumns',
			'selectedColumns' :  selectedColumnsIds,
			'columnContextId' :  columnsContextId
		};

		BX.ajax({
			'method'      : 'POST',
			'dataType'    : 'json',
			'url'         :  tasksListAjaxUrl,
			'data'        :  data,
			'async'       :  true,
			'processData' :  false,
			'onsuccess'   : function(){
				location.reload(false);
			}
		});
	},

	onResetToDefaultColumns : function(columnsContextId)
	{
		var data = {
			'sessid'          :  BX.message('bitrix_sessid'),
			'mode'            : 'resetColumnsToDefault',
			'columnContextId' :  columnsContextId
		};

		BX.ajax({
			'method'      : 'POST',
			'dataType'    : 'json',
			'url'         :  tasksListAjaxUrl,
			'data'        :  data,
			'async'       :  true,
			'processData' :  false,
			'onsuccess'   : function(){
				location.reload(false);
			}
		});
	},

	isAnyCheckboxChecked : function()
	{
		var i, cnt;
		var isChecked = false;

		if (this.groupActionSelectAll.checked)
			isChecked = true;
		else
		{
			cnt = this.groupActionCheckboxes.length;

			for (i=0; i < cnt; ++i)
			{
				if (this.groupActionCheckboxes[i].checked)
				{
					isChecked = true;
					break;
				}
			}
		}

		return (isChecked);
	},

	onCheckboxChanged : function(checkbox, bSkipRefresh)
	{
		if ( ! bSkipRefresh )
			this.refreshCheckboxes();

		this.lastCheckboxIndex = null;

		if (this.isAnyCheckboxChecked())
		{
			BX.removeClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');
			BX.addClass(BX('task-table-footer'), 'task-footer-btn-active');
		}
		else
		{
			BX.addClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');
			BX.removeClass(BX('task-table-footer'), 'task-footer-btn-active');
		}
	},

	onCheckboxClick : function(e, checkbox)
	{
		var i, iMax, clickedCheckboxIndex = null, checked;

		this.refreshCheckboxes();

		checked = checkbox.checked;

		iMax = this.groupActionCheckboxes.length;

		for (i = 0; i < iMax; ++i)
		{
			if (this.groupActionCheckboxes[i].id == checkbox.id)
			{
				clickedCheckboxIndex = i;
				break;
			}
		}

		if (e.shiftKey && clickedCheckboxIndex && (this.lastCheckboxIndex !== null))
		{
			iMax = Math.max(this.lastCheckboxIndex, clickedCheckboxIndex);
			i    = Math.min(this.lastCheckboxIndex, clickedCheckboxIndex)

			for (;i <= iMax; ++i)
			{
				this.groupActionCheckboxes[i].checked = checked;
			}
		}

		this.onCheckboxChanged(checkbox, true);

		this.lastCheckboxIndex = clickedCheckboxIndex;
	},

	getColumnsOrder : function()
	{
		var i, iMax, hiddenInput, columnsOrder = [];

		if ( ! this.table )
			return ([2, 5, 4, 3]);	// COLUMN_TITLE, COLUMN_DEADLINE, COLUMN_RESPONSIBLE, COLUMN_ORIGINATOR

		// skip first & last column
		iMax = this.table.rows[0].cells.length - 2;
		for (i = 1; i <= iMax; ++i)
		{
			hiddenInput = BX.findChild(
				this.table.rows[0].cells[i],
				{tagName: "input"},
				true,		// recursive
				false		// get_all
			);

			// push columnId
			if(BX.type.isDomNode(hiddenInput))
				columnsOrder.push(parseInt(hiddenInput.value));
		}

		return (columnsOrder);
	},

	getColumnIndex : function(columnName)
	{
		var i, iMax, columnId, columns;

		columns = this.getColumnsOrder();

		switch (columnName)
		{
			case 'title':
				columnId = 2;	// CTaskColumnList::COLUMN_TITLE
			break;

			default:
				columnId = 0;
			break;
		}

		if (columnId == 0)
			return (false);

		iMax = columns.length;

		for (i = 0; i < iMax; ++i)
		{
			if (columns[i] == columnId)
				return i;
		}

		return false;
	},

	onPopupTaskChanged : function(task, action, params, newDataPack, legacyHtmlTaskItem)
	{
		var currentRow, newRow, tmpRow;

		var currentParentId = null, currentParent, currentProjectId = null;
		var newDepth = 0;
		var currentDepth;
		var taskChildCountSpan;

		tasksMenuPopup[task.id] = task.menuItems;
		quickInfoData[task.id]  = task;

		if (
			(typeof legacyHtmlTaskItem === 'undefined')
			|| ( ! legacyHtmlTaskItem )
		)
		{
			return;
		}

		currentRow = BX("task-" + task.id);
		if ( ! currentRow )
			return;

		currentDepth = Depth(currentRow);

		if (currentDepth > 0 && (currentParent = BX.findPreviousSibling(currentRow, {className: "task-depth-" + (currentDepth - 1)})))
		{
			currentParentId = currentParent.id.replace("task-", "");
		}

		tmpRow = currentRow;

		if (BX('task-current-project') && (BX('task-current-project').value > 0))
			currentProjectId = BX('task-current-project').value;
		else
		{
			do {
				if (tmpRow.id.substr(0, 13) == "task-project-")
				{
					currentProjectId = tmpRow.id.replace("task-project-", "");
				}
				tmpRow = BX.findPreviousSibling(tmpRow, {tagName: "tr"});
			} while (tmpRow && !currentProjectId);
		}

		if (legacyHtmlTaskItem)
		{
			tempDiv = document.createElement("div");
			tempDiv.innerHTML = "<table>" + legacyHtmlTaskItem + "</table>";

			newRow    = tempDiv.firstChild.rows[0];
			scriptRaw = tempDiv.firstChild.getElementsByTagName("SCRIPT")[0];

			BX.removeClass(newRow, 'task-depth-0');
			BX.addClass(newRow, 'task-depth-' + currentDepth);

			if (BX.hasClass(currentRow, 'task-list-item-opened'))
				BX.addClass(newRow, 'task-list-item-opened');

			currentRow.parentNode.replaceChild(newRow, currentRow);

			if (
				(!BX.browser.IsIE())
				|| (!!document.documentMode && document.documentMode >= 10)
			)
			{
				script = BX.create(
					"script", {
						props : {type : "text/javascript"},
						html: scriptRaw.innerHTML
					}
				)
			}
			else
			{
				script = scriptRaw;
			}

			this.table.appendChild(script);

			if (currentParentId != task.parentTaskId)
			{
				if (currentParent)
				{
					taskChildCountSpan = BX('task-children-count-' + currentParentId);
					taskChildCountSpan.innerHTML = parseInt(taskChildCountSpan.innerHTML) - 1;

					if (parseInt(taskChildCountSpan.innerHTML) ==  0)
					{
						BX.remove(taskChildCountSpan.parentNode);
						BX.removeClass(currentParent, "task-list-item-opened");
					}
				}
				
				if (task.parentTaskId && BX("task-" + task.parentTaskId))
					newDepth = Depth(BX("task-" + task.parentTaskId)) + 1;
				else
					newDepth = 0;

				if (newDepth != currentDepth)
				{
					BX.removeClass(newRow, "task-depth-" + currentDepth);
					BX.addClass(newRow, "task-depth-" + newDepth);
				}
			}
			else
				newDepth = currentDepth;

			if (
				(	// need reattach to another place?
					(currentParentId != task.parentTaskId)
					&& BX('task-' + task.parentTaskId)
				)
				|| (newDepth == 0 && currentProjectId != task.projectId))
			{
				beforeRow = __FindBeforeRow(this.table, task.parentTaskId, {id: task.projectId, title: task.projectName});

				// reattach
				if (beforeRow && (beforeRow != newRow))
				{
					beforeRow.parentNode.insertBefore(newRow, beforeRow);
				}
				else if ( ! beforeRow )
				{
					this.table.appendChild(newRow);
				}
			}
		}
	},

	onPopupTaskAdded : function(task, action, params, newDataPack, legacyHtmlTaskItem)
	{
		var row, tempDiv, scriptRaw, script;

		// detailTaksID inited when list loaded from tasks.task.detail
		if (typeof(detailTaksID) == "undefined" || detailTaksID == task.parentTaskId)
		{
			if(typeof legacyHtmlTaskItem != 'undefined')
			{
				tasksMenuPopup[task.id] = task.menuItems;
				quickInfoData[task.id] = task;
				BX.onCustomEvent("onTaskListTaskAdd", [task]);

				tempDiv = document.createElement("div");
				tempDiv.innerHTML = "<table>" + legacyHtmlTaskItem + "</table>";

				row       = tempDiv.firstChild.rows[0];
				scriptRaw = tempDiv.firstChild.getElementsByTagName("SCRIPT")[0];

				if (!BX(row.id))
				{
					beforeRow = __FindBeforeRow(this.table, task.parentTaskId, {id: task.projectId, title: task.projectName});

					if (beforeRow)
					{
						beforeRow.parentNode.insertBefore(row, beforeRow);
					}
					else
					{
						this.table.appendChild(row);
					}

					if (
						(!BX.browser.IsIE())
						|| (!!document.documentMode && document.documentMode >= 10)
					)
					{
						script = BX.create(
							"script", {
								props : {type : "text/javascript"},
								html: scriptRaw.innerHTML
							}
						)
					}
					else
					{
						script = scriptRaw;
					}

					if (beforeRow)
					{
						beforeRow.parentNode.insertBefore(script, beforeRow);
					}
					else
					{
						this.table.appendChild(script);
					}
				}

				if ((typeof(params) == 'object') && (params !== null))
				{
					if (typeof(params.callbackOnAfterAdd) == 'function')
						params.callbackOnAfterAdd();
				}

			}
		}

		if (BX("task-list-no-tasks"))
			BX("task-list-no-tasks").style.display = "none";
	},

	refreshCheckboxes : function()
	{
		var checkboxes, actionCheckboxes = [];

		checkboxes = BX.findChild(
			this.table,
			{ tagName: "input" },
			true,		// recursive
			true		// get_all
		);

		for (i in checkboxes)
		{
			if ( ! checkboxes.hasOwnProperty(i) )
				continue;

			if (checkboxes[i].name === this.groupActionCheckboxesName)
				actionCheckboxes.push(checkboxes[i]);
		}

		this.groupActionCheckboxes = actionCheckboxes;
	},

	onActionSelect : function(selector)
	{
		var action, selectors;

		action = selector.options[selector.selectedIndex].value;

		BX('task-list-group-action-days_count-selector').disabled      =  true;
		BX('task-list-group-action-days_count-selector').style.display = 'none';
		BX('task-list-group-action-days_type-selector').disabled       =  true;
		BX('task-list-group-action-days_type-selector').style.display  = 'none';

		BX('task-list-group-action-user-selector').disabled      =  true;
		BX('task-list-group-action-user-selector').style.display = 'none';

		BX('task-list-group-action-date-selector').disabled      =  true;
		BX('task-list-group-action-date-selector').parentNode.style.display = 'none';
		BX.removeClass(BX('task-list-group-action-date-selector').parentNode, 'task-table-footer-inp-del');

		BX('task-list-group-action-group-selector').disabled      =  true;
		BX('task-list-group-action-group-selector').parentNode.style.display = 'none';
		BX.removeClass(BX('task-list-group-action-group-selector').parentNode, 'task-table-footer-inp-del');

		BX('task-list-group-action-value').value = '';

		if (
			(action === 'change_responsible')
			|| (action === 'change_originator')
			|| (action === 'add_auditor')
			|| (action === 'add_accomplice')
		)
		{
			if (this.userSelector === null)
			{
				BX('task-list-group-action-user-selector').value =  this.curUserName;
				BX('task-list-group-action-value').value         =  BX.message('USER_ID');
				BX.addClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');

				selectors = BX.Tasks.lwPopup.__initSelectors([{
					requestedObject   : 'intranet.user.selector.new',
					selectedUsersIds  :  BX.message('USER_ID'),
					anchorId          : 'task-list-group-action-user-selector',
					bindClickTo       : 'task-list-group-action-user-selector',
					userInputId       : 'task-list-group-action-user-selector',
					multiple          : 'N',
					GROUP_ID_FOR_SITE :  0,
					callbackOnSelect  :  function (arUser)
					{
						BX('task-list-group-action-value').value = arUser.id;
					},
					onLoadedViaAjax : (function(self){
						return function()
						{
							BX('task-list-group-action-user-selector').disabled      = false;
							BX('task-list-group-action-user-selector').style.display = '';

							self.refreshCheckboxes();
							if (self.isAnyCheckboxChecked())
								BX.removeClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');
						};
					})(this)
				}]);

				this.userSelector = selectors[0];
			}
			else
			{
				BX('task-list-group-action-user-selector').disabled      = false;
				BX('task-list-group-action-user-selector').style.display = '';
			}
		}
		else if (
			(action === 'adjust_deadline')
			|| (action === 'substract_deadline')
		)
		{
			if (
				(BX('task-list-group-action-days_count-selector').value == '')
				|| (BX('task-list-group-action-days_count-selector').value == 0)
			)
			{
				BX.addClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');
			}
			else
			{
				self.refreshCheckboxes();
				if (this.isAnyCheckboxChecked())
					BX.removeClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');
			}

			BX('task-list-group-action-days_count-selector').disabled      =  false;
			BX('task-list-group-action-days_count-selector').style.display = '';
			BX('task-list-group-action-days_type-selector').disabled       =  false;
			BX('task-list-group-action-days_type-selector').style.display  = '';
		}
		else if (action === 'set_deadline')
		{
			BX('task-list-group-action-value').value                 = '';
			BX('task-list-group-action-date-selector').value         = '';
			BX('task-list-group-action-date-selector').disabled      =  false;
			BX('task-list-group-action-date-selector').parentNode.style.display = '';

			BX.bind(
				BX('task-list-group-action-date-delete'),
				'click',
				function(){
					BX('task-list-group-action-value').value = '';
					BX('task-list-group-action-date-selector').value = '';
					BX.removeClass(BX('task-list-group-action-date-selector').parentNode, 'task-table-footer-inp-del');
				}
			);
		}
		else if (action === 'set_group')
		{
			BX('task-list-group-action-value').value = '';

			if (this.groupSelector === null)
			{
				BX('task-list-group-action-group-selector').disabled      =  true;
				BX('task-list-group-action-group-selector').parentNode.style.display = '';
				BX('task-list-group-action-group-selector').value = BX.message('TASKS_LIST_GROUP_ACTION_PLEASE_WAIT');
				BX.addClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');

				BX.Tasks.lwPopup.__initSelectors([{
					requestedObject  : 'socialnetwork.group.selector',
					bindElement      : 'task-list-group-action-group-selector',
					callbackOnSelect : function (arGroups, params)
					{
						if (arGroups && arGroups[0])
						{
							BX('task-list-group-action-value').value = arGroups[0]['id'];
							BX('task-list-group-action-group-selector').value = arGroups[0]['title'];
							BX.addClass(BX('task-list-group-action-group-selector').parentNode, 'task-table-footer-inp-del');

						}
						else
						{
							BX('task-list-group-action-value').value = 0;
							BX('task-list-group-action-group-selector').value = '';
							BX.addClass(BX('task-list-group-action-group-selector').parentNode, 'task-table-footer-inp-del');
						}
					},
					onLoadedViaAjax : (function(self){
						return function(jsObjectName)
						{
							var wait = function(delay, timeout)
							{
								if (typeof window[jsObjectName] === 'undefined')
								{
									if (timeout > 0)
										window.setTimeout(function() { wait(delay, timeout - delay); }, delay);
								}
								else
								{
									self.groupSelector = window[jsObjectName];

									BX('task-list-group-action-group-selector').disabled = false;
									BX('task-list-group-action-group-selector').value    = '';

									self.refreshCheckboxes();
									if (self.isAnyCheckboxChecked())
										BX.removeClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');

									BX.bind(
										BX('task-list-group-action-group-selector'),
										'click',
										function(){
											self.groupSelector.show();
										}
									);

									BX.bind(
										BX('task-list-group-action-group-delete'),
										'click',
										function(){
											BX('task-list-group-action-value').value = 0;
											BX('task-list-group-action-group-selector').value = '';
											BX.removeClass(BX('task-list-group-action-group-selector').parentNode, 'task-table-footer-inp-del');
										}
									);
								}
							}

							wait(100, 15000);	// every 100ms, not more than 15000ms
						}
					})(this)
				}]);
			}
			else
			{
				BX('task-list-group-action-group-selector').disabled = false;
				BX('task-list-group-action-group-selector').value    = '';
				BX('task-list-group-action-group-selector').parentNode.style.display = '';
			}
		}
	},

	onGroupActionDaysChanged : function()
	{
		var selector, i, value, seconds, selectedType;

		value    = BX('task-list-group-action-days_count-selector').value;
		selector = BX('task-list-group-action-days_type-selector');

		if ((value == 0) || (value == ''))
			BX.addClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');
		else
			BX.removeClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');

		for (i = 0; i < selector.options.length; ++i)
		{
			selector.options[i].innerHTML = BX.CJSTask.getMessagePlural(
				value,
				'TASKS_LIST_GROUP_ACTION_' + selector.options[i].value.toUpperCase()
			);
		}

		selectedType = selector.options[selector.selectedIndex].value;

		if (selectedType === 'days')
			seconds = value * 3600 * 24;	// 24 hours per day, 3600 seconds per hour
		else if (selectedType === 'weeks')
			seconds = value * 3600 * 24 * 7;
		else if (selectedType === 'monthes')
			seconds = value * 3600 * 24 * 30;
		else
			seconds = value;

		BX('task-list-group-action-value').value = seconds;
	},

	submitGroupAction : function(object, subaction, additionalFields, url)
	{
		var form, elements_ids, i, len, arFilter = {}, arFilterTmp, value;

		if(!this.groupActionCheckboxes)
		{
			return;
		}

		if (this.groupActionSelectAll.checked)
			elements_ids = 'all';
		else
		{
			elements_ids = '0';

			len = this.groupActionCheckboxes.length;

			for (i = 0; i < len; ++i)
			{
				if (this.groupActionCheckboxes[i].checked)
					elements_ids += ',' + this.groupActionCheckboxes[i].value;
			}

			if (elements_ids === '0')
				return;
		}

		if (this.arFilter.hasOwnProperty('ONLY_ROOT_TASKS'))
		{
			arFilterTmp = JSON.parse(JSON.stringify(this.arFilter));

			for (i in arFilterTmp)
			{
				if ( ! arFilterTmp.hasOwnProperty(i) )
					continue;

				if ((i === 'SAME_GROUP_PARENT') || (i === 'ONLY_ROOT_TASKS'))
					continue;

				arFilter[i] = arFilterTmp[i];
			}

		}
		else
			arFilter = this.arFilter;

		if (
			(subaction === 'change_responsible')
			|| (subaction === 'change_originator')
			|| (subaction === 'add_auditor')
			|| (subaction === 'add_accomplice')
			|| (subaction === 'adjust_deadline')
			|| (subaction === 'substract_deadline')
			|| (subaction === 'set_deadline')
			|| (subaction === 'set_group')
		)
		{
			value = BX('task-list-group-action-value').value;
		}
		else
			value = 0;

		var formAttributes = {
			method : "POST"
		};

		if(typeof url != 'undefined')
			formAttributes.action = url;

		form = BX.create("form", {
			props : formAttributes,
			style : {
				display : "none"
			},
			children : [
				BX.create("input",{
					props : {
						name  : "sessid",
						type  : "hidden",
						value :  BX.message("bitrix_sessid")
					}
				}),
				BX.create("input",{
					props : {
						name  : "module",
						type  : "hidden",
						value : "tasks"
					}
				}),
				BX.create("input",{
					props : {
						name  : "arFilter",
						type  : "hidden",
						value :  JSON.stringify(arFilter)
					}
				}),
				BX.create("input",{
					props : {
						name  : "action",
						type  : "hidden",
						value : "group_action"
					}
				}),
				BX.create("input",{
					props : {
						name  : "subaction",
						type  : "hidden",
						value :  subaction
					}
				}),
				BX.create("input",{
					props : {
						name  : "elements_ids",
						type  : "hidden",
						value :  elements_ids
					}
				}),
				BX.create("input",{
					props : {
						name  : "value",
						type  : "hidden",
						value :  value
					}
				})
			]
		});

		if(typeof additionalFields != 'undefined')
		{
			for(var k in additionalFields)
			{
				BX.append(BX.create("input",{
					props : {
						name  : k,
						type  : "hidden",
						value : additionalFields[k]
					}
				}), form);
			}
		}

		document.body.appendChild(form);
		BX.submit(form);
	},

	onReady : function(
		table, groupActionCheckboxesName, groupActionSelectAllOnPage,
		groupActionSelectAll, minColumnWidth, lastColumnMinWidth, 
		columnsContextId, menuElement, knownColumnsIds, selectedColumnsIds,
		arFilter, curUserName
	)
	{
		var i, ii, curColumnData;
		var knownColumnsCnt;
		var selectedColumnsCnt;
		var isSelected;
		var columnsMenuItems = [];

		this.table                     = table;
		this.columnsContextId          = columnsContextId;
		this.arFilter                  = arFilter;
		this.groupActionCheckboxesName = groupActionCheckboxesName;
		this.groupActionSelectAll      = groupActionSelectAll;
		this.curUserName               = curUserName;

		knownColumnsCnt    = knownColumnsIds.length;
		selectedColumnsCnt = selectedColumnsIds.length;

		for (i = 0; i < knownColumnsCnt; ++i)
		{
			isSelected = false;

			for (ii = 0; ii < selectedColumnsCnt; ++ii)
			{
				if (selectedColumnsIds[ii] == knownColumnsIds[i])
				{
					isSelected = true;
					break;
				}
			}

			curColumnData = {
				id         : knownColumnsIds[i],
				title      : BX.message('TASKS_LIST_COLUMN_' + knownColumnsIds[i]),
				isSelected : isSelected
			};

			this.columnsMetaData.push(curColumnData);

			columnsMenuItems.push({
				id        : curColumnData.id,
				text      : curColumnData.title,
				className : curColumnData.isSelected ? 'menu-popup-item-accept' : 'menu-popup-item-task-noclass',
				onclick   : (function(self, curColumnData, id){
					return function()
					{
						curColumnData.isSelected = ! curColumnData.isSelected;

						if (curColumnData.isSelected)
							BX.addClass(self.menu.getMenuItem(id).layout.item, 'menu-popup-item-accept');
						else
							BX.removeClass(self.menu.getMenuItem(id).layout.item, 'menu-popup-item-accept');
					};
				})(this, curColumnData, curColumnData.id)
			});
		}

		this.initialColumnsMetaDataHash = JSON.stringify(this.columnsMetaData);

		if(typeof tasks_TableColumnResize != 'undefined'){
			this.objResizer = new tasks_TableColumnResize(
				table,
				minColumnWidth,
				{
					lastColumnAsElastic   : true,
					elasticColumnMinWidth : lastColumnMinWidth,
					callbackOnStopResize  : (function(columnsContextId){
						return function(result) {
							var columnId = 0;
							var columnHiddenInput = null;

							if (table.rows[0].cells[result.columnIndex])
							{
								columnHiddenInput = BX.findChild(
									table.rows[0].cells[result.columnIndex],
									{tagName: "input"},
									true,		// recursive
									false		// get_all
								);

								if (columnHiddenInput)
									columnId = parseInt(columnHiddenInput.value);
							}
							
							tasksListNS.onColumnResize(columnId, result.columnWidth, columnsContextId);
						};
					})(columnsContextId)
				}
			);
		}

		if(typeof tasks_TableColumnMove != 'undefined'){
			new tasks_TableColumnMove(
				table,
				false,
				(function(self){
					return function(prevCellIndex, newCellIndex){
						self.onColumnMoved(prevCellIndex, newCellIndex);
					};
				})(this),
				'task-head-cell-wrap',
				'task-list-th-draggable'
			);
		}

		columnsMenuItems.push({
			delimiter : true
		});

		columnsMenuItems.push({
			text    : BX.message('TASKS_LIST_MENU_RESET_TO_DEFAULT_PRESET'),
			onclick : (function(columnsContextId){
				return function()
				{
					tasksListNS.onResetToDefaultColumns(columnsContextId);
				};
			})(columnsContextId)
		});

		this.menu = BX.PopupMenu.create(
			'task-list-controls-menu',
			menuElement,
			columnsMenuItems,
			{
				closeByEsc  : false,
				zIndex      : 1010,
				autoHide    : true,
				bindOptions : {
					position : 'bottom'
				},
				events      : {
					onPopupClose : (function(columnsContextId){
						return function(){
							tasksListNS.onColumnAddRemove(columnsContextId);
						};
					})(columnsContextId)
				}
			}
		);

		BX.bind(
			menuElement,
			'click',
			(function(self){
				return function()
				{
					self.menu.popupWindow.show();
				};
			})(this)
		);

		BX.bind(
			groupActionSelectAllOnPage,
			'click',
			(function(self){
				return function(){
					var i, len;

					self.refreshCheckboxes();

					len = self.groupActionCheckboxes.length;
					
					if (this.checked)
					{
						for (i = 0; i < len; ++i)
						{
							// mark checked only visible checkboxes
							if (self.groupActionCheckboxes[i].offsetWidth > 0)
								self.groupActionCheckboxes[i].checked = true;
							else
								self.groupActionCheckboxes[i].checked = false;
						}							
					}
					else
					{
						for (i = 0; i < len; ++i)
						{
							// mark unchecked all checkboxes
							self.groupActionCheckboxes[i].checked = false;
						}							
					}

					self.onCheckboxChanged();
				};
			})(this)
		);

		BX.bind(
			groupActionSelectAll,
			'click',
			(function(self){
				return function(){
					if (groupActionSelectAll.checked && ! confirm(BX.message('TASKS_LIST_CONFIRM_ACTION_FOR_ALL_ITEMS')))
						groupActionSelectAll.checked = false;

					self.onCheckboxChanged();
				};
			})(this)
		);

		this.isReady = true;
	}
};


function __CreateProjectRow(groupObj)
{
	var addUrl = BX.message("TASKS_PATH_TO_TASK").replace("#action#", "edit").replace("#task_id#", 0);
	var groupUrl = BX.message("PATH_TO_GROUP_TASKS").replace("#group_id#", groupObj.id);
	var colSpanCount = 9;

	if ((typeof tasksListNS !== 'undefined') && tasksListNS.getColumnsOrder)
		colSpanCount = 2 + (tasksListNS.getColumnsOrder()).length;

	return BX.create("tr", {
		props: {
			className: "task-list-item task-list-project-item task-depth-0",
			id: "task-project-" + groupObj.id
		},
		attrs: {
			"data-project-id": groupObj.id
		},
		children: [
			BX.create("td", {
				props: {
					className: "task-project-column",
					colSpan: colSpanCount
				},
				html: '\
					<div class="task-project-column-inner">\
						<div class="task-project-name">\
							<span class="task-project-folding" onclick="ToggleProjectTasks(' + groupObj.id + ', event);"></span>\
							<a class="task-project-name-link" href="' + groupUrl + '" onclick="ToggleProjectTasks(' + groupObj.id + ', event);">' + BX.util.htmlspecialchars(groupObj.title) + '</a>\
						</div>\
						<div class="task-project-actions">\
							<a onclick="AddQuickPopupTask(event, {GROUP_ID: ' + groupObj.id + '});" class="task-project-action-link" href="' + addUrl + (addUrl.indexOf("?") == -1 ? "?" : "&") + 'GROUP_ID=' + groupObj.id + '">\
								<i class="task-project-action-icon"></i>\
								<span class="task-project-action-text">' + BX.message("TASKS_ADD_TASK") + '</span>\
							</a>\
						</div>\
					</div>\
				'
			})
		]
	});
}


function __FindBeforeRow(table, parentId, groupObj)
{
	var newRow = BX("task-new-item-row");
	var beforeRow, parentRow;
	if (parentId > 0 && (parentRow = BX("task-" + parentId)))
	{
		beforeRow = BX.findNextSibling(parentRow, {tagName : "tr"});

		if (BX.findChild(parentRow, {tagName: "div", className : "task-title-folding"}, true))
		{
			var span = BX.findChild(parentRow, {tagName : "span"}, true);
			span.innerHTML = parseInt(span.innerHTML) + 1;
		}
		else
		{
			var folding = "<div class=\"task-title-folding\" onclick=\"ToggleSubtasks(this.parentNode.parentNode.parentNode, " + Depth(parentRow) + ", " + parentId + ")\"><span id=\"task-children-count-" + parentId + "\">1</span></div>";
			var titleHolder = BX.findChild(parentRow, {tagName : "div", className : "task-title-container"}, true);
			titleHolder.innerHTML = folding + titleHolder.innerHTML;
			BX.addClass(parentRow, "task-list-item-opened");
			loadedTasks[parentId] = true;
		}
	}
	else if (groupObj && groupObj.id > 0)
	{
		if (BX("task-project-" + groupObj.id))
		{
			beforeRow = BX.findNextSibling(BX("task-project-" + groupObj.id), {tagName : "tr"});
		}
		else
		{
			beforeRow = null;

			for(var i = 0, count = table.rows.length; i < count; i++) {
				if(table.rows[i].id.substr(0, 13) == "task-project-")
				{
					beforeRow = table.rows[i];
					break;
				}
			}

			var projectRow = __CreateProjectRow(groupObj);
			if (beforeRow)
			{
				beforeRow.parentNode.insertBefore(projectRow, beforeRow);
			}
			else
			{
				table.appendChild(projectRow);
			}
		}
	}
	else
	{
		if (table.firstChild.id == "task-new-item-row")
		{
			beforeRow = newRow.nextSibling;
		}
		else
		{
			beforeRow = table.firstChild;
		}
	}
	
	return beforeRow;
}

function AddToFavorite(taskId, parameters)
{
	var data = {
		mode : "favorite",
		add : 1,
		sessid : BX.message("bitrix_sessid"),
		id : taskId
	};
	
	BX.ajax({
		"method": "POST",
		"dataType": "html",
		"url": tasksListAjaxUrl,
		"data":  data,
		"processData" : false,
		"onsuccess": (function(taskId){
			return function(datum) {
				//TASKS_table_view_onDeleteClick_onSuccess(taskId, datum, parameters);
			};
		})(taskId)
	});
}

function DeleteFavorite(taskId, parameters)
{
	var data = {
		mode : "favorite",
		sessid : BX.message("bitrix_sessid"),
		id : taskId
	};

	BX.ajax({
		"method": "POST",
		"dataType": "html",
		"url": tasksListAjaxUrl,
		"data":  data,
		"processData" : false,
		"onsuccess": (function(taskId){

			if(parameters.rowDelete)
			{
				return function(datum) {
					TASKS_table_view_onDeleteClick_onSuccess(taskId, datum, parameters);
				};
			}
		})(taskId)
	});
}

function DeleteTask(taskId, parameters)
{
	var data = {
		mode : "delete",
		sessid : BX.message("bitrix_sessid"),
		id : taskId
	};
	
	BX.ajax({
		"method": "POST",
		"dataType": "html",
		"url": tasksListAjaxUrl,
		"data":  data,
		"processData" : false,
		"onsuccess": (function(taskId){
			return function(datum) {
				TASKS_table_view_onDeleteClick_onSuccess(taskId, datum, parameters);
			};
		})(taskId)
	});
}


function TASKS_table_view_onDeleteClick_onSuccess(taskId, data, parameters)
{
	if (data && data.length > 0)
	{
		BX.Tasks.alert([BX.message('TASKS_LIST_GROUP_ACTION_DELETE_ERROR')]);
	}
	else
	{
		__DeleteTaskRow(taskId, parameters);
		BX.onCustomEvent('onTaskListTaskDelete', [taskId]);
	}
}

function GetRowIdByDomNode(node)
{
	var id = node.id;
	if(typeof node.id != 'undefined')
	{
		return parseInt(node.id.toString().replace('task-', ''));
	}
	else
		return false;
}

function GetChildrenCountByRowId(id)
{
	try{
		return parseInt(BX('task-children-count-'+id).innerHTML);
	}catch(e)
	{
		return false;
	}
}

function SetChildrenCount(id, count, row)
{
	var counter = BX('task-children-count-'+id);

	if(BX.type.isDomNode(counter))
	{
		if(count <= 0)
		{
			var folding = row.querySelector('.task-title-folding');
			if(BX.type.isDomNode(folding))
				BX.remove(folding);

			BX.removeClass(row, 'task-list-item-opened');
		}
		else
			BX('task-children-count-'+id).innerHTML = count;
	}
}

function __DeleteTaskRow(taskId, parameters)
{
	var row = BX("task-" + taskId);
	var depth = Depth(row);
	
	var nextDepth = 0;
	var directChild = 0;
	
	var prevRow = BX.findPreviousSibling(row, {tagName : "tr"});
	var nextRow = BX.findNextSibling(row, {tagName : "tr"});

	var modeDeleteSubtree = typeof parameters != 'undefined' && parameters['mode'] == 'delete-subtree';

	if(modeDeleteSubtree)
	{
		var tmpRow = row;
		var parentRow = null;

		// find parent
		while(tmpRow){
			if(Depth(tmpRow) < depth)
			{
				parentRow = tmpRow;
				break;
			}

			tmpRow = BX.findPreviousSibling(tmpRow, {tagName : "tr"});
		}

		tmpRow = BX.findNextSibling(row, {tagName : "tr"});
		var toRemove = null;

		// hunt down all children
		while(tmpRow)
		{
			if(Depth(tmpRow) <= depth)
				break;

			toRemove = tmpRow;
			tmpRow = BX.findNextSibling(tmpRow, {tagName : "tr"});

			BX.remove(toRemove);
		}
		BX.remove(row);

		if(parentRow !== null)
		{
			var parentId = GetRowIdByDomNode(parentRow);
			if(parentId !== false)
			{
				var parentChildCount = GetChildrenCountByRowId(parentId);
				if(parentChildCount !== false)
				{
					if(parentChildCount > 0)
						SetChildrenCount(parentId, parentChildCount - 1, parentRow);
				}
			}
		}

		//task-list-no-tasks
		if(document.querySelectorAll('#task-list-table tbody tr').length <= 2) // adjustment row + "nothing found" row
			BX.style(BX('task-list-no-tasks'), 'display', 'table-row');
	}
	else
	{
		while (
			nextRow 
			&& (nextRow.id !== 'task-list-no-tasks') 
			&& (nextDepth = Depth(nextRow)) > depth
		)
		{
			if (nextDepth == depth + 1)
			{
				directChild++;
			}
			BX.removeClass(nextRow, "task-depth-" + nextDepth);
			BX.addClass(nextRow, "task-depth-" + (nextDepth - 1));
			nextRow = BX.findNextSibling(nextRow, {tagName : "tr"});
		}
		
		if (depth > 0)
		{
			var parentRow = BX.findPreviousSibling(row, {tagName : "tr", className : "task-depth-" + (depth - 1)});
			if (parentRow)
			{
				var span = BX.findChild(parentRow, {tagName : "span"}, true);
				span.innerHTML = parseInt(span.innerHTML) - 1 + directChild;
				if (parseInt(span.innerHTML) ==  0)
				{
					BX.remove(span.parentNode);
					BX.removeClass(parentRow, "task-list-item-opened");
				}
			}
		}

		// let's count, how many rows with tasks will be after removing
		var taskRowsCount = 0;

		prevRow = BX.findPreviousSibling(row, {tagName : "tr"});
		while ( (taskRowsCount == 0) 
			&& (prevRow)
		)
		{
			if ( (prevRow.id !== 'task-list-no-tasks')
				&& (prevRow.id.substr(0, 13) !== 'task-project-' )
				&& (prevRow.id !== 'task-new-item-row')
			)
			{
				taskRowsCount = taskRowsCount + 1;
			}

			prevRow = BX.findPreviousSibling(prevRow, {tagName : "tr"})
		}

		nextRow = BX.findNextSibling(row, {tagName : "tr"});
		while ( (taskRowsCount == 0) 
			&& (nextRow)
		)
		{
			if ( (nextRow.id !== 'task-list-no-tasks')
				&& (nextRow.id.substr(0, 13) !== 'task-project-' )
				&& (nextRow.id !== 'task-new-item-row')
			)
			{
				taskRowsCount = taskRowsCount + 1;
			}

			nextRow = BX.findNextSibling(nextRow, {tagName : "tr"});
		}

		// if no more tasks in list => show phrase "there is no tasks"
		// and hide 'task-project-' rows, if exists
		if (taskRowsCount == 0)
		{
			BX('task-list-no-tasks').style.display = "";

			var bMustBeRemoved = false;
			var rowToBeRemoved = null;

			prevRow = BX.findPreviousSibling(row, {tagName : "tr"});
			while ( (taskRowsCount == 0) 
				&& (prevRow)
			)
			{
				if (prevRow.id.substr(0, 13) === 'task-project-')
				{
					bMustBeRemoved = true;
					rowToBeRemoved = prevRow;
				}
				
				prevRow = BX.findPreviousSibling(prevRow, {tagName : "tr"})

				if (bMustBeRemoved)
				{
					BX.remove (rowToBeRemoved);
					bMustBeRemoved = false;
				}
			}

			nextRow = BX.findNextSibling(row, {tagName : "tr"});
			while ( (taskRowsCount == 0) 
				&& (nextRow)
			)
			{
				if (nextRow.id.substr(0, 13) === 'task-project-')
				{
					bMustBeRemoved = true;
					rowToBeRemoved = nextRow;
				}

				nextRow = BX.findNextSibling(nextRow, {tagName : "tr"});

				if (bMustBeRemoved)
				{
					BX.remove (rowToBeRemoved);
					bMustBeRemoved = false;
				}
			}
		}

		BX.remove(row);
	}
}


function ToggleProjectTasks(projectID, e)
{
	if(!e) e = window.event;
	
	var row = BX.findNextSibling(BX("task-project-" + projectID, true), {tagName : "tr"});
	
	var span = BX.findChild(BX("task-project-" + projectID, true), {tag: "span", className: "task-project-folding"}, true);
	var bFoldingClosed = BX.hasClass(span, 'task-project-folding-closed');

	if (bFoldingClosed)
		BX.userOptions.save('tasks', 'opened_projects', projectID, true);
	else
		BX.userOptions.save('tasks', 'opened_projects', projectID, false);
	
	while(row && !BX.hasClass(row.cells[0], "task-project-column") && !BX.hasClass(row.cells[0], "task-new-item-column"))
	{
		if (bFoldingClosed)
			row.style.display = "";
		else
			row.style.display = "none";

		row =  BX.findNextSibling(row, {tagName : "tr"});
	}
	BX.toggleClass(span, 'task-project-folding-closed');
	
	BX.PreventDefault(e);
}

function SortTable(url, e)
{
	if(!e) e = window.event;
	window.location = url;
	BX.PreventDefault(e);
}


function onGroupSelect(groups)
{
	if (groups[0])
	{
		if (groups[0].title.length > 40)
		{
			groups[0].title = groups[0].title.substr(0, 40) + "...";
		}
		BX.adjust(BX("task-new-item-link-group"), {
			text: groups[0].title
		});

		var deleteIcon = BX.findChild(BX("task-new-item-link-group").parentNode, {tag: "span", className: "task-group-delete"});
		if (!deleteIcon)
		{
			deleteIcon = BX.create("span", {props: {className: "task-group-delete"}});
			BX("task-new-item-link-group").parentNode.appendChild(deleteIcon);
		}

		BX.adjust(deleteIcon, {
			events: {
				click: function(e) {
					if (!e) e = window.event;
					BX.cleanNode(this, true);
					BX.adjust(BX("task-new-item-link-group"), {
						text: BX.message("TASKS_QUICK_IN_GROUP")
					});
					groupsPopup.deselect(groups[0].id);
					newTaskGroup = 0;
					newTaskGroupObj = null;
				}
			}
		});

		newTaskGroup = groups[0].id;
		newTaskGroupObj = groups[0];
	}
}


function onPopupTaskChanged(task, action, params, newDataPack, legacyHtmlTaskItem)
{
	tasksListNS.onPopupTaskChanged(task, action, params, newDataPack, legacyHtmlTaskItem);
}


function onPopupTaskAdded(task, action, params, newDataPack, legacyHtmlTaskItem)
{
	tasksListNS.onPopupTaskAdded(task, action, params, newDataPack, legacyHtmlTaskItem);
}


function onPopupTaskDeleted(taskId)
{
	__DeleteTaskRow(taskId);
}


function __renderMark(task)
{
	if ((task.directorId == currentUser || task.isSubordinate) && task.responsibleId != currentUser)
	{
		return '<a href="javascript: void(0)" class="task-grade-and-report' + (task.mark ? ' task-grade-' + (task.mark == "N" ? "minus" : "plus") : "") + (task.isInReport ? " task-in-report" : "") + '" onclick="return ShowGradePopup(' + task.id + ', this, {listValue : \'' + (task.mark ? task.mark : "NULL") + '\'' + (task.isSubordinate ? ", report : " + (task.isInReport ? "true" : "false") : "") + '});" title="' + BX.message("TASKS_MARK") + ': ' + BX.message("TASKS_MARK_" + (task.mark ? task.mark : "NONE")) + '"><span class="task-grade-and-report-inner"><i class="task-grade-and-report-icon"></i></span></a>';
	}
	else
	{
		return '&nbsp;';
	}
}


function __renderPriority(task)
{
	if (currentUser == task.directorId)
	{
		return '<a href="javascript: void(0)" class="task-priority-box" onclick="return ShowPriorityPopup(' + task.id + ', this, ' + task.priority + ');" title="' + BX.message("TASKS_PRIORITY") + ': ' + BX.message("TASKS_PRIORITY_" + task.priority) + '"><i class="task-priority-icon task-priority-' + (task.priority == 2 ? "high" : (task.priority == 0 ? "low" : "medium")) + '"></i></a>';
	}
	else
	{
		return '<i class="task-priority-icon task-priority-' + (task.priority == 2 ? "high" : (task.priority == 0 ? "low" : "medium") + '" title="' + BX.message("TASKS_PRIORITY") + ': ' + BX.message("TASKS_PRIORITY_" + task.priority)) + '"></i>';
	}
}


function __renderDeadline(task)
{
	var bCanEditDeadline = false;

	if (task.canEditDealine)
		bCanEditDeadline = true;

	return (tasksListNS.renderDeadline(task.id, task.dateDeadline, bCanEditDeadline));
}


function __renderFlag(task)
{
	if (task.responsibleId == currentUser)
	{
		return '<a href="javascript: void(0)" class="task-flag-begin-perform" onclick="StartTask(' + task.id + ')"  title="' + BX.message("TASKS_START") + '">';
	}
	else if (task.status == "new")
	{
		return '<span class="task-flag-waiting-confirm"  title="' + BX.message("TASKS_WAINTING_CONFIRM") + '" />';
	}
	else
	{
		return '&nbsp;';
	}
}


function CloseTask(taskId)
{
	var titleColIndex, cells, title, row = BX("task-" + taskId);
	if (row)
	{
		SetCSSStatus(row, "completed", "task-status-");

		titleColIndex = tasksListNS.getColumnIndex('title');

		if (titleColIndex !== false)
		{
			cells = row.getElementsByTagName("TD");
			title = BX.findChild(cells[titleColIndex], {tagName : "a"}, true);
			BX.style(title, "text-decoration", "line-through");
		}

		if (BX('task-title-btn-start-' + taskId))
			BX('task-title-btn-start-' + taskId).innerHTML = "&nbsp;";

		var link = BX.findChild(row, {tagName : "a", className : "task-complete-action"}, true);
		if (link)
		{
			link.onclick = null;
			link.title = BX.message("TASKS_FINISHED");
		}
	}
	SetServerStatus(taskId, "close");
}


function StartTask(taskId)
{
	var row = BX("task-" + taskId);
	if (row)
	{
		SetCSSStatus(row, "in-progress", "task-status-");

		if (BX('task-title-btn-start-' + taskId))
			BX('task-title-btn-start-' + taskId).innerHTML = "<span class=\"task-flag-in-progress\"></span>";
	}
	SetServerStatus(taskId, "start");
}


function AcceptTask(taskId)
{
	var row = BX("task-" + taskId);
	if (row)
	{
		SetCSSStatus(row, "accepted", "task-status-");
		if (BX('task-title-btn-start-' + taskId))
			BX('task-title-btn-start-' + taskId).innerHTML = "&nbsp;";
	}
	SetServerStatus(taskId, "accept");
}


function PauseTask(taskId)
{
	var row = BX("task-" + taskId);
	if (row)
	{
		SetCSSStatus(row, "accepted", "task-status-");
		if (BX('task-title-btn-start-' + taskId))
			BX('task-title-btn-start-' + taskId).innerHTML = "&nbsp;";
	}
	SetServerStatus(taskId, "pause");
}


function RenewTask(taskId)
{
	var row = BX("task-" + taskId);
	if (row)
	{
		SetCSSStatus(row, "new", "task-status-");
		if (BX('task-title-btn-start-' + taskId))
			BX('task-title-btn-start-' + taskId).innerHTML = "<span class=\"task-flag-waiting-confirm\"></span>";
	}
	SetServerStatus(taskId, "renew");
}


function DeferTask(taskId)
{
	var row = BX("task-" + taskId);
	if (row)
	{
		SetCSSStatus(row, "delayed", "task-status-");
		if (BX('task-title-btn-start-' + taskId))
			BX('task-title-btn-start-' + taskId).innerHTML = "&nbsp;";
	}
	SetServerStatus(taskId, "defer");
}